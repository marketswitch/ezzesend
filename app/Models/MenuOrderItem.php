<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MenuOrderItem extends Model
{
    protected $guarded = ['id'];
    protected $table   = 'menu_order_items';

    protected $casts = ['modifiers_snapshot' => 'array'];

    public function order()    { return $this->belongsTo(MenuOrder::class, 'order_id'); }
    public function menuItem() { return $this->belongsTo(MenuItem::class, 'item_id'); }

    public function displayLineTotal(): string
    {
        $order = $this->order;
        return MenuRestaurant::filsToDisplay($this->line_total_fils, $order?->currency ?? 'KWD');
    }
}
