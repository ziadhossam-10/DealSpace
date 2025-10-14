<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CalendarAccountResource;
use App\Models\CalendarAccount;
use App\Services\CalendarAccounts\CalendarSyncService;
use App\Services\CalendarAccounts\CalendarAccountService;
use Illuminate\Http\Request;

class CalendarAccountController extends Controller
{
    private $calendarSyncService;
    private $calendarAccountService;

    public function __construct(
        CalendarSyncService $calendarSyncService,
        CalendarAccountService $calendarAccountService
    ) {
        $this->calendarSyncService = $calendarSyncService;
        $this->calendarAccountService = $calendarAccountService;
    }

    /**
     * Get all calendar accounts for the authenticated user
     */
    public function index()
    {
        $accounts = CalendarAccount::orderBy('created_at', 'desc')->get();

        return response()->json([
            'success' => true,
            'data' => CalendarAccountResource::collection($accounts)
        ]);
    }

    /**
     * Get a specific calendar account
     */
    public function show($id)
    {
        $account = CalendarAccount::findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => new CalendarAccountResource($account)
        ]);
    }

    /**
     * Connect/activate a calendar account
     */
    public function connect($id)
    {
        $account = CalendarAccount::findOrFail($id);

        $account->update(['is_active' => true]);

        return response()->json([
            'success' => true,
            'message' => 'Calendar account connected successfully'
        ]);
    }

    /**
     * Disconnect/deactivate a calendar account
     */
    public function disconnect($id)
    {
        $account = CalendarAccount::findOrFail($id);

        $account->update(['is_active' => false]);

        return response()->json([
            'success' => true,
            'message' => 'Calendar account disconnected successfully'
        ]);
    }

    /**
     * Delete a calendar account
     */
    public function destroy($id)
    {
        $account = CalendarAccount::findOrFail($id);

        $this->calendarAccountService->delete($account);

        return response()->json([
            'success' => true,
            'message' => 'Calendar account deleted successfully'
        ]);
    }

    /**
     * Sync calendar events for a specific account
     */
    public function sync($id)
    {
        $account = CalendarAccount::where('is_active', true)
            ->findOrFail($id);

        $syncedCount = $this->calendarSyncService->syncCalendarEvents($account);

        return response()->json([
            'success' => true,
            'message' => 'Calendar sync completed',
            'synced_count' => $syncedCount
        ]);
    }

    /**
     * Refresh webhook for a calendar account
     */
    public function refreshWebhook($id)
    {
        $account = CalendarAccount::findOrFail($id);

        $success = $this->calendarAccountService->refreshWebhook($account);

        return response()->json([
            'success' => $success,
            'message' => $success ? 'Webhook refreshed successfully' : 'Failed to refresh webhook'
        ]);
    }

    /**
     * Update calendar account settings
     */
    public function updateSettings($id, Request $request)
    {
        $account = CalendarAccount::findOrFail($id);

        $validated = $request->validate([
            'settings.sync_direction' => 'in:in,out,bidirectional',
            'settings.sync_all_events' => 'boolean',
            'settings.sync_attendees' => 'boolean',
            'settings.sync_reminders' => 'boolean',
            'settings.auto_create_meetings' => 'boolean',
            'settings.meeting_prefix' => 'string|max:50',
            'settings.sync_private_events' => 'boolean'
        ]);

        $account->update([
            'settings' => array_merge($account->settings ?? [], $validated['settings'] ?? [])
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Calendar settings updated successfully',
            'data' => new CalendarAccountResource($account)
        ]);
    }
}