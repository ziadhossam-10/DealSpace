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
        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('person_id');
            $table->unsignedBigInteger('assigned_user_id');
            $table->string('name');
            $table->enum('type', [
                'Follow Up',
                'Call',
                'Text',
                'Email',
                'Appointment',
                'Showing',
                'Closing',
                'Open House',
                'Thank You'
            ]);
            $table->boolean('is_completed')->default(false);
            $table->date('due_date')->nullable();
            $table->timestamp('due_date_time')->nullable();
            $table->integer('remind_seconds_before')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Foreign key constraints
            $table->foreign('person_id')->references('id')->on('people')->onDelete('cascade');
            $table->foreign('assigned_user_id')->references('id')->on('users')->onDelete('cascade');

            // Tenant support
            $table->uuid('tenant_id')->nullable()->index();
            $table->foreign('tenant_id')
                ->references('id')
                ->on('tenants')
                ->onDelete('cascade');

            // Indexes for better performance
            $table->index(['person_id', 'is_completed']);
            $table->index(['assigned_user_id', 'is_completed']);
            $table->index(['due_date', 'is_completed']);
            $table->index(['due_date_time', 'is_completed']);
            $table->index(['type', 'is_completed']);
            $table->index(['created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tasks');
    }
};
