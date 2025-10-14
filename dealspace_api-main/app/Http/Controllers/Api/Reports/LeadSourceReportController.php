<?php

namespace App\Http\Controllers\Api\Reports;

use App\Enums\RoleEnum;
use App\Exports\LeadSourceReportExport;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Models\Person;
use App\Models\Deal;
use App\Models\Event;
use App\Models\Call;
use App\Models\Email;
use App\Models\Note;
use App\Models\Task;
use App\Models\Appointment;
use App\Models\TextMessage;
use Maatwebsite\Excel\Facades\Excel;

class LeadSourceReportController extends Controller
{
    public function index(Request $request)
    {
        $startDate = Carbon::parse($request->start_date ?? now()->startOfMonth());
        $endDate = Carbon::parse($request->end_date ?? now()->endOfMonth());
        $leadSources = $request->lead_sources ?? ['all'];
        $leadTypes = $request->lead_types ?? ['all'];
        $userIds = $request->user_ids ?? [];

        $report = [
            'period' => [
                'start' => $startDate->format('Y-m-d'),
                'end' => $endDate->format('Y-m-d'),
            ],
            'lead_sources' => $this->getLeadSourceMetrics($startDate, $endDate, $leadSources, $leadTypes, $userIds),
            'totals' => $this->getTotalMetrics($startDate, $endDate, $leadSources, $leadTypes, $userIds),
            'time_series' => $this->getTimeSeriesData($startDate, $endDate, $leadSources, $leadTypes, $userIds),
        ];

        return response()->json($report);
    }

    public function export(Request $request)
    {
        $params = [
            'start_date' => $request->start_date ?? now()->startOfMonth(),
            'end_date' => $request->end_date ?? now()->endOfMonth(),
            'lead_sources' => $request->lead_sources ?? ['all'],
            'lead_types' => $request->lead_types ?? ['all'],
            'user_ids' => $request->user_ids ?? [],
        ];

        $fileName = 'lead_source_report_' . Carbon::now()->format('Ymd_His') . '.xlsx';
        return Excel::download(new LeadSourceReportExport($params), $fileName);
    }

    private function getLeadSourceMetrics($startDate, $endDate, $leadSources, $leadTypes, $userIds)
    {
        $query = Person::select('source')->distinct();

        if ($leadSources !== ['all']) {
            $query->whereIn('source', $leadSources);
        }

        if (!empty($userIds)) {
            $query->whereIn('assigned_user_id', $userIds);
        }

        $sources = $query->pluck('source')->toArray();
        $metrics = [];

        foreach ($sources as $source) {
            $metrics[] = [
                'lead_source' => $source,
                // Lead Counts
                'new_leads' => $this->getNewLeadsCount($source, $startDate, $endDate, $leadTypes, $userIds),
                // Activity Counts
                'calls' => $this->getCallsCount($source, $startDate, $endDate, $userIds),
                'emails' => $this->getEmailsCount($source, $startDate, $endDate, $userIds),
                'texts' => $this->getTextsCount($source, $startDate, $endDate, $userIds),
                'notes' => $this->getNotesCount($source, $startDate, $endDate, $userIds),
                'tasks_completed' => $this->getTasksCompletedCount($source, $startDate, $endDate, $userIds),
                'appointments' => $this->getAppointmentsCount($source, $startDate, $endDate, $userIds),
                // Response Tracking
                'leads_not_acted_on' => $this->getLeadsNotActedOn($source, $startDate, $endDate, $leadTypes, $userIds),
                'leads_not_called' => $this->getLeadsNotCalled($source, $startDate, $endDate, $leadTypes, $userIds),
                'leads_not_emailed' => $this->getLeadsNotEmailed($source, $startDate, $endDate, $leadTypes, $userIds),
                'leads_not_texted' => $this->getLeadsNotTexted($source, $startDate, $endDate, $leadTypes, $userIds),
                // Speed Metrics (in minutes)
                'avg_speed_to_action' => $this->getAverageSpeedToAction($source, $startDate, $endDate, $leadTypes, $userIds),
                'avg_speed_to_first_call' => $this->getAverageSpeedToFirstCall($source, $startDate, $endDate, $leadTypes, $userIds),
                'avg_speed_to_first_email' => $this->getAverageSpeedToFirstEmail($source, $startDate, $endDate, $leadTypes, $userIds),
                'avg_speed_to_first_text' => $this->getAverageSpeedToFirstText($source, $startDate, $endDate, $leadTypes, $userIds),
                // Contact Attempts
                'avg_contact_attempts' => $this->getAverageContactAttempts($source, $startDate, $endDate, $leadTypes, $userIds),
                'avg_call_attempts' => $this->getAverageCallAttempts($source, $startDate, $endDate, $leadTypes, $userIds),
                'avg_email_attempts' => $this->getAverageEmailAttempts($source, $startDate, $endDate, $leadTypes, $userIds),
                'avg_text_attempts' => $this->getAverageTextAttempts($source, $startDate, $endDate, $leadTypes, $userIds),
                // Response Rates
                'response_rate' => $this->getResponseRate($source, $startDate, $endDate, $leadTypes, $userIds),
                'email_response_rate' => $this->getEmailResponseRate($source, $startDate, $endDate, $leadTypes, $userIds),
                'phone_response_rate' => $this->getPhoneResponseRate($source, $startDate, $endDate, $leadTypes, $userIds),
                'text_response_rate' => $this->getTextResponseRate($source, $startDate, $endDate, $leadTypes, $userIds),
                // Closed Deals
                'deals_closed' => $this->getDealsClosedCount($source, $startDate, $endDate, $leadTypes, $userIds),
                'deal_value' => $this->getDealValue($source, $startDate, $endDate, $leadTypes, $userIds),
                'deal_commission' => $this->getDealCommission($source, $startDate, $endDate, $leadTypes, $userIds),
                'conversion_rate' => $this->getConversionRate($source, $startDate, $endDate, $leadTypes, $userIds),
                // Website Activity
                'website_registrations' => $this->getWebsiteRegistrations($source, $startDate, $endDate, $userIds),
                'inquiries' => $this->getInquiriesCount($source, $startDate, $endDate, $userIds),
                'properties_viewed' => $this->getPropertiesViewedCount($source, $startDate, $endDate, $userIds),
                'properties_saved' => $this->getPropertiesSavedCount($source, $startDate, $endDate, $userIds),
                'page_views' => $this->getPageViewsCount($source, $startDate, $endDate, $userIds),
            ];
        }

        return $metrics;
    }

