<?php

namespace App\Jobs;

use App\Models\Stage;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class CreateDefaultStagesJob implements ShouldQueue
{
    use Dispatchable, Queueable;

    protected $user;

    /**
     * Create a new job instance.
     */
    public function __construct(User $user)
    {
        $this->user = $user;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $defaultStages = [
            ['name' => 'New Lead', 'description' => 'A new, uncontacted inquiry.', 'slug' => 'new-lead'],
            ['name' => 'Hot Prospect', 'description' => "A lead you've spoken with who is looking to transact within the next 90 days.", 'slug' => 'hot-prospect'],
            ['name' => 'Nurture', 'description' => 'A lead with a longer-term timeline (3+ months).', 'slug' => 'nurture'],
            ['name' => 'Active Client', 'description' => 'A client you are actively working with under a signed agreement.', 'slug' => 'active-client'],
            ['name' => 'Closed', 'description' => 'A transaction has been successfully completed.', 'slug' => 'closed'],
            ['name' => 'Past Client', 'description' => 'A former client whose transaction is complete.', 'slug' => 'past-client'],
            ['name' => 'Sphere of Influence (SOI)', 'description' => 'Personal contacts, friends, and family who are not currently active leads but are valuable for referrals.', 'slug' => 'soi'],
            ['name' => 'Unresponsive', 'description' => 'A lead who has stopped responding to your outreach attempts.', 'slug' => 'unresponsive'],
            ['name' => 'Trash', 'description' => 'A lead that is unqualified or has requested not to be contacted.', 'slug' => 'trash'],
        ];

        foreach ($defaultStages as $stage) {
            Stage::create([
                'tenant_id' => $this->user->tenant_id,
                'name' => $stage['name'],
                'description' => $stage['description'],
                'slug' => $stage['slug'],
            ]);
        }
    }
}
