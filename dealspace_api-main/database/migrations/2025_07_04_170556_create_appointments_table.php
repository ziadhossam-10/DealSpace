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
        Schema::create('appointments', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->boolean('all_day')->default(false);
            $table->datetime('start');
            $table->datetime('end');
            $table->string('location')->nullable();
            $table->unsignedBigInteger('created_by_id');
            $table->unsignedBigInteger('type_id')->nullable();
            $table->unsignedBigInteger('outcome_id')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Foreign key constraints
            $table->foreign('created_by_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('type_id')->references('id')->on('appointment_types')->onDelete('set null');
            $table->foreign('outcome_id')->references('id')->on('appointment_outcomes')->onDelete('set null');

            // Tenant support
            $table->uuid('tenant_id')->nullable()->index();
            $table->foreign('tenant_id')
                ->references('id')
                ->on('tenants')
                ->onDelete('cascade');

            // Indexes for better performance
            $table->index(['created_by_id', 'start']);
            $table->index(['type_id', 'start']);
            $table->index(['outcome_id', 'start']);
            $table->index(['start', 'end']);
            $table->index(['all_day', 'start']);
            $table->index(['created_at']);
            $table->index(['start']);
            $table->index(['end']);

            // Composite indexes for common queries
            $table->index(['created_by_id', 'all_day', 'start']);
            $table->index(['type_id', 'created_by_id', 'start']);
            $table->index(['start', 'created_by_id']);
            $table->index(['end', 'created_by_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('appointments');
    }
};
