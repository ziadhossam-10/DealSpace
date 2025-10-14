<?php

namespace App\Http\Requests\People;

use Illuminate\Foundation\Http\FormRequest;

class StorePersonFileRequest extends FormRequest
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'file' => 'required|file|max:10240', // Max 10MB
            'name' => 'nullable|string|max:255',
            'type' => 'nullable|string|in:document,image,video,audio,spreadsheet,text,other',
            'description' => 'nullable|string|max:1000',
        ];
    }

    /**
     * Get custom error messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'file.required' => 'A file must be uploaded.',
            'file.file' => 'The uploaded file is not valid.',
            'file.max' => 'The file size must not exceed 10MB.',
            'name.max' => 'The file name must not exceed 255 characters.',
            'type.in' => 'The file type must be one of: document, image, video, audio, spreadsheet, text, other.',
            'description.max' => 'The description must not exceed 1000 characters.',
        ];
    }
}
