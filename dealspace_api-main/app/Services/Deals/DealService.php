<?php

namespace App\Services\Deals;

use App\Models\Deal;
use App\Repositories\Deals\DealsRepositoryInterface;
use App\Repositories\Deals\DealStagesRepositoryInterface;
use App\Repositories\Users\UsersRepositoryInterface;
use App\Repositories\People\PeopleRepositoryInterface;
use App\Repositories\Deals\DealTypesRepositoryInterface;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;

class DealService implements DealServiceInterface
{
    protected $dealsRepository;
    protected $usersRepository;
    protected $peopleRepository;
    protected $stagesRepository;
    protected $typesRepository;
    protected $dealAttachmentService;

    public function __construct(
        DealsRepositoryInterface $dealsRepository,
        UsersRepositoryInterface $usersRepository,
        PeopleRepositoryInterface $peopleRepository,
        DealStagesRepositoryInterface $stagesRepository,
        DealTypesRepositoryInterface $typesRepository,
        DealAttachmentService $dealAttachmentService
    ) {
        $this->dealsRepository = $dealsRepository;
        $this->usersRepository = $usersRepository;
        $this->peopleRepository = $peopleRepository;
        $this->stagesRepository = $stagesRepository;
        $this->typesRepository = $typesRepository;
        $this->dealAttachmentService = $dealAttachmentService;
    }

    /**
     * Get all deals.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getAll(int $perPage = 15, int $page = 1, ?string $search, ?int $personId, ?int $stageId)
    {
        return $this->dealsRepository->getAll($perPage, $page, $search, $personId, $stageId);
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
        return $this->dealsRepository->getByClosingDateInterval($startDate, $endDate, $perPage, $page);
    }

    /**
     * Get totals for filtered deals.
     *
     * @param string|null $search Search term
     * @param int|null $personId Filter by person ID
     * @param int|null $stageId Filter by stage ID
     * @return array Array containing total_count and total_price
     */
    public function getTotals(?string $search, ?int $personId, ?int $stageId): array
    {
        return $this->dealsRepository->getTotals($search, $personId, $stageId);
    }

    /**
     * Get a deal by ID.
     *
     * @param int $dealId
     * @return Deal
     * @throws ModelNotFoundException
     */
    public function findById(int $dealId): Deal
    {
        $deal = $this->dealsRepository->findById($dealId);
        if (!$deal) {
            throw new ModelNotFoundException('Deal not found');
        }
        return $deal;
    }

    /**
     * Create a new deal with people, users, and attachments.
     *
     * @param array $data The complete deal data including:
     * - 'name' (string) The name of the deal
     * - 'stage_id' (int) The ID of the stage
     * - 'type_id' (int) The ID of the type
     * - 'description' (string) Optional description
     * - 'price' (int) Price of the deal
     * - 'projected_close_date' (date) Projected close date
     * - 'order_weight' (int) Order weight
     * - 'commission_value' (int) Commission value
     * - 'agent_commission' (int) Agent commission
     * - 'team_commission' (int) Team commission
     * - ['people_ids'] (array) Array of person IDs to add to the deal
     * - ['users_ids'] (array) Array of user IDs to add to the deal
     * - ['attachments'] (array) Array of UploadedFile objects to attach to the deal
     * @return Deal
     * @throws ModelNotFoundException
     */
    public function create(array $data): Deal
    {
        return DB::transaction(function () use ($data) {
            // Extract relationship arrays
            $peopleIds = $data['people_ids'] ?? [];
            $userIds = $data['users_ids'] ?? [];
            $attachments = $data['attachments'] ?? [];

            // Remove relationship arrays from data to prevent SQL errors
            unset($data['people_ids'], $data['users_ids'], $data['attachments']);

            // Verify that the stage exists
            $stage = $this->stagesRepository->findById($data['stage_id']);
            if (!$stage) {
                throw new ModelNotFoundException('Stage not found');
            }

            // Verify that the type exists
            $type = $this->typesRepository->findById($data['type_id']);
            if (!$type) {
                throw new ModelNotFoundException('Type not found');
            }

            // Create the deal
            $deal = $this->dealsRepository->create($data);

            // Add people to deal if people_ids array is provided
            if (!empty($peopleIds)) {
                $this->addPeopleToDeal($deal->id, $peopleIds);
            }

            // Add users to deal if users_ids array is provided
            if (!empty($userIds)) {
                $this->addUsersToDeal($deal->id, $userIds);
            }

            // Handle file attachments
            if (!empty($attachments)) {
                $this->handleFileAttachments($deal->id, $attachments);
            }

            return $deal;
        });
    }

