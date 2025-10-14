import { useGetStatusQuery, useGetUsageQuery, useLazyCheckFeatureQuery } from '../subscriptionApi';
import { useEffect } from 'react';
import { toast } from 'react-toastify';

export function useSubscriptionAccess() {
  const { data: statusData, isLoading: statusLoading } = useGetStatusQuery(undefined);
  const { data: usageData, isLoading: usageLoading } = useGetUsageQuery(undefined);
  const [checkFeature] = useLazyCheckFeatureQuery();

  const isSubscribed = statusData?.data?.subscribed || false;
  const subscription = statusData?.data?.subscription;
  const usage = usageData?.data;

  /**
   * Check if user can access a feature
   */
  const canAccessFeature = async (feature: string): Promise<boolean> => {
    if (!isSubscribed) {
      toast.error('Active subscription required to access this feature');
      return false;
    }

    try {
      const response = await checkFeature(feature).unwrap();
      if (!response.success || !response.data.can_use) {
        const message = response.message || 'Feature limit reached for your plan';
        toast.error(message);
        return false;
      }
      return true;
    } catch (error: any) {
      toast.error(error?.data?.message || 'Unable to verify feature access');
      return false;
    }
  };

  /**
   * Get usage stats for a specific feature
   */
  const getFeatureUsage = (feature: string) => {
    if (!usage?.usage) return null;
    return usage.usage[feature];
  };

  /**
   * Check if a feature limit is reached
   */
  const isFeatureLimitReached = (feature: string): boolean => {
    const featureUsage = getFeatureUsage(feature);
    return featureUsage?.limit_reached || false;
  };

  /**
   * Get remaining usage for a feature
   */
  const getRemainingUsage = (feature: string): number | null => {
    const featureUsage = getFeatureUsage(feature);
    return featureUsage?.remaining || null;
  };

  return {
    isSubscribed,
    subscription,
    usage,
    isLoading: statusLoading || usageLoading,
    canAccessFeature,
    getFeatureUsage,
    isFeatureLimitReached,
    getRemainingUsage,
  };
}