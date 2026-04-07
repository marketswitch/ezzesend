<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MenuOrder extends Model
{
    protected $guarded = ['id'];
    protected $table   = 'menu_orders';

    protected $casts = [
        'wa_confirmed_sent' => 'boolean',
        'wa_ready_sent'     => 'boolean',
        'wa_review_sent'    => 'boolean',
    ];

    public function restaurant() { return $this->belongsTo(MenuRestaurant::class, 'restaurant_id'); }
    public function branch()     { return $this->belongsTo(MenuBranch::class, 'branch_id'); }
    public function table()      { return $this->belongsTo(MenuTable::class, 'table_id'); }
    public function items()      { return $this->hasMany(MenuOrderItem::class, 'order_id'); }
    public function statusLogs() { return $this->hasMany(MenuOrderStatusLog::class, 'order_id')->orderByDesc('changed_at'); }

    public static function generateRef(): string
    {
        $date  = now()->format('Ymd');
        $count = static::whereDate('created_at', today())->count() + 1;
        return 'ORD-' . $date . '-' . str_pad($count, 4, '0', STR_PAD_LEFT);
    }

    public static function isIdempotencyUsed(string $key): bool
    {
        return static::where('idempotency_key', $key)->exists();
    }

    public function displayTotal(): string
    {
        return MenuRestaurant::filsToDisplay($this->total_fils, $this->currency);
    }

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

    public static function sanitizePhone(string $phone): string
    {
        $clean = preg_replace('/[^\d+]/', '', $phone);
        if (!str_starts_with($clean, '+')) { $clean = '+' . $clean; }
        return $clean;
    }

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
