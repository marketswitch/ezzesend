<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MenuModifierGroup extends Model
{
    protected $guarded = ['id'];
    protected $table   = 'menu_modifier_groups';

    public function item()
    {
        return $this->belongsTo(MenuItem::class, 'item_id');
    }

    public function options()
    {
        return $this->hasMany(MenuModifierOption::class, 'group_id');
    }

    public function availableOptions()
    {
        return $this->options()->where('is_available', 1);
    }
}
