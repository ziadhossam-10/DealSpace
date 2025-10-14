<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

class LeadFlowRule extends Model
{
    protected $fillable = [
        'tenant_id',
        'name',
        'description',
        'source_type',
        'source_name',
        'priority',
        'is_active',
        'is_default',
        'match_type',
        'assigned_agent_id',
        'assigned_lender_id',
        'group_id',
        'pond_id',
        'action_plan_id',
        'leads_count',
        'last_lead_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_default' => 'boolean',
        'priority' => 'integer',
        'leads_count' => 'integer',
        'last_lead_at' => 'datetime',
    ];

    /**
     * Relationship: Rule conditions
     */
    public function ruleConditions(): HasMany
    {
        return $this->hasMany(LeadFlowRuleCondition::class, 'lead_flow_rule_id')
            ->orderBy('order');
    }

    /**
     * Relationship: Assigned agent
     */
    public function assignedAgent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_agent_id');
    }

    /**
     * Relationship: Assigned lender
     */
    public function assignedLender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_lender_id');
    }

    /**
     * Relationship: Group
     */
    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }

    /**
     * Relationship: Pond
     */
    public function pond(): BelongsTo
    {
        return $this->belongsTo(Pond::class);
    }

    /**
     * Relationship: Action plan
     */
    public function actionPlan(): BelongsTo
    {
        return $this->belongsTo(ActionPlan::class);
    }

    /**
     * Scope: Order by priority
     */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('priority', 'asc')->orderBy('id', 'asc');
    }

    /**
     * Scope: Filter by source
     */
    public function scopeForSource(Builder $query, string $sourceType, string $sourceName): Builder
    {
        return $query->where(function ($q) use ($sourceType, $sourceName) {
            $q->where(function ($subQ) use ($sourceType, $sourceName) {
                $subQ->where('source_type', $sourceType)
                     ->where('source_name', $sourceName);
            })->orWhere('is_default', true);
        });
    }

    /**
     * Scope: Only active rules
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}