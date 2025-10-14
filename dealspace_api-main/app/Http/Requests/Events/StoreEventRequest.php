<?php
// StoreEventRequest.php
namespace App\Http\Requests\Events;

use App\Models\Event;
use Illuminate\Foundation\Http\FormRequest;

class StoreEventRequest extends FormRequest
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
            'source' => 'nullable|string|max:255',
            'system' => 'nullable|string|max:255',
            'type' => 'required|string|in:' . implode(',', Event::getTypes()),
            'message' => 'nullable|string',
            'description' => 'nullable|string',
            'person' => 'nullable|array',
            'person.id' => 'nullable|integer',
            'person.firstName' => 'nullable|string|max:255',
            'person.lastName' => 'nullable|string|max:255',
            'person.stage' => 'nullable|string|max:255',
            'person.source' => 'nullable|string|max:255',
            'person.sourceUrl' => 'nullable|url',
            'person.contacted' => 'nullable|boolean',
            'person.price' => 'nullable|integer',
            'person.assignedTo' => 'nullable|string|max:255',
            'person.assignedLenderName' => 'nullable|string|max:255',
            'person.assignedUserId' => 'nullable|integer',
            'person.assignedPondId' => 'nullable|integer',
            'person.assignedLenderId' => 'nullable|integer',
            'person.emails' => 'nullable|array',
            'person.emails.*' => 'email',
            'person.phones' => 'nullable|array',
            'person.addresses' => 'nullable|array',
            'person.tags' => 'nullable|array',
            'person.tags.*' => 'string',
            'property' => 'nullable|array',
            'property.street' => 'nullable|string|max:255',
            'property.city' => 'nullable|string|max:255',
            'property.state' => 'nullable|string|max:10',
            'property.code' => 'nullable|string|max:20',
            'property.mlsNumber' => 'nullable|string|max:255',
            'property.price' => 'nullable|integer',
            'property.forRent' => 'nullable|boolean',
            'property.url' => 'nullable|url',
            'property.type' => 'nullable|string|max:255',
            'property.bedrooms' => 'nullable|string|max:10',
            'property.bathrooms' => 'nullable|string|max:10',
            'property.area' => 'nullable|string|max:255',
            'property.lot' => 'nullable|string|max:255',
            'property_search' => 'nullable|array',
            'campaign' => 'nullable|array',
            'page_title' => 'nullable|string|max:255',
            'page_url' => 'nullable|url',
            'page_referrer' => 'nullable|url',
            'page_duration' => 'nullable|integer|min:0',
            'occurred_at' => 'nullable|date',
        ];
    }
}
