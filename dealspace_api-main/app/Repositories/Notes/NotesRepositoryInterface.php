<?php

namespace App\Repositories\Notes;

use App\Models\Note;

interface NotesRepositoryInterface
{
    public function getAll(int $perPage = 15, int $page = 1, int $personId): \Illuminate\Contracts\Pagination\LengthAwarePaginator;

    public function findById(int $noteId): ?Note;

    public function create(array $data): Note;

    public function update(Note $note, array $data): Note;

    public function delete(Note $note): bool;

    public function assignMentions(Note $note, array $userIds): void;

    public function addMentions(Note $note, array $userIds): void;

    public function removeMentions(Note $note, array $userIds): void;

    public function getNotesWhereMentioned(int $userId, int $perPage = 15, int $page = 1): \Illuminate\Contracts\Pagination\LengthAwarePaginator;

    public function getNoteMentions(Note $note): \Illuminate\Database\Eloquent\Collection;
}
