import { useSelector } from "react-redux";
import { RootState } from "../app/store";
import { useGetCurrentUserQuery } from "../features/auth/authApi";
import { useEffect } from "react";
import { useDispatch } from "react-redux";
import { updateUser } from "../features/auth/authSlice";

export const useAuth = () => {
  const dispatch = useDispatch();
  const { user, token, isAuthenticated } = useSelector(
    (state: RootState) => state.auth
  );

  // Optionally fetch latest user data if logged in
  const { data: currentUserData, isFetching } = useGetCurrentUserQuery(undefined, {
    skip: !isAuthenticated, // only fetch if logged in
  });

  useEffect(() => {
    if (currentUserData?.data && !isFetching) {
      dispatch(updateUser(currentUserData.data));
    }
  }, [currentUserData, isFetching, dispatch]);

  const role = user?.role || null; // default to null

  return {
    user,
    role,
    token,
    isAuthenticated,
    isFetchingUser: isFetching,
  };
};
