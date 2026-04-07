# EzzeSend — Digital Menu Module

WhatsApp-powered QR menu and ordering system built as a Laravel extension for EzzeSend.

---

## What This Is

A drop-in module that adds a full digital menu + ordering system to EzzeSend. Every customer who orders automatically becomes an EzzeSend contact, ready for WhatsApp campaigns, RFM segmentation, and automation.

**Competitors (Ordable, Mnasati) charge separately and have zero CRM. This is built in.**

---

## Features

- QR table ordering (no app needed — opens in browser)
- Bilingual Arabic RTL / English
- Menu builder with categories, items, modifiers, images
- Multi-branch support
- KNET, card, and cash payment
- Real-time kitchen dashboard via Pusher
- Audio alarm + full-screen flash on new orders
- WhatsApp order confirmation to customer (automatic)
- Auto-CRM sync — every order creates an EzzeSend contact
- RFM segmentation on order history
- Menu analytics (revenue, top items, peak hours)

---

## Installation

### 1. Copy files into your Laravel project

Drop each file from this repo into the matching path in your EzzeSend Laravel project.

### 2. Run migrations

```bash
php artisan migrate
```

Creates 10 tables: menu_restaurants, menu_branches, menu_categories, menu_items, menu_modifier_groups, menu_modifier_options, menu_tables, menu_orders, menu_order_items, menu_order_status_logs.

### 3. Add routes

In `routes/user.php` — add authenticated menu routes from `routes/menu_routes.php`.
In `routes/web.php` — add public menu routes.
In `routes/channels.php` — add Pusher channel auth from `routes/channels_addition.php`.

### 4. Register middleware

```php
$middleware->alias([
    'menu.owner' => \App\Http\Middleware\MenuOwnership::class,
]);
```

### 5. Add FileInfo paths

In `app/Constants/FileInfo.php` add entries for: menuLogo, menuCover, menuCategory, menuItem.

### 6. Fire the kitchen event

After the DB transaction in `PublicMenuController::placeOrder()` add:

```php
event(new \App\Events\NewMenuOrder($order->load('items', 'table'), $restaurant));
```

### 7. Clear caches

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

---

## Security

| Threat | Protection |
|--------|-----------|
| Price tampering | Server recalculates from DB — client prices ignored |
| IDOR | All queries scoped to restaurant_id + user_id |
| QR forgery | 32-byte cryptographic token |
| Double-submit | UUID idempotency key per order |
| Order spam | Rate limiting: 5 orders / 10 min per IP |
| Phone injection | E.164 sanitization before WhatsApp |
| XSS | strip_tags() + e() on all user content |
| Illegal state jumps | State machine validates every status transition |

---

## URLs

Customer menu: `https://yourdomain.com/menu/{slug}`  
Kitchen dashboard: `https://yourdomain.com/user/menu/orders`

---

Built by EzzeSend — [send.ezzemedia.com](https://send.ezzemedia.com)
