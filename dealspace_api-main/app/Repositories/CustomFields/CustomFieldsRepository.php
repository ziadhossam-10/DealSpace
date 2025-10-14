<?php

namespace App\Repositories\CustomFields;

use App\Models\CustomField;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class CustomFieldsRepository implements CustomFieldsRepositoryInterface
{
    protected $model;

    /**
     * Constructor
     *
     * @param CustomField $model The CustomField model instance
     */
    public function __construct(CustomField $model)
    {
        $this->model = $model;
    }

    /**
     * Get all custom fields with pagination and optional search.
     *
     * @return Collection
     */
    public function getAll(): Collection
    {
        return $this->model->get();
    }

    /**
     * Find a custom field by ID.
     *
     * @param int $fieldId
     * @return CustomField|null
     */
    public function findById(int $fieldId): ?CustomField
    {
        return $this->model->find($fieldId);
    }

    /**
     * Create a new custom field.
     *
     * @param array $data
     * @return CustomField
     */
    public function create(array $data): CustomField
    {
        return $this->model->create($data);
    }

    /**
     * Update an existing custom field.
     *
     * @param CustomField $field
     * @param array $data
     * @return CustomField
     */
    public function update(CustomField $field, array $data): CustomField
    {
        $field->update($data);
        return $field->fresh();
    }

    /**
     * Delete a custom field.
     *
     * @param CustomField $field
     * @return bool
     */
    public function delete(CustomField $field): bool
    {
        return $field->delete();
    }
}
