<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class MenuRestaurant extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'status' => 'integer',
    ];

    // ─── Relationships ─────────────────────────────────────────────────

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function branches()
    {
        return $this->hasMany(MenuBranch::class, 'restaurant_id');
    }

    public function categories()
    {
        return $this->hasMany(MenuCategory::class, 'restaurant_id')
                    ->orderBy('sort_order');
    }

    public function items()
    {
        return $this->hasMany(MenuItem::class, 'restaurant_id');
    }

    public function orders()
    {
        return $this->hasMany(MenuOrder::class, 'restaurant_id');
    }

    public function whatsappAccount()
    {
        return $this->belongsTo(WhatsappAccount::class, 'whatsapp_account_id');
    }

    // ─── Helpers ───────────────────────────────────────────────────────

    /**
     * Generate a unique URL-safe slug from the English name.
     */
    public static function generateSlug(string $name): string
    {
        $base = Str::slug($name);
        $slug = $base;
        $i    = 1;

        while (static::where('slug', $slug)->exists()) {
            $slug = $base . '-' . $i++;
        }

        return $slug;
    }

    /**
     * Public menu URL (no auth required).
     */
    public function publicUrl(): string
    {
        return route('menu.show', $this->slug);
    }

    /**
     * Format fils to display currency string.
     * KWD uses 3 decimal places.
     */
    public static function filsToDisplay(int $fils, string $currency = 'KWD'): string
    {
        $divisor  = $currency === 'KWD' ? 1000 : 100;
        $decimals = $currency === 'KWD' ? 3 : 2;

        return number_format($fils / $divisor, $decimals);
    }

    /**
     * Parse a display string back to fils.
     * Sanitizes input — only digits and one decimal separator accepted.
     */
    public static function displayToFils(string $input, string $currency = 'KWD'): int
    {
        // ⚡ SECURITY: strip anything that isn't a digit or decimal point
        $clean   = preg_replace('/[^\d.]/', '', $input);
        $divisor = $currency === 'KWD' ? 1000 : 100;

        return (int) round((float) $clean * $divisor);
    }
}
