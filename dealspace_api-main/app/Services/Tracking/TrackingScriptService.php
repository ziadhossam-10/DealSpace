<?php

namespace App\Services\Tracking;

use App\Models\TrackingScript;
use App\Models\Event;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class TrackingScriptService implements TrackingScriptServiceInterface
{
    /**
     * Get all tracking scripts for a user
     */
    public function getAll(int $userId, int $perPage = 15, int $page = 1, array $filters = []): LengthAwarePaginator
    {
        $query = TrackingScript::where('user_id', $userId);

        // Apply filters
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('domain', 'like', "%{$search}%");
            });
        }

        if (isset($filters['is_active'])) {
            $query->where('is_active', $filters['is_active']);
        }

        return $query->orderBy('created_at', 'desc')
            ->paginate($perPage, ['*'], 'page', $page);
    }

    /**
     * Find tracking script by ID
     */
    public function findById(int $id, int $userId): TrackingScript
    {
        $script = TrackingScript::where('id', $id)
            ->where('user_id', $userId)
            ->first();

        if (!$script) {
            throw new ModelNotFoundException('Tracking script not found');
        }

        return $script;
    }

    /**
     * Create a new tracking script
     */
    public function create(array $data, int $userId): TrackingScript
    {
        return DB::transaction(function () use ($data, $userId) {
            $data['user_id'] = $userId;

            // Set default field mappings if not provided
            if (empty($data['field_mappings'])) {
                $data['field_mappings'] = TrackingScript::getDefaultFieldMappings();
            }

            return TrackingScript::create($data);
        });
    }

    /**
     * Update an existing tracking script
     */
    public function update(int $id, array $data, int $userId): TrackingScript
    {
        return DB::transaction(function () use ($id, $data, $userId) {
            $script = $this->findById($id, $userId);

            // Don't allow changing user_id or script_key through update
            unset($data['user_id'], $data['script_key']);

            $script->update($data);

            return $script->fresh();
        });
    }

    /**
     * Delete a tracking script
     */
    public function delete(int $id, int $userId): bool
    {
        return DB::transaction(function () use ($id, $userId) {
            $script = $this->findById($id, $userId);

            // Optionally, you might want to keep the events but mark them as orphaned
            // or delete them as well. For now, we'll keep them.

            return $script->delete();
        });
    }

    /**
     * Toggle active status of a tracking script
     */
    public function toggleStatus(int $id, int $userId): TrackingScript
    {
        $script = $this->findById($id, $userId);
        $script->is_active = !$script->is_active;
        $script->save();

        return $script;
    }

    /**
     * Regenerate script key
     */
    public function regenerateKey(int $id, int $userId): TrackingScript
    {
        $script = $this->findById($id, $userId);
        $script->regenerateKey();

        return $script;
    }

    /**
     * Get tracking script statistics
     */
    public function getStatistics(int $id, int $userId, array $dateRange = []): array
    {
        $script = $this->findById($id, $userId);

        $query = Event::where('source', $script->script_key);

        // Apply date range filter
        if (!empty($dateRange['start_date'])) {
            $query->where('occurred_at', '>=', $dateRange['start_date']);
        }

        if (!empty($dateRange['end_date'])) {
            $query->where('occurred_at', '<=', $dateRange['end_date']);
        }

        $totalEvents = $query->count();

        // Events by type
        $eventsByType = $query->select('type', DB::raw('count(*) as count'))
            ->groupBy('type')
            ->pluck('count', 'type')
            ->toArray();

        // Events by day (last 30 days)
        $eventsByDay = $query->select(
            DB::raw('DATE(occurred_at) as date'),
            DB::raw('count(*) as count')
        )
            ->where('occurred_at', '>=', now()->subDays(30))
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->keyBy('date')
            ->map(function ($item) {
                return $item->count;
            })
            ->toArray();

        // Page views
        $pageViews = $query->where('type', Event::TYPE_VIEWED_PAGE)->count();

        // Form submissions
        $formSubmissions = $query->whereIn('type', [
            Event::TYPE_INQUIRY,
            Event::TYPE_PROPERTY_INQUIRY,
            Event::TYPE_SELLER_INQUIRY,
            Event::TYPE_GENERAL_INQUIRY,
            Event::TYPE_REGISTRATION
        ])->count();

        // Unique visitors (based on person data or visitor tracking)
        $uniqueVisitors = $query->whereNotNull('person')
            ->select(DB::raw('COUNT(DISTINCT JSON_EXTRACT(person, "$.visitor_id")) as count'))
            ->value('count') ?? 0;

        return [
            'total_events' => $totalEvents,
            'page_views' => $pageViews,
            'form_submissions' => $formSubmissions,
            'unique_visitors' => $uniqueVisitors,
            'events_by_type' => $eventsByType,
            'events_by_day' => $eventsByDay,
            'conversion_rate' => $pageViews > 0 ? round(($formSubmissions / $pageViews) * 100, 2) : 0,
        ];
    }

    /**
     * Get recent events for a tracking script
     */
    public function getRecentEvents(int $id, int $userId, int $limit = 50): array
    {
        $script = $this->findById($id, $userId);

        return Event::where('source', $script->script_key)
            ->orderBy('occurred_at', 'desc')
            ->limit($limit)
            ->get()
            ->toArray();
    }

    /**
     * Validate field mappings
     */
    public function validateFieldMappings(array $fieldMappings): array
    {
        $errors = [];
        $allowedPersonFields = ['name', 'first_name', 'last_name', 'email', 'phone', 'message', 'company', 'property_interest', 'budget'];

        foreach ($fieldMappings as $personField => $formFields) {
            if (!in_array($personField, $allowedPersonFields)) {
                $errors[] = "Invalid person field: {$personField}";
            }

            if (!is_array($formFields)) {
                $errors[] = "Form fields for {$personField} must be an array";
            }
        }

        return $errors;
    }

    /**
     * Get field mapping suggestions based on form field names
     */
    public function suggestFieldMappings(array $formFields): array
    {
        $suggestions = [];
        $defaultMappings = TrackingScript::getDefaultFieldMappings();

        foreach ($formFields as $formField) {
            $formFieldLower = strtolower($formField);

            foreach ($defaultMappings as $personField => $possibleFields) {
                $possibleFieldsLower = array_map('strtolower', $possibleFields);

                if (in_array($formFieldLower, $possibleFieldsLower)) {
                    $suggestions[$personField][] = $formField;
                }
            }
        }

        return $suggestions;
    }

    /**
     * Duplicate a tracking script
     */
    public function duplicate(int $id, int $userId, string $newName): TrackingScript
    {
        return DB::transaction(function () use ($id, $userId, $newName) {
            $originalScript = $this->findById($id, $userId);

            $duplicateData = $originalScript->toArray();
            unset($duplicateData['id'], $duplicateData['script_key'], $duplicateData['created_at'], $duplicateData['updated_at']);

            $duplicateData['name'] = $newName;

            return TrackingScript::create($duplicateData);
        });
    }
}