    // Lead Count Calculations
    private function getNewLeadsCount($source, $startDate, $endDate, $leadTypes, $userIds)
    {
        $query = Person::where('source', $source)
            ->whereBetween('created_at', [$startDate, $endDate]);

        if ($leadTypes !== ['all']) {
            $query->whereIn('stage_id', $leadTypes);
        }

        if (!empty($userIds)) {
            $query->whereIn('assigned_user_id', $userIds);
        }

        return $query->count();
    }

    // Activity Count Calculations
    private function getCallsCount($source, $startDate, $endDate, $userIds)
    {
        $query = Call::whereHas('person', function ($q) use ($source) {
            $q->where('source', $source);
        })->whereBetween('created_at', [$startDate, $endDate]);

        if (!empty($userIds)) {
            $query->whereIn('user_id', $userIds);
        }

        return $query->count();
    }

    private function getEmailsCount($source, $startDate, $endDate, $userIds)
    {
        $query = Email::whereHas('person', function ($q) use ($source) {
            $q->where('source', $source);
        })->where('is_incoming', false)
            ->whereBetween('created_at', [$startDate, $endDate]);

        if (!empty($userIds)) {
            $query->whereIn('user_id', $userIds);
        }

        return $query->count();
    }

    private function getTextsCount($source, $startDate, $endDate, $userIds)
    {
        $query = TextMessage::whereHas('person', function ($q) use ($source) {
            $q->where('source', $source);
        })->where('is_incoming', false)
            ->whereBetween('created_at', [$startDate, $endDate]);

        if (!empty($userIds)) {
            $query->whereIn('user_id', $userIds);
        }

        return $query->count();
    }

    private function getNotesCount($source, $startDate, $endDate, $userIds)
    {
        $query = Note::whereHas('person', function ($q) use ($source) {
            $q->where('source', $source);
        })->whereBetween('created_at', [$startDate, $endDate]);

        if (!empty($userIds)) {
            $query->whereIn('created_by', $userIds);
        }

        return $query->count();
    }

    private function getTasksCompletedCount($source, $startDate, $endDate, $userIds)
    {
        $query = Task::whereHas('person', function ($q) use ($source) {
            $q->where('source', $source);
        })->where('is_completed', true)
            ->whereBetween('updated_at', [$startDate, $endDate]);

        if (!empty($userIds)) {
            $query->whereIn('assigned_user_id', $userIds);
        }

        return $query->count();
    }

    private function getAppointmentsCount($source, $startDate, $endDate, $userIds)
    {
        $query = Appointment::whereHas('invitedPeople', function ($q) use ($source) {
            $q->where('source', $source);
        })->whereBetween('created_at', [$startDate, $endDate]);

        if (!empty($userIds)) {
            $query->whereIn('created_by_id', $userIds);
        }

        return $query->count();
    }

    // Response Tracking Calculations
    private function getLeadsNotActedOn($source, $startDate, $endDate, $leadTypes, $userIds)
    {
        $newLeadsQuery = Person::where('source', $source)
            ->whereBetween('created_at', [$startDate, $endDate]);

        if ($leadTypes !== ['all']) {
            $newLeadsQuery->whereIn('stage_id', $leadTypes);
        }

        if (!empty($userIds)) {
            $newLeadsQuery->whereIn('assigned_user_id', $userIds);
        }

        $newLeadIds = $newLeadsQuery->pluck('id');

        $actedOnPeople = Person::whereIn('id', $newLeadIds)
            ->where(function ($query) {
                $query->whereHas('calls')
                    ->orWhereHas('emails', function ($q) {
                        $q->where('is_incoming', false);
                    })
                    ->orWhereHas('texts', function ($q) {
                        $q->where('is_incoming', false);
                    });
            })
            ->count();

        return $newLeadIds->count() - $actedOnPeople;
    }

