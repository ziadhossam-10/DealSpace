<?php

namespace App\Services\Activities;

use App\Models\ActivityLog;
use App\Models\Call;
use App\Models\Email;
use App\Models\TextMessage;
use App\Models\Note;

class ActivityService
{
    public function logActivity(string $type, int $activityId, int $personId, ?int $userId = null, array $metadata = []): ActivityLog
    {
        $activity = $this->getActivityRecord($type, $activityId);

        return ActivityLog::create([
            'person_id' => $personId,
            'user_id' => $userId,
            'activity_type' => $type,
            'activity_id' => $activityId,
            'tenant_id' => $activity?->tenant_id,
            'title' => $this->generateTitle($type, $activity),
            'description' => $this->generateDescription($type, $activity),
            'metadata' => array_merge($this->extractMetadata($type, $activity), $metadata),
        ]);
    }

    public function getPersonActivities(int $personId, int $perPage = 15, int $page = 1)
    {
        return ActivityLog::with(['activity', 'user'])
            ->byPerson($personId)
            ->orderBy('created_at', 'desc')
            ->paginate($perPage, ['*'], 'page', $page);
    }

    public function getPersonActivitiesByType(int $personId, string $type, int $perPage = 15, int $page = 1)
    {
        return ActivityLog::with(['activity', 'user'])
            ->byPerson($personId)
            ->byType($type)
            ->orderBy('created_at', 'desc')
            ->paginate($perPage, ['*'], 'page', $page);
    }

    private function getActivityRecord(string $type, int $activityId)
    {
        return match ($type) {
            'Call' => Call::find($activityId),
            'Email' => Email::find($activityId),
            'TextMessage' => TextMessage::find($activityId),
            'Note' => Note::find($activityId),
            default => null,
        };
    }

    private function generateTitle(string $type, $activity): string
    {
        return match ($type) {
            'Call' => $activity->is_incoming ? 'Incoming Call' : 'Outgoing Call',
            'Email' => ($activity->is_incoming ? 'Received: ' : 'Sent: ') . $activity->subject,
            'TextMessage' => $activity->is_incoming ? 'Received Text Message' : 'Sent Text Message',
            'Note' => 'Note: ' . $activity->subject,
            default => 'Unknown Activity',
        };
    }

    private function generateDescription(string $type, $activity): ?string
    {
        return match ($type) {
            'Call' => $activity->note,
            'Email' => substr(strip_tags($activity->body ?? $activity->body_html), 0, 200),
            'TextMessage' => $activity->message,
            'Note' => substr($activity->body, 0, 200),
            default => null,
        };
    }

    private function extractMetadata(string $type, $activity): array
    {
        return match ($type) {
            'Call' => [
                'duration' => $activity->duration,
                'outcome' => $activity->outcome,
                'phone' => $activity->phone,
                'is_incoming' => $activity->is_incoming,
            ],
            'Email' => [
                'to_email' => $activity->to_email,
                'from_email' => $activity->from_email,
                'is_incoming' => $activity->is_incoming,
            ],
            'TextMessage' => [
                'to_number' => $activity->to_number,
                'from_number' => $activity->from_number,
                'is_incoming' => $activity->is_incoming,
            ],
            'Note' => [
                'created_by' => $activity->created_by,
                'updated_by' => $activity->updated_by,
            ],
            default => [],
        };
    }
}
