import { useEffect, useState } from 'react'
import { useSearchParams, useNavigate } from 'react-router-dom'
import { useVerifyCheckoutSessionMutation } from './subscriptionApi'
import { CheckCircle, XCircle, Loader2 } from 'lucide-react'

export default function VerifySubscription() {
  const [searchParams] = useSearchParams()
  const navigate = useNavigate()
  const [verify, { isLoading }] = useVerifyCheckoutSessionMutation()
  const [status, setStatus] = useState<'verifying' | 'success' | 'error'>('verifying')

  useEffect(() => {
    const sessionId = searchParams.get('session_id')
    if (!sessionId) {
      navigate('/admin/subscriptions')
      return
    }

    verify({ session_id: sessionId })
      .unwrap()
      .then(() => {
        setStatus('success')
        setTimeout(() => navigate('/admin/subscriptions'), 2000)
      })
      .catch(() => {
        setStatus('error')
      })
  }, [searchParams, verify, navigate])

  return (
    <div className="min-h-screen flex items-center justify-center bg-gray-50">
      <div className="max-w-md w-full bg-white rounded-lg shadow-lg p-8 text-center">
        {status === 'verifying' && (
          <>
            <Loader2 className="w-16 h-16 text-blue-600 mx-auto mb-4 animate-spin" />
            <h2 className="text-2xl font-semibold text-gray-900 mb-2">Verifying Payment</h2>
            <p className="text-gray-600">Please wait while we confirm your subscription...</p>
          </>
        )}

        {status === 'success' && (
          <>
            <CheckCircle className="w-16 h-16 text-green-600 mx-auto mb-4" />
            <h2 className="text-2xl font-semibold text-gray-900 mb-2">Payment Successful!</h2>
            <p className="text-gray-600">Your subscription is now active. Redirecting...</p>
          </>
        )}

        {status === 'error' && (
          <>
            <XCircle className="w-16 h-16 text-red-600 mx-auto mb-4" />
            <h2 className="text-2xl font-semibold text-gray-900 mb-2">Verification Failed</h2>
            <p className="text-gray-600 mb-4">We couldn't verify your payment. Please contact support.</p>
            <button
              onClick={() => navigate('/admin/subscriptions')}
              className="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700"
            >
              Return to Subscriptions
            </button>
          </>
        )}
      </div>
    </div>
  )
}