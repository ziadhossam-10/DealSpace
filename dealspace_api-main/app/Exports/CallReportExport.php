<?php

namespace App\Exports;

use App\Models\Call;
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

class CallReportExport implements FromCollection, WithHeadings, WithMapping, WithStyles, ShouldAutoSize
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
            'Calls Made',
            'Calls Connected',
            'Conversations',
            'Calls Received',
            'Calls Missed',
            'Total Talk Time',
            'Average Call Duration',
            'Average Conversation Duration',
            'Average Answer Time',
            'Connection Rate (%)',
            'Conversation Rate (%)',
            'Answer Rate (%)',
            'Unique Contacts Called',
            'Contacts Reached',
            'Average Calls per Day',
            'Average Talk Time per Day',
        ];
    }

    public function map($agent): array
    {
        return [
            $agent->name,
            $agent->email,
            $this->getCallsMade($agent->id),
            $this->getCallsConnected($agent->id),
            $this->getConversations($agent->id),
            $this->getCallsReceived($agent->id),
            $this->getCallsMissed($agent->id),
            $this->formatDuration($this->getTotalTalkTime($agent->id)),
            $this->formatDuration($this->getAverageCallDuration($agent->id)),
            $this->formatDuration($this->getAverageConversationDuration($agent->id)),
            $this->formatDuration($this->getAverageAnswerTime($agent->id)),
            $this->getConnectionRate($agent->id),
            $this->getConversationRate($agent->id),
            $this->getAnswerRate($agent->id),
            $this->getUniqueContactsCalled($agent->id),
            $this->getContactsReached($agent->id),
            $this->getAverageCallsPerDay($agent->id),
            $this->formatDuration($this->getAverageTalkTimePerDay($agent->id)),
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

    // Core Call Metrics
    private function getCallsMade($agentId)
    {
        return Call::where('user_id', $agentId)
            ->where('is_incoming', false)
            ->whereBetween('created_at', [$this->startDate, $this->endDate])
            ->count();
    }

    private function getCallsConnected($agentId)
    {
        return Call::where('user_id', $agentId)
            ->where('is_incoming', false)
            ->whereBetween('created_at', [$this->startDate, $this->endDate])
            ->where('duration', '>=', 60)
            ->count();
    }

    private function getConversations($agentId)
    {
        return Call::where('user_id', $agentId)
            ->where('is_incoming', false)
            ->whereBetween('created_at', [$this->startDate, $this->endDate])
            ->where('duration', '>=', 120)
            ->count();
    }

    private function getCallsReceived($agentId)
    {
        return Call::where('user_id', $agentId)
            ->where('is_incoming', true)
            ->whereBetween('created_at', [$this->startDate, $this->endDate])
            ->count();
    }

    private function getCallsMissed($agentId)
    {
        return Call::where('user_id', $agentId)
            ->where('is_incoming', true)
            ->whereBetween('created_at', [$this->startDate, $this->endDate])
            ->where('duration', 0)
            ->count();
    }

    // Duration Metrics
    private function getTotalTalkTime($agentId)
    {
        return Call::where('user_id', $agentId)
            ->whereBetween('created_at', [$this->startDate, $this->endDate])
            ->sum('duration');
    }

    private function getAverageCallDuration($agentId)
    {
        return Call::where('user_id', $agentId)
            ->whereBetween('created_at', [$this->startDate, $this->endDate])
            ->where('duration', '>', 0)
            ->avg('duration') ?? 0;
    }

    private function getAverageConversationDuration($agentId)
    {
        return Call::where('user_id', $agentId)
            ->whereBetween('created_at', [$this->startDate, $this->endDate])
            ->where('duration', '>=', 120)
            ->avg('duration') ?? 0;
    }

    // Response Time Metrics
    private function getAverageAnswerTime($agentId)
    {
        // Placeholder - requires actual tracking in system
        return 0;
    }

    // Performance Ratios
    private function getConnectionRate($agentId)
    {
        $totalCalls = $this->getCallsMade($agentId);
        $connectedCalls = $this->getCallsConnected($agentId);
        return $totalCalls > 0 ? round(($connectedCalls / $totalCalls) * 100, 2) : 0;
    }

    private function getConversationRate($agentId)
    {
        $totalCalls = $this->getCallsMade($agentId);
        $conversations = $this->getConversations($agentId);
        return $totalCalls > 0 ? round(($conversations / $totalCalls) * 100, 2) : 0;
    }

    private function getAnswerRate($agentId)
    {
        $receivedCalls = $this->getCallsReceived($agentId);
        $missedCalls = $this->getCallsMissed($agentId);
        $answeredCalls = $receivedCalls - $missedCalls;
        return $receivedCalls > 0 ? round(($answeredCalls / $receivedCalls) * 100, 2) : 0;
    }

    // Contact Metrics
    private function getUniqueContactsCalled($agentId)
    {
        return Call::where('user_id', $agentId)
            ->where('is_incoming', false)
            ->whereBetween('created_at', [$this->startDate, $this->endDate])
            ->distinct('person_id')
            ->count('person_id');
    }

    private function getContactsReached($agentId)
    {
        return Call::where('user_id', $agentId)
            ->where('is_incoming', false)
            ->whereBetween('created_at', [$this->startDate, $this->endDate])
            ->where('duration', '>=', 60)
            ->distinct('person_id')
            ->count('person_id');
    }

    // Daily Averages
    private function getAverageCallsPerDay($agentId)
    {
        $totalCalls = $this->getCallsMade($agentId);
        $daysDiff = $this->startDate->diffInDays($this->endDate) + 1;
        return round($totalCalls / $daysDiff, 2);
    }

    private function getAverageTalkTimePerDay($agentId)
    {
        $totalTalkTime = $this->getTotalTalkTime($agentId);
        $daysDiff = $this->startDate->diffInDays($this->endDate) + 1;
        return round($totalTalkTime / $daysDiff, 2);
    }

    // Helper Method
    private function formatDuration($seconds)
    {
        if (!$seconds) {
            return '00:00:00';
        }

        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);
        $secs = $seconds % 60;

        return sprintf('%02d:%02d:%02d', $hours, $minutes, $secs);
    }
}