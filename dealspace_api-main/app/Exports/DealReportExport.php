<?php

namespace App\Exports;

use App\Models\Deal;
use App\Models\User;
use App\Enums\RoleEnum;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\Exportable;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Carbon\Carbon;

class DealReportExport implements FromCollection, WithHeadings, WithMapping, WithStyles, ShouldAutoSize
{
    use Exportable;

    protected $startDate;
    protected $endDate;
    protected $agentIds;
    protected $stageId;
    protected $typeId;
    protected $status;

    public function __construct(array $params = [])
    {
        $this->startDate = Carbon::parse($params['start_date'] ?? now()->startOfMonth());
        $this->endDate = Carbon::parse($params['end_date'] ?? now()->endOfMonth());
        $this->agentIds = $params['agent_ids'] ?? [];
        $this->stageId = $params['stage_id'] ?? null;
        $this->typeId = $params['type_id'] ?? null;
        $this->status = $params['status'] ?? 'all';
    }

    public function collection()
    {
        $query = User::where('role', RoleEnum::AGENT);

        if (!empty($this->agentIds)) {
            $query->whereIn('id', $this->agentIds);
        }

        return $query->get();
    }

    public function headings(): array
    {
        return [
            'Agent Name',
            'Email',
            'Deals Created',
            'Deals Closed Won',
            'Deals Closed Lost',
            'Deals in Pipeline',
            'Total Deal Value',
            'Closed Deal Value',
            'Pipeline Value',
            'Average Deal Size',
            'Total Commission',
            'Agent Commission',
            'Team Commission',
            'Close Rate (%)',
            'Win Rate (%)',
            'Avg Time to Close (Days)',
            'Avg Time in Current Stage (Days)',
            'Unique Contacts',
            'Contacts Reached',
            'Avg Deals per Day',
            'Avg Value per Day',
        ];
    }

