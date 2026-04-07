<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MenuTable extends Model
{
    protected $guarded = ['id'];
    protected $table   = 'menu_tables';

    public function branch()
    {
        return $this->belongsTo(MenuBranch::class, 'branch_id');
    }

    public static function generateToken(): string
    {
        return bin2hex(random_bytes(32));
    }

    public function qrUrl(): string
    {
        return route('menu.show', [
            'slug'  => $this->branch->restaurant->slug,
            'table' => $this->token,
        ]);
    }
}
