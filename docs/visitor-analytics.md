# Visitor Analytics — ZoyicaVisitor Package

A deep visitor tracking package for Bagisto that records every meaningful interaction on the storefront and surfaces it in a real-time admin dashboard.

---

## Overview

```
Visitor lands on storefront
        ↓
TrackVisitorSession middleware (runs on every web request)
        ↓
Assigns cookie zv_sid → creates/updates zv_sessions row
        ↓
Records page_view in zv_events
        ↓
Bagisto events fire (cart add, order placed, etc.)
        ↓
Listeners write to zv_events
        ↓
Admin dashboard reads & aggregates both tables
```

---

## Package Location

```
packages/Zoyica/ZoyicaVisitor/
├── src/
│   ├── Config/
│   │   └── admin-menu.php           ← sidebar menu entry
│   ├── Http/
│   │   ├── Controllers/
│   │   │   └── Admin/AnalyticsController.php
│   │   └── Middleware/
│   │       └── TrackVisitorSession.php
│   ├── Listeners/
│   │   ├── TrackCartEvent.php
│   │   └── TrackOrderEvent.php
│   ├── Models/
│   │   ├── VisitorSession.php
│   │   └── VisitorEvent.php
│   ├── Providers/
│   │   └── ZoyicaVisitorServiceProvider.php
│   ├── Resources/views/admin/analytics/
│   │   └── index.blade.php
│   └── Routes/
│       └── web.php
└── database/migrations/
    ├── 2026_05_30_000001_create_visitor_sessions_table.php
    └── 2026_05_30_000002_create_visitor_events_table.php
```

---

## Database Tables

### `zv_sessions` — one row per visitor session

| Column | Type | Description |
|---|---|---|
| `session_id` | varchar(36) | UUID assigned via cookie `zv_sid` |
| `customer_id` | bigint nullable | Linked when visitor logs in |
| `ip` | varchar(45) | Visitor IP address |
| `user_agent` | text | Raw user-agent string |
| `device` | varchar(20) | `desktop` / `mobile` / `tablet` |
| `browser` | varchar(100) | Chrome / Firefox / Safari / Edge / Opera |
| `os` | varchar(100) | Windows / macOS / Android / iOS / Linux |
| `referrer` | text | HTTP referrer URL |
| `utm_source` | varchar | UTM source query param |
| `utm_medium` | varchar | UTM medium query param |
| `utm_campaign` | varchar | UTM campaign query param |
| `page_count` | int | Total pages viewed this session |
| `event_count` | int | Total events recorded this session |
| `is_converted` | boolean | `true` when an order is placed |
| `first_seen_at` | timestamp | Session start time |
| `last_seen_at` | timestamp | Most recent activity |

### `zv_events` — one row per tracked action

| Column | Type | Description |
|---|---|---|
| `session_id` | varchar(36) | Foreign key → `zv_sessions.session_id` |
| `event_type` | varchar(50) | See event types table below |
| `page_url` | text | Full URL where event occurred |
| `meta` | json | Event-specific payload |
| `created_at` | timestamp | When the event happened |

### Event Types

| `event_type` | Trigger | `meta` payload |
|---|---|---|
| `page_view` | Every storefront page load | `{ referer }` |
| `search` | URL contains `/search` + `?query=` | `{ query }` |
| `cart_add` | `checkout.cart.add.after` | `{ product_id, product_name, qty, price }` |
| `cart_remove` | `checkout.cart.delete.after` | `{ item_id }` |
| `order_placed` | `checkout.order.save.after` | `{ order_id, order_total, items_count }` |

---

## How Tracking Works

### Session Cookie

Every visitor gets a `zv_sid` cookie (UUID) set on their first request. It persists forever (`cookie()->forever()`). This ties all events across page views into one session row.

### Middleware — `TrackVisitorSession`

Injected into Laravel's `web` middleware group via:

```php
$router->pushMiddlewareToGroup('web', TrackVisitorSession::class);
```

Runs **after** the response is generated (calls `$next($request)` first) to avoid slowing down page delivery.

**Skips tracking for:**
- `/admin/*` routes
- `/api/*` routes
- AJAX / JSON requests
- Asset file extensions (`.css`, `.js`, `.png`, `.woff`, etc.)

### Event Listeners

Registered in the service provider:

