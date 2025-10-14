<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\TrackingScript\StoreTrackingScriptRequest;
use App\Http\Requests\TrackingScript\UpdateTrackingScriptRequest;
use App\Http\Requests\TrackingScript\StatisticsRequest;
use App\Http\Requests\TrackingScript\FieldMappingSuggestionRequest;
use App\Http\Requests\TrackingScript\DuplicateTrackingScriptRequest;
use App\Http\Resources\TrackingScriptCollection;
use App\Http\Resources\TrackingScriptResource;
use App\Services\Tracking\TrackingScriptServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TrackingScriptController extends Controller
{
    protected $trackingScriptService;

    public function __construct(TrackingScriptServiceInterface $trackingScriptService)
    {
        $this->trackingScriptService = $trackingScriptService;
    }

    /**
     * Display a listing of tracking scripts
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = $request->get('per_page', 15);
        $page = $request->get('page', 1);
        $filters = $request->only(['search', 'is_active']);

        $scripts = $this->trackingScriptService->getAll(
            Auth::id(),
            $perPage,
            $page,
            $filters
        );

        return successResponse(
            'Tracking scripts retrieved successfully',
            new TrackingScriptCollection($scripts)
        );
    }

    /**
     * Store a newly created tracking script
     */
    public function store(StoreTrackingScriptRequest $request): JsonResponse
    {
        // Validate field mappings if provided
        if ($request->has('field_mappings')) {
            $mappingErrors = $this->trackingScriptService->validateFieldMappings($request->field_mappings);
            if (!empty($mappingErrors)) {
                return errorResponse(
                    'Field mapping validation failed',
                    422,
                    ['field_mappings' => $mappingErrors],
                );
            }
        }

        $script = $this->trackingScriptService->create($request->validated(), Auth::id());

        return successResponse(
            'Tracking script created successfully',
            new TrackingScriptResource($script),
            201
        );
    }

    /**
     * Display the specified tracking script
     */
    public function show(int $id): JsonResponse
    {
        $script = $this->trackingScriptService->findById($id, Auth::id());

        return successResponse(
            'Tracking script retrieved successfully',
            new TrackingScriptResource($script)
        );
    }

    /**
     * Update the specified tracking script
     */
    public function update(UpdateTrackingScriptRequest $request, int $id): JsonResponse
    {
        // Validate field mappings if provided
        if ($request->has('field_mappings')) {
            $mappingErrors = $this->trackingScriptService->validateFieldMappings($request->field_mappings);
            if (!empty($mappingErrors)) {
                return errorResponse(
                    'Field mapping validation failed',
                    422,
                    ['field_mappings' => $mappingErrors],
                );
            }
        }

        $script = $this->trackingScriptService->update($id, $request->validated(), Auth::id());

        return successResponse(
            'Tracking script updated successfully',
            new TrackingScriptResource($script)
        );
    }

    /**
     * Remove the specified tracking script
     */
    public function destroy(int $id): JsonResponse
    {
        $this->trackingScriptService->delete($id, Auth::id());

        return successResponse(
            'Tracking script deleted successfully',
            null
        );
    }

    /**
     * Toggle the active status of a tracking script
     */
    public function toggleStatus(int $id): JsonResponse
    {
        $script = $this->trackingScriptService->toggleStatus($id, Auth::id());

        return successResponse(
            'Tracking script status updated successfully',
            new TrackingScriptResource($script)
        );
    }

    /**
     * Regenerate the script key
     */
    public function regenerateKey(int $id): JsonResponse
    {
        $script = $this->trackingScriptService->regenerateKey($id, Auth::id());

        return successResponse(
            'Script key regenerated successfully',
            new TrackingScriptResource($script)
        );
    }

    /**
     * Get tracking script statistics
     */
    public function statistics(StatisticsRequest $request, int $id): JsonResponse
    {
        $dateRange = $request->only(['start_date', 'end_date']);
        $statistics = $this->trackingScriptService->getStatistics($id, Auth::id(), $dateRange);

        return successResponse(
            'Statistics retrieved successfully',
            $statistics
        );
    }

    /**
     * Get recent events for a tracking script
     */
    public function recentEvents(Request $request, int $id): JsonResponse
    {
        $limit = $request->get('limit', 50);
        $events = $this->trackingScriptService->getRecentEvents($id, Auth::id(), $limit);

        return successResponse(
            'Recent events retrieved successfully',
            $events
        );
    }

    /**
     * Get the tracking code for embedding
     */
    public function getTrackingCode(int $id): JsonResponse
    {
        $script = $this->trackingScriptService->findById($id, Auth::id());

        $data = [
            'tracking_code' => $script->tracking_code,
            'script_key' => $script->script_key,
            'instructions' => $this->getImplementationInstructions()
        ];

        return successResponse(
            'Tracking code retrieved successfully',
            $data
        );
    }

    /**
     * Suggest field mappings based on form field names
     */
    public function suggestFieldMappings(FieldMappingSuggestionRequest $request): JsonResponse
    {
        $suggestions = $this->trackingScriptService->suggestFieldMappings($request->form_fields);

        return successResponse(
            'Field mapping suggestions generated successfully',
            $suggestions
        );
    }

    /**
     * Duplicate a tracking script
     */
    public function duplicate(DuplicateTrackingScriptRequest $request, int $id): JsonResponse
    {
        $script = $this->trackingScriptService->duplicate($id, Auth::id(), $request->name);

        return successResponse(
            'Tracking script duplicated successfully',
            new TrackingScriptResource($script),
            201
        );
    }

    /**
     * Get implementation instructions
     */
    protected function getImplementationInstructions(): array
    {
        return [
            'basic_setup' => [
                'title' => 'Basic Setup',
                'description' => 'Copy and paste the tracking code into your website\'s <head> section.',
                'code_example' => '<!-- Place this code in your <head> section -->'
            ],
            'custom_events' => [
                'title' => 'Custom Event Tracking',
                'description' => 'Use the dealspace.track() function to track custom events.',
                'code_example' => "// Track a custom event\ndealspace.track('Button Clicked', {\n  message: 'User clicked the CTA button',\n  description: 'Primary call-to-action button on homepage'\n});"
            ],
            'property_tracking' => [
                'title' => 'Property Tracking',
                'description' => 'Track property-related events using the property helper.',
                'code_example' => "// Track property view\ndealspace.trackProperty({\n  id: '12345',\n  address: '123 Main St, City, State',\n  price: 450000,\n  bedrooms: 3,\n  bathrooms: 2\n}, 'Viewed Property');"
            ],
            'form_customization' => [
                'title' => 'Form Field Mapping',
                'description' => 'The pixel automatically tracks forms based on your configuration. Make sure your form field names match the mappings you\'ve set up.',
                'code_example' => '<!-- Example form with mapped fields -->\n<form>\n  <input name="person_name" placeholder="Full Name">\n  <input name="email" placeholder="Email Address">\n  <textarea name="message" placeholder="Your Message"></textarea>\n  <button type="submit">Submit</button>\n</form>'
            ]
        ];
    }
}
