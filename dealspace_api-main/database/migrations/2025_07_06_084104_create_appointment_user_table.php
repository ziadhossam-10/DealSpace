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
        Schema::create('appointment_user', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('appointment_id');
            $table->unsignedBigInteger('user_id');
            $table->enum('response_status', ['pending', 'accepted', 'declined', 'maybe'])->default('pending');
            $table->timestamp('responded_at')->nullable();
            $table->timestamps();

            // Foreign key constraints
            $table->foreign('appointment_id')->references('id')->on('appointments')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');

            // Unique constraint to prevent duplicate invitations
            $table->unique(['appointment_id', 'user_id']);

            // Indexes for better performance
            $table->index(['appointment_id']);
            $table->index(['user_id']);
            $table->index(['response_status']);
            $table->index(['appointment_id', 'response_status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('appointment_user');
    }
};
