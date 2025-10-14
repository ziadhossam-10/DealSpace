<?php

namespace App\Services\CustomFields;

use App\Enums\CustomFieldTypeEnum;
use App\Models\CustomField;
use App\Repositories\CustomFields\CustomFieldsRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class CustomFieldService implements CustomFieldServiceInterface
{
    protected $customFieldsRepository;

    public function __construct(CustomFieldsRepositoryInterface $customFieldsRepository)
    {
        $this->customFieldsRepository = $customFieldsRepository;
    }

    /**
     * Get all custom fields
     *
     * @return Collection
     */
    public function getAll(): Collection
    {
        return $this->customFieldsRepository->getAll();
    }

    /**
     * Find a custom field by ID.
     *
     * @param int $fieldId
     * @return CustomField
     * @throws ModelNotFoundException
     */
    public function findById(int $fieldId): CustomField
    {
        $field = $this->customFieldsRepository->findById($fieldId);
        if (!$field) {
            throw new ModelNotFoundException('Custom field not found');
        }
        return $field;
    }

    /**
     * Create a new custom field.
     *
     * @param array $data
     * @return CustomField
     * @throws InvalidArgumentException
     */
    public function create(array $data): CustomField
    {
        $data['name'] = $this->customFieldName($data['label'] ?? '');
        $data['options'] = $data['options'] ?? [];

        return DB::transaction(function () use ($data) {
            // Create the field
            return $this->customFieldsRepository->create($data);
        });
    }

    /**
     * Update an existing custom field.
     *
     * @param int $fieldId
     * @param array $data
     * @return CustomField
     * @throws ModelNotFoundException
     * @throws InvalidArgumentException
     */
    public function update(int $fieldId, array $data): CustomField
    {
        $data['name'] = $this->customFieldName($data['label'] ?? '');
        $data['options'] = $data['options'] ?? [];

        return DB::transaction(function () use ($fieldId, $data) {
            $field = $this->customFieldsRepository->findById($fieldId);

            if (!$field) {
                throw new ModelNotFoundException('Custom field not found');
            }

            // Update the field
            return $this->customFieldsRepository->update($field, $data);
        });
    }

    /**
     * Delete a custom field.
     *
     * @param int $fieldId
     * @return bool
     * @throws ModelNotFoundException
     */
    public function delete(int $fieldId): bool
    {
        $field = $this->customFieldsRepository->findById($fieldId);
        if (!$field) {
            throw new ModelNotFoundException('Custom field not found');
        }

        return $this->customFieldsRepository->delete($field);
    }

    /**
     * Convert an email-like string to camel case and prefix with 'customField'.
     *
     * This method removes non-alphanumeric characters, splits the string into parts,
     * converts them to camel case, and prefixes the result with 'customField'.
     *
     * Example:
     *   Input: "John.DOE@example.com"
     *   Output: "customFieldJohnDoeExampleCom"
     *
     * @param string $email The input string resembling an email address.
     * @return string The camel-cased string prefixed with 'customField'.
     */
    private function customFieldName(string $label): string
    {
        // Remove non-alphanumeric characters and split into parts
        $label = preg_replace('/[^a-zA-Z0-9]/', ' ', $label);
        $parts = explode(' ', strtolower($label));

        // Convert parts to camel case
        $camelParts = array_map('ucfirst', $parts);
        $camelCase = implode('', $camelParts);

        // Prepend the prefix
        return 'customField' . $camelCase;
    }
}
