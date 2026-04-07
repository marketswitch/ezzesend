<?php

namespace App\Events;

use App\Models\MenuOrder;
use App\Models\MenuRestaurant;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Fired immediately when a customer places a new menu order.
 * Uses ShouldBroadcastNow (not ShouldBroadcast) so it fires
 * synchronously without needing a queue worker — same pattern
 * as the existing ReceiveMessage event.
 *
 * Channel: private-menu-new-order-{restaurant_id}
 * Event:   menu-new-order
 */
class NewMenuOrder implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public int    $restaurantId;
    public array  $data;

    public function __construct(MenuOrder $order, MenuRestaurant $restaurant)
    {
        $this->restaurantId = $restaurant->id;

        // Only send what the kitchen screen needs — no sensitive payment data
        $this->data = [
            'order_id'       => $order->id,
            'order_ref'      => $order->order_ref,
            'table_label'    => $order->table?->label ?? 'Takeaway',
            'customer_name'  => $order->customer_name  ?: '—',
            'item_count'     => $order->items->count(),
            'total'          => $order->displayTotal(),
            'currency'       => $order->currency,
            'notes'          => $order->notes ?: '',
            'payment_method' => $order->payment_method,
            'created_at'     => $order->created_at->format('H:i'),
            // Pre-rendered item summary for the toast card
            'items_summary'  => $order->items->map(fn($i) =>
                "{$i->qty}× {$i->item_name_en}"
            )->join(', '),
        ];
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('menu-new-order-' . $this->restaurantId),
        ];
    }

    public function broadcastAs(): string
    {
        return 'menu-new-order';
    }
}
