<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Events\UpdateEventRequest;
use App\Http\Resources\EventResource;
use App\Models\TrackingScript;
use App\Services\Events\EventServiceInterface;
use App\Services\Tracking\TrackingServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class TrackingController extends Controller
{
    protected $trackingService;

    public function __construct(TrackingServiceInterface $trackingService)
    {
        $this->trackingService = $trackingService;
    }

    /**
     * Serve the tracking JavaScript
     */
    public function script(Request $request): Response
    {
        $scriptKey = $request->get('key');

        if (!$scriptKey) {
            return response('// Invalid script key', 400)
                ->header('Content-Type', 'application/javascript');
        }

        $trackingScript = TrackingScript::where('script_key', $scriptKey)
            ->where('is_active', true)
            ->first();

        if (!$trackingScript) {
            return response('// Script not found or inactive', 404)
                ->header('Content-Type', 'application/javascript');
        }

        $js = $this->generateTrackingScript($trackingScript);

        return response($js)
            ->header('Content-Type', 'application/javascript')
            ->header('Cache-Control', 'public, max-age=300'); // 5 minutes cache
    }

    /**
     * Track page view
     */
    public function trackPageView(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'script_key' => 'required|string',
            'page_url' => 'required|url',
            'page_title' => 'nullable|string|max:255',
            'page_referrer' => 'nullable|url',
            'page_duration' => 'nullable|integer|min:0',
            'utm_params' => 'nullable|array',
            'person_data' => 'nullable|array',
            'active_time' => 'nullable|integer|min:0',
            'scroll_depth' => 'nullable|integer|min:0|max:100',
            'is_refresh' => 'nullable|boolean',
            'is_unload' => 'nullable|boolean',
            'user_agent' => 'nullable|string',
            'screen_resolution' => 'nullable|string',
            'viewport_size' => 'nullable|string',
            // Property data validations
            'property' => 'nullable|array',
            'property.street' => 'nullable|string|max:255',
            'property.city' => 'nullable|string|max:255',
            'property.state' => 'nullable|string|max:10',
            'property.code' => 'nullable|string|max:20',
            'property.mlsNumber' => 'nullable|string|max:255',
            'property.price' => 'nullable|integer',
            'property.forRent' => 'nullable|boolean',
            'property.url' => 'nullable|url',
            'property.type' => 'nullable|string|max:255',
            'property.bedrooms' => 'nullable|string|max:10',
            'property.bathrooms' => 'nullable|string|max:10',
            'property.area' => 'nullable|string|max:255',
            'property.lot' => 'nullable|string|max:255',
            'property_search' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        }

        try {
            // Verify domain if script has domain restrictions
            $this->verifyDomain($request->input('script_key'), $request->input('page_url'));

            $event = $this->trackingService->trackPageView(
                $request->input('script_key'),
                $request->all()
            );

            return response()->json([
                'success' => true,
                'event_id' => $event->id,
                'message' => 'Page view tracked successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Page view tracking failed: ' . $e->getMessage(), [
                'script_key' => $request->input('script_key'),
                'page_url' => $request->input('page_url'),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to track page view'
            ], 500);
        }
    }

    /**
     * Track form submission
     */
    public function trackFormSubmission(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'form_key' => 'nullable|string|max:255',
            'script_key' => 'required|string',
            'page_url' => 'required|url',
            'form_data' => 'required|array',
            'form_selector' => 'nullable|string',
            'page_title' => 'nullable|string|max:255',
            'page_referrer' => 'nullable|url',
            'utm_params' => 'nullable|array',
            'person_data' => 'nullable|array',
            // Property data validations
            'property' => 'nullable|array',
            'property.street' => 'nullable|string|max:255',
            'property.city' => 'nullable|string|max:255',
            'property.state' => 'nullable|string|max:10',
            'property.code' => 'nullable|string|max:20',
            'property.mlsNumber' => 'nullable|string|max:255',
            'property.price' => 'nullable|integer',
            'property.forRent' => 'nullable|boolean',
            'property.url' => 'nullable|url',
            'property.type' => 'nullable|string|max:255',
            'property.bedrooms' => 'nullable|string|max:10',
            'property.bathrooms' => 'nullable|string|max:10',
            'property.area' => 'nullable|string|max:255',
            'property.lot' => 'nullable|string|max:255',
            'property_search' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        }

        try {
            // Verify domain if script has domain restrictions
            $this->verifyDomain($request->input('script_key'), $request->input('page_url'));

            $event = $this->trackingService->trackFormSubmission(
                $request->input('script_key'),
                $request->all()
            );

            return response()->json([
                'success' => true,
                'event_id' => $event->id,
                'message' => 'Form submission tracked successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Form submission tracking failed: ' . $e->getMessage(), [
                'script_key' => $request->input('script_key'),
                'form_data' => $request->input('form_data'),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to track form submission'
            ], 500);
        }
    }

    /**
     * Track custom event
     */
    public function trackCustomEvent(Request $request): JsonResponse
    {
        // Define form-related event types that require form_key
        $formEventTypes = ['Form Filled', 'Form Started', 'Form Submitted'];
        $eventType = $request->input('event_type');

        // Base validation rules
        $rules = [
            'script_key' => 'required|string',
            'event_type' => 'required|string|max:100',
            'page_url' => 'required|url',
            'message' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:1000',
            'page_title' => 'nullable|string|max:255',
            'page_referrer' => 'nullable|url',
            'utm_params' => 'nullable|array',
            'person_data' => 'nullable|array',
            // Property data validations
            'property' => 'nullable|array',
            'property.street' => 'nullable|string|max:255',
            'property.city' => 'nullable|string|max:255',
            'property.state' => 'nullable|string|max:10',
            'property.code' => 'nullable|string|max:20',
            'property.mlsNumber' => 'nullable|string|max:255',
            'property.price' => 'nullable|integer',
            'property.forRent' => 'nullable|boolean',
            'property.url' => 'nullable|url',
            'property.type' => 'nullable|string|max:255',
            'property.bedrooms' => 'nullable|string|max:10',
            'property.bathrooms' => 'nullable|string|max:10',
            'property.area' => 'nullable|string|max:255',
            'property.lot' => 'nullable|string|max:255',
            'property_search' => 'nullable|array',
        ];

        // Make form_key required for form-related events
        if (in_array($eventType, $formEventTypes)) {
            $rules['form_key'] = 'required|string|max:255';
        } else {
            $rules['form_key'] = 'nullable|string|max:255';
        }

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        }

        try {
            // Verify domain if script has domain restrictions
            $this->verifyDomain($request->input('script_key'), $request->input('page_url'));

            $event = $this->trackingService->trackCustomEvent(
                $request->input('script_key'),
                $request->all()
            );

            return response()->json([
                'success' => true,
                'event_id' => $event->id,
                'message' => 'Custom event tracked successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Custom event tracking failed: ' . $e->getMessage(), [
                'script_key' => $request->input('script_key'),
                'event_type' => $request->input('event_type'),
                'error' => $e
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to track custom event'
            ], 500);
        }
    }

    /**
     * Verify domain restrictions
     */
    protected function verifyDomain(string $scriptKey, string $pageUrl): void
    {
        $trackingScript = TrackingScript::where('script_key', $scriptKey)
            ->where('is_active', true)
            ->first();

        if (!$trackingScript) {
            throw new \Exception('Invalid or inactive script key');
        }

        // Skip domain verification if no domain restrictions set
        if (empty($trackingScript->domain)) {
            return;
        }

        $parsedUrl = parse_url($pageUrl);
        $domain = $parsedUrl['host'] ?? '';

        if (!$trackingScript->isDomainAllowed($domain)) {
            throw new \Exception('Domain not allowed for this tracking script');
        }
    }

    /**
     * Generate the tracking JavaScript code
     */
    protected function generateTrackingScript(TrackingScript $trackingScript): string
    {
        $config = [
            'scriptKey' => $trackingScript->script_key,
            'apiUrl' => config('app.url') . '/api/tracking',
            'trackPageViews' => $trackingScript->track_page_views,
            'trackAllForms' => $trackingScript->track_all_forms,
            'formSelectors' => $trackingScript->form_selectors ?? [],
            'autoLeadCapture' => $trackingScript->auto_lead_capture,
            'trackUtmParameters' => $trackingScript->track_utm_parameters,
            'fieldMappings' => $trackingScript->field_mappings ?? TrackingScript::getDefaultFieldMappings(),
            'customEvents' => $trackingScript->custom_events ?? [],
            'debug' => config('app.debug', false), // Enable debug mode in development
        ];

        // Load the JavaScript template and inject configuration
        $jsTemplate = $this->getTrackingScriptTemplate();

        return str_replace(
            '{{CONFIG_PLACEHOLDER}}',
            json_encode($config, JSON_UNESCAPED_SLASHES),
            $jsTemplate
        );
    }

    /**
     * Get the tracking script template
     */
    protected function getTrackingScriptTemplate()
    {
        // You can either load this from a file or return the template string
        $templatePath = resource_path('js/tracking-pixel-template.js');

        if (file_exists($templatePath)) {
            return file_get_contents($templatePath);
        }
    }
}
