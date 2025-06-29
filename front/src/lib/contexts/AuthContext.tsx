"use client";

import React, { createContext, useContext, useEffect, useState } from 'react';

interface User {
  id: number;
  name: string;
  email: string;
  username?: string;
  avatar_url?: string;
}

interface AuthContextProps {
  user: User | null;
  loading: boolean;
  isAuthenticated: boolean;
  refresh: () => void;
}

const AuthContext = createContext<AuthContextProps>({
  user: null,
  loading: true,
  isAuthenticated: false,
  refresh: () => {},
});

export function AuthProvider({ children }: { children: React.ReactNode }) {
  const [user, setUser] = useState<User | null>(null);
  const [loading, setLoading] = useState(true);
  const [refreshIndex, setRefreshIndex] = useState(0);

  useEffect(() => {
    setLoading(true);
    fetch('/api/user', {
      headers: { 'Accept': 'application/json' },
      credentials: 'include',
    })
      .then(async res => {
        if (!res.ok) throw new Error('Not authenticated');
        return res.json();
      })
      .then(setUser)
      .catch(() => setUser(null))
      .finally(() => setLoading(false));
  }, [refreshIndex]);

  return (
    <AuthContext.Provider value={{ user, loading, isAuthenticated: !!user, refresh: () => setRefreshIndex(i => i + 1) }}>
      {children}
    </AuthContext.Provider>
  );
}

export function useAuth() {
  return useContext(AuthContext);
}