    private function getLeadsNotCalled($source, $startDate, $endDate, $leadTypes, $userIds)
    {
        $newLeadsQuery = Person::where('source', $source)
            ->whereBetween('created_at', [$startDate, $endDate]);

        if ($leadTypes !== ['all']) {
            $newLeadsQuery->whereIn('stage_id', $leadTypes);
        }

        if (!empty($userIds)) {
            $newLeadsQuery->whereIn('assigned_user_id', $userIds);
        }

        $newLeadIds = $newLeadsQuery->pluck('id');

        $calledLeads = Person::whereIn('id', $newLeadIds)
            ->whereHas('calls')
            ->count();

        return $newLeadIds->count() - $calledLeads;
    }

    private function getLeadsNotEmailed($source, $startDate, $endDate, $leadTypes, $userIds)
    {
        $newLeadsQuery = Person::where('source', $source)
            ->whereBetween('created_at', [$startDate, $endDate]);

        if ($leadTypes !== ['all']) {
            $newLeadsQuery->whereIn('stage_id', $leadTypes);
        }

        if (!empty($userIds)) {
            $newLeadsQuery->whereIn('assigned_user_id', $userIds);
        }

        $newLeadIds = $newLeadsQuery->pluck('id');

        $emailedLeads = Person::whereIn('id', $newLeadIds)
            ->whereHas('emails', function ($q) {
                $q->where('is_incoming', false);
            })
            ->count();

        return $newLeadIds->count() - $emailedLeads;
    }

    private function getLeadsNotTexted($source, $startDate, $endDate, $leadTypes, $userIds)
    {
        $newLeadsQuery = Person::where('source', $source)
            ->whereBetween('created_at', [$startDate, $endDate]);

        if ($leadTypes !== ['all']) {
            $newLeadsQuery->whereIn('stage_id', $leadTypes);
        }

        if (!empty($userIds)) {
            $newLeadsQuery->whereIn('assigned_user_id', $userIds);
        }

        $newLeadIds = $newLeadsQuery->pluck('id');

        $textedLeads = Person::whereIn('id', $newLeadIds)
            ->whereHas('texts', function ($q) {
                $q->where('is_incoming', false);
            })
            ->count();

        return $newLeadIds->count() - $textedLeads;
    }

    // Speed Calculation Methods
    private function getAverageSpeedToAction($source, $startDate, $endDate, $leadTypes, $userIds)
    {
        $leadsQuery = Person::where('source', $source)
            ->whereBetween('created_at', [$startDate, $endDate]);

        if ($leadTypes !== ['all']) {
            $leadsQuery->whereIn('stage_id', $leadTypes);
        }

        if (!empty($userIds)) {
            $leadsQuery->whereIn('assigned_user_id', $userIds);
        }

        $leads = $leadsQuery->get();
        $totalMinutes = 0;
        $count = 0;

        foreach ($leads as $lead) {
            $firstAction = $this->getFirstActionTime($lead->id);
            if ($firstAction) {
                $leadCreated = Carbon::parse($lead->created_at);
                $firstActionTime = Carbon::parse($firstAction);
                $totalMinutes += $leadCreated->diffInMinutes($firstActionTime);
                $count++;
            }
        }

        return $count > 0 ? round($totalMinutes / $count, 2) : 0;
    }

    private function getFirstActionTime($personId)
    {
        $firstCall = Call::where('person_id', $personId)->min('created_at');
        $firstEmail = Email::where('person_id', $personId)
            ->where('is_incoming', false)
            ->min('created_at');
        $firstText = TextMessage::where('person_id', $personId)
            ->where('is_incoming', false)
            ->min('created_at');

        $actions = array_filter([$firstCall, $firstEmail, $firstText]);
        return !empty($actions) ? min($actions) : null;
    }

    private function getAverageSpeedToFirstCall($source, $startDate, $endDate, $leadTypes, $userIds)
    {
        $leadsQuery = Person::where('source', $source)
            ->whereBetween('created_at', [$startDate, $endDate]);

        if ($leadTypes !== ['all']) {
            $leadsQuery->whereIn('stage_id', $leadTypes);
        }

        if (!empty($userIds)) {
            $leadsQuery->whereIn('assigned_user_id', $userIds);
        }

        $leads = $leadsQuery->get();
        $totalMinutes = 0;
        $count = 0;

        foreach ($leads as $lead) {
            $firstCall = Call::where('person_id', $lead->id)->min('created_at');
            if ($firstCall) {
                $leadCreated = Carbon::parse($lead->created_at);
                $firstCallTime = Carbon::parse($firstCall);
                $totalMinutes += $leadCreated->diffInMinutes($firstCallTime);
                $count++;
            }
        }

        return $count > 0 ? round($totalMinutes / $count, 2) : 0;
    }

