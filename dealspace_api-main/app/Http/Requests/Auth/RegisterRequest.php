<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;
use App\Enums\ProviderEnum;
use App\Enums\IndustryEnum;
use App\Enums\UsageEnum;

class RegisterRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6|confirmed',
            'avatar' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',

            'provider' => ['nullable', new Enum(ProviderEnum::class)],
            'industry' => ['required', new Enum(IndustryEnum::class)],
            'usage' => ['required', new Enum(UsageEnum::class)],
        ];
    }
}
