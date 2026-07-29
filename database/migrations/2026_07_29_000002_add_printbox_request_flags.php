<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cart_items', function (Blueprint $table) {
            $table->boolean('printbox_requested')->default(false)->after('creator_price_snapshot');
        });

        Schema::table('purchase_items', function (Blueprint $table) {
            $table->boolean('printbox_requested')->default(false)->after('printbox_fee');
        });
    }

    public function down(): void
    {
        Schema::table('purchase_items', function (Blueprint $table) {
            $table->dropColumn('printbox_requested');
        });

        Schema::table('cart_items', function (Blueprint $table) {
            $table->dropColumn('printbox_requested');
        });
    }
};
