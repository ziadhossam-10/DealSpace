<?php

namespace App\Services\Notes;

use App\Models\Note;
use App\Repositories\Notes\NotesRepositoryInterface;
use App\Repositories\Users\UsersRepositoryInterface;
use App\Services\Notifications\NotificationService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

class NoteService implements NoteServiceInterface
{
    protected $notesRepository;
    protected $usersRepository;
    protected $notificationService;

    public function __construct(
        NotesRepositoryInterface $notesRepository,
        UsersRepositoryInterface $usersRepository,
        NotificationService $notificationService
    ) {
        $this->notesRepository = $notesRepository;
        $this->usersRepository = $usersRepository;
        $this->notificationService = $notificationService;
    }

    /**
     * Get all notes for a person.
     *
     * @param int $perPage
     * @param int $page
     * @param int $personId
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getAll(int $perPage = 15, int $page = 1, int $personId)
    {
        return $this->notesRepository->getAll($perPage, $page, $personId);
    }

    /**
     * Get a note by ID.
     *
     * @param int $noteId
     * @return Note
     * @throws ModelNotFoundException
     */
    public function findById(int $noteId): Note
    {
        $note = $this->notesRepository->findById($noteId);
        if (!$note) {
            throw new ModelNotFoundException('Note not found');
        }
        return $note;
    }

    /**
     * Create a new note with mentions.
     *
     * @param array $data The complete note data including:
     * - 'subject' (string) The subject of the note
     * - 'body' (string) The body content of the note
     * - 'person_id' (int) The ID of the person the note belongs to
     * - 'created_by' (int) The ID of the user creating the note
     * - ['mentions'] (array) Array of user IDs to mention
     * @return Note
     * @throws ModelNotFoundException
     */
    public function create(array $data): Note
    {
        return DB::transaction(function () use ($data) {
            // Extract mention-related arrays
            $mentionIds = $data['mentions'] ?? [];

            // Remove mention arrays from data to prevent SQL errors
            unset($data['mentions']);

            // Verify that the creator exists before creating the note
            if (isset($data['created_by'])) {
                $creator = $this->usersRepository->findById($data['created_by']);
                if (!$creator) {
                    throw new ModelNotFoundException('Creator user not found');
                }
            }

            // Create the note
            $note = $this->notesRepository->create($data);

            // Add mentions to note if mention IDs array is provided
            if (!empty($mentionIds)) {
                $this->addMentionsToNote($note->id, $mentionIds);
            }

            // notify mentioned users
            $this->notificationService->create([
                'user_ids' => $mentionIds,
                'title' => 'You were mentioned in a note',
                'message' => 'You have been mentioned in a note: ' . $note->subject . ' by ' . $creator->name,
                'action' => '/people/' . $note->person_id,
                'image' => $creator->avatar ? $creator->avatar : null,
            ]);

            return $note;
        });
    }

    /**
     * Update an existing note and its mentions.
     *
     * @param int $noteId
     * @param array $data The complete note data including:
     * - Note fields to update
     * - ['mentions'] (array) Array of user IDs to mention (replaces all existing mentions)
     * - ['mentions_to_add'] (array) Array of user IDs to add to mentions
     * - ['mentions_to_remove'] (array) Array of user IDs to remove from mentions
     * @return Note
     * @throws ModelNotFoundException
     */
    public function update(int $noteId, array $data): Note
    {
        return DB::transaction(function () use ($noteId, $data) {
            $note = $this->notesRepository->findById($noteId);
            if (!$note) {
                throw new ModelNotFoundException('Note not found');
            }

            // Extract mention-related arrays
            $mentions = $data['mentions'] ?? null;
            $mentionsToAdd = $data['mentions_to_add'] ?? [];
            $mentionsToRemove = $data['mentions_to_remove'] ?? [];

            // Remove mention arrays from data to prevent SQL errors
            unset($data['mentions'], $data['mentions_to_add'], $data['mentions_to_remove']);

            // If changing updater, verify that the user exists
            if (isset($data['updated_by'])) {
                $updater = $this->usersRepository->findById($data['updated_by']);
                if (!$updater) {
                    throw new ModelNotFoundException('Updater user not found');
                }
            }

            // Update the note
            $updatedNote = $this->notesRepository->update($note, $data);

            // Handle mentions
            if ($mentions !== null) {
                // Replace all mentions
                $this->assignMentionsToNote($noteId, $mentions);
            } else {
                // Add mentions to note
                if (!empty($mentionsToAdd)) {
                    $this->addMentionsToNote($noteId, $mentionsToAdd);
                }

                // Remove mentions from note
                if (!empty($mentionsToRemove)) {
                    $this->removeMentionsFromNote($noteId, $mentionsToRemove);
                }
            }

            return $updatedNote;
        });
    }

