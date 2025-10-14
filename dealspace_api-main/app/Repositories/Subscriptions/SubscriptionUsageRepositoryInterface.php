<?php

namespace App\Repositories\Subscriptions;

use App\Models\SubscriptionUsage;

interface SubscriptionUsageRepositoryInterface
{
    public function getByUserAndFeature(int $userId, string $feature): ?SubscriptionUsage;
    public function create(array $data): SubscriptionUsage;
    public function update(SubscriptionUsage $usage, array $data): bool;
}
