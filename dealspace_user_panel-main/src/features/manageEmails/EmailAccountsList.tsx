"use client"
import { Mail, Unplug, Link, CheckCircle, XCircle } from "lucide-react"
import {
  useGetEmailAccountsQuery,
  useDisconnectEmailAccountMutation,
  useConnectEmailAccountMutation,
} from "./emailAccountsApi"
import type { EmailAccount } from "../../types/emailAccounts"

interface EmailAccountsListProps {
  onAccountDeleted?: () => void
}

export default function EmailAccountsList({ onAccountDeleted }: EmailAccountsListProps) {
  const { data, isLoading, error, refetch } = useGetEmailAccountsQuery({ page: 1, per_page: 15 })
  const [disconnectAccount] = useDisconnectEmailAccountMutation()
  const [connectAccount, { isLoading: isConnecting }] = useConnectEmailAccountMutation()

  const handleDisconnectAccount = async (accountId: number) => {
    if (!window.confirm("Are you sure you want to disconnect this account?")) return

    try {
      await disconnectAccount(accountId).unwrap()
      refetch()
      onAccountDeleted?.()
    } catch (err) {
      console.error("Failed to disconnect account:", err)
    }
  }

  const handleConnectAccount = async (accountId: number) => {
    try {
      await connectAccount(accountId).unwrap()
      refetch()
      onAccountDeleted?.()
    } catch (err) {
      console.error("Failed to connect account:", err)
    }
  }

  const getProviderIcon = (provider: string) => {
    if (provider === "google") {
      return (
        <div className="w-8 h-8 bg-red-500 rounded-full flex items-center justify-center">
          <span className="text-white text-sm font-bold">G</span>
        </div>
      )
    } else {
      return (
        <div className="w-8 h-8 bg-blue-500 rounded-full flex items-center justify-center">
          <span className="text-white text-sm font-bold">O</span>
        </div>
      )
    }
  }

  const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleDateString("en-US", {
      year: "numeric",
      month: "short",
      day: "numeric",
      hour: "2-digit",
      minute: "2-digit",
    })
  }

  if (isLoading) {
    return (
      <div className="bg-white rounded-lg shadow-lg border border-gray-200">
        <div className="p-6 border-b border-gray-200">
          <h3 className="text-lg font-semibold text-gray-900 flex items-center">
            <Mail className="mr-2 h-5 w-5" />
            Connected Accounts
          </h3>
        </div>
        <div className="p-6">
          <div className="animate-pulse space-y-4">
            {[1, 2, 3].map((i) => (
              <div key={i} className="flex items-center space-x-3">
                <div className="w-8 h-8 bg-gray-200 rounded-full"></div>
                <div className="flex-1">
                  <div className="h-4 bg-gray-200 rounded w-3/4"></div>
                  <div className="h-3 bg-gray-200 rounded w-1/2 mt-1"></div>
                </div>
              </div>
            ))}
          </div>
        </div>
      </div>
    )
  }

  if (error) {
    return (
      <div className="bg-white rounded-lg shadow-lg border border-gray-200">
        <div className="p-6">
          <div className="text-center text-red-500">Failed to load email accounts</div>
        </div>
      </div>
    )
  }

  const accounts = data?.data?.items || []

  return (
    <div className="bg-white rounded-lg shadow-lg border border-gray-200">
      <div className="p-6 border-b border-gray-200">
        <h3 className="text-lg font-semibold text-gray-900 flex items-center">
          <Mail className="mr-2 h-5 w-5" />
          Connected Accounts ({accounts.length})
        </h3>
      </div>
      <div className="p-6">
        {accounts.length === 0 ? (
          <div className="text-center py-8 text-gray-500">
            <Mail size={48} className="mx-auto mb-4 opacity-50" />
            <p>No email accounts connected yet.</p>
          </div>
        ) : (
          <div className="space-y-4">
            {accounts.map((account: EmailAccount) => (
              <div key={account.id} className="border border-gray-200 rounded-lg p-4 hover:shadow-md transition-shadow">
                <div className="flex items-center justify-between">
                  <div className="flex items-center space-x-3">
                    {getProviderIcon(account.provider)}
                    <div>
                      <h4 className="font-medium text-gray-800">{account.email}</h4>
                      <p className="text-sm text-gray-500 capitalize">{account.provider}</p>
                    </div>
                  </div>

                  <div className="flex items-center space-x-3">
                    <div className="text-right">
                      <div className="flex items-center">
                        {account.is_active ? (
                          <>
                            <CheckCircle className="w-4 h-4 text-green-500 mr-2" />
                            <span className="text-sm text-green-600">Active</span>
                          </>
                        ) : (
                          <>
                            <XCircle className="w-4 h-4 text-red-500 mr-2" />
                            <span className="text-sm text-red-600">Inactive</span>
                          </>
                        )}
                      </div>
                      {account.created_at && (
                        <p className="text-xs text-gray-400">Connected: {formatDate(account.created_at)}</p>
                      )}
                    </div>

                    {account.is_active ? (
                      <button
                        onClick={() => handleDisconnectAccount(account.id)}
                        className="p-2 text-orange-500 hover:text-orange-700 hover:bg-orange-50 rounded-lg transition-colors"
                        title="Disconnect account"
                      >
                        <Unplug className="h-4 w-4" />
                      </button>
                    ) : (
                      <button
                        onClick={() => handleConnectAccount(account.id)}
                        disabled={isConnecting}
                        className="p-2 text-green-500 hover:text-green-700 hover:bg-green-50 rounded-lg transition-colors disabled:opacity-50"
                        title="Connect account"
                      >
                        <Link className="h-4 w-4" />
                      </button>
                    )}
                  </div>
                </div>

                {/* Token Status */}
                <div className="mt-3 pt-3 border-t border-gray-100">
                  <div className="flex justify-between text-xs text-gray-500">
                    <span>Token expires:</span>
                    <span>{formatDate(account.token_expires_at)}</span>
                  </div>
                </div>
              </div>
            ))}
          </div>
        )}
      </div>
    </div>
  )
}
