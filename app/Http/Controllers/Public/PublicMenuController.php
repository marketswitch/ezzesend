<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Contact;
use App\Models\MenuBranch;
use App\Models\MenuCategory;
use App\Models\MenuItem;
use App\Models\MenuModifierGroup;
use App\Models\MenuModifierOption;
use App\Models\MenuOrder;
use App\Models\MenuOrderItem;
use App\Models\MenuOrderStatusLog;
use App\Models\MenuRestaurant;
use App\Models\MenuTable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;

class PublicMenuController extends Controller
{
    // ══════════════════════════════════════════════════════════════════
    // SHOW MENU PAGE
    // ══════════════════════════════════════════════════════════════════

    public function show(string $slug, Request $request)
    {
        // Active restaurants only
        $restaurant = MenuRestaurant::where('slug', $slug)
            ->where('status', 1)
            ->firstOrFail();

        // Resolve table from signed token (optional)
        $table = null;
        if ($token = $request->query('table')) {
            // ⚡ SECURITY: token is 64-char hex — strip anything else before querying
            $cleanToken = preg_replace('/[^a-f0-9]/', '', strtolower($token));

            if (strlen($cleanToken) === 64) {
                $table = MenuTable::whereHas('branch', function ($q) use ($restaurant) {
                    $q->where('restaurant_id', $restaurant->id);
                })
                ->where('token', $cleanToken)
                ->where('is_active', 1)
                ->first();
            }
        }

        // Load menu: categories with available items only
        $categories = MenuCategory::where('restaurant_id', $restaurant->id)
            ->where('is_available', 1)
            ->orderBy('sort_order')
            ->with(['availableItems.modifierGroups.availableOptions'])
            ->get();

        // Detect preferred language from Accept-Language header, default AR for Kuwait
        $lang = $this->detectLang($request);

        return view('public.menu.show', compact(
            'restaurant', 'categories', 'table', 'lang'
        ));
    }

    // ══════════════════════════════════════════════════════════════════
    // PLACE ORDER (public, no auth)
    // ══════════════════════════════════════════════════════════════════