    public function map($agent): array
    {
        return [
            $agent->name,
            $agent->email,
            $this->getDealsCreated($agent->id),
            $this->getDealsClosedWon($agent->id),
            $this->getDealsClosedLost($agent->id),
            $this->getDealsInPipeline($agent->id),
            $this->getTotalDealValue($agent->id),
            $this->getClosedDealValue($agent->id),
            $this->getPipelineValue($agent->id),
            $this->getAverageDealSize($agent->id),
            $this->getTotalCommission($agent->id),
            $this->getAgentCommission($agent->id),
            $this->getTeamCommission($agent->id),
            $this->getCloseRate($agent->id),
            $this->getWinRate($agent->id),
            $this->getAverageTimeToClose($agent->id),
            $this->getAverageTimeInCurrentStage($agent->id),
            $this->getUniqueContacts($agent->id),
            $this->getContactsReached($agent->id),
            $this->getAverageDealsPerDay($agent->id),
            $this->getAverageValuePerDay($agent->id),
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'E0E0E0'],
                ],
            ],
        ];
    }

    // Core Deal Metrics
    private function getDealsCreated($agentId)
    {
        $query = Deal::whereHas('users', function ($query) use ($agentId) {
            $query->where('users.id', $agentId);
        })->whereBetween('created_at', [$this->startDate, $this->endDate]);

        if ($this->stageId) {
            $query->where('stage_id', $this->stageId);
        }

        if ($this->typeId) {
            $query->where('type_id', $this->typeId);
        }

        return $query->count();
    }

    private function getDealsClosedWon($agentId)
    {
        $query = Deal::whereHas('users', function ($query) use ($agentId) {
            $query->where('users.id', $agentId);
        })->whereHas('stage', function ($query) {
            $query->where('name', 'LIKE', '%won%')
                ->orWhere('name', 'LIKE', '%closed%')
                ->orWhere('name', 'LIKE', '%success%');
        })->whereBetween('updated_at', [$this->startDate, $this->endDate]);

        if ($this->stageId) {
            $query->where('stage_id', $this->stageId);
        }

        if ($this->typeId) {
            $query->where('type_id', $this->typeId);
        }

        return $query->count();
    }

    private function getDealsClosedLost($agentId)
    {
        $query = Deal::whereHas('users', function ($query) use ($agentId) {
            $query->where('users.id', $agentId);
        })->whereHas('stage', function ($query) {
            $query->where('name', 'LIKE', '%lost%')
                ->orWhere('name', 'LIKE', '%rejected%')
                ->orWhere('name', 'LIKE', '%cancelled%');
        })->whereBetween('updated_at', [$this->startDate, $this->endDate]);

        if ($this->stageId) {
            $query->where('stage_id', $this->stageId);
        }

        if ($this->typeId) {
            $query->where('type_id', $this->typeId);
        }

        return $query->count();
    }

    private function getDealsInPipeline($agentId)
    {
        $query = Deal::whereHas('users', function ($query) use ($agentId) {
            $query->where('users.id', $agentId);
        })->whereHas('stage', function ($query) {
            $query->where('name', 'NOT LIKE', '%won%')
                ->where('name', 'NOT LIKE', '%lost%')
                ->where('name', 'NOT LIKE', '%closed%')
                ->where('name', 'NOT LIKE', '%rejected%')
                ->where('name', 'NOT LIKE', '%cancelled%');
        });

        if ($this->stageId) {
            $query->where('stage_id', $this->stageId);
        }

        if ($this->typeId) {
            $query->where('type_id', $this->typeId);
        }

        return $query->count();
    }

    // Value Metrics
    private function getTotalDealValue($agentId)
    {
        $query = Deal::whereHas('users', function ($query) use ($agentId) {
            $query->where('users.id', $agentId);
        })->whereBetween('created_at', [$this->startDate, $this->endDate]);

        if ($this->stageId) {
            $query->where('stage_id', $this->stageId);
        }

        if ($this->typeId) {
            $query->where('type_id', $this->typeId);
        }

        return $query->sum('price') ?? 0;
    }

    private function getClosedDealValue($agentId)
    {
        $query = Deal::whereHas('users', function ($query) use ($agentId) {
            $query->where('users.id', $agentId);
        })->whereHas('stage', function ($query) {
            $query->where('name', 'LIKE', '%won%')
                ->orWhere('name', 'LIKE', '%closed%')
                ->orWhere('name', 'LIKE', '%success%');
        })->whereBetween('updated_at', [$this->startDate, $this->endDate]);

        if ($this->stageId) {
            $query->where('stage_id', $this->stageId);
        }

        if ($this->typeId) {
            $query->where('type_id', $this->typeId);
        }

        return $query->sum('price') ?? 0;
    }

    private function getPipelineValue($agentId)
    {
        $query = Deal::whereHas('users', function ($query) use ($agentId) {
            $query->where('users.id', $agentId);
        })->whereHas('stage', function ($query) {
            $query->where('name', 'NOT LIKE', '%won%')
                ->where('name', 'NOT LIKE', '%lost%')
                ->where('name', 'NOT LIKE', '%closed%')
                ->where('name', 'NOT LIKE', '%rejected%')
                ->where('name', 'NOT LIKE', '%cancelled%');
        });

        if ($this->stageId) {
            $query->where('stage_id', $this->stageId);
        }

        if ($this->typeId) {
            $query->where('type_id', $this->typeId);
        }

        return $query->sum('price') ?? 0;
    }

    private function getAverageDealSize($agentId)
    {
        $query = Deal::whereHas('users', function ($query) use ($agentId) {
            $query->where('users.id', $agentId);
        })->whereBetween('created_at', [$this->startDate, $this->endDate])
            ->where('price', '>', 0);

        if ($this->stageId) {
            $query->where('stage_id', $this->stageId);
        }

        if ($this->typeId) {
            $query->where('type_id', $this->typeId);
        }

        return round($query->avg('price') ?? 0, 2);
    }

    // Commission Metrics
    private function getTotalCommission($agentId)
    {
        $query = Deal::whereHas('users', function ($query) use ($agentId) {
            $query->where('users.id', $agentId);
        })->whereHas('stage', function ($query) {
            $query->where('name', 'LIKE', '%won%')
                ->orWhere('name', 'LIKE', '%closed%')
                ->orWhere('name', 'LIKE', '%success%');
        })->whereBetween('updated_at', [$this->startDate, $this->endDate]);

        if ($this->stageId) {
            $query->where('stage_id', $this->stageId);
        }

        if ($this->typeId) {
            $query->where('type_id', $this->typeId);
        }

        return $query->sum('commission_value') ?? 0;
    }

    private function getAgentCommission($agentId)
    {
        $query = Deal::whereHas('users', function ($query) use ($agentId) {
            $query->where('users.id', $agentId);
        })->whereHas('stage', function ($query) {
            $query->where('name', 'LIKE', '%won%')
                ->orWhere('name', 'LIKE', '%closed%')
                ->orWhere('name', 'LIKE', '%success%');
        })->whereBetween('updated_at', [$this->startDate, $this->endDate]);

        if ($this->stageId) {
            $query->where('stage_id', $this->stageId);
        }

        if ($this->typeId) {
            $query->where('type_id', $this->typeId);
        }

        return $query->sum('agent_commission') ?? 0;
    }

    private function getTeamCommission($agentId)
    {
        $query = Deal::whereHas('users', function ($query) use ($agentId) {
            $query->where('users.id', $agentId);
        })->whereHas('stage', function ($query) {
            $query->where('name', 'LIKE', '%won%')
                ->orWhere('name', 'LIKE', '%closed%')
                ->orWhere('name', 'LIKE', '%success%');
        })->whereBetween('updated_at', [$this->startDate, $this->endDate]);

        if ($this->stageId) {
            $query->where('stage_id', $this->stageId);
        }

        if ($this->typeId) {
            $query->where('type_id', $this->typeId);
        }

        return $query->sum('team_commission') ?? 0;
    }

    // Performance Ratios
    private function getCloseRate($agentId)
    {
        $totalDeals = $this->getDealsCreated($agentId);
        $closedDeals = $this->getDealsClosedWon($agentId);

        return $totalDeals > 0 ? round(($closedDeals / $totalDeals) * 100, 2) : 0;
    }

    private function getWinRate($agentId)
    {
        $closedWon = $this->getDealsClosedWon($agentId);
        $closedLost = $this->getDealsClosedLost($agentId);
        $totalClosed = $closedWon + $closedLost;

        return $totalClosed > 0 ? round(($closedWon / $totalClosed) * 100, 2) : 0;
    }

    // Time Metrics
    private function getAverageTimeToClose($agentId)
    {
        $query = Deal::whereHas('users', function ($query) use ($agentId) {
            $query->where('users.id', $agentId);
        })->whereHas('stage', function ($query) {
            $query->where('name', 'LIKE', '%won%')
                ->orWhere('name', 'LIKE', '%closed%')
                ->orWhere('name', 'LIKE', '%success%');
        })->whereBetween('updated_at', [$this->startDate, $this->endDate]);

        if ($this->stageId) {
            $query->where('stage_id', $this->stageId);
        }

        if ($this->typeId) {
            $query->where('type_id', $this->typeId);
        }

        $deals = $query->get();
        $totalDays = 0;
        $count = 0;

        foreach ($deals as $deal) {
            $days = $deal->created_at->diffInDays($deal->updated_at);
            $totalDays += $days;
            $count++;
        }

        return $count > 0 ? round($totalDays / $count, 1) : 0;
    }

    private function getAverageTimeInCurrentStage($agentId)
    {
        $query = Deal::whereHas('users', function ($query) use ($agentId) {
            $query->where('users.id', $agentId);
        })->whereHas('stage', function ($query) {
            $query->where('name', 'NOT LIKE', '%won%')
                ->where('name', 'NOT LIKE', '%lost%')
                ->where('name', 'NOT LIKE', '%closed%')
                ->where('name', 'NOT LIKE', '%rejected%')
                ->where('name', 'NOT LIKE', '%cancelled%');
        });

        if ($this->stageId) {
            $query->where('stage_id', $this->stageId);
        }

        if ($this->typeId) {
            $query->where('type_id', $this->typeId);
        }

        $deals = $query->get();
        $totalDays = 0;
        $count = 0;

        foreach ($deals as $deal) {
            $days = $deal->updated_at->diffInDays(now());
            $totalDays += $days;
            $count++;
        }

        return $count > 0 ? round($totalDays / $count, 1) : 0;
    }

    // Contact Metrics
    private function getUniqueContacts($agentId)
    {
        $query = Deal::whereHas('users', function ($query) use ($agentId) {
            $query->where('users.id', $agentId);
        })->whereBetween('created_at', [$this->startDate, $this->endDate])
            ->with('people');

        if ($this->stageId) {
            $query->where('stage_id', $this->stageId);
        }

        if ($this->typeId) {
            $query->where('type_id', $this->typeId);
        }

        $deals = $query->get();
        $uniqueContacts = collect();

        foreach ($deals as $deal) {
            foreach ($deal->people as $person) {
                $uniqueContacts->push($person->id);
            }
        }

        return $uniqueContacts->unique()->count();
    }

    private function getContactsReached($agentId)
    {
        $query = Deal::whereHas('users', function ($query) use ($agentId) {
            $query->where('users.id', $agentId);
        })->whereHas('stage', function ($query) {
            $query->where('name', 'LIKE', '%won%')
                ->orWhere('name', 'LIKE', '%closed%')
                ->orWhere('name', 'LIKE', '%success%');
        })->whereBetween('updated_at', [$this->startDate, $this->endDate])
            ->with('people');

        if ($this->stageId) {
            $query->where('stage_id', $this->stageId);
        }

        if ($this->typeId) {
            $query->where('type_id', $this->typeId);
        }

        $deals = $query->get();
        $reachedContacts = collect();

        foreach ($deals as $deal) {
            foreach ($deal->people as $person) {
                $reachedContacts->push($person->id);
            }
        }

        return $reachedContacts->unique()->count();
    }

    // Daily Averages
    private function getAverageDealsPerDay($agentId)
    {
        $totalDeals = $this->getDealsCreated($agentId);
        $daysDiff = $this->startDate->diffInDays($this->endDate) + 1;

        return round($totalDeals / $daysDiff, 2);
    }

    private function getAverageValuePerDay($agentId)
    {
        $totalValue = $this->getTotalDealValue($agentId);
        $daysDiff = $this->startDate->diffInDays($this->endDate) + 1;

        return round($totalValue / $daysDiff, 2);
    }
}