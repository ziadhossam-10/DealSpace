<?php

namespace App\Services\Reports;

use App\Repositories\Reports\MarketingRepository;
use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;

class MarketingReportService
{
    private MarketingRepository $repository;

    public function __construct(MarketingRepository $repository)
    {
        $this->repository = $repository;
    }

    public function getReportData($filters): array
    {
        [$startDate, $endDate] = $this->getDateRange($filters->dateFilter);
        $campaignData = $this->repository->getCampaignMetrics($startDate, $endDate, $filters->campaignFilter);

        $enhancedData = $campaignData->map(function ($campaign) use ($startDate, $endDate) {
            return $this->enhanceCampaignData($campaign, $startDate, $endDate);
        })->sortByDesc('leads');

        return [
            'campaigns' => $enhancedData,
            'totals' => $this->calculateTotals($enhancedData),
            'dateRange' => [
                'start' => $startDate,
                'end' => $endDate
            ],
            'filters' => $filters
        ];
    }

    public function getCampaignDetails(string $source, string $medium, string $campaign, string $dateFilter): LengthAwarePaginator
    {
        [$startDate, $endDate] = $this->getDateRange($dateFilter);
        return $this->repository->getCampaignEvents($source, $medium, $campaign, $startDate, $endDate);
    }

    private function enhanceCampaignData($campaign, Carbon $startDate, Carbon $endDate): array
    {
        $source = trim($campaign->source, '"');
        $medium = trim($campaign->medium, '"');
        $campaignName = trim($campaign->campaign_name, '"');

        $personIds = $this->repository->getPersonIdsForCampaign($source, $medium, $campaignName, $startDate, $endDate);

        return [
            'platform' => $this->formatPlatformName($source, $medium),
            'source' => $source,
            'medium' => $medium,
            'campaign' => $campaignName,
            'source_count' => $this->repository->getSourceCount($source, $startDate, $endDate),
            'leads' => $campaign->leads,
            'appointments' => $this->repository->getAppointmentsCount($personIds, $startDate, $endDate),
            'people_with_appointments' => $this->repository->getPeopleWithAppointmentsCount($personIds, $startDate, $endDate),
            'closed_deals' => $this->repository->getClosedDealsCount($personIds, $startDate, $endDate),
            'deal_value' => $this->repository->getClosedDealsValue($personIds, $startDate, $endDate),
            'total_events' => $campaign->total_events
        ];
    }

    private function formatPlatformName(string $source, string $medium): string
    {
        $source = strtolower($source);
        $medium = strtolower($medium);

        $platformMappings = [
            'google' => 'Google',
            'facebook' => 'Facebook Ads',
            'bing' => 'Bing',
            'adwords' => 'AdWords',
            'instagram' => 'Instagram',
            'linkedin' => 'LinkedIn',
            'youtube' => 'YouTube',
            'twitter' => 'Twitter',
        ];

        if (isset($platformMappings[$source])) {
            return $platformMappings[$source];
        }

        if (in_array($medium, ['cpc', 'paid'])) {
            return ucfirst($source) . ' Ads';
        }

        if ($medium === 'social') {
            return ucfirst($source);
        }

        return ucfirst($source);
    }

    private function getDateRange(string $filter): array
    {
        $now = Carbon::now();

        return match ($filter) {
            'today' => [
                $now->copy()->startOfDay(),
                $now->copy()->endOfDay()
            ],
            'yesterday' => [
                $now->copy()->subDay()->startOfDay(),
                $now->copy()->subDay()->endOfDay()
            ],
            'last_7_days' => [
                $now->copy()->subDays(7)->startOfDay(),
                $now->copy()->endOfDay()
            ],
            'last_14_days' => [
                $now->copy()->subDays(14)->startOfDay(),
                $now->copy()->endOfDay()
            ],
            'this_month' => [
                $now->copy()->startOfMonth(),
                $now->copy()->endOfMonth()
            ],
            'last_month' => [
                $now->copy()->subMonth()->startOfMonth(),
                $now->copy()->subMonth()->endOfMonth()
            ],
            'this_year' => [
                $now->copy()->startOfYear(),
                $now->copy()->endOfYear()
            ],
            default => [
                $now->copy()->subDays(30)->startOfDay(),
                $now->copy()->endOfDay()
            ]
        };
    }

    private function calculateTotals($campaigns): array
    {
        return [
            'total_leads' => $campaigns->sum('leads'),
            'total_appointments' => $campaigns->sum('appointments'),
            'total_people_with_appointments' => $campaigns->sum('people_with_appointments'),
            'total_closed_deals' => $campaigns->sum('closed_deals'),
            'total_deal_value' => $campaigns->sum('deal_value')
        ];
    }

    public function exportReport($filters): string
    {
        $fileName = 'marketing_report_' . Carbon::now()->format('Y_m_d_H_i_s') . '.xlsx';
        $filePath = 'exports/' . $fileName;

        // Create the export
        $export = new \App\Exports\MarketingReportExport($filters, $this);

        // Store the file
        \Maatwebsite\Excel\Facades\Excel::store($export, $filePath, 'public');

        // Return the public URL
        return \Illuminate\Support\Facades\Storage::url($filePath);
    }
}
