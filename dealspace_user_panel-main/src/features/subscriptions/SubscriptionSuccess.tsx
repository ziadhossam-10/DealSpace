import { useEffect, useState } from 'react';
import { useNavigate, useSearchParams } from 'react-router-dom';
import { useVerifyCheckoutSessionMutation } from './subscriptionApi';
import { toast } from 'react-toastify';

export function SubscriptionSuccess() {
  const [searchParams] = useSearchParams();
  const navigate = useNavigate();
  const [verifySession, { isLoading }] = useVerifyCheckoutSessionMutation();
  const [verified, setVerified] = useState(false);

  useEffect(() => {
    const sessionId = searchParams.get('session_id');

    if (!sessionId) {
      toast.error('Invalid session');
      navigate('/subscriptions/plans');
      return;
    }

    const verify = async () => {
      try {
        const response = await verifySession(sessionId).unwrap();
        
        if (response.success) {
          setVerified(true);
          toast.success('Subscription activated successfully!');

          // Redirect to subscription status after 3 seconds
          setTimeout(() => {
            navigate('/subscriptions/status');
          }, 3000);
        }
      } catch (error: any) {
        console.error('Verification error:', error);
        toast.error('Failed to verify subscription');
        navigate('/subscriptions/plans');
      }
    };

    verify();
  }, [searchParams, verifySession, navigate]);

  if (isLoading) {
    return (
      <div className="flex items-center justify-center min-h-screen bg-gradient-to-br from-gray-50 to-gray-100">
        <div className="text-center">
          <div className="animate-spin rounded-full h-16 w-16 border-b-4 border-blue-600 mx-auto mb-4"></div>
          <p className="text-gray-600 font-medium">Verifying your subscription...</p>
        </div>
      </div>
    );
  }

  if (verified) {
    return (
      <div className="flex items-center justify-center min-h-screen bg-gradient-to-br from-green-50 to-blue-50">
        <div className="text-center max-w-md mx-auto p-8">
          <div className="bg-green-100 rounded-full w-20 h-20 flex items-center justify-center mx-auto mb-6">
            <svg className="w-12 h-12 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M5 13l4 4L19 7" />
            </svg>
          </div>
          <h1 className="text-3xl font-bold text-gray-900 mb-4">
            Welcome to DealSpace!
          </h1>
          <p className="text-gray-600 mb-6">
            Your subscription has been activated successfully. You now have access to all features.
          </p>
          <div className="animate-pulse text-sm text-gray-500">
            Redirecting to dashboard...
          </div>
        </div>
      </div>
    );
  }

  return null;
}