<?php

namespace App\Jobs;

use App\Models\AppointmentOutcome;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class CreateDefaultAppointmentOutcomesJob implements ShouldQueue
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
        $defaultAppointmentOutcomes = [
            ['name' => 'Attended', 'description' => 'Appointment took place as scheduled.'],
            ['name' => 'No Show', 'description' => 'Client did not attend the appointment.'],
            ['name' => 'Rescheduled', 'description' => 'Appointment was moved to a different time/date.'],
            ['name' => 'Canceled', 'description' => 'Appointment was canceled.'],
            ['name' => 'Working with Buyer', 'description' => 'Client agreed to work as a buyer.'],
            ['name' => 'Working with Seller', 'description' => 'Client agreed to work as a seller.'],
            ['name' => 'Not a Fit', 'description' => 'Client was not a good fit for services.'],
            ['name' => 'Lost Opportunity', 'description' => 'Opportunity was lost after the appointment.'],
            ['name' => 'Contract Signed', 'description' => 'A contract was signed during/after the appointment.'],
            ['name' => 'Needs Follow-up', 'description' => 'Further action is required after the appointment.'],
        ];

        $sortOrder = 1;
        foreach ($defaultAppointmentOutcomes as $outcome) {
            AppointmentOutcome::create([
                'tenant_id' => $this->user->tenant_id,
                'name' => $outcome['name'],
                'sort' => $sortOrder,
                'description' => $outcome['description'],
            ]);
            $sortOrder++;
        }
    }
}
