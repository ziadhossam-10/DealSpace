<?php
// UpdateEventRequest.php
namespace App\Http\Requests\Events;

use App\Models\Event;
use Illuminate\Foundation\Http\FormRequest;

class UpdateEventRequest extends FormRequest
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
            'source' => 'sometimes|nullable|string|max:255',
            'system' => 'sometimes|nullable|string|max:255',
            'type' => 'sometimes|required|string|in:' . implode(',', Event::getTypes()),
            'message' => 'sometimes|nullable|string',
            'description' => 'sometimes|nullable|string',
            'person' => 'sometimes|nullable|array',
            'person.id' => 'sometimes|nullable|integer',
            'person.firstName' => 'sometimes|nullable|string|max:255',
            'person.lastName' => 'sometimes|nullable|string|max:255',
            'person.stage' => 'sometimes|nullable|string|max:255',
            'person.source' => 'sometimes|nullable|string|max:255',
            'person.sourceUrl' => 'sometimes|nullable|url',
            'person.contacted' => 'sometimes|nullable|boolean',
            'person.price' => 'sometimes|nullable|integer',
            'person.assignedTo' => 'sometimes|nullable|string|max:255',
            'person.assignedLenderName' => 'sometimes|nullable|string|max:255',
            'person.assignedUserId' => 'sometimes|nullable|integer',
            'person.assignedPondId' => 'sometimes|nullable|integer',
            'person.assignedLenderId' => 'sometimes|nullable|integer',
            'person.emails' => 'sometimes|nullable|array',
            'person.emails.*' => 'email',
            'person.phones' => 'sometimes|nullable|array',
            'person.addresses' => 'sometimes|nullable|array',
            'person.tags' => 'sometimes|nullable|array',
            'person.tags.*' => 'string',
            'property' => 'sometimes|nullable|array',
            'property.street' => 'sometimes|nullable|string|max:255',
            'property.city' => 'sometimes|nullable|string|max:255',
            'property.state' => 'sometimes|nullable|string|max:10',
            'property.code' => 'sometimes|nullable|string|max:20',
            'property.mlsNumber' => 'sometimes|nullable|string|max:255',
            'property.price' => 'sometimes|nullable|integer',
            'property.forRent' => 'sometimes|nullable|boolean',
            'property.url' => 'sometimes|nullable|url',
            'property.type' => 'sometimes|nullable|string|max:255',
            'property.bedrooms' => 'sometimes|nullable|string|max:10',
            'property.bathrooms' => 'sometimes|nullable|string|max:10',
            'property.area' => 'sometimes|nullable|string|max:255',
            'property.lot' => 'sometimes|nullable|string|max:255',
            'property_search' => 'sometimes|nullable|array',
            'campaign' => 'sometimes|nullable|array',
            'page_title' => 'sometimes|nullable|string|max:255',
            'page_url' => 'sometimes|nullable|url',
            'page_referrer' => 'sometimes|nullable|url',
            'page_duration' => 'sometimes|nullable|integer|min:0',
            'occurred_at' => 'sometimes|nullable|date',
        ];
    }
}
