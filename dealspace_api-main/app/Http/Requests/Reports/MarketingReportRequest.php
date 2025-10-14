<?php

namespace App\Http\Requests\Reports;

use Illuminate\Foundation\Http\FormRequest;

class MarketingReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'date_filter' => 'string|in:today,yesterday,last_7_days,last_14_days,last_30_days,this_month,last_month,this_year',
            'campaign_filter' => 'string'
        ];
    }

    /**
     * Get the validated filters as an object
     */
    public function getFilters(): object
    {
        $validated = $this->validated();

        return (object) [
            'dateFilter' => $validated['date_filter'] ?? 'last_30_days',
            'campaignFilter' => $validated['campaign_filter'] ?? 'all'
        ];
    }

    /**
     * Get the validated filters as an array (alternative method)
     */
    public function getFiltersArray(): array
    {
        $validated = $this->validated();

        return [
            'dateFilter' => $validated['date_filter'] ?? 'last_30_days',
            'campaignFilter' => $validated['campaign_filter'] ?? 'all'
        ];
    }
}
