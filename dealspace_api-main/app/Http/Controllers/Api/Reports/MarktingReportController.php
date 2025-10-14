<?php

namespace App\Http\Controllers\Api\Reports;

use App\Http\Controllers\Controller;
use App\Models\Event;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class MarktingReportController extends Controller
{
    public function index(Request $request)
    {
        $startDate = Carbon::parse($request->start_date ?? now()->startOfMonth());
        $endDate = Carbon::parse($request->end_date ?? now()->endOfMonth());

        $sources = Event::query()
            ->whereNotNull('campaign')
            ->whereRaw("JSON_EXTRACT(campaign, '$.source') IS NOT NULL") // Make sure 'source' exists in campaign JSON
            ->select(
                DB::raw("JSON_UNQUOTE(JSON_EXTRACT(campaign, '$.source')) as source"),
                DB::raw("COUNT(DISTINCT person_id) as people_count")
            )
            ->groupBy('source')
            ->paginate(15);
    }
}
