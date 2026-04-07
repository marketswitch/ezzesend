<?php

namespace App\Http\Middleware;

use App\Models\MenuRestaurant;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * ⚡ SECURITY: MenuOwnership middleware
 *
 * Ensures the authenticated user can only access their OWN restaurant's
 * data. Without this, user A could modify user B's menu by guessing IDs.
 *
 * Attach to all authenticated menu routes:
 *   Route::middleware(['auth', 'menu.owner'])
 *
 * The restaurant is resolved from the route's {restaurant} model binding
 * OR from the auth user's restaurant record. Result is stored on the
 * request so controllers don't need to re-query.
 */
class MenuOwnership
{
    public function handle(Request $request, Closure $next): Response
    {
        $restaurantId = $request->route('restaurant');

        if ($restaurantId) {
            // ⚡ ALWAYS filter by user_id — never trust the route ID alone
            $restaurant = MenuRestaurant::where('id', $restaurantId)
                ->where('user_id', auth()->id())
                ->first();

            if (! $restaurant) {
                // Return 404 (not 403) — don't confirm the resource exists to an attacker
                abort(404);
            }

            $request->merge(['_menu_restaurant' => $restaurant]);
        }

        return $next($request);
    }
}
