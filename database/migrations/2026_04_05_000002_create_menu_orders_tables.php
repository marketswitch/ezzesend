<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ─── Physical tables / QR codes ────────────────────────────────
        Schema::create('menu_tables', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('branch_id')->index();
            $table->string('label', 50);           // "Table 5", "Takeaway", "Drive-in A"
            // ⚡ SECURITY: token is a cryptographically random 32-byte hex (not sequential ID)
            // Prevents QR code enumeration / order hijacking
            $table->string('token', 64)->unique(); // URL: /menu/{slug}?t={token}
            $table->tinyInteger('is_active')->default(1);
            $table->timestamps();

            $table->foreign('branch_id')->references('id')->on('menu_branches')->onDelete('cascade');
        });

        // ─── Orders (server-calculated totals only) ────────────────────
        Schema::create('menu_orders', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('restaurant_id')->index();
            $table->unsignedBigInteger('branch_id')->index();
            $table->unsignedBigInteger('table_id')->nullable();    // null = takeaway
            $table->string('order_ref', 20)->unique();             // e.g. "ORD-20260405-0001"
            $table->string('customer_phone', 30)->nullable();      // sanitized, E.164
            $table->string('customer_name', 100)->nullable();
            // ⚡ SECURITY: subtotal and total are ALWAYS calculated server-side
            // Client-submitted prices are IGNORED — items re-fetched from DB
            $table->unsignedInteger('subtotal_fils')->default(0);
            $table->unsignedInteger('total_fils')->default(0);
            $table->string('currency', 10)->default('KWD');
            $table->string('notes', 500)->nullable();
            $table->enum('status', [
                'received',
                'preparing',
                'ready',
                'served',
                'rejected',
                'cancelled',
            ])->default('received');
            $table->enum('payment_method', ['card', 'knet', 'cash'])->default('cash');
            $table->enum('payment_status', ['pending', 'paid', 'failed', 'refunded'])->default('pending');
            $table->string('gateway_ref', 100)->nullable();        // payment gateway tx ID
            // ⚡ SECURITY: idempotency key prevents double-submit / replay attacks
            $table->string('idempotency_key', 64)->unique()->nullable();
            $table->tinyInteger('wa_confirmed_sent')->default(0);  // WhatsApp order confirm sent?
            $table->tinyInteger('wa_ready_sent')->default(0);      // WhatsApp order ready sent?
            $table->tinyInteger('wa_review_sent')->default(0);     // WhatsApp review request sent?
            // CRM sync
            $table->unsignedBigInteger('contact_id')->nullable();  // FK to contacts table
            $table->timestamps();

            $table->foreign('restaurant_id')->references('id')->on('menu_restaurants')->onDelete('cascade');
        });

        // ─── Order line items (snapshot of item at order time) ─────────
        Schema::create('menu_order_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('order_id')->index();
            $table->unsignedBigInteger('item_id')->nullable();     // nullable in case item deleted
            // ⚡ SECURITY: snapshot fields — price is locked at time of order
            // Even if item price changes later, historical order is accurate
            $table->string('item_name_ar', 150);
            $table->string('item_name_en', 150);
            $table->unsignedInteger('unit_price_fils');            // base price snapshot
            $table->unsignedSmallInteger('qty')->default(1);
            $table->json('modifiers_snapshot')->nullable();        // [{name_ar, name_en, price_add_fils}]
            $table->unsignedInteger('modifier_total_fils')->default(0);
            $table->unsignedInteger('line_total_fils');            // (unit_price + modifier_total) * qty
            $table->timestamps();

            $table->foreign('order_id')->references('id')->on('menu_orders')->onDelete('cascade');
        });

        // ─── Order status history log ──────────────────────────────────
        Schema::create('menu_order_status_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('order_id')->index();
            $table->string('from_status', 30)->nullable();
            $table->string('to_status', 30);
            $table->string('note', 255)->nullable();
            $table->unsignedBigInteger('changed_by')->nullable();  // user_id (staff)
            $table->timestamp('changed_at')->useCurrent();

            $table->foreign('order_id')->references('id')->on('menu_orders')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('menu_order_status_logs');
        Schema::dropIfExists('menu_order_items');
        Schema::dropIfExists('menu_orders');
        Schema::dropIfExists('menu_tables');
    }
};
