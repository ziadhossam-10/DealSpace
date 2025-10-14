<?php

namespace App\Http\Controllers\Api\People;

use App\Http\Controllers\Controller;
use App\Http\Requests\People\StorePersonAddressRequest;
use App\Http\Requests\People\UpdatePersonAddressRequest;
use App\Services\People\AddressServiceInterface;
use Illuminate\Http\JsonResponse;

class AddressesController extends Controller
{
    protected $addressService;

    public function __construct(AddressServiceInterface $addressService)
    {
        $this->addressService = $addressService;
    }

    /**
     * Display a listing of addresses for the person.
     *
     * @param int $personId The ID of the person to get addresses for.
     * @return JsonResponse JSON response containing the list of addresses.
     */
    public function index(int $personId): JsonResponse
    {
        $addresses = $this->addressService->getAll($personId);

        return successResponse(
            'Addresses retrieved successfully',
            $addresses
        );
    }

    /**
     * Store a newly created address for the person.
     *
     * @param StorePersonAddressRequest $request The request instance containing the data to create.
     * @param int $personId The ID of the person to add the address to.
     * @return JsonResponse JSON response containing the added address and a 201 status code.
     */
    public function store(StorePersonAddressRequest $request, int $personId): JsonResponse
    {
        $address = $this->addressService->create($personId, $request->validated());

        return successResponse(
            'Address created successfully',
            $address,
            201
        );
    }

    /**
     * Display the specified address of the person.
     *
     * @param int $personId The ID of the person.
     * @param int $addressId The ID of the address to show.
     * @return JsonResponse JSON response containing the address.
     */
    public function show(int $personId, int $addressId): JsonResponse
    {
        $address = $this->addressService->findById($personId, $addressId);

        return successResponse(
            'Address retrieved successfully',
            $address
        );
    }

    /**
     * Update the specified address of the person.
     *
     * @param UpdatePersonAddressRequest $request The request instance containing the data to update.
     * @param int $personId The ID of the person.
     * @param int $addressId The ID of the address to update.
     * @return JsonResponse JSON response containing the updated address.
     */
    public function update(UpdatePersonAddressRequest $request, int $personId, int $addressId): JsonResponse
    {
        $address = $this->addressService->update($personId, $addressId, $request->validated());

        return successResponse(
            'Address updated successfully',
            $address
        );
    }

    /**
     * Remove the specified address from the person.
     *
     * @param int $personId The ID of the person.
     * @param int $addressId The ID of the address to delete.
     * @return JsonResponse JSON response indicating the result of the deletion.
     */
    public function destroy(int $personId, int $addressId): JsonResponse
    {
        $this->addressService->delete($personId, $addressId);

        return successResponse(
            'Address deleted successfully',
            null
        );
    }

    /**
     * Set the specified address as primary for the person.
     *
     * @param int $personId The ID of the person.
     * @param int $addressId The ID of the address to set as primary.
     * @return JsonResponse JSON response indicating the result of the operation.
     */
    public function setPrimary(int $personId, int $addressId): JsonResponse
    {
        $this->addressService->setPrimary($personId, $addressId);
        return successResponse(
            'Address set as primary successfully',
            null
        );
    }
}
