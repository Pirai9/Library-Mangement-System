<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('books', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('author');
            $table->string('isbn')->unique()->nullable();
            $table->foreignId('category_id')->constrained()->onDelete('cascade');
            $table->string('publisher')->nullable();
            $table->smallInteger('publication_year')->nullable();
            $table->text('description')->nullable();
            $table->string('cover_image')->nullable();
            $table->unsignedInteger('total_quantity')->default(1);
            $table->unsignedInteger('available_quantity')->default(1);
            $table->string('shelf_location')->nullable();
            $table->string('language')->default('English');
            $table->unsignedInteger('pages')->nullable();
            $table->decimal('price', 8, 2)->nullable();
            $table->string('tags')->nullable();
            $table->string('qr_code')->nullable();
            $table->unsignedInteger('borrow_count')->default(0);
            $table->unsignedInteger('reservation_count')->default(0);
            $table->decimal('rating', 3, 2)->default(0);
            $table->boolean('has_digital')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['title', 'author']);
            $table->index('isbn');
            $table->fullText(['title', 'author', 'description']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('books');
    }
};
