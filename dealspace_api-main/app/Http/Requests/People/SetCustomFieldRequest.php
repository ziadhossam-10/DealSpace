<?php

namespace App\Http\Requests\People;

use Illuminate\Foundation\Http\FormRequest;

class SetCustomFieldRequest extends FormRequest
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
            "custom_fields" => [
                "required",
                "array",
            ],
            "custom_fields.*.id" => [
                "required",
                "integer",
                "exists:custom_fields,id",
            ],
            "custom_fields.*.value" => [
                "required",
                "string",
                "max:1000",
            ],
        ];
    }
}
