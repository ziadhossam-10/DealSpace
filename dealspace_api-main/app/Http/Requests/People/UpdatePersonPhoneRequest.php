<?php

namespace App\Http\Requests\People;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePersonPhoneRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'value' => 'string|max:20',
            'type' => 'string|in:home,mobile,work,other',
            'is_primary' => 'boolean',
            'status' => 'string|in:Valid,Invalid,Not Validated'
        ];
    }
}
