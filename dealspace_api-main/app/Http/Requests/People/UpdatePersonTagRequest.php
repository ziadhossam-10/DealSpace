<?php

namespace App\Http\Requests\People;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePersonTagRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'name' => 'string|max:255',
            'color' => 'nullable|string|max:50',
            'description' => 'nullable|string|max:1000'
        ];
    }
}
