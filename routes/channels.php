<?php

use App\Models\MenuRestaurant;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('menu-new-order-{restaurantId}', function ($user, $restaurantId) {
    return MenuRestaurant::where('id', $restaurantId)
        ->where('user_id', $user->id)
        ->exists();
});
