<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Campaigns\CampaignService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Response;

class CampaignTrackingController extends Controller
{
    private $campaignService;

    public function __construct(CampaignService $campaignService)
    {
        $this->campaignService = $campaignService;
    }

    /**
     * Track email opens via tracking pixel with improved handling
     */
    public function trackOpen(string $token, Request $request)
    {
        // Log the tracking attempt with detailed info
        Log::info('Open tracking pixel requested', [
            'token' => $token,
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'referer' => $request->header('referer'),
            'accept' => $request->header('accept'),
            'format' => $request->get('format', 'gif'),
            'timestamp' => now(),
            'query_params' => $request->query()
        ]);

        try {
            $this->campaignService->trackOpen($token);
            Log::info('Open tracking successful', ['token' => $token]);
        } catch (\Exception $e) {
            Log::warning('Campaign open tracking failed', [
                'token' => $token,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }

        // Return appropriate image format based on request
        $format = $request->get('format', 'gif');

        switch ($format) {
            case 'png':
                return $this->returnTransparentPNG();
            case 'bg':
                return $this->returnTransparentGIF(); // For CSS background images
            default:
                return $this->returnTransparentGIF();
        }
    }

    /**
     * Return a 1x1 transparent GIF
     */
    private function returnTransparentGIF()
    {
        // 1x1 transparent GIF in base64
        $pixel = base64_decode('R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7');

        return response($pixel)
            ->header('Content-Type', 'image/gif')
            ->header('Content-Length', strlen($pixel))
            ->header('Cache-Control', 'no-cache, no-store, must-revalidate, max-age=0')
            ->header('Pragma', 'no-cache')
            ->header('Expires', 'Thu, 01 Jan 1970 00:00:00 GMT')
            ->header('Last-Modified', gmdate('D, d M Y H:i:s') . ' GMT')
            ->header('ETag', '"' . md5($pixel) . '"')
            ->header('Access-Control-Allow-Origin', '*')
            ->header('Access-Control-Allow-Methods', 'GET')
            ->header('X-Robots-Tag', 'noindex, nofollow');
    }

    /**
     * Return a 1x1 transparent PNG
     */
    private function returnTransparentPNG()
    {
        // 1x1 transparent PNG in base64
        $pixel = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8/5+hHgAHggJ/PchI7wAAAABJRU5ErkJggg==');

        return response($pixel)
            ->header('Content-Type', 'image/png')
            ->header('Content-Length', strlen($pixel))
            ->header('Cache-Control', 'no-cache, no-store, must-revalidate, max-age=0')
            ->header('Pragma', 'no-cache')
            ->header('Expires', 'Thu, 01 Jan 1970 00:00:00 GMT')
            ->header('Last-Modified', gmdate('D, d M Y H:i:s') . ' GMT')
            ->header('ETag', '"' . md5($pixel) . '"')
            ->header('Access-Control-Allow-Origin', '*')
            ->header('Access-Control-Allow-Methods', 'GET')
            ->header('X-Robots-Tag', 'noindex, nofollow');
    }

    /**
     * Track link clicks and redirect
     */
    public function trackClick(string $token, Request $request)
    {
        $recipientToken = $request->get('recipient');

        Log::info('Click tracking attempt', [
            'link_token' => $token,
            'recipient_token' => $recipientToken,
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'referer' => $request->header('referer')
        ]);

        if (!$recipientToken) {
            Log::warning('Click tracking failed - no recipient token', ['token' => $token]);
            return response('Invalid tracking link', 400);
        }

        try {
            $metadata = [
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'referer' => $request->header('referer'),
                'timestamp' => now()->toISOString(),
                'accept_language' => $request->header('accept-language'),
                'accept' => $request->header('accept')
            ];

            $originalUrl = $this->campaignService->trackClick($token, $recipientToken, $metadata);

            Log::info('Click tracking successful', [
                'link_token' => $token,
                'recipient_token' => $recipientToken,
                'original_url' => $originalUrl
            ]);

            return redirect($originalUrl);
        } catch (\Exception $e) {
            Log::error('Campaign click tracking failed', [
                'token' => $token,
                'recipient' => $recipientToken,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response('Invalid tracking link', 400);
        }
    }

    /**
     * Health check endpoint for tracking
     */
    public function healthCheck()
    {
        return response()->json([
            'status' => 'ok',
            'timestamp' => now(),
            'service' => 'Campaign Tracking'
        ]);
    }

    /**
     * Debug endpoint to test tracking (remove in production)
     */
    public function debug(Request $request)
    {
        if (!app()->environment(['local', 'staging'])) {
            abort(404);
        }

        return response()->json([
            'headers' => $request->headers->all(),
            'query' => $request->query(),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'url' => $request->fullUrl(),
            'method' => $request->method()
        ]);
    }
}
