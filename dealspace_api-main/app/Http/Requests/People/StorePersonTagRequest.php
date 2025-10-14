<?php

namespace App\Http\Requests\People;

use Illuminate\Foundation\Http\FormRequest;

class StorePersonTagRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'name' => 'required|string|max:255',
            'color' => 'nullable|string|max:50',
            'description' => 'nullable|string|max:1000'
        ];
    }
}
