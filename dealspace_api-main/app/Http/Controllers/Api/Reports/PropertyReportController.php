<?php

namespace App\Http\Controllers\Api\Reports;

use App\Http\Controllers\Controller;
use App\Services\Reports\PropertyReportServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PropertyReportController extends Controller
{
    protected $propertyReportService;

    public function __construct(PropertyReportServiceInterface $propertyReportService)
    {
        $this->propertyReportService = $propertyReportService;
    }

    /**
     * Get property report with events and leads grouped by MLS number.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getPropertyReport(Request $request): JsonResponse
    {
        // Build filters array from request
        $filters = [];
        if ($request->has('date_from')) {
            $filters['date_from'] = $request->input('date_from');
        }
        if ($request->has('date_to')) {
            $filters['date_to'] = $request->input('date_to');
        }
        if ($request->has('event_types')) {
            $filters['event_types'] = $request->input('event_types');
        }
        if ($request->has('city')) {
            $filters['city'] = $request->input('city');
        }
        if ($request->has('state')) {
            $filters['state'] = $request->input('state');
        }
        if ($request->has('zip_code')) {
            $filters['zip_code'] = $request->input('zip_code');
        }

        $report = $this->propertyReportService->getPropertyReport($filters);

        return successResponse(
            'Property report retrieved successfully',
            $report
        );
    }

    /**
     * Get inquiries grouped by zip code.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getInquiriesByZipCode(Request $request): JsonResponse
    {
        $filters = [];
        if ($request->has('date_from')) {
            $filters['date_from'] = $request->input('date_from');
        }
        if ($request->has('date_to')) {
            $filters['date_to'] = $request->input('date_to');
        }
        if ($request->has('event_types')) {
            $filters['event_types'] = $request->input('event_types');
        }

        $report = $this->propertyReportService->getInquiriesByZipCode($filters);

        return successResponse(
            'Inquiries by zip code retrieved successfully',
            $report
        );
    }

    /**
     * Get inquiries grouped by property (MLS number).
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getInquiriesByProperty(Request $request): JsonResponse
    {
        $perPage = $request->input('per_page', 15);
        $page = $request->input('page', 1);

        $filters = [];
        if ($request->has('date_from')) {
            $filters['date_from'] = $request->input('date_from');
        }
        if ($request->has('date_to')) {
            $filters['date_to'] = $request->input('date_to');
        }
        if ($request->has('event_types')) {
            $filters['event_types'] = $request->input('event_types');
        }
        if ($request->has('city')) {
            $filters['city'] = $request->input('city');
        }
        if ($request->has('state')) {
            $filters['state'] = $request->input('state');
        }

        $report = $this->propertyReportService->getInquiriesByProperty($perPage, $page, $filters);

        return successResponse(
            'Inquiries by property retrieved successfully',
            $report
        );
    }

    /**
     * Get detailed report for a specific property by MLS number.
     *
     * @param Request $request
     * @param string $mlsNumber
     * @return JsonResponse
     */
    public function getPropertyDetailReport(Request $request, string $mlsNumber): JsonResponse
    {
        $perPage = $request->input('per_page', 15);
        $page = $request->input('page', 1);

        $filters = [];
        if ($request->has('date_from')) {
            $filters['date_from'] = $request->input('date_from');
        }
        if ($request->has('date_to')) {
            $filters['date_to'] = $request->input('date_to');
        }
        if ($request->has('event_types')) {
            $filters['event_types'] = $request->input('event_types');
        }

        $report = $this->propertyReportService->getPropertyDetailReport($mlsNumber, $perPage, $page, $filters);

        return successResponse(
            'Property detail report retrieved successfully',
            $report
        );
    }

    /**
     * Get summary statistics for property reports.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getPropertyReportSummary(Request $request): JsonResponse
    {
        $filters = [];
        if ($request->has('date_from')) {
            $filters['date_from'] = $request->input('date_from');
        }
        if ($request->has('date_to')) {
            $filters['date_to'] = $request->input('date_to');
        }
        if ($request->has('event_types')) {
            $filters['event_types'] = $request->input('event_types');
        }

        $summary = $this->propertyReportService->getPropertyReportSummary($filters);

        return successResponse(
            'Property report summary retrieved successfully',
            $summary
        );
    }

    /**
     * Get top performing properties by inquiry count.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getTopPerformingProperties(Request $request): JsonResponse
    {
        $limit = $request->input('limit', 10);

        $filters = [];
        if ($request->has('date_from')) {
            $filters['date_from'] = $request->input('date_from');
        }
        if ($request->has('date_to')) {
            $filters['date_to'] = $request->input('date_to');
        }
        if ($request->has('event_types')) {
            $filters['event_types'] = $request->input('event_types');
        }

        $properties = $this->propertyReportService->getTopPerformingProperties($limit, $filters);

        return successResponse(
            'Top performing properties retrieved successfully',
            $properties
        );
    }

    /**
     * Get leads/persons associated with events for a specific property.
     *
     * @param Request $request
     * @param string $mlsNumber
     * @return JsonResponse
     */
    public function getPropertyLeads(Request $request, string $mlsNumber): JsonResponse
    {
        $perPage = $request->input('per_page', 15);
        $page = $request->input('page', 1);

        $filters = [];
        if ($request->has('date_from')) {
            $filters['date_from'] = $request->input('date_from');
        }
        if ($request->has('date_to')) {
            $filters['date_to'] = $request->input('date_to');
        }
        if ($request->has('event_types')) {
            $filters['event_types'] = $request->input('event_types');
        }

        $leads = $this->propertyReportService->getPropertyLeads($mlsNumber, $perPage, $page, $filters);

        return successResponse(
            'Property leads retrieved successfully',
            $leads
        );
    }
}
