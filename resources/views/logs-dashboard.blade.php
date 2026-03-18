<section class="w-full">
    <x-logs.layout>
<div class="flex min-h-0 flex-col gap-6">
    @if(!$loading && !$error && count($chartData) > 0 && count($chartSeries) > 0)
        <flux:card>
            <flux:heading size="lg">{{ __('Log volume by service') }}</flux:heading>
            <flux:chart :value="$chartData" class="mt-4">
                <flux:chart.viewport class="aspect-[12/1]">
                <flux:chart.svg>
                    @foreach($chartSeries as $service => $colors)
                        <flux:chart.line field="{{ $service }}" class="{{ $colors['line'] }}" curve="none" />
                    @endforeach
                    <flux:chart.axis axis="x" field="date" tick-count="8">
                        <flux:chart.axis.tick :format="$chartDateFormat" />
                        <flux:chart.axis.line />
                    </flux:chart.axis>
                    <flux:chart.axis axis="y">
                        <flux:chart.axis.grid />
                        <flux:chart.axis.tick />
                    </flux:chart.axis>
                    <flux:chart.cursor />
                </flux:chart.svg>
                </flux:chart.viewport>
                <flux:chart.tooltip>
                    <flux:chart.tooltip.heading field="date" :format="$chartDateFormat" />
                    @foreach($chartSeries as $service => $colors)
                        <flux:chart.tooltip.value field="{{ $service }}" label="{{ $service }}" />
                    @endforeach
                </flux:chart.tooltip>
                <div class="flex flex-wrap justify-center gap-4 pt-4">
                    @foreach($chartSeries as $service => $colors)
                        <flux:chart.legend label="{{ $service }}">
                            <flux:chart.legend.indicator class="{{ $colors['indicator'] }}" />
                        </flux:chart.legend>
                    @endforeach
                </div>
            </flux:chart>
        </flux:card>
    @endif

    <flux:card class="{{ (!$loading && !$error && count($chartData) > 0 && count($chartSeries) > 0) ? 'mt-6' : '' }}">
        <div class="flex flex-wrap items-end gap-4">
            <flux:input
                wire:model="query"
                label="{{ __('LogQL query') }}"
                :placeholder="$queryPlaceholder"
                class="min-w-[280px] flex-1"
            />
            <flux:date-picker
                mode="range"
                wire:model.live="range"
                max="today"
                with-presets
                presets="today yesterday last7Days last30Days thisMonth lastMonth"
                min="2020-01-01"
                label="{{ __('Date range') }}"
                class="shrink-0"
            />
            <flux:time-picker
                wire:model.live="startTime"
                label="{{ __('Start time') }}"
                time-format="24-hour"
                interval="15"
                class="shrink-0"
            />
            <flux:time-picker
                wire:model.live="endTime"
                label="{{ __('End time') }}"
                time-format="24-hour"
                interval="15"
                class="shrink-0"
            />
            <flux:input type="number" wire:model="limit" label="{{ __('Limit') }}" min="10" max="500" />
            <flux:select wire:model.live="selectedService" label="{{ __('Service') }}">
                <option value="">{{ __('All') }}</option>
                @foreach($services as $svc)
                    <option value="{{ $svc }}">{{ $svc }}</option>
                @endforeach
            </flux:select>
            <flux:button
                wire:click="togglePolling"
                :variant="$polling ? 'primary' : 'ghost'"
                icon="refresh-cw"
                square
                class="shrink-0 self-end"
                :aria-label="$polling ? __('Pause auto-refresh') : __('Auto-refresh')"
            />
            <flux:button wire:click="refreshLogs" variant="primary" :disabled="$loading" class="shrink-0 self-end">
                {{ $loading ? __('Loading…') : __('Refresh') }}
            </flux:button>
        </div>
    </flux:card>

    @if($error)
        <flux:card class="mt-6 border-red-500 dark:border-red-600">
            <flux:heading size="sm" class="text-red-600 dark:text-red-400">{{ __('Error') }}</flux:heading>
            <p class="mt-2 font-mono text-sm text-red-700 dark:text-red-300">{{ $error }}</p>
        </flux:card>
    @elseif($loading)
        <flux:card class="mt-6">
            <flux:skeleton.group>
                @foreach(range(1, 8) as $i)
                    <flux:skeleton.line />
                @endforeach
            </flux:skeleton.group>
        </flux:card>
    @else
        <div @if($polling) wire:poll.5s="refreshLogs" @endif>
        <flux:card class="mt-6 flex min-h-0 flex-1 flex-col">
            <flux:heading size="lg">{{ __('Entries') }} ({{ $paginator->total() }})</flux:heading>
            <div id="logs-container" class="mt-4 min-h-0 flex-1 overflow-y-auto rounded border border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900">
                @forelse($paginator->items() as $entry)
                    <div class="border-b border-zinc-200 px-3 py-2 font-mono text-xs dark:border-zinc-700 last:border-0">
                        <span class="text-zinc-500 dark:text-zinc-400">
                            {{ isset($entry['timestamp']) ? date('Y-m-d H:i:s', (int) ($entry['timestamp'] / 1000000000)) : '-' }}
                        </span>
                        @if(!empty($entry['labels']))
                            <span class="ms-2 text-accent-600 dark:text-accent-400">
                                [{{ collect($entry['labels'])->map(function ($v, $k) { return $k . '=' . $v; })->implode(' ') }}]
                            </span>
                        @endif
                        <div class="mt-1 break-all text-zinc-800 dark:text-zinc-200">{{ $entry['line'] ?? '' }}</div>
                    </div>
                @empty
                    <div class="p-4 text-center text-zinc-500 dark:text-zinc-400">{{ __('No logs found') }}</div>
                @endforelse
            </div>
            <flux:pagination :paginator="$paginator" scroll-to="#logs-container" />
        </flux:card>
        </div>
    @endif
</div>
    </x-logs.layout>
</section>