    /**
     * Delete a note.
     *
     * @param int $noteId
     * @return bool
     * @throws ModelNotFoundException
     */
    public function delete(int $noteId): bool
    {
        $note = $this->notesRepository->findById($noteId);
        if (!$note) {
            throw new ModelNotFoundException('Note not found');
        }

        return $this->notesRepository->delete($note);
    }

    /**
     * Add a mention to a note.
     *
     * @param int $noteId
     * @param int $userId
     * @return void
     * @throws ModelNotFoundException
     */
    public function addMentionToNote(int $noteId, int $userId): void
    {
        $note = $this->notesRepository->findById($noteId);
        if (!$note) {
            throw new ModelNotFoundException('Note not found');
        }

        $user = $this->usersRepository->findById($userId);
        if (!$user) {
            throw new ModelNotFoundException('User not found');
        }

        $this->notesRepository->addMentions($note, [$userId]);
    }

    /**
     * Remove a mention from a note.
     *
     * @param int $noteId
     * @param int $userId
     * @return void
     * @throws ModelNotFoundException
     */
    public function removeMentionFromNote(int $noteId, int $userId): void
    {
        $note = $this->notesRepository->findById($noteId);
        if (!$note) {
            throw new ModelNotFoundException('Note not found');
        }

        $this->notesRepository->removeMentions($note, [$userId]);
    }

    /**
     * Assign mentions to a note (replace all existing mentions).
     *
     * @param int $noteId
     * @param array $userIds
     * @return void
     * @throws ModelNotFoundException
     */
    public function assignMentionsToNote(int $noteId, array $userIds): void
    {
        $note = $this->notesRepository->findById($noteId);
        if (!$note) {
            throw new ModelNotFoundException('Note not found');
        }

        // Verify all users exist
        foreach ($userIds as $userId) {
            $user = $this->usersRepository->findById($userId);
            if (!$user) {
                throw new ModelNotFoundException("User with ID {$userId} not found");
            }
        }

        $this->notesRepository->assignMentions($note, $userIds);
    }

    /**
     * Add multiple mentions to a note.
     *
     * @param int $noteId
     * @param array $userIds
     * @return void
     * @throws ModelNotFoundException
     */
    protected function addMentionsToNote(int $noteId, array $userIds): void
    {
        foreach ($userIds as $userId) {
            $this->addMentionToNote($noteId, $userId);
        }
    }

    /**
     * Remove multiple mentions from a note.
     *
     * @param int $noteId
     * @param array $userIds
     * @return void
     * @throws ModelNotFoundException
     */
    protected function removeMentionsFromNote(int $noteId, array $userIds): void
    {
        foreach ($userIds as $userId) {
            $this->removeMentionFromNote($noteId, $userId);
        }
    }

    /**
     * Get notes where a specific user is mentioned.
     *
     * @param int $userId
     * @param int $perPage
     * @param int $page
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getNotesWhereMentioned(int $userId, int $perPage = 15, int $page = 1)
    {
        return $this->notesRepository->getNotesWhereMentioned($userId, $perPage, $page);
    }

    /**
     * Get all mentions for a specific note.
     *
     * @param int $noteId
     * @return \Illuminate\Database\Eloquent\Collection
     * @throws ModelNotFoundException
     */
    public function getNoteMentions(int $noteId): \Illuminate\Database\Eloquent\Collection
    {
        $note = $this->notesRepository->findById($noteId);
        if (!$note) {
            throw new ModelNotFoundException('Note not found');
        }

        return $this->notesRepository->getNoteMentions($note);
    }
}