    /**
     * Update an existing deal and its relationships.
     *
     * @param int $dealId
     * @param array $data The complete deal data including:
     * - Deal fields to update
     * - ['people_ids'] (array) Array of person IDs to add to the deal
     * - ['people_ids_to_delete'] (array) Array of person IDs to remove from the deal
     * - ['users_ids'] (array) Array of user IDs to add to the deal
     * - ['users_ids_to_delete'] (array) Array of user IDs to remove from the deal
     * - ['attachments'] (array) Array of UploadedFile objects to attach to the deal
     * - ['attachments_to_delete'] (array) Array of attachment IDs to remove from the deal
     * @return Deal
     * @throws ModelNotFoundException
     */
    public function update(int $dealId, array $data): Deal
    {
        return DB::transaction(function () use ($dealId, $data) {
            $deal = $this->dealsRepository->findById($dealId);
            if (!$deal) {
                throw new ModelNotFoundException('Deal not found');
            }

            // Extract relationship arrays
            $peopleIdsToAdd = $data['people_ids'] ?? [];
            $peopleIdsToDelete = $data['people_ids_to_delete'] ?? [];
            $userIdsToAdd = $data['users_ids'] ?? [];
            $userIdsToDelete = $data['users_ids_to_delete'] ?? [];
            $attachments = $data['attachments'] ?? [];
            $attachmentIdsToDelete = $data['attachments_to_delete'] ?? [];

            // Remove relationship arrays from data to prevent SQL errors
            unset(
                $data['people_ids'],
                $data['people_ids_to_delete'],
                $data['users_ids'],
                $data['users_ids_to_delete'],
                $data['attachments'],
                $data['attachments_to_delete']
            );

            // If changing stage, verify that the new stage exists
            if (isset($data['stage_id'])) {
                $stage = $this->stagesRepository->findById($data['stage_id']);
                if (!$stage) {
                    throw new ModelNotFoundException('Stage not found');
                }
            }

            // If changing type, verify that the new type exists
            if (isset($data['type_id'])) {
                $type = $this->typesRepository->findById($data['type_id']);
                if (!$type) {
                    throw new ModelNotFoundException('Type not found');
                }
            }

            // Update the deal
            $updatedDeal = $this->dealsRepository->update($deal, $data);

            // Add people to deal
            if (!empty($peopleIdsToAdd)) {
                $this->addPeopleToDeal($dealId, $peopleIdsToAdd);
            }

            // Remove people from deal
            if (!empty($peopleIdsToDelete)) {
                $this->removePeopleFromDeal($dealId, $peopleIdsToDelete);
            }

            // Add users to deal
            if (!empty($userIdsToAdd)) {
                $this->addUsersToDeal($dealId, $userIdsToAdd);
            }

            // Remove users from deal
            if (!empty($userIdsToDelete)) {
                $this->removeUsersFromDeal($dealId, $userIdsToDelete);
            }

            // Handle file attachments
            if (!empty($attachments)) {
                $this->handleFileAttachments($dealId, $attachments);
            }

            // Delete specified attachments
            if (!empty($attachmentIdsToDelete)) {
                $this->deleteAttachments($dealId, $attachmentIdsToDelete);
            }

            return $updatedDeal;
        });
    }

    /**
     * Delete a deal.
     *
     * @param int $dealId
     * @return bool
     * @throws ModelNotFoundException
     */
    public function delete(int $dealId): bool
    {
        $deal = $this->dealsRepository->findById($dealId);
        if (!$deal) {
            throw new ModelNotFoundException('Deal not found');
        }

        // Delete all attachments and their files before deleting the deal
        $attachments = $this->dealAttachmentService->getAll($dealId);
        foreach ($attachments as $attachment) {
            // Delete the physical file
            if ($attachment->path && Storage::disk('public')->exists($attachment->path)) {
                Storage::disk('public')->delete($attachment->path);
            }
            // Delete the attachment record
            $this->dealAttachmentService->delete($dealId, $attachment->id);
        }

        return $this->dealsRepository->delete($deal);
    }

    /**
     * Add a person to a deal.
     *
     * @param int $dealId
     * @param int $personId
     * @return void
     * @throws ModelNotFoundException
     */
    public function addPersonToDeal(int $dealId, int $personId): void
    {
        $deal = $this->dealsRepository->findById($dealId);
        if (!$deal) {
            throw new ModelNotFoundException('Deal not found');
        }

        $person = $this->peopleRepository->findById($personId);
        if (!$person) {
            throw new ModelNotFoundException('Person not found');
        }

        $this->dealsRepository->addPerson($deal, $personId);
    }

    /**
     * Remove a person from a deal.
     *
     * @param int $dealId
     * @param int $personId
     * @return void
     * @throws ModelNotFoundException
     */
    public function removePersonFromDeal(int $dealId, int $personId): void
    {
        $deal = $this->dealsRepository->findById($dealId);
        if (!$deal) {
            throw new ModelNotFoundException('Deal not found');
        }

        $this->dealsRepository->removePerson($deal, $personId);
    }

