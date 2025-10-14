<?php

namespace App\Http\Requests\People;

use Illuminate\Foundation\Http\FormRequest;

class StorePersonCollaboratorRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'name' => 'required|string|max:255',
            'assigned' => 'boolean',
            'role' => 'nullable|string|max:100'
        ];
    }
}