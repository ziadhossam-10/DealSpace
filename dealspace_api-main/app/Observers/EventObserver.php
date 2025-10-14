<?php

namespace App\Observers;

use App\Models\Event;
use App\Models\Person;
use App\Models\PersonEmail;
use App\Models\PersonPhone;
use App\Models\Stage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class EventObserver
{
    /**
     * Handle the Event "created" event.
     */
    public function created(Event $event): void
    {
        $this->handlePersonCreation($event);
    }

    public function updated(Event $event): void
    {
        // Only handle person creation for updates if person_id is not set
        if (!$event->person_id) {
            $this->handlePersonCreation($event);
        }
    }

    /**
     * Handle person creation/update when an event is created
     */
    private function handlePersonCreation(Event $event): void
    {
        if (!$event->person || !is_array($event->person)) {
            return;
        }

        $personData = $event->person;

        $name = $this->extractName($personData);
        $email = $this->extractEmail($personData);
        $phone = $this->extractPhone($personData);

        if (!$name || (!$email && !$phone)) {
            return;
        }

        try {
            $personId = $this->processPersonCreation($email, $phone, $name, $event);
            if ($personId) {
                $this->updateEventPersonId($event, $personId);
            }
        } catch (\Exception $e) {
            Log::error('Error in EventObserver person creation', [
                'event_id' => $event->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Process person creation/update within the lock
     */
    private function processPersonCreation(?string $email, ?string $phone, string $name, Event $event): ?int
    {
        // Use database transaction for consistency
        return DB::transaction(function () use ($email, $phone, $name, $event) {
            $tenantId = (int) ($event->tenant_id ?? 0);
            $existingPerson = $this->findExistingPerson($email, $phone, $tenantId);

            if ($existingPerson) {
                $this->updatePersonContactInfo($existingPerson, $email, $phone);
                return $existingPerson->id;
            } else {
                return $this->createNewPerson($name, $email, $phone, $event);
            }
        });
    }

    /**
     * Update event with person ID
     */
    private function updateEventPersonId(Event $event, int $personId): void
    {
        try {
            // Use a separate query to avoid recursion in observer
            DB::table('events')
                ->where('id', $event->id)
                ->update(['person_id' => $personId, 'updated_at' => now()]);
        } catch (\Exception $e) {
            Log::error('Failed to update event person_id', [
                'event_id' => $event->id,
                'person_id' => $personId,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Extract name from person data
     */
    private function extractName(array $personData): ?string
    {
        // Try different name field variations
        $nameFields = ['name', 'fullName', 'full_name', 'firstName', 'first_name'];

        foreach ($nameFields as $field) {
            if (!empty($personData[$field])) {
                return trim($personData[$field]);
            }
        }

        // Try combining firstName and lastName
        $firstName = $personData['firstName'] ?? $personData['first_name'] ?? '';
        $lastName = $personData['lastName'] ?? $personData['last_name'] ?? '';

        if ($firstName || $lastName) {
            return trim($firstName . ' ' . $lastName);
        }

        return null;
    }

    /**
     * Extract email from person data
     */
    private function extractEmail(array $personData): ?string
    {
        $emailFields = ['email', 'emailAddress', 'email_address', 'userEmail'];

        foreach ($emailFields as $field) {
            if (!empty($personData[$field]) && filter_var($personData[$field], FILTER_VALIDATE_EMAIL)) {
                return strtolower(trim($personData[$field]));
            }
        }

        return null;
    }

    /**
     * Extract phone from person data
     */
    private function extractPhone(array $personData): ?string
    {
        $phoneFields = ['phone', 'phoneNumber', 'phone_number', 'mobile', 'cell'];

        foreach ($phoneFields as $field) {
            if (!empty($personData[$field])) {
                // Clean phone number (remove non-digits)
                $phone = preg_replace('/\D/', '', $personData[$field]);
                if (strlen($phone) >= 10) {
                    return $phone;
                }
            }
        }

        return null;
    }

    /**
     * Find existing person by email or phone within tenant
     */
    private function findExistingPerson(?string $email, ?string $phone, int $tenantId): ?Person
    {
        $query = Person::where('tenant_id', $tenantId);

        if ($email && $phone) {
            // Look for person with either this email or this phone
            $query->where(function ($q) use ($email, $phone) {
                $q->whereHas('emailAccounts', function ($eq) use ($email) {
                    $eq->where('value', $email);
                })->orWhereHas('phones', function ($pq) use ($phone) {
                    $pq->where('value', $phone);
                });
            });
        } elseif ($email) {
            // Look for person with this email
            $query->whereHas('emailAccounts', function ($eq) use ($email) {
                $eq->where('value', $email);
            });
        } elseif ($phone) {
            // Look for person with this phone
            $query->whereHas('phones', function ($pq) use ($phone) {
                $pq->where('value', $phone);
            });
        } else {
            return null;
        }

        return $query->first();
    }

    /**
     * Update existing person with new contact information
     */
    private function updatePersonContactInfo(Person $person, ?string $email, ?string $phone): void
    {
        // Add new email if provided and doesn't exist
        if ($email && !$person->emailAccounts()->where('value', $email)->exists()) {
            try {
                PersonEmail::create([
                    'person_id' => $person->id,
                    'value' => $email,
                    'type' => 'other',
                    'is_primary' => !$person->emailAccounts()->exists(),
                    'status' => 'active'
                ]);
            } catch (\Exception $e) {
                Log::warning('Failed to create person email', [
                    'person_id' => $person->id,
                    'email' => $email,
                    'error' => $e->getMessage()
                ]);
            }
        }

        // Add new phone if provided and doesn't exist
        if ($phone && !$person->phones()->where('value', $phone)->exists()) {
            try {
                PersonPhone::create([
                    'person_id' => $person->id,
                    'value' => $phone,
                    'type' => 'other',
                    'is_primary' => !$person->phones()->exists(),
                    'status' => 'active'
                ]);
            } catch (\Exception $e) {
                Log::warning('Failed to create person phone', [
                    'person_id' => $person->id,
                    'phone' => $phone,
                    'error' => $e->getMessage()
                ]);
            }
        }

        // Update last activity
        $person->update(['last_activity' => now()]);
    }

    /**
     * Create new person with contact information
     */
    private function createNewPerson(string $name, ?string $email, ?string $phone, Event $event): int
    {
        // Determine source based on campaign or event
        $source = $this->determinePersonSource($event);

        // Split name into first and last name
        $nameParts = $this->splitName($name);

        $stageName = 'New Lead';

        // Try to find the stage using LIKE or create it with proper error handling
        $stage = Stage::where('tenant_id', $event->tenant_id)
            ->where('name', 'LIKE', "%{$stageName}%")
            ->first();

        if (!$stage) {
            try {
                $stage = Stage::create([
                    'name' => $stageName,
                    'description' => 'Auto-created stage for new leads',
                    'tenant_id' => $event->tenant_id,
                    'color' => '#3B82F6', // Default blue color
                    'position' => 0,
                ]);
            } catch (\Exception $e) {
                Log::error('Failed to create stage, using fallback', [
                    'stage_name' => $stageName,
                    'tenant_id' => $event->tenant_id,
                    'error' => $e->getMessage()
                ]);

                // Fallback: try to find any stage for this tenant
                $stage = Stage::where('tenant_id', $event->tenant_id)->first();

                if (!$stage) {
                    throw new \Exception('No stage available for tenant: ' . $event->tenant_id);
                }
            }
        }

        // Create person
        $person = Person::create([
            'name' => $name,
            'first_name' => $nameParts['first_name'],
            'last_name' => $nameParts['last_name'],
            'source' => $source,
            'source_url' => $event->page_url,
            'created_via' => 'event_observer',
            'last_activity' => $event->occurred_at ?? now(),
            'tenant_id' => $event->tenant_id,
            'stage_id' => $stage->id
        ]);

        // Add email if provided
        if ($email) {
            try {
                PersonEmail::create([
                    'person_id' => $person->id,
                    'value' => $email,
                    'type' => 'primary',
                    'is_primary' => true,
                    'status' => 'active'
                ]);
            } catch (\Exception $e) {
                Log::warning('Failed to create person email for new person', [
                    'person_id' => $person->id,
                    'email' => $email,
                    'error' => $e->getMessage()
                ]);
            }
        }

        // Add phone if provided
        if ($phone) {
            try {
                PersonPhone::create([
                    'person_id' => $person->id,
                    'value' => $phone,
                    'type' => 'primary',
                    'is_primary' => true,
                    'status' => 'active'
                ]);
            } catch (\Exception $e) {
                Log::warning('Failed to create person phone for new person', [
                    'person_id' => $person->id,
                    'phone' => $phone,
                    'error' => $e->getMessage()
                ]);
            }
        }

        return $person->id;
    }

    /**
     * Determine person source based on campaign or event
     */
    private function determinePersonSource(Event $event): ?string
    {
        // If event has campaign data and campaign has source, use campaign source
        if ($event->campaign && is_array($event->campaign)) {
            if (!empty($event->campaign['source'])) {
                return $event->campaign['source'];
            }
        }

        // Otherwise use event source
        return $event->source;
    }

    /**
     * Split full name into first and last name
     */
    private function splitName(string $fullName): array
    {
        $parts = explode(' ', trim($fullName), 2);

        return [
            'first_name' => $parts[0] ?? null,
            'last_name' => $parts[1] ?? null,
        ];
    }
}
