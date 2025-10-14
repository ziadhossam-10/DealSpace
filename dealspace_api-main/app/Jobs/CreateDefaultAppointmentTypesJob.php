<?php

namespace App\Jobs;

use App\Models\AppointmentType;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class CreateDefaultAppointmentTypesJob implements ShouldQueue
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
        $defaultAppointmentTypes = [
            ['name' => 'Buyer Consultation', 'description' => 'Initial meeting with a prospective buyer.'],
            ['name' => 'Seller Consultation', 'description' => 'Initial meeting with a prospective seller.'],
            ['name' => 'Property Showing', 'description' => 'Touring properties with a client.'],
            ['name' => 'Open House', 'description' => 'Scheduled open house event.'],
            ['name' => 'Closing', 'description' => 'Finalization of a transaction.'],
            ['name' => 'Follow-up Call', 'description' => 'Scheduled call for follow-up.'],
            ['name' => 'Virtual Meeting', 'description' => 'Online meeting with a client.'],
            ['name' => 'Pre-qualification Call', 'description' => 'Assessing client financial readiness.'],
        ];

        $sortOrder = 1;
        foreach ($defaultAppointmentTypes as $type) {
            AppointmentType::create([
                'tenant_id' => $this->user->tenant_id,
                'name' => $type['name'],
                'sort' => $sortOrder,
                'description' => $type['description'],
            ]);
            $sortOrder++;
        }
    }
}
