<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('blog_posts', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('excerpt');
            $table->longText('content');
            $table->string('image')->nullable();
            $table->string('author');
            $table->string('author_avatar')->nullable();
            $table->integer('likes')->default(0);
            $table->integer('dislikes')->default(0);
            $table->integer('views')->default(0);
            $table->integer('read_time')->nullable();
            $table->string('category')->nullable();
            $table->timestamps();
        });

        // Create a separate table for tags (many-to-many relationship)
        Schema::create('tags', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->timestamps();
        });

        // Create a pivot table for blog_posts and tags
        Schema::create('blog_post_tag', function (Blueprint $table) {
            $table->id();
            $table->foreignId('blog_post_id')->constrained()->onDelete('cascade');
            $table->foreignId('tag_id')->constrained()->onDelete('cascade');
            $table->unique(['blog_post_id', 'tag_id']);
        });

        // Create a table for comments
        Schema::create('comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('blog_post_id')->constrained()->onDelete('cascade');
            $table->string('author');
            $table->text('content');
            $table->string('author_avatar')->nullable();
            $table->integer('likes')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('comments');
        Schema::dropIfExists('blog_post_tag');
        Schema::dropIfExists('tags');
        Schema::dropIfExists('blog_posts');
    }
};
