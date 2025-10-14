<?php

namespace App\Http\Requests\Calls;

use App\Enums\OutcomeOptionsEnum;
use Illuminate\Foundation\Http\FormRequest;

class UpdateCallRequest extends FormRequest
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
            'phone'             => ['sometimes', 'string'],
            'is_incoming'       => ['sometimes', 'boolean'],
            'to_number'         => ['sometimes', 'string'],
            'from_number'       => ['sometimes', 'string'],
            'note'              => ['nullable', 'string'],
            'outcome'           => ['nullable', 'integer', 'in:' . $this->getEnumValues(OutcomeOptionsEnum::class)],
            'duration'          => ['nullable', 'integer', 'min:0'],
            'recording_url'     => ['nullable', 'url'],

            'mentions'          => ['nullable', 'array'],
            'mentions.*'        => ['integer', 'exists:users,id'],
            'mentions_to_add'   => ['nullable', 'array'],
            'mentions_to_add.*' => ['integer', 'exists:users,id'],
            'mentions_to_remove' => ['nullable', 'array'],
            'mentions_to_remove.*' => ['integer', 'exists:users,id'],
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
