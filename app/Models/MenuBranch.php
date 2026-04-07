<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MenuBranch extends Model
{
    protected $guarded = ['id'];
    protected $table   = 'menu_branches';

    public function restaurant()
    {
        return $this->belongsTo(MenuRestaurant::class, 'restaurant_id');
    }

    public function tables()
    {
        return $this->hasMany(MenuTable::class, 'branch_id');
    }

    public function orders()
    {
        return $this->hasMany(MenuOrder::class, 'branch_id');
    }
}
