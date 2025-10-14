<?php

namespace App\Services\Notes;

use App\Models\Note;

interface NoteServiceInterface
{
    /**
     * Get all notes for a person.
     *
     * @param int $perPage
     * @param int $page
     * @param int $personId
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getAll(int $perPage = 15, int $page = 1, int $personId);

    /**
     * Get a note by ID.
     *
     * @param int $noteId
     * @return Note
     */
    public function findById(int $noteId): Note;

    /**
     * Create a new note with mentions.
     *
     * @param array $data
     * @return Note
     */
    public function create(array $data): Note;

    /**
     * Update an existing note and its mentions.
     *
     * @param int $noteId
     * @param array $data
     * @return Note
     */
    public function update(int $noteId, array $data): Note;

    /**
     * Delete a note.
     *
     * @param int $noteId
     * @return bool
     */
    public function delete(int $noteId): bool;


    /**
     * Add a mention to a note.
     *
     * @param int $noteId
     * @param int $userId
     * @return void
     */
    public function addMentionToNote(int $noteId, int $userId): void;

    /**
     * Remove a mention from a note.
     *
     * @param int $noteId
     * @param int $userId
     * @return void
     */
    public function removeMentionFromNote(int $noteId, int $userId): void;

    /**
     * Assign mentions to a note (replace all existing mentions).
     *
     * @param int $noteId
     * @param array $userIds
     * @return void
     */
    public function assignMentionsToNote(int $noteId, array $userIds): void;

    /**
     * Get notes where a specific user is mentioned.
     *
     * @param int $userId
     * @param int $perPage
     * @param int $page
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getNotesWhereMentioned(int $userId, int $perPage = 15, int $page = 1);

    /**
     * Get all mentions for a specific note.
     *
     * @param int $noteId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getNoteMentions(int $noteId): \Illuminate\Database\Eloquent\Collection;
}
