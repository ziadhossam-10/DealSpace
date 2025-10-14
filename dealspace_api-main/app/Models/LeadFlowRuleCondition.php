<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeadFlowRuleCondition extends Model
{
    protected $fillable = [
        'lead_flow_rule_id',
        'field',
        'operator',
        'value',
        'order',
    ];

    protected $casts = [
        'order' => 'integer',
    ];

    public $timestamps = false;

    /**
     * Relationship: Parent rule
     */
    public function leadFlowRule(): BelongsTo
    {
        return $this->belongsTo(LeadFlowRule::class, 'lead_flow_rule_id');
    }
}