<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Notes\StoreNoteRequest;
use App\Http\Requests\Notes\UpdateNoteRequest;
use App\Http\Resources\NoteCollection;
use App\Http\Resources\NoteResource;
use App\Services\Notes\NoteServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotesController extends Controller
{
    protected $noteService;

    public function __construct(NoteServiceInterface $noteService)
    {
        $this->noteService = $noteService;
    }

    /**
     * Get all notes for a specific person.
     *
     * @param Request $request
     * @param int $personId The ID of the person whose notes to retrieve.
     * @return JsonResponse JSON response containing all notes.
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = $request->input('per_page', 15);
        $page = $request->input('page', 1);
        $personId = $request->input('person_id');

        $notes = $this->noteService->getAll($perPage, $page, $personId);

        return successResponse(
            'Notes retrieved successfully',
            new NoteCollection($notes)
        );
    }

    /**
     * Get a specific note by ID.
     *
     * @param int $id The ID of the note to retrieve.
     * @return JsonResponse JSON response containing the note.
     */
    public function show(int $id): JsonResponse
    {
        $note = $this->noteService->findById($id);

        return successResponse(
            'Note retrieved successfully',
            new NoteResource($note)
        );
    }

    /**
     * Create a new note.
     *
     * @param StoreNoteRequest $request The request instance containing the data to create a note.
     * @return JsonResponse JSON response containing the created note and a 201 status code.
     */
    public function store(StoreNoteRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['created_by'] = $request->user()->id;

        $note = $this->noteService->create($data);

        return successResponse(
            'Note created successfully',
            new NoteResource($note),
            201
        );
    }

    /**
     * Update an existing note.
     *
     * @param UpdateNoteRequest $request The request instance containing the data to update.
     * @param int $id The ID of the note to update.
     * @return JsonResponse JSON response containing the updated note.
     */
    public function update(UpdateNoteRequest $request, int $id): JsonResponse
    {
        $data = $request->validated();
        $data['updated_by'] = $request->user()->id;

        $note = $this->noteService->update($id, $data);

        return successResponse(
            'Note updated successfully',
            new NoteResource($note)
        );
    }

    /**
     * Delete a note.
     *
     * @param int $id The ID of the note to delete.
     * @return JsonResponse JSON response indicating the result of the deletion.
     */
    public function destroy(int $id): JsonResponse
    {
        $this->noteService->delete($id);

        return successResponse(
            'Note deleted successfully',
            null
        );
    }

    /**
     * Get notes where a specific user is mentioned.
     *
     * @param Request $request
     * @param int $userId The ID of the user to search for in mentions.
     * @return JsonResponse JSON response containing notes where the user is mentioned.
     */
    public function getMentionedNotes(Request $request, int $userId): JsonResponse
    {
        $perPage = $request->input('per_page', 15);
        $page = $request->input('page', 1);

        $notes = $this->noteService->getNotesWhereMentioned($userId, $perPage, $page);

        return successResponse(
            'Mentioned notes retrieved successfully',
            new NoteCollection($notes)
        );
    }
}
