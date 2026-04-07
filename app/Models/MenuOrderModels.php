<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

// ══════════════════════════════════════════════════════════════════════════════
// MenuOrder
// ══════════════════════════════════════════════════════════════════════════════
class MenuOrder extends Model
{
    protected $guarded = ['id'];
    protected $table   = 'menu_orders';

    protected $casts = [
        'wa_confirmed_sent' => 'boolean',
        'wa_ready_sent'     => 'boolean',
        'wa_review_sent'    => 'boolean',
    ];

    // ─── Relationships ─────────────────────────────────────────────────

    public function restaurant()
    {
        return $this->belongsTo(MenuRestaurant::class, 'restaurant_id');
    }

    public function branch()
    {
        return $this->belongsTo(MenuBranch::class, 'branch_id');
    }

    public function table()
    {
        return $this->belongsTo(MenuTable::class, 'table_id');
    }

    public function items()
    {
        return $this->hasMany(MenuOrderItem::class, 'order_id');
    }

    public function statusLogs()
    {
        return $this->hasMany(MenuOrderStatusLog::class, 'order_id')->orderByDesc('changed_at');
    }

    public function contact()
    {
        return $this->belongsTo(Contact::class, 'contact_id');
    }

    // ─── Business logic ────────────────────────────────────────────────

    /**
     * Generate a human-readable order reference.
     * Format: ORD-YYYYMMDD-XXXX (XXXX = today's sequential count padded to 4)
     */
    public static function generateRef(): string
    {
        $date  = now()->format('Ymd');
        $count = static::whereDate('created_at', today())->count() + 1;

        return 'ORD-' . $date . '-' . str_pad($count, 4, '0', STR_PAD_LEFT);
    }

    /**
     * ⚡ SECURITY: idempotency key prevents duplicate orders from double-taps.
     * Client generates a UUID on page load; server rejects if already used.
     */
    public static function isIdempotencyUsed(string $key): bool
    {
        return static::where('idempotency_key', $key)->exists();
    }

    /**
     * Display total in currency format.
     */
    public function displayTotal(): string
    {
        return MenuRestaurant::filsToDisplay($this->total_fils, $this->currency);
    }

    /**
     * Validate status transition — prevents skipping states.
     */
    public static function validTransitions(): array
    {
        return [
            'received'  => ['preparing', 'rejected'],
            'preparing' => ['ready', 'rejected'],
            'ready'     => ['served', 'rejected'],
            'served'    => [],
            'rejected'  => [],
            'cancelled' => [],
        ];
    }

    public function canTransitionTo(string $newStatus): bool
    {
        return in_array($newStatus, static::validTransitions()[$this->status] ?? []);
    }

    /**
     * Sanitize customer phone to E.164 format before storing.
     * ⚡ SECURITY: prevent injection of special chars into WhatsApp templates.
     */
    public static function sanitizePhone(string $phone): string
    {
        // Strip everything except digits and leading +
        $clean = preg_replace('/[^\d+]/', '', $phone);

        // Ensure it starts with + for E.164
        if (!str_starts_with($clean, '+')) {
            $clean = '+' . $clean;
        }

        return $clean;
    }

    /**
     * Status badge HTML — read-only computed attribute.
     */
    public function statusBadge(): string
    {
        $map = [
            'received'  => ['warning', 'Received'],
            'preparing' => ['info',    'Preparing'],
            'ready'     => ['success', 'Ready'],
            'served'    => ['dark',    'Served'],
            'rejected'  => ['danger',  'Rejected'],
            'cancelled' => ['danger',  'Cancelled'],
        ];

        [$colour, $label] = $map[$this->status] ?? ['secondary', ucfirst($this->status)];

        return '<span class="badge badge--' . $colour . '">' . trans($label) . '</span>';
    }
}

// ══════════════════════════════════════════════════════════════════════════════
// MenuOrderItem
// ══════════════════════════════════════════════════════════════════════════════
class MenuOrderItem extends Model
{
    protected $guarded = ['id'];
    protected $table   = 'menu_order_items';

    protected $casts = [
        'modifiers_snapshot' => 'array',
    ];

    public function order()
    {
        return $this->belongsTo(MenuOrder::class, 'order_id');
    }

    public function menuItem()
    {
        return $this->belongsTo(MenuItem::class, 'item_id');
    }

    public function displayLineTotal(): string
    {
        $order = $this->order;
        return MenuRestaurant::filsToDisplay($this->line_total_fils, $order?->currency ?? 'KWD');
    }
}

// ══════════════════════════════════════════════════════════════════════════════
// MenuOrderStatusLog
// ══════════════════════════════════════════════════════════════════════════════
class MenuOrderStatusLog extends Model
{
    public $timestamps = false;
    protected $guarded = ['id'];
    protected $table   = 'menu_order_status_logs';

    public function order()
    {
        return $this->belongsTo(MenuOrder::class, 'order_id');
    }
}
