<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateBlogPostsTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('blog_posts', function (Blueprint $table) {
            // Make excerpt nullable
            $table->text('excerpt')->nullable()->change();
            // Add is_published column, default to false
            $table->boolean('is_published')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('blog_posts', function (Blueprint $table) {
            // Revert excerpt to non-nullable (assuming it was required before)
            $table->text('excerpt')->nullable(false)->change();
            // Drop is_published column
            $table->dropColumn('is_published');
        });
    }
}
