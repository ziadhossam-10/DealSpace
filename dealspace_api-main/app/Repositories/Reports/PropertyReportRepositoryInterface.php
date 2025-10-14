<?php

namespace App\Repositories\Reports;

interface PropertyReportRepositoryInterface
{
    /**
     * Get property report with events and leads grouped by MLS number.
     *
     * @param int $perPage
     * @param int $page
     * @param array $filters
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getPropertyReport(array $filters);

    /**
     * Get inquiries grouped by zip code.
     *
     * @param int $perPage
     * @param int $page
     * @param array $filters
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getInquiriesByZipCode(array $filters);

    /**
     * Get inquiries grouped by property (MLS number).
     *
     * @param int $perPage
     * @param int $page
     * @param array $filters
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getInquiriesByProperty(int $perPage, int $page, array $filters);

    /**
     * Get property information by MLS number.
     *
     * @param string $mlsNumber
     * @return array|null
     */
    public function getPropertyInfo(string $mlsNumber);

    /**
     * Get events for a specific property.
     *
     * @param string $mlsNumber
     * @param int $perPage
     * @param int $page
     * @param array $filters
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getPropertyEvents(string $mlsNumber, int $perPage, int $page, array $filters);

    /**
     * Get event statistics for a specific property.
     *
     * @param string $mlsNumber
     * @param array $filters
     * @return array
     */
    public function getPropertyEventStats(string $mlsNumber, array $filters);

    /**
     * Get count of unique leads for a specific property.
     *
     * @param string $mlsNumber
     * @param array $filters
     * @return int
     */
    public function getPropertyLeadsCount(string $mlsNumber, array $filters);

    /**
     * Get leads/persons for a specific property.
     *
     * @param string $mlsNumber
     * @param int $perPage
     * @param int $page
     * @param array $filters
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getPropertyLeads(string $mlsNumber, int $perPage, int $page, array $filters);

    /**
     * Get total properties with events.
     *
     * @param array $filters
     * @return int
     */
    public function getTotalPropertiesWithEvents(array $filters);

    /**
     * Get total events count.
     *
     * @param array $filters
     * @return int
     */
    public function getTotalEvents(array $filters);

    /**
     * Get total inquiries count.
     *
     * @param array $filters
     * @return int
     */
    public function getTotalInquiries(array $filters);

    /**
     * Get total unique leads count.
     *
     * @param array $filters
     * @return int
     */
    public function getTotalUniqueLeads(array $filters);

    /**
     * Get average inquiries per property.
     *
     * @param array $filters
     * @return float
     */
    public function getAverageInquiriesPerProperty(array $filters);

    /**
     * Get top zip codes by inquiry count.
     *
     * @param int $limit
     * @param array $filters
     * @return array
     */
    public function getTopZipCodes(int $limit, array $filters);

    /**
     * Get event type breakdown.
     *
     * @param array $filters
     * @return array
     */
    public function getEventTypeBreakdown(array $filters);

    /**
     * Get top performing properties by inquiry count.
     *
     * @param int $limit
     * @param array $filters
     * @return array
     */
    public function getTopPerformingProperties(int $limit, array $filters);
}
