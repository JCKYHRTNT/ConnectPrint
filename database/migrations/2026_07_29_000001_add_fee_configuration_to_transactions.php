<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('app_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('value')->nullable();
            $table->timestamps();
        });

        DB::table('app_settings')->insert([
            ['key' => 'application_fee', 'value' => '10000', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'printbox_bw_low_fee', 'value' => '750', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'printbox_bw_bulk_fee', 'value' => '500', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'printbox_color_fee', 'value' => '750', 'created_at' => now(), 'updated_at' => now()],
        ]);

        Schema::table('purchases', function (Blueprint $table) {
            $table->integer('application_fee')->default(0)->after('subtotal');
            $table->integer('printbox_fee')->default(0)->after('application_fee');
            $table->string('payment_method')->nullable()->after('payment_status');
        });

        Schema::table('purchase_items', function (Blueprint $table) {
            $table->integer('printbox_fee')->default(0)->after('creator_price');
        });
    }

    public function down(): void
    {
        Schema::table('purchase_items', function (Blueprint $table) {
            $table->dropColumn('printbox_fee');
        });

        Schema::table('purchases', function (Blueprint $table) {
            $table->dropColumn(['application_fee', 'printbox_fee', 'payment_method']);
        });

        Schema::dropIfExists('app_settings');
    }
};
