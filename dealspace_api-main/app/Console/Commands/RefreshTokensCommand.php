<?php

namespace App\Console\Commands;

use App\Models\EmailAccount;
use App\Services\EmailAccounts\GoogleOAuthService;
use App\Services\EmailAccounts\MicrosoftOAuthService;
use Illuminate\Console\Command;

class RefreshTokensCommand extends Command
{
    protected $signature = 'tokens:refresh';
    protected $description = 'Refresh expired tokens for email accounts';

    public function handle()
    {
        $expiredAccounts = EmailAccount::where('token_expires_at', '<', now()->addMinutes(10))
            ->get();

        foreach ($expiredAccounts as $account) {
            $service = $account->provider === 'gmail'
                ? app(GoogleOAuthService::class)
                : app(MicrosoftOAuthService::class);

            $refreshed = $service->refreshToken($account);

            $this->info("Account {$account->email} ({$account->provider}): " .
                ($refreshed ? 'Token refreshed' : 'Failed to refresh'));
        }
    }
}
