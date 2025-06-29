"use client";

import React, { createContext, useContext, useEffect, useState, useCallback } from 'react';
import { getUser, logout as apiLogout } from '@/lib/api/auth'; // Import new functions

interface User {
  id: number;
  name: string;
  email: string;
  username?: string;
  avatar_url?: string;
  // Add any other fields your User object might have from the backend
}

interface AuthContextProps {
  user: User | null;
  loading: boolean;
  isAuthenticated: boolean;
  refreshUser: () => void; // Renamed for clarity
  logoutUser: () => Promise<void>; // Added logout function
}

const AuthContext = createContext<AuthContextProps>({
  user: null,
  loading: true,
  isAuthenticated: false,
  refreshUser: () => {},
  logoutUser: async () => {},
});

export function AuthProvider({ children }: { children: React.ReactNode }) {
  const [user, setUser] = useState<User | null>(null);
  const [loading, setLoading] = useState(true);
  const [refreshTrigger, setRefreshTrigger] = useState(0); // Used to trigger refresh

  const fetchUser = useCallback(async () => {
    setLoading(true);
    try {
      const userData = await getUser();
      setUser(userData);
    } catch (error) {
      // console.error('Failed to fetch user:', error); // apiClient already logs
      setUser(null); // Ensure user is null if fetch fails (e.g., 401)
    } finally {
      setLoading(false);
    }
  }, []);

  useEffect(() => {
    fetchUser();
  }, [fetchUser, refreshTrigger]);

  const refreshUser = useCallback(() => {
    setRefreshTrigger(prev => prev + 1);
  }, []);

  const logoutUser = useCallback(async () => {
    try {
      await apiLogout();
      setUser(null);
      // Optionally, redirect to login page or home page
      // For example: router.push('/auth/login'); (if router is available here)
      // Or handle redirection in the component calling logoutUser
    } catch (error) {
      console.error('Logout failed:', error);
      // Even if logout API call fails, clear user locally as a fallback
      setUser(null);
    }
  }, []);

  return (
    <AuthContext.Provider value={{ user, loading, isAuthenticated: !!user, refreshUser, logoutUser }}>
      {children}
    </AuthContext.Provider>
  );
}

export function useAuth() {
  return useContext(AuthContext);
}
