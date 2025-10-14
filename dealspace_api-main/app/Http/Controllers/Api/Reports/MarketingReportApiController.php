<?php

namespace App\Http\Controllers\Api\Reports;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Services\Reports\MarketingReportService;
use App\Http\Resources\MarketingReportResource;
use App\Http\Requests\Reports\MarketingReportRequest;
use App\Http\Requests\Reports\CampaignDetailsRequest;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Excel;

class MarketingReportApiController extends Controller
{
    private MarketingReportService $marketingReportService;

    public function __construct(MarketingReportService $marketingReportService)
    {
        $this->marketingReportService = $marketingReportService;
    }

    /**
     * Get marketing report data
     *
     * @param MarketingReportRequest $request
     * @return JsonResponse
     */
    public function index(MarketingReportRequest $request): JsonResponse
    {
        try {
            $filters = $request->getFilters();
            $reportData = $this->marketingReportService->getReportData($filters);

            return response()->json([
                'success' => true,
                'data' => new MarketingReportResource($reportData),
            ]);
        } catch (\Exception $e) {
            Log::error('Marketing report generation failed', [
                'error' => $e->getMessage(),
                'filters' => $request->getFilters() ?? [],
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Unable to generate marketing report',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Get detailed campaign data
     *
     * @param CampaignDetailsRequest $request
     * @return JsonResponse
     */
    public function campaignDetails(CampaignDetailsRequest $request): JsonResponse
    {
        try {
            $details = $this->marketingReportService->getCampaignDetails(
                $request->validated('source'),
                $request->validated('medium'),
                $request->validated('campaign'),
                $request->validated('date_filter', 'last_30_days')
            );

            if (!$details) {
                return response()->json([
                    'success' => false,
                    'message' => 'Campaign details not found'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $details,
                'meta' => [
                    'generated_at' => now()->toISOString()
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Campaign details retrieval failed', [
                'error' => $e->getMessage(),
                'request_data' => $request->validated(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Unable to retrieve campaign details',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    public function export(MarketingReportRequest $request)
    {
        try {
            $filters = $request->getFilters();

            // Create the export
            $export = new \App\Exports\MarketingReportExport($filters, $this->marketingReportService);

            // Generate filename
            $fileName = 'marketing_report_' . Carbon::now()->format('Y_m_d_H_i_s') . '.xlsx';

            // Return as download
            return Excel::download($export, $fileName);
        } catch (\Exception $e) {
            Log::error('Marketing report export failed', [
                'error' => $e->getMessage(),
                'filters' => $request->getFilters() ?? [],
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Unable to export marketing report',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }
}
