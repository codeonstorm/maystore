<x-admin::layouts>
    <x-slot:title>Visitor Analytics</x-slot:title>

    <div class="flex gap-4 justify-between items-center max-sm:flex-wrap">
        <p class="text-xl text-gray-800 dark:text-white font-bold">
            Visitor Analytics
        </p>
        <div class="flex gap-2">
            @foreach ([7 => 'Last 7 days', 30 => 'Last 30 days', 90 => 'Last 90 days'] as $d => $label)
                <a href="?days={{ $d }}"
                   class="px-3 py-1.5 rounded text-sm border transition-all
                       {{ $days == $d ? 'bg-navyBlue text-white border-navyBlue' : 'bg-white text-gray-600 border-gray-200 hover:border-navyBlue' }}">
                    {{ $label }}
                </a>
            @endforeach
        </div>
    </div>

    {{-- Summary Cards --}}
    <div class="mt-6 grid grid-cols-2 gap-4 lg:grid-cols-4">
        @foreach ([
            ['Sessions',   $totalSessions,  'text-indigo-700',  'bg-indigo-50',  'icon-users'],
            ['Unique IPs', $uniqueIps,      'text-cyan-700',    'bg-cyan-50',    'icon-location'],
            ['Page Views', $totalPageViews, 'text-emerald-700', 'bg-emerald-50', 'icon-eye'],
            ['Orders',     $conversions,    'text-rose-700',    'bg-rose-50',    'icon-orders'],
        ] as [$label, $value, $textCls, $bgCls, $icon])
        <div class="p-5 bg-white dark:bg-gray-900 rounded box-shadow flex items-center gap-4">
            <div class="w-12 h-12 rounded-full {{ $bgCls }} flex items-center justify-center shrink-0">
                <span class="{{ $icon }} text-xl {{ $textCls }}"></span>
            </div>
            <div>
                <p class="text-xs text-gray-500 dark:text-gray-400">{{ $label }}</p>
                <p class="text-2xl font-bold {{ $textCls }}">{{ number_format($value) }}</p>
            </div>
        </div>
        @endforeach
    </div>

    {{-- Cart Funnel Card --}}
    <div class="mt-4 p-5 bg-white dark:bg-gray-900 rounded box-shadow">
        <div class="flex items-center justify-between mb-5">
            <p class="text-base font-semibold text-gray-700 dark:text-white">Cart Funnel</p>
            <span class="text-xs px-2.5 py-1 rounded-full bg-amber-50 text-amber-700 font-semibold">
                {{ $conversionRate }}% conversion rate
            </span>
        </div>

        @php
            $funnelMax = max($cartAdds, 1);
            $steps = [
                ['Cart Adds',    $cartAdds,      'bg-amber-400',   'text-amber-700',   '#F59E0B'],
                ['Cart Removes', $cartRemoves,   'bg-orange-400',  'text-orange-700',  '#F97316'],
                ['Abandoned',    $cartAbandoned, 'bg-red-400',     'text-red-700',     '#F87171'],
                ['Converted',    $conversions,   'bg-emerald-500', 'text-emerald-700', '#10B981'],
            ];
        @endphp

        {{-- Bar metrics --}}
        <div class="grid grid-cols-2 gap-x-8 gap-y-4 lg:grid-cols-4">
            @foreach ($steps as [$label, $val, $barCls, $txtCls, $hex])
                @php $pct = round(($val / $funnelMax) * 100); @endphp
                <div class="flex flex-col gap-1.5">
                    <div class="flex justify-between items-center">
                        <span class="text-xs text-gray-500 dark:text-gray-400">{{ $label }}</span>
                        <span class="text-sm font-bold {{ $txtCls }}">{{ number_format($val) }}</span>
                    </div>
                    <div class="h-2.5 bg-gray-100 rounded-full overflow-hidden">
                        <div class="{{ $barCls }} h-2.5 rounded-full transition-all"
                             style="width: {{ $pct }}%"></div>
                    </div>
                    <span class="text-[11px] text-gray-400">{{ $pct }}% of cart adds</span>
                </div>
            @endforeach
        </div>

        {{-- Visual funnel strip --}}
        <div class="mt-5 flex rounded overflow-hidden h-7 gap-0.5">
            @foreach ($steps as [$lbl, $n, $barCls])
                @php $flex = max(1, round(($n / max(array_sum(array_column($steps, 1)), 1)) * 100)); @endphp
                <div class="{{ $barCls }} flex items-center justify-center text-white text-xs font-semibold overflow-hidden"
                     style="flex: {{ $flex }}"
                     title="{{ $lbl }}: {{ $n }}">
                    <span class="truncate px-2">{{ $lbl }} ({{ $n }})</span>
                </div>
            @endforeach
        </div>
    </div>

    <div class="mt-4 grid grid-cols-1 gap-4 lg:grid-cols-3">

        {{-- Daily Chart --}}
        <div class="lg:col-span-2 p-4 bg-white dark:bg-gray-900 rounded box-shadow">
            <p class="text-base font-semibold text-gray-700 dark:text-white mb-4">
                Daily Sessions — last {{ $days }} days
            </p>
            @if ($dailyVisitors->isEmpty())
                <p class="text-sm text-gray-400">No data yet. Visit your storefront to start tracking.</p>
            @else
                @php $max = $dailyVisitors->max() ?: 1; @endphp
                <div class="flex items-end gap-1 h-32 overflow-hidden">
                    @foreach ($dailyVisitors as $date => $count)
                        @php $pct = max(2, round(($count / $max) * 100)); @endphp
                        <div class="flex-1 flex flex-col items-center gap-1">
                            <div class="w-full rounded-t bg-indigo-500 hover:bg-indigo-600 transition-all cursor-default"
                                 style="height: {{ $pct }}%"
                                 title="{{ $date }}: {{ $count }} sessions"></div>
                            <span class="text-[9px] text-gray-400 whitespace-nowrap">
                                {{ \Carbon\Carbon::parse($date)->format('d M') }}
                            </span>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- Device + Browser --}}
        <div class="p-4 bg-white dark:bg-gray-900 rounded box-shadow">
            <p class="text-base font-semibold text-gray-700 dark:text-white mb-3">Devices</p>
            @foreach (['desktop' => '#4F46E5', 'mobile' => '#0891B2', 'tablet' => '#059669'] as $dev => $color)
                @php $cnt = $devices[$dev] ?? 0; $pct = $totalSessions > 0 ? round($cnt / $totalSessions * 100) : 0; @endphp
                <div class="mb-3">
                    <div class="flex justify-between text-sm mb-1">
                        <span class="capitalize text-gray-600 dark:text-gray-300">{{ $dev }}</span>
                        <span class="font-semibold text-gray-800 dark:text-white">{{ $cnt }} ({{ $pct }}%)</span>
                    </div>
                    <div class="h-1.5 bg-gray-100 rounded-full">
                        <div class="h-1.5 rounded-full" style="width:{{ $pct }}%;background:{{ $color }}"></div>
                    </div>
                </div>
            @endforeach

            <p class="text-base font-semibold text-gray-700 dark:text-white mt-5 mb-2">Browsers</p>
            @foreach ($browsers as $browser => $cnt)
                <div class="flex justify-between text-sm py-1 border-b border-gray-50 dark:border-gray-800">
                    <span class="text-gray-600 dark:text-gray-300">{{ $browser ?: 'Unknown' }}</span>
                    <span class="font-semibold text-gray-800 dark:text-white">{{ $cnt }}</span>
                </div>
            @endforeach
        </div>
    </div>

    <div class="mt-4 grid grid-cols-1 gap-4 lg:grid-cols-2">

        {{-- Top Pages --}}
        <div class="p-4 bg-white dark:bg-gray-900 rounded box-shadow">
            <p class="text-base font-semibold text-gray-700 dark:text-white mb-3">Top Pages</p>
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-100 dark:border-gray-800">
                        <th class="text-left py-2 text-gray-500 font-medium">Path</th>
                        <th class="text-right py-2 text-gray-500 font-medium w-16">Views</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($topPages as $page)
                        <tr class="border-b border-gray-50 dark:border-gray-800 hover:bg-gray-50 dark:hover:bg-gray-800">
                            <td class="py-2 text-gray-700 dark:text-gray-300 truncate max-w-xs"
                                title="{{ $page->page_url }}">
                                {{ parse_url($page->page_url, PHP_URL_PATH) ?: '/' }}
                            </td>
                            <td class="py-2 text-right font-semibold text-indigo-600">{{ $page->views }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="2" class="py-4 text-center text-gray-400">No data yet</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Top Searches --}}
        <div class="p-4 bg-white dark:bg-gray-900 rounded box-shadow">
            <p class="text-base font-semibold text-gray-700 dark:text-white mb-3">Top Searches</p>
            @if ($topSearches->isEmpty())
                <p class="text-sm text-gray-400">No searches recorded yet.</p>
            @else
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-gray-100 dark:border-gray-800">
                            <th class="text-left py-2 text-gray-500 font-medium">Query</th>
                            <th class="text-right py-2 text-gray-500 font-medium w-16">Count</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($topSearches as $query => $count)
                            <tr class="border-b border-gray-50 dark:border-gray-800 hover:bg-gray-50 dark:hover:bg-gray-800">
                                <td class="py-2 text-gray-700 dark:text-gray-300">{{ $query }}</td>
                                <td class="py-2 text-right font-semibold text-amber-600">{{ $count }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    </div>

    {{-- Recent Sessions --}}
    <div class="mt-4 p-4 bg-white dark:bg-gray-900 rounded box-shadow mb-6">
        <p class="text-base font-semibold text-gray-700 dark:text-white mb-3">Recent Visitor Sessions</p>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-100 dark:border-gray-800 text-gray-500 font-medium">
                        <th class="text-left py-2">Session</th>
                        <th class="text-left py-2">IP</th>
                        <th class="text-left py-2">Device</th>
                        <th class="text-left py-2">Browser / OS</th>
                        <th class="text-left py-2">Referrer</th>
                        <th class="text-center py-2">Pages</th>
                        <th class="text-center py-2">Events</th>
                        <th class="text-center py-2">Converted</th>
                        <th class="text-left py-2">Last Seen</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($recentSessions as $session)
                        <tr class="border-b border-gray-50 dark:border-gray-800 hover:bg-gray-50 dark:hover:bg-gray-800">
                            <td class="py-2 font-mono text-xs text-gray-400">
                                {{ substr($session->session_id, 0, 8) }}…
                                @if ($session->customer_id)
                                    <span class="ml-1 text-emerald-500 text-xs font-sans font-semibold">●</span>
                                @endif
                            </td>
                            <td class="py-2 text-gray-600 dark:text-gray-300">{{ $session->ip }}</td>
                            <td class="py-2">
                                <span class="px-2 py-0.5 rounded text-xs capitalize
                                    {{ $session->device === 'mobile' ? 'bg-blue-100 text-blue-700' :
                                       ($session->device === 'tablet' ? 'bg-purple-100 text-purple-700' : 'bg-gray-100 text-gray-600') }}">
                                    {{ $session->device }}
                                </span>
                            </td>
                            <td class="py-2 text-gray-600 dark:text-gray-300 text-xs">
                                {{ $session->browser }} / {{ $session->os }}
                            </td>
                            <td class="py-2 text-gray-400 text-xs truncate max-w-[140px]"
                                title="{{ $session->referrer }}">
                                {{ $session->referrer ? (parse_url($session->referrer, PHP_URL_HOST) ?? '—') : '—' }}
                            </td>
                            <td class="py-2 text-center font-semibold text-gray-700 dark:text-white">
                                {{ $session->page_count }}
                            </td>
                            <td class="py-2 text-center text-gray-500">{{ $session->event_count }}</td>
                            <td class="py-2 text-center">
                                @if ($session->is_converted)
                                    <span class="text-emerald-500 font-bold text-base">✓</span>
                                @else
                                    <span class="text-gray-300">—</span>
                                @endif
                            </td>
                            <td class="py-2 text-gray-400 text-xs">
                                {{ $session->last_seen_at?->diffForHumans() }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="py-8 text-center text-gray-400">
                                No sessions yet. Visit your storefront to start tracking.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</x-admin::layouts>
