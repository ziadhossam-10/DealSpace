<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Campaign;
use App\Services\Campaigns\CampaignService;
use App\Jobs\SendCampaignJob;

class CampaignController extends Controller
{
    private $campaignService;

    public function __construct(CampaignService $campaignService)
    {
        $this->campaignService = $campaignService;
    }

    /**
     * Create and send a campaign immediately
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'subject' => 'required|string|max:255',
            'body' => 'required|string',
            'body_html' => 'nullable|string',
            'email_account_id' => 'required|exists:email_accounts,id',
            'use_all_emails' => 'boolean',

            // Either specific recipient IDs or use filters
            'recipient_ids' => 'nullable|array|min:1',
            'recipient_ids.*' => 'exists:people,id',
            'is_all_selected' => 'boolean',

            // Filter options (same as People controller)
            'stage_id' => 'nullable|integer|exists:stages,id',
            'team_id' => 'nullable|integer|exists:teams,id',
            'user_ids' => 'nullable|array',
            'user_ids.*' => 'integer|exists:users,id',
            'search' => 'nullable|string|max:255',
            'deal_type_id' => 'nullable|integer|exists:deal_types,id',
        ]);

        // Validate that either recipient_ids or is_all_selected is provided
        if (!$validated['is_all_selected'] && empty($validated['recipient_ids'])) {
            return response()->json([
                'error' => 'Either recipient_ids or is_all_selected must be provided'
            ], 422);
        }

        if ($validated['is_all_selected'] && !empty($validated['recipient_ids'])) {
            return response()->json([
                'error' => 'Cannot use both recipient_ids and filters at the same time'
            ], 422);
        }

        try {
            // Create the campaign
            $campaign = $this->campaignService->createCampaign([
                'name' => $validated['name'],
                'description' => $validated['description'],
                'subject' => $validated['subject'],
                'body' => $validated['body'],
                'body_html' => $validated['body_html'] ?? $validated['body'],
                'email_account_id' => $validated['email_account_id'],
                'user_id' => $request->user()->id,
            ]);

            // Add recipients
            if ($validated['is_all_selected']) {
                // Build filters array
                $filters = [];

                if (isset($validated['stage_id'])) {
                    $filters['stage_id'] = $validated['stage_id'];
                }

                if (isset($validated['team_id'])) {
                    $filters['team_id'] = $validated['team_id'];
                }

                if (isset($validated['user_ids'])) {
                    $filters['user_ids'] = $validated['user_ids'];
                }

                if (isset($validated['search'])) {
                    $filters['search'] = $validated['search'];
                }

                if (isset($validated['deal_type_id'])) {
                    $filters['deal_type_id'] = $validated['deal_type_id'];
                }

                // Store filters in campaign for later use
                $campaign->update(['filters' => json_encode($filters)]);

                $this->campaignService->addRecipientsWithFilters($campaign, $filters);
            } else {
                $this->campaignService->addRecipients($campaign, $validated['recipient_ids']);
            }

            // Send the campaign using the job queue
            SendCampaignJob::dispatch($campaign);

            return response()->json([
                'message' => 'Campaign created and queued for sending successfully',
                'campaign' => $campaign->load('recipients'),
                'status' => 'queued_for_sending'
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to create and send campaign: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get campaign analytics
     */
    public function analytics(Campaign $campaign)
    {
        $analytics = $this->campaignService->getCampaignAnalytics($campaign);
        return response()->json($analytics);
    }

    /**
     * List all campaigns
     */
    public function index()
    {
        $campaigns = Campaign::with(['emailAccount', 'user'])
            ->orderBy('created_at', 'desc')
            ->paginate(15);
        return response()->json($campaigns);
    }

    /**
     * Get single campaign with details
     */
    public function show(Campaign $campaign)
    {
        return response()->json($campaign->load(['recipients.person', 'links', 'emailAccount']));
    }

    /**
     * Preview recipients for a campaign based on filters
     */
    public function previewRecipients(Request $request)
    {
        $validated = $request->validate([
            'stage_id' => 'nullable|integer|exists:stages,id',
            'team_id' => 'nullable|integer|exists:teams,id',
            'user_ids' => 'nullable|array',
            'user_ids.*' => 'integer|exists:users,id',
            'search' => 'nullable|string|max:255',
            'deal_type_id' => 'nullable|integer|exists:deal_types,id',
            'use_all_emails' => 'boolean',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        $filters = array_filter($validated, function ($key) {
            return !in_array($key, ['use_all_emails', 'per_page']);
        }, ARRAY_FILTER_USE_KEY);

        $recipients = $this->campaignService->previewRecipients(
            $filters,
            $validated['use_all_emails'] ?? false,
            $validated['per_page'] ?? 15
        );

        return response()->json([
            'message' => 'Recipients preview generated successfully',
            'recipients' => $recipients
        ]);
    }
}
