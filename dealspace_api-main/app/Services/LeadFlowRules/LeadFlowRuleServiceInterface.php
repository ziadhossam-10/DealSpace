<?php

namespace App\Services\LeadFlowRules;

use App\Models\Person;

interface LeadFlowRuleServiceInterface
{
    public function processLead(Person $lead, ?string $sourceType = null, ?string $sourceName = null): void;
}
