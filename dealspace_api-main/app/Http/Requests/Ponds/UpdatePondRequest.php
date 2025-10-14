<?php
namespace App\Http\Requests\Ponds;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePondRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true; // You may want to add authorization logic here
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'name' => 'sometimes|string|max:255',
            'user_id' => 'sometimes|integer|exists:users,id',
            'user_ids' => 'sometimes|array',
            'user_ids.*' => 'integer|exists:users,id',
            'user_ids_to_delete' => 'sometimes|array',
            'user_ids_to_delete.*' => 'integer|exists:users,id'
        ];
    }
}