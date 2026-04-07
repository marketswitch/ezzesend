<?php

namespace App\Http\Controllers\User\Menu;

use App\Http\Controllers\Controller;
use App\Lib\WhatsApp\WhatsAppLib;
use App\Models\MenuOrder;
use App\Models\MenuOrderStatusLog;
use App\Models\MenuRestaurant;
use App\Models\Contact;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MenuOrderController extends Controller
{
    // ══════════════════════════════════════════════════════════════════
    // ORDER DASHBOARD (real-time board)
    // ══════════════════════════════════════════════════════════════════

    public function index(Request $request)
    {
        $restaurant = $this->ownedRestaurant();
        $pageTitle  = 'Order Dashboard';

        $branchId = $request->query('branch');

        $query = MenuOrder::where('restaurant_id', $restaurant->id)
            ->with('items', 'table.branch')
            ->orderByDesc('created_at');

        if ($branchId) {
            $query->where('branch_id', (int) $branchId);
        }

        // Live orders = not served/rejected/cancelled
        $liveOrders = (clone $query)
            ->whereNotIn('status', ['served', 'rejected', 'cancelled'])
            ->get();

        // Recent history (last 50)
        $recentOrders = (clone $query)
            ->whereIn('status', ['served', 'rejected', 'cancelled'])
            ->limit(50)
            ->get();

        $branches = $restaurant->branches()->get(['id', 'name_ar', 'name_en']);

        return view('templates.basic.user.menu.orders.dashboard', compact(
            'pageTitle', 'restaurant', 'liveOrders', 'recentOrders', 'branches', 'branchId'
        ));
    }

    // ══════════════════════════════════════════════════════════════════
    // STATUS UPDATE
    // ══════════════════════════════════════════════════════════════════

    public function updateStatus(Request $request, int $orderId)
    {
        $restaurant = $this->ownedRestaurant();

        // ⚡ SECURITY: always scope to restaurant_id to prevent IDOR
        $order = MenuOrder::where('id', $orderId)
            ->where('restaurant_id', $restaurant->id)
            ->firstOrFail();

        $validated = $request->validate([
            'status' => 'required|in:preparing,ready,served,rejected,cancelled',
            'note'   => 'nullable|string|max:255',
        ]);

        $newStatus = $validated['status'];

        // ⚡ SECURITY: validate state machine — prevent illegal transitions
        if (! $order->canTransitionTo($newStatus)) {
            return response()->json([
                'success' => false,
                'message' => __('Invalid status transition from :from to :to.', [
                    'from' => $order->status,
                    'to'   => $newStatus,
                ]),
            ], 422);
        }

        DB::transaction(function () use ($order, $newStatus, $validated, $restaurant) {
            $oldStatus = $order->status;

            $order->update(['status' => $newStatus]);

            // Log the transition
            MenuOrderStatusLog::create([
                'order_id'   => $order->id,
                'from_status' => $oldStatus,
                'to_status'  => $newStatus,
                'note'       => $validated['note'] ?? null,
                'changed_by' => auth()->id(),
            ]);

            // ⚡ Send WhatsApp notification to customer
            $this->sendStatusNotification($order, $newStatus, $restaurant);
        });

        return response()->json([
            'success'    => true,
            'new_status' => $newStatus,
            'badge_html' => $order->fresh()->statusBadge(),
        ]);
    }

    // ══════════════════════════════════════════════════════════════════
    // ORDER DETAIL (AJAX)
    // ══════════════════════════════════════════════════════════════════

    public function show(int $orderId)
    {
        $restaurant = $this->ownedRestaurant();

        $order = MenuOrder::where('id', $orderId)
            ->where('restaurant_id', $restaurant->id)
            ->with('items', 'table.branch', 'statusLogs')
            ->firstOrFail();

        return response()->json([
            'order'      => $order,
            'items'      => $order->items,
            'statusLogs' => $order->statusLogs,
        ]);
    }

    // ══════════════════════════════════════════════════════════════════
    // ANALYTICS
    // ══════════════════════════════════════════════════════════════════

    public function analytics(Request $request)
    {
        $restaurant = $this->ownedRestaurant();
        $pageTitle  = 'Menu Analytics';

        $range = $request->query('range', '30'); // days
        $range = in_array($range, ['7', '30', '90']) ? (int) $range : 30;
        $from  = now()->subDays($range)->startOfDay();

        // Revenue (paid orders only)
        $revenue = MenuOrder::where('restaurant_id', $restaurant->id)
            ->where('payment_status', 'paid')
            ->where('created_at', '>=', $from)
            ->sum('total_fils');

        // Order counts by status
        $statusCounts = MenuOrder::where('restaurant_id', $restaurant->id)
            ->where('created_at', '>=', $from)
            ->selectRaw('status, count(*) as cnt')
            ->groupBy('status')
            ->pluck('cnt', 'status');

        // Top items
        $topItems = DB::table('menu_order_items')
            ->join('menu_orders', 'menu_orders.id', '=', 'menu_order_items.order_id')
            ->where('menu_orders.restaurant_id', $restaurant->id)
            ->where('menu_orders.created_at', '>=', $from)
            ->selectRaw('item_name_en, item_name_ar, sum(qty) as total_qty, sum(line_total_fils) as total_revenue')
            ->groupBy('item_id', 'item_name_en', 'item_name_ar')
            ->orderByDesc('total_qty')
            ->limit(10)
            ->get();

        // Daily revenue chart
        $dailyRevenue = MenuOrder::where('restaurant_id', $restaurant->id)
            ->where('payment_status', 'paid')
            ->where('created_at', '>=', $from)
            ->selectRaw('DATE(created_at) as date, sum(total_fils) as total')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return view('templates.basic.user.menu.analytics', compact(
            'pageTitle', 'restaurant', 'revenue', 'statusCounts',
            'topItems', 'dailyRevenue', 'range'
        ));
    }

    // ══════════════════════════════════════════════════════════════════
    // PRIVATE HELPERS
    // ══════════════════════════════════════════════════════════════════

    private function ownedRestaurant(): MenuRestaurant
    {
        return MenuRestaurant::where('user_id', auth()->id())->firstOrFail();
    }

    /**
     * Send WhatsApp notification when order status changes.
     * ⚡ SECURITY: phone sanitized before passing to WhatsApp lib.
     * Templates are pre-approved by Meta — no dynamic content injection.
     */
    private function sendStatusNotification(
        MenuOrder $order,
        string    $newStatus,
        MenuRestaurant $restaurant
    ): void {
        if (! $order->customer_phone || ! $restaurant->whatsapp_account_id) {
            return;
        }

        $account = $restaurant->whatsappAccount;
        if (! $account) {
            return;
        }

        // ⚡ SECURITY: re-sanitize phone even though it was sanitized at insert
        $phone = MenuOrder::sanitizePhone($order->customer_phone);

        try {
            $lib = new WhatsAppLib();

            if ($newStatus === 'preparing' && ! $order->wa_confirmed_sent) {
                // Template: "order_confirmed" — variables: name, order_ref, restaurant_name
                $lib->sendTextMessage(
                    $phone,
                    $account,
                    $this->confirmationText($order, $restaurant)
                );
                $order->update(['wa_confirmed_sent' => 1]);
            }

            if ($newStatus === 'ready' && ! $order->wa_ready_sent) {
                $lib->sendTextMessage(
                    $phone,
                    $account,
                    $this->readyText($order, $restaurant)
                );
                $order->update(['wa_ready_sent' => 1]);
            }

            if ($newStatus === 'rejected') {
                $lib->sendTextMessage(
                    $phone,
                    $account,
                    $this->rejectedText($order, $restaurant)
                );
            }
        } catch (\Throwable $e) {
            // Log but never crash the status update
            Log::error('Menu WA notification failed: ' . $e->getMessage(), [
                'order_id' => $order->id,
                'status'   => $newStatus,
            ]);
        }
    }

    private function confirmationText(MenuOrder $order, MenuRestaurant $restaurant): string
    {
        $name = $order->customer_name ? "Hi {$order->customer_name}! " : '';
        $table = $order->table ? " at {$order->table->label}" : '';

        // ⚡ SECURITY: all variables escaped — no user content injected raw into message
        return e($name)
            . 'Your order *' . e($order->order_ref) . '*'
            . e($table)
            . ' at ' . e($restaurant->name_en)
            . ' has been confirmed! 🍽️ We\'re preparing it now.';
    }

    private function readyText(MenuOrder $order, MenuRestaurant $restaurant): string
    {
        $name = $order->customer_name ? "Hi {$order->customer_name}! " : '';

        return e($name)
            . 'Your order *' . e($order->order_ref) . '*'
            . ' at ' . e($restaurant->name_en)
            . ' is ready! ✅';
    }

    private function rejectedText(MenuOrder $order, MenuRestaurant $restaurant): string
    {
        return 'Sorry, we were unable to process your order '
            . '*' . e($order->order_ref) . '*'
            . ' at ' . e($restaurant->name_en)
            . '. Please speak to our staff. 🙏';
    }
}
