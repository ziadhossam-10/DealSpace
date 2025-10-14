<?php

namespace App\Exports;

use App\Http\Controllers\Api\Reports\DealsReportController;
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
use App\Models\Deal;

class DealsReportExport implements WithMultipleSheets
{
    use Exportable;

    protected $params;
    protected $dealsReportController;

    public function __construct($params)
    {
        $this->params = $params;
        $this->dealsReportController = new DealsReportController();
    }

    public function sheets(): array
    {
        return [
            'Deals Summary' => new DealsSummarySheet($this->params, $this->dealsReportController),
            'Deals Details' => new DealsDetailsSheet($this->params, $this->dealsReportController),
            'Agent Performance' => new DealsAgentPerformanceSheet($this->params, $this->dealsReportController),
            'Pipeline Metrics' => new DealsPipelineMetricsSheet($this->params, $this->dealsReportController),
            'Source Performance' => new DealsSourcePerformanceSheet($this->params, $this->dealsReportController),
        ];
    }
}

class DealsSummarySheet implements FromCollection, WithHeadings, WithMapping, WithStyles, ShouldAutoSize, WithTitle
{
    use Exportable;

    protected $params;
    protected $dealsReportController;
    protected $reportData;

    public function __construct($params, DealsReportController $dealsReportController)
    {
        $this->params = $params;
        $this->dealsReportController = $dealsReportController;

        // Create a mock request with the parameters
        $request = new \Illuminate\Http\Request($this->params);
        $response = $this->dealsReportController->index($request);
        $this->reportData = json_decode($response->getContent(), true);
    }

    public function collection()
    {
        $summary = $this->reportData['summary'];

        return collect([
            [
                'metric' => 'Total Deals',
                'value' => $summary['total_deals'],
                'type' => 'count'
            ],
            [
                'metric' => 'Total Deal Value',
                'value' => $summary['total_deal_value'],
                'type' => 'currency'
            ],
            [
                'metric' => 'Deals Created YTD',
                'value' => $summary['deals_created_ytd'],
                'type' => 'count'
            ],
            [
                'metric' => 'Deals Created MTD',
                'value' => $summary['deals_created_mtd'],
                'type' => 'count'
            ],
            [
                'metric' => 'Deals Closed Period',
                'value' => $summary['deals_closed_period'],
                'type' => 'count'
            ],
            [
                'metric' => 'Deals Closed YTD',
                'value' => $summary['deals_closed_ytd'],
                'type' => 'count'
            ],
            [
                'metric' => 'Deals Closed MTD',
                'value' => $summary['deals_closed_mtd'],
                'type' => 'count'
            ],
            [
                'metric' => 'Pending Closings',
                'value' => $summary['pending_closings'],
                'type' => 'count'
            ],
            [
                'metric' => 'Total Commission',
                'value' => $summary['total_commission'],
                'type' => 'currency'
            ],
            [
                'metric' => 'Total Agent Commission',
                'value' => $summary['total_agent_commission'],
                'type' => 'currency'
            ],
            [
                'metric' => 'Total Team Commission',
                'value' => $summary['total_team_commission'],
                'type' => 'currency'
            ],
            [
                'metric' => 'Average Deal Value',
                'value' => $summary['avg_deal_value'],
                'type' => 'currency'
            ],
            [
                'metric' => 'Closed Deal Value Period',
                'value' => $summary['closed_deal_value_period'],
                'type' => 'currency'
            ],
            [
                'metric' => 'Closed Deal Value YTD',
                'value' => $summary['closed_deal_value_ytd'],
                'type' => 'currency'
            ],
            [
                'metric' => 'Closed Deal Value MTD',
                'value' => $summary['closed_deal_value_mtd'],
                'type' => 'currency'
            ],
            [
                'metric' => 'Pending Deal Value',
                'value' => $summary['pending_deal_value'],
                'type' => 'currency'
            ],
            [
                'metric' => 'Average Time to Close (Days)',
                'value' => $summary['avg_time_to_close'],
                'type' => 'number'
            ],
            [
                'metric' => 'Conversion Rate (%)',
                'value' => $summary['conversion_rate'],
                'type' => 'percentage'
            ],
            [
                'metric' => 'Win Rate (%)',
                'value' => $summary['win_rate'],
                'type' => 'percentage'
            ],
            [
                'metric' => 'Active Deals',
                'value' => $summary['active_deals'],
                'type' => 'count'
            ]
        ]);
    }

