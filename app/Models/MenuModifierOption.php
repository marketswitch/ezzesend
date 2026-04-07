<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MenuModifierOption extends Model
{
    protected $guarded = ['id'];
    protected $table   = 'menu_modifier_options';

    public function group()
    {
        return $this->belongsTo(MenuModifierGroup::class, 'group_id');
    }

    public function getPriceAddFilsAttribute(): int
    {
        return (int) $this->attributes['price_add_fils'];
    }
}
