<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cart_items', function (Blueprint $table) {
            $table->string('printbox_mode', 20)->nullable()->after('printbox_requested');
            $table->integer('printbox_sheet_count')->default(1)->after('printbox_mode');
        });

        Schema::table('purchase_items', function (Blueprint $table) {
            $table->string('printbox_mode', 20)->nullable()->after('printbox_requested');
            $table->integer('printbox_sheet_count')->default(1)->after('printbox_mode');
        });
    }

    public function down(): void
    {
        Schema::table('purchase_items', function (Blueprint $table) {
            $table->dropColumn(['printbox_mode', 'printbox_sheet_count']);
        });

        Schema::table('cart_items', function (Blueprint $table) {
            $table->dropColumn(['printbox_mode', 'printbox_sheet_count']);
        });
    }
};
