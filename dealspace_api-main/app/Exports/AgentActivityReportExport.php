<?php

namespace App\Exports;

use App\Enums\RoleEnum;
use App\Models\User;
use App\Models\Person;
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

class AgentActivityReportExport implements FromCollection, WithHeadings, WithMapping, WithStyles, ShouldAutoSize
{
    use Exportable;

    protected $startDate;
    protected $endDate;
    protected $agentIds;
    protected $leadTypes;

    public function __construct(array $params = [])
    {
        $this->startDate = Carbon::parse($params['start_date'] ?? now()->startOfMonth());
        $this->endDate = Carbon::parse($params['end_date'] ?? now()->endOfMonth());
        $this->agentIds = $params['agent_ids'] ?? [];
        $this->leadTypes = $params['lead_types'] ?? ['all'];
    }

    public function collection()
    {
        $agentQuery = User::where('role', RoleEnum::AGENT);

        if (!empty($this->agentIds)) {
            $agentQuery->whereIn('id', $this->agentIds);
        }

        return $agentQuery->get();
    }

    public function headings(): array
    {
        return [
            'Agent ID',
            'Agent Name',
            'Email',
            'New Leads',
            'Initially Assigned Leads',
            'Currently Assigned Leads',
            'Calls Made',
            'Emails Sent',
            'Texts Sent',
            'Notes Added',
            'Tasks Completed',
            'Appointments Attended',
            'Appointments Set',
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
            'Text Response Rate (%)'
        ];
    }