    private function getAverageSpeedToFirstEmail($source, $startDate, $endDate, $leadTypes, $userIds)
    {
        $leadsQuery = Person::where('source', $source)
            ->whereBetween('created_at', [$startDate, $endDate]);

        if ($leadTypes !== ['all']) {
            $leadsQuery->whereIn('stage_id', $leadTypes);
        }

        if (!empty($userIds)) {
            $leadsQuery->whereIn('assigned_user_id', $userIds);
        }

        $leads = $leadsQuery->get();
        $totalMinutes = 0;
        $count = 0;

        foreach ($leads as $lead) {
            $firstEmail = Email::where('person_id', $lead->id)
                ->where('is_incoming', false)
                ->min('created_at');
            if ($firstEmail) {
                $leadCreated = Carbon::parse($lead->created_at);
                $firstEmailTime = Carbon::parse($firstEmail);
                $totalMinutes += $leadCreated->diffInMinutes($firstEmailTime);
                $count++;
            }
        }

        return $count > 0 ? round($totalMinutes / $count, 2) : 0;
    }

    private function getAverageSpeedToFirstText($source, $startDate, $endDate, $leadTypes, $userIds)
    {
        $leadsQuery = Person::where('source', $source)
            ->whereBetween('created_at', [$startDate, $endDate]);

        if ($leadTypes !== ['all']) {
            $leadsQuery->whereIn('stage_id', $leadTypes);
        }

        if (!empty($userIds)) {
            $leadsQuery->whereIn('assigned_user_id', $userIds);
        }

        $leads = $leadsQuery->get();
        $totalMinutes = 0;
        $count = 0;

        foreach ($leads as $lead) {
            $firstText = TextMessage::where('person_id', $lead->id)
                ->where('is_incoming', false)
                ->min('created_at');
            if ($firstText) {
                $leadCreated = Carbon::parse($lead->created_at);
                $firstTextTime = Carbon::parse($firstText);
                $totalMinutes += $leadCreated->diffInMinutes($firstTextTime);
                $count++;
            }
        }

        return $count > 0 ? round($totalMinutes / $count, 2) : 0;
    }

    // Contact Attempts Calculations
    private function getAverageContactAttempts($source, $startDate, $endDate, $leadTypes, $userIds)
    {
        $leadsQuery = Person::where('source', $source)
            ->whereBetween('created_at', [$startDate, $endDate]);

        if ($leadTypes !== ['all']) {
            $leadsQuery->whereIn('stage_id', $leadTypes);
        }

        if (!empty($userIds)) {
            $leadsQuery->whereIn('assigned_user_id', $userIds);
        }

        $leads = $leadsQuery->get();
        $totalAttempts = 0;

        foreach ($leads as $lead) {
            $calls = Call::where('person_id', $lead->id)->count();
            $emails = Email::where('person_id', $lead->id)
                ->where('is_incoming', false)
                ->count();
            $texts = TextMessage::where('person_id', $lead->id)
                ->where('is_incoming', false)
                ->count();
            $totalAttempts += ($calls + $emails + $texts);
        }

        return $leads->count() > 0 ? round($totalAttempts / $leads->count(), 2) : 0;
    }

    private function getAverageCallAttempts($source, $startDate, $endDate, $leadTypes, $userIds)
    {
        $leadsQuery = Person::where('source', $source)
            ->whereBetween('created_at', [$startDate, $endDate]);

        if ($leadTypes !== ['all']) {
            $leadsQuery->whereIn('stage_id', $leadTypes);
        }

        if (!empty($userIds)) {
            $leadsQuery->whereIn('assigned_user_id', $userIds);
        }

        $leads = $leadsQuery->get();
        $totalCalls = 0;

        foreach ($leads as $lead) {
            $calls = Call::where('person_id', $lead->id)->count();
            $totalCalls += $calls;
        }

        return $leads->count() > 0 ? round($totalCalls / $leads->count(), 2) : 0;
    }

    private function getAverageEmailAttempts($source, $startDate, $endDate, $leadTypes, $userIds)
    {
        $leadsQuery = Person::where('source', $source)
            ->whereBetween('created_at', [$startDate, $endDate]);

        if ($leadTypes !== ['all']) {
            $leadsQuery->whereIn('stage_id', $leadTypes);
        }

        if (!empty($userIds)) {
            $leadsQuery->whereIn('assigned_user_id', $userIds);
        }

        $leads = $leadsQuery->get();
        $totalEmails = 0;

        foreach ($leads as $lead) {
            $emails = Email::where('person_id', $lead->id)
                ->where('is_incoming', false)
                ->count();
            $totalEmails += $emails;
        }

        return $leads->count() > 0 ? round($totalEmails / $leads->count(), 2) : 0;
    }

    private function getAverageTextAttempts($source, $startDate, $endDate, $leadTypes, $userIds)
    {
        $leadsQuery = Person::where('source', $source)
            ->whereBetween('created_at', [$startDate, $endDate]);

        if ($leadTypes !== ['all']) {
            $leadsQuery->whereIn('stage_id', $leadTypes);
        }

        if (!empty($userIds)) {
            $leadsQuery->whereIn('assigned_user_id', $userIds);
        }

        $leads = $leadsQuery->get();
        $totalTexts = 0;

        foreach ($leads as $lead) {
            $texts = TextMessage::where('person_id', $lead->id)
                ->where('is_incoming', false)
                ->count();
            $totalTexts += $texts;
        }

        return $leads->count() > 0 ? round($totalTexts / $leads->count(), 2) : 0;
    }

