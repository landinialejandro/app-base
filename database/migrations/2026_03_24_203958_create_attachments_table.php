<?php

// FILE: database/migrations/2026_03_24_000000_create_attachments_table.php | V1

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attachments', function (Blueprint $table) {
            $table->id();

            $table->char('tenant_id', 36);
            $table->string('attachable_type');
            $table->unsignedBigInteger('attachable_id');

            $table->foreignId('uploaded_by_user_id')->nullable()->constrained('users')->nullOnDelete();

            $table->string('disk', 50)->default('local');
            $table->string('directory')->nullable();
            $table->string('stored_name');
            $table->string('original_name');
            $table->string('extension', 20)->nullable();
            $table->string('mime_type', 120)->nullable();
            $table->unsignedBigInteger('size_bytes')->default(0);
            $table->string('checksum_sha256', 64)->nullable();

            $table->string('kind', 50)->default('other');
            $table->string('category', 100)->nullable();
            $table->boolean('is_image')->default(false);
            $table->unsignedInteger('sort_order')->default(0);

            $table->string('title')->nullable();
            $table->text('description')->nullable();
            $table->json('tags_json')->nullable();

            $table->string('visibility', 50)->nullable();
            $table->json('meta_json')->nullable();

            $table->longText('extracted_text')->nullable();
            $table->string('analysis_status', 50)->nullable();
            $table->timestamp('analyzed_at')->nullable();
            $table->string('analysis_version', 50)->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();

            $table->index('tenant_id');
            $table->index(['attachable_type', 'attachable_id'], 'attachments_attachable_index');
            $table->index(['tenant_id', 'attachable_type', 'attachable_id'], 'attachments_tenant_attachable_index');
            $table->index('kind');
            $table->index('analysis_status');
            $table->index('uploaded_by_user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attachments');
    }
};
