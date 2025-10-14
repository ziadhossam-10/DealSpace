<?php

namespace App\Services\Campaigns;

use App\Models\Campaign;
use App\Models\CampaignClick;
use App\Models\CampaignRecipient;
use App\Models\CampaignLink;
use App\Models\Person;
use App\Models\Team;
use App\Services\Emails\EmailServiceInterface;
use App\Repositories\People\PeopleRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;

class CampaignService
{
    private $emailService;
    private $peopleRepository;

    public function __construct(EmailServiceInterface $emailService, PeopleRepositoryInterface $peopleRepository)
    {
        $this->emailService = $emailService;
        $this->peopleRepository = $peopleRepository;
    }

    /**
     * Create a new campaign
     */
    public function createCampaign(array $data): Campaign
    {
        return Campaign::create($data);
    }

    /**
     * Add recipients to a campaign by IDs
     */
    public function addRecipients(Campaign $campaign, array $personIds): void
    {
        $recipients = [];

        foreach ($personIds as $personId) {
            $person = Person::find($personId);
            if ($person) {
                $emails = $this->getPersonEmails($person, $campaign->use_all_emails);

                foreach ($emails as $email) {
                    $recipients[] = [
                        'campaign_id' => $campaign->id,
                        'person_id' => $person->id,
                        'email' => $email,
                        'tracking_token' => bin2hex(random_bytes(16)),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
            }
        }

        if (!empty($recipients)) {
            CampaignRecipient::insert($recipients);
        }

        // Update total recipients count
        $campaign->update(['total_recipients' => count($recipients)]);
    }

    /**
     * Add recipients to a campaign using filters
     */
    public function addRecipientsWithFilters(Campaign $campaign, array $filters): void
    {
        $recipients = [];

        // Get all people matching the filters
        $query = Person::query();
        $this->applyFilters($query, $filters);

        $people = $query->get();

        foreach ($people as $person) {
            $emails = $this->getPersonEmails($person, $campaign->use_all_emails);

            foreach ($emails as $email) {
                $recipients[] = [
                    'campaign_id' => $campaign->id,
                    'person_id' => $person->id,
                    'email' => $email,
                    'tracking_token' => bin2hex(random_bytes(16)),
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        if (!empty($recipients)) {
            CampaignRecipient::insert($recipients);
        }

        // Update total recipients count
        $campaign->update(['total_recipients' => count($recipients)]);
    }

    /**
     * Get emails for a person based on campaign settings
     */
    private function getPersonEmails(Person $person, bool | null $useAllEmails): array
    {
        $emails = [];

        if ($useAllEmails) {
            // Get all active emails for the person
            $emailAccounts = $person->emailAccounts()
                ->get();

            foreach ($emailAccounts as $emailAccount) {
                $emails[] = $emailAccount->value;
            }
        } else {
            // Get only the primary email
            $primaryEmail = $person->emailAccounts()
                ->where('is_primary', true)
                ->first();

            if ($primaryEmail) {
                $emails[] = $primaryEmail->value;
            }
        }

        return $emails ?? [];
    }

    /**
     * Apply filters to a query (same logic as PeopleRepository)
     */
    private function applyFilters($query, array $filters): void
    {
        if (isset($filters['stage_id'])) {
            $query->where('stage_id', $filters['stage_id']);
        }

        if (isset($filters['team_id'])) {
            $team = Team::find($filters['team_id']);
            $users = $team ? $team->users()->pluck('user_id')->toArray() : [];
            $leaders = $team ? $team->leaders()->pluck('user_id')->toArray() : [];

            $query->where(function ($q) use ($users, $leaders) {
                $q->whereIn('assigned_lender_id', array_merge($users, $leaders))
                    ->orWhereIn('assigned_user_id', array_merge($users, $leaders))
                    ->orWhereHas('collaborators', function ($q) use ($users, $leaders) {
                        $q->whereIn('user_id', array_merge($users, $leaders));
                    });
            });
        }

        if (isset($filters['user_ids'])) {
            $query->where(function ($q) use ($filters) {
                $q->whereIn('assigned_lender_id', $filters['user_ids'])
                    ->orWhereIn('assigned_user_id', $filters['user_ids'])
                    ->orWhereHas('collaborators', function ($q) use ($filters) {
                        $q->whereIn('user_id', $filters['user_ids']);
                    });
            });
        }

        if (isset($filters['deal_type_id'])) {
            $query->whereHas('deals', function ($q) use ($filters) {
                $q->where('type_id', $filters['deal_type_id']);
            });
        }

        if (isset($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%")
                    ->orWhereHas('emailAccounts', function ($q) use ($search) {
                        $q->where('value', 'like', "%{$search}%");
                    })
                    ->orWhereHas('phones', function ($q) use ($search) {
                        $q->where('value', 'like', "%{$search}%");
                    });
            });
        }
    }

    /**
     * Preview recipients based on filters
     */
    public function previewRecipients(array $filters, bool $useAllEmails = false, int $perPage = 15): LengthAwarePaginator
    {
        $query = Person::query();
        $this->applyFilters($query, $filters);

        // Only get people with active emails
        $query->whereHas('emailAccounts', function ($q) use ($useAllEmails) {
            $q->where('status', 'active');
            if (!$useAllEmails) {
                $q->where('is_primary', true);
            }
        });

        return $query->with(['emailAccounts' => function ($q) use ($useAllEmails) {
            $q->where('status', 'active');
            if (!$useAllEmails) {
                $q->where('is_primary', true);
            }
        }])->paginate($perPage);
    }

    /**
     * Process email content and extract/replace links for tracking
     */
    public function processEmailContent(Campaign $campaign): string
    {
        $content = $campaign->body_html ?: $campaign->body;

        // Find all links in the content
        preg_match_all('/<a[^>]+href=["\']([^"\']+)["\'][^>]*>/', $content, $matches, PREG_SET_ORDER);

        foreach ($matches as $match) {
            $originalUrl = $match[1];

            // Skip mailto and tel links
            if (str_starts_with($originalUrl, 'mailto:') || str_starts_with($originalUrl, 'tel:')) {
                continue;
            }

            // Create or find existing campaign link
            $campaignLink = CampaignLink::firstOrCreate([
                'campaign_id' => $campaign->id,
                'original_url' => $originalUrl,
            ]);

            // Replace the original URL with tracking URL
            $trackingUrl = route('campaign.click', [
                'token' => $campaignLink->tracking_token,
                'recipient' => '{{RECIPIENT_TOKEN}}'
            ]);

            $content = str_replace($originalUrl, $trackingUrl, $content);
        }

        return $content;
    }

    /**
     * Generate improved tracking pixel with better deliverability
     */
    private function generateTrackingPixel(string $token): string
    {
        $trackingUrl = config('app.url') . "/api/campaigns/tracking/open/{$token}";

        // Use multiple formats for better compatibility
        $pixel = '<div style="display: none !important; visibility: hidden !important; opacity: 0 !important; color: transparent !important; height: 0 !important; width: 0 !important;">';

        // Primary tracking pixel (1x1 GIF)
        $pixel .= '<img src="' . $trackingUrl . '?t=' . time() . '&r=' . rand(1000, 9999) . '" width="1" height="1" border="0" style="display: block !important; visibility: hidden !important; opacity: 0 !important; width: 1px !important; height: 1px !important; border: none !important; margin: 0 !important; padding: 0 !important;" alt="" />';

        // Backup tracking methods
        $pixel .= '<img src="' . $trackingUrl . '?format=png&t=' . time() . '" width="1" height="1" style="display: none;" alt="" />';

        // CSS background image method (some clients load these)
        $pixel .= '<div style="background-image: url(\'' . $trackingUrl . '?format=bg&t=' . time() . '\'); width: 1px; height: 1px; display: block; visibility: hidden;"></div>';

        $pixel .= '</div>';

        return $pixel;
    }

    /**
     * Send campaign emails
     */
    public function sendCampaign(Campaign $campaign): void
    {
        DB::transaction(function () use ($campaign) {
            // Update campaign status
            $campaign->update([
                'status' => 'sending',
                'sent_at' => now()
            ]);

            // Process email content for link tracking
            $processedContent = $this->processEmailContent($campaign);

            $recipients = $campaign->recipients()->where('status', 'pending')->get();

            foreach ($recipients as $recipient) {
                try {
                    // Replace recipient token in content
                    $personalizedContent = str_replace('{{RECIPIENT_TOKEN}}', $recipient->tracking_token, $processedContent);

                    // Add improved tracking pixel for open tracking
                    $trackingPixel = $this->generateTrackingPixel($recipient->tracking_token);

                    // Insert tracking pixel before closing body tag, or append if no body tag
                    if (strpos($personalizedContent, '</body>') !== false) {
                        $personalizedContent = str_replace('</body>', $trackingPixel . '</body>', $personalizedContent);
                    } else {
                        $personalizedContent .= $trackingPixel;
                    }

                    Log::info('Campaign email prepared', [
                        'campaign_id' => $campaign->id,
                        'recipient_id' => $recipient->id,
                        'tracking_token' => $recipient->tracking_token,
                        'has_tracking_pixel' => strpos($personalizedContent, 'track/open') !== false
                    ]);

                    // Send email
                    $emailData = [
                        'account_id' => $campaign->email_account_id,
                        'person_id' => $recipient->person_id,
                        'subject' => $campaign->subject,
                        'body' => strip_tags($personalizedContent),
                        'body_html' => $personalizedContent,
                        'to_email' => $recipient->email,
                    ];

                    $this->emailService->sendEmail($emailData, $campaign->user_id);

                    // Update recipient status
                    $recipient->update([
                        'status' => 'sent',
                        'sent_at' => now()
                    ]);

                    $campaign->increment('emails_sent');
                } catch (\Exception $e) {
                    Log::error('Campaign email send failed', [
                        'campaign_id' => $campaign->id,
                        'recipient_id' => $recipient->id,
                        'error' => $e->getMessage()
                    ]);

                    // Update recipient with failure
                    $recipient->update([
                        'status' => 'failed',
                        'failure_reason' => $e->getMessage()
                    ]);
                }
            }

            // Update final campaign status
            $campaign->update(['status' => 'sent']);
        });
    }

    /**
     * Track email open with improved logging and duplicate detection
     */
    public function trackOpen(string $token): void
    {
        Log::info('Open tracking attempt', [
            'token' => $token,
            'timestamp' => now(),
            'user_agent' => request()->userAgent(),
            'ip' => request()->ip()
        ]);

        $recipient = CampaignRecipient::where('tracking_token', $token)->first();

        if (!$recipient) {
            Log::warning('Open tracking failed - recipient not found', ['token' => $token]);
            return;
        }

        // Track multiple opens but only count unique opens for campaign stats
        $isFirstOpen = is_null($recipient->opened_at);

        $recipient->update([
            'status' => 'opened',
            'opened_at' => $recipient->opened_at ?: now(), // Keep first open time
            'last_opened_at' => now(), // Track latest open
            'open_count' => $recipient->open_count + 1
        ]);

        // Only increment campaign counter for first open
        if ($isFirstOpen) {
            $recipient->campaign->increment('emails_opened');
            Log::info('First email open tracked', [
                'campaign_id' => $recipient->campaign_id,
                'recipient_id' => $recipient->id,
                'token' => $token
            ]);
        } else {
            Log::info('Additional email open tracked', [
                'campaign_id' => $recipient->campaign_id,
                'recipient_id' => $recipient->id,
                'token' => $token,
                'open_count' => $recipient->open_count
            ]);
        }
    }

    /**
     * Track email click
     */
    public function trackClick(string $linkToken, string $recipientToken, array $metadata = []): string
    {
        $link = CampaignLink::where('tracking_token', $linkToken)->first();
        $recipient = CampaignRecipient::where('tracking_token', $recipientToken)->first();

        if ($link && $recipient && $recipient->campaign_id === $link->campaign_id) {
            // Record the click
            CampaignClick::create([
                'campaign_id' => $link->campaign_id,
                'campaign_recipient_id' => $recipient->id,
                'campaign_link_id' => $link->id,
                'ip_address' => $metadata['ip'] ?? null,
                'user_agent' => $metadata['user_agent'] ?? null,
                'metadata' => $metadata,
            ]);

            // Update recipient click tracking (first click marks as clicked)
            $isFirstClick = is_null($recipient->first_clicked_at);

            $recipient->update([
                'status' => 'clicked',
                'first_clicked_at' => $recipient->first_clicked_at ?: now(),
                'last_clicked_at' => now(),
                'click_count' => $recipient->click_count + 1
            ]);

            // Update link and campaign counts
            $link->increment('click_count');

            // Only increment campaign counter for first click per recipient
            if ($isFirstClick) {
                $recipient->campaign->increment('emails_clicked');
            }

            Log::info('Email click tracked', [
                'campaign_id' => $link->campaign_id,
                'recipient_id' => $recipient->id,
                'link_id' => $link->id,
                'is_first_click' => $isFirstClick,
                'total_clicks' => $recipient->click_count
            ]);

            return $link->original_url;
        }

        throw new \Exception('Invalid tracking tokens');
    }

    /**
     * Get campaign analytics
     */
    public function getCampaignAnalytics(Campaign $campaign): array
    {
        return [
            'total_recipients' => $campaign->total_recipients,
            'emails_sent' => $campaign->emails_sent,
            'emails_delivered' => $campaign->emails_delivered,
            'emails_opened' => $campaign->emails_opened,
            'emails_clicked' => $campaign->emails_clicked,
            'emails_bounced' => $campaign->emails_bounced,
            'emails_unsubscribed' => $campaign->emails_unsubscribed,
            'open_rate' => $campaign->open_rate,
            'click_rate' => $campaign->click_rate,
            'bounce_rate' => $campaign->bounce_rate,
            'top_links' => $campaign->links()
                ->orderBy('click_count', 'desc')
                ->limit(10)
                ->get(),
            'recipient_breakdown' => $campaign->recipients()
                ->selectRaw('status, COUNT(*) as count')
                ->groupBy('status')
                ->get()
        ];
    }
}
