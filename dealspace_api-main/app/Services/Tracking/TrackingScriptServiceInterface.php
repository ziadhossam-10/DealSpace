<?php

namespace App\Services\Tracking;

interface TrackingScriptServiceInterface
{
    /**
     * Get all tracking scripts for a user
     *
     * @param int $userId
     * @param int $perPage
     * @param int $page
     * @param array $filters
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getAll(int $userId, int $perPage = 15, int $page = 1, array $filters = []);

    /**
     * Find tracking script by ID
     *
     * @param int $id
     * @param int $userId
     * @return \App\Models\TrackingScript
     */
    public function findById(int $id, int $userId);

    /**
     * Create a new tracking script
     *
     * @param array $data
     * @param int $userId
     * @return \App\Models\TrackingScript
     */
    public function create(array $data, int $userId);

    /**
     * Update an existing tracking script
     *
     * @param int $id
     * @param array $data
     * @param int $userId
     * @return \App\Models\TrackingScript
     */
    public function update(int $id, array $data, int $userId);

    /**
     * Delete a tracking script
     *
     * @param int $id
     * @param int $userId
     * @return bool
     */
    public function delete(int $id, int $userId): bool;

    /**
     * Toggle active status of a tracking script
     *
     * @param int $id
     * @param int $userId
     * @return \App\Models\TrackingScript
     */
    public function toggleStatus(int $id, int $userId);

    /**
     * Regenerate script key
     *
     * @param int $id
     * @param int $userId
     * @return \App\Models\TrackingScript
     */
    public function regenerateKey(int $id, int $userId);

    /**
     * Get tracking script statistics
     *
     * @param int $id
     * @param int $userId
     * @param array $dateRange
     * @return array
     */
    public function getStatistics(int $id, int $userId, array $dateRange = []): array;

    /**
     * Get recent events for a tracking script
     *
     * @param int $id
     * @param int $userId
     * @param int $limit
     * @return array
     */
    public function getRecentEvents(int $id, int $userId, int $limit = 50): array;

    /**
     * Validate field mappings
     *
     * @param array $fieldMappings
     * @return array
     */
    public function validateFieldMappings(array $fieldMappings): array;

    /**
     * Get field mapping suggestions based on form field names
     *
     * @param array $formFields
     * @return array
     */
    public function suggestFieldMappings(array $formFields): array;

    /**
     * Duplicate a tracking script
     *
     * @param int $id
     * @param int $userId
     * @param string $newName
     * @return \App\Models\TrackingScript
     */
    public function duplicate(int $id, int $userId, string $newName);
}
