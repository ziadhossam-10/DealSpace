<?php

namespace App\Exports;

use App\Services\Reports\MarketingReportService;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Maatwebsite\Excel\Concerns\Exportable;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class MarketingReportExport implements WithMultipleSheets
{
    use Exportable;

    protected $filters;
    protected $marketingReportService;

    public function __construct($filters, MarketingReportService $marketingReportService)
    {
        $this->filters = $filters;
        $this->marketingReportService = $marketingReportService;
    }

    public function sheets(): array
    {
        return [
            'Campaign Summary' => new MarketingCampaignSummarySheet($this->filters, $this->marketingReportService),
            'Campaign Details' => new MarketingCampaignDetailsSheet($this->filters, $this->marketingReportService),
        ];
    }
}

class MarketingCampaignSummarySheet implements FromCollection, WithHeadings, WithMapping, WithStyles, ShouldAutoSize, WithTitle
{
    use Exportable;

    protected $filters;
    protected $marketingReportService;
    protected $reportData;

    public function __construct($filters, MarketingReportService $marketingReportService)
    {
        $this->filters = $filters;
        $this->marketingReportService = $marketingReportService;
        $this->reportData = $this->marketingReportService->getReportData($this->filters);
    }

    public function collection()
    {
        return collect($this->reportData['campaigns']);
    }

    public function headings(): array
    {
        return [
            'Platform',
            'Source',
            'Medium',
            'Campaign Name',
            'Source Count',
            'Total Leads',
            'Appointments Set',
            'People with Appointments',
            'Closed Deals',
            'Deal Value ($)',
            'Total Events',
            'Lead to Appointment Rate (%)',
            'Lead to Deal Rate (%)',
            'Average Deal Value ($)',
            'Cost Per Lead ($)',
            'Revenue Per Lead ($)',
            'ROI (%)',
            'Appointment Show Rate (%)',
            'Deal Close Rate from Appointments (%)'
        ];
    }

    public function map($campaign): array
    {
        return [
            $campaign['platform'],
            $campaign['source'],
            $campaign['medium'],
            $campaign['campaign'],
            $campaign['source_count'],
            $campaign['leads'],
            $campaign['appointments'],
            $campaign['people_with_appointments'],
            $campaign['closed_deals'],
            number_format($campaign['deal_value'], 2),
            $campaign['total_events'],
            $this->calculateLeadToAppointmentRate($campaign),
            $this->calculateLeadToDealRate($campaign),
            $this->calculateAverageDealValue($campaign),
            $this->calculateCostPerLead($campaign),
            $this->calculateRevenuePerLead($campaign),
            $this->calculateROI($campaign),
            $this->calculateAppointmentShowRate($campaign),
            $this->calculateDealCloseRateFromAppointments($campaign)
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true, 'size' => 12],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '4472C4']
                ],
                'font' => ['color' => ['rgb' => 'FFFFFF'], 'bold' => true]
            ],
            'J:J' => ['numberFormat' => ['formatCode' => '"$"#,##0.00']], // Deal Value
            'N:N' => ['numberFormat' => ['formatCode' => '"$"#,##0.00']], // Average Deal Value
            'O:O' => ['numberFormat' => ['formatCode' => '"$"#,##0.00']], // Cost Per Lead
            'P:P' => ['numberFormat' => ['formatCode' => '"$"#,##0.00']], // Revenue Per Lead
            'L:L' => ['numberFormat' => ['formatCode' => '0.00"%"']], // Lead to Appointment Rate
            'M:M' => ['numberFormat' => ['formatCode' => '0.00"%"']], // Lead to Deal Rate
            'Q:Q' => ['numberFormat' => ['formatCode' => '0.00"%"']], // ROI
            'R:R' => ['numberFormat' => ['formatCode' => '0.00"%"']], // Appointment Show Rate
            'S:S' => ['numberFormat' => ['formatCode' => '0.00"%"']], // Deal Close Rate from Appointments
        ];
    }

    public function title(): string
    {
        return 'Campaign Summary';
    }

    private function calculateLeadToAppointmentRate($campaign): float
    {
        return $campaign['leads'] > 0 ? round(($campaign['appointments'] / $campaign['leads']) * 100, 2) : 0;
    }

    private function calculateLeadToDealRate($campaign): float
    {
        return $campaign['leads'] > 0 ? round(($campaign['closed_deals'] / $campaign['leads']) * 100, 2) : 0;
    }

    private function calculateAverageDealValue($campaign): float
    {
        return $campaign['closed_deals'] > 0 ? round($campaign['deal_value'] / $campaign['closed_deals'], 2) : 0;
    }

    private function calculateCostPerLead($campaign): float
    {
        // This would need to be calculated based on campaign costs if available
        // For now, returning 0 as cost data isn't in the current structure
        return 0;
    }

    private function calculateRevenuePerLead($campaign): float
    {
        return $campaign['leads'] > 0 ? round($campaign['deal_value'] / $campaign['leads'], 2) : 0;
    }

    private function calculateROI($campaign): float
    {
        // ROI calculation would need cost data
        // For now, returning 0 as cost data isn't available
        return 0;
    }

    private function calculateAppointmentShowRate($campaign): float
    {
        return $campaign['appointments'] > 0 ? round(($campaign['people_with_appointments'] / $campaign['appointments']) * 100, 2) : 0;
    }

    private function calculateDealCloseRateFromAppointments($campaign): float
    {
        return $campaign['people_with_appointments'] > 0 ? round(($campaign['closed_deals'] / $campaign['people_with_appointments']) * 100, 2) : 0;
    }
}

