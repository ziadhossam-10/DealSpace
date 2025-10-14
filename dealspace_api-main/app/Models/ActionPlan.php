<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ActionPlan extends Model
{
    protected $fillable = [
        'tenant_id',
        'name',
        'description',
        'is_active',
        'leads_count',
        'last_lead_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'leads_count' => 'integer',
        'last_lead_at' => 'datetime',
    ];

    public function steps(): HasMany
    {
        return $this->hasMany(ActionPlanStep::class)->orderBy('order');
    }

    public function executions(): HasMany
    {
        return $this->hasMany(ActionPlanExecution::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}