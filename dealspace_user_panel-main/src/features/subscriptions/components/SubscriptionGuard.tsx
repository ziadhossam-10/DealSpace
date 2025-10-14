import { ReactNode } from 'react';
import { useGetStatusQuery } from '../subscriptionApi';
import { Link } from 'react-router-dom';

interface SubscriptionGuardProps {
  children: ReactNode;
  feature?: string;
  fallback?: ReactNode;
}

export function SubscriptionGuard({ children, feature, fallback }: SubscriptionGuardProps) {
  const { data: statusData, isLoading } = useGetStatusQuery(undefined);

  if (isLoading) {
    return (
      <div className="flex items-center justify-center p-8">
        <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
      </div>
    );
  }

  const isSubscribed = statusData?.data?.subscribed;

  if (!isSubscribed) {
    if (fallback) {
      return <>{fallback}</>;
    }

    return (
      <div className="bg-gradient-to-br from-blue-50 to-indigo-100 rounded-lg shadow-lg p-8 text-center">
        <div className="max-w-md mx-auto">
          <div className="bg-white rounded-full w-16 h-16 flex items-center justify-center mx-auto mb-4">
            <svg
              className="w-8 h-8 text-blue-600"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path
                strokeLinecap="round"
                strokeLinejoin="round"
                strokeWidth={2}
                d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"
              />
            </svg>
          </div>
          <h2 className="text-2xl font-bold text-gray-900 mb-2">
            Subscription Required
          </h2>
          <p className="text-gray-600 mb-6">
            {feature
              ? `This ${feature} feature requires an active subscription.`
              : 'An active subscription is required to access this feature.'}
          </p>
          <Link
            to="/subscriptions/plans"
            className="inline-block bg-blue-600 text-white px-6 py-3 rounded-lg font-semibold hover:bg-blue-700 transition-colors"
          >
            View Subscription Plans
          </Link>
        </div>
      </div>
    );
  }

  return <>{children}</>;
}