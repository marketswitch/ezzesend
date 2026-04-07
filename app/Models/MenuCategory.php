<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MenuCategory extends Model
{
    protected $guarded = ['id'];
    protected $table   = 'menu_categories';

    public function restaurant()
    {
        return $this->belongsTo(MenuRestaurant::class, 'restaurant_id');
    }

    public function items()
    {
        return $this->hasMany(MenuItem::class, 'category_id')->orderBy('sort_order');
    }

    public function availableItems()
    {
        return $this->items()->where('is_available', 1);
    }
}