    public function map($agent): array
    {
        return [
            $agent->id,
            $agent->name,
            $agent->email,
            $this->getNewLeadsCount($agent->id),
            $this->getInitiallyAssignedLeadsCount($agent->id),
            $this->getCurrentlyAssignedLeadsCount($agent->id),
            $this->getCallsCount($agent->id),
            $this->getEmailsCount($agent->id),
            $this->getTextsCount($agent->id),
            $this->getNotesCount($agent->id),
            $this->getTasksCompletedCount($agent->id),
            $this->getAppointmentsCount($agent->id),
            $this->getAppointmentsSetCount($agent->id),
            $this->getLeadsNotActedOn($agent->id),
            $this->getLeadsNotCalled($agent->id),
            $this->getLeadsNotEmailed($agent->id),
            $this->getLeadsNotTexted($agent->id),
            $this->getAverageSpeedToAction($agent->id),
            $this->getAverageSpeedToFirstCall($agent->id),
            $this->getAverageSpeedToFirstEmail($agent->id),
            $this->getAverageSpeedToFirstText($agent->id),
            $this->getAverageContactAttempts($agent->id),
            $this->getAverageCallAttempts($agent->id),
            $this->getAverageEmailAttempts($agent->id),
            $this->getAverageTextAttempts($agent->id),
            $this->getResponseRate($agent->id),
            $this->getEmailResponseRate($agent->id),
            $this->getPhoneResponseRate($agent->id),
            $this->getTextResponseRate($agent->id)
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'E0E0E0']
                ]
            ],
        ];
    }

    // Lead Count Calculations
    private function getNewLeadsCount($agentId)
    {
        $query = Person::where('assigned_user_id', $agentId)
            ->whereBetween('created_at', [$this->startDate, $this->endDate]);

        if ($this->leadTypes !== ['all']) {
            $query->whereIn('stage_id', $this->leadTypes);
        }

        return $query->count();
    }

    private function getInitiallyAssignedLeadsCount($agentId)
    {
        $query = Person::where('initial_assigned_user_id', $agentId)
            ->whereBetween('created_at', [$this->startDate, $this->endDate]);

        if ($this->leadTypes !== ['all']) {
            $query->whereIn('stage_id', $this->leadTypes);
        }

        return $query->count();
    }

    private function getCurrentlyAssignedLeadsCount($agentId)
    {
        $query = Person::where('assigned_user_id', $agentId);

        if ($this->leadTypes !== ['all']) {
            $query->whereIn('stage_id', $this->leadTypes);
        }

        return $query->count();
    }

    // Activity Count Calculations
    private function getCallsCount($agentId)
    {
        return Call::where('user_id', $agentId)
            ->whereBetween('created_at', [$this->startDate, $this->endDate])
            ->count();
    }

    private function getEmailsCount($agentId)
    {
        return Email::where('user_id', $agentId)
            ->where('is_incoming', false)
            ->whereBetween('created_at', [$this->startDate, $this->endDate])
            ->count();
    }

    private function getTextsCount($agentId)
    {
        return TextMessage::where('user_id', $agentId)
            ->where('is_incoming', false)
            ->whereBetween('created_at', [$this->startDate, $this->endDate])
            ->count();
    }

    private function getNotesCount($agentId)
    {
        return Note::where('created_by', $agentId)
            ->whereBetween('created_at', [$this->startDate, $this->endDate])
            ->count();
    }

    private function getTasksCompletedCount($agentId)
    {
        return Task::where('assigned_user_id', $agentId)
            ->where('is_completed', true)
            ->whereBetween('updated_at', [$this->startDate, $this->endDate])
            ->count();
    }

    private function getAppointmentsCount($agentId)
    {
        return Appointment::whereHas('invitedUsers', function ($q) use ($agentId) {
            $q->where('user_id', $agentId)
                ->where('role', RoleEnum::AGENT);
        })
            ->whereBetween('created_at', [$this->startDate, $this->endDate])
            ->count();
    }

    private function getAppointmentsSetCount($agentId)
    {
        return Appointment::where('created_by_id', $agentId)
            ->whereBetween('created_at', [$this->startDate, $this->endDate])
            ->count();
    }

    // Response Tracking Calculations
    private function getLeadsNotActedOn($agentId)
    {
        $newLeadsQuery = Person::where('assigned_user_id', $agentId)
            ->whereBetween('created_at', [$this->startDate, $this->endDate]);

        if ($this->leadTypes !== ['all']) {
            $newLeadsQuery->whereIn('stage_id', $this->leadTypes);
        }

        $newLeadIds = $newLeadsQuery->pluck('id');

        $actedOnPeople = Person::whereIn('id', $newLeadIds)
            ->where(function ($query) {
                $query->whereHas('calls')
                    ->orWhereHas('emails')
                    ->orWhereHas('texts');
            })
            ->count();

        return $newLeadIds->count() - $actedOnPeople;
    }

    private function getLeadsNotCalled($agentId)
    {
        $newLeadsQuery = Person::where('assigned_user_id', $agentId)
            ->whereBetween('created_at', [$this->startDate, $this->endDate]);

        if ($this->leadTypes !== ['all']) {
            $newLeadsQuery->whereIn('stage_id', $this->leadTypes);
        }

        $newLeadIds = $newLeadsQuery->pluck('id');

        $calledLeads = Person::whereIn('id', $newLeadIds)
            ->whereHas('calls', function ($q) use ($agentId) {
                $q->where('user_id', $agentId);
            })
            ->count();

        return $newLeadIds->count() - $calledLeads;
    }

    private function getLeadsNotEmailed($agentId)
    {
        $newLeadsQuery = Person::where('assigned_user_id', $agentId)
            ->whereBetween('created_at', [$this->startDate, $this->endDate]);

        if ($this->leadTypes !== ['all']) {
            $newLeadsQuery->whereIn('stage_id', $this->leadTypes);
        }

        $newLeadIds = $newLeadsQuery->pluck('id');

        $emailedLeads = Person::whereIn('id', $newLeadIds)
            ->whereHas('emails', function ($q) use ($agentId) {
                $q->where('user_id', $agentId)
                    ->where('is_incoming', false);
            })
            ->count();

        return $newLeadIds->count() - $emailedLeads;
    }

    private function getLeadsNotTexted($agentId)
    {
        $newLeadsQuery = Person::where('assigned_user_id', $agentId)
            ->whereBetween('created_at', [$this->startDate, $this->endDate]);

        if ($this->leadTypes !== ['all']) {
            $newLeadsQuery->whereIn('stage_id', $this->leadTypes);
        }

        $newLeadIds = $newLeadsQuery->pluck('id');

        $textedLeads = Person::whereIn('id', $newLeadIds)
            ->whereHas('texts', function ($q) use ($agentId) {
                $q->where('user_id', $agentId)
                    ->where('is_incoming', false);
            })
            ->count();

        return $newLeadIds->count() - $textedLeads;
    }

    // Speed Calculation Methods
    private function getAverageSpeedToAction($agentId)
    {
        $webLeads = Person::where('assigned_user_id', $agentId)
            ->whereBetween('created_at', [$this->startDate, $this->endDate])
            ->get();

        $totalMinutes = 0;
        $count = 0;

        foreach ($webLeads as $lead) {
            $firstAction = $this->getFirstActionTime($lead->id, $agentId);
            if ($firstAction) {
                $leadCreated = Carbon::parse($lead->created_at);
                $firstActionTime = Carbon::parse($firstAction);
                $totalMinutes += $leadCreated->diffInMinutes($firstActionTime);
                $count++;
            }
        }

        return $count > 0 ? round($totalMinutes / $count, 2) : 0;
    }

    private function getFirstActionTime($personId, $agentId)
    {
        $firstCall = Call::where('person_id', $personId)
            ->where('user_id', $agentId)
            ->min('created_at');

        $firstEmail = Email::where('person_id', $personId)
            ->where('user_id', $agentId)
            ->where('is_incoming', false)
            ->min('created_at');

        $actions = array_filter([$firstCall, $firstEmail]);

        return !empty($actions) ? min($actions) : null;
    }

    private function getAverageSpeedToFirstCall($agentId)
    {
        $webLeads = Person::where('assigned_user_id', $agentId)
            ->whereBetween('created_at', [$this->startDate, $this->endDate])
            ->get();

        $totalMinutes = 0;
        $count = 0;

        foreach ($webLeads as $lead) {
            $firstCall = Call::where('person_id', $lead->id)
                ->where('user_id', $agentId)
                ->min('created_at');

            if ($firstCall) {
                $leadCreated = Carbon::parse($lead->created_at);
                $firstCallTime = Carbon::parse($firstCall);
                $totalMinutes += $leadCreated->diffInMinutes($firstCallTime);
                $count++;
            }
        }

        return $count > 0 ? round($totalMinutes / $count, 2) : 0;
    }

    private function getAverageSpeedToFirstEmail($agentId)
    {
        $webLeads = Person::where('assigned_user_id', $agentId)
            ->whereBetween('created_at', [$this->startDate, $this->endDate])
            ->get();

        $totalMinutes = 0;
        $count = 0;

        foreach ($webLeads as $lead) {
            $firstEmail = Email::where('person_id', $lead->id)
                ->where('user_id', $agentId)
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

    private function getAverageSpeedToFirstText($agentId)
    {
        $webLeads = Person::where('assigned_user_id', $agentId)
            ->whereBetween('created_at', [$this->startDate, $this->endDate]);

        if ($this->leadTypes !== ['all']) {
            $webLeads->whereIn('stage_id', $this->leadTypes);
        }

        $webLeads = $webLeads->get();

        $totalMinutes = 0;
        $count = 0;

        foreach ($webLeads as $lead) {
            $firstText = TextMessage::where('person_id', $lead->id)
                ->where('user_id', $agentId)
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
    private function getAverageContactAttempts($agentId)
    {
        $newLeadsQuery = Person::where('assigned_user_id', $agentId)
            ->whereBetween('created_at', [$this->startDate, $this->endDate]);

        if ($this->leadTypes !== ['all']) {
            $newLeadsQuery->whereIn('stage_id', $this->leadTypes);
        }

        $newLeads = $newLeadsQuery->get();
        $totalAttempts = 0;

        foreach ($newLeads as $lead) {
            $calls = Call::where('person_id', $lead->id)
                ->where('user_id', $agentId)
                ->count();

            $emails = Email::where('person_id', $lead->id)
                ->where('user_id', $agentId)
                ->where('is_incoming', false)
                ->count();

            $texts = TextMessage::where('person_id', $lead->id)
                ->where('user_id', $agentId)
                ->where('is_incoming', false)
                ->count();

            $totalAttempts += ($calls + $emails + $texts);
        }

        return $newLeads->count() > 0 ? round($totalAttempts / $newLeads->count(), 2) : 0;
    }

    private function getAverageCallAttempts($agentId)
    {
        $newLeadsQuery = Person::where('assigned_user_id', $agentId)
            ->whereBetween('created_at', [$this->startDate, $this->endDate]);

        if ($this->leadTypes !== ['all']) {
            $newLeadsQuery->whereIn('stage_id', $this->leadTypes);
        }

        $newLeads = $newLeadsQuery->get();
        $totalCalls = 0;

        foreach ($newLeads as $lead) {
            $calls = Call::where('person_id', $lead->id)
                ->where('user_id', $agentId)
                ->count();

            $totalCalls += $calls;
        }

        return $newLeads->count() > 0 ? round($totalCalls / $newLeads->count(), 2) : 0;
    }

    private function getAverageEmailAttempts($agentId)
    {
        $newLeadsQuery = Person::where('assigned_user_id', $agentId)
            ->whereBetween('created_at', [$this->startDate, $this->endDate]);

        if ($this->leadTypes !== ['all']) {
            $newLeadsQuery->whereIn('stage_id', $this->leadTypes);
        }

        $newLeads = $newLeadsQuery->get();
        $totalEmails = 0;

        foreach ($newLeads as $lead) {
            $emails = Email::where('person_id', $lead->id)
                ->where('user_id', $agentId)
                ->where('is_incoming', false)
                ->count();

            $totalEmails += $emails;
        }

        return $newLeads->count() > 0 ? round($totalEmails / $newLeads->count(), 2) : 0;
    }

    private function getAverageTextAttempts($agentId)
    {
        $newLeadsQuery = Person::where('assigned_user_id', $agentId)
            ->whereBetween('created_at', [$this->startDate, $this->endDate]);

        if ($this->leadTypes !== ['all']) {
            $newLeadsQuery->whereIn('stage_id', $this->leadTypes);
        }

        $newLeads = $newLeadsQuery->get();
        $totalTexts = 0;

        foreach ($newLeads as $lead) {
            $texts = TextMessage::where('person_id', $lead->id)
                ->where('user_id', $agentId)
                ->where('is_incoming', false)
                ->count();

            $totalTexts += $texts;
        }

        return $newLeads->count() > 0 ? round($totalTexts / $newLeads->count(), 2) : 0;
    }

    // Response Rate Calculations
    private function getResponseRate($agentId)
    {
        $newLeadsQuery = Person::where('assigned_user_id', $agentId)
            ->whereBetween('created_at', [$this->startDate, $this->endDate]);

        if ($this->leadTypes !== ['all']) {
            $newLeadsQuery->whereIn('stage_id', $this->leadTypes);
        }

        $newLeads = $newLeadsQuery->get();
        $respondedCount = 0;

        foreach ($newLeads as $lead) {
            $responded = $this->hasResponseAfterOutgoing(Email::class, $lead->id, $agentId)
                || $this->hasResponseAfterOutgoing(Call::class, $lead->id, $agentId)
                || $this->hasResponseAfterOutgoing(TextMessage::class, $lead->id, $agentId);

            if ($responded) {
                $respondedCount++;
            }
        }

        return $newLeads->count() > 0 ? round(($respondedCount / $newLeads->count()) * 100, 2) : 0;
    }

    private function getEmailResponseRate($agentId)
    {
        return $this->getChannelResponseRate(Email::class, $agentId);
    }

    private function getPhoneResponseRate($agentId)
    {
        return $this->getChannelResponseRate(Call::class, $agentId);
    }

    private function getTextResponseRate($agentId)
    {
        return $this->getChannelResponseRate(TextMessage::class, $agentId);
    }

    private function getChannelResponseRate($modelClass, $agentId)
    {
        $newLeadsQuery = Person::where('assigned_user_id', $agentId)
            ->whereBetween('created_at', [$this->startDate, $this->endDate]);

        if ($this->leadTypes !== ['all']) {
            $newLeadsQuery->whereIn('stage_id', $this->leadTypes);
        }

        $newLeads = $newLeadsQuery->get();
        $contactedLeads = 0;
        $respondedLeads = 0;

        foreach ($newLeads as $lead) {
            $hasOutgoing = $modelClass::where('person_id', $lead->id)
                ->where('user_id', $agentId)
                ->where('is_incoming', false)
                ->exists();

            if ($hasOutgoing) {
                $contactedLeads++;

                if ($this->hasResponseAfterOutgoing($modelClass, $lead->id, $agentId)) {
                    $respondedLeads++;
                }
            }
        }

        return $contactedLeads > 0 ? round(($respondedLeads / $contactedLeads) * 100, 2) : 0;
    }

    private function hasResponseAfterOutgoing($modelClass, $leadId, $agentId)
    {
        $firstOutgoing = $modelClass::where('person_id', $leadId)
            ->where('user_id', $agentId)
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
}
