<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\CalendarAccounts\CalendarAccountService;
use App\Services\CalendarAccounts\GoogleCalendarOAuthService;
use App\Services\CalendarAccounts\MicrosoftCalendarOAuthService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CalendarAuthController extends Controller
{
    private $calendarAccountService;
    private $googleOAuthService;
    private $microsoftOAuthService;

    public function __construct(
        CalendarAccountService $calendarAccountService,
        GoogleCalendarOAuthService $googleOAuthService,
        MicrosoftCalendarOAuthService $microsoftOAuthService
    ) {
        $this->calendarAccountService = $calendarAccountService;
        $this->googleOAuthService = $googleOAuthService;
        $this->microsoftOAuthService = $microsoftOAuthService;
    }

    /**
     * Initiate Google Calendar OAuth flow
     */
    public function googleAuth(Request $request)
    {
        try {
            $user = $request->user();
            $authUrl = $this->googleOAuthService->getAuthUrl($user->id);

            return response()->json([
                'success' => true,
                'auth_url' => $authUrl
            ]);
        } catch (\Exception $e) {
            Log::error('Google OAuth initiation failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to initiate Google authentication'
            ], 500);
        }
    }

    /**
     * Handle Google Calendar OAuth callback
     */
    public function googleCallback(Request $request)
    {
        try {
            $code = $request->get('code');
            $state = $request->get('state');
            $error = $request->get('error');

            if ($error) {
                return response()->json([
                    'success' => false,
                    'message' => $error === 'access_denied' ? 'Access denied by user' : 'Authentication was cancelled or failed'
                ], 400);
            }

            if (!$code) {
                return response()->json([
                    'success' => false,
                    'message' => 'No authorization code received'
                ], 400);
            }

            // Exchange code for tokens and create account
            $account = $this->googleOAuthService->handleCallback($code, $state);

            if ($account) {
                return response()->json([
                    'success' => true,
                    'message' => 'Google Calendar connected successfully',
                    'account' => $account
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Failed to connect Google Calendar'
            ], 500);
        } catch (\Exception $e) {
            Log::error('Google OAuth callback failed: ', [$e]);
            return response()->json([
                'success' => false,
                'message' => 'Authentication failed'
            ], 500);
        }
    }

    /**
     * Initiate Outlook Calendar OAuth flow
     */
    public function outlookAuth(Request $request)
    {
        try {
            $user = $request->user();
            $authUrl = $this->microsoftOAuthService->getAuthUrl($user->id);

            return response()->json([
                'success' => true,
                'auth_url' => $authUrl
            ]);
        } catch (\Exception $e) {
            Log::error('Outlook OAuth initiation failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to initiate Outlook authentication'
            ], 500);
        }
    }

    /**
     * Handle Outlook Calendar OAuth callback
     */
    public function outlookCallback(Request $request)
    {
        try {
            $code = $request->get('code');
            $state = $request->get('state');
            $error = $request->get('error');

            if ($error) {
                return response()->json([
                    'success' => false,
                    'message' => $error === 'access_denied' ? 'Access denied by user' : 'Authentication was cancelled or failed'
                ], 400);
            }

            if (!$code) {
                return response()->json([
                    'success' => false,
                    'message' => 'No authorization code received'
                ], 400);
            }

            // Exchange code for tokens and create account
            $account = $this->microsoftOAuthService->handleCallback($code, $state);

            if ($account) {
                return response()->json([
                    'success' => true,
                    'message' => 'Outlook Calendar connected successfully',
                    'account' => $account
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Failed to connect Outlook Calendar'
            ], 500);
        } catch (\Exception $e) {
            Log::error('Outlook OAuth callback failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Authentication failed'
            ], 500);
        }
    }

    /**
     * Disconnect calendar account
     */
    public function disconnect(Request $request)
    {
        try {
            $accountId = $request->get('account_id');
            $user = $request->user();

            $account = $user->calendarAccounts()->findOrFail($accountId);

            $this->calendarAccountService->delete($account);

            return response()->json([
                'success' => true,
                'message' => 'Calendar account disconnected successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Calendar disconnect failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to disconnect calendar account'
            ], 500);
        }
    }
}