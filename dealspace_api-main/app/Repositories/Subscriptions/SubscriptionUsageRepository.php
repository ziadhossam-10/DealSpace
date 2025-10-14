<?php

namespace App\Repositories\Subscriptions;

use App\Models\SubscriptionUsage;

class SubscriptionUsageRepository implements SubscriptionUsageRepositoryInterface
{
    public function getByUserAndFeature(int $userId, string $feature): ?SubscriptionUsage
    {
        return SubscriptionUsage::where('user_id', $userId)
            ->where('feature', $feature)
            ->latest('period_start')
            ->first();
    }

    public function create(array $data): SubscriptionUsage
    {
        return SubscriptionUsage::create($data);
    }

    public function update(SubscriptionUsage $usage, array $data): bool
    {
        return $usage->update($data);
    }
}
