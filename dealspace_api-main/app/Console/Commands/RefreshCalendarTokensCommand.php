<?php

namespace App\Console\Commands;

use App\Models\CalendarAccount;
use App\Services\CalendarAccounts\GoogleCalendarOAuthService;
use App\Services\CalendarAccounts\MicrosoftCalendarOAuthService;
use Illuminate\Console\Command;

class RefreshCalendarTokensCommand extends Command
{
    protected $signature = 'calendar-tokens:refresh';
    protected $description = 'Refresh expired tokens for calendar accounts';

    public function handle()
    {
        $expiredAccounts = CalendarAccount::where('token_expires_at', '<', now()->addMinutes(10))
            ->get();

        if ($expiredAccounts->isEmpty()) {
            $this->info('No calendar accounts need token refresh.');
            return;
        }

        $this->info("Found {$expiredAccounts->count()} calendar accounts with expiring tokens.");

        foreach ($expiredAccounts as $account) {
            $service = $account->provider === 'google'
                ? app(GoogleCalendarOAuthService::class)
                : app(MicrosoftCalendarOAuthService::class);

            $refreshed = $service->refreshToken($account);

            $status = $refreshed ? 'Token refreshed' : 'Failed to refresh';
            $this->info("Account {$account->email} ({$account->provider}): {$status}");

            // If refresh failed, also try to refresh webhook since token might be needed
            if (!$refreshed) {
                $this->warn("  - Account {$account->email} marked as inactive due to token refresh failure");
            } else {
                // Optionally refresh webhook if token was successfully refreshed
                if ($account->webhook_expires_at && $account->webhook_expires_at < now()->addDays(1)) {
                    $calendarService = app(\App\Services\CalendarAccounts\CalendarAccountService::class);
                    $webhookRefreshed = $calendarService->refreshWebhook($account);
                    $webhookStatus = $webhookRefreshed ? 'Webhook refreshed' : 'Webhook refresh failed';
                    $this->info("  - {$webhookStatus}");
                }
            }
        }

        $this->info('Calendar token refresh completed.');
    }
}