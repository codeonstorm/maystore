<?php

namespace Zoyica\ZoyicaVisitor\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Zoyica\ZoyicaVisitor\Models\VisitorSession;
use Zoyica\ZoyicaVisitor\Models\VisitorEvent;

class AnalyticsController extends Controller
{
    public function index(Request $request)
    {
        $days  = (int) $request->query('days', 7);
        $since = now()->subDays($days)->startOfDay();

        // Summary cards
        $totalSessions   = VisitorSession::where('first_seen_at', '>=', $since)->count();
        $uniqueIps       = VisitorSession::where('first_seen_at', '>=', $since)->distinct('ip')->count('ip');
        $totalPageViews  = VisitorEvent::where('event_type', 'page_view')->where('created_at', '>=', $since)->count();
        $conversions     = VisitorSession::where('first_seen_at', '>=', $since)->where('is_converted', true)->count();
        $cartAdds        = VisitorEvent::where('event_type', 'cart_add')->where('created_at', '>=', $since)->count();
        $cartRemoves     = VisitorEvent::where('event_type', 'cart_remove')->where('created_at', '>=', $since)->count();
        $cartAbandoned   = max(0, $cartAdds - $conversions);
        $conversionRate  = $cartAdds > 0 ? round(($conversions / $cartAdds) * 100, 1) : 0;

        // Device breakdown
        $devices = VisitorSession::where('first_seen_at', '>=', $since)
            ->select('device', DB::raw('count(*) as total'))
            ->groupBy('device')
            ->pluck('total', 'device');

        // Browser breakdown
        $browsers = VisitorSession::where('first_seen_at', '>=', $since)
            ->select('browser', DB::raw('count(*) as total'))
            ->groupBy('browser')
            ->orderByDesc('total')
            ->pluck('total', 'browser');

        // Top pages
        $topPages = VisitorEvent::where('event_type', 'page_view')
            ->where('created_at', '>=', $since)
            ->select('page_url', DB::raw('count(*) as views'))
            ->groupBy('page_url')
            ->orderByDesc('views')
            ->limit(15)
            ->get();

        // Top searches
        $topSearches = VisitorEvent::where('event_type', 'search')
            ->where('created_at', '>=', $since)
            ->get()
            ->map(fn ($e) => $e->meta['query'] ?? null)
            ->filter()
            ->countBy()
            ->sortDesc()
            ->take(15);

        // Daily visitors chart data (last N days)
        $dailyVisitors = VisitorSession::where('first_seen_at', '>=', now()->subDays($days))
            ->select(DB::raw('DATE(first_seen_at) as date'), DB::raw('count(*) as total'))
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('total', 'date');

        // Recent sessions
        $recentSessions = VisitorSession::with(['events' => fn ($q) => $q->latest('created_at')->limit(5)])
            ->orderByDesc('last_seen_at')
            ->limit(20)
            ->get();

        return view('zoyica-visitor::admin.analytics.index', compact(
            'days', 'totalSessions', 'uniqueIps', 'totalPageViews',
            'conversions', 'cartAdds', 'cartRemoves', 'cartAbandoned', 'conversionRate',
            'devices', 'browsers', 'topPages', 'topSearches', 'dailyVisitors', 'recentSessions'
        ));
    }
}
