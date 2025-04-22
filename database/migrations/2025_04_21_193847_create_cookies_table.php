<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCookiesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('cookies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('snapshot_id')->constrained()->onDelete('cascade');
            $table->string('name')->nullable();
            $table->text('value')->nullable();
            $table->string('domain')->nullable();
            $table->text('path')->nullable();
            $table->bigInteger('expires')->nullable();
            $table->boolean('secure')->nullable();
            $table->boolean('http_only')->nullable();
            $table->string('same_site')->nullable();
            $table->timestamps();

            $table->index('name');
            $table->index('domain');
            $table->index('snapshot_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('cookies');
    }
}