    public function headings(): array
    {
        return [
            'Metric',
            'Value',
            'Period: ' . $this->params['start_date'] . ' to ' . $this->params['end_date']
        ];
    }

    public function map($row): array
    {
        $value = $row['value'];

        switch ($row['type']) {
            case 'currency':
                $formattedValue = '$' . number_format($value, 2);
                break;
            case 'percentage':
                $formattedValue = number_format($value, 2) . '%';
                break;
            case 'number':
                $formattedValue = number_format($value, 2);
                break;
            default:
                $formattedValue = number_format($value);
        }

        return [
            $row['metric'],
            $formattedValue,
            '' // Empty third column for spacing
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
            'A:A' => ['font' => ['bold' => true]],
        ];
    }

    public function title(): string
    {
        return 'Deals Summary';
    }
}

class DealsDetailsSheet implements FromCollection, WithHeadings, WithMapping, WithStyles, ShouldAutoSize, WithTitle
{
    use Exportable;

    protected $params;
    protected $dealsReportController;
    protected $dealsData;

    public function __construct($params, DealsReportController $dealsReportController)
    {
        $this->params = $params;
        $this->dealsReportController = $dealsReportController;

        // Create a mock request with the parameters
        $request = new \Illuminate\Http\Request($this->params);
        $response = $this->dealsReportController->index($request);
        $reportData = json_decode($response->getContent(), true);
        $this->dealsData = collect($reportData['deals']);
    }

    public function collection()
    {
        return $this->dealsData;
    }

    public function headings(): array
    {
        return [
            'Deal ID',
            'Deal Name',
            'Stage',
            'Pipeline',
            'Type',
            'Status',
            'Entered Stage',
            'Time in Stage (Days)',
            'Close Date',
            'Time to Close (Days)',
            'Created Date',
            'Price ($)',
            'Commission ($)',
            'Agent Commission ($)',
            'Team Commission ($)',
            'People Count',
            'People Names',
            'Team Members Count',
            'Team Member Names',
            'Description'
        ];
    }

    public function map($deal): array
    {
        $peopleNames = collect($deal['people'])->pluck('name')->implode(', ');
        $teamNames = collect($deal['team'])->pluck('name')->implode(', ');

        return [
            $deal['id'],
            $deal['name'],
            $deal['stage']['name'] ?? '',
            $deal['stage']['pipeline'] ?? '',
            $deal['type'] ?? '',
            $deal['status'],
            $deal['entered_stage'] ?? '',
            $deal['time_in_stage'] ?? '',
            $deal['close_date'] ?? '',
            $deal['time_to_close'] ?? '',
            $deal['created_at'],
            $deal['price'] ?? 0,
            $deal['commission'] ?? 0,
            $deal['agent_commission'] ?? 0,
            $deal['team_commission'] ?? 0,
            count($deal['people']),
            $peopleNames,
            count($deal['team']),
            $teamNames,
            $deal['description'] ?? ''
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
            'L:O' => ['numberFormat' => ['formatCode' => '"$"#,##0.00']], // Price and Commission columns
        ];
    }

    public function title(): string
    {
        return 'Deals Details';
    }
}

class DealsAgentPerformanceSheet implements FromCollection, WithHeadings, WithMapping, WithStyles, ShouldAutoSize, WithTitle
{
    use Exportable;

