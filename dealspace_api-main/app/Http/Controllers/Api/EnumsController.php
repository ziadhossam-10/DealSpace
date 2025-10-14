<?php

namespace App\Http\Controllers\Api;

use App\Enums\IndustryEnum;
use App\Enums\RoleEnum;
use App\Enums\UsageEnum;
use App\Http\Controllers\Controller;

class EnumsController extends Controller
{
    /**
     * Retrieves all the available industries.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getIndustries()
    {
        return successResponse('Industries retrieved successfully', IndustryEnum::options());
    }

    /**
     * Retrieves all the available roles.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getRoles() {
        return successResponse('Roles retrieved successfully', RoleEnum::options());
    }

    /**
     * Retrieves all the available usage capacities.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getUsageCapacities() {
        return successResponse('Usage Capacities retrieved successfully', UsageEnum::options());
    }
}
