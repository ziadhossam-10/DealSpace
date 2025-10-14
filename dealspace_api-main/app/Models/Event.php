<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;

class Event extends Model
{
    use HasFactory, BelongsToTenant;

    protected $fillable = [
        'source',
        'system',
        'type',
        'message',
        'description',
        'person',
        'property',
        'property_search',
        'campaign',
        'page_title',
        'page_url',
        'page_referrer',
        'page_duration',
        'occurred_at',
        'tenant_id',
        'person_id',
        'form_key'
    ];

    protected $casts = [
        'person' => 'array',
        'property' => 'array',
        'property_search' => 'array',
        'campaign' => 'array',
        'occurred_at' => 'datetime',
        'page_duration' => 'integer',
    ];

    /**
     * Event types constants
     */
    const TYPE_REGISTRATION = 'Registration';
    const TYPE_INQUIRY = 'Inquiry';
    const TYPE_SELLER_INQUIRY = 'Seller Inquiry';
    const TYPE_PROPERTY_INQUIRY = 'Property Inquiry';
    const TYPE_GENERAL_INQUIRY = 'General Inquiry';
    const TYPE_VIEWED_PROPERTY = 'Viewed Property';
    const TYPE_SAVED_PROPERTY = 'Saved Property';
    const TYPE_VISITED_WEBSITE = 'Visited Website';
    const TYPE_INCOMING_CALL = 'Incoming Call';
    const TYPE_UNSUBSCRIBED = 'Unsubscribed';
    const TYPE_PROPERTY_SEARCH = 'Property Search';
    const TYPE_SAVED_PROPERTY_SEARCH = 'Saved Property Search';
    const TYPE_VISITED_OPEN_HOUSE = 'Visited Open House';
    const TYPE_VIEWED_PAGE = 'Viewed Page';

    /**
     * Relationship to Person model
     */
    public function personRecord(): BelongsTo
    {
        return $this->belongsTo(Person::class, 'person_id');
    }

    /**
     * Get all available event types
     */
    public static function getTypes(): array
    {
        return [
            self::TYPE_REGISTRATION,
            self::TYPE_INQUIRY,
            self::TYPE_SELLER_INQUIRY,
            self::TYPE_PROPERTY_INQUIRY,
            self::TYPE_GENERAL_INQUIRY,
            self::TYPE_VIEWED_PROPERTY,
            self::TYPE_SAVED_PROPERTY,
            self::TYPE_VISITED_WEBSITE,
            self::TYPE_INCOMING_CALL,
            self::TYPE_UNSUBSCRIBED,
            self::TYPE_PROPERTY_SEARCH,
            self::TYPE_SAVED_PROPERTY_SEARCH,
            self::TYPE_VISITED_OPEN_HOUSE,
            self::TYPE_VIEWED_PAGE,
        ];
    }

    /**
     * Check if the event is historical (occurred more than 1 day ago)
     */
    public function isHistorical(): bool
    {
        if (!$this->occurred_at) {
            return false;
        }

        return $this->occurred_at->diffInDays(now()) > 1;
    }

    /**
     * Get the person's full name from the person data
     */
    public function getPersonFullNameAttribute(): ?string
    {
        if (!$this->person) {
            return null;
        }

        $firstName = $this->person['firstName'] ?? '';
        $lastName = $this->person['lastName'] ?? '';

        return trim($firstName . ' ' . $lastName) ?: null;
    }

    /**
     * Get the property address from the property data
     */
    public function getPropertyAddressAttribute(): ?string
    {
        if (!$this->property) {
            return null;
        }

        $street = $this->property['street'] ?? '';
        $city = $this->property['city'] ?? '';
        $state = $this->property['state'] ?? '';

        return trim($street . ', ' . $city . ', ' . $state) ?: null;
    }
}
