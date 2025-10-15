import { useSelector } from "react-redux";
import { RootState } from "../app/store";
import { useGetCurrentUserQuery } from "../features/auth/authApi";
import { useEffect, useRef } from "react";
import { useDispatch } from "react-redux";
import { updateUser } from "../features/auth/authSlice";

const USER_REFRESH_INTERVAL = 5 * 60 * 1000; // 5 minutes

export const useAuth = () => {
  const dispatch = useDispatch();
  const lastFetchTime = useRef<number>(0);
  
  const { user, token, isAuthenticated } = useSelector(
    (state: RootState) => state.auth
  );

  // Only fetch user data if:
  // 1. User is authenticated
  // 2. User data doesn't exist yet, OR
  // 3. Last fetch was more than 5 minutes ago
  const shouldFetch = 
    isAuthenticated && 
    (!user || Date.now() - lastFetchTime.current > USER_REFRESH_INTERVAL);

  const { data: currentUserData, isFetching } = useGetCurrentUserQuery(undefined, {
    skip: !shouldFetch,
    // Optional: use polling for background refresh
    // pollingInterval: USER_REFRESH_INTERVAL,
  });

  useEffect(() => {
    if (currentUserData?.data && !isFetching) {
      dispatch(updateUser(currentUserData.data));
      lastFetchTime.current = Date.now();
    }
  }, [currentUserData, isFetching, dispatch]);

  const role = user?.role;

  return {
    user,
    role,
    token,
    isAuthenticated,
    isFetchingUser: isFetching,
  };
};
