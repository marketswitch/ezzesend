<?php

/**
 * ── Patch for PublicMenuController::placeOrder() ──────────────────────
 *
 * After the DB::transaction block that creates the order, add:
 *
 *   event(new \App\Events\NewMenuOrder($order->load('items', 'table'), $restaurant));
 *
 * Full context shown below — replace the existing try{} block:
 */

// ─── Inside placeOrder(), replace the try block with this ─────────────

try {
    $order = DB::transaction(function () use (
        $restaurant, $table, $validated, $orderItems, $subtotalFils
    ) {
        $phone = isset($validated['customer_phone'])
            ? MenuOrder::sanitizePhone($validated['customer_phone'])
            : null;

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
            'total_fils'      => $subtotalFils,
            'currency'        => $restaurant->currency,
            'notes'           => $notes,
            'status'          => 'received',
            'payment_method'  => $validated['payment_method'],
            'payment_status'  => 'pending',
            'idempotency_key' => $validated['idempotency_key'],
        ]);

        foreach ($orderItems as $lineData) {
            MenuOrderItem::create(array_merge($lineData, ['order_id' => $order->id]));
        }

        MenuOrderStatusLog::create([
            'order_id'    => $order->id,
            'from_status' => null,
            'to_status'   => 'received',
            'note'        => 'Order placed by customer',
        ]);

        if ($phone && $restaurant->user_id) {
            $this->syncContactToCrm($order, $phone, $restaurant);
        }

        return $order;
    });

    // ── ✅ FIRE KITCHEN NOTIFICATION ──────────────────────────────────
    // Load relationships needed for the broadcast payload, then fire.
    // ShouldBroadcastNow sends immediately (no queue needed).
    event(new \App\Events\NewMenuOrder(
        $order->load('items', 'table'),
        $restaurant
    ));
    // ─────────────────────────────────────────────────────────────────

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