```php
Event::listen('checkout.cart.add.after',    [TrackCartEvent::class, 'onAdd']);
Event::listen('checkout.cart.delete.after', [TrackCartEvent::class, 'onRemove']);
Event::listen('checkout.order.save.after',  [TrackOrderEvent::class, 'handle']);
```

Listeners read `app('zv.session_id')` which the middleware stores in the service container — this ties the event to the correct session without passing anything through the request chain.

---

## Admin Dashboard

**URL:** `http://your-domain/admin/zoyica/analytics`

**Sidebar:** Admin → Visitor Analytics (report icon, sort 12)

### Dashboard Sections

| Section | What it shows |
|---|---|
| **Summary cards** | Sessions, Unique IPs, Page Views, Orders — with icon badges |
| **Cart Funnel** | Cart Adds → Removes → Abandoned → Converted with progress bars and a proportional funnel strip |
| **Daily Sessions chart** | Bar chart of sessions per day for the selected period |
| **Devices** | Desktop / mobile / tablet breakdown with progress bars |
| **Browsers** | Browser counts |
| **Top Pages** | Most visited URLs by view count |
| **Top Searches** | Most searched queries extracted from search event meta |
| **Recent Sessions** | Last 20 sessions with session ID, IP, device, browser/OS, referrer, page count, event count, conversion status, last seen |

### Date Range Filter

Append `?days=7`, `?days=30`, or `?days=90` to the URL. Buttons in the page header do this automatically.

---

## Installation Steps

> Already installed on this project. Follow these steps only when deploying to a new environment.

```bash
# 1. Register namespace in composer.json → autoload.psr-4
"Zoyica\\ZoyicaVisitor\\": "packages/Zoyica/ZoyicaVisitor/src"

# 2. Regenerate autoload
composer dump-autoload

# 3. Register service provider in bootstrap/providers.php
Zoyica\ZoyicaVisitor\Providers\ZoyicaVisitorServiceProvider::class,

# 4. Run migrations
php artisan migrate --path=packages/Zoyica/ZoyicaVisitor/database/migrations

# 5. Clear caches
php artisan optimize:clear
```

---

## Extending the Package

### Track a new event type

Call `VisitorEvent::create()` anywhere in the app:

```php
use Zoyica\ZoyicaVisitor\Models\VisitorEvent;

$sessionId = app()->bound('zv.session_id') ? app('zv.session_id') : null;

if ($sessionId) {
    VisitorEvent::create([
        'session_id' => $sessionId,
        'event_type' => 'wishlist_add',
        'page_url'   => request()->url(),
        'meta'       => ['product_id' => $productId],
    ]);
}
```

### Listen to a new Bagisto event

In `ZoyicaVisitorServiceProvider::boot()`:

```php
Event::listen('catalog.product.review.after', function ($review) {
    $sessionId = app()->bound('zv.session_id') ? app('zv.session_id') : null;
    if (! $sessionId) return;
    VisitorEvent::create([
        'session_id' => $sessionId,
        'event_type' => 'review_posted',
        'meta'       => ['product_id' => $review->product_id, 'rating' => $review->rating],
    ]);
});
```

### Add a new metric to the dashboard

1. Query `zv_events` or `zv_sessions` in `AnalyticsController::index()`
2. Pass the result via `compact()`
3. Render in `index.blade.php`

---

## Cart Funnel Explained

```
Cart Adds       — visitors who added at least one item
Cart Removes    — visitors who removed items
Abandoned       — Cart Adds minus Orders (added but never bought)
Converted       — sessions where an order was placed
Conversion rate — (Orders / Cart Adds) × 100
```

A high abandoned number relative to cart adds indicates checkout friction — consider improving the checkout flow, adding trust signals, or sending cart abandonment emails.

---

## Common Pitfalls

| Pitfall | Cause | Fix |
|---|---|---|
| Sessions not recording | Middleware not in `web` group | Check `ZoyicaVisitorServiceProvider::boot()` calls `pushMiddlewareToGroup` |
| Cart events not tracked | `zv.session_id` not bound | Middleware must run before listeners; ensure a page view occurred first in the same request cycle |
| Dashboard 404 | Admin middleware mismatch | Route uses `['web', 'admin']` — must match Bagisto's admin group exactly |
| `zv_sessions` / `zv_events` tables missing | Migrations not run | Run `php artisan migrate --path=packages/Zoyica/ZoyicaVisitor/database/migrations` |
| Sidebar menu not showing | Config not merged | `register()` in service provider must call `mergeConfigFrom(..., 'menu.admin')` |
