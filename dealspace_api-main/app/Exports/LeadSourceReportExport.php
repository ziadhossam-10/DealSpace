<?php

namespace App\Exports;

use App\Models\Person;
use App\Models\Deal;
use App\Models\Event;
use App\Models\Call;
use App\Models\Email;
use App\Models\Note;
use App\Models\Task;
use App\Models\Appointment;
use App\Models\TextMessage;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Maatwebsite\Excel\Concerns\Exportable;
use Carbon\Carbon;

class LeadSourceReportExport implements FromCollection, WithHeadings, WithMapping, WithStyles, ShouldAutoSize
{
    use Exportable;

    protected $startDate;
    protected $endDate;
    protected $leadSources;
    protected $leadTypes;
    protected $userIds;

    public function __construct(array $params = [])
    {
        $this->startDate = Carbon::parse($params['start_date'] ?? now()->startOfMonth());
        $this->endDate = Carbon::parse($params['end_date'] ?? now()->endOfMonth());
        $this->leadSources = $params['lead_sources'] ?? ['all'];
        $this->leadTypes = $params['lead_types'] ?? ['all'];
        $this->userIds = $params['user_ids'] ?? [];
    }

    public function collection()
    {
        $query = Person::select('source')->distinct();

        if ($this->leadSources !== ['all']) {
            $query->whereIn('source', $this->leadSources);
        }

        if (!empty($this->userIds)) {
            $query->whereIn('assigned_user_id', $this->userIds);
        }

        return $query->get();
    }

    public function headings(): array
    {
        return [
            'Lead Source',
            'New Leads',
            'Calls Made',
            'Emails Sent',
            'Texts Sent',
            'Notes Added',
            'Tasks Completed',
            'Appointments',
            'Leads Not Acted On',
            'Leads Not Called',
            'Leads Not Emailed',
            'Leads Not Texted',
            'Avg Speed to First Action (min)',
            'Avg Speed to First Call (min)',
            'Avg Speed to First Email (min)',
            'Avg Speed to First Text (min)',
            'Avg Contact Attempts per Lead',
            'Avg Call Attempts per Lead',
            'Avg Email Attempts per Lead',
            'Avg Text Attempts per Lead',
            'Response Rate (%)',
            'Email Response Rate (%)',
            'Phone Response Rate (%)',
            'Text Response Rate (%)',
            'Deals Closed',
            'Deal Value',
            'Deal Commission',
            'Conversion Rate (%)',
            'Website Registrations',
            'Inquiries',
            'Properties Viewed',
            'Properties Saved',
            'Page Views',
        ];
    }

