<?php

namespace App\Http\Requests\CustomFields;

use App\Enums\CustomFieldTypeEnum;
use Illuminate\Foundation\Http\FormRequest;

class UpdateCustomFieldRequest extends FormRequest
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
            'label' => 'required|string|max:255',
            'type' => ['required', 'in:' . $this->getEnumValues(CustomFieldTypeEnum::class)],
            'options' => [
                'required_if:type,' . CustomFieldTypeEnum::DROPDOWN->value,
                'array',
            ],
            'options.*' => 'string|max:255',
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
