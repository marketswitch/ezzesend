<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MenuOrderStatusLog extends Model
{
    public $timestamps = false;
    protected $guarded = ['id'];
    protected $table   = 'menu_order_status_logs';

    public function order() { return $this->belongsTo(MenuOrder::class, 'order_id'); }
}
