<?php

namespace App\Http\Requests\Reports;

use Illuminate\Foundation\Http\FormRequest;

class CampaignDetailsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Adjust based on your authorization logic
    }

    public function rules(): array
    {
        return [
            'source' => 'required|string|max:100',
            'medium' => 'required|string|max:100',
            'campaign' => 'required|string|max:255',
            'date_filter' => 'string|in:today,yesterday,last_7_days,last_14_days,last_30_days,this_month,last_month,this_year'
        ];
    }

    public function messages(): array
    {
        return [
            'source.required' => 'Campaign source is required',
            'medium.required' => 'Campaign medium is required',
            'campaign.required' => 'Campaign name is required',
            'date_filter.in' => 'Invalid date filter option'
        ];
    }
}
