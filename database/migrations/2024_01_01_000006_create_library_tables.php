<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('digital_resources', function (Blueprint $table) {
            $table->id();
            $table->foreignId('book_id')->constrained()->onDelete('cascade');
            $table->string('title');
            $table->string('file_path');
            $table->enum('file_type', ['pdf', 'epub', 'audio', 'video'])->default('pdf');
            $table->unsignedBigInteger('file_size')->nullable();
            $table->string('thumbnail')->nullable();
            $table->string('duration')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('reading_progress', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('digital_resource_id')->constrained()->onDelete('cascade');
            $table->unsignedInteger('current_page')->default(1);
            $table->decimal('progress_percent', 5, 2)->default(0);
            $table->json('bookmarks')->nullable();
            $table->timestamp('last_read_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'digital_resource_id']);
        });

        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('title');
            $table->text('message');
            $table->enum('type', ['borrow', 'return', 'overdue', 'reservation', 'fine', 'system', 'recommendation'])->default('system');
            $table->boolean('is_read')->default(false);
            $table->string('action_url')->nullable();
            $table->json('data')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'is_read']);
        });

        Schema::create('book_favorites', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('book_id')->constrained()->onDelete('cascade');
            $table->timestamps();

            $table->unique(['user_id', 'book_id']);
        });

        Schema::create('book_ratings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('book_id')->constrained()->onDelete('cascade');
            $table->unsignedTinyInteger('rating');
            $table->text('review')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'book_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('book_ratings');
        Schema::dropIfExists('book_favorites');
        Schema::dropIfExists('notifications');
        Schema::dropIfExists('reading_progress');
        Schema::dropIfExists('digital_resources');
    }
};
