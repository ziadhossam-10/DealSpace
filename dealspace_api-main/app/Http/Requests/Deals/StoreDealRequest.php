<?php

namespace App\Http\Requests\Deals;

use Illuminate\Foundation\Http\FormRequest;

class StoreDealRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255'],
            'stage_id' => ['required', 'integer', 'exists:deal_stages,id'],
            'type_id' => ['required', 'integer', 'exists:deal_types,id'],
            'description' => ['nullable', 'string'],
            'people_ids' => ['nullable'],
            'people_ids.*' => ['integer', 'exists:people,id'],
            'users_ids' => ['array'],
            'users_ids.*' => ['integer', 'exists:users,id'],
            'price' => ['nullable', 'integer', 'min:0'],
            'projected_close_date' => ['nullable', 'date'],
            'order_weight' => ['nullable', 'integer', 'min:0'],
            'commission_value' => ['nullable', 'integer', 'min:0'],
            'agent_commission' => ['nullable', 'integer', 'min:0'],
            'team_commission' => ['nullable', 'integer', 'min:0'],
            'attachments' => ['array'],
            'attachments.*' => "file",
        ];
    }
}