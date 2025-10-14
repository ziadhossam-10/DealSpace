<?php

namespace App\Services\CalendarAccounts;

use App\Models\CalendarAccount;
use App\Models\CalendarEvent;
use Carbon\Carbon;
use Google\Service\Calendar;
use Google\Service\Tasks;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CalendarSyncService
{
    private $googleOAuthService;
    private $microsoftOAuthService;

    public function __construct(
        GoogleCalendarOAuthService $googleOAuthService,
        MicrosoftCalendarOAuthService $microsoftOAuthService
    ) {
        $this->googleOAuthService = $googleOAuthService;
        $this->microsoftOAuthService = $microsoftOAuthService;
    }

    /**
     * Sync calendar events bidirectionally for a specific account
     */
    public function syncCalendarEvents(CalendarAccount $account): array
    {
        try {
            Log::info('Starting bidirectional calendar sync', [
                'account_id' => $account->id,
                'provider' => $account->provider
            ]);

            $results = [
                'from_external' => ['events' => 0, 'tasks' => 0, 'appointments' => 0],
                'to_external' => ['events' => 0, 'tasks' => 0, 'appointments' => 0]
            ];

            // Sync FROM external calendar TO local
            if ($account->provider === 'google') {
                $results['from_external']['events'] = $this->syncGoogleCalendarEvents($account);
                $results['from_external']['tasks'] = $this->syncGoogleTasks($account);
            } elseif ($account->provider === 'outlook') {
                $results['from_external']['events'] = $this->syncOutlookCalendarEvents($account);
                $results['from_external']['tasks'] = $this->syncOutlookTasks($account);
            }

            // Sync FROM local TO external calendar
            $results['to_external'] = $this->syncLocalEventsToExternal($account);

            $account->update(['last_sync_at' => now()]);

            Log::info('Bidirectional calendar sync completed', [
                'account_id' => $account->id,
                'results' => $results
            ]);

            return $results;
        } catch (\Exception $e) {
            Log::error('Calendar sync failed', [
                'account_id' => $account->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return [
                'from_external' => ['events' => 0, 'tasks' => 0, 'appointments' => 0],
                'to_external' => ['events' => 0, 'tasks' => 0, 'appointments' => 0]
            ];
        }
    }

    /**
     * Sync local events to external calendar
     */
    private function syncLocalEventsToExternal(CalendarAccount $account): array
    {
        $results = ['events' => 0, 'tasks' => 0, 'appointments' => 0];

        // Get all pending local events that need to be synced
        $pendingEvents = CalendarEvent::where('calendar_account_id', $account->id)
            ->where('sync_direction', 'to_external')
            ->where('sync_status', 'pending')
            ->get();

        foreach ($pendingEvents as $event) {
            try {
                $success = false;

                if ($account->provider === 'google') {
                    $success = $this->syncLocalEventToGoogle($account, $event);
                } elseif ($account->provider === 'outlook') {
                    $success = $this->syncLocalEventToOutlook($account, $event);
                }

                if ($success) {
                    $results[$event->event_type]++;
                    $event->markAsSynced();
                } else {
                    $event->markAsFailed('Failed to sync to external calendar');
                }
            } catch (\Exception $e) {
                Log::error('Failed to sync local event to external', [
                    'event_id' => $event->id,
                    'error' => $e->getMessage()
                ]);
                $event->markAsFailed($e->getMessage());
            }
        }

        return $results;
    }

    /**
     * Sync local event to Google Calendar
     */
    public function syncLocalEventToGoogle(CalendarAccount $account, CalendarEvent $event): bool
    {
        try {
            if ($event->event_type === 'task') {
                return $this->syncLocalTaskToGoogle($account, $event);
            } else {
                return $this->syncLocalEventToGoogleCalendar($account, $event);
            }
        } catch (\Exception $e) {
            Log::error('Failed to sync local event to Google', [
                'event_id' => $event->id,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Sync local task to Google Tasks
     */
    private function syncLocalTaskToGoogle(CalendarAccount $account, CalendarEvent $event): bool
    {
        $client = new \Google\Client();
        $accessToken = $this->googleOAuthService->getValidToken($account);
        if (!$accessToken) {
            return false;
        }

        $client->setAccessToken($accessToken);
        $tasksService = new \Google\Service\Tasks($client);

        try {
            // Get the list of task lists
            $taskLists = $tasksService->tasklists->listTasklists();
            $defaultTaskList = $taskLists->getItems()[0] ?? null;

            Log::info('task lists', [
                'account_id' => $account->id,
                'task_lists' => $taskLists->getItems()
            ]);

            Log::info('Event details', [$event]);

            if (!$defaultTaskList) {
                return false;
            }

            $createdTask = null;

            if ($event->external_id) {
                // Try to find and update the task in all task lists
                $taskFound = false;

                foreach ($taskLists->getItems() as $taskList) {
                    try {
                        $existingTask = $tasksService->tasks->get($taskList->getId(), $event->external_id);

                        if ($existingTask) {
                            // Modify the existing task object
                            $existingTask->setTitle($event->title);
                            $existingTask->setNotes($event->description);
                            $existingTask->setStatus($event->status === 'confirmed' ? 'completed' : 'needsAction');

                            if ($event->start_time) {
                                $existingTask->setDue($event->start_time->toRfc3339String());
                            }

                            $createdTask = $tasksService->tasks->update(
                                $taskList->getId(),
                                $event->external_id,
                                $existingTask
                            );

                            $taskFound = true;

                            Log::info('Successfully updated Google task', [
                                'event_id' => $event->id,
                                'external_id' => $event->external_id,
                                'task_list_id' => $taskList->getId()
                            ]);

                            break;
                        }
                    } catch (\Exception $e) {
                        Log::debug('Task not found in task list', [
                            'task_list_id' => $taskList->getId(),
                            'external_id' => $event->external_id,
                            'error' => $e->getMessage()
                        ]);
                        continue;
                    }
                }

                if (!$taskFound) {
                    Log::warning('Task not found in any list, creating new task', [
                        'event_id' => $event->id,
                        'external_id' => $event->external_id
                    ]);

                    $googleTask = new \Google\Service\Tasks\Task();
                    $googleTask->setTitle($event->title);
                    $googleTask->setNotes($event->description);
                    $googleTask->setStatus($event->status === 'confirmed' ? 'completed' : 'needsAction');

                    if ($event->start_time) {
                        $googleTask->setDue($event->start_time->toRfc3339String());
                    }

                    $createdTask = $tasksService->tasks->insert(
                        $defaultTaskList->getId(),
                        $googleTask
                    );
                }
            } else {
                // Create new task
                $googleTask = new \Google\Service\Tasks\Task();
                $googleTask->setTitle($event->title);
                $googleTask->setNotes($event->description);
                $googleTask->setStatus($event->status === 'confirmed' ? 'completed' : 'needsAction');

                if ($event->start_time) {
                    $googleTask->setDue($event->start_time->toRfc3339String());
                }

                $createdTask = $tasksService->tasks->insert(
                    $defaultTaskList->getId(),
                    $googleTask
                );
            }

            if ($createdTask) {
                $event->update([
                    'external_id' => $createdTask->getId(),
                    'external_updated_at' => \Carbon\Carbon::parse($createdTask->getUpdated())
                ]);
            }

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to sync task to Google', [
                'event_id' => $event->id,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Sync local event to Google Calendar
     */
    private function syncLocalEventToGoogleCalendar(CalendarAccount $account, CalendarEvent $event): bool
    {
        $calendar = $this->googleOAuthService->getCalendarService($account);
        if (!$calendar) {
            return false;
        }

        try {
            $googleEvent = new \Google\Service\Calendar\Event();
            $googleEvent->setSummary($event->title);
            $googleEvent->setDescription($event->description);
            $googleEvent->setLocation($event->location);

            // Set start and end times
            $start = new \Google\Service\Calendar\EventDateTime();
            $end = new \Google\Service\Calendar\EventDateTime();


            if ($event->is_all_day || ($event->start_time == null && !$event->end_time == null)) {
                $start->setDate($event->start_time->format('Y-m-d'));
                $end->setDate($event->end_time->format('Y-m-d'));
            } else {
                $start->setDateTime($event->start_time?->toISOString());
                $start->setTimeZone($event->timezone);
                $end->setDateTime($event->end_time?->toISOString());
                $end->setTimeZone($event->timezone);
            }

            $googleEvent->setStart($start);
            $googleEvent->setEnd($end);

            // Set attendees
            if ($event->attendees) {
                $attendees = [];
                foreach ($event->attendees as $attendee) {
                    $googleAttendee = new \Google\Service\Calendar\EventAttendee();
                    $googleAttendee->setEmail($attendee['email']);
                    $googleAttendee->setDisplayName($attendee['name'] ?? '');
                    $attendees[] = $googleAttendee;
                }
                $googleEvent->setAttendees($attendees);
            }

            // Set reminders
            if ($event->reminders) {
                $reminders = new \Google\Service\Calendar\EventReminders();
                $reminders->setUseDefault(false);
                $overrides = [];
                foreach ($event->reminders as $reminder) {
                    $override = new \Google\Service\Calendar\EventReminder();
                    $override->setMethod($reminder['method']);
                    $override->setMinutes($reminder['minutes']);
                    $overrides[] = $override;
                }
                $reminders->setOverrides($overrides);
                $googleEvent->setReminders($reminders);
            }

            if ($event->external_id) {
                // Update existing event
                $createdEvent = $calendar->events->update(
                    $account->calendar_id,
                    $event->external_id,
                    $googleEvent
                );
            } else {
                // Create new event
                $createdEvent = $calendar->events->insert(
                    $account->calendar_id,
                    $googleEvent
                );
            }

            // Update local event with external ID
            $event->update([
                'external_id' => $createdEvent->getId(),
                'external_updated_at' => Carbon::parse($createdEvent->getUpdated())
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to sync event to Google Calendar', [
                'event_id' => $event->id,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Sync local event to Outlook
     */
    public function syncLocalEventToOutlook(CalendarAccount $account, CalendarEvent $event): bool
    {
        try {
            if ($event->event_type === 'task') {
                return $this->syncLocalTaskToOutlook($account, $event);
            } else {
                return $this->syncLocalEventToOutlookCalendar($account, $event);
            }
        } catch (\Exception $e) {
            Log::error('Failed to sync local event to Outlook', [
                'event_id' => $event->id,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Sync local task to Outlook Tasks
     */
    private function syncLocalTaskToOutlook(CalendarAccount $account, CalendarEvent $event): bool
    {
        $accessToken = $this->microsoftOAuthService->getValidToken($account);
        if (!$accessToken) {
            return false;
        }

        try {
            $taskData = [
                'title' => $event->title,
                'body' => [
                    'content' => $event->description ?? '',
                    'contentType' => 'text'
                ],
                'importance' => 'normal',
                'status' => $event->status === 'confirmed' ? 'completed' : 'notStarted'
            ];

            if ($event->start_time) {
                $taskData['dueDateTime'] = [
                    'dateTime' => $event->start_time->toISOString(),
                    'timeZone' => $event->timezone
                ];
            }

            $headers = [
                'Authorization' => 'Bearer ' . $accessToken,
                'Content-Type' => 'application/json'
            ];

            if ($event->external_id) {
                // Update existing task
                $response = Http::withHeaders($headers)
                    ->patch("https://graph.microsoft.com/v1.0/me/todo/lists/tasks/{$event->external_id}", $taskData);
            } else {
                // Create new task - first get the default task list
                $listsResponse = Http::withHeaders($headers)
                    ->get('https://graph.microsoft.com/v1.0/me/todo/lists');

                if (!$listsResponse->successful()) {
                    return false;
                }

                $taskLists = $listsResponse->json()['value'] ?? [];
                $defaultList = collect($taskLists)->first(fn($list) => $list['wellknownListName'] === 'defaultList');

                if (!$defaultList) {
                    $defaultList = $taskLists[0] ?? null;
                }

                if (!$defaultList) {
                    return false;
                }

                $response = Http::withHeaders($headers)
                    ->post("https://graph.microsoft.com/v1.0/me/todo/lists/{$defaultList['id']}/tasks", $taskData);
            }

            if ($response->successful()) {
                $responseData = $response->json();
                $event->update([
                    'external_id' => $responseData['id'],
                    'external_updated_at' => Carbon::parse($responseData['lastModifiedDateTime'])
                ]);
                return true;
            }

            return false;
        } catch (\Exception $e) {
            Log::error('Failed to sync task to Outlook', [
                'event_id' => $event->id,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Sync local event to Outlook Calendar
     */
    private function syncLocalEventToOutlookCalendar(CalendarAccount $account, CalendarEvent $event): bool
    {
        $accessToken = $this->microsoftOAuthService->getValidToken($account);
        if (!$accessToken) {
            return false;
        }

        try {
            $eventData = [
                'subject' => $event->title,
                'body' => [
                    'content' => $event->description ?? '',
                    'contentType' => 'text'
                ],
                'isAllDay' => $event->is_all_day,
                'showAs' => 'busy'
            ];

            if ($event->location) {
                $eventData['location'] = [
                    'displayName' => $event->location
                ];
            }

            // Set start and end times
            if ($event->is_all_day) {
                $eventData['start'] = [
                    'dateTime' => $event->start_time->format('Y-m-d\T00:00:00'),
                    'timeZone' => $event->timezone
                ];
                $eventData['end'] = [
                    'dateTime' => $event->end_time->format('Y-m-d\T23:59:59'),
                    'timeZone' => $event->timezone
                ];
            } else {
                $eventData['start'] = [
                    'dateTime' => $event->start_time->toISOString(),
                    'timeZone' => $event->timezone
                ];
                $eventData['end'] = [
                    'dateTime' => $event->end_time->toISOString(),
                    'timeZone' => $event->timezone
                ];
            }

            // Set attendees
            if ($event->attendees) {
                $attendees = [];
                foreach ($event->attendees as $attendee) {
                    $attendees[] = [
                        'emailAddress' => [
                            'address' => $attendee['email'],
                            'name' => $attendee['name'] ?? ''
                        ],
                        'type' => 'required'
                    ];
                }
                $eventData['attendees'] = $attendees;
            }

            // Set reminders
            if ($event->reminders) {
                $firstReminder = $event->reminders[0] ?? null;
                if ($firstReminder) {
                    $eventData['reminderMinutesBeforeStart'] = $firstReminder['minutes'];
                }
            }

            $headers = [
                'Authorization' => 'Bearer ' . $accessToken,
                'Content-Type' => 'application/json'
            ];

            if ($event->external_id) {
                // Update existing event
                $response = Http::withHeaders($headers)
                    ->patch("https://graph.microsoft.com/v1.0/me/events/{$event->external_id}", $eventData);
            } else {
                // Create new event
                $response = Http::withHeaders($headers)
                    ->post('https://graph.microsoft.com/v1.0/me/events', $eventData);
            }

            if ($response->successful()) {
                $responseData = $response->json();
                $event->update([
                    'external_id' => $responseData['id'],
                    'external_updated_at' => Carbon::parse($responseData['lastModifiedDateTime'])
                ]);
                return true;
            }

            return false;
        } catch (\Exception $e) {
            Log::error('Failed to sync event to Outlook Calendar', [
                'event_id' => $event->id,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Delete external event/task
     */
    public function deleteExternalEvent(CalendarEvent $event): bool
    {
        if (!$event->external_id || !$event->calendarAccount) {
            return false;
        }

        $account = $event->calendarAccount;

        try {
            if ($account->provider === 'google') {
                return $this->deleteGoogleEvent($account, $event);
            } elseif ($account->provider === 'outlook') {
                return $this->deleteOutlookEvent($account, $event);
            }
        } catch (\Exception $e) {
            Log::error('Failed to delete external event', [
                'event_id' => $event->id,
                'external_id' => $event->external_id,
                'error' => $e->getMessage()
            ]);
        }

        return false;
    }

    /**
     * Delete Google event/task
     */
    private function deleteGoogleEvent(CalendarAccount $account, CalendarEvent $event): bool
    {
        try {
            if ($event->event_type === 'task') {
                $client = new \Google\Client();
                $accessToken = $this->googleOAuthService->getValidToken($account);
                if (!$accessToken) {
                    return false;
                }

                $client->setAccessToken($accessToken);
                $tasksService = new Tasks($client);

                // Get task lists to find the task
                $taskLists = $tasksService->tasklists->listTasklists();
                foreach ($taskLists->getItems() as $taskList) {
                    try {
                        $tasksService->tasks->delete($taskList->getId(), $event->external_id);
                        return true;
                    } catch (\Exception $e) {
                        // Continue to next list if task not found in this list
                        continue;
                    }
                }
            } else {
                $calendar = $this->googleOAuthService->getCalendarService($account);
                if (!$calendar) {
                    return false;
                }

                $calendar->events->delete($account->calendar_id, $event->external_id);
                return true;
            }
        } catch (\Exception $e) {
            Log::error('Failed to delete Google event', [
                'event_id' => $event->id,
                'error' => $e->getMessage()
            ]);
        }

        return false;
    }

    /**
     * Delete Outlook event/task
     */
    private function deleteOutlookEvent(CalendarAccount $account, CalendarEvent $event): bool
    {
        $accessToken = $this->microsoftOAuthService->getValidToken($account);
        if (!$accessToken) {
            return false;
        }

        try {
            $headers = [
                'Authorization' => 'Bearer ' . $accessToken
            ];

            if ($event->event_type === 'task') {
                $response = Http::withHeaders($headers)
                    ->delete("https://graph.microsoft.com/v1.0/me/todo/lists/tasks/{$event->external_id}");
            } else {
                $response = Http::withHeaders($headers)
                    ->delete("https://graph.microsoft.com/v1.0/me/events/{$event->external_id}");
            }

            return $response->successful();
        } catch (\Exception $e) {
            Log::error('Failed to delete Outlook event', [
                'event_id' => $event->id,
                'error' => $e->getMessage()
            ]);
        }

        return false;
    }


    /**
     * Sync Google Calendar events
     */
    private function syncGoogleCalendarEvents(CalendarAccount $account): int
    {
        $calendar = $this->googleOAuthService->getCalendarService($account);
        if (!$calendar) {
            throw new \Exception('Cannot get Google Calendar service');
        }

        $syncedCount = 0;
        $timeMin = now()->subDays(30)->toISOString();
        $timeMax = now()->addDays(365)->toISOString();

        $optParams = [
            'timeMin' => $timeMin,
            'timeMax' => $timeMax,
            'singleEvents' => true,
            'orderBy' => 'startTime',
            'maxResults' => 250
        ];

        if ($account->sync_token) {
            $optParams['syncToken'] = $account->sync_token;
            unset($optParams['timeMin'], $optParams['timeMax']);
        }

        try {
            $events = $calendar->events->listEvents($account->calendar_id, $optParams);


            foreach ($events->getItems() as $event) {
                if ($this->syncGoogleEvent($account, $event)) {
                    $syncedCount++;
                }
            }

            if ($events->getNextSyncToken()) {
                $account->update(['sync_token' => $events->getNextSyncToken()]);
            }
        } catch (\Google\Service\Exception $e) {
            if ($e->getCode() === 410) {
                $account->update(['sync_token' => null]);
                return $this->syncGoogleCalendarEvents($account);
            }
            throw $e;
        }

        return $syncedCount;
    }

    /**
     * Sync Google Tasks
     */
    public function syncGoogleTasks(CalendarAccount $account): int
    {
        $client = new \Google\Client();
        $accessToken = $this->googleOAuthService->getValidToken($account);
        if (!$accessToken) {
            throw new \Exception('Invalid access token for Google Tasks');
        }

        $client->setAccessToken($accessToken);
        $tasksService = new Tasks($client);
        $syncedCount = 0;

        try {
            // Get all task lists
            $taskLists = $tasksService->tasklists->listTasklists();

            foreach ($taskLists->getItems() as $taskList) {
                // Get tasks from each list
                $tasks = $tasksService->tasks->listTasks($taskList->getId(), [
                    'showCompleted' => true,
                    'showDeleted' => false,
                    'maxResults' => 100
                ]);

                foreach ($tasks->getItems() as $task) {
                    if ($this->syncGoogleTask($account, $task, $taskList->getTitle())) {
                        $syncedCount++;
                    }
                }
            }
        } catch (\Exception $e) {
            Log::error('Failed to sync Google Tasks', [
                'account_id' => $account->id,
                'error' => $e->getMessage()
            ]);
        }

        return $syncedCount;
    }

    /**
     * Sync individual Google Task
     */
    private function syncGoogleTask(CalendarAccount $account, $googleTask, string $taskListName): bool
    {
        try {
            $taskId = $googleTask->getId();
            $status = $googleTask->getStatus();

            // Handle deleted tasks
            if ($status === 'deleted') {
                CalendarEvent::where('external_id', $taskId)
                    ->where('calendar_account_id', $account->id)
                    ->where('event_type', 'task')
                    ->delete();
                return true;
            }

            $due = $googleTask->getDue();
            $completed = $googleTask->getCompleted();

            // Create start and end times for the task
            $startTime = $due ? Carbon::parse($due) : now()->addDay();
            $endTime = $startTime->copy()->addHour(); // Default 1 hour duration

            $eventData = [
                'calendar_account_id' => $account->id,
                'person_id' => null,
                'user_id' => $account->user_id,
                'tenant_id' => $account->tenant_id,
                'external_id' => $taskId,
                'title' => $googleTask->getTitle() ?: 'Untitled Task',
                'description' => $googleTask->getNotes() . ($taskListName ? "\n\nTask List: " . $taskListName : ''),
                'location' => null,
                'start_time' => $startTime,
                'end_time' => $endTime,
                'timezone' => config('app.timezone'),
                'is_all_day' => false, // Tasks should never be all day
                'status' => $status === 'completed' ? 'confirmed' : 'tentative',
                'visibility' => 'default',
                'attendees' => [],
                'organizer_email' => null,
                'meeting_link' => null,
                'reminders' => [],
                'recurrence' => [],
                'sync_status' => 'synced',
                'sync_direction' => 'from_external',
                'last_synced_at' => now(),
                'external_updated_at' => Carbon::parse($googleTask->getUpdated()),
                'event_type' => 'task'
            ];

            CalendarEvent::updateOrCreate(
                [
                    'external_id' => $taskId,
                    'calendar_account_id' => $account->id,
                    'event_type' => 'task'
                ],
                $eventData
            );

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to sync Google task', [
                'account_id' => $account->id,
                'task_id' => $googleTask->getId(),
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Sync individual Google Calendar event
     */
    private function syncGoogleEvent(CalendarAccount $account, $googleEvent): bool
    {
        Log::info('does appointment', [$googleEvent]);
        try {
            $eventId = $googleEvent->getId();
            $status = $googleEvent->getStatus();


            if ($status === 'cancelled') {
                CalendarEvent::where('external_id', $eventId)
                    ->where('calendar_account_id', $account->id)
                    ->where('event_type', 'event')
                    ->delete();
                return true;
            }

            $start = $googleEvent->getStart();
            $end = $googleEvent->getEnd();

            if (!$start || !$end) {
                return false;
            }

            $isAllDay = !empty($start->getDate());
            $startTime = $isAllDay ?
                Carbon::parse($start->getDate())->startOfDay() :
                Carbon::parse($start->getDateTime());
            $endTime = $isAllDay ?
                Carbon::parse($end->getDate())->endOfDay() :
                Carbon::parse($end->getDateTime());

            $attendees = [];
            if ($googleEvent->getAttendees()) {
                foreach ($googleEvent->getAttendees() as $attendee) {
                    $attendees[] = [
                        'email' => $attendee->getEmail(),
                        'name' => $attendee->getDisplayName(),
                        'status' => $attendee->getResponseStatus()
                    ];
                }
            }

            $reminders = [];
            if ($googleEvent->getReminders() && $googleEvent->getReminders()->getOverrides()) {
                foreach ($googleEvent->getReminders()->getOverrides() as $reminder) {
                    $reminders[] = [
                        'method' => $reminder->getMethod(),
                        'minutes' => $reminder->getMinutes()
                    ];
                }
            }

            $recurrence = [];
            if ($googleEvent->getRecurrence()) {
                $recurrence = $googleEvent->getRecurrence();
            }

            // Determine if this is an appointment (has attendees and organizer)
            $eventType = 'event';
            if (!empty($attendees) || $googleEvent->getOrganizer()) {
                $eventType = 'appointment';
            }

            $eventData = [
                'calendar_account_id' => $account->id,
                'person_id' => null,
                'user_id' => $account->user_id,
                'tenant_id' => $account->tenant_id,
                'external_id' => $eventId,
                'title' => $googleEvent->getSummary() ?: 'No Title',
                'description' => $googleEvent->getDescription(),
                'location' => $googleEvent->getLocation(),
                'start_time' => $startTime,
                'end_time' => $endTime,
                'timezone' => $start->getTimeZone() ?: config('app.timezone'),
                'is_all_day' => $isAllDay,
                'status' => $status,
                'visibility' => $googleEvent->getVisibility() ?: 'default',
                'attendees' => $attendees,
                'organizer_email' => $googleEvent->getOrganizer()?->getEmail(),
                'meeting_link' => $googleEvent->getHangoutLink(),
                'reminders' => $reminders,
                'recurrence' => $recurrence,
                'sync_status' => 'synced',
                'sync_direction' => 'from_external',
                'last_synced_at' => now(),
                'external_updated_at' => Carbon::parse($googleEvent->getUpdated()),
                'event_type' => $eventType
            ];

            CalendarEvent::updateOrCreate(
                [
                    'external_id' => $eventId,
                    'calendar_account_id' => $account->id,
                    'event_type' => $eventType
                ],
                $eventData
            );

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to sync Google event', [
                'account_id' => $account->id,
                'event_id' => $googleEvent->getId(),
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Sync Outlook Calendar events
     */
    private function syncOutlookCalendarEvents(CalendarAccount $account): int
    {
        $accessToken = $this->microsoftOAuthService->getValidToken($account);
        if (!$accessToken) {
            throw new \Exception('Invalid access token for Outlook');
        }

        $syncedCount = 0;
        $startTime = now()->subDays(30)->toISOString();
        $endTime = now()->addDays(365)->toISOString();

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $accessToken
        ])->get('https://graph.microsoft.com/v1.0/me/events', [
            '$filter' => "start/dateTime ge '$startTime' and start/dateTime le '$endTime'",
            '$orderby' => 'start/dateTime',
            '$top' => 250
        ]);

        if (!$response->successful()) {
            throw new \Exception('Failed to fetch Outlook events: ' . $response->body());
        }

        $events = $response->json()['value'] ?? [];

        foreach ($events as $event) {
            if ($this->syncOutlookEvent($account, $event)) {
                $syncedCount++;
            }
        }

        return $syncedCount;
    }

    /**
     * Sync Outlook Tasks
     */
    private function syncOutlookTasks(CalendarAccount $account): int
    {
        $accessToken = $this->microsoftOAuthService->getValidToken($account);
        if (!$accessToken) {
            throw new \Exception('Invalid access token for Outlook Tasks');
        }

        $syncedCount = 0;

        try {
            // Get tasks from Microsoft To Do
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $accessToken
            ])->get('https://graph.microsoft.com/v1.0/me/todo/lists');

            if (!$response->successful()) {
                Log::warning('Failed to fetch Outlook task lists', [
                    'account_id' => $account->id,
                    'response' => $response->body()
                ]);
                return 0;
            }

            $taskLists = $response->json()['value'] ?? [];

            foreach ($taskLists as $taskList) {
                // Get tasks from each list
                $tasksResponse = Http::withHeaders([
                    'Authorization' => 'Bearer ' . $accessToken
                ])->get("https://graph.microsoft.com/v1.0/me/todo/lists/{$taskList['id']}/tasks");

                if ($tasksResponse->successful()) {
                    $tasks = $tasksResponse->json()['value'] ?? [];

                    foreach ($tasks as $task) {
                        if ($this->syncOutlookTask($account, $task, $taskList['displayName'])) {
                            $syncedCount++;
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            Log::error('Failed to sync Outlook Tasks', [
                'account_id' => $account->id,
                'error' => $e->getMessage()
            ]);
        }

        return $syncedCount;
    }

    /**
     * Sync individual Outlook Task
     */
    private function syncOutlookTask(CalendarAccount $account, array $outlookTask, string $taskListName): bool
    {
        try {
            $taskId = $outlookTask['id'];
            $status = $outlookTask['status'] ?? 'notStarted';

            $due = $outlookTask['dueDateTime'] ?? null;
            $completed = $outlookTask['completedDateTime'] ?? null;

            // Create start and end times for the task
            $startTime = $due ? Carbon::parse($due['dateTime']) : now()->addDay();
            $endTime = $startTime->copy()->addHour(); // Default 1 hour duration

            $eventData = [
                'calendar_account_id' => $account->id,
                'person_id' => null,
                'user_id' => $account->user_id,
                'tenant_id' => $account->tenant_id,
                'external_id' => $taskId,
                'title' => $outlookTask['title'] ?: 'Untitled Task',
                'description' => ($outlookTask['body']['content'] ?? '') . ($taskListName ? "\n\nTask List: " . $taskListName : ''),
                'location' => null,
                'start_time' => $startTime,
                'end_time' => $endTime,
                'timezone' => $due['timeZone'] ?? config('app.timezone'),
                'is_all_day' => false, // Tasks should never be all day
                'status' => $status === 'completed' ? 'confirmed' : 'tentative',
                'visibility' => $outlookTask['importance'] ?? 'normal',
                'attendees' => [],
                'organizer_email' => null,
                'meeting_link' => null,
                'reminders' => [],
                'recurrence' => [],
                'sync_status' => 'synced',
                'sync_direction' => 'from_external',
                'last_synced_at' => now(),
                'external_updated_at' => Carbon::parse($outlookTask['lastModifiedDateTime']),
                'event_type' => 'task'
            ];

            CalendarEvent::updateOrCreate(
                [
                    'external_id' => $taskId,
                    'calendar_account_id' => $account->id,
                    'event_type' => 'task'
                ],
                $eventData
            );

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to sync Outlook task', [
                'account_id' => $account->id,
                'task_id' => $outlookTask['id'] ?? 'unknown',
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Sync individual Outlook event
     */
    private function syncOutlookEvent(CalendarAccount $account, array $outlookEvent): bool
    {
        try {
            $eventId = $outlookEvent['id'];
            $status = $outlookEvent['showAs'] ?? 'busy';

            if (isset($outlookEvent['isCancelled']) && $outlookEvent['isCancelled']) {
                CalendarEvent::where('external_id', $eventId)
                    ->where('calendar_account_id', $account->id)
                    ->delete();
                return true;
            }

            $start = $outlookEvent['start'] ?? null;
            $end = $outlookEvent['end'] ?? null;

            if (!$start || !$end) {
                return false;
            }

            $isAllDay = $outlookEvent['isAllDay'] ?? false;
            $startTime = $isAllDay ?
                Carbon::parse($start['dateTime'])->startOfDay() :
                Carbon::parse($start['dateTime']);
            $endTime = $isAllDay ?
                Carbon::parse($end['dateTime'])->endOfDay() :
                Carbon::parse($end['dateTime']);

            $attendees = [];
            if (isset($outlookEvent['attendees'])) {
                foreach ($outlookEvent['attendees'] as $attendee) {
                    $attendees[] = [
                        'email' => $attendee['emailAddress']['address'] ?? '',
                        'name' => $attendee['emailAddress']['name'] ?? '',
                        'status' => $attendee['status']['response'] ?? 'none'
                    ];
                }
            }

            $reminders = [];
            if (isset($outlookEvent['reminderMinutesBeforeStart'])) {
                $reminders[] = [
                    'method' => 'popup',
                    'minutes' => $outlookEvent['reminderMinutesBeforeStart']
                ];
            }

            $recurrence = [];
            if (isset($outlookEvent['recurrence'])) {
                $recurrence = $outlookEvent['recurrence'];
            }

            // Determine if this is an appointment (has attendees and organizer)
            $eventType = 'event';
            if (!empty($attendees) || isset($outlookEvent['organizer'])) {
                $eventType = 'appointment';
            }

            $eventData = [
                'calendar_account_id' => $account->id,
                'person_id' => null,
                'user_id' => $account->user_id,
                'tenant_id' => $account->tenant_id,
                'external_id' => $eventId,
                'title' => $outlookEvent['subject'] ?? 'No Title',
                'description' => $outlookEvent['body']['content'] ?? null,
                'location' => $outlookEvent['location']['displayName'] ?? null,
                'start_time' => $startTime,
                'end_time' => $endTime,
                'timezone' => $start['timeZone'] ?? config('app.timezone'),
                'is_all_day' => $isAllDay,
                'status' => $this->mapOutlookStatusToGoogle($status),
                'visibility' => $outlookEvent['sensitivity'] ?? 'normal',
                'attendees' => $attendees,
                'organizer_email' => $outlookEvent['organizer']['emailAddress']['address'] ?? null,
                'meeting_link' => $outlookEvent['webLink'] ?? null,
                'reminders' => $reminders,
                'recurrence' => $recurrence,
                'sync_status' => 'synced',
                'sync_direction' => 'from_external',
                'last_synced_at' => now(),
                'external_updated_at' => Carbon::parse($outlookEvent['lastModifiedDateTime']),
                'event_type' => $eventType
            ];

            CalendarEvent::updateOrCreate(
                [
                    'external_id' => $eventId,
                    'calendar_account_id' => $account->id,
                    'event_type' => $eventType
                ],
                $eventData
            );

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to sync Outlook event', [
                'account_id' => $account->id,
                'event_id' => $outlookEvent['id'] ?? 'unknown',
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Map Outlook status to Google Calendar status
     */
    private function mapOutlookStatusToGoogle(string $outlookStatus): string
    {
        $statusMap = [
            'busy' => 'confirmed',
            'free' => 'confirmed',
            'tentative' => 'tentative',
            'outOfOffice' => 'confirmed',
            'workingElsewhere' => 'confirmed'
        ];

        return $statusMap[$outlookStatus] ?? 'confirmed';
    }

    /**
     * Sync events for all active accounts
     */
    public function syncAllAccounts(): array
    {
        $accounts = CalendarAccount::where('is_active', true)->get();
        $results = [];

        foreach ($accounts as $account) {
            $results[$account->id] = $this->syncCalendarEvents($account);
        }

        return $results;
    }

    /**
     * Sync events for a specific user
     */
    public function syncUserAccounts(int $userId): array
    {
        $accounts = CalendarAccount::where('user_id', $userId)
            ->where('is_active', true)
            ->get();

        $results = [];

        foreach ($accounts as $account) {
            $results[$account->id] = $this->syncCalendarEvents($account);
        }

        return $results;
    }

    /**
     * Clean up old synced events
     */
    public function cleanupOldEvents(int $daysOld = 365): int
    {
        $cutoffDate = now()->subDays($daysOld);

        return CalendarEvent::where('end_time', '<', $cutoffDate)
            ->where('sync_direction', 'from_external')
            ->delete();
    }
}