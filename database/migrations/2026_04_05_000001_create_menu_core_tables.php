<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ─── Restaurant profile ────────────────────────────────────────
        Schema::create('menu_restaurants', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->index();
            $table->string('name_ar', 120);
            $table->string('name_en', 120);
            $table->string('slug', 120)->unique();          // e.g. "burger-house-kw"
            $table->text('description_ar')->nullable();
            $table->text('description_en')->nullable();
            $table->string('logo')->nullable();
            $table->string('cover')->nullable();
            $table->string('phone', 30)->nullable();
            $table->string('currency', 10)->default('KWD');
            $table->tinyInteger('status')->default(1);      // 1=active, 0=inactive
            $table->unsignedBigInteger('whatsapp_account_id')->nullable(); // FK to whatsapp_accounts
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });

        // ─── Branches (multi-location) ─────────────────────────────────
        Schema::create('menu_branches', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('restaurant_id')->index();
            $table->string('name_ar', 100);
            $table->string('name_en', 100);
            $table->string('address_ar', 255)->nullable();
            $table->string('address_en', 255)->nullable();
            $table->string('phone', 30)->nullable();
            $table->tinyInteger('status')->default(1);
            $table->timestamps();

            $table->foreign('restaurant_id')->references('id')->on('menu_restaurants')->onDelete('cascade');
        });

        // ─── Menu categories ───────────────────────────────────────────
        Schema::create('menu_categories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('restaurant_id')->index();
            $table->string('name_ar', 100);
            $table->string('name_en', 100);
            $table->string('image')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->tinyInteger('is_available')->default(1);
            $table->timestamps();

            $table->foreign('restaurant_id')->references('id')->on('menu_restaurants')->onDelete('cascade');
        });

        // ─── Menu items ────────────────────────────────────────────────
        Schema::create('menu_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('category_id')->index();
            $table->unsignedBigInteger('restaurant_id')->index(); // denormalised for fast ownership checks
            $table->string('name_ar', 150);
            $table->string('name_en', 150);
            $table->text('description_ar')->nullable();
            $table->text('description_en')->nullable();
            // ⚡ SECURITY: price stored in fils (integer) to avoid float rounding exploits
            // KWD 1.500 = 1500 fils.  Display divides by 1000.
            $table->unsignedInteger('price_fils')->default(0);
            $table->string('image')->nullable();
            $table->tinyInteger('is_available')->default(1);
            $table->tinyInteger('is_featured')->default(0);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->unsignedSmallInteger('calories')->nullable();
            $table->timestamps();

            $table->foreign('category_id')->references('id')->on('menu_categories')->onDelete('cascade');
            $table->foreign('restaurant_id')->references('id')->on('menu_restaurants')->onDelete('cascade');
        });

        // ─── Modifier groups (e.g. "Size", "Extras") ──────────────────
        Schema::create('menu_modifier_groups', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('item_id')->index();
            $table->string('name_ar', 100);
            $table->string('name_en', 100);
            $table->enum('type', ['single', 'multi'])->default('single');
            $table->tinyInteger('is_required')->default(0);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->foreign('item_id')->references('id')->on('menu_items')->onDelete('cascade');
        });

        // ─── Modifier options (e.g. "Large +500 fils") ────────────────
        Schema::create('menu_modifier_options', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('group_id')->index();
            $table->string('name_ar', 100);
            $table->string('name_en', 100);
            // ⚡ SECURITY: also in fils — server recalculates total, never trusts client
            $table->integer('price_add_fils')->default(0);   // can be negative for discounts
            $table->tinyInteger('is_available')->default(1);
            $table->timestamps();

            $table->foreign('group_id')->references('id')->on('menu_modifier_groups')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('menu_modifier_options');
        Schema::dropIfExists('menu_modifier_groups');
        Schema::dropIfExists('menu_items');
        Schema::dropIfExists('menu_categories');
        Schema::dropIfExists('menu_branches');
        Schema::dropIfExists('menu_restaurants');
    }
};
