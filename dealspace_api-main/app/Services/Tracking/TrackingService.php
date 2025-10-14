<?php

namespace App\Services\Tracking;

use App\Models\Event;
use App\Models\Person;
use App\Models\TrackingScript;
use App\Services\People\PersonServiceInterface;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TrackingService implements TrackingServiceInterface
{
    protected $personService;

    public function __construct(PersonServiceInterface $personService)
    {
        $this->personService = $personService;
    }

    /**
     * Track a page view event
     */
    public function trackPageView(string $scriptKey, array $data): Event
    {
        $trackingScript = $this->getTrackingScript($scriptKey);

        if (!$trackingScript || !$trackingScript->track_page_views) {
            throw new \Exception('Page view tracking is disabled for this script');
        }

        $eventData = [
            'source' => $this->extractCampaignData($data)->source ?? 'Dealspace Pixel',
            'system' => 'dealspace_pixel',
            'type' => Event::TYPE_VIEWED_PAGE,
            'message' => 'Page viewed: ' . ($data['page_title'] ?? 'Unknown Page'),
            'description' => 'User viewed a page on the website',
            'page_title' => $data['page_title'] ?? null,
            'page_url' => $data['page_url'] ?? null,
            'page_referrer' => $data['page_referrer'] ?? null,
            'page_duration' => $data['page_duration'] ?? null,
            'active_time' => $data['active_time'] ?? null,
            'scroll_depth' => $data['scroll_depth'] ?? null,
            'is_refresh' => $data['is_refresh'] ?? false,
            'is_unload' => $data['is_unload'] ?? false,
            'user_agent' => $data['user_agent'] ?? null,
            'screen_resolution' => $data['screen_resolution'] ?? null,
            'viewport_size' => $data['viewport_size'] ?? null,
            'campaign' => $this->extractCampaignData($data),
            'occurred_at' => Carbon::parse($data['timestamp'] ?? now()),
            'tenant_id' => $trackingScript->tenant_id
        ];

        // Handle person data if provided
        if (!empty($data['person_data']) && $this->hasMinimumPersonData($data['person_data'])) {
            // Store person data in the event
            $eventData['person'] = $data['person_data'];

            // If auto lead capture is enabled, create or update the person record
            if ($trackingScript->auto_lead_capture) {
                try {
                    $person = $this->createOrUpdatePerson($data['person_data'], $trackingScript);
                    $eventData['person_id'] = $person->id;

                    Log::info('Person associated with page view', [
                        'person_id' => $person->id,
                        'email' => $data['person_data']['email'] ?? null,
                        'page_url' => $data['page_url']
                    ]);
                } catch (\Exception $e) {
                    Log::warning('Failed to create/update person for page view', [
                        'error' => $e->getMessage(),
                        'person_data' => $data['person_data'],
                        'page_url' => $data['page_url']
                    ]);
                }
            }
        }
        return Event::create($eventData);
    }

    /**
     * Track a form submission event with form key deduplication
     */
    public function trackFormSubmission(string $scriptKey, array $data): Event
    {
        $trackingScript = $this->getTrackingScript($scriptKey);

        if (!$trackingScript) {
            throw new \Exception('Invalid tracking script key');
        }

        // Validate form tracking is enabled
        if (!$trackingScript->track_all_forms && !$this->isFormAllowed($trackingScript, $data['form_selector'] ?? null)) {
            throw new \Exception('Form tracking is not enabled for this form');
        }

        $formKey = $data['form_key'] ?? null;
        $personData = $this->extractPersonData($data['form_data'] ?? [], $trackingScript);
        $person = null;

        // Create or update person if we have enough data
        if ($trackingScript->auto_lead_capture && $this->hasMinimumPersonData($personData)) {
            $person = $this->createOrUpdatePerson($personData, $trackingScript);
        }

        $eventData = [
            'source' => $this->extractCampaignData($data)->source ?? 'Dealspace Pixel',
            'system' => 'dealspace_pixel',
            'type' => $this->determineEventType($data['form_data'] ?? [], $trackingScript),
            'message' => 'Form submitted: ' . ($data['form_selector'] ?? 'Unknown Form'),
            'description' => 'User submitted a form on the website',
            'person' => $personData,
            'page_title' => $data['page_title'] ?? null,
            'page_url' => $data['page_url'] ?? null,
            'page_referrer' => $data['page_referrer'] ?? null,
            'campaign' => $this->extractCampaignData($data),
            'occurred_at' => Carbon::parse($data['timestamp'] ?? now()),
            'tenant_id' => $trackingScript->tenant_id,
            'form_key' => $formKey, // Store the form key
            'form_data' => $data['form_data'] ?? [], // Store complete form data for merging
        ];

        // Add property data if it's a property-related inquiry
        if (!empty($data['property_data'])) {
            $eventData['property'] = $data['property_data'];
        }

        // Check if event with same form key exists and handle accordingly
        if ($formKey) {
            return $this->createOrUpdateEventByFormKey($eventData, $formKey);
        }

        return Event::create($eventData);
    }

    /**
     * Track a custom event with form key deduplication
     */
    public function trackCustomEvent(string $scriptKey, array $data): Event
    {
        $trackingScript = $this->getTrackingScript($scriptKey);

        if (!$trackingScript) {
            throw new \Exception('Invalid tracking script key');
        }

        $eventType = $data['event_type'] ?? 'Custom Event';
        $formKey = $data['form_key'] ?? null;

        // Validate custom event is allowed
        if (!$this->isCustomEventAllowed($trackingScript, $eventType)) {
            throw new \Exception('Custom event type is not allowed');
        }

        $eventData = [
            'source' => $this->extractCampaignData($data)->source ?? 'Dealspace Pixel',
            'system' => 'dealspace_pixel',
            'type' => $eventType,
            'message' => $data['message'] ?? 'Custom event triggered',
            'description' => $data['description'] ?? 'User triggered a custom event',
            'page_title' => $data['page_title'] ?? null,
            'page_url' => $data['page_url'] ?? null,
            'page_referrer' => $data['page_referrer'] ?? null,
            'campaign' => $this->extractCampaignData($data),
            'occurred_at' => Carbon::parse($data['timestamp'] ?? now()),
            'tenant_id' => $trackingScript->tenant_id,
            'form_key' => $formKey, // Store the form key
            'custom_data' => $data['custom_data'] ?? [], // Store custom event data for merging
        ];

        // Add person data if available
        if (!empty($data['person_data'])) {
            $eventData['person'] = $data['person_data'];
        }

        // Add property data if available
        if (!empty($data['property_data'])) {
            $eventData['property'] = $data['property_data'];
        }

        // Check if event with same form key exists and handle accordingly
        if ($formKey) {
            return $this->createOrUpdateEventByFormKey($eventData, $formKey);
        }

        return Event::create($eventData);
    }

    /**
     * Create or update event based on form key
     */
    protected function createOrUpdateEventByFormKey(array $eventData, string $formKey): Event
    {
        // Find existing event with the same form key
        $existingEvent = Event::where('form_key', $formKey)
            ->where('tenant_id', $eventData['tenant_id'])
            ->first();

        if ($existingEvent) {
            // Merge the data instead of replacing
            $mergedData = $this->mergeEventData($existingEvent, $eventData);

            // Update the existing event
            $existingEvent->update($mergedData);

            Log::info('Event updated with form key: ' . $formKey, [
                'event_id' => $existingEvent->id,
                'form_key' => $formKey
            ]);

            return $existingEvent->fresh();
        }

        // Create new event if none exists
        return Event::create($eventData);
    }

    /**
     * Merge existing event data with new event data
     */
    protected function mergeEventData(Event $existingEvent, array $newEventData): array
    {
        $merged = $newEventData;

        // Merge form_data if both exist
        if (isset($existingEvent->form_data) && isset($newEventData['form_data'])) {
            $merged['form_data'] = array_merge(
                $existingEvent->form_data ?? [],
                $newEventData['form_data'] ?? []
            );
        }

        // Merge custom_data if both exist
        if (isset($existingEvent->custom_data) && isset($newEventData['custom_data'])) {
            $merged['custom_data'] = array_merge(
                $existingEvent->custom_data ?? [],
                $newEventData['custom_data'] ?? []
            );
        }

        // Merge person data if both exist
        if (isset($existingEvent->person) && isset($newEventData['person'])) {
            $merged['person'] = array_merge(
                $existingEvent->person ?? [],
                $newEventData['person'] ?? []
            );
        }

        // Merge property data if both exist
        if (isset($existingEvent->property) && isset($newEventData['property'])) {
            $merged['property'] = array_merge(
                $existingEvent->property ?? [],
                $newEventData['property'] ?? []
            );
        }

        // Merge campaign data if both exist
        if (isset($existingEvent->campaign) && isset($newEventData['campaign'])) {
            $merged['campaign'] = array_merge(
                $existingEvent->campaign ?? [],
                $newEventData['campaign'] ?? []
            );
        }

        // Update timestamps
        $merged['updated_at'] = now();

        // Keep the original occurred_at unless it's significantly different
        $originalTime = Carbon::parse($existingEvent->occurred_at);
        $newTime = Carbon::parse($newEventData['occurred_at']);

        // Only update occurred_at if the new time is more than 5 minutes different
        if (abs($originalTime->diffInMinutes($newTime)) > 5) {
            $merged['occurred_at'] = $newEventData['occurred_at'];
        }

        // Update message to reflect it's been updated
        $merged['message'] = $newEventData['message'] ?? $existingEvent->message;
        $merged['description'] = $newEventData['type'] === "Form Started" ? "Form interaction started" : ($newEventData['type'] === "Form Filled" ? "Form interaction filled" : ($newEventData['type'] === "Form Submitted" ? "Form interaction submitted" : $existingEvent->description));

        return $merged;
    }

    /**
     * Generate a unique form key based on form characteristics
     */
    public function generateFormKey(array $data): string
    {
        // Create a unique key based on form selector, page URL, and user identifier
        $keyComponents = [
            $data['form_selector'] ?? 'unknown-form',
            $data['page_url'] ?? '',
            $data['visitor_id'] ?? $data['session_id'] ?? 'anonymous',
            $data['person_identifier'] ?? '' // Could be email, phone, or other unique identifier
        ];

        return md5(implode('|', array_filter($keyComponents)));
    }

    /**
     * Get tracking script by key
     */
    protected function getTrackingScript(string $scriptKey): ?TrackingScript
    {
        return TrackingScript::where('script_key', $scriptKey)
            ->where('is_active', true)
            ->first();
    }

    /**
     * Extract campaign data from UTM parameters
     */
    protected function extractCampaignData(array $data): ?array
    {
        $utmParams = $data['utm_params'] ?? [];

        if (empty($utmParams) || !is_array($utmParams)) {
            return null;
        }

        // Only return campaign data if we have at least one UTM parameter
        $campaignData = array_filter([
            'source' => $utmParams['utm_source'] ?? null,
            'medium' => $utmParams['utm_medium'] ?? null,
            'campaign' => $utmParams['utm_campaign'] ?? null,
            'term' => $utmParams['utm_term'] ?? null,
            'content' => $utmParams['utm_content'] ?? null,
        ]);

        return !empty($campaignData) ? $campaignData : null;
    }


    /**
     * Extract person data from form data using field mappings
     */
    protected function extractPersonData(array $formData, TrackingScript $trackingScript): array
    {
        $personData = [];
        $fieldMappings = $trackingScript->field_mappings ?? TrackingScript::getDefaultFieldMappings();

        foreach ($fieldMappings as $personField => $possibleFields) {
            foreach ($possibleFields as $formField) {
                if (isset($formData[$formField]) && !empty($formData[$formField])) {
                    $personData[$personField] = $formData[$formField];
                    break; // Use the first match found
                }
            }
        }

        return $personData;
    }

    /**
     * Check if we have minimum required data to create a person
     */
    protected function hasMinimumPersonData(array $personData): bool
    {
        // Must have either email OR (name/first_name + phone)
        $hasEmail = !empty($personData['email']) &&
            filter_var($personData['email'], FILTER_VALIDATE_EMAIL);

        $hasPhone = !empty($personData['phone']) &&
            strlen(preg_replace('/\D/', '', $personData['phone'])) >= 10;

        $hasName = !empty($personData['name']) ||
            (!empty($personData['first_name']) && !empty($personData['last_name']));

        // Valid person data requires:
        // 1. Email + any name, OR
        // 2. Phone + any name
        return ($hasEmail && $hasName) || ($hasPhone && $hasName);
    }

    /**
     * Create or update person from extracted data
     */
    protected function createOrUpdatePerson(array $personData, TrackingScript $trackingScript): Person
    {
        // Try to find existing person by email
        $existingPerson = null;
        if (!empty($personData['email'])) {
            $existingPerson = $this->personService->findByEmail($personData['email']);
        }

        if ($existingPerson) {
            // Update existing person
            $updateData = $this->preparePersonUpdateData($personData, $trackingScript);
            return $this->personService->update($existingPerson->id, $updateData);
        } else {
            // Create new person
            $createData = $this->preparePersonCreateData($personData, $trackingScript);
            return $this->personService->create($createData);
        }
    }

    /**
     * Prepare person data for creation
     */
    protected function preparePersonCreateData(array $personData, TrackingScript $trackingScript, string $context = 'form'): array
    {
        $data = [
            'source' => 'dealspace_pixel',
            'source_url' => $trackingScript->domain,
            'created_via' => $context === 'page_view' ? 'page_view_tracking' : 'tracking_pixel',
            'assigned_user_id' => $trackingScript->user_id,
            'tenant_id' => $trackingScript->tenant_id,
        ];

        // Map person fields
        if (!empty($personData['name'])) {
            $data['name'] = $personData['name'];
        }
        if (!empty($personData['first_name'])) {
            $data['first_name'] = $personData['first_name'];
        }
        if (!empty($personData['last_name'])) {
            $data['last_name'] = $personData['last_name'];
        }

        // Add email
        if (!empty($personData['email'])) {
            $data['emails'] = [
                ['value' => $personData['email'], 'is_primary' => true, 'type' => 'email']
            ];
        }

        // Add phone
        if (!empty($personData['phone'])) {
            $data['phones'] = [
                ['number' => $personData['phone'], 'is_primary' => true, 'type' => 'mobile']
            ];
        }

        // Add company if provided
        if (!empty($personData['company'])) {
            $data['company'] = $personData['company'];
        }

        // Add any additional person data as custom fields
        $customFields = [];
        foreach ($personData as $key => $value) {
            if (!in_array($key, ['name', 'first_name', 'last_name', 'email', 'phone', 'company']) && !empty($value)) {
                $customFields[$key] = $value;
            }
        }

        if (!empty($customFields)) {
            $data['custom_fields'] = $customFields;
        }

        return $data;
    }

    /**
     * Prepare person data for update
     */
    protected function preparePersonUpdateData(array $personData, TrackingScript $trackingScript): array
    {
        $data = [];

        // Only update basic fields if they're not already set
        if (!empty($personData['name'])) {
            $data['name'] = $personData['name'];
        }
        if (!empty($personData['first_name'])) {
            $data['first_name'] = $personData['first_name'];
        }
        if (!empty($personData['last_name'])) {
            $data['last_name'] = $personData['last_name'];
        }

        return $data;
    }

    /**
     * Check if form is allowed for tracking
     */
    protected function isFormAllowed(TrackingScript $trackingScript, ?string $formSelector): bool
    {
        if ($trackingScript->track_all_forms) {
            return true;
        }

        $allowedSelectors = $trackingScript->form_selectors ?? [];
        return in_array($formSelector, $allowedSelectors);
    }

    /**
     * Check if custom event is allowed
     */
    protected function isCustomEventAllowed(TrackingScript $trackingScript, string $eventType): bool
    {
        $allowedEvents = $trackingScript->custom_events ?? [];
        return empty($allowedEvents) || in_array($eventType, $allowedEvents);
    }

    /**
     * Determine event type based on form data
     */
    protected function determineEventType(array $formData, TrackingScript $trackingScript): string
    {
        // Check for property-related inquiries
        if (isset($formData['property_id']) || isset($formData['property']) || isset($formData['listing'])) {
            return Event::TYPE_PROPERTY_INQUIRY;
        }

        // Check for seller inquiries
        if (
            isset($formData['selling']) || isset($formData['sell_property']) ||
            stripos(json_encode($formData), 'sell') !== false
        ) {
            return Event::TYPE_SELLER_INQUIRY;
        }

        // Check for registration forms
        if (
            isset($formData['password']) || isset($formData['confirm_password']) ||
            stripos(json_encode($formData), 'register') !== false
        ) {
            return Event::TYPE_REGISTRATION;
        }

        // Default to general inquiry
        return Event::TYPE_GENERAL_INQUIRY;
    }
}
