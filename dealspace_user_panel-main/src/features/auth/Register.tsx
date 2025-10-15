"use client"

import type React from "react"

import { useEffect, useState } from "react"
import { useDispatch } from "react-redux"
import { useNavigate } from "react-router-dom";
import GridShape from "../../components/common/GridShape"
import { Link } from "react-router"
import { ChevronLeftIcon, EyeCloseIcon, EyeIcon } from "../../icons"
import PageMeta from "../../components/common/PageMeta"
import { useRegisterMutation } from "./authApi";
import { setCredentials } from "./authSlice"
import { BASE_URL } from "../../utils/helpers"
import { ApiResponse } from "../../types/meta"

// Update the component to include new fields
export default function Register() {
  const navigate = useNavigate();
  const dispatch = useDispatch();
  const [register, { isLoading }] = useRegisterMutation()

  const [showPassword, setShowPassword] = useState(false)
  const [showConfirmPassword, setShowConfirmPassword] = useState(false)
  const [firstName, setFirstName] = useState("")
  const [lastName, setLastName] = useState("")
  const [email, setEmail] = useState("")
  const [password, setPassword] = useState("")
  const [passwordConfirmation, setPasswordConfirmation] = useState("")
  const [industry, setIndustry] = useState("")
  const [usage, setUsage] = useState("")
  const [error, setError] = useState("")
  const [fieldErrors, setFieldErrors] = useState<Record<string, string[]>>({})

  const [usageCapacities, setUsageCapacities] = useState<string[]>([]);
  const [industries, setIndustries] = useState<string[]>([]);
  const [loading, setLoading] = useState(true);

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault()
    setError("")
    setFieldErrors({})

    // Basic validation
    if (password !== passwordConfirmation) {
      setFieldErrors({
        password_confirmation: ["Passwords do not match"],
      })
      return
    }

    const fullName = `${firstName} ${lastName}`.trim()
    try {
      const response = await register({
        name: fullName,
        email,
        password,
        password_confirmation: passwordConfirmation,
        industry,
        usage,
      }).unwrap()

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
        setError(response.message || "Registration failed")
      }
    } catch (err: any) {
      if (err.data?.errors) {
        setFieldErrors(err.data.errors)
      }
      setError(err.data?.message || "Failed to register. Please check your information.")
    }
  }

  const getFieldError = (field: string) => {
    return fieldErrors[field] ? fieldErrors[field][0] : null
  }

  useEffect(() => {
      const fetchEnums = async () => {
        try {
          const [usageRes, industriesRes] = await Promise.all([
            fetch(`${BASE_URL}/enums/usage-capacities`),
            fetch(`${BASE_URL}/enums/industries`)
          ]);

          const usageData: ApiResponse<string[]> = await usageRes.json();
          const industriesData: ApiResponse<string[]> = await industriesRes.json();

          if (usageData.status) setUsageCapacities(usageData.data);
          if (industriesData.status) setIndustries(industriesData.data);
        } catch (error) {
          console.error('Failed to fetch enums:', error);
        } finally {
          setLoading(false);
        }
      };

      fetchEnums();
    }, []);

  if (loading)
    return (
      <div className="flex items-center justify-center w-full h-screen">
        <div className="loader"></div>
      </div>
    );

  return (
    <>
      <PageMeta title="Sign Up | Dashboard" description="Create a new account to access your dashboard" />
      <div className="relative flex w-full h-screen px-4 py-6 overflow-hidden bg-white z-1 dark:bg-gray-900 sm:p-0">
        <div className="flex flex-col flex-1 p-6 rounded-2xl sm:rounded-none sm:border-0 sm:p-8 overflow-auto">
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
                  Create Account
                </h1>
                <p className="text-sm text-gray-500 dark:text-gray-400">Enter your details to create a new account!</p>
              </div>
              <div>
                {error && (
                  <div className="mb-4 p-3 text-sm text-red-500 bg-red-50 dark:bg-red-900/20 dark:text-red-400 rounded-lg">
                    {error}
                  </div>
                )}
                <form onSubmit={handleSubmit}>
                  <div className="space-y-6">
                    <div className="flex items-center gap-4 justify-center">
                      {/* First Name */}
                      <div className="flex-1">
                        <label className="block mb-2 text-sm font-medium text-gray-700 dark:text-gray-300">
                          First Name <span className="text-error-500">*</span>
                        </label>
                        <input
                          type="text"
                          placeholder="John"
                          value={firstName}
                          onChange={(e) => setFirstName(e.target.value)}
                          required
                          className={`w-full px-4 py-3 text-sm text-gray-700 bg-white border ${
                            getFieldError("first_name") ? "border-error-500" : "border-gray-200"
                          } rounded-lg focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-transparent dark:bg-gray-800 dark:border-gray-700 dark:text-white`}
                        />
                        {getFieldError("first_name") && (
                          <p className="mt-1 text-sm text-error-500">{getFieldError("first_name")}</p>
                        )}
                      </div>

                      {/* Last Name */}
                      <div className="flex-1">
                        <label className="block mb-2 text-sm font-medium text-gray-700 dark:text-gray-300">
                          Last Name <span className="text-error-500">*</span>
                        </label>
                        <input
                          type="text"
                          placeholder="Doe"
                          value={lastName}
                          onChange={(e) => setLastName(e.target.value)}
                          required
                          className={`w-full px-4 py-3 text-sm text-gray-700 bg-white border ${
                            getFieldError("last_name") ? "border-error-500" : "border-gray-200"
                          } rounded-lg focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-transparent dark:bg-gray-800 dark:border-gray-700 dark:text-white`}
                        />
                        {getFieldError("last_name") && (
                          <p className="mt-1 text-sm text-error-500">{getFieldError("last_name")}</p>
                        )}
                      </div>
                    </div>
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
                        className={`w-full px-4 py-3 text-sm text-gray-700 bg-white border ${
                          getFieldError("email") ? "border-error-500" : "border-gray-200"
                        } rounded-lg focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-transparent dark:bg-gray-800 dark:border-gray-700 dark:text-white`}
                      />
                      {getFieldError("email") && (
                        <p className="mt-1 text-sm text-error-500">{getFieldError("email")}</p>
                      )}
                    </div>

                    {/* Industry dropdown */}
                    <div>
                      <label className="block mb-2 text-sm font-medium text-gray-700 dark:text-gray-300">
                        Industry
                      </label>
                      <select
                        value={industry}
                        onChange={(e) => setIndustry(e.target.value)}
                        className={`w-full px-4 py-3 text-sm text-gray-700 bg-white border ${
                          getFieldError("industry") ? "border-error-500" : "border-gray-200"
                        } rounded-lg focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-transparent dark:bg-gray-800 dark:border-gray-700 dark:text-white`}
                      >
                        <option value="">Select your industry</option>
                        {industries.map((option, index) => (
                          <option key={option} value={index}>
                            {option}
                          </option>
                        ))}
                      </select>
                      {getFieldError("industry") && (
                        <p className="mt-1 text-sm text-error-500">{getFieldError("industry")}</p>
                      )}
                    </div>

                    {/* Usage dropdown */}
                    <div>
                      <label className="block mb-2 text-sm font-medium text-gray-700 dark:text-gray-300">
                        Team Size
                      </label>
                      <select
                        value={usage}
                        onChange={(e) => setUsage(e.target.value)}
                        className={`w-full px-4 py-3 text-sm text-gray-700 bg-white border ${
                          getFieldError("usage") ? "border-error-500" : "border-gray-200"
                        } rounded-lg focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-transparent dark:bg-gray-800 dark:border-gray-700 dark:text-white`}
                      >
                        <option value="">Select your team size</option>
                        {usageCapacities.map((option, index) => (
                          <option key={option} value={index}>
                            {option}
                          </option>
                        ))}
                      </select>
                      {getFieldError("usage") && (
                        <p className="mt-1 text-sm text-error-500">{getFieldError("usage")}</p>
                      )}
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
                          className={`w-full px-4 py-3 text-sm text-gray-700 bg-white border ${
                            getFieldError("password") ? "border-error-500" : "border-gray-200"
                          } rounded-lg focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-transparent dark:bg-gray-800 dark:border-gray-700 dark:text-white`}
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
                      {getFieldError("password") && (
                        <p className="mt-1 text-sm text-error-500">{getFieldError("password")}</p>
                      )}
                    </div>
                    <div>
                      <label className="block mb-2 text-sm font-medium text-gray-700 dark:text-gray-300">
                        Confirm Password <span className="text-error-500">*</span>{" "}
                      </label>
                      <div className="relative">
                        <input
                          type={showConfirmPassword ? "text" : "password"}
                          placeholder="Confirm your password"
                          value={passwordConfirmation}
                          onChange={(e) => setPasswordConfirmation(e.target.value)}
                          required
                          className={`w-full px-4 py-3 text-sm text-gray-700 bg-white border ${
                            getFieldError("password_confirmation") ? "border-error-500" : "border-gray-200"
                          } rounded-lg focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-transparent dark:bg-gray-800 dark:border-gray-700 dark:text-white`}
                        />
                        <span
                          onClick={() => setShowConfirmPassword(!showConfirmPassword)}
                          className="absolute z-30 -translate-y-1/2 cursor-pointer right-4 top-1/2"
                        >
                          {showConfirmPassword ? (
                            <EyeIcon className="fill-gray-500 dark:fill-gray-400" />
                          ) : (
                            <EyeCloseIcon className="fill-gray-500 dark:fill-gray-400" />
                          )}
                        </span>
                      </div>
                      {getFieldError("password_confirmation") && (
                        <p className="mt-1 text-sm text-error-500">{getFieldError("password_confirmation")}</p>
                      )}
                    </div>
                    <div className="flex items-center justify-between">
                      <button
                        className="w-full px-4 py-3 text-sm font-medium text-white transition-colors bg-[#000] rounded-lg hover:bg-[#222] focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed"
                        type="submit"
                        disabled={isLoading}
                      >
                        {isLoading ? "Creating account..." : "Create account"}
                      </button>
                    </div>
                    <div className="text-center">
                      <p className="text-sm text-gray-500 dark:text-gray-400">
                        Already have an account?{" "}
                        <Link to="/login" className="text-brand-500 hover:text-brand-600">
                          Sign in
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
