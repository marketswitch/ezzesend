<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('template_cards', function (Blueprint $table) {
            if (!Schema::hasColumn('template_cards', 'media_path')) {
                $table->string('media_path', 255)->nullable()->after('media_id');
            }
        });
    }

    public function down(): void {
        Schema::table('template_cards', function (Blueprint $table) {
            if (Schema::hasColumn('template_cards', 'media_path')) {
                $table->dropColumn('media_path');
            }
        });
    }
};