<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tags', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('slug')->unique();
            $table->timestamps();
        });

        Schema::create('product_tag', function (Blueprint $table) {
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tag_id')->constrained()->cascadeOnDelete();
            $table->primary(['product_id', 'tag_id']);
        });

        Schema::table('cart_items', function (Blueprint $table) {
            $table->integer('creator_price_snapshot')->nullable()->after('quantity');
            $table->unique(['cart_id', 'product_id']);
        });

        Schema::create('purchases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('purchase_number')->unique();
            $table->string('status', 20)->default('completed');
            $table->string('payment_status', 30)->default('simulated_paid');
            $table->integer('subtotal')->default(0);
            $table->integer('total')->default(0);
            $table->timestamps();
        });

        Schema::create('purchase_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('creator_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('artwork_title_snapshot');
            $table->string('creator_name_snapshot');
            $table->integer('creator_price');
            $table->string('original_path_snapshot')->nullable();
            $table->timestamps();
        });

        Schema::create('app_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('message');
            $table->string('url')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });

        Schema::create('artwork_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('reporter_id')->constrained('users')->cascadeOnDelete();
            $table->string('reason', 30);
            $table->text('details')->nullable();
            $table->string('status', 20)->default('open');
            $table->timestamps();
            $table->unique(['product_id', 'reporter_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('artwork_reports');
        Schema::dropIfExists('app_notifications');
        Schema::dropIfExists('purchase_items');
        Schema::dropIfExists('purchases');
        Schema::table('cart_items', function (Blueprint $table) {
            $table->dropUnique(['cart_id', 'product_id']);
            $table->dropColumn('creator_price_snapshot');
        });
        Schema::dropIfExists('product_tag');
        Schema::dropIfExists('tags');
    }
};
