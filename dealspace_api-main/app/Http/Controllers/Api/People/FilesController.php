<?php

namespace App\Http\Controllers\Api\People;

use App\Http\Controllers\Controller;
use App\Http\Requests\People\StorePersonFileRequest;
use App\Http\Requests\People\UpdatePersonFileRequest;
use App\Http\Resources\FileResource;
use App\Services\People\FileServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;

class FilesController extends Controller
{
    protected $fileService;

    public function __construct(FileServiceInterface $fileService)
    {
        $this->fileService = $fileService;
    }

    /**
     * Display a listing of files for the person.
     *
     * @param int $personId The ID of the person to get files for.
     * @return JsonResponse JSON response containing the list of files.
     */
    public function index(int $personId): JsonResponse
    {
        $files = $this->fileService->getAll($personId);

        return successResponse(
            'Files retrieved successfully',
            FileResource::collection($files)
        );
    }

    /**
     * Store a newly created file for the person.
     *
     * @param StorePersonFileRequest $request The request instance containing the data to create.
     * @param int $personId The ID of the person to add the file to.
     * @return JsonResponse JSON response containing the added file and a 201 status code.
     */
    public function store(StorePersonFileRequest $request, int $personId): JsonResponse
    {
        $validatedData = $request->validated();

        if ($request->hasFile('file')) {
            $uploadedFile = $request->file('file');

            // Store the file and get the path
            $filePath = $uploadedFile->store('person_files/' . $personId, 'public');

            // Extract file information
            $fileData = [
                'name' => $validatedData['name'] ?? $uploadedFile->getClientOriginalName(),
                'path' => $filePath,
                'size' => $uploadedFile->getSize(),
                'mime_type' => $uploadedFile->getMimeType(),
                'type' => $validatedData['type'] ?? $this->getFileTypeFromMime($uploadedFile->getMimeType()),
                'is_primary' => $validatedData['is_primary'] ?? false,
                'description' => $validatedData['description'] ?? null,
            ];
        } else {
            // If no file uploaded, use provided data (for cases where file is already stored)
            $fileData = $validatedData;
        }

        $file = $this->fileService->create($personId, $fileData);

        return successResponse(
            'File created successfully',
            new FileResource($file),
            201
        );
    }

    /**
     * Display the specified file of the person.
     *
     * @param int $personId The ID of the person.
     * @param int $fileId The ID of the file to show.
     * @return JsonResponse JSON response containing the file.
     */
    public function show(int $personId, int $fileId): JsonResponse
    {
        $file = $this->fileService->findById($personId, $fileId);

        return successResponse(
            'File retrieved successfully',
            new FileResource($file)
        );
    }

    /**
     * Update the specified file of the person.
     *
     * @param UpdatePersonFileRequest $request The request instance containing the data to update.
     * @param int $personId The ID of the person.
     * @param int $fileId The ID of the file to update.
     * @return JsonResponse JSON response containing the updated file.
     */
    public function update(UpdatePersonFileRequest $request, int $personId, int $fileId): JsonResponse
    {
        $validatedData = $request->validated();

        // Get the existing file to handle file replacement
        $existingFile = $this->fileService->findById($personId, $fileId);

        if ($request->hasFile('file')) {
            $uploadedFile = $request->file('file');

            // Delete the old file if it exists
            if ($existingFile->path && Storage::disk('public')->exists($existingFile->path)) {
                Storage::disk('public')->delete($existingFile->path);
            }

            // Store the new file
            $filePath = $uploadedFile->store('person_files/' . $personId, 'public');

            // Update file information
            $fileData = [
                'name' => $validatedData['name'] ?? $uploadedFile->getClientOriginalName(),
                'path' => $filePath,
                'size' => $uploadedFile->getSize(),
                'mime_type' => $uploadedFile->getMimeType(),
                'type' => $validatedData['type'] ?? $this->getFileTypeFromMime($uploadedFile->getMimeType()),
                'is_primary' => $validatedData['is_primary'] ?? $existingFile->is_primary,
                'description' => $validatedData['description'] ?? $existingFile->description,
            ];
        } else {
            // If no new file uploaded, just update the metadata
            $fileData = array_filter($validatedData, function ($value) {
                return $value !== null;
            });
        }

        $file = $this->fileService->update($personId, $fileId, $fileData);

        return successResponse(
            'File updated successfully',
            new FileResource($file)
        );
    }

    /**
     * Remove the specified file from the person.
     *
     * @param int $personId The ID of the person.
     * @param int $fileId The ID of the file to delete.
     * @return JsonResponse JSON response indicating the result of the deletion.
     */
    public function destroy(int $personId, int $fileId): JsonResponse
    {
        // Get the file to access its path before deletion
        $file = $this->fileService->findById($personId, $fileId);

        // Delete the file from storage
        if ($file->path && Storage::disk('public')->exists($file->path)) {
            Storage::disk('public')->delete($file->path);
        }

        $this->fileService->delete($personId, $fileId);

        return successResponse(
            'File deleted successfully',
            null
        );
    }

    /**
     * Get file type from MIME type.
     *
     * @param string $mimeType
     * @return string
     */
    private function getFileTypeFromMime(string $mimeType): string
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
