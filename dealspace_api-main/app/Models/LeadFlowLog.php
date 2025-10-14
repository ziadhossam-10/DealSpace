<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeadFlowLog extends Model
{
    protected $fillable = [
        'tenant_id',
        'person_id',
        'lead_flow_rule_id',
        'action',
        'rule_data',
        'conditions_met',
    ];

    protected $casts = [
        'rule_data' => 'array',
        'conditions_met' => 'array',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class);
    }

    public function leadFlowRule(): BelongsTo
    {
        return $this->belongsTo(LeadFlowRule::class);
    }
}