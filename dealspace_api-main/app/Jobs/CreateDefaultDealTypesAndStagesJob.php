<?php

namespace App\Jobs;

use App\Models\DealType;
use App\Models\DealStage;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class CreateDefaultDealTypesAndStagesJob implements ShouldQueue
{
    use Dispatchable, Queueable;

    protected User $user;

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
        $defaultTypes = [
            ['name' => 'Buyers', 'sort' => 1],
            ['name' => 'Sellers', 'sort' => 2],
        ];

        $defaultStages = [
            ['name' => 'Offer', 'sort' => 1, 'color' => '#f59e0b'],
            ['name' => 'Offer Accepted', 'sort' => 2, 'color' => '#10b981'],
            ['name' => 'Pending', 'sort' => 3, 'color' => '#3b82f6'],
            ['name' => 'Closed', 'sort' => 4, 'color' => '#6366f1'],
        ];

        foreach ($defaultTypes as $typeData) {
            $dealType = DealType::create([
                'tenant_id' => $this->user->tenant_id,
                'name' => $typeData['name'],
                'sort' => $typeData['sort'],
            ]);

            foreach ($defaultStages as $stageData) {
                DealStage::create([
                    'tenant_id' => $this->user->tenant_id,
                    'type_id' => $dealType->id,
                    'name' => $stageData['name'],
                    'sort' => $stageData['sort'],
                    'color' => $stageData['color'],
                ]);
            }
        }
    }
}
