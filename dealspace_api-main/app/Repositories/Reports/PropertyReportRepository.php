<?php

namespace App\Repositories\Reports;

use App\Models\Event;
use Illuminate\Support\Facades\DB;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;

class PropertyReportRepository implements PropertyReportRepositoryInterface
{
    protected $model;

    public function __construct(Event $model)
    {
        $this->model = $model;
    }

    /**
     * Get property report with events and leads grouped by MLS number.
     *
     * @param int $perPage
     * @param int $page
     * @param array $filters
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getPropertyReport(array $filters)
    {
        $query = $this->model->query()
            ->whereNotNull('property->mlsNumber')
            ->where('property->mlsNumber', '!=', '');

        $this->applyFilters($query, $filters);

        // Group by MLS number and get aggregated data
        $results = $query->select([
            DB::raw("JSON_UNQUOTE(JSON_EXTRACT(property, '$.mlsNumber')) as mls_number"),
            DB::raw("JSON_UNQUOTE(JSON_EXTRACT(property, '$.street')) as street"),
            DB::raw("JSON_UNQUOTE(JSON_EXTRACT(property, '$.city')) as city"),
            DB::raw("JSON_UNQUOTE(JSON_EXTRACT(property, '$.state')) as state"),
            DB::raw("JSON_UNQUOTE(JSON_EXTRACT(property, '$.code')) as zip_code"),
            DB::raw("JSON_UNQUOTE(JSON_EXTRACT(property, '$.price')) as price"),
            DB::raw("COUNT(*) as total_events"),
            DB::raw("COUNT(CASE WHEN type IN ('Inquiry', 'Property Inquiry', 'Seller Inquiry', 'General Inquiry') THEN 1 END) as total_inquiries"),
            DB::raw("COUNT(DISTINCT CASE WHEN person IS NOT NULL THEN JSON_EXTRACT(person, '$.id') END) as unique_leads"),
            DB::raw("MAX(occurred_at) as latest_event_date"),
            DB::raw("MIN(occurred_at) as first_event_date")
        ])
            ->groupBy([
                DB::raw("JSON_UNQUOTE(JSON_EXTRACT(property, '$.mlsNumber'))"),
                DB::raw("JSON_UNQUOTE(JSON_EXTRACT(property, '$.street'))"),
                DB::raw("JSON_UNQUOTE(JSON_EXTRACT(property, '$.city'))"),
                DB::raw("JSON_UNQUOTE(JSON_EXTRACT(property, '$.state'))"),
                DB::raw("JSON_UNQUOTE(JSON_EXTRACT(property, '$.code'))"),
                DB::raw("JSON_UNQUOTE(JSON_EXTRACT(property, '$.price'))")
            ])
            ->orderBy('total_inquiries', 'desc');

        return $query->get();
    }

    /**
     * Get inquiries grouped by zip code.
     *
     * @param int $perPage
     * @param int $page
     * @param array $filters
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getInquiriesByZipCode(array $filters)
    {
        $query = $this->model->query()
            ->whereNotNull('property->code')
            ->where('property->code', '!=', '');

        $this->applyFilters($query, $filters);

        $results = $query->select([
            DB::raw("JSON_UNQUOTE(JSON_EXTRACT(property, '$.code')) as zip_code"),
            DB::raw("JSON_UNQUOTE(JSON_EXTRACT(property, '$.city')) as city"),
            DB::raw("JSON_UNQUOTE(JSON_EXTRACT(property, '$.state')) as state"),
            DB::raw("COUNT(*) as total_events"),
            DB::raw("COUNT(CASE WHEN type IN ('Inquiry', 'Property Inquiry', 'Seller Inquiry', 'General Inquiry') THEN 1 END) as total_inquiries"),
            DB::raw("COUNT(DISTINCT JSON_UNQUOTE(JSON_EXTRACT(property, '$.mlsNumber'))) as unique_properties"),
            DB::raw("COUNT(DISTINCT CASE WHEN person IS NOT NULL THEN JSON_EXTRACT(person, '$.id') END) as unique_leads")
        ])
            ->groupBy([
                DB::raw("JSON_UNQUOTE(JSON_EXTRACT(property, '$.code'))"),
                DB::raw("JSON_UNQUOTE(JSON_EXTRACT(property, '$.city'))"),
                DB::raw("JSON_UNQUOTE(JSON_EXTRACT(property, '$.state'))")
            ])
            ->orderBy('total_inquiries', 'desc');

        return $query->get();
    }

    /**
     * Get inquiries grouped by property (MLS number).
     *
     * @param int $perPage
     * @param int $page
     * @param array $filters
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getInquiriesByProperty(int $perPage, int $page, array $filters)
    {
        return $this->getPropertyReport($filters);
    }

    /**
     * Get property information by MLS number.
     *
     * @param string $mlsNumber
     * @return array|null
     */
    public function getPropertyInfo(string $mlsNumber)
    {
        $event = $this->model->query()
            ->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(property, '$.mlsNumber')) = ?", [$mlsNumber])
            ->orderBy('occurred_at', 'desc')
            ->first();

