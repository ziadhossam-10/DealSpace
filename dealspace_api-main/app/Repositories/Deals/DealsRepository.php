<?php

namespace App\Repositories\Deals;

use App\Models\Deal;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;

class DealsRepository implements DealsRepositoryInterface
{
    protected $model;

    public function __construct(Deal $model)
    {
        $this->model = $model;
    }

    /**
     * Get all deals with pagination.
     *
     * @param int $perPage Number of items per page
     * @param int $page Current page number
     * @param string|null $search Search term
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator Paginated deal records with relationships loaded.
     */
    public function getAll(int $perPage = 15, int $page = 1, ?string $search, ?int $personId, ?int $stageId)
    {
        $dealQuery = $this->model->query();
        $user = Auth::user();
        if ($user) {
            $dealQuery->visibleTo($user);
        }

        $dealQuery->where(function ($query) use ($personId, $stageId) {
            if ($personId) {
                $query->whereHas('people', function ($q) use ($personId) {
                    $q->where('person_id', $personId);
                });
            }
            if ($stageId) {
                $query->where('stage_id', $stageId);
            }
        });

        if ($search) {
            $dealQuery->where(function ($query) use ($search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        return $dealQuery->with(['stage', 'type', 'people', 'users', 'attachments'])->latest()
            ->paginate($perPage, ['*'], 'page', $page);
    }

    /**
     * Get deals with closing dates within a specified interval.
     *
     * @param string $startDate
     * @param string $endDate
     * @param int $perPage
     * @param int $page
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getByClosingDateInterval(string $startDate, string $endDate, int $perPage = 15, int $page = 1)
    {
        return $this->model->with(['stage', 'type', 'people', 'users'])
            ->whereNotNull('projected_close_date')
            ->whereBetween('projected_close_date', [$startDate, $endDate])
            ->latest()
            ->paginate($perPage, ['*'], 'page', $page);
    }

    /**
     * Get totals for filtered deals (count and sum of prices).
     *
     * @param string|null $search Search term
     * @param int|null $personId Filter by person ID
     * @param int|null $stageId Filter by stage ID
     * @return array Array containing total_count and total_price
     */
    public function getTotals(?string $search, ?int $personId, ?int $stageId): array
    {
        $dealQuery = $this->model->query();

        $dealQuery->where(function ($query) use ($personId, $stageId) {
            if ($personId) {
                $query->whereHas('people', function ($q) use ($personId) {
                    $q->where('person_id', $personId);
                });
            }
            if ($stageId) {
                $query->where('stage_id', $stageId);
            }
        });

        if ($search) {
            $dealQuery->where(function ($query) use ($search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $totals = $dealQuery->selectRaw('COUNT(*) as total_count, SUM(price) as total_price')->first();

        return [
            'total_count' => (int) $totals->total_count,
            'total_price' => (float) ($totals->total_price ?? 0)
        ];
    }

    /**
     * Find a deal by its ID.
     *
     * @param int $dealId The ID of the deal to find.
     * @return Deal|null The found deal or null if not found.
     */
    public function findById(int $dealId): ?Deal
    {
        return $this->model->with(['stage', 'type', 'people', 'users'])->find($dealId);
    }

    /**
     * Create a new deal record.
     *
     * @param array $data The data for the new deal.
     * @return Deal The newly created Deal model instance.
     */
    public function create(array $data): Deal
    {
        return $this->model->create($data);
    }

    /**
     * Update an existing deal.
     *
     * @param Deal $deal The deal to update.
     * @param array $data The updated deal data.
     * @return Deal The updated Deal model instance with fresh relationships.
     */
    public function update(Deal $deal, array $data): Deal
    {
        $deal->update($data);
        return $deal->fresh(['stage', 'type', 'people', 'users']);
    }

    /**
     * Delete a deal.
     *
     * @param Deal $deal The deal to delete.
     * @return bool True if deletion was successful, false otherwise.
     */
    public function delete(Deal $deal): bool
    {
        return $deal->delete();
    }

    /**
     * Get deals by stage ID with pagination.
     *
     * @param int $stageId The ID of the stage.
     * @param int $perPage Number of items per page
     * @param int $page Current page number
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator Paginated deals in the stage.
     */
    public function getByStageId(int $stageId, int $perPage = 15, int $page = 1)
    {
        return $this->model->with(['stage', 'type', 'people', 'users'])
            ->where('stage_id', $stageId)
            ->paginate($perPage, ['*'], 'page', $page);
    }

    /**
     * Get deals by type ID with pagination.
     *
     * @param int $typeId The ID of the type.
     * @param int $perPage Number of items per page
     * @param int $page Current page number
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator Paginated deals of the type.
     */
    public function getByTypeId(int $typeId, int $perPage = 15, int $page = 1)
    {
        return $this->model->with(['stage', 'type', 'people', 'users'])
            ->where('type_id', $typeId)
            ->paginate($perPage, ['*'], 'page', $page);
    }

    /**
     * Add a person to a deal.
     *
     * @param Deal $deal The deal to add the person to.
     * @param int $personId The ID of the person to add.
     * @return void
     */
    public function addPerson(Deal $deal, int $personId): void
    {
        if (!$deal->people()->where('person_id', $personId)->exists()) {
            $deal->people()->attach($personId);
        }
    }

    /**
     * Remove a person from a deal.
     *
     * @param Deal $deal The deal to remove the person from.
     * @param int $personId The ID of the person to remove.
     * @return void
     */
    public function removePerson(Deal $deal, int $personId): void
    {
        $deal->people()->detach($personId);
    }

    /**
     * Add a user to a deal.
     *
     * @param Deal $deal The deal to add the user to.
     * @param int $userId The ID of the user to add.
     * @return void
     */
    public function addUser(Deal $deal, int $userId): void
    {
        if (!$deal->users()->where('user_id', $userId)->exists()) {
            $deal->users()->attach($userId);
        }
    }

    /**
     * Remove a user from a deal.
     *
     * @param Deal $deal The deal to remove the user from.
     * @param int $userId The ID of the user to remove.
     * @return void
     */
    public function removeUser(Deal $deal, int $userId): void
    {
        $deal->users()->detach($userId);
    }

    /**
     * List deals for a user with pagination and filtering.
     *
     * @param User $user The user to list deals for.
     * @param array $filters An array of filters to apply (e.g., stage_id, per_page).
     * @return LengthAwarePaginator Paginated list of deals for the user.
     */
    public function listForUser(User $user, array $filters = []): LengthAwarePaginator
    {
        return Deal::query()
            ->visibleTo($user)
            ->when($filters['stage_id'] ?? null, fn ($q, $stage) => $q->where('stage_id', $stage))
            ->paginate($filters['per_page'] ?? 15);
    }
}