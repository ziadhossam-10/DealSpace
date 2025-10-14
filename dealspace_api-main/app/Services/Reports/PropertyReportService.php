<?php

namespace App\Services\Reports;

use App\Models\Event;
use App\Repositories\Reports\PropertyReportRepositoryInterface;
use Carbon\Carbon;

class PropertyReportService implements PropertyReportServiceInterface
{
    protected $propertyReportRepository;

    public function __construct(PropertyReportRepositoryInterface $propertyReportRepository)
    {
        $this->propertyReportRepository = $propertyReportRepository;
    }

    /**
     * Get property report with events and leads grouped by MLS number.
     *
     * @param int $perPage
     * @param int $page
     * @param array $filters
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getPropertyReport(array $filters = [])
    {
        $processedFilters = $this->processFilters($filters);
        return $this->propertyReportRepository->getPropertyReport($processedFilters);
    }

    /**
     * Get inquiries grouped by zip code.
     *
     * @param int $perPage
     * @param int $page
     * @param array $filters
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getInquiriesByZipCode(array $filters = [])
    {
        $processedFilters = $this->processFilters($filters);
        return $this->propertyReportRepository->getInquiriesByZipCode($processedFilters);
    }

    /**
     * Get inquiries grouped by property (MLS number).
     *
     * @param int $perPage
     * @param int $page
     * @param array $filters
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getInquiriesByProperty(int $perPage = 15, int $page = 1, array $filters = [])
    {
        $processedFilters = $this->processFilters($filters);
        return $this->propertyReportRepository->getInquiriesByProperty($perPage, $page, $processedFilters);
    }

    /**
     * Get detailed report for a specific property by MLS number.
     *
     * @param string $mlsNumber
     * @param int $perPage
     * @param int $page
     * @param array $filters
     * @return array
     */
    public function getPropertyDetailReport(string $mlsNumber, int $perPage = 15, int $page = 1, array $filters = [])
    {
        $processedFilters = $this->processFilters($filters);

        // Get property information
        $propertyInfo = $this->propertyReportRepository->getPropertyInfo($mlsNumber);

        // Get events for this property
        $events = $this->propertyReportRepository->getPropertyEvents($mlsNumber, $perPage, $page, $processedFilters);

        // Get event statistics
        $eventStats = $this->propertyReportRepository->getPropertyEventStats($mlsNumber, $processedFilters);

        // Get unique leads/persons
        $leadsCount = $this->propertyReportRepository->getPropertyLeadsCount($mlsNumber, $processedFilters);

        return [
            'property_info' => $propertyInfo,
            'events' => $events,
            'statistics' => [
                'total_events' => $eventStats['total_events'] ?? 0,
                'total_inquiries' => $eventStats['total_inquiries'] ?? 0,
                'total_leads' => $leadsCount,
                'event_breakdown' => $eventStats['event_breakdown'] ?? [],
                'monthly_breakdown' => $eventStats['monthly_breakdown'] ?? [],
            ]
        ];
    }

    /**
     * Get summary statistics for property reports.
     *
     * @param array $filters
     * @return array
     */
    public function getPropertyReportSummary(array $filters = [])
    {
        $processedFilters = $this->processFilters($filters);

        return [
            'total_properties_with_events' => $this->propertyReportRepository->getTotalPropertiesWithEvents($processedFilters),
            'total_events' => $this->propertyReportRepository->getTotalEvents($processedFilters),
            'total_inquiries' => $this->propertyReportRepository->getTotalInquiries($processedFilters),
            'total_unique_leads' => $this->propertyReportRepository->getTotalUniqueLeads($processedFilters),
            'average_inquiries_per_property' => $this->propertyReportRepository->getAverageInquiriesPerProperty($processedFilters),
            'top_zip_codes' => $this->propertyReportRepository->getTopZipCodes(5, $processedFilters),
            'event_type_breakdown' => $this->propertyReportRepository->getEventTypeBreakdown($processedFilters),
        ];
    }

    /**
     * Get top performing properties by inquiry count.
     *
     * @param int $limit
     * @param array $filters
     * @return array
     */
    public function getTopPerformingProperties(int $limit = 10, array $filters = [])
    {
        $processedFilters = $this->processFilters($filters);
        return $this->propertyReportRepository->getTopPerformingProperties($limit, $processedFilters);
    }

    /**
     * Get leads/persons associated with events for a specific property.
     *
     * @param string $mlsNumber
     * @param int $perPage
     * @param int $page
     * @param array $filters
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getPropertyLeads(string $mlsNumber, int $perPage = 15, int $page = 1, array $filters = [])
    {
        $processedFilters = $this->processFilters($filters);
        return $this->propertyReportRepository->getPropertyLeads($mlsNumber, $perPage, $page, $processedFilters);
    }

    /**
     * Process and normalize filters.
     *
     * @param array $filters
     * @return array
     */
    private function processFilters(array $filters): array
    {
        $processedFilters = [];

        // Parse date filters
        if (!empty($filters['date_from'])) {
            $processedFilters['date_from'] = Carbon::parse($filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $processedFilters['date_to'] = Carbon::parse($filters['date_to']);
        }

        // Process event types filter
        if (!empty($filters['event_types'])) {
            if (is_string($filters['event_types'])) {
                $processedFilters['event_types'] = explode(',', $filters['event_types']);
            } else {
                $processedFilters['event_types'] = $filters['event_types'];
            }

            // Validate event types
            $validTypes = Event::getTypes();
            $processedFilters['event_types'] = array_intersect($processedFilters['event_types'], $validTypes);
        }

        // Copy other filters as-is
        $otherFilters = ['city', 'state', 'zip_code'];
        foreach ($otherFilters as $filter) {
            if (!empty($filters[$filter])) {
                $processedFilters[$filter] = $filters[$filter];
            }
        }

        return $processedFilters;
    }
}