    protected $params;
    protected $dealsReportController;
    protected $agentData;

    public function __construct($params, DealsReportController $dealsReportController)
    {
        $this->params = $params;
        $this->dealsReportController = $dealsReportController;

        // Create a mock request with the parameters
        $request = new \Illuminate\Http\Request($this->params);
        $response = $this->dealsReportController->index($request);
        $reportData = json_decode($response->getContent(), true);
        $this->agentData = collect($reportData['agents_performance']);
    }

    public function collection()
    {
        return $this->agentData;
    }

    public function headings(): array
    {
        return [
            'Agent ID',
            'Agent Name',
            'Email',
            'Total Deals',
            'Deals Created',
            'Deals Closed',
            'Active Deals',
            'Pending Closings',
            'Total Deal Value ($)',
            'Closed Deal Value ($)',
            'Average Deal Value ($)',
            'Total Commission ($)',
            'Agent Commission ($)',
            'Conversion Rate (%)',
            'Win Rate (%)',
            'Average Time to Close (Days)',
            'Average Deal Cycle (Days)'
        ];
    }

    public function map($agent): array
    {
        return [
            $agent['agent_id'],
            $agent['agent_name'],
            $agent['email'],
            $agent['total_deals'],
            $agent['deals_created'],
            $agent['deals_closed'],
            $agent['active_deals'],
            $agent['pending_closings'],
            $agent['total_deal_value'],
            $agent['closed_deal_value'],
            $agent['avg_deal_value'],
            $agent['total_commission'],
            $agent['agent_commission'],
            $agent['conversion_rate'],
            $agent['win_rate'],
            $agent['avg_time_to_close'],
            $agent['avg_deal_cycle']
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true, 'size' => 12],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'FFC000']
                ],
                'font' => ['color' => ['rgb' => 'FFFFFF'], 'bold' => true]
            ],
            'I:M' => ['numberFormat' => ['formatCode' => '"$"#,##0.00']], // Deal Value and Commission columns
            'N:O' => ['numberFormat' => ['formatCode' => '0.00"%"']], // Percentage columns
        ];
    }

    public function title(): string
    {
        return 'Agent Performance';
    }
}

class DealsPipelineMetricsSheet implements FromCollection, WithHeadings, WithMapping, WithStyles, ShouldAutoSize, WithTitle
{
    use Exportable;

    protected $params;
    protected $dealsReportController;
    protected $pipelineData;

    public function __construct($params, DealsReportController $dealsReportController)
    {
        $this->params = $params;
        $this->dealsReportController = $dealsReportController;

        // Create a mock request with the parameters
        $request = new \Illuminate\Http\Request($this->params);
        $response = $this->dealsReportController->index($request);
        $reportData = json_decode($response->getContent(), true);
        $this->pipelineData = $this->flattenPipelineData($reportData['pipeline_metrics']);
    }

    public function collection()
    {
        return $this->pipelineData;
    }

    public function headings(): array
    {
        return [
            'Pipeline ID',
            'Pipeline Name',
            'Stage ID',
            'Stage Name',
            'Deals Count',
            'Total Value ($)',
            'Average Time in Stage (Days)',
            'Conversion Rate (%)'
        ];
    }

