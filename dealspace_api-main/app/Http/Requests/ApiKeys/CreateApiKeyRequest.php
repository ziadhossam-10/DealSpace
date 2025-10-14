<?php

namespace App\Http\Requests\ApiKeys;

use Illuminate\Foundation\Http\FormRequest;

class CreateApiKeyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'allowed_domains' => 'nullable|array',
            'allowed_domains.*' => 'string|max:255',
            'allowed_endpoints' => 'nullable|array',
            'allowed_endpoints.*' => 'string|max:255',
        ];
    }
}
