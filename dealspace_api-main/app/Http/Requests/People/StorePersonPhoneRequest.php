<?php

namespace App\Http\Requests\People;

use Illuminate\Foundation\Http\FormRequest;

class StorePersonPhoneRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'value' => 'required|string|max:20',
            'type' => 'required|string|in:home,mobile,work,other',
            'is_primary' => 'boolean',
            'status' => 'string|in:Valid,Invalid,Not Validated'
        ];
    }
}
