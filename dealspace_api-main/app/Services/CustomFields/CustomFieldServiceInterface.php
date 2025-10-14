<?php

namespace App\Services\CustomFields;

use App\Models\CustomField;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface CustomFieldServiceInterface
{
    /**
     * Get all custom fields.
     * @return Collection
     */
    public function getAll(): Collection;

    /**
     * Find a custom field by ID.
     *
     * @param int $fieldId
     * @return CustomField
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function findById(int $fieldId): CustomField;

    /**
     * Create a new custom field.
     *
     * @param array $data
     * @return CustomField
     * @throws \InvalidArgumentException
     */
    public function create(array $data): CustomField;

    /**
     * Update an existing custom field.
     *
     * @param int $fieldId
     * @param array $data
     * @return CustomField
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     * @throws \InvalidArgumentException
     */
    public function update(int $fieldId, array $data): CustomField;

    /**
     * Delete a custom field.
     *
     * @param int $fieldId
     * @return bool
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function delete(int $fieldId): bool;
}