    public function map($source): array
    {
        $sourceName = $source->source;
        return [
            $sourceName,
            $this->getNewLeadsCount($sourceName),
            $this->getCallsCount($sourceName),
            $this->getEmailsCount($sourceName),
            $this->getTextsCount($sourceName),
            $this->getNotesCount($sourceName),
            $this->getTasksCompletedCount($sourceName),
            $this->getAppointmentsCount($sourceName),
            $this->getLeadsNotActedOn($sourceName),
            $this->getLeadsNotCalled($sourceName),
            $this->getLeadsNotEmailed($sourceName),
            $this->getLeadsNotTexted($sourceName),
            $this->getAverageSpeedToAction($sourceName),
            $this->getAverageSpeedToFirstCall($sourceName),
            $this->getAverageSpeedToFirstEmail($sourceName),
            $this->getAverageSpeedToFirstText($sourceName),
            $this->getAverageContactAttempts($sourceName),
            $this->getAverageCallAttempts($sourceName),
            $this->getAverageEmailAttempts($sourceName),
            $this->getAverageTextAttempts($sourceName),
            $this->getResponseRate($sourceName),
            $this->getEmailResponseRate($sourceName),
            $this->getPhoneResponseRate($sourceName),
            $this->getTextResponseRate($sourceName),
            $this->getDealsClosedCount($sourceName),
            $this->getDealValue($sourceName),
            $this->getDealCommission($sourceName),
            $this->getConversionRate($sourceName),
            $this->getWebsiteRegistrations($sourceName),
            $this->getInquiriesCount($sourceName),
            $this->getPropertiesViewedCount($sourceName),
            $this->getPropertiesSavedCount($sourceName),
            $this->getPageViewsCount($sourceName),
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

    // Lead Count Calculations
    private function getNewLeadsCount($source)
    {
        $query = Person::where('source', $source)
            ->whereBetween('created_at', [$this->startDate, $this->endDate]);

        if ($this->leadTypes !== ['all']) {
            $query->whereIn('stage_id', $this->leadTypes);
        }

        if (!empty($this->userIds)) {
            $query->whereIn('assigned_user_id', $this->userIds);
        }

        return $query->count();
    }

    // Activity Count Calculations
    private function getCallsCount($source)
    {
        $query = Call::whereHas('person', function ($q) use ($source) {
            $q->where('source', $source);
        })->whereBetween('created_at', [$this->startDate, $this->endDate]);

        if (!empty($this->userIds)) {
            $query->whereIn('user_id', $this->userIds);
        }

        return $query->count();
    }

    private function getEmailsCount($source)
    {
        $query = Email::whereHas('person', function ($q) use ($source) {
            $q->where('source', $source);
        })->where('is_incoming', false)
            ->whereBetween('created_at', [$this->startDate, $this->endDate]);

        if (!empty($this->userIds)) {
            $query->whereIn('user_id', $this->userIds);
        }

        return $query->count();
    }

    private function getTextsCount($source)
    {
        $query = TextMessage::whereHas('person', function ($q) use ($source) {
            $q->where('source', $source);
        })->where('is_incoming', false)
            ->whereBetween('created_at', [$this->startDate, $this->endDate]);

        if (!empty($this->userIds)) {
            $query->whereIn('user_id', $this->userIds);
        }

        return $query->count();
    }

    private function getNotesCount($source)
    {
        $query = Note::whereHas('person', function ($q) use ($source) {
            $q->where('source', $source);
        })->whereBetween('created_at', [$this->startDate, $this->endDate]);

        if (!empty($this->userIds)) {
            $query->whereIn('created_by', $this->userIds);
        }

        return $query->count();
    }

    private function getTasksCompletedCount($source)
    {
        $query = Task::whereHas('person', function ($q) use ($source) {
            $q->where('source', $source);
        })->where('is_completed', true)
            ->whereBetween('updated_at', [$this->startDate, $this->endDate]);

        if (!empty($this->userIds)) {
            $query->whereIn('assigned_user_id', $this->userIds);
        }

        return $query->count();
    }

    private function getAppointmentsCount($source)
    {
        $query = Appointment::whereHas('invitedPeople', function ($q) use ($source) {
            $q->where('source', $source);
        })->whereBetween('created_at', [$this->startDate, $this->endDate]);

        if (!empty($this->userIds)) {
            $query->whereIn('created_by_id', $this->userIds);
        }

        return $query->count();
    }

    // Response Tracking Calculations
    private function getLeadsNotActedOn($source)
    {
        $newLeadsQuery = Person::where('source', $source)
            ->whereBetween('created_at', [$this->startDate, $this->endDate]);

        if ($this->leadTypes !== ['all']) {
            $newLeadsQuery->whereIn('stage_id', $this->leadTypes);
        }

        if (!empty($this->userIds)) {
            $newLeadsQuery->whereIn('assigned_user_id', $this->userIds);
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

    private function getLeadsNotCalled($source)
    {
        $newLeadsQuery = Person::where('source', $source)
            ->whereBetween('created_at', [$this->startDate, $this->endDate]);

        if ($this->leadTypes !== ['all']) {
            $newLeadsQuery->whereIn('stage_id', $this->leadTypes);
        }

        if (!empty($this->userIds)) {
            $newLeadsQuery->whereIn('assigned_user_id', $this->userIds);
        }

        $newLeadIds = $newLeadsQuery->pluck('id');

        $calledLeads = Person::whereIn('id', $newLeadIds)
            ->whereHas('calls')
            ->count();

        return $newLeadIds->count() - $calledLeads;
    }

    private function getLeadsNotEmailed($source)
    {
        $newLeadsQuery = Person::where('source', $source)
            ->whereBetween('created_at', [$this->startDate, $this->endDate]);

        if ($this->leadTypes !== ['all']) {
            $newLeadsQuery->whereIn('stage_id', $this->leadTypes);
        }

        if (!empty($this->userIds)) {
            $newLeadsQuery->whereIn('assigned_user_id', $this->userIds);
        }

        $newLeadIds = $newLeadsQuery->pluck('id');

        $emailedLeads = Person::whereIn('id', $newLeadIds)
            ->whereHas('emails', function ($q) {
                $q->where('is_incoming', false);
            })
            ->count();

        return $newLeadIds->count() - $emailedLeads;
    }

    private function getLeadsNotTexted($source)
    {
        $newLeadsQuery = Person::where('source', $source)
            ->whereBetween('created_at', [$this->startDate, $this->endDate]);

        if ($this->leadTypes !== ['all']) {
            $newLeadsQuery->whereIn('stage_id', $this->leadTypes);
        }

        if (!empty($this->userIds)) {
            $newLeadsQuery->whereIn('assigned_user_id', $this->userIds);
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
    private function getAverageSpeedToAction($source)
    {
        $leadsQuery = Person::where('source', $source)
            ->whereBetween('created_at', [$this->startDate, $this->endDate]);

        if ($this->leadTypes !== ['all']) {
            $leadsQuery->whereIn('stage_id', $this->leadTypes);
        }

        if (!empty($this->userIds)) {
            $leadsQuery->whereIn('assigned_user_id', $this->userIds);
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

    private function getAverageSpeedToFirstCall($source)
    {
        $leadsQuery = Person::where('source', $source)
            ->whereBetween('created_at', [$this->startDate, $this->endDate]);

        if ($this->leadTypes !== ['all']) {
            $leadsQuery->whereIn('stage_id', $this->leadTypes);
        }

        if (!empty($this->userIds)) {
            $leadsQuery->whereIn('assigned_user_id', $this->userIds);
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

    private function getAverageSpeedToFirstEmail($source)
    {
        $leadsQuery = Person::where('source', $source)
            ->whereBetween('created_at', [$this->startDate, $this->endDate]);

        if ($this->leadTypes !== ['all']) {
            $leadsQuery->whereIn('stage_id', $this->leadTypes);
        }

        if (!empty($this->userIds)) {
            $leadsQuery->whereIn('assigned_user_id', $this->userIds);
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

    private function getAverageSpeedToFirstText($source)
    {
        $leadsQuery = Person::where('source', $source)
            ->whereBetween('created_at', [$this->startDate, $this->endDate]);

        if ($this->leadTypes !== ['all']) {
            $leadsQuery->whereIn('stage_id', $this->leadTypes);
        }

        if (!empty($this->userIds)) {
            $leadsQuery->whereIn('assigned_user_id', $this->userIds);
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
    private function getAverageContactAttempts($source)
    {
        $leadsQuery = Person::where('source', $source)
            ->whereBetween('created_at', [$this->startDate, $this->endDate]);

        if ($this->leadTypes !== ['all']) {
            $leadsQuery->whereIn('stage_id', $this->leadTypes);
        }

        if (!empty($this->userIds)) {
            $leadsQuery->whereIn('assigned_user_id', $this->userIds);
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

    private function getAverageCallAttempts($source)
    {
        $leadsQuery = Person::where('source', $source)
            ->whereBetween('created_at', [$this->startDate, $this->endDate]);

        if ($this->leadTypes !== ['all']) {
            $leadsQuery->whereIn('stage_id', $this->leadTypes);
        }

        if (!empty($this->userIds)) {
            $leadsQuery->whereIn('assigned_user_id', $this->userIds);
        }

        $leads = $leadsQuery->get();
        $totalCalls = 0;

        foreach ($leads as $lead) {
            $calls = Call::where('person_id', $lead->id)->count();
            $totalCalls += $calls;
        }

        return $leads->count() > 0 ? round($totalCalls / $leads->count(), 2) : 0;
    }

    private function getAverageEmailAttempts($source)
    {
        $leadsQuery = Person::where('source', $source)
            ->whereBetween('created_at', [$this->startDate, $this->endDate]);

        if ($this->leadTypes !== ['all']) {
            $leadsQuery->whereIn('stage_id', $this->leadTypes);
        }

        if (!empty($this->userIds)) {
            $leadsQuery->whereIn('assigned_user_id', $this->userIds);
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

    private function getAverageTextAttempts($source)
    {
        $leadsQuery = Person::where('source', $source)
            ->whereBetween('created_at', [$this->startDate, $this->endDate]);

        if ($this->leadTypes !== ['all']) {
            $leadsQuery->whereIn('stage_id', $this->leadTypes);
        }

        if (!empty($this->userIds)) {
            $leadsQuery->whereIn('assigned_user_id', $this->userIds);
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
    private function getResponseRate($source)
    {
        $leadsQuery = Person::where('source', $source)
            ->whereBetween('created_at', [$this->startDate, $this->endDate]);

        if ($this->leadTypes !== ['all']) {
            $leadsQuery->whereIn('stage_id', $this->leadTypes);
        }

        if (!empty($this->userIds)) {
            $leadsQuery->whereIn('assigned_user_id', $this->userIds);
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

    private function getEmailResponseRate($source)
    {
        return $this->getChannelResponseRate(Email::class, $source);
    }

    private function getPhoneResponseRate($source)
    {
        return $this->getChannelResponseRate(Call::class, $source);
    }

    private function getTextResponseRate($source)
    {
        return $this->getChannelResponseRate(TextMessage::class, $source);
    }

    private function getChannelResponseRate($modelClass, $source)
    {
        $leadsQuery = Person::where('source', $source)
            ->whereBetween('created_at', [$this->startDate, $this->endDate]);

        if ($this->leadTypes !== ['all']) {
            $leadsQuery->whereIn('stage_id', $this->leadTypes);
        }

        if (!empty($this->userIds)) {
            $leadsQuery->whereIn('assigned_user_id', $this->userIds);
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
    private function getDealsClosedCount($source)
    {
        $query = Deal::whereHas('people', function ($q) use ($source) {
            $q->where('source', $source);
            if ($this->leadTypes !== ['all']) {
                $q->whereIn('stage_id', $this->leadTypes);
            }
        })->whereBetween('projected_close_date', [$this->startDate, $this->endDate]);

        if (!empty($this->userIds)) {
            $query->whereHas('users', function ($q) {
                $q->whereIn('users.id', $this->userIds);
            });
        }

        return $query->count();
    }

    private function getDealValue($source)
    {
        $query = Deal::whereHas('people', function ($q) use ($source) {
            $q->where('source', $source);
            if ($this->leadTypes !== ['all']) {
                $q->whereIn('stage_id', $this->leadTypes);
            }
        })->whereBetween('projected_close_date', [$this->startDate, $this->endDate]);

        if (!empty($this->userIds)) {
            $query->whereHas('users', function ($q) {
                $q->whereIn('users.id', $this->userIds);
            });
        }

        return $query->sum('price');
    }

    private function getDealCommission($source)
    {
        $query = Deal::whereHas('people', function ($q) use ($source) {
            $q->where('source', $source);
            if ($this->leadTypes !== ['all']) {
                $q->whereIn('stage_id', $this->leadTypes);
            }
        })->whereBetween('projected_close_date', [$this->startDate, $this->endDate]);

        if (!empty($this->userIds)) {
            $query->whereHas('users', function ($q) {
                $q->whereIn('users.id', $this->userIds);
            });
        }

        return $query->sum('commission_value');
    }

    private function getConversionRate($source)
    {
        $newLeads = $this->getNewLeadsCount($source);
        $dealsClosed = $this->getDealsClosedCount($source);

        return $newLeads > 0 ? round(($dealsClosed / $newLeads) * 100, 2) : 0;
    }

    // Website Activity Calculations
    private function getWebsiteRegistrations($source)
    {
        $query = Event::where('type', Event::TYPE_REGISTRATION)
            ->where('source', $source)
            ->whereBetween('occurred_at', [$this->startDate, $this->endDate]);

        if (!empty($this->userIds)) {
            $query->whereHas('person', function ($q) {
                $q->whereIn('assigned_user_id', $this->userIds);
            });
        }

        return $query->count();
    }

    private function getInquiriesCount($source)
    {
        $query = Event::whereIn('type', [
            Event::TYPE_INQUIRY,
            Event::TYPE_SELLER_INQUIRY,
            Event::TYPE_PROPERTY_INQUIRY,
            Event::TYPE_GENERAL_INQUIRY,
        ])->where('source', $source)
            ->whereBetween('occurred_at', [$this->startDate, $this->endDate]);

        if (!empty($this->userIds)) {
            $query->whereHas('person', function ($q) {
                $q->whereIn('assigned_user_id', $this->userIds);
            });
        }

        return $query->count();
    }

    private function getPropertiesViewedCount($source)
    {
        $query = Event::where('type', Event::TYPE_VIEWED_PROPERTY)
            ->where('source', $source)
            ->whereBetween('occurred_at', [$this->startDate, $this->endDate]);

        if (!empty($this->userIds)) {
            $query->whereHas('person', function ($q) {
                $q->whereIn('assigned_user_id', $this->userIds);
            });
        }

        return $query->count();
    }

    private function getPropertiesSavedCount($source)
    {
        $query = Event::where('type', Event::TYPE_SAVED_PROPERTY)
            ->where('source', $source)
            ->whereBetween('occurred_at', [$this->startDate, $this->endDate]);

        if (!empty($this->userIds)) {
            $query->whereHas('person', function ($q) {
                $q->whereIn('assigned_user_id', $this->userIds);
            });
        }

        return $query->count();
    }

    private function getPageViewsCount($source)
    {
        $query = Event::where('type', Event::TYPE_VIEWED_PAGE)
            ->where('source', $source)
            ->whereBetween('occurred_at', [$this->startDate, $this->endDate]);

        if (!empty($this->userIds)) {
            $query->whereHas('person', function ($q) {
                $q->whereIn('assigned_user_id', $this->userIds);
            });
        }

        return $query->count();
    }
}
