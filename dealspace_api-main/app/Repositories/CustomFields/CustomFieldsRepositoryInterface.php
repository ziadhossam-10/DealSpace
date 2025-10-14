<?php

namespace App\Repositories\CustomFields;

use App\Models\CustomField;
use Illuminate\Database\Eloquent\Collection;

interface CustomFieldsRepositoryInterface
{
    /**
     * Get all custom fields .
     *
     * @return Collection
     */
    public function getAll(): Collection;

    /**
     * Find a custom field by ID.
     *
     * @param int $fieldId
     * @return CustomField|null
     */
    public function findById(int $fieldId): ?CustomField;

    /**
     * Create a new custom field.
     *
     * @param array $data
     * @return CustomField
     */
    public function create(array $data): CustomField;

    /**
     * Update an existing custom field.
     *
     * @param CustomField $field
     * @param array $data
     * @return CustomField
     */
    public function update(CustomField $field, array $data): CustomField;

    /**
     * Delete a custom field.
     *
     * @param CustomField $field
     * @return bool
     */
    public function delete(CustomField $field): bool;
}
