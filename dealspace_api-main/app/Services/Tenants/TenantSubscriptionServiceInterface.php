<?php

namespace App\Services\Tenants;

use App\Models\Tenant;
use App\Models\User;

interface TenantSubscriptionServiceInterface
{
    /**
     * Get the subscription holder for a tenant.
     *
     * @param Tenant $tenant
     * @return User|null
     */
    public function getSubscriptionHolder(Tenant $tenant): ?User;

    /**
     * Check if tenant has active subscription.
     *
     * @param Tenant $tenant
     * @return bool
     */
    public function hasActiveSubscription(Tenant $tenant): bool;

    /**
     * Get tenant's subscription details.
     *
     * @param Tenant $tenant
     * @return array|null
     */
    public function getSubscriptionDetails(Tenant $tenant): ?array;

    /**
     * Get tenant from user's tenant_id.
     *
     * @param User $user
     * @return Tenant|null
     */
    public function getTenantFromUser(User $user): ?Tenant;

    /**
     * Check if any user in tenant can manage subscriptions (owner/admin).
     *
     * @param User $user
     * @param Tenant $tenant
     * @return bool
     */
    public function canManageSubscription(User $user, Tenant $tenant): bool;

    /**
     * Transfer subscription holder to another owner/admin.
     *
     * @param Tenant $tenant
     * @param User $newHolder
     * @return bool
     */
    public function transferSubscriptionHolder(Tenant $tenant, User $newHolder): bool;

    /**
     * Create new subscription for tenant.
     *
     * @param Tenant $tenant
     * @param string $planKey
     * @return string Checkout URL
     */
    public function createSubscription(Tenant $tenant, string $planKey): string;

    /**
     * Change tenant's subscription plan.
     *
     * @param Tenant $tenant
     * @param string $newPlanKey
     * @return array
     */
    public function changePlan(Tenant $tenant, string $newPlanKey): array;

    /**
     * Cancel subscription at end of period.
     *
     * @param Tenant $tenant
     * @return void
     */
    public function cancelSubscription(Tenant $tenant): void;

    /**
     * Cancel subscription immediately.
     *
     * @param Tenant $tenant
     * @return void
     */
    public function cancelSubscriptionNow(Tenant $tenant): void;

    /**
     * Resume canceled subscription.
     *
     * @param Tenant $tenant
     * @return void
     */
    public function resumeSubscription(Tenant $tenant): void;
}