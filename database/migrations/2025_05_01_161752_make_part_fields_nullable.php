<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class MakePartFieldsNullable extends Migration
{
    public function up()
    {
        Schema::table('parts', function (Blueprint $table) {
            $table->float('price')->nullable()->change();
            $table->float('original_price')->nullable()->change();
            $table->integer('stock')->nullable()->change();
            $table->float('rating')->nullable()->change();
            $table->integer('review_count')->nullable()->change();
        });
    }

    public function down()
    {
        Schema::table('parts', function (Blueprint $table) {
            $table->float('price')->nullable(false)->change();
            $table->float('original_price')->nullable()->change();
            $table->integer('stock')->nullable(false)->change();
            $table->float('rating')->nullable()->change();
            $table->integer('review_count')->nullable()->change();
        });
    }
}
