<?php

namespace Zoyica\ZoyicaVisitor\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Zoyica\ZoyicaVisitor\Models\VisitorSession;
use Zoyica\ZoyicaVisitor\Models\VisitorEvent;

class TrackVisitorSession
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        if ($this->shouldSkip($request)) {
            return $response;
        }

        try {
            $sessionId = $request->cookie('zv_sid') ?? (string) Str::uuid();

            $session = VisitorSession::firstOrCreate(
                ['session_id' => $sessionId],
                [
                    'customer_id'  => auth('customer')->id(),
                    'ip'           => $request->ip(),
                    'user_agent'   => substr($request->userAgent() ?? '', 0, 500),
                    'device'       => $this->detectDevice($request),
                    'browser'      => $this->detectBrowser($request),
                    'os'           => $this->detectOs($request),
                    'referrer'     => substr($request->headers->get('referer', ''), 0, 500) ?: null,
                    'utm_source'   => $request->query('utm_source'),
                    'utm_medium'   => $request->query('utm_medium'),
                    'utm_campaign' => $request->query('utm_campaign'),
                    'first_seen_at' => now(),
                    'last_seen_at'  => now(),
                ]
            );

            $session->increment('page_count');
            $session->update([
                'last_seen_at' => now(),
                'customer_id'  => $session->customer_id ?? auth('customer')->id(),
            ]);

            // Record page view
            VisitorEvent::create([
                'session_id' => $sessionId,
                'event_type' => 'page_view',
                'page_url'   => $request->url(),
                'meta'       => array_filter([
                    'title'   => null,
                    'referer' => $request->headers->get('referer'),
                ]),
            ]);

            $session->increment('event_count');

            // Track search queries
            $query = $request->query('query') ?? $request->query('q');
            if ($query && str_contains($request->path(), 'search')) {
                VisitorEvent::create([
                    'session_id' => $sessionId,
                    'event_type' => 'search',
                    'page_url'   => $request->url(),
                    'meta'       => ['query' => $query],
                ]);
                $session->increment('event_count');
            }

            $response->withCookie(
                cookie()->forever('zv_sid', $sessionId, '/', null, false, false, false, null)
            );

            // Share session_id for listeners to use
            app()->instance('zv.session_id', $sessionId);

        } catch (\Throwable $e) {
            // Never break the storefront due to tracking errors
        }

        return $response;
    }

    private function shouldSkip(Request $request): bool
    {
        $path = $request->path();

        // Skip admin, API, assets, AJAX
        if (str_starts_with($path, config('app.admin_url', 'admin'))) return true;
        if (str_starts_with($path, 'api/')) return true;
        if ($request->ajax()) return true;
        if ($request->expectsJson()) return true;

        // Skip asset file extensions
        if (preg_match('/\.(css|js|png|jpg|jpeg|gif|svg|ico|woff|woff2|ttf|map)$/i', $path)) return true;

        return false;
    }

    private function detectDevice(Request $request): string
    {
        $ua = strtolower($request->userAgent() ?? '');
        if (preg_match('/ipad|tablet|kindle/i', $ua)) return 'tablet';
        if (preg_match('/mobile|android|iphone|ipod|blackberry|windows phone/i', $ua)) return 'mobile';
        return 'desktop';
    }

    private function detectBrowser(Request $request): string
    {
        $ua = $request->userAgent() ?? '';
        if (str_contains($ua, 'Edg/'))    return 'Edge';
        if (str_contains($ua, 'OPR/'))    return 'Opera';
        if (str_contains($ua, 'Chrome/')) return 'Chrome';
        if (str_contains($ua, 'Firefox/')) return 'Firefox';
        if (str_contains($ua, 'Safari/')) return 'Safari';
        return 'Other';
    }

    private function detectOs(Request $request): string
    {
        $ua = $request->userAgent() ?? '';
        if (str_contains($ua, 'Windows')) return 'Windows';
        if (str_contains($ua, 'Mac OS'))  return 'macOS';
        if (str_contains($ua, 'Android')) return 'Android';
        if (str_contains($ua, 'iPhone') || str_contains($ua, 'iPad')) return 'iOS';
        if (str_contains($ua, 'Linux'))   return 'Linux';
        return 'Other';
    }
}
