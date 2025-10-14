<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('person_custom_field_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('person_id')->constrained()->onDelete('cascade');
            $table->foreignId('custom_field_id')->constrained()->onDelete('cascade');
            $table->text('value')->nullable(); // Store the actual value
            $table->timestamps();

            // Ensure one value per person per custom field
            $table->unique(['person_id', 'custom_field_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('person_custom_field_values');
    }
};