    // Response Rate Calculations
    private function getResponseRate($source, $startDate, $endDate, $leadTypes, $userIds)
    {
        $leadsQuery = Person::where('source', $source)
            ->whereBetween('created_at', [$startDate, $endDate]);

        if ($leadTypes !== ['all']) {
            $leadsQuery->whereIn('stage_id', $leadTypes);
        }

        if (!empty($userIds)) {
            $leadsQuery->whereIn('assigned_user_id', $userIds);
        }

        $leads = $leadsQuery->get();
        $respondedCount = 0;

        foreach ($leads as $lead) {
            $responded = $this->hasResponseAfterOutgoing(Email::class, $lead->id)
                || $this->hasResponseAfterOutgoing(Call::class, $lead->id)
                || $this->hasResponseAfterOutgoing(TextMessage::class, $lead->id);
            if ($responded) {
                $respondedCount++;
            }
        }

        return $leads->count() > 0 ? round(($respondedCount / $leads->count()) * 100, 2) : 0;
    }

    private function getEmailResponseRate($source, $startDate, $endDate, $leadTypes, $userIds)
    {
        return $this->getChannelResponseRate(Email::class, $source, $startDate, $endDate, $leadTypes, $userIds);
    }

    private function getPhoneResponseRate($source, $startDate, $endDate, $leadTypes, $userIds)
    {
        return $this->getChannelResponseRate(Call::class, $source, $startDate, $endDate, $leadTypes, $userIds);
    }

    private function getTextResponseRate($source, $startDate, $endDate, $leadTypes, $userIds)
    {
        return $this->getChannelResponseRate(TextMessage::class, $source, $startDate, $endDate, $leadTypes, $userIds);
    }

    private function getChannelResponseRate($modelClass, $source, $startDate, $endDate, $leadTypes, $userIds)
    {
        $leadsQuery = Person::where('source', $source)
            ->whereBetween('created_at', [$startDate, $endDate]);

        if ($leadTypes !== ['all']) {
            $leadsQuery->whereIn('stage_id', $leadTypes);
        }

        if (!empty($userIds)) {
            $leadsQuery->whereIn('assigned_user_id', $userIds);
        }

        $leads = $leadsQuery->get();
        $contactedLeads = 0;
        $respondedLeads = 0;

        foreach ($leads as $lead) {
            $hasOutgoing = $modelClass::where('person_id', $lead->id)
                ->where('is_incoming', false)
                ->exists();
            if ($hasOutgoing) {
                $contactedLeads++;
                if ($this->hasResponseAfterOutgoing($modelClass, $lead->id)) {
                    $respondedLeads++;
                }
            }
        }

        return $contactedLeads > 0 ? round(($respondedLeads / $contactedLeads) * 100, 2) : 0;
    }

    private function hasResponseAfterOutgoing($modelClass, $leadId)
    {
        $firstOutgoing = $modelClass::where('person_id', $leadId)
            ->where('is_incoming', false)
            ->orderBy('created_at')
            ->first();

        if (!$firstOutgoing) {
            return false;
        }

        return $modelClass::where('person_id', $leadId)
            ->where('is_incoming', true)
            ->where('created_at', '>', $firstOutgoing->created_at)
            ->exists();
    }

    // Closed Deal Calculations
    private function getDealsClosedCount($source, $startDate, $endDate, $leadTypes, $userIds)
    {
        $query = Deal::whereHas('people', function ($q) use ($source, $leadTypes) {
            $q->where('source', $source);
            if ($leadTypes !== ['all']) {
                $q->whereIn('stage_id', $leadTypes);
            }
        })->whereBetween('projected_close_date', [$startDate, $endDate]);

        if (!empty($userIds)) {
            $query->whereHas('users', function ($q) use ($userIds) {
                $q->whereIn('users.id', $userIds);
            });
        }

        return $query->count();
    }

    private function getDealValue($source, $startDate, $endDate, $leadTypes, $userIds)
    {
        $query = Deal::whereHas('people', function ($q) use ($source, $leadTypes) {
            $q->where('source', $source);
            if ($leadTypes !== ['all']) {
                $q->whereIn('stage_id', $leadTypes);
            }
        })->whereBetween('projected_close_date', [$startDate, $endDate]);

        if (!empty($userIds)) {
            $query->whereHas('users', function ($q) use ($userIds) {
                $q->whereIn('users.id', $userIds);
            });
        }

        return $query->sum('price');
    }

    private function getDealCommission($source, $startDate, $endDate, $leadTypes, $userIds)
    {
        $query = Deal::whereHas('people', function ($q) use ($source, $leadTypes) {
            $q->where('source', $source);
            if ($leadTypes !== ['all']) {
                $q->whereIn('stage_id', $leadTypes);
            }
        })->whereBetween('projected_close_date', [$startDate, $endDate]);

        if (!empty($userIds)) {
            $query->whereHas('users', function ($q) use ($userIds) {
                $q->whereIn('users.id', $userIds);
            });
        }

        return $query->sum('commission_value');
    }

    private function getConversionRate($source, $startDate, $endDate, $leadTypes, $userIds)
    {
        $newLeads = $this->getNewLeadsCount($source, $startDate, $endDate, $leadTypes, $userIds);
        $dealsClosed = $this->getDealsClosedCount($source, $startDate, $endDate, $leadTypes, $userIds);

        return $newLeads > 0 ? round(($dealsClosed / $newLeads) * 100, 2) : 0;
    }

