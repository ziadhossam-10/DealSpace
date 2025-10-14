<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('person_emails', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('person_id');
            $table->string('value');
            $table->string('type')->default('home');
            $table->boolean('is_primary')->default(false);
            $table->string('status')->default('Not Validated');
            $table->timestamps();

            $table->foreign('person_id')->references('id')->on('people')->onDelete('cascade');
            $table->unique(['person_id', 'value']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('person_emails');
    }
}; 