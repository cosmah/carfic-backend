<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEmailFindingsTable extends Migration
{
    public function up()
    {
        Schema::create('email_findings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('snapshot_id')->constrained('snapshots')->onDelete('cascade');
            $table->string('domain');
            $table->string('data_type'); // e.g., 'cookie', 'localStorage', etc.
            $table->string('source_name'); // e.g., cookie name or key
            $table->text('value')->nullable(); // The original value; consider encrypting this
            $table->string('match'); // The detected email or phone
            $table->string('match_type'); // e.g., 'direct', 'base64'
            $table->string('confidence'); // e.g., 'high', 'medium'
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('email_findings');
    }
}
