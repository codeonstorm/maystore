<?php

namespace Zoyica\ZoyicaVisitor\Listeners;

use Zoyica\ZoyicaVisitor\Models\VisitorEvent;
use Zoyica\ZoyicaVisitor\Models\VisitorSession;

class TrackOrderEvent
{
    public function handle($order): void
    {
        $sessionId = app()->bound('zv.session_id') ? app('zv.session_id') : null;
        if (! $sessionId) return;

        VisitorEvent::create([
            'session_id' => $sessionId,
            'event_type' => 'order_placed',
            'page_url'   => request()->url(),
            'meta'       => [
                'order_id'    => $order->id ?? null,
                'order_total' => $order->grand_total ?? null,
                'items_count' => $order->items?->count(),
            ],
        ]);

        VisitorSession::where('session_id', $sessionId)->update([
            'is_converted' => true,
            'event_count'  => \DB::raw('event_count + 1'),
        ]);
    }
}
