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
        Schema::create('calls', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('person_id')->nullable(); // Nullable for incoming calls without person association
            $table->string('phone');
            $table->boolean('is_incoming');
            $table->text('note')->nullable(); // Changed to text for longer notes
            $table->string('outcome')->nullable(); // Changed to string to match service usage
            $table->integer('duration')->nullable()->default(0); // Nullable with default
            $table->string('to_number')->nullable();
            $table->string('from_number')->nullable();
            $table->unsignedBigInteger('user_id')->nullable(); // Nullable for incoming calls before agent assignment
            $table->string('recording_url')->nullable();
            $table->string('recording_sid')->nullable(); // Missing field from service
            $table->string('twilio_call_sid')->nullable()->unique(); // Missing field from service
            $table->string('status')->nullable(); // Missing field from service
            $table->timestamps();

            // Foreign key constraints
            $table->foreign('person_id')->references('id')->on('people')->onDelete('set null');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');

            // Tenant support
            $table->uuid('tenant_id')->nullable()->index();
            $table->foreign('tenant_id')
                ->references('id')
                ->on('tenants')
                ->onDelete('cascade');

            // Indexes for better performance
            $table->index(['is_incoming', 'created_at']);
            $table->index(['user_id', 'created_at']);
            $table->index(['person_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('calls');
    }
};
