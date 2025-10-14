<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\EmailAccountResource;
use App\Models\EmailAccount;
use App\Services\EmailAccounts\GoogleOAuthService;
use App\Services\EmailAccounts\MicrosoftOAuthService;
use Illuminate\Http\Request;

class EmailsOAuthController extends Controller
{
    public function redirectToGoogle(GoogleOAuthService $googleService)
    {
        $authUrl = $googleService->getAuthUrl();
        return response()->json(['auth_url' => $authUrl]);
    }

    public function redirectToMicrosoft(MicrosoftOAuthService $microsoftService)
    {
        $authUrl = $microsoftService->getAuthUrl();
        return response()->json(['auth_url' => $authUrl]);
    }

    public function handleGoogleCallback(Request $request, GoogleOAuthService $googleService)
    {
        try {
            $code = $request->get('code');
            $account = $googleService->handleCallback($code);

            return response()->json([
                'success' => true,
                'message' => 'Gmail account connected successfully',
                'account' => $account
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    public function handleMicrosoftCallback(Request $request, MicrosoftOAuthService $microsoftService)
    {
        try {
            $code = $request->get('code');
            $account = $microsoftService->handleCallback($code);

            return response()->json([
                'success' => true,
                'message' => 'Outlook account connected successfully',
                'account' => $account
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    public function getConnectedAccounts()
    {
        $accounts = EmailAccount::where('is_active', true)->get();
        return successResponse("Active accounts fetched successfully", EmailAccountResource::collection($accounts));
    }

    public function connectAccount($id)
    {
        $account = EmailAccount::findOrFail($id);
        $account->update(['is_active' => true]);

        return successResponse('Account connected successfully');
    }
    public function disconnectAccount($id)
    {
        $account = EmailAccount::findOrFail($id);
        $account->update(['is_active' => false]);

        return successResponse('Account disconnected successfully');
    }
}