        if (!$event || !$event->property) {
            return null;
        }

        return $event->property;
    }

    /**
     * Get events for a specific property.
     *
     * @param string $mlsNumber
     * @param int $perPage
     * @param int $page
     * @param array $filters
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getPropertyEvents(string $mlsNumber, int $perPage, int $page, array $filters)
    {
        $query = $this->model->query()
            ->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(property, '$.mlsNumber')) = ?", [$mlsNumber]);

        $this->applyFilters($query, $filters);

        return $query->orderBy('occurred_at', 'desc')
            ->paginate($perPage, ['*'], 'page', $page);
    }

    /**
     * Get event statistics for a specific property.
     *
     * @param string $mlsNumber
     * @param array $filters
     * @return array
     */
    public function getPropertyEventStats(string $mlsNumber, array $filters)
    {
        $query = $this->model->query()
            ->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(property, '$.mlsNumber')) = ?", [$mlsNumber]);

        $this->applyFilters($query, $filters);

        // Get basic stats
        $totalEvents = $query->count();
        $totalInquiries = (clone $query)->whereIn('type', [
            'Inquiry',
            'Property Inquiry',
            'Seller Inquiry',
            'General Inquiry'
        ])->count();

        // Get event breakdown by type
        $eventBreakdown = (clone $query)
            ->select('type', DB::raw('COUNT(*) as count'))
            ->groupBy('type')
            ->orderBy('count', 'desc')
            ->get()
            ->pluck('count', 'type')
            ->toArray();

        // Get monthly breakdown
        $monthlyBreakdown = (clone $query)
            ->select(
                DB::raw('DATE_FORMAT(occurred_at, "%Y-%m") as month'),
                DB::raw('COUNT(*) as total_events'),
                DB::raw("COUNT(CASE WHEN type IN ('Inquiry', 'Property Inquiry', 'Seller Inquiry', 'General Inquiry') THEN 1 END) as inquiries")
            )
            ->groupBy(DB::raw('DATE_FORMAT(occurred_at, "%Y-%m")'))
            ->orderBy('month')
            ->get()
            ->toArray();

        return [
            'total_events' => $totalEvents,
            'total_inquiries' => $totalInquiries,
            'event_breakdown' => $eventBreakdown,
            'monthly_breakdown' => $monthlyBreakdown,
        ];
    }

    /**
     * Get count of unique leads for a specific property.
     *
     * @param string $mlsNumber
     * @param array $filters
     * @return int
     */
    public function getPropertyLeadsCount(string $mlsNumber, array $filters)
    {
        $query = $this->model->query()
            ->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(property, '$.mlsNumber')) = ?", [$mlsNumber])
            ->whereNotNull('person');

        $this->applyFilters($query, $filters);

        return $query->distinct()
            ->count(DB::raw("JSON_EXTRACT(person, '$.id')"));
    }

    /**
     * Get leads/persons for a specific property.
     *
     * @param string $mlsNumber
     * @param int $perPage
     * @param int $page
     * @param array $filters
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getPropertyLeads(string $mlsNumber, int $perPage, int $page, array $filters)
    {
        $query = $this->model->query()
            ->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(property, '$.mlsNumber')) = ?", [$mlsNumber])
            ->whereNotNull('person');

        $this->applyFilters($query, $filters);

        $results = $query->select([
            DB::raw("JSON_EXTRACT(person, '$.id') as person_id"),
            DB::raw("JSON_UNQUOTE(JSON_EXTRACT(person, '$.firstName')) as first_name"),
            DB::raw("JSON_UNQUOTE(JSON_EXTRACT(person, '$.lastName')) as last_name"),
            DB::raw("JSON_EXTRACT(person, '$.emails') as emails"),
            DB::raw("JSON_EXTRACT(person, '$.phones') as phones"),
            DB::raw("JSON_UNQUOTE(JSON_EXTRACT(person, '$.stage')) as stage"),
            DB::raw("JSON_UNQUOTE(JSON_EXTRACT(person, '$.source')) as source"),
            DB::raw("COUNT(*) as total_events"),
            DB::raw("COUNT(CASE WHEN type IN ('Inquiry', 'Property Inquiry', 'Seller Inquiry', 'General Inquiry') THEN 1 END) as total_inquiries"),
            DB::raw("MAX(occurred_at) as latest_event_date"),
            DB::raw("MIN(occurred_at) as first_event_date")
        ])
            ->groupBy([
                DB::raw("JSON_EXTRACT(person, '$.id')"),
                DB::raw("JSON_UNQUOTE(JSON_EXTRACT(person, '$.firstName'))"),
                DB::raw("JSON_UNQUOTE(JSON_EXTRACT(person, '$.lastName'))"),
                DB::raw("JSON_EXTRACT(person, '$.emails')"),
                DB::raw("JSON_EXTRACT(person, '$.phones')"),
                DB::raw("JSON_UNQUOTE(JSON_EXTRACT(person, '$.stage'))"),
                DB::raw("JSON_UNQUOTE(JSON_EXTRACT(person, '$.source'))")
            ])
            ->orderBy('total_inquiries', 'desc');

        return $this->paginateQuery($query, $perPage, $page);
    }

    /**
     * Get total properties with events.
     *
     * @param array $filters
     * @return int
     */
    public function getTotalPropertiesWithEvents(array $filters)
    {
        $query = $this->model->query()
            ->whereNotNull('property->mlsNumber')
            ->where('property->mlsNumber', '!=', '');

        $this->applyFilters($query, $filters);

        return $query->distinct()
            ->count(DB::raw("JSON_UNQUOTE(JSON_EXTRACT(property, '$.mlsNumber'))"));
    }

    /**
     * Get total events count.
     *
     * @param array $filters
     * @return int
     */
    public function getTotalEvents(array $filters)
    {
        $query = $this->model->query()
            ->whereNotNull('property->mlsNumber')
            ->where('property->mlsNumber', '!=', '');

        $this->applyFilters($query, $filters);

        return $query->count();
    }

    /**
     * Get total inquiries count.
     *
     * @param array $filters
     * @return int
     */
    public function getTotalInquiries(array $filters)
    {
        $query = $this->model->query()
            ->whereNotNull('property->mlsNumber')
            ->where('property->mlsNumber', '!=', '');

        $this->applyFilters($query, $filters);

        return $query->count();
    }

    /**
     * Get total unique leads count.
     *
     * @param array $filters
     * @return int
     */
    public function getTotalUniqueLeads(array $filters)
    {
        $query = $this->model->query()
            ->whereNotNull('property->mlsNumber')
            ->where('property->mlsNumber', '!=', '')
            ->whereNotNull('person');

        $this->applyFilters($query, $filters);

        return $query->distinct()
            ->count(DB::raw("JSON_EXTRACT(person, '$.id')"));
    }

    /**
     * Get average inquiries per property.
     *
     * @param array $filters
     * @return float
     */
    public function getAverageInquiriesPerProperty(array $filters)
    {
        $totalInquiries = $this->getTotalInquiries($filters);
        $totalProperties = $this->getTotalPropertiesWithEvents($filters);

        return $totalProperties > 0 ? round($totalInquiries / $totalProperties, 2) : 0;
    }

    /**
     * Get top zip codes by inquiry count.
     *
     * @param int $limit
     * @param array $filters
     * @return array
     */
    public function getTopZipCodes(int $limit, array $filters)
    {
        $query = $this->model->query()
            ->whereNotNull('property->code')
            ->where('property->code', '!=', '');

        $this->applyFilters($query, $filters);

        return $query->select([
            DB::raw("JSON_UNQUOTE(JSON_EXTRACT(property, '$.code')) as zip_code"),
            DB::raw("JSON_UNQUOTE(JSON_EXTRACT(property, '$.city')) as city"),
            DB::raw("JSON_UNQUOTE(JSON_EXTRACT(property, '$.state')) as state"),
            DB::raw("COUNT(*) as inquiry_count")
        ])
            ->groupBy([
                DB::raw("JSON_UNQUOTE(JSON_EXTRACT(property, '$.code'))"),
                DB::raw("JSON_UNQUOTE(JSON_EXTRACT(property, '$.city'))"),
                DB::raw("JSON_UNQUOTE(JSON_EXTRACT(property, '$.state'))")
            ])
            ->orderBy('inquiry_count', 'desc')
            ->limit($limit)
            ->get()
            ->toArray();
    }

    /**
     * Get event type breakdown.
     *
     * @param array $filters
     * @return array
     */
    public function getEventTypeBreakdown(array $filters)
    {
        $query = $this->model->query()
            ->whereNotNull('property->mlsNumber')
            ->where('property->mlsNumber', '!=', '');

        $this->applyFilters($query, $filters);

        return $query->select('type', DB::raw('COUNT(*) as count'))
            ->groupBy('type')
            ->orderBy('count', 'desc')
            ->get()
            ->pluck('count', 'type')
            ->toArray();
    }

    /**
     * Get top performing properties by inquiry count.
     *
     * @param int $limit
     * @param array $filters
     * @return array
     */
    public function getTopPerformingProperties(int $limit, array $filters)
    {
        $query = $this->model->query()
            ->whereNotNull('property->mlsNumber')
            ->where('property->mlsNumber', '!=', '');

        $this->applyFilters($query, $filters);

        return $query->select([
            DB::raw("JSON_UNQUOTE(JSON_EXTRACT(property, '$.mlsNumber')) as mls_number"),
            DB::raw("JSON_UNQUOTE(JSON_EXTRACT(property, '$.street')) as street"),
            DB::raw("JSON_UNQUOTE(JSON_EXTRACT(property, '$.city')) as city"),
            DB::raw("JSON_UNQUOTE(JSON_EXTRACT(property, '$.state')) as state"),
            DB::raw("JSON_UNQUOTE(JSON_EXTRACT(property, '$.price')) as price"),
            DB::raw("COUNT(*) as inquiry_count"),
            DB::raw("COUNT(DISTINCT CASE WHEN person IS NOT NULL THEN JSON_EXTRACT(person, '$.id') END) as unique_leads")
        ])
            ->groupBy([
                DB::raw("JSON_UNQUOTE(JSON_EXTRACT(property, '$.mlsNumber'))"),
                DB::raw("JSON_UNQUOTE(JSON_EXTRACT(property, '$.street'))"),
                DB::raw("JSON_UNQUOTE(JSON_EXTRACT(property, '$.city'))"),
                DB::raw("JSON_UNQUOTE(JSON_EXTRACT(property, '$.state'))"),
                DB::raw("JSON_UNQUOTE(JSON_EXTRACT(property, '$.price'))")
            ])
            ->orderBy('inquiry_count', 'desc')
            ->limit($limit)
            ->get()
            ->toArray();
    }

    /**
     * Apply filters to query.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param array $filters
     * @return void
     */
    private function applyFilters($query, array $filters)
    {
        if (!empty($filters['date_from'])) {
            $query->whereDate('occurred_at', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->whereDate('occurred_at', '<=', $filters['date_to']);
        }

        if (!empty($filters['event_types'])) {
            $query->whereIn('type', $filters['event_types']);
        }

        if (!empty($filters['city'])) {
            $query->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(property, '$.city')) = ?", [$filters['city']]);
        }

        if (!empty($filters['state'])) {
            $query->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(property, '$.state')) = ?", [$filters['state']]);
        }

        if (!empty($filters['zip_code'])) {
            $query->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(property, '$.code')) = ?", [$filters['zip_code']]);
        }
    }

    /**
     * Paginate query results manually.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $perPage
     * @param int $page
     * @param array $filters
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    private function paginateQuery($query, int $perPage, int $page, array $filters = [])
    {
        // Create a simple count query for distinct MLS numbers
        $countQuery = $this->model->query()
            ->whereNotNull('property->mlsNumber')
            ->where('property->mlsNumber', '!=', '');

        // Apply the same filters
        $this->applyFilters($countQuery, $filters);

        // Count distinct MLS numbers
        $total = $countQuery->distinct()
            ->count(DB::raw("JSON_UNQUOTE(JSON_EXTRACT(property, '$.mlsNumber'))"));

        $offset = ($page - 1) * $perPage;
        $items = $query->offset($offset)->limit($perPage)->get();

        return new LengthAwarePaginator(
            $items,
            $total,
            $perPage,
            $page,
            [
                'path' => Paginator::resolveCurrentPath(),
                'pageName' => 'page',
            ]
        );
    }
}
