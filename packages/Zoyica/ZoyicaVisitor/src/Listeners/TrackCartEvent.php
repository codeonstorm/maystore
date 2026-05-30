<?php

namespace Zoyica\ZoyicaVisitor\Listeners;

use Zoyica\ZoyicaVisitor\Models\VisitorEvent;
use Zoyica\ZoyicaVisitor\Models\VisitorSession;

class TrackCartEvent
{
    public function onAdd($cart): void
    {
        $sessionId = app()->bound('zv.session_id') ? app('zv.session_id') : null;
        if (! $sessionId) return;

        $item = is_object($cart) ? $cart->items?->last() : null;

        VisitorEvent::create([
            'session_id' => $sessionId,
            'event_type' => 'cart_add',
            'page_url'   => request()->url(),
            'meta'       => [
                'product_id'   => $item?->product_id,
                'product_name' => $item?->name,
                'qty'          => $item?->quantity,
                'price'        => $item?->price,
            ],
        ]);

        VisitorSession::where('session_id', $sessionId)->increment('event_count');
    }

    public function onRemove($itemId): void
    {
        $sessionId = app()->bound('zv.session_id') ? app('zv.session_id') : null;
        if (! $sessionId) return;

        VisitorEvent::create([
            'session_id' => $sessionId,
            'event_type' => 'cart_remove',
            'page_url'   => request()->url(),
            'meta'       => ['item_id' => $itemId],
        ]);

        VisitorSession::where('session_id', $sessionId)->increment('event_count');
    }
}
