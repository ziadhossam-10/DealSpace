<?php

namespace App\Exports;

use App\Models\TextMessage;
use App\Models\User;
use App\Enums\RoleEnum;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\Exportable;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class TextReportExport implements FromCollection, WithHeadings, WithMapping, WithStyles, ShouldAutoSize
{
    use Exportable;

    protected $startDate;
    protected $endDate;
    protected $agentIds;

    public function __construct(array $params = [])
    {
        $this->startDate = Carbon::parse($params['start_date'] ?? now()->startOfMonth());
        $this->endDate = Carbon::parse($params['end_date'] ?? now()->endOfMonth());
        $this->agentIds = $params['agent_ids'] ?? [];
    }

    public function collection()
    {
        $query = User::where('role', RoleEnum::AGENT);

        if (!empty($this->agentIds)) {
            $query->whereIn('id', $this->agentIds);
        }

        return $query->get();
    }

    public function headings(): array
    {
        return [
            'Agent Name',
            'Email',
            'Texts Sent',
            'Texts Received',
            'Texts Delivered',
            'Texts Failed',
            'Unique Contacts Texted',
            'Contacts Responded',
            'Conversations Initiated',
            'Active Conversations',
            'Delivery Rate (%)',
            'Response Rate (%)',
            'Engagement Rate (%)',
            'Opt Outs',
            'Carrier Filtered',
            'Other Errors',
            'Average Texts per Day',
            'Average Responses per Day',
            'Average Message Length (chars)',
        ];
    }

    public function map($agent): array
    {
        return [
            $agent->name,
            $agent->email,
            $this->getTextsSent($agent->id),
            $this->getTextsReceived($agent->id),
            $this->getTextsDelivered($agent->id),
            $this->getTextsFailed($agent->id),
            $this->getUniqueContactsTexted($agent->id),
            $this->getContactsResponded($agent->id),
            $this->getConversationsInitiated($agent->id),
            $this->getActiveConversations($agent->id),
            $this->getDeliveryRate($agent->id),
            $this->getResponseRate($agent->id),
            $this->getEngagementRate($agent->id),
            $this->getOptOuts($agent->id),
            $this->getCarrierFiltered($agent->id),
            $this->getOtherErrors($agent->id),
            $this->getAverageTextsPerDay($agent->id),
            $this->getAverageResponsesPerDay($agent->id),
            $this->getAverageMessageLength($agent->id),
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

    // Core Text Metrics
    private function getTextsSent($agentId)
    {
        return TextMessage::where('user_id', $agentId)
            ->where('is_incoming', false)
            ->whereBetween('created_at', [$this->startDate, $this->endDate])
            ->count();
    }

    private function getTextsReceived($agentId)
    {
        return TextMessage::where('user_id', $agentId)
            ->where('is_incoming', true)
            ->whereBetween('created_at', [$this->startDate, $this->endDate])
            ->count();
    }

    private function getTextsDelivered($agentId)
    {
        return TextMessage::where('user_id', $agentId)
            ->where('is_incoming', false)
            ->whereBetween('created_at', [$this->startDate, $this->endDate])
            ->whereNotIn('status', ['failed', 'undelivered', 'carrier_filtered'])
            ->count();
    }

    private function getTextsFailed($agentId)
    {
        return TextMessage::where('user_id', $agentId)
            ->where('is_incoming', false)
            ->whereBetween('created_at', [$this->startDate, $this->endDate])
            ->whereIn('status', ['failed', 'undelivered'])
            ->count();
    }

    // Engagement Metrics
    private function getUniqueContactsTexted($agentId)
    {
        return TextMessage::where('user_id', $agentId)
            ->where('is_incoming', false)
            ->whereBetween('created_at', [$this->startDate, $this->endDate])
            ->distinct('person_id')
            ->count('person_id');
    }

    private function getContactsResponded($agentId)
    {
        $contactsTexted = TextMessage::where('user_id', $agentId)
            ->where('is_incoming', false)
            ->whereBetween('created_at', [$this->startDate, $this->endDate])
            ->pluck('person_id')
            ->unique();

        return TextMessage::where('user_id', $agentId)
            ->where('is_incoming', true)
            ->whereBetween('created_at', [$this->startDate, $this->endDate])
            ->whereIn('person_id', $contactsTexted)
            ->distinct('person_id')
            ->count('person_id');
    }

    private function getConversationsInitiated($agentId)
    {
        return TextMessage::where('user_id', $agentId)
            ->where('is_incoming', false)
            ->whereBetween('created_at', [$this->startDate, $this->endDate])
            ->whereNotExists(function ($query) use ($agentId) {
                $query->select(DB::raw(1))
                    ->from('text_messages as tm2')
                    ->whereColumn('tm2.person_id', 'text_messages.person_id')
                    ->where('tm2.user_id', $agentId)
                    ->whereColumn('tm2.created_at', '<', 'text_messages.created_at');
            })
            ->distinct('person_id')
            ->count('person_id');
    }

    private function getActiveConversations($agentId)
    {
        return TextMessage::where('user_id', $agentId)
            ->whereBetween('created_at', [$this->startDate, $this->endDate])
            ->whereExists(function ($query) use ($agentId) {
                $query->select(DB::raw(1))
                    ->from('text_messages as tm_out')
                    ->whereColumn('tm_out.person_id', 'text_messages.person_id')
                    ->where('tm_out.user_id', $agentId)
                    ->where('tm_out.is_incoming', false);
            })
            ->whereExists(function ($query) use ($agentId) {
                $query->select(DB::raw(1))
                    ->from('text_messages as tm_in')
                    ->whereColumn('tm_in.person_id', 'text_messages.person_id')
                    ->where('tm_in.user_id', $agentId)
                    ->where('tm_in.is_incoming', true);
            })
            ->distinct('person_id')
            ->count('person_id');
    }

    // Performance Ratios
    private function getDeliveryRate($agentId)
    {
        $totalSent = $this->getTextsSent($agentId);
        $delivered = $this->getTextsDelivered($agentId);
        return $totalSent > 0 ? round(($delivered / $totalSent) * 100, 2) : 0;
    }

    private function getResponseRate($agentId)
    {
        $contactsTexted = $this->getUniqueContactsTexted($agentId);
        $contactsResponded = $this->getContactsResponded($agentId);
        return $contactsTexted > 0 ? round(($contactsResponded / $contactsTexted) * 100, 2) : 0;
    }

    private function getEngagementRate($agentId)
    {
        $contactsTexted = $this->getUniqueContactsTexted($agentId);
        $activeConversations = $this->getActiveConversations($agentId);
        return $contactsTexted > 0 ? round(($activeConversations / $contactsTexted) * 100, 2) : 0;
    }

    // Quality Metrics
    private function getOptOuts($agentId)
    {
        return TextMessage::where('user_id', $agentId)
            ->where('is_incoming', true)
            ->whereBetween('created_at', [$this->startDate, $this->endDate])
            ->where(function ($query) {
                $query->where('message', 'like', '%STOP%')
                    ->orWhere('message', 'like', '%UNSUBSCRIBE%')
                    ->orWhere('message', 'like', '%OPT OUT%')
                    ->orWhere('message', 'like', '%OPTOUT%');
            })
            ->count();
    }

    private function getCarrierFiltered($agentId)
    {
        return TextMessage::where('user_id', $agentId)
            ->where('is_incoming', false)
            ->whereBetween('created_at', [$this->startDate, $this->endDate])
            ->where('status', 'carrier_filtered')
            ->count();
    }

    private function getOtherErrors($agentId)
    {
        return TextMessage::where('user_id', $agentId)
            ->where('is_incoming', false)
            ->whereBetween('created_at', [$this->startDate, $this->endDate])
            ->whereIn('status', ['invalid_number', 'landline', 'blocked'])
            ->count();
    }

    // Daily Averages
    private function getAverageTextsPerDay($agentId)
    {
        $totalTexts = $this->getTextsSent($agentId);
        $daysDiff = $this->startDate->diffInDays($this->endDate) + 1;
        return round($totalTexts / $daysDiff, 2);
    }

    private function getAverageResponsesPerDay($agentId)
    {
        $totalResponses = $this->getTextsReceived($agentId);
        $daysDiff = $this->startDate->diffInDays($this->endDate) + 1;
        return round($totalResponses / $daysDiff, 2);
    }

    // Message Length Metrics
    private function getAverageMessageLength($agentId)
    {
        $avgLength = TextMessage::where('user_id', $agentId)
            ->where('is_incoming', false)
            ->whereBetween('created_at', [$this->startDate, $this->endDate])
            ->selectRaw('AVG(CHAR_LENGTH(message)) as avg_length')
            ->value('avg_length');
        return round($avgLength ?? 0, 2);
    }
}
