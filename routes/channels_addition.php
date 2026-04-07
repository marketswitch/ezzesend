<?php

/**
 * ── Add this block to routes/channels.php ──────────────────────────────
 *
 * Authorises the private Pusher channel for the kitchen order dashboard.
 * Only the restaurant owner (auth user whose restaurant ID matches) can
 * subscribe to their channel.
 *
 * ⚡ SECURITY: the restaurant_id in the channel name is verified against
 * the authenticated user's actual restaurant — prevents staff from one
 * restaurant subscribing to another restaurant's channel.
 */

use App\Models\MenuRestaurant;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('menu-new-order-{restaurantId}', function ($user, $restaurantId) {
    // Only the owner of this restaurant can subscribe
    return MenuRestaurant::where('id', $restaurantId)
        ->where('user_id', $user->id)
        ->exists();
});
