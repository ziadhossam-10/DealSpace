import { fetchBaseQuery } from '@reduxjs/toolkit/query/react';
import { BASE_URL } from '../utils/helpers';
import { RootState } from '../app/store';
import { logout } from '../features/auth/authSlice'; // Import your logout action
import { toast } from 'react-toastify';

// Create a custom fetch base query with consistent headers
const baseQueryWithAuth = fetchBaseQuery({
  baseUrl: BASE_URL,
  prepareHeaders: (headers, { getState }) => {
    // Add auth token if available
    const token = (getState() as RootState).auth.token;
    if (token) {
      headers.set('authorization', `Bearer ${token}`);
    }
    headers.set('accept', '*/*');
    return headers;
  },
});

// Wrapper for handling 401 errors
export const customBaseQuery = async (args: any, api: any, extraOptions: any) => {
  const result = await baseQueryWithAuth(args, api, extraOptions);
  
  // If we get a 401 Unauthorized response, dispatch logout action
  if (result.error && result.error.status === 401) {
    // Dispatch logout action
    api.dispatch(logout());
  }

    // Handle validation errors (422)
    if (result.error && result.error.status === 422) {
      const errorData = result.error.data as any;
      const firstErrorKey = Object.keys(errorData.errors || {})[0];
      const firstErrorMessage = errorData.errors?.[firstErrorKey]?.[0] || 'Validation failed';

      toast.error(firstErrorMessage)
  
    }
  
    // Handle internal server error (500)
    if (result.error && result.error.status === 500) {
      return {
        error: {
          status: 500,
          message: 'Server error. Please try again later.',
        },
      };
    }
    
  return result;
};