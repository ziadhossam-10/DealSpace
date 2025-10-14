<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lead_flow_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('source_type')->nullable(); // e.g., 'Website', 'Zillow', 'API'
            $table->string('source_name')->nullable(); // e.g., 'Buyers', 'Chuck Finley'
            $table->integer('priority')->default(0); // Lower number = higher priority
            $table->boolean('is_active')->default(true);
            $table->boolean('is_default')->default(false);
            $table->json('conditions')->nullable(); // Array of condition rules
            $table->string('match_type')->default('all'); // 'all' or 'any'
            $table->foreignId('assigned_agent_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('assigned_lender_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('action_plan_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('group_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('pond_id')->nullable()->constrained()->nullOnDelete();
            $table->json('metadata')->nullable(); // Additional settings
            $table->timestamp('last_lead_at')->nullable();
            $table->integer('leads_count')->default(0);
            $table->timestamps();
            
            $table->index(['tenant_id', 'is_active', 'priority']);
        });

        Schema::create('lead_flow_rule_conditions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lead_flow_rule_id')->constrained()->cascadeOnDelete();
            $table->string('field'); // e.g., 'price', 'location', 'custom_field_id'
            $table->string('operator'); // e.g., 'equals', 'greater_than', 'contains'
            $table->text('value');
            $table->integer('order')->default(0);
            $table->timestamps();
        });

        Schema::create('lead_flow_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('person_id')->constrained()->cascadeOnDelete();
            $table->foreignId('lead_flow_rule_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action'); // 'assigned', 'skipped', 'default_applied'
            $table->json('rule_data')->nullable();
            $table->json('conditions_met')->nullable();
            $table->timestamps();
            
            $table->index(['tenant_id', 'person_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lead_flow_logs');
        Schema::dropIfExists('lead_flow_rule_conditions');
        Schema::dropIfExists('lead_flow_rules');
    }
};