    // Website Activity Calculations
    private function getWebsiteRegistrations($source, $startDate, $endDate, $userIds)
    {
        $query = Event::where('type', Event::TYPE_REGISTRATION)
            ->where('source', $source)
            ->whereBetween('occurred_at', [$startDate, $endDate]);

        if (!empty($userIds)) {
            $query->whereHas('person', function ($q) use ($userIds) {
                $q->whereIn('assigned_user_id', $userIds);
            });
        }

        return $query->count();
    }

    private function getInquiriesCount($source, $startDate, $endDate, $userIds)
    {
        $query = Event::whereIn('type', [
            Event::TYPE_INQUIRY,
            Event::TYPE_SELLER_INQUIRY,
            Event::TYPE_PROPERTY_INQUIRY,
            Event::TYPE_GENERAL_INQUIRY,
        ])->where('source', $source)
            ->whereBetween('occurred_at', [$startDate, $endDate]);

        if (!empty($userIds)) {
            $query->whereHas('person', function ($q) use ($userIds) {
                $q->whereIn('assigned_user_id', $userIds);
            });
        }

        return $query->count();
    }

    private function getPropertiesViewedCount($source, $startDate, $endDate, $userIds)
    {
        $query = Event::where('type', Event::TYPE_VIEWED_PROPERTY)
            ->where('source', $source)
            ->whereBetween('occurred_at', [$startDate, $endDate]);

        if (!empty($userIds)) {
            $query->whereHas('person', function ($q) use ($userIds) {
                $q->whereIn('assigned_user_id', $userIds);
            });
        }

        return $query->count();
    }

    private function getPropertiesSavedCount($source, $startDate, $endDate, $userIds)
    {
        $query = Event::where('type', Event::TYPE_SAVED_PROPERTY)
            ->where('source', $source)
            ->whereBetween('occurred_at', [$startDate, $endDate]);

        if (!empty($userIds)) {
            $query->whereHas('person', function ($q) use ($userIds) {
                $q->whereIn('assigned_user_id', $userIds);
            });
        }

        return $query->count();
    }

    private function getPageViewsCount($source, $startDate, $endDate, $userIds)
    {
        $query = Event::where('type', Event::TYPE_VIEWED_PAGE)
            ->where('source', $source)
            ->whereBetween('occurred_at', [$startDate, $endDate]);

        if (!empty($userIds)) {
            $query->whereHas('person', function ($q) use ($userIds) {
                $q->whereIn('assigned_user_id', $userIds);
            });
        }

        return $query->count();
    }

    // Total Metrics
    private function getTotalMetrics($startDate, $endDate, $leadSources, $leadTypes, $userIds)
    {
        $query = Person::select('source')->distinct();

        if ($leadSources !== ['all']) {
            $query->whereIn('source', $leadSources);
        }

        if (!empty($userIds)) {
            $query->whereIn('assigned_user_id', $userIds);
        }

        $sources = $query->pluck('source')->toArray();

        $totals = [
            'new_leads' => 0,
            'calls' => 0,
            'emails' => 0,
            'texts' => 0,
            'notes' => 0,
            'tasks_completed' => 0,
            'appointments' => 0,
            'leads_not_acted_on' => 0,
            'leads_not_called' => 0,
            'leads_not_emailed' => 0,
            'leads_not_texted' => 0,
            'avg_speed_to_action' => 0,
            'avg_speed_to_first_call' => 0,
            'avg_speed_to_first_email' => 0,
            'avg_speed_to_first_text' => 0,
            'avg_contact_attempts' => 0,
            'avg_call_attempts' => 0,
            'avg_email_attempts' => 0,
            'avg_text_attempts' => 0,
            'response_rate' => 0,
            'email_response_rate' => 0,
            'phone_response_rate' => 0,
            'text_response_rate' => 0,
            'deals_closed' => 0,
            'deal_value' => 0,
            'deal_commission' => 0,
            'conversion_rate' => 0,
            'website_registrations' => 0,
            'inquiries' => 0,
            'properties_viewed' => 0,
            'properties_saved' => 0,
            'page_views' => 0,
        ];

        foreach ($sources as $source) {
            $totals['new_leads'] += $this->getNewLeadsCount($source, $startDate, $endDate, $leadTypes, $userIds);
            $totals['calls'] += $this->getCallsCount($source, $startDate, $endDate, $userIds);
            $totals['emails'] += $this->getEmailsCount($source, $startDate, $endDate, $userIds);
            $totals['texts'] += $this->getTextsCount($source, $startDate, $endDate, $userIds);
            $totals['notes'] += $this->getNotesCount($source, $startDate, $endDate, $userIds);
            $totals['tasks_completed'] += $this->getTasksCompletedCount($source, $startDate, $endDate, $userIds);
            $totals['appointments'] += $this->getAppointmentsCount($source, $startDate, $endDate, $userIds);
            $totals['leads_not_acted_on'] += $this->getLeadsNotActedOn($source, $startDate, $endDate, $leadTypes, $userIds);
            $totals['leads_not_called'] += $this->getLeadsNotCalled($source, $startDate, $endDate, $leadTypes, $userIds);
            $totals['leads_not_emailed'] += $this->getLeadsNotEmailed($source, $startDate, $endDate, $leadTypes, $userIds);
            $totals['leads_not_texted'] += $this->getLeadsNotTexted($source, $startDate, $endDate, $leadTypes, $userIds);
            $totals['deals_closed'] += $this->getDealsClosedCount($source, $startDate, $endDate, $leadTypes, $userIds);
            $totals['deal_value'] += $this->getDealValue($source, $startDate, $endDate, $leadTypes, $userIds);
            $totals['deal_commission'] += $this->getDealCommission($source, $startDate, $endDate, $leadTypes, $userIds);
            $totals['website_registrations'] += $this->getWebsiteRegistrations($source, $startDate, $endDate, $userIds);
            $totals['inquiries'] += $this->getInquiriesCount($source, $startDate, $endDate, $userIds);
            $totals['properties_viewed'] += $this->getPropertiesViewedCount($source, $startDate, $endDate, $userIds);
            $totals['properties_saved'] += $this->getPropertiesSavedCount($source, $startDate, $endDate, $userIds);
            $totals['page_views'] += $this->getPageViewsCount($source, $startDate, $endDate, $userIds);
        }

        $sourceCount = count($sources);
        if ($sourceCount > 0) {
            $speedSum = $attemptSum = $responseSum = $conversionSum = 0;
            foreach ($sources as $source) {
                $speedSum += $this->getAverageSpeedToAction($source, $startDate, $endDate, $leadTypes, $userIds);
                $attemptSum += $this->getAverageContactAttempts($source, $startDate, $endDate, $leadTypes, $userIds);
                $responseSum += $this->getResponseRate($source, $startDate, $endDate, $leadTypes, $userIds);
                $conversionSum += $this->getConversionRate($source, $startDate, $endDate, $leadTypes, $userIds);
            }
            $totals['avg_speed_to_action'] = round($speedSum / $sourceCount, 2);
            $totals['avg_contact_attempts'] = round($attemptSum / $sourceCount, 2);
            $totals['response_rate'] = round($responseSum / $sourceCount, 2);
            $totals['conversion_rate'] = round($conversionSum / $sourceCount, 2);
        }

        return $totals;
    }

