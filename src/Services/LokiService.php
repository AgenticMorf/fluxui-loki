<?php

namespace AgenticMorf\FluxUILoki\Services;

use Illuminate\Support\Facades\Http;

class LokiService
{
    public function __construct(
        protected string $baseUrl
    ) {}

    public static function fromConfig(): self
    {
        $url = rtrim(config('fluxui-loki.url', 'http://loki:3100'), '/');

        return new self($url);
    }

    /**
     * Query Loki's query_range API.
     *
     * @param  int|null  $end  Unix timestamp in nanoseconds. Null = now.
     */
    public function queryRange(
        string $query,
        int $limit = 100,
        ?int $start = null,
        ?int $end = null,
        string $direction = 'backward'
    ): array {
        $now = (int) (microtime(true) * 1e9);
        $params = [
            'query' => $query,
            'limit' => $limit,
            'direction' => $direction,
        ];
        if ($start !== null) {
            $params['start'] = $start;
        }
        if ($end !== null) {
            $params['end'] = $end;
        } else {
            $params['end'] = $now;
        }

        $response = Http::timeout(30)
            ->get("{$this->baseUrl}/loki/api/v1/query_range", $params);

        $response->throw();

        return $response->json();
    }

    /**
     * Fetch distinct values for a label from Loki's labels API.
     *
     * @param  string|null  $query  Optional stream selector to filter (e.g. {compose_project="eyejay"}).
     * @return array<int, string>
     */
    public function labelValues(string $label, ?string $query = null): array
    {
        $params = [];
        if ($query !== null) {
            $params['query'] = $query;
        }

        $response = Http::timeout(10)
            ->get("{$this->baseUrl}/loki/api/v1/label/{$label}/values", $params);

        $response->throw();

        $data = $response->json();
        $values = $data['data'] ?? [];

        return is_array($values) ? $values : [];
    }

    /**
     * Parse Loki query_range response into a flat array of entries.
     *
     * @return array<int, array{timestamp?: int, labels?: array<string, string>, line?: string}>
     */
    public function parseEntries(array $response): array
    {
        $entries = [];
        $data = $response['data'] ?? [];
        $result = $data['result'] ?? [];

        foreach ($result as $stream) {
            $labels = $stream['stream'] ?? [];
            $values = $stream['values'] ?? [];

            foreach ($values as [$timestamp, $line]) {
                $entries[] = [
                    'timestamp' => (int) $timestamp,
                    'labels' => $labels,
                    'line' => $line,
                ];
            }
        }

        return $entries;
    }
}
