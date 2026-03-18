<?php

namespace AgenticMorf\FluxUILoki\Livewire;

use Carbon\Carbon;
use AgenticMorf\FluxUILoki\Services\LokiService;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Session;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.app.sidebar')]
#[Title('Logs')]
class LogsDashboard extends Component
{
    use WithPagination;

    /** @var array<int, string> Tailwind text classes for chart lines */
    protected const CHART_COLORS = [
        'text-blue-500 dark:text-blue-400',
        'text-red-500 dark:text-red-400',
        'text-green-500 dark:text-green-400',
        'text-amber-500 dark:text-amber-400',
        'text-purple-500 dark:text-purple-400',
        'text-pink-500 dark:text-pink-400',
        'text-cyan-500 dark:text-cyan-400',
        'text-emerald-500 dark:text-emerald-400',
    ];

    public string $query = '{compose_service=~".+"}';

    /**
     * Date range: start and end as Y-m-d. Defaults to last 7 days.
     */
    public array $range = [];

    /**
     * Start time (H:i). Default 00:00 = start of first day.
     */
    public string $startTime = '00:00';

    /**
     * End time (H:i). Default 23:59 = end of last day.
     */
    public string $endTime = '23:59';

    public int $limit = 100;

    public int $perPage = 50;

    public array $entries = [];

    public bool $loading = false;

    public ?string $error = null;

    /**
     * Placeholder for LogQL input (avoids Blade parsing braces).
     */
    public string $queryPlaceholder = '{compose_service=~"laravel.test"} or {container_name=~"laravel.*"}';

    #[Session]
    public bool $polling = false;

    public string $selectedService = '';

    public array $services = [];

    public function togglePolling(): void
    {
        $this->polling = ! $this->polling;
    }

    public function mount(): void
    {
        if (empty($this->range['start']) || empty($this->range['end'])) {
            $this->range = [
                'start' => now()->subDays(6)->format('Y-m-d'),
                'end' => now()->format('Y-m-d'),
            ];
        }
        $this->loadServices();
        $this->refreshLogs();
    }

    public function loadServices(): void
    {
        try {
            $loki = LokiService::fromConfig();
            $label = config('fluxui-loki.service_label', 'compose_service');
            $this->services = $loki->labelValues($label);
            sort($this->services);
        } catch (\Throwable) {
            $this->services = [];
        }
    }

    public function updatedRangeStart(): void
    {
        $this->refreshLogsIfRangeValid();
    }

    public function updatedRangeEnd(): void
    {
        $this->refreshLogsIfRangeValid();
    }

    public function updatedRange(): void
    {
        $this->refreshLogsIfRangeValid();
    }

    public function updatedStartTime(): void
    {
        $this->refreshLogsIfRangeValid();
    }

    public function updatedEndTime(): void
    {
        $this->refreshLogsIfRangeValid();
    }

    protected function refreshLogsIfRangeValid(): void
    {
        if (! empty($this->range['start']) && ! empty($this->range['end'])) {
            $this->refreshLogs();
        }
    }

    public function updatedSelectedService(): void
    {
        $label = config('fluxui-loki.service_label', 'compose_service');
        if ($this->selectedService === '') {
            $this->query = '{'.$label.'=~".+"}';
        } else {
            $this->query = '{'.$label.'="'.str_replace('"', '\\"', $this->selectedService).'"}';
        }
        $this->refreshLogs();
    }

    public function refreshLogs(): void
    {
        $this->resetPage('page');
        $this->loading = true;
        $this->error = null;

        try {
            $loki = LokiService::fromConfig();
            $start = $this->range['start'] instanceof Carbon
                ? $this->range['start']->copy()
                : Carbon::parse($this->range['start']);
            $end = $this->range['end'] instanceof Carbon
                ? $this->range['end']->copy()
                : Carbon::parse($this->range['end']);
            $start->setTimeFromTimeString($this->startTime ?: '00:00');
            $end->setTimeFromTimeString($this->endTime ?: '23:59')->addMinute();
            $startNs = (int) ($start->timestamp * 1000000000);
            $endNs = (int) ($end->timestamp * 1000000000);
            $response = $loki->queryRange($this->query, $this->limit, $startNs, $endNs, 'backward');
            $this->entries = $loki->parseEntries($response);
        } catch (\Throwable $e) {
            $this->error = $e->getMessage();
            $this->entries = [];
        }

        $this->loading = false;
    }

