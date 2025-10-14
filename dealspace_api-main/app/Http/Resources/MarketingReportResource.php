<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class MarketingReportResource extends JsonResource
{
    public function toArray($request)
    {
        // $this->resource contains the entire array returned from getReportData()
        $campaigns = $this->resource['campaigns'];

        // Convert Collection to array if it's still a Collection
        if ($campaigns instanceof \Illuminate\Support\Collection) {
            $campaigns = $campaigns->toArray();
        }

        return [
            'campaigns' => $campaigns,
            'totals' => $this->resource['totals'],
            'date_range' => [
                'start' => $this->resource['dateRange']['start']->toISOString(),
                'end' => $this->resource['dateRange']['end']->toISOString(),
            ],
            'filters' => $this->resource['filters'],
        ];
    }
}
