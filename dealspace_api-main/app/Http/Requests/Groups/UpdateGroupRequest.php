<?php

namespace App\Http\Requests\Groups;

use App\Enums\GroupDistributionEnum;
use App\Enums\GroupTypeEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateGroupRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true; // You may want to add authorization logic here
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $groupId = $this->route('group');

        return [
            'name' => 'sometimes|string|max:255',
            'type' => ['sometimes', 'in:' . $this->getEnumValues(GroupTypeEnum::class)],
            'distribution' => ['sometimes', 'in:' . $this->getEnumValues(GroupDistributionEnum::class)],
            'default_user_id' => 'sometimes||nullable|integer|exists:users,id',
            'default_pond_id' => 'sometimes|nullable|integer|exists:ponds,id',
            'default_group_id' => [
                'sometimes',
                'integer',
                'nullable',
                'exists:groups,id',
                Rule::notIn([$groupId]) // Prevent self-reference
            ],
            'claim_window' => 'sometimes|integer',
            'is_primary' => 'sometimes|boolean',
            'user_ids' => 'sometimes|array',
            'user_ids.*' => 'integer|exists:users,id',
            'user_ids_to_delete' => 'sometimes|array',
            'user_ids_to_delete.*' => 'integer|exists:users,id',
        ];
    }

    /**
     * Get the enum values as a comma-separated string
     *
     * @param string $enumClass
     * @return string
     */
    protected function getEnumValues($enumClass)
    {
        return implode(',', array_map(fn($case) => $case->value, $enumClass::cases()));
    }
}