    /**
     * Aggregate entries into chart data: time buckets with log counts per service.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getChartData(): array
    {
        if (empty($this->range['start']) || empty($this->range['end']) || empty($this->entries)) {
            return [];
        }

        $label = config('fluxui-loki.service_label', 'compose_service');
        $start = Carbon::parse($this->range['start'])->setTimeFromTimeString($this->startTime ?: '00:00');
        $end = Carbon::parse($this->range['end'])->setTimeFromTimeString($this->endTime ?: '23:59');
        $totalSeconds = $start->diffInSeconds($end);
        $bucketCount = min(48, max(12, (int) ceil($totalSeconds / 3600)));
        $bucketSeconds = (int) ceil($totalSeconds / $bucketCount);

        $buckets = [];
        $cursor = $start->copy();
        for ($i = 0; $i < $bucketCount; $i++) {
            $bucketStart = $cursor->timestamp;
            $buckets[] = [
                'date' => $cursor->format('Y-m-d\TH:i:s'),
                '_start' => $bucketStart,
                '_end' => $bucketStart + $bucketSeconds,
            ];
            $cursor->addSeconds($bucketSeconds);
        }

        $serviceNames = [];
        foreach ($this->entries as $entry) {
            $ts = (int) (($entry['timestamp'] ?? 0) / 1000000000);
            $svc = $entry['labels'][$label] ?? 'unknown';
            $serviceNames[$svc] = true;

            foreach ($buckets as $idx => $row) {
                if ($ts >= $row['_start'] && $ts < $row['_end']) {
                    $buckets[$idx][$svc] = ($buckets[$idx][$svc] ?? 0) + 1;
                    break;
                }
            }
        }

        foreach (array_keys($serviceNames) as $svc) {
            foreach ($buckets as $idx => $row) {
                if (! isset($buckets[$idx][$svc])) {
                    $buckets[$idx][$svc] = 0;
                }
            }
        }

        return array_map(function ($row) {
            unset($row['_start'], $row['_end']);

            return $row;
        }, $buckets);
    }

    /**
     * Services with their chart colors for lines and legend indicators.
     *
     * @return array<string, array{line: string, indicator: string}>
     */
    public function getChartSeries(): array
    {
        $label = config('fluxui-loki.service_label', 'compose_service');
        $services = [];
        foreach ($this->entries as $entry) {
            $svc = $entry['labels'][$label] ?? 'unknown';
            $services[$svc] = true;
        }
        $services = array_keys($services);
        sort($services);

        $lineColors = self::CHART_COLORS;
        $indicatorColors = [
            'bg-blue-500',
            'bg-red-500',
            'bg-green-500',
            'bg-amber-500',
            'bg-purple-500',
            'bg-pink-500',
            'bg-cyan-500',
            'bg-emerald-500',
        ];
        $result = [];
        foreach ($services as $i => $svc) {
            $result[$svc] = [
                'line' => $lineColors[$i % count($lineColors)],
                'indicator' => $indicatorColors[$i % count($indicatorColors)],
            ];
        }

        return $result;
    }

    public function render()
    {
        $lastPage = (int) max(1, ceil(count($this->entries) / $this->perPage));
        $page = min($this->getPage('page'), $lastPage);
        $paginator = new LengthAwarePaginator(
            array_slice($this->entries, ($page - 1) * $this->perPage, $this->perPage),
            count($this->entries),
            $this->perPage,
            $page,
            ['path' => request()->url(), 'pageName' => 'page']
        );

        return view('fluxui-loki::logs-dashboard', [
            'paginator' => $paginator,
            'chartData' => $this->getChartData(),
            'chartSeries' => $this->getChartSeries(),
            'chartDateFormat' => ['month' => 'short', 'day' => 'numeric', 'hour' => '2-digit', 'minute' => '2-digit'],
        ]);
    }
}
