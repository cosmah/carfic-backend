<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSnapshotsTable extends Migration
{
    public function up()
    {
        Schema::create('snapshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('cascade'); // Assuming you have a users table for authentication
            $table->timestamp('timestamp')->useCurrent();
            $table->string('domain_filter')->nullable();
            $table->json('summary')->nullable(); // Stores the summary JSON
            $table->json('data')->nullable(); // Stores the full data (cookies, localStorage, etc.) as JSON for simplicity
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('snapshots');
    }
}