    public function placeOrder(Request $request, string $slug)
    {
        // ─── Rate limiting ─────────────────────────────────────────────
        // ⚡ SECURITY: 5 orders per IP per 10 minutes — prevents order spam
        $limiterKey = 'menu_order:' . $request->ip();
        if (RateLimiter::tooManyAttempts($limiterKey, 5)) {
            $seconds = RateLimiter::availableIn($limiterKey);
            return response()->json([
                'success' => false,
                'message' => __('Too many orders. Please wait :s seconds.', ['s' => $seconds]),
            ], 429);
        }
        RateLimiter::hit($limiterKey, 600); // 10 min window

        // ─── Validate restaurant ───────────────────────────────────────
        $restaurant = MenuRestaurant::where('slug', $slug)
            ->where('status', 1)
            ->firstOrFail();

        // ─── Validate input ────────────────────────────────────────────
        $validated = $request->validate([
            'customer_name'    => 'nullable|string|max:100',
            'customer_phone'   => 'nullable|string|max:30',
            'notes'            => 'nullable|string|max:500',
            'payment_method'   => 'required|in:card,knet,cash',
            'table_token'      => 'nullable|string|size:64|regex:/^[a-f0-9]+$/',
            // ⚡ SECURITY: idempotency key from client (UUID v4) prevents double-submit
            'idempotency_key'  => 'required|string|size:36|regex:/^[0-9a-f\-]+$/i',
            'items'            => 'required|array|min:1|max:50',
            'items.*.item_id'  => 'required|integer',
            'items.*.qty'      => 'required|integer|min:1|max:20',
            'items.*.modifier_option_ids' => 'nullable|array',
            'items.*.modifier_option_ids.*' => 'integer',
        ]);

        // ─── Idempotency check ─────────────────────────────────────────
        // ⚡ SECURITY: reject if this key was already used (duplicate submit)
        if (MenuOrder::isIdempotencyUsed($validated['idempotency_key'])) {
            // Return the existing order (idempotent response)
            $existing = MenuOrder::where('idempotency_key', $validated['idempotency_key'])->first();
            return response()->json([
                'success'   => true,
                'order_ref' => $existing->order_ref,
                'message'   => __('Order already placed.'),
            ]);
        }

        // ─── Resolve table ─────────────────────────────────────────────
        $table = null;
        if (! empty($validated['table_token'])) {
            $table = MenuTable::whereHas('branch', function ($q) use ($restaurant) {
                $q->where('restaurant_id', $restaurant->id);
            })
            ->where('token', $validated['table_token'])
            ->where('is_active', 1)
            ->first();

            if (! $table) {
                return response()->json([
                    'success' => false,
                    'message' => __('Invalid table. Please re-scan the QR code.'),
                ], 422);
            }
        }

        // ─── Price calculation — SERVER SIDE ONLY ─────────────────────
        // ⚡ SECURITY: NEVER use client-submitted prices.
        // Re-fetch every item and modifier from the database.
        $orderItems    = [];
        $subtotalFils  = 0;

        foreach ($validated['items'] as $lineInput) {
            // ⚡ SECURITY: fetch item by ID AND restaurant_id — prevents ordering items from other restaurants
            $item = MenuItem::where('id', $lineInput['item_id'])
                ->where('restaurant_id', $restaurant->id)
                ->where('is_available', 1)
                ->first();

            if (! $item) {
                return response()->json([
                    'success' => false,
                    'message' => __('Item ":id" is unavailable or does not exist.', ['id' => $lineInput['item_id']]),
                ], 422);
            }

            $qty              = (int) $lineInput['qty'];
            $unitPriceFils    = $item->price_fils;
            $modifierTotal    = 0;
            $modifierSnapshot = [];

            // Calculate modifier additions from DB — never from client
            if (! empty($lineInput['modifier_option_ids'])) {
                foreach ($lineInput['modifier_option_ids'] as $optionId) {
                    // ⚡ SECURITY: verify option belongs to this item's modifier group
                    $option = MenuModifierOption::whereHas('group', function ($q) use ($item) {
                        $q->where('item_id', $item->id);
                    })
                    ->where('id', $optionId)
                    ->where('is_available', 1)
                    ->first();

                    if ($option) {
                        $modifierTotal += $option->price_add_fils;
                        $modifierSnapshot[] = [
                            'option_id'      => $option->id,
                            'name_ar'        => $option->name_ar,
                            'name_en'        => $option->name_en,
                            'price_add_fils' => $option->price_add_fils,
                        ];
                    }
                }
            }

            $lineTotal     = ($unitPriceFils + $modifierTotal) * $qty;
            $subtotalFils += $lineTotal;

            $orderItems[] = [
                'item_id'             => $item->id,
                'item_name_ar'        => $item->name_ar,
                'item_name_en'        => $item->name_en,
                'unit_price_fils'     => $unitPriceFils,
                'qty'                 => $qty,
                'modifiers_snapshot'  => $modifierSnapshot,
                'modifier_total_fils' => $modifierTotal,
                'line_total_fils'     => $lineTotal,
            ];
        }

        // ─── Create order ──────────────────────────────────────────────
        try {
            $order = DB::transaction(function () use (
                $restaurant, $table, $validated, $orderItems, $subtotalFils
            ) {
                // ⚡ SECURITY: sanitize phone before storing
                $phone = isset($validated['customer_phone'])
                    ? MenuOrder::sanitizePhone($validated['customer_phone'])
                    : null;

                // ⚡ SECURITY: strip HTML from notes
                $notes = isset($validated['notes'])
                    ? strip_tags($validated['notes'])
                    : null;

                $order = MenuOrder::create([
                    'restaurant_id'   => $restaurant->id,
                    'branch_id'       => $table?->branch_id ?? $restaurant->branches()->first()?->id,
                    'table_id'        => $table?->id,
                    'order_ref'       => MenuOrder::generateRef(),
                    'customer_phone'  => $phone,
                    'customer_name'   => isset($validated['customer_name'])
                        ? strip_tags($validated['customer_name'])
                        : null,
                    'subtotal_fils'   => $subtotalFils,
                    'total_fils'      => $subtotalFils, // no delivery fee v1
                    'currency'        => $restaurant->currency,
                    'notes'           => $notes,
                    'status'          => 'received',
                    'payment_method'  => $validated['payment_method'],
                    'payment_status'  => $validated['payment_method'] === 'cash' ? 'pending' : 'pending',
                    'idempotency_key' => $validated['idempotency_key'],
                ]);

                // Save line items
                foreach ($orderItems as $lineData) {
                    MenuOrderItem::create(array_merge($lineData, ['order_id' => $order->id]));
                }

                // Log creation
                MenuOrderStatusLog::create([
                    'order_id'   => $order->id,
                    'from_status' => null,
                    'to_status'  => 'received',
                    'note'       => 'Order placed by customer',
                ]);

                // ─── CRM sync: create/update contact ──────────────────
                if ($phone && $restaurant->user_id) {
                    $this->syncContactToCrm($order, $phone, $restaurant);
                }

                return $order;
            });

            RateLimiter::hit($limiterKey, 600);

            return response()->json([
                'success'   => true,
                'order_ref' => $order->order_ref,
                'message'   => __('Your order has been placed! We\'ll send you a WhatsApp confirmation shortly.'),
            ]);
        } catch (\Throwable $e) {
            Log::error('Menu order failed: ' . $e->getMessage(), ['slug' => $slug]);

            return response()->json([
                'success' => false,
                'message' => __('Something went wrong. Please try again.'),
            ], 500);
        }
    }

