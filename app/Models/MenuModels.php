<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

// ══════════════════════════════════════════════════════════════════════════════
// MenuBranch
// ══════════════════════════════════════════════════════════════════════════════
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

// ══════════════════════════════════════════════════════════════════════════════
// MenuTable (physical dining table / QR point)
// ══════════════════════════════════════════════════════════════════════════════
class MenuTable extends Model
{
    protected $guarded = ['id'];
    protected $table   = 'menu_tables';

    public function branch()
    {
        return $this->belongsTo(MenuBranch::class, 'branch_id');
    }

    /**
     * ⚡ SECURITY: tokens are cryptographically random — never sequential.
     * 32 bytes = 64 hex chars. Guessing probability is 1/2^256.
     */
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

// ══════════════════════════════════════════════════════════════════════════════
// MenuCategory
// ══════════════════════════════════════════════════════════════════════════════
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
        return $this->hasMany(MenuItem::class, 'category_id')
                    ->orderBy('sort_order');
    }

    public function availableItems()
    {
        return $this->items()->where('is_available', 1);
    }
}

// ══════════════════════════════════════════════════════════════════════════════
// MenuItem
// ══════════════════════════════════════════════════════════════════════════════
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
        return $this->hasMany(MenuModifierGroup::class, 'item_id')
                    ->orderBy('sort_order');
    }

    /**
     * ⚡ SECURITY: price always read from DB, never from request input.
     * Callers must use this accessor — never trust a client-submitted price.
     */
    public function getPriceFilsAttribute(): int
    {
        return (int) $this->attributes['price_fils'];
    }

    public function displayPrice(string $currency = 'KWD'): string
    {
        return MenuRestaurant::filsToDisplay($this->price_fils, $currency);
    }
}

// ══════════════════════════════════════════════════════════════════════════════
// MenuModifierGroup
// ══════════════════════════════════════════════════════════════════════════════
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

// ══════════════════════════════════════════════════════════════════════════════
// MenuModifierOption
// ══════════════════════════════════════════════════════════════════════════════
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