    // Time Series Data for Charts
    private function getTimeSeriesData($startDate, $endDate, $leadSources, $leadTypes, $userIds)
    {
        $timeSeriesData = [];
        $currentDate = $startDate->copy();

        while ($currentDate <= $endDate) {
            $dayStart = $currentDate->copy()->startOfDay();
            $dayEnd = $currentDate->copy()->endOfDay();

            $dayData = [
                'date' => $currentDate->format('Y-m-d'),
                'new_leads' => $this->getNewLeadsCountForDay($dayStart, $dayEnd, $leadSources, $leadTypes, $userIds),
                'calls' => $this->getCallsCountForDay($dayStart, $dayEnd, $leadSources, $userIds),
                'emails' => $this->getEmailsCountForDay($dayStart, $dayEnd, $leadSources, $userIds),
                'texts' => $this->getTextsCountForDay($dayStart, $dayEnd, $leadSources, $userIds),
                'appointments' => $this->getAppointmentsCountForDay($dayStart, $dayEnd, $leadSources, $userIds),
                'deals_closed' => $this->getDealsClosedCountForDay($dayStart, $dayEnd, $leadSources, $leadTypes, $userIds),
                'website_registrations' => $this->getWebsiteRegistrationsForDay($dayStart, $dayEnd, $leadSources, $userIds),
                'inquiries' => $this->getInquiriesCountForDay($dayStart, $dayEnd, $leadSources, $userIds),
                'properties_viewed' => $this->getPropertiesViewedCountForDay($dayStart, $dayEnd, $leadSources, $userIds),
                'properties_saved' => $this->getPropertiesSavedCountForDay($dayStart, $dayEnd, $leadSources, $userIds),
                'page_views' => $this->getPageViewsCountForDay($dayStart, $dayEnd, $leadSources, $userIds),
            ];

            $timeSeriesData[] = $dayData;
            $currentDate->addDay();
        }

        return $timeSeriesData;
    }

    private function getNewLeadsCountForDay($dayStart, $dayEnd, $leadSources, $leadTypes, $userIds)
    {
        $query = Person::whereBetween('created_at', [$dayStart, $dayEnd]);

        if ($leadSources !== ['all']) {
            $query->whereIn('source', $leadSources);
        }

        if ($leadTypes !== ['all']) {
            $query->whereIn('stage_id', $leadTypes);
        }

        if (!empty($userIds)) {
            $query->whereIn('assigned_user_id', $userIds);
        }

        return $query->count();
    }

    private function getCallsCountForDay($dayStart, $dayEnd, $leadSources, $userIds)
    {
        $query = Call::whereBetween('created_at', [$dayStart, $dayEnd])
            ->whereHas('person', function ($q) use ($leadSources) {
                if ($leadSources !== ['all']) {
                    $q->whereIn('source', $leadSources);
                }
            });

        if (!empty($userIds)) {
            $query->whereIn('user_id', $userIds);
        }

        return $query->count();
    }