    /**
     * Add a user to a deal.
     *
     * @param int $dealId
     * @param int $userId
     * @return void
     * @throws ModelNotFoundException
     */
    public function addUserToDeal(int $dealId, int $userId): void
    {
        $deal = $this->dealsRepository->findById($dealId);
        if (!$deal) {
            throw new ModelNotFoundException('Deal not found');
        }

        $user = $this->usersRepository->findById($userId);
        if (!$user) {
            throw new ModelNotFoundException('User not found');
        }

        $this->dealsRepository->addUser($deal, $userId);
    }

    /**
     * Remove a user from a deal.
     *
     * @param int $dealId
     * @param int $userId
     * @return void
     * @throws ModelNotFoundException
     */
    public function removeUserFromDeal(int $dealId, int $userId): void
    {
        $deal = $this->dealsRepository->findById($dealId);
        if (!$deal) {
            throw new ModelNotFoundException('Deal not found');
        }

        $this->dealsRepository->removeUser($deal, $userId);
    }

    /**
     * Add multiple people to a deal.
     *
     * @param int $dealId
     * @param array $peopleIds
     * @return void
     * @throws ModelNotFoundException
     */
    protected function addPeopleToDeal(int $dealId, array $peopleIds): void
    {
        foreach ($peopleIds as $personId) {
            $this->addPersonToDeal($dealId, $personId);
        }
    }

    /**
     * Remove multiple people from a deal.
     *
     * @param int $dealId
     * @param array $peopleIds
     * @return void
     * @throws ModelNotFoundException
     */
    protected function removePeopleFromDeal(int $dealId, array $peopleIds): void
    {
        foreach ($peopleIds as $personId) {
            $this->removePersonFromDeal($dealId, $personId);
        }
    }

    /**
     * Add multiple users to a deal.
     *
     * @param int $dealId
     * @param array $userIds
     * @return void
     * @throws ModelNotFoundException
     */
    protected function addUsersToDeal(int $dealId, array $userIds): void
    {
        foreach ($userIds as $userId) {
            $this->addUserToDeal($dealId, $userId);
        }
    }

    /**
     * Remove multiple users from a deal.
     *
     * @param int $dealId
     * @param array $userIds
     * @return void
     * @throws ModelNotFoundException
     */
    protected function removeUsersFromDeal(int $dealId, array $userIds): void
    {
        foreach ($userIds as $userId) {
            $this->removeUserFromDeal($dealId, $userId);
        }
    }

    /**
     * Handle file attachments for a deal.
     *
     * @param int $dealId
     * @param array $attachments Array of UploadedFile objects
     * @return void
     */
    protected function handleFileAttachments(int $dealId, array $attachments): void
    {
        foreach ($attachments as $attachment) {
            if ($attachment instanceof UploadedFile) {
                // Store the file
                $filePath = $attachment->store('deal_attachments/' . $dealId, 'public');

                // Auto-generate file data
                $fileData = [
                    'name' => $attachment->getClientOriginalName(),
                    'path' => $filePath,
                    'size' => $attachment->getSize(),
                    'mime_type' => $attachment->getMimeType(),
                    'type' => $this->getFileTypeFromMime($attachment->getMimeType()),
                    'description' => null, // Auto-set to null, can be updated later if needed
                ];

                // Create the attachment record
                $this->dealAttachmentService->create($dealId, $fileData);
            }
        }
    }

    /**
     * Delete multiple attachments from a deal.
     *
     * @param int $dealId
     * @param array $attachmentIds
     * @return void
     */
    protected function deleteAttachments(int $dealId, array $attachmentIds): void
    {
        foreach ($attachmentIds as $attachmentId) {
            try {
                // Get the attachment to access its path
                $attachment = $this->dealAttachmentService->findById($dealId, $attachmentId);

                // Delete the physical file
                if ($attachment->path && Storage::disk('public')->exists($attachment->path)) {
                    Storage::disk('public')->delete($attachment->path);
                }

                // Delete the attachment record
                $this->dealAttachmentService->delete($dealId, $attachmentId);
            } catch (ModelNotFoundException $e) {
                // Attachment not found, continue with next one
                continue;
            }
        }
    }

    /**
     * Get file type from MIME type.
     *
     * @param string $mimeType
     * @return string
     */
    protected function getFileTypeFromMime(string $mimeType): string
    {
        $typeMap = [
            'image/jpeg' => 'image',
            'image/png' => 'image',
            'image/gif' => 'image',
            'image/webp' => 'image',
            'application/pdf' => 'document',
            'application/msword' => 'document',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'document',
            'application/vnd.ms-excel' => 'spreadsheet',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'spreadsheet',
            'text/plain' => 'text',
            'text/csv' => 'spreadsheet',
            'video/mp4' => 'video',
            'video/avi' => 'video',
            'audio/mpeg' => 'audio',
            'audio/wav' => 'audio',
        ];

        return $typeMap[$mimeType] ?? 'other';
    }
}