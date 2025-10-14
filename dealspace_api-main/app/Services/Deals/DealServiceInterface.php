<?php

namespace App\Services\Deals;

use App\Models\Deal;

interface DealServiceInterface
{
    /**
     * Get all deals with pagination.
     *
     * @param int $perPage
     * @param int $page
     * @param string|null $search
     * @param int|null $personId
     * @param int|null $stageId
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getAll(int $perPage = 15, int $page = 1, ?string $search, ?int $personId, ?int $stageId);

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

    /**
     * Get totals for filtered deals.
     *
     * @param string|null $search
     * @param int|null $personId
     * @param int|null $stageId
     * @return array
     */
    public function getTotals(?string $search, ?int $personId, ?int $stageId): array;

    /**
     * Find a deal by ID.
     *
     * @param int $dealId
     * @return Deal
     */
    public function findById(int $dealId): Deal;

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
     * @param int $dealId
     * @param array $data
     * @return Deal
     */
    public function update(int $dealId, array $data): Deal;

    /**
     * Delete a deal.
     *
     * @param int $dealId
     * @return bool
     */
    public function delete(int $dealId): bool;

    /**
     * Add a person to a deal.
     *
     * @param int $dealId
     * @param int $personId
     * @return void
     */
    public function addPersonToDeal(int $dealId, int $personId): void;

    /**
     * Remove a person from a deal.
     *
     * @param int $dealId
     * @param int $personId
     * @return void
     */
    public function removePersonFromDeal(int $dealId, int $personId): void;

    /**
     * Add a user to a deal.
     *
     * @param int $dealId
     * @param int $userId
     * @return void
     */
    public function addUserToDeal(int $dealId, int $userId): void;

    /**
     * Remove a user from a deal.
     *
     * @param int $dealId
     * @param int $userId
     * @return void
     */
    public function removeUserFromDeal(int $dealId, int $userId): void;
}