    // ══════════════════════════════════════════════════════════════════
    // ORDER STATUS (public poll)
    // ══════════════════════════════════════════════════════════════════

    public function orderStatus(string $orderRef)
    {
        // ⚡ SECURITY: only expose status and label — no internal IDs or prices
        $order = MenuOrder::where('order_ref', $orderRef)
            ->select('order_ref', 'status', 'customer_name', 'total_fils', 'currency', 'created_at')
            ->firstOrFail();

        return response()->json([
            'order_ref'  => $order->order_ref,
            'status'     => $order->status,
            'total'      => MenuRestaurant::filsToDisplay($order->total_fils, $order->currency),
            'currency'   => $order->currency,
            'created_at' => $order->created_at->diffForHumans(),
        ]);
    }

    // ══════════════════════════════════════════════════════════════════
    // PRIVATE HELPERS
    // ══════════════════════════════════════════════════════════════════

    /**
     * Auto-create or update EzzeSend Contact from a menu order.
     * This is the CRM sync that differentiates EzzeSend Menu from Ordable/Mnasati.
     */
    private function syncContactToCrm(MenuOrder $order, string $phone, MenuRestaurant $restaurant): void
    {
        try {
            // Parse phone: +96550545078 → mobile_code=965, mobile=50545078
            preg_match('/^\+?(\d{1,4})(\d{7,12})$/', ltrim($phone, '+'), $matches);

            if (! $matches) {
                return;
            }

            $mobileCode = $matches[1];
            $mobile     = $matches[2];

            $contact = Contact::firstOrCreate(
                [
                    'user_id'     => $restaurant->user_id,
                    'mobile_code' => $mobileCode,
                    'mobile'      => $mobile,
                ],
                [
                    'firstname' => $order->customer_name ?? '',
                    'lastname'  => '',
                ]
            );

            // Tag as menu customer (ContactTag relationship)
            // Uses existing tags infrastructure
            DB::table('contact_tag_contacts')->insertOrIgnore([
                'contact_id'     => $contact->id,
                'contact_tag_id' => $this->getOrCreateMenuTag($restaurant),
            ]);

            // Link order to contact
            $order->update(['contact_id' => $contact->id]);
        } catch (\Throwable $e) {
            Log::warning('Menu CRM sync failed: ' . $e->getMessage());
            // Non-fatal — order is already saved
        }
    }

    /**
     * Get or create a "menu-customer" tag for this restaurant's user.
     */
    private function getOrCreateMenuTag(MenuRestaurant $restaurant): int
    {
        $tag = DB::table('contact_tags')
            ->where('user_id', $restaurant->user_id)
            ->where('name', 'menu-customer')
            ->first();

        if ($tag) {
            return $tag->id;
        }

        return DB::table('contact_tags')->insertGetId([
            'user_id'    => $restaurant->user_id,
            'name'       => 'menu-customer',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Detect language from Accept-Language header.
     * Defaults to Arabic for Kuwait market.
     */
    private function detectLang(Request $request): string
    {
        $accept = $request->header('Accept-Language', 'ar');
        return str_starts_with(strtolower($accept), 'ar') ? 'ar' : 'en';
    }
}
