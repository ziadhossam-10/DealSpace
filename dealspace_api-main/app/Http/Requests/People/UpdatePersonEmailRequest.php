<?php

namespace App\Http\Requests\People;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePersonEmailRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'value' => 'email|max:255',
            'type' => 'string|in:home,work,other',
            'is_primary' => 'boolean',
            'status' => 'string|in:Valid,Invalid,Not Validated'
        ];
    }
}
