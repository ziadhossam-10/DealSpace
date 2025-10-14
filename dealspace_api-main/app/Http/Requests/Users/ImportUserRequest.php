<?php

namespace App\Http\Requests\Users;

use Illuminate\Foundation\Http\FormRequest;

class ImportUserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true; // Adjust based on your authorization logic
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        return [
            'file' => 'required|file|mimes:xlsx,xls,csv|max:10240' // 10MB max file size
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array
     */
    public function messages()
    {
        return [
            'file.required' => 'Please select a file to import',
            'file.file' => 'The uploaded file is invalid',
            'file.mimes' => 'The file must be an Excel file (xlsx, xls) or CSV',
            'file.max' => 'The file size must not exceed 10MB',
        ];
    }

    /**
     * Get the uploaded file from the request.
     *
     * @return \Illuminate\Http\UploadedFile
     */
    public function getFile()
    {
        return $this->file('file');
    }
}