    public function map($stage): array
    {
        return [
            $stage['pipeline_id'],
            $stage['pipeline_name'],
            $stage['stage_id'] ?? '',
            $stage['stage_name'] ?? $stage['pipeline_name'], // Use pipeline name for summary rows
            $stage['deals_count'],
            $stage['total_value'],
            $stage['avg_time_in_stage'] ?? '',
            $stage['conversion_rate'] ?? ''
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true, 'size' => 12],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'E74C3C']
                ],
                'font' => ['color' => ['rgb' => 'FFFFFF'], 'bold' => true]
            ],
            'F:F' => ['numberFormat' => ['formatCode' => '"$"#,##0.00']], // Total Value column
            'H:H' => ['numberFormat' => ['formatCode' => '0.00"%"']], // Conversion Rate column
        ];
    }

    public function title(): string
    {
        return 'Pipeline Metrics';
    }

    private function flattenPipelineData($pipelineMetrics): Collection
    {
        $flattened = collect();

        foreach ($pipelineMetrics as $pipeline) {
            // Add pipeline summary row
            $flattened->push([
                'pipeline_id' => $pipeline['pipeline_id'],
                'pipeline_name' => $pipeline['pipeline_name'] . ' (TOTAL)',
                'stage_id' => '',
                'stage_name' => '',
                'deals_count' => $pipeline['total_deals'],
                'total_value' => $pipeline['total_value'],
                'avg_time_in_stage' => '',
                'conversion_rate' => ''
            ]);

            // Add individual stage rows
            foreach ($pipeline['stages'] as $stage) {
                $flattened->push([
                    'pipeline_id' => $pipeline['pipeline_id'],
                    'pipeline_name' => $pipeline['pipeline_name'],
                    'stage_id' => $stage['stage_id'],
                    'stage_name' => $stage['stage_name'],
                    'deals_count' => $stage['deals_count'],
                    'total_value' => $stage['total_value'],
                    'avg_time_in_stage' => $stage['avg_time_in_stage'],
                    'conversion_rate' => $stage['conversion_rate']
                ]);
            }
        }

        return $flattened;
    }
}

class DealsSourcePerformanceSheet implements FromCollection, WithHeadings, WithMapping, WithStyles, ShouldAutoSize, WithTitle
{
    use Exportable;

    protected $params;
    protected $dealsReportController;
    protected $sourceData;

    public function __construct($params, DealsReportController $dealsReportController)
    {
        $this->params = $params;
        $this->dealsReportController = $dealsReportController;

        // Create a mock request with the parameters
        $request = new \Illuminate\Http\Request($this->params);
        $response = $this->dealsReportController->index($request);
        $reportData = json_decode($response->getContent(), true);
        $this->sourceData = collect($reportData['source_performance']);
    }

    public function collection()
    {
        return $this->sourceData;
    }

    public function headings(): array
    {
        return [
            'Source',
            'Source Type',
            'Total Deals',
            'Total Value ($)',
            'Closed Deals',
            'Closed Value ($)',
            'Conversion Rate (%)',
            'Average Deal Value ($)',
            'Performance Rating'
        ];
    }

    public function map($source): array
    {
        return [
            $source['source'],
            $source['type'],
            $source['deals_count'],
            $source['total_value'],
            $source['closed_deals'],
            $source['closed_value'],
            $source['conversion_rate'],
            $source['avg_deal_value'],
            $this->getPerformanceRating($source)
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true, 'size' => 12],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '9B59B6']
                ],
                'font' => ['color' => ['rgb' => 'FFFFFF'], 'bold' => true]
            ],
            'D:F' => ['numberFormat' => ['formatCode' => '"$"#,##0.00']], // Value columns
            'G:G' => ['numberFormat' => ['formatCode' => '0.00"%"']], // Conversion Rate column
            'H:H' => ['numberFormat' => ['formatCode' => '"$"#,##0.00']], // Average Deal Value column
        ];
    }

    public function title(): string
    {
        return 'Source Performance';
    }

    private function getPerformanceRating($source): string
    {
        $conversionRate = $source['conversion_rate'];
        $avgDealValue = $source['avg_deal_value'];

        if ($conversionRate >= 20 && $avgDealValue >= 50000) {
            return 'Excellent';
        } elseif ($conversionRate >= 15 && $avgDealValue >= 30000) {
            return 'Good';
        } elseif ($conversionRate >= 10 && $avgDealValue >= 15000) {
            return 'Average';
        } elseif ($conversionRate >= 5 && $avgDealValue >= 5000) {
            return 'Below Average';
        } else {
            return 'Poor';
        }
    }
}
