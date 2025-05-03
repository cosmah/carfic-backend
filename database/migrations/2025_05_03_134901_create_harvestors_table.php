<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('harvestor', function (Blueprint $table) {
            $table->id();
            $table->string('type'); // 'email' or 'phone'
            $table->string('value')->unique();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('harvestor');
    }
};
