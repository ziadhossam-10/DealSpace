<?php

namespace App\Http\Requests\TrackingScript;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTrackingScriptRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'domain' => 'nullable|array',
            'domain.*' => 'string|max:255',
            'is_active' => 'boolean',
            'track_all_forms' => 'boolean',
            'form_selectors' => 'nullable|array',
            'form_selectors.*' => 'string|max:255',
            'field_mappings' => 'nullable|array',
            'auto_lead_capture' => 'boolean',
            'track_page_views' => 'boolean',
            'track_utm_parameters' => 'boolean',
            'custom_events' => 'nullable|array',
            'custom_events.*' => 'string|max:100',
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'The tracking script name is required when provided.',
            'name.max' => 'The tracking script name may not be greater than 255 characters.',
            'description.max' => 'The description may not be greater than 1000 characters.',
            'domain.array' => 'The domain field must be an array.',
            'domain.*.string' => 'Each domain must be a string.',
            'domain.*.max' => 'Each domain may not be greater than 255 characters.',
            'form_selectors.array' => 'The form selectors field must be an array.',
            'form_selectors.*.string' => 'Each form selector must be a string.',
            'form_selectors.*.max' => 'Each form selector may not be greater than 255 characters.',
            'field_mappings.array' => 'The field mappings must be an array.',
            'custom_events.array' => 'The custom events field must be an array.',
            'custom_events.*.string' => 'Each custom event must be a string.',
            'custom_events.*.max' => 'Each custom event may not be greater than 100 characters.',
        ];
    }
}