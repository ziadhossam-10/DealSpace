<?php

namespace App\Http\Controllers\Api\People;

use App\Http\Controllers\Controller;
use App\Http\Requests\People\StorePersonPhoneRequest;
use App\Http\Requests\People\UpdatePersonPhoneRequest;
use App\Services\People\PhoneServiceInterface;
use Illuminate\Http\JsonResponse;

class PhonesController extends Controller
{
    protected $phoneService;

    public function __construct(PhoneServiceInterface $phoneService)
    {
        $this->phoneService = $phoneService;
    }

    /**
     * Display a listing of phones for the person.
     *
     * @param int $personId The ID of the person to get phones for.
     * @return JsonResponse JSON response containing the list of phones.
     */
    public function index(int $personId): JsonResponse
    {
        $phones = $this->phoneService->getAll($personId);

        return successResponse(
            'Phones retrieved successfully',
            $phones
        );
    }

    /**
     * Store a newly created phone for the person.
     *
     * @param StorePersonPhoneRequest $request The request instance containing the data to create.
     * @param int $personId The ID of the person to add the phone to.
     * @return JsonResponse JSON response containing the added phone and a 201 status code.
     */
    public function store(StorePersonPhoneRequest $request, int $personId): JsonResponse
    {
        $phone = $this->phoneService->create($personId, $request->validated());

        return successResponse(
            'Phone created successfully',
            $phone,
            201
        );
    }

    /**
     * Display the specified phone of the person.
     *
     * @param int $personId The ID of the person.
     * @param int $phoneId The ID of the phone to show.
     * @return JsonResponse JSON response containing the phone.
     */
    public function show(int $personId, int $phoneId): JsonResponse
    {
        $phone = $this->phoneService->findById($personId, $phoneId);

        return successResponse(
            'Phone retrieved successfully',
            $phone
        );
    }

    /**
     * Update the specified phone of the person.
     *
     * @param UpdatePersonPhoneRequest $request The request instance containing the data to update.
     * @param int $personId The ID of the person.
     * @param int $phoneId The ID of the phone to update.
     * @return JsonResponse JSON response containing the updated phone.
     */
    public function update(UpdatePersonPhoneRequest $request, int $personId, int $phoneId): JsonResponse
    {
        $phone = $this->phoneService->update($personId, $phoneId, $request->validated());

        return successResponse(
            'Phone updated successfully',
            $phone
        );
    }

    /**
     * Remove the specified phone from the person.
     *
     * @param int $personId The ID of the person.
     * @param int $phoneId The ID of the phone to delete.
     * @return JsonResponse JSON response indicating the result of the deletion.
     */
    public function destroy(int $personId, int $phoneId): JsonResponse
    {
        $this->phoneService->delete($personId, $phoneId);

        return successResponse(
            'Phone deleted successfully',
            null
        );
    }

    /**
     * Set the specified phone as primary for the person.
     *
     * @param int $personId The ID of the person.
     * @param int $phoneId The ID of the phone to set as primary.
     * @return JsonResponse JSON response indicating the result of the operation.
     */
    public function setPrimary(int $personId, int $phoneId): JsonResponse
    {
        $this->phoneService->setPrimary($personId, $phoneId);
        return successResponse(
            'Phone set as primary successfully',
            null
        );
    }
}
