<?php

namespace App\Repositories\Notes;

use App\Models\Note;
use App\Models\NoteMention;

class NotesRepository implements NotesRepositoryInterface
{
    protected $model;

    public function __construct(Note $model)
    {
        $this->model = $model;
    }

    /**
     * Get all notes with pagination.
     *
     * @param int $perPage Number of items per page
     * @param int $page Current page number
     * @param int $personId
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator Paginated note records with relationships loaded.
     */
    public function getAll(int $perPage = 15, int $page = 1, int $personId): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        return $this->model->where(['person_id' => $personId])
            ->with('mentionedUsers') // Load mentioned users
            ->latest()
            ->paginate($perPage, ['*'], 'page', $page);
    }

    /**
     * Find a note by its ID.
     *
     * @param int $noteId The ID of the note to find.
     * @return Note|null The found note or null if not found.
     */
    public function findById(int $noteId): ?Note
    {
        return $this->model->with('mentionedUsers')->find($noteId);
    }

    /**
     * Create a new note record.
     *
     * @param array $data The data for the new note, including:
     * - 'subject' (string) The subject of the note.
     * - 'body' (string) The body content of the note.
     * - 'person_id' (int) The ID of the person the note belongs to.
     * - 'created_by' (int) The ID of the user creating the note.
     * - 'mentions' (array) Optional array of user IDs to mention.
     * @return Note The newly created Note model instance.
     */
    public function create(array $data): Note
    {
        $mentions = $data['mentions'] ?? [];
        unset($data['mentions']); // Remove mentions from main data

        $note = $this->model->create($data);

        if (!empty($mentions)) {
            $this->assignMentions($note, $mentions);
        }

        return $note->fresh(['mentionedUsers']);
    }

    /**
     * Update an existing note.
     *
     * @param Note $note The note to update.
     * @param array $data The updated note data including:
     * - ['subject'] (string) The updated subject of the note.
     * - ['body'] (string) The updated body content of the note.
     * - ['mentions'] (array) Optional array of user IDs to mention.
     * @return Note The updated Note model instance with fresh relationships.
     */
    public function update(Note $note, array $data): Note
    {
        $mentions = $data['mentions'] ?? null;
        unset($data['mentions']); // Remove mentions from main data

        $note->update($data);

        if ($mentions !== null) {
            $this->assignMentions($note, $mentions);
        }

        return $note->fresh(['mentionedUsers']);
    }

    /**
     * Delete a note.
     *
     * @param Note $note The note to delete.
     * @return bool True if deletion was successful, false otherwise.
     */
    public function delete(Note $note): bool
    {
        return $note->delete();
    }

    /**
     * Assign mentions to a note.
     *
     * @param Note $note The note to assign mentions to.
     * @param array $userIds Array of user IDs to mention.
     * @return void
     */
    public function assignMentions(Note $note, array $userIds): void
    {
        // Remove existing mentions
        $note->mentions()->delete();

        // Add new mentions
        if (!empty($userIds)) {
            $mentions = collect($userIds)->map(function ($userId) use ($note) {
                return [
                    'note_id' => $note->id,
                    'mentioned_user_id' => $userId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            })->toArray();

            NoteMention::insert($mentions);
        }
    }

    /**
     * Add mentions to an existing note without removing existing ones.
     *
     * @param Note $note The note to add mentions to.
     * @param array $userIds Array of user IDs to mention.
     * @return void
     */
    public function addMentions(Note $note, array $userIds): void
    {
        $existingMentions = $note->mentions()->pluck('mentioned_user_id')->toArray();
        $newMentions = array_diff($userIds, $existingMentions);

        if (!empty($newMentions)) {
            $mentions = collect($newMentions)->map(function ($userId) use ($note) {
                return [
                    'note_id' => $note->id,
                    'mentioned_user_id' => $userId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            })->toArray();

            NoteMention::insert($mentions);
        }
    }

    /**
     * Remove specific mentions from a note.
     *
     * @param Note $note The note to remove mentions from.
     * @param array $userIds Array of user IDs to remove from mentions.
     * @return void
     */
    public function removeMentions(Note $note, array $userIds): void
    {
        $note->mentions()->whereIn('mentioned_user_id', $userIds)->delete();
    }

    /**
     * Get notes where a specific user is mentioned.
     *
     * @param int $userId The user ID to search for in mentions.
     * @param int $perPage Number of items per page.
     * @param int $page Current page number.
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getNotesWhereMentioned(int $userId, int $perPage = 15, int $page = 1): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        return $this->model->whereHas('mentions', function ($query) use ($userId) {
            $query->where('mentioned_user_id', $userId);
        })->with('mentionedUsers')
            ->paginate($perPage, ['*'], 'page', $page);
    }

    /**
     * Get all mentions for a specific note.
     *
     * @param Note $note The note to get mentions for.
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getNoteMentions(Note $note): \Illuminate\Database\Eloquent\Collection
    {
        return $note->mentions()->with('mentionedUser')->get();
    }
}
