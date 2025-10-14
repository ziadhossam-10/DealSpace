<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CalendarAccount;
use App\Services\CalendarAccounts\CalendarSyncService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CalendarsWebhookController extends Controller
{
    private $calendarSyncService;

    public function __construct(CalendarSyncService $calendarSyncService)
    {
        $this->calendarSyncService = $calendarSyncService;
    }

    /**
     * Handle Google Calendar webhook notifications
     */
    public function handleGoogleWebhook(Request $request)
    {
        Log::info('Google Calendar webhook received', [
            'headers' => $request->headers->all(),
            'body' => $request->getContent()
        ]);
        try {
            // Google Calendar sends notifications via HTTP POST
            $channelId = $request->header('X-Goog-Channel-ID');
            $resourceId = $request->header('X-Goog-Resource-ID');
            $resourceState = $request->header('X-Goog-Resource-State');
            $resourceUri = $request->header('X-Goog-Resource-URI');

            if (!$channelId || !$resourceId) {
                Log::warning('Google Calendar webhook received without required headers');
                return response('OK', 200);
            }

            // Find the calendar account
            $account = CalendarAccount::where('webhook_channel_id', $channelId)
                ->where('webhook_resource_id', $resourceId)
                ->where('provider', 'google')
                ->where('is_active', true)
                ->first();

            if (!$account) {
                Log::info('Google Calendar webhook: Account not found', [
                    'channel_id' => $channelId,
                    'resource_id' => $resourceId
                ]);
                return response('OK', 200);
            }

            // Handle different resource states
            switch ($resourceState) {
                case 'sync':
                    // Initial sync - ignore this
                    Log::info('Google Calendar webhook: Initial sync', ['account_id' => $account->id]);
                    break;

                case 'exists':
                    // Calendar events changed
                    Log::info('Google Calendar webhook: Events changed', ['account_id' => $account->id]);
                    $this->calendarSyncService->syncCalendarEvents($account);
                    break;

                case 'not_exists':
                    // Calendar was deleted
                    Log::info('Google Calendar webhook: Calendar deleted', ['account_id' => $account->id]);
                    $account->update(['is_active' => false]);
                    break;
            }

            return response('OK', 200);
        } catch (\Exception $e) {
            Log::error('Google Calendar webhook error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'request_headers' => $request->headers->all()
            ]);
            return response('Error', 500);
        }
    }

    /**
     * Handle Outlook Calendar webhook notifications
     */
    public function handleOutlookWebhook(Request $request)
    {
        try {
            $data = $request->json()->all();

            // Handle validation request from Microsoft
            if (isset($data['validationToken'])) {
                return response($data['validationToken'], 200)
                    ->header('Content-Type', 'text/plain');
            }

            if (!isset($data['value']) || !is_array($data['value'])) {
                Log::warning('Outlook Calendar webhook: Invalid data format');
                return response('OK', 200);
            }

            foreach ($data['value'] as $notification) {
                $this->processOutlookNotification($notification);
            }

            return response('OK', 200);
        } catch (\Exception $e) {
            Log::error('Outlook Calendar webhook error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->all()
            ]);
            return response('Error', 500);
        }
    }

    /**
     * Process individual Outlook notification
     */
    private function processOutlookNotification(array $notification): void
    {
        $subscriptionId = $notification['subscriptionId'] ?? '';
        $changeType = $notification['changeType'] ?? '';
        $resource = $notification['resource'] ?? '';
        $clientState = $notification['clientState'] ?? '';

        if (!$subscriptionId) {
            Log::warning('Outlook Calendar webhook: Missing subscription ID');
            return;
        }

        // Find the calendar account
        $account = CalendarAccount::where('webhook_subscription_id', $subscriptionId)
            ->where('provider', 'outlook')
            ->where('is_active', true)
            ->first();

        if (!$account) {
            Log::info('Outlook Calendar webhook: Account not found', [
                'subscription_id' => $subscriptionId
            ]);
            return;
        }

        // Verify client state
        $expectedClientState = hash_hmac('sha256', $account->id, config('app.key'));
        if ($clientState !== $expectedClientState) {
            Log::warning('Outlook Calendar webhook: Client state mismatch', [
                'account_id' => $account->id,
                'expected' => $expectedClientState,
                'received' => $clientState
            ]);
            return;
        }

        // Handle different change types
        switch ($changeType) {
            case 'created':
            case 'updated':
            case 'deleted':
                Log::info('Outlook Calendar webhook: Event changed', [
                    'account_id' => $account->id,
                    'change_type' => $changeType,
                    'resource' => $resource
                ]);
                $this->calendarSyncService->syncCalendarEvents($account);
                break;
        }
    }
}