import { useGetStatusQuery, useGetUsageQuery, useCreatePortalSessionMutation } from './subscriptionApi';
import { UsageBadge } from './components/UsageBadge';
import { toast } from 'react-toastify';

export function SubscriptionStatus() {
  const { data: statusData, isLoading: statusLoading } = useGetStatusQuery({});
  const { data: usageData, isLoading: usageLoading } = useGetUsageQuery(undefined);
  const [createPortalSession, { isLoading: isCreatingPortal }] = useCreatePortalSessionMutation();

  const handleManageSubscription = async () => {
    try {
      const response = await createPortalSession(undefined).unwrap();
      
      if (response.success && response.data.portal_url) {
        window.location.href = response.data.portal_url;
      }
    } catch (error: any) {
      console.error('Portal error:', error);
      toast.error(error?.data?.message || 'Failed to open billing portal');
    }
  };

  if (statusLoading || usageLoading) {
    return (
      <div className="flex items-center justify-center p-8">
        <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
      </div>
    );
  }

  const subscription = statusData?.data?.subscription;
  const usage = usageData?.data;

  if (!subscription) {
    return (
      <div className="bg-white rounded-lg shadow p-6 text-center">
        <h3 className="text-xl font-bold text-gray-900 mb-4">No Active Subscription</h3>
        <p className="text-gray-600 mb-6">Subscribe to unlock all features</p>
        <a
          href="/admin/subscriptions/plans"
          className="inline-block bg-blue-600 text-white px-6 py-3 rounded-lg font-semibold hover:bg-blue-700"
        >
          View Plans
        </a>
      </div>
    );
  }

  return (
    <div className="space-y-6">
      {/* Subscription Card */}
      <div className="bg-white rounded-lg shadow p-6">
        <div className="flex items-center justify-between mb-4">
          <h3 className="text-xl font-bold text-gray-900">Your Subscription</h3>
          <button
            onClick={handleManageSubscription}
            disabled={isCreatingPortal}
            className="px-4 py-2 bg-blue-600 text-white rounded-lg font-semibold hover:bg-blue-700 disabled:opacity-50"
          >
            {isCreatingPortal ? 'Loading...' : 'Manage Subscription'}
          </button>
        </div>

        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <p className="text-sm text-gray-600">Current Plan</p>
            <p className="text-lg font-bold text-gray-900 capitalize">{subscription.plan}</p>
          </div>
          <div>
            <p className="text-sm text-gray-600">Status</p>
            <span className={`inline-flex items-center px-3 py-1 rounded-full text-sm font-medium ${
              subscription.stripe_status === 'active' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'
            }`}>
              {subscription.stripe_status}
            </span>
          </div>
        </div>

        {subscription.cancel_at_period_end && (
          <div className="mt-4 p-4 bg-yellow-50 border border-yellow-200 rounded-lg">
            <p className="text-sm text-yellow-800">
              Your subscription will be cancelled on {new Date(subscription.current_period_end * 1000).toLocaleDateString()}
            </p>
          </div>
        )}
      </div>

      {/* Usage Card */}
      {usage && (
        <div className="bg-white rounded-lg shadow p-6">
          <h3 className="text-xl font-bold text-gray-900 mb-4">Usage This Period</h3>
          
          <div className="space-y-4">
            {Object.entries(usage.usage).map(([feature, stats]: [string, any]) => (
              <div key={feature} className="flex items-center justify-between">
                <span className="text-gray-700 capitalize">{feature}</span>
                <UsageBadge feature={feature} />
              </div>
            ))}
          </div>

          {usage.billing_period && (
            <div className="mt-6 pt-4 border-t border-gray-200">
              <p className="text-sm text-gray-600">
                Billing period ends in {usage.billing_period.days_remaining} days
              </p>
              <p className="text-xs text-gray-500 mt-1">
                {usage.billing_period.start} - {usage.billing_period.end}
              </p>
            </div>
          )}
        </div>
      )}
    </div>
  );
}