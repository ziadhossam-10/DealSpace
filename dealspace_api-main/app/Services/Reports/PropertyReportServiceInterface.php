<?php

namespace App\Services\Reports;

interface PropertyReportServiceInterface
{
    /**
     * Get property report with events and leads grouped by MLS number.
     *
     * @param int $perPage
     * @param int $page
     * @param array $filters
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getPropertyReport(array $filters = []);

    /**
     * Get inquiries grouped by zip code.
     *
     * @param int $perPage
     * @param int $page
     * @param array $filters
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getInquiriesByZipCode(array $filters = []);

    /**
     * Get inquiries grouped by property (MLS number).
     *
     * @param int $perPage
     * @param int $page
     * @param array $filters
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getInquiriesByProperty(int $perPage = 15, int $page = 1, array $filters = []);

    /**
     * Get detailed report for a specific property by MLS number.
     *
     * @param string $mlsNumber
     * @param int $perPage
     * @param int $page
     * @param array $filters
     * @return array
     */
    public function getPropertyDetailReport(string $mlsNumber, int $perPage = 15, int $page = 1, array $filters = []);

    /**
     * Get summary statistics for property reports.
     *
     * @param array $filters
     * @return array
     */
    public function getPropertyReportSummary(array $filters = []);

    /**
     * Get top performing properties by inquiry count.
     *
     * @param int $limit
     * @param array $filters
     * @return array
     */
    public function getTopPerformingProperties(int $limit = 10, array $filters = []);

    /**
     * Get leads/persons associated with events for a specific property.
     *
     * @param string $mlsNumber
     * @param int $perPage
     * @param int $page
     * @param array $filters
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getPropertyLeads(string $mlsNumber, int $perPage = 15, int $page = 1, array $filters = []);
}
