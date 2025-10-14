<?php

namespace App\Http\Requests\Groups;

use App\Enums\GroupDistributionEnum;
use App\Enums\GroupTypeEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreGroupRequest extends FormRequest
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
        return [
            'name' => 'required|string|max:255',
            'type' => ['required', 'in:' . $this->getEnumValues(GroupTypeEnum::class)],
            'distribution' => ['required', 'in:' . $this->getEnumValues(GroupDistributionEnum::class)],
            'default_user_id' => 'sometimes|integer|exists:users,id',
            'default_pond_id' => 'sometimes|integer|exists:ponds,id',
            'default_group_id' => 'sometimes|integer|exists:groups,id|different:id',
            'claim_window' => 'sometimes|integer',
            'is_primary' => 'sometimes|boolean',
            'user_ids' => 'sometimes|array',
            'user_ids.*' => 'integer|exists:users,id',
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
