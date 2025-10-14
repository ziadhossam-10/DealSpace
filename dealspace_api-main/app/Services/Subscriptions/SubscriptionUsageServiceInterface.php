<?php

namespace App\Services\Subscriptions;

use App\Models\User;

interface SubscriptionUsageServiceInterface
{
    /**
     * Get user's current plan
     */
    public function getUserPlan(User $user): string;

    /**
     * Get usage data for a feature
     */
    public function getUsage(User $user, string $feature): array;

    /**
     * Check if user can use a feature
     */
    public function canUse(User $user, string $feature): bool;

    /**
     * Get all usage statistics for user
     */
    public function getUserUsageStats(User $user): array;

    /**
     * Check if feature usage would exceed limit
     */
    public function wouldExceedLimit(User $user, string $feature, int $additional = 1): bool;

    /**
     * Get upgrade recommendation based on usage
     */
    public function getUpgradeRecommendation(User $user): ?array;
}