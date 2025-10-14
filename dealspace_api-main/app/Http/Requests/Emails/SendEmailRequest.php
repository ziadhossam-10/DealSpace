<?php

namespace App\Http\Requests\Emails;

use Illuminate\Foundation\Http\FormRequest;

class SendEmailRequest extends FormRequest
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
            'account_id' => 'required|exists:email_accounts,id',
            'person_id' => 'required|exists:people,id',
            'to_email' => 'required|email',
            'subject' => 'required|string',
            'body' => 'required|string',
            'body_html' => 'nullable|string'
        ];
    }
}
