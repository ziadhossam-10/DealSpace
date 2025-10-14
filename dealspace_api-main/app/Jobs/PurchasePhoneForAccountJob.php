<?php

namespace App\Jobs;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Services\Auth\TwilioPhoneNumberServiceInterface;

class PurchasePhoneForAccountJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $user;
    public $countryCode;

    /**
     * Create a new job instance.
     */
    public function __construct(User $user, string $countryCode)
    {
        $this->user = $user;
        $this->countryCode = $countryCode;
    }

    /**
     * Execute the job.
     */
    public function handle(TwilioPhoneNumberServiceInterface $twilioPhoneNumberService): void
    {
        $twilioPhoneNumberService->provisionPhoneNumberForUser(
            $this->user,
            $this->countryCode
        );
    }
}
