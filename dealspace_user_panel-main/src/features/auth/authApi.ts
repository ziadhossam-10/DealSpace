// src/services/authApi.ts
import { createApi, fetchBaseQuery } from "@reduxjs/toolkit/query/react";
import { BASE_URL } from "../../utils/helpers";

// Define response interfaces based on your API structure
interface ApiResponse<T> {
  status: boolean;
  message: string;
  data: T;
  errors?: Record<string, string[]> | null;
}

interface UserData {
  id: number;
  name: string;
  email: string;
  avatar: string | null;
  role: number;
  created_at: string;
  updated_at: string;
}

interface AuthResponse extends ApiResponse<{
  user: UserData;
  token: string;
}> {}

interface ProfileResponse extends ApiResponse<UserData> {}

interface LogoutResponse extends ApiResponse<null> {}

// Request interfaces
interface RegisterRequest {
  name: string;
  email: string;
  password: string;
  password_confirmation: string;
  provider?: string;
  industry?: string;
  usage?: string;
  avatar?: File;
}

interface LoginRequest {
  email: string;
  password: string;
}

interface UpdateProfileRequest {
  name?: string;
  avatar?: File;
  provider?: string;
  industry?: string;
  usage?: string;
}

// Helper function to prepare formData
const prepareFormData = (data: any): FormData | any => {
  if (data.avatar instanceof File) {
    const formData = new FormData();
    Object.keys(data).forEach(key => {
      formData.append(key, data[key]);
    });
    return formData;
  }
  return data;
};

// Create the API service
export const authApi = createApi({
  reducerPath: "authApi",
  baseQuery: fetchBaseQuery({ 
    baseUrl: BASE_URL + "/auth",
    prepareHeaders: (headers, { getState }) => {
      // Get token from state
      const token = (getState() as any).auth.token;
      
      // If we have a token, add it to the headers
      if (token) {
        headers.set("Authorization", `Bearer ${token}`);
      }
      
      return headers;
    },
  }),

  endpoints: (builder) => ({
    // Register user
    register: builder.mutation<AuthResponse, RegisterRequest>({
      query: (userData) => {
        const data = prepareFormData(userData);
        return {
          url: "/register",
          method: "POST",
          body: data,
          formData: userData.avatar instanceof File,
        };
      },
    }),
    
    // Login user
    login: builder.mutation<AuthResponse, LoginRequest>({
      query: (credentials) => ({
        url: "/login",
        method: "POST",
        body: credentials,
      }),
    }),
    
    // Update profile
    updateProfile: builder.mutation<ProfileResponse, UpdateProfileRequest>({
      query: (profileData) => {
        const data = prepareFormData(profileData);
        return {
          url: "/update",
          method: "POST",
          body: data,
          formData: profileData.avatar instanceof File,
        };
      },
    }),
    
    // Logout user
    logout: builder.mutation<LogoutResponse, void>({
      query: () => ({
        url: "/logout",
        method: "POST",
      }),
    }),
    
    // Get current user
    getCurrentUser: builder.query<ProfileResponse, void>({
      query: () => "/me",
    }),
  }),
});

// Export hooks for usage in components
export const {
  useRegisterMutation,
  useLoginMutation,
  useUpdateProfileMutation,
  useLogoutMutation,
  useGetCurrentUserQuery,
} = authApi;
