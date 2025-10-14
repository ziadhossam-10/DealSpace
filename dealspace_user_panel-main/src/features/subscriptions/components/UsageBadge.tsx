import { useGetUsageQuery } from '../subscriptionApi';

interface UsageBadgeProps {
  feature: string;
  showDetails?: boolean;
}

export function UsageBadge({ feature, showDetails = true }: UsageBadgeProps) {
  const { data: usageData, isLoading } = useGetUsageQuery(undefined);

  if (isLoading || !usageData?.data?.usage) {
    return null;
  }

  const usage = usageData.data.usage[feature];

  if (!usage) {
    return null;
  }

  const percentage = usage.limit
    ? Math.min((usage.used / usage.limit) * 100, 100)
    : 0;

  const getColorClass = () => {
    if (usage.unlimited) return 'bg-green-100 text-green-800 border-green-200';
    if (percentage >= 90) return 'bg-red-100 text-red-800 border-red-200';
    if (percentage >= 70) return 'bg-yellow-100 text-yellow-800 border-yellow-200';
    return 'bg-blue-100 text-blue-800 border-blue-200';
  };

  return (
    <div className={`inline-flex items-center px-3 py-1 rounded-full text-sm font-medium border ${getColorClass()}`}>
      {usage.unlimited ? (
        <span>Unlimited</span>
      ) : (
        <>
          <span>
            {usage.used} / {usage.limit}
          </span>
          {showDetails && usage.limit_reached && (
            <svg
              className="w-4 h-4 ml-1"
              fill="currentColor"
              viewBox="0 0 20 20"
            >
              <path
                fillRule="evenodd"
                d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z"
                clipRule="evenodd"
              />
            </svg>
          )}
        </>
      )}
    </div>
  );
}