    private function getEmailsCountForDay($dayStart, $dayEnd, $leadSources, $userIds)
    {
        $query = Email::where('is_incoming', false)
            ->whereBetween('created_at', [$dayStart, $dayEnd])
            ->whereHas('person', function ($q) use ($leadSources) {
                if ($leadSources !== ['all']) {
                    $q->whereIn('source', $leadSources);
                }
            });

        if (!empty($userIds)) {
            $query->whereIn('user_id', $userIds);
        }

        return $query->count();
    }

    private function getTextsCountForDay($dayStart, $dayEnd, $leadSources, $userIds)
    {
        $query = TextMessage::where('is_incoming', false)
            ->whereBetween('created_at', [$dayStart, $dayEnd])
            ->whereHas('person', function ($q) use ($leadSources) {
                if ($leadSources !== ['all']) {
                    $q->whereIn('source', $leadSources);
                }
            });

        if (!empty($userIds)) {
            $query->whereIn('user_id', $userIds);
        }

        return $query->count();
    }

    private function getAppointmentsCountForDay($dayStart, $dayEnd, $leadSources, $userIds)
    {
        $query = Appointment::whereBetween('created_at', [$dayStart, $dayEnd])
            ->whereHas('invitedPeople', function ($q) use ($leadSources) {
                if ($leadSources !== ['all']) {
                    $q->whereIn('source', $leadSources);
                }
            });

        if (!empty($userIds)) {
            $query->whereIn('created_by_id', $userIds);
        }

        return $query->count();
    }

    private function getDealsClosedCountForDay($dayStart, $dayEnd, $leadSources, $leadTypes, $userIds)
    {
        $query = Deal::whereHas('people', function ($q) use ($leadSources, $leadTypes) {
            if ($leadSources !== ['all']) {
                $q->whereIn('source', $leadSources);
            }
            if ($leadTypes !== ['all']) {
                $q->whereIn('stage_id', $leadTypes);
            }
        })->whereBetween('projected_close_date', [$dayStart, $dayEnd]);

        if (!empty($userIds)) {
            $query->whereHas('users', function ($q) use ($userIds) {
                $q->whereIn('users.id', $userIds);
            });
        }

        return $query->count();
    }

    private function getWebsiteRegistrationsForDay($dayStart, $dayEnd, $leadSources, $userIds)
    {
        $query = Event::where('type', Event::TYPE_REGISTRATION)
            ->whereBetween('occurred_at', [$dayStart, $dayEnd]);

        if ($leadSources !== ['all']) {
            $query->whereIn('source', $leadSources);
        }

        if (!empty($userIds)) {
            $query->whereHas('person', function ($q) use ($userIds) {
                $q->whereIn('assigned_user_id', $userIds);
            });
        }

        return $query->count();
    }

    private function getInquiriesCountForDay($dayStart, $dayEnd, $leadSources, $userIds)
    {
        $query = Event::whereIn('type', [
            Event::TYPE_INQUIRY,
            Event::TYPE_SELLER_INQUIRY,
            Event::TYPE_PROPERTY_INQUIRY,
            Event::TYPE_GENERAL_INQUIRY,
        ])->whereBetween('occurred_at', [$dayStart, $dayEnd]);

        if ($leadSources !== ['all']) {
            $query->whereIn('source', $leadSources);
        }

        if (!empty($userIds)) {
            $query->whereHas('person', function ($q) use ($userIds) {
                $q->whereIn('assigned_user_id', $userIds);
            });
        }

        return $query->count();
    }

    private function getPropertiesViewedCountForDay($dayStart, $dayEnd, $leadSources, $userIds)
    {
        $query = Event::where('type', Event::TYPE_VIEWED_PROPERTY)
            ->whereBetween('occurred_at', [$dayStart, $dayEnd]);

        if ($leadSources !== ['all']) {
            $query->whereIn('source', $leadSources);
        }

        if (!empty($userIds)) {
            $query->whereHas('person', function ($q) use ($userIds) {
                $q->whereIn('assigned_user_id', $userIds);
            });
        }

        return $query->count();
    }

    private function getPropertiesSavedCountForDay($dayStart, $dayEnd, $leadSources, $userIds)
    {
        $query = Event::where('type', Event::TYPE_SAVED_PROPERTY)
            ->whereBetween('occurred_at', [$dayStart, $dayEnd]);

        if ($leadSources !== ['all']) {
            $query->whereIn('source', $leadSources);
        }

        if (!empty($userIds)) {
            $query->whereHas('person', function ($q) use ($userIds) {
                $q->whereIn('assigned_user_id', $userIds);
            });
        }

        return $query->count();
    }

    private function getPageViewsCountForDay($dayStart, $dayEnd, $leadSources, $userIds)
    {
        $query = Event::where('type', Event::TYPE_VIEWED_PAGE)
            ->whereBetween('occurred_at', [$dayStart, $dayEnd]);

        if ($leadSources !== ['all']) {
            $query->whereIn('source', $leadSources);
        }

        if (!empty($userIds)) {
            $query->whereHas('person', function ($q) use ($userIds) {
                $q->whereIn('assigned_user_id', $userIds);
            });
        }

        return $query->count();
    }
}