class MarketingCampaignDetailsSheet implements FromCollection, WithHeadings, WithMapping, WithStyles, ShouldAutoSize, WithTitle
{
    use Exportable;

    protected $filters;
    protected $marketingReportService;
    protected $campaignDetails;

    public function __construct($filters, MarketingReportService $marketingReportService)
    {
        $this->filters = $filters;
        $this->marketingReportService = $marketingReportService;
        $this->campaignDetails = $this->getAllCampaignDetails();
    }

    public function collection()
    {
        return $this->campaignDetails;
    }

    public function headings(): array
    {
        return [
            'Campaign Source',
            'Campaign Medium',
            'Campaign Name',
            'Lead Name',
            'Lead Email',
            'Lead Phone',
            'Lead Created Date',
            'Lead Stage',
            'Appointments Count',
            'Latest Appointment Date',
            'Deals Count',
            'Total Deal Value ($)',
            'Latest Deal Date',
            'Days Since Lead Created',
            'Lead Status',
            'First Contact Date',
            'Last Activity Date',
            'Total Activities'
        ];
    }

    public function map($detail): array
    {
        return [
            $detail['source'],
            $detail['medium'],
            $detail['campaign'],
            $detail['lead_name'],
            $detail['lead_email'],
            $detail['lead_phone'],
            $detail['lead_created_date']->format('Y-m-d H:i:s'),
            $detail['lead_stage'],
            $detail['appointments_count'],
            $detail['latest_appointment_date'] ? $detail['latest_appointment_date']->format('Y-m-d H:i:s') : '',
            $detail['deals_count'],
            number_format($detail['total_deal_value'], 2),
            $detail['latest_deal_date'] ? $detail['latest_deal_date']->format('Y-m-d H:i:s') : '',
            $detail['days_since_created'],
            $detail['lead_status'],
            $detail['first_contact_date'] ? $detail['first_contact_date']->format('Y-m-d H:i:s') : '',
            $detail['last_activity_date'] ? $detail['last_activity_date']->format('Y-m-d H:i:s') : '',
            $detail['total_activities']
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true, 'size' => 12],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '70AD47']
                ],
                'font' => ['color' => ['rgb' => 'FFFFFF'], 'bold' => true]
            ],
            'L:L' => ['numberFormat' => ['formatCode' => '"$"#,##0.00']], // Total Deal Value
        ];
    }

    public function title(): string
    {
        return 'Campaign Details';
    }

    private function getAllCampaignDetails(): Collection
    {
        $reportData = $this->marketingReportService->getReportData($this->filters);
        $allDetails = collect();

        foreach ($reportData['campaigns'] as $campaign) {
            $details = $this->marketingReportService->getCampaignDetails(
                $campaign['source'],
                $campaign['medium'],
                $campaign['campaign'],
                $this->filters->dateFilter ?? 'last_30_days'
            );

            // Convert paginated results to collection and enhance with campaign info
            $campaignDetails = collect($details->items())->map(function ($detail) use ($campaign) {
                return [
                    'source' => $campaign['source'],
                    'medium' => $campaign['medium'],
                    'campaign' => $campaign['campaign'],
                    'lead_name' => $detail->name ?? '',
                    'lead_email' => $detail->email ?? '',
                    'lead_phone' => $detail->phone ?? '',
                    'lead_created_date' => Carbon::parse($detail->created_at),
                    'lead_stage' => $detail->stage->name ?? 'Unknown',
                    'appointments_count' => $detail->appointments_count ?? 0,
                    'latest_appointment_date' => $detail->latest_appointment_date ? Carbon::parse($detail->latest_appointment_date) : null,
                    'deals_count' => $detail->deals_count ?? 0,
                    'total_deal_value' => $detail->total_deal_value ?? 0,
                    'latest_deal_date' => $detail->latest_deal_date ? Carbon::parse($detail->latest_deal_date) : null,
                    'days_since_created' => Carbon::parse($detail->created_at)->diffInDays(Carbon::now()),
                    'lead_status' => $this->getLeadStatus($detail),
                    'first_contact_date' => $detail->first_contact_date ? Carbon::parse($detail->first_contact_date) : null,
                    'last_activity_date' => $detail->last_activity_date ? Carbon::parse($detail->last_activity_date) : null,
                    'total_activities' => $detail->total_activities ?? 0
                ];
            });

            $allDetails = $allDetails->merge($campaignDetails);
        }

        return $allDetails->sortByDesc('lead_created_date');
    }

    private function getLeadStatus($detail): string
    {
        if ($detail->deals_count > 0) {
            return 'Converted';
        }

        if ($detail->appointments_count > 0) {
            return 'Qualified';
        }

        if ($detail->total_activities > 0) {
            return 'Contacted';
        }

        return 'New';
    }
}
