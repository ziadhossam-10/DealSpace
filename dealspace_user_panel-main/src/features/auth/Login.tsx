"use client"

import type React from "react"

import { useState } from "react"
import { useDispatch } from "react-redux"
import GridShape from "../../components/common/GridShape"
import { Link } from "react-router"
import { ChevronLeftIcon, EyeCloseIcon, EyeIcon } from "../../icons"
import PageMeta from "../../components/common/PageMeta"
import { useLoginMutation } from "./authApi"
import { setCredentials } from "./authSlice"

export default function Login() {
  const dispatch = useDispatch()
  const [login, { isLoading }] = useLoginMutation()

  const [showPassword, setShowPassword] = useState(false)
  const [email, setEmail] = useState("")
  const [password, setPassword] = useState("")
  const [error, setError] = useState("")

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault()
    setError("")

    try {
      const response = await login({ email, password }).unwrap()

      if (response.status) {
        // Store credentials in Redux state
        dispatch(
          setCredentials({
            token: response.data.token,
            user: response.data?.user,
          }),
        )
        // Redirect to dashboard or home page
        window.location.href = "/"
      } else {
        setError(response.message || "Login failed")
      }
    } catch (err: any) {
      const message =
        err?.data?.message ??
        err?.error ??
        err?.message ??
        "Failed to login. Please check your credentials."

      setError(message)
    }
  }

  return (
    <>
      <PageMeta title="Sign In | Dashboard" description="Sign in to access your dashboard" />
      <div className="relative flex w-full h-screen px-4 py-6 overflow-hidden bg-white z-1 dark:bg-gray-900 sm:p-0">
        <div className="flex flex-col flex-1 p-6 rounded-2xl sm:rounded-none sm:border-0 sm:p-8">
          <div className="w-full max-w-md pt-10 mx-auto">
            <Link
              to="/"
              className="inline-flex items-center text-sm text-gray-500 transition-colors hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300"
            >
              <ChevronLeftIcon />
              Back to dashboard
            </Link>
          </div>
          <div className="flex flex-col justify-center flex-1 w-full max-w-md mx-auto">
            <div>
              <div className="mb-5 sm:mb-8">
                <h1 className="mb-2 font-semibold text-gray-800 text-title-sm dark:text-white/90 sm:text-title-md">
                  Sign In
                </h1>
                <p className="text-sm text-gray-500 dark:text-gray-400">Enter your email and password to sign in!</p>
              </div>
              <div>
                {error && (
                  <div className="mb-4 p-3 text-sm text-red-500 bg-red-50 dark:bg-red-900/20 dark:text-red-400 rounded-lg">
                    {error}
                  </div>
                )}
                <form onSubmit={handleSubmit}>
                  <div className="space-y-6">
                    <div>
                      <label className="block mb-2 text-sm font-medium text-gray-700 dark:text-gray-300">
                        Email <span className="text-error-500">*</span>{" "}
                      </label>
                      <input
                        type="email"
                        placeholder="info@gmail.com"
                        value={email}
                        onChange={(e) => setEmail(e.target.value)}
                        required
                        className="w-full px-4 py-3 text-sm text-gray-700 bg-white border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-transparent dark:bg-gray-800 dark:border-gray-700 dark:text-white"
                      />
                    </div>
                    <div>
                      <label className="block mb-2 text-sm font-medium text-gray-700 dark:text-gray-300">
                        Password <span className="text-error-500">*</span>{" "}
                      </label>
                      <div className="relative">
                        <input
                          type={showPassword ? "text" : "password"}
                          placeholder="Enter your password"
                          value={password}
                          onChange={(e) => setPassword(e.target.value)}
                          required
                          className="w-full px-4 py-3 text-sm text-gray-700 bg-white border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-transparent dark:bg-gray-800 dark:border-gray-700 dark:text-white"
                        />
                        <span
                          onClick={() => setShowPassword(!showPassword)}
                          className="absolute z-30 -translate-y-1/2 cursor-pointer right-4 top-1/2"
                        >
                          {showPassword ? (
                            <EyeIcon className="fill-gray-500 dark:fill-gray-400" />
                          ) : (
                            <EyeCloseIcon className="fill-gray-500 dark:fill-gray-400" />
                          )}
                        </span>
                      </div>
                    </div>
                    <div className="flex items-center justify-between">
                      <button
                        className="w-full px-4 py-3 text-sm font-medium text-white transition-colors bg-[#000] rounded-lg hover:bg-[#222] focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed"
                        type="submit"
                        disabled={isLoading}
                      >
                        {isLoading ? "Signing in..." : "Sign in"}
                      </button>
                    </div>
                    <div className="text-center">
                      <p className="text-sm text-gray-500 dark:text-gray-400">
                        Do not have account?{" "}
                        <Link to="/register" className="text-brand-500 hover:text-brand-600">
                          Sign Up
                        </Link>
                      </p>
                    </div>
                  </div>
                </form>

             </div>
            </div>
          </div>
        </div>
        <div className="relative items-center justify-center flex-1 hidden p-8 z-1 bg-[#000] dark:bg-white/5 lg:flex">
          {/* <!-- ===== Common Grid Shape Start ===== --> */}
          <GridShape />
          <div className="flex flex-col items-center max-w-xs">
            <Link to="/" className="block mb-4">
              <img src="./images/logo/auth-logo.png" alt="Logo" />
            </Link>
            <p className="text-center text-gray-400 dark:text-white/60">
              CRM tool that turns your past clients <br /> into lead magnets
            </p>
          </div>
        </div>
      </div>
    </>
  )
}

