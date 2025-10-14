<?php

namespace App\Repositories\Reports;

use App\Models\Event;
use App\Models\Appointment;
use App\Models\Deal;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class MarketingRepository
{
    public function getCampaignMetrics(Carbon $startDate, Carbon $endDate, string $campaignFilter): Collection
    {
        // Use raw SQL to avoid Laravel's GROUP BY complexities
        $sql = "
            SELECT
                JSON_EXTRACT(campaign, '$.source') as source,
                JSON_EXTRACT(campaign, '$.medium') as medium,
                JSON_EXTRACT(campaign, '$.campaign') as campaign_name,
                COUNT(DISTINCT person_id) as leads,
                COUNT(*) as total_events
            FROM events
            WHERE occurred_at BETWEEN ? AND ?
                AND campaign IS NOT NULL
                AND person_id IS NOT NULL
                AND tenant_id = ?
        ";

        $bindings = [$startDate, $endDate, tenant('id')];

        if ($campaignFilter !== 'all') {
            $sql .= " AND JSON_EXTRACT(campaign, '$.source') = ?";
            $bindings[] = $campaignFilter;
        }

        $sql .= "
            GROUP BY
                JSON_EXTRACT(campaign, '$.source'),
                JSON_EXTRACT(campaign, '$.medium'),
                JSON_EXTRACT(campaign, '$.campaign')
        ";

        $results = DB::select($sql, $bindings);

        return collect($results);
    }

    public function getPersonIdsForCampaign(string $source, string $medium, string $campaign, Carbon $startDate, Carbon $endDate): Collection
    {
        return Event::whereBetween('occurred_at', [$startDate, $endDate])
            ->whereJsonContains('campaign->source', $source)
            ->whereJsonContains('campaign->medium', $medium)
            ->whereJsonContains('campaign->campaign', $campaign)
            ->whereNotNull('person_id')
            ->pluck('person_id')
            ->unique();
    }

    public function getAppointmentsCount(Collection $personIds, Carbon $startDate, Carbon $endDate): int
    {
        if ($personIds->isEmpty()) {
            return 0;
        }

        return Appointment::whereHas('invitedPeople', function ($query) use ($personIds) {
            $query->whereIn('person_id', $personIds);
        })->whereBetween('start', [$startDate, $endDate])->count();
    }

    public function getPeopleWithAppointmentsCount(Collection $personIds, Carbon $startDate, Carbon $endDate): int
    {
        if ($personIds->isEmpty()) {
            return 0;
        }

        return Appointment::whereHas('invitedPeople', function ($query) use ($personIds) {
            $query->whereIn('person_id', $personIds);
        })
            ->whereBetween('start', [$startDate, $endDate])
            ->with('invitedPeople')
            ->get()
            ->flatMap(function ($appointment) use ($personIds) {
                return $appointment->invitedPeople->whereIn('id', $personIds)->pluck('id');
            })
            ->unique()
            ->count();
    }

    public function getClosedDealsCount(Collection $personIds, Carbon $startDate, Carbon $endDate): int
    {
        if ($personIds->isEmpty()) {
            return 0;
        }

        return $this->getClosedDealsQuery($personIds, $startDate, $endDate)->count();
    }

    public function getClosedDealsValue(Collection $personIds, Carbon $startDate, Carbon $endDate): float
    {
        if ($personIds->isEmpty()) {
            return 0;
        }

        return $this->getClosedDealsQuery($personIds, $startDate, $endDate)->sum('price') ?? 0;
    }

    public function getSourceCount(string $source, Carbon $startDate, Carbon $endDate): int
    {
        return Event::whereBetween('occurred_at', [$startDate, $endDate])
            ->whereJsonContains('campaign->source', $source)
            ->distinct('page_url')
            ->count('page_url');
    }

    public function getCampaignEvents(string $source, string $medium, string $campaign, Carbon $startDate, Carbon $endDate): LengthAwarePaginator
    {
        return Event::whereBetween('occurred_at', [$startDate, $endDate])
            ->whereJsonContains('campaign->source', $source)
            ->whereJsonContains('campaign->medium', $medium)
            ->whereJsonContains('campaign->campaign', $campaign)
            ->with('personRecord')
            ->orderBy('occurred_at', 'desc')
            ->paginate(50);
    }

    private function getClosedDealsQuery(Collection $personIds, Carbon $startDate, Carbon $endDate)
    {
        return Deal::whereHas('people', function ($query) use ($personIds) {
            $query->whereIn('person_id', $personIds);
        })
            ->whereHas('stage', function ($query) {
                $query->where('name', 'like', '%closed%')
                    ->orWhere('name', 'like', '%won%')
                    ->orWhere('name', 'like', '%complete%');
            })
            ->whereBetween('created_at', [$startDate, $endDate]);
    }
}
