<?php
// database/migrations/2025_04_30_000000_create_parts_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('parts', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('category');
            $table->decimal('price', 8, 2);
            $table->decimal('original_price', 8, 2)->nullable();
            $table->integer('stock');
            $table->float('rating')->nullable();
            $table->integer('review_count')->default(0);
            $table->text('description');
            $table->json('features')->nullable();
            $table->json('specifications')->nullable();
            $table->json('images')->nullable();
            $table->json('compatibility')->nullable();
            $table->string('image')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('parts');
    }
};
