<?php

namespace App\Http\Requests\Deals;

use Illuminate\Foundation\Http\FormRequest;

class UpdateDealRequest extends FormRequest
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
            'name' => ['sometimes', 'string', 'max:255'],
            'stage_id' => ['sometimes', 'integer', 'exists:deal_stages,id'],
            'type_id' => ['sometimes', 'integer', 'exists:deal_types,id'],
            'description' => ['sometimes', 'string'],
            'people_ids' => ['sometimes'],
            'people_ids.*' => ['integer', 'exists:people,id'],
            'users_ids' => ['array'],
            'users_ids.*' => ['integer', 'exists:users,id'],
            'price' => ['sometimes', 'integer', 'min:0'],
            'projected_close_date' => ['sometimes', 'date'],
            'order_weight' => ['sometimes', 'integer', 'min:0'],
            'commission_value' => ['sometimes', 'integer', 'min:0'],
            'agent_commission' => ['sometimes', 'integer', 'min:0'],
            'team_commission' => ['sometimes', 'integer', 'min:0'],
            'people_ids_to_delete' => ['array'],
            'people_ids_to_delete.*' => ['integer', 'exists:people,id'],
            'user_ids_to_delete' => ['array'],
            'user_ids_to_delete.*' => ['integer', 'exists:users,id'],
            'attachments' => ['array'],
            'attachments.*' => "file",
            'attachments_to_delete' => ['array'],
            'attachments_to_delete.*' => ['integer', 'exists:deal_attachments,id'],
        ];
    }
}