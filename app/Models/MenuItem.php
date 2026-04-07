<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MenuItem extends Model
{
    protected $guarded = ['id'];
    protected $table   = 'menu_items';

    public function category()
    {
        return $this->belongsTo(MenuCategory::class, 'category_id');
    }

    public function restaurant()
    {
        return $this->belongsTo(MenuRestaurant::class, 'restaurant_id');
    }

    public function modifierGroups()
    {
        return $this->hasMany(MenuModifierGroup::class, 'item_id')->orderBy('sort_order');
    }

    public function getPriceFilsAttribute(): int
    {
        return (int) $this->attributes['price_fils'];
    }

    public function displayPrice(string $currency = 'KWD'): string
    {
        return MenuRestaurant::filsToDisplay($this->price_fils, $currency);
    }
}
