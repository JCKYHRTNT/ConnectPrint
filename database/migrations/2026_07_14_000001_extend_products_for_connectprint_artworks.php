<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->after('id')->constrained()->nullOnDelete();
            $table->string('slug')->nullable()->after('name')->index();
            $table->string('original_filename')->nullable()->after('description');
            $table->string('original_path')->nullable()->after('original_filename');
            $table->string('preview_path')->nullable()->after('original_path');
            $table->string('mime_type')->nullable()->after('preview_path');
            $table->unsignedBigInteger('file_size')->nullable()->after('mime_type');
            $table->unsignedInteger('width')->nullable()->after('file_size');
            $table->unsignedInteger('height')->nullable()->after('width');
            $table->string('visibility', 20)->default('public')->after('height')->index();
            $table->string('share_token', 64)->nullable()->unique()->after('visibility');
            $table->boolean('is_printable')->default(true)->after('share_token')->index();
            $table->string('moderation_status', 20)->default('approved')->after('is_printable')->index();
            $table->text('moderation_reason')->nullable()->after('moderation_status');
            $table->timestamp('published_at')->nullable()->after('moderation_reason');
            $table->timestamp('archived_at')->nullable()->after('published_at');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropConstrainedForeignId('user_id');
            $table->dropUnique(['share_token']);
            $table->dropIndex(['slug']);
            $table->dropIndex(['visibility']);
            $table->dropIndex(['is_printable']);
            $table->dropIndex(['moderation_status']);
            $table->dropColumn([
                'slug',
                'original_filename',
                'original_path',
                'preview_path',
                'mime_type',
                'file_size',
                'width',
                'height',
                'visibility',
                'share_token',
                'is_printable',
                'moderation_status',
                'moderation_reason',
                'published_at',
                'archived_at',
            ]);
        });
    }
};
