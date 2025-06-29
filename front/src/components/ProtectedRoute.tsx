"use client";

import React, { ReactNode, useEffect } from 'react';
import { useAuth } from '@/lib/contexts/AuthContext';
import { useRouter } from 'next/navigation';

export default function ProtectedRoute({ children }: { children: ReactNode }) {
  const { isAuthenticated, loading } = useAuth();
  const router = useRouter();

  useEffect(() => {
    if (!loading && !isAuthenticated) {
      router.replace('/auth/login');
    }
  }, [loading, isAuthenticated, router]);

  if (loading) return <div className="flex justify-center items-center h-screen text-gray-400">Chargement…</div>;
  if (!isAuthenticated) return null;
  return <>{children}</>;
}
