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
        Schema::create('action_plans', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id');
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('leads_count')->default(0);
            $table->timestamp('last_lead_at')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'is_active']);
        });

        Schema::create('action_plan_steps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('action_plan_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->text('description')->nullable();
            $table->enum('type', ['task', 'email', 'sms', 'call', 'note']);
            $table->integer('delay_days')->default(0);
            $table->integer('delay_hours')->default(0);
            $table->integer('order')->default(0);
            $table->json('metadata')->nullable(); // For email templates, SMS content, etc.
            $table->timestamps();

            $table->index(['action_plan_id', 'order']);
        });

        Schema::create('action_plan_executions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('action_plan_id')->constrained()->onDelete('cascade');
            $table->foreignId('action_plan_step_id')->constrained()->onDelete('cascade');
            $table->foreignId('person_id')->constrained()->onDelete('cascade');
            $table->foreignId('assigned_to_user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->enum('status', ['pending', 'completed', 'skipped', 'failed'])->default('pending');
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['person_id', 'status']);
            $table->index(['assigned_to_user_id', 'status']);
            $table->index('scheduled_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('action_plan_executions');
        Schema::dropIfExists('action_plan_steps');
        Schema::dropIfExists('action_plans');
    }
};
