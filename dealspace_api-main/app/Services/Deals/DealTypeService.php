<?php

namespace App\Services\Deals;

use App\Models\DealType;
use App\Repositories\Deals\DealTypesRepositoryInterface;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class DealTypeService implements DealTypeServiceInterface
{
    protected $dealTypesRepository;

    public function __construct(DealTypesRepositoryInterface $dealTypesRepository)
    {
        $this->dealTypesRepository = $dealTypesRepository;
    }

    /**
     * Get all deal types.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getAll()
    {
        return $this->dealTypesRepository->getAll();
    }

    /**
     * Get a deal type by ID.
     *
     * @param int $dealTypeId
     * @return DealType
     * @throws ModelNotFoundException
     */
    public function findById(int $dealTypeId): DealType
    {
        $dealType = $this->dealTypesRepository->findById($dealTypeId);
        if (!$dealType) {
            throw new ModelNotFoundException('Deal type not found');
        }
        return $dealType;
    }

    /**
     * Create a new deal type.
     *
     * @param array $data The deal type data including:
     * - 'name' (string) The name of the deal type
     * - 'sort' (int) The sort order
     * @return DealType
     */
    public function create(array $data): DealType
    {
        return $this->dealTypesRepository->create($data);
    }

    /**
     * Update an existing deal type.
     *
     * @param int $dealTypeId
     * @param array $data The updated deal type data
     * @return DealType
     * @throws ModelNotFoundException
     */
    public function update(int $dealTypeId, array $data): DealType
    {
        $dealType = $this->dealTypesRepository->findById($dealTypeId);
        if (!$dealType) {
            throw new ModelNotFoundException('Deal type not found');
        }
        return $this->dealTypesRepository->update($dealType, $data);
    }

    /**
     * Delete a deal type.
     *
     * @param int $dealTypeId
     * @return bool
     * @throws ModelNotFoundException
     */
    public function delete(int $dealTypeId): bool
    {
        $dealType = $this->dealTypesRepository->findById($dealTypeId);
        if (!$dealType) {
            throw new ModelNotFoundException('Deal type not found');
        }
        return $this->dealTypesRepository->delete($dealType);
    }

    /**
     * Update the sort order of a deal type.
     *
     * @param int $dealTypeId The ID of the deal type to update.
     * @param int $newSortOrder The new sort order value.
     * @return void
     * @throws ModelNotFoundException
     */
    public function updateSortOrder(int $dealTypeId, int $newSortOrder): void
    {
        $dealType = $this->dealTypesRepository->findById($dealTypeId);
        if (!$dealType) {
            throw new ModelNotFoundException('Deal type not found');
        }

        $this->dealTypesRepository->updateSortOrder($dealTypeId, $newSortOrder);
    }
}