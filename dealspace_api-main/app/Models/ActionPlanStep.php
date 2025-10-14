<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ActionPlanStep extends Model
{
    protected $fillable = [
        'action_plan_id',
        'name',
        'description',
        'type',
        'delay_days',
        'delay_hours',
        'order',
        'metadata',
    ];

    protected $casts = [
        'delay_days' => 'integer',
        'delay_hours' => 'integer',
        'order' => 'integer',
        'metadata' => 'array',
    ];

    public function actionPlan(): BelongsTo
    {
        return $this->belongsTo(ActionPlan::class);
    }

    public function executions(): HasMany
    {
        return $this->hasMany(ActionPlanExecution::class);
    }

    public function getTotalDelayInHours(): int
    {
        return ($this->delay_days * 24) + $this->delay_hours;
    }
}