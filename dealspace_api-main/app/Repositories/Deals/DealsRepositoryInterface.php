<?php

namespace App\Repositories\Deals;

use App\Models\Deal;

interface DealsRepositoryInterface
{
    /**
     * Get all deals with pagination.
     *
     * @param int $perPage Number of items per page
     * @param int $page Current page number
     * @param string|null $search Search term
     * @param int|null $personId Filter by person ID
     * @param int|null $stageId Filter by stage ID
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getAll(int $perPage = 15, int $page = 1, ?string $search, ?int $personId, ?int $stageId);

    /**
     * Get totals for filtered deals (count and sum of prices).
     *
     * @param string|null $search Search term
     * @param int|null $personId Filter by person ID
     * @param int|null $stageId Filter by stage ID
     * @return array Array containing total_count and total_price
     */

    /**
     * Get deals with closing dates within a specified interval.
     *
     * @param string $startDate
     * @param string $endDate
     * @param int $perPage
     * @param int $page
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getByClosingDateInterval(string $startDate, string $endDate, int $perPage = 15, int $page = 1);

    public function getTotals(?string $search, ?int $personId, ?int $stageId): array;

    /**
     * Find a deal by its ID.
     *
     * @param int $dealId
     * @return Deal|null
     */
    public function findById(int $dealId): ?Deal;

    /**
     * Create a new deal.
     *
     * @param array $data
     * @return Deal
     */
    public function create(array $data): Deal;

    /**
     * Update an existing deal.
     *
     * @param Deal $deal
     * @param array $data
     * @return Deal
     */
    public function update(Deal $deal, array $data): Deal;

    /**
     * Delete a deal.
     *
     * @param Deal $deal
     * @return bool
     */
    public function delete(Deal $deal): bool;

    /**
     * Get deals by stage ID with pagination.
     *
     * @param int $stageId
     * @param int $perPage
     * @param int $page
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getByStageId(int $stageId, int $perPage = 15, int $page = 1);

    /**
     * Get deals by type ID with pagination.
     *
     * @param int $typeId
     * @param int $perPage
     * @param int $page
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getByTypeId(int $typeId, int $perPage = 15, int $page = 1);

    /**
     * Add a person to a deal.
     *
     * @param Deal $deal
     * @param int $personId
     * @return void
     */
    public function addPerson(Deal $deal, int $personId): void;

    /**
     * Remove a person from a deal.
     *
     * @param Deal $deal
     * @param int $personId
     * @return void
     */
    public function removePerson(Deal $deal, int $personId): void;

    /**
     * Add a user to a deal.
     *
     * @param Deal $deal
     * @param int $userId
     * @return void
     */
    public function addUser(Deal $deal, int $userId): void;

    /**
     * Remove a user from a deal.
     *
     * @param Deal $deal
     * @param int $userId
     * @return void
     */
    public function removeUser(Deal $deal, int $userId): void;
}