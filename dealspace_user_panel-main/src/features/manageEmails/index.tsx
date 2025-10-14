"use client"

import { useState, useCallback } from "react"
import { Mail, CheckCircle, XCircle, Plus, Unplug, Link } from "lucide-react"
import {
  useGetEmailAccountsQuery,
  useDisconnectEmailAccountMutation,
  useConnectEmailAccountMutation,
} from "./emailAccountsApi"
import ConnectAnotherEmailModal from "./ConnectAnotherEmailModal"
import AdminLayout from "../../layout/AdminLayout"
import type { EmailAccount } from "../../types/emailAccounts" 
import { toast } from "react-toastify"

export default function ManageEmails() {
  const [showConnectModal, setShowConnectModal] = useState(false)
  const [selectedAccountId, setSelectedAccountId] = useState<number | null>(null)
  const [showDisconnectModal, setShowDisconnectModal] = useState(false)

  const { data, isLoading, error, refetch } = useGetEmailAccountsQuery({ page: 1, per_page: 50 })
  const [disconnectAccount, { isLoading: isDisconnecting }] = useDisconnectEmailAccountMutation()
  const [connectAccount, { isLoading: isConnecting }] = useConnectEmailAccountMutation()

  const handleDisconnectAccount = useCallback((accountId: number) => {
    setSelectedAccountId(accountId)
    setShowDisconnectModal(true)
  }, [])

  const handleConnectAccount = async (accountId: number) => {
    try {
      const response = await connectAccount(accountId).unwrap()
      if (response.status) {
        refetch()
        // Show success message if needed
        toast.success(response.message || "Account connected successfully")
        console.log(response.message || "Account connected successfully")
      }
    } catch (err: any) {
      console.error("Failed to connect account:", err)
      // Show error message if needed
      console.error(err?.data?.message || "Failed to connect account")
    }
  }

  const handleConfirmDisconnect = async () => {
    if (!selectedAccountId) return

    try {
      const response = await disconnectAccount(selectedAccountId).unwrap()
      if (response.status) {
        // Close modal first
        setShowDisconnectModal(false)
        setSelectedAccountId(null)
        // Then refetch data
        refetch()
        // Show success message if needed
        toast.success(response.message || "Account disconnected successfully")
        console.log(response.message || "Account disconnected successfully")
      }
    } catch (err: any) {
      console.error("Failed to disconnect account:", err)
      // Show error message if needed
      console.error(err?.data?.message || "Failed to disconnect account")
      // Still close modal on error
      setShowDisconnectModal(false)
      setSelectedAccountId(null)
    }
  }

  const handleCancelDisconnect = useCallback(() => {
    setShowDisconnectModal(false)
    setSelectedAccountId(null)
  }, [])

  const handleAccountConnected = () => {
    refetch()
  }

  const getProviderIcon = (provider: string) => {
    if (provider === "gmail") {
      return (
        <div className="w-10 h-10 bg-red-500 rounded-full flex items-center justify-center">
          <span className="text-white text-sm font-bold">G</span>
        </div>
      )
    } else {
      return (
        <div className="w-10 h-10 bg-blue-500 rounded-full flex items-center justify-center">
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

  const accounts = data?.data?.items || []

  return (
    <AdminLayout>
      <div className="space-y-6">
        {/* Header */}
        <div className="flex items-center justify-between">
          <div>
            <h1 className="text-2xl font-bold text-gray-900 flex items-center">
              <Mail className="mr-3 h-8 w-8" />
              Manage Your Emails
            </h1>
            <p className="text-gray-600 mt-1">Connect and manage email accounts for your organization</p>
          </div>
          <button
            onClick={() => setShowConnectModal(true)}
            className="bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded-lg transition-colors flex items-center"
          >
            <Plus className="mr-2 h-4 w-4" />
            Connect Email Account
          </button>
        </div>

        {/* Stats Cards */}
        <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
          <div className="bg-white rounded-lg shadow border border-gray-200 p-6">
            <div className="flex items-center">
              <div className="p-2 bg-blue-100 rounded-lg">
                <Mail className="h-6 w-6 text-blue-600" />
              </div>
              <div className="ml-4">
                <p className="text-sm font-medium text-gray-600">Total Accounts</p>
                <p className="text-2xl font-bold text-gray-900">{accounts.length}</p>
              </div>
            </div>
          </div>

          <div className="bg-white rounded-lg shadow border border-gray-200 p-6">
            <div className="flex items-center">
              <div className="p-2 bg-green-100 rounded-lg">
                <CheckCircle className="h-6 w-6 text-green-600" />
              </div>
              <div className="ml-4">
                <p className="text-sm font-medium text-gray-600">Active Accounts</p>
                <p className="text-2xl font-bold text-gray-900">
                  {accounts.filter((account) => account.is_active).length}
                </p>
              </div>
            </div>
          </div>

          <div className="bg-white rounded-lg shadow border border-gray-200 p-6">
            <div className="flex items-center">
              <div className="p-2 bg-red-100 rounded-lg">
                <XCircle className="h-6 w-6 text-red-600" />
              </div>
              <div className="ml-4">
                <p className="text-sm font-medium text-gray-600">Inactive Accounts</p>
                <p className="text-2xl font-bold text-gray-900">
                  {accounts.filter((account) => !account.is_active).length}
                </p>
              </div>
            </div>
          </div>
        </div>

        {/* Email Accounts List */}
        <div className="bg-white rounded-lg shadow border border-gray-200">
          <div className="p-6 border-b border-gray-200">
            <h3 className="text-lg font-semibold text-gray-900">Connected Email Accounts</h3>
          </div>

          {isLoading ? (
            <div className="p-6">
              <div className="animate-pulse space-y-4">
                {[1, 2, 3].map((i) => (
                  <div key={i} className="flex items-center space-x-4">
                    <div className="w-10 h-10 bg-gray-200 rounded-full"></div>
                    <div className="flex-1">
                      <div className="h-4 bg-gray-200 rounded w-3/4 mb-2"></div>
                      <div className="h-3 bg-gray-200 rounded w-1/2"></div>
                    </div>
                    <div className="w-20 h-8 bg-gray-200 rounded"></div>
                  </div>
                ))}
              </div>
            </div>
          ) : error ? (
            <div className="p-6">
              <div className="text-center text-red-500">
                <p>Failed to load email accounts. Please try again.</p>
              </div>
            </div>
          ) : accounts.length === 0 ? (
            <div className="p-12 text-center">
              <Mail size={48} className="mx-auto mb-4 text-gray-400" />
              <h3 className="text-lg font-medium text-gray-900 mb-2">No email accounts connected</h3>
              <p className="text-gray-500 mb-6">Connect your first email account to get started</p>
              <button
                onClick={() => setShowConnectModal(true)}
                className="bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded-lg transition-colors flex items-center mx-auto"
              >
                <Plus className="mr-2 h-4 w-4" />
                Connect Email Account
              </button>
            </div>
          ) : (
            <div className="divide-y divide-gray-200">
              {accounts.map((account: EmailAccount) => (
                <div key={account.id} className="p-6 hover:bg-gray-50 transition-colors">
                  <div className="flex items-center justify-between">
                    <div className="flex items-center space-x-4">
                      {getProviderIcon(account.provider)}
                      <div className="flex-1">
                        <div className="flex items-center space-x-3">
                          <h4 className="text-lg font-medium text-gray-900">{account.email}</h4>
                          {account.is_active ? (
                            <span className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                              <CheckCircle className="w-3 h-3 mr-1" />
                              Active
                            </span>
                          ) : (
                            <span className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                              <XCircle className="w-3 h-3 mr-1" />
                              Inactive
                            </span>
                          )}
                        </div>
                        <div className="mt-1 flex items-center space-x-4 text-sm text-gray-500">
                          <span className="capitalize">{account.provider}</span>
                          {account.created_at && <span>Connected: {formatDate(account.created_at)}</span>}
                        </div>
                      </div>
                    </div>

                    <div className="flex items-center space-x-2">

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
                </div>
              ))}
            </div>
          )}
        </div>

        {/* Connect Another Email Modal */}
        <ConnectAnotherEmailModal
          isOpen={showConnectModal}
          onClose={() => setShowConnectModal(false)}
          onAccountConnected={handleAccountConnected}
        />

        {/* Disconnect Confirmation Modal */}
        {showDisconnectModal && (
          <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
            <div className="bg-white rounded-lg shadow-lg max-w-md w-full mx-4">
              <div className="p-6">
                <div className="flex items-center mb-4">
                  <div className="p-2 bg-orange-100 rounded-lg mr-3">
                    <Unplug className="h-6 w-6 text-orange-600" />
                  </div>
                  <h3 className="text-lg font-medium text-gray-900">Disconnect Email Account</h3>
                </div>
                <p className="text-gray-600 mb-6">
                  Are you sure you want to disconnect this email account? This will stop all email synchronization and
                  you won't be able to send emails from this account.
                </p>
                <div className="flex justify-end space-x-3">
                  <button
                    onClick={handleCancelDisconnect}
                    disabled={isDisconnecting}
                    className="px-4 py-2 text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg transition-colors disabled:opacity-50"
                  >
                    Cancel
                  </button>
                  <button
                    onClick={handleConfirmDisconnect}
                    disabled={isDisconnecting}
                    className="px-4 py-2 bg-orange-600 hover:bg-orange-700 text-white rounded-lg transition-colors disabled:opacity-50 flex items-center"
                  >
                    {isDisconnecting ? (
                      <>
                        <div className="animate-spin rounded-full h-4 w-4 border-b-2 border-white mr-2"></div>
                        Disconnecting...
                      </>
                    ) : (
                      <>
                        <Unplug className="h-4 w-4 mr-2" />
                        Disconnect
                      </>
                    )}
                  </button>
                </div>
              </div>
            </div>
          </div>
        )}
      </div>
    </AdminLayout>
  )
}
