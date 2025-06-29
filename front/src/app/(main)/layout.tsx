import React from 'react';
import Sidebar from '@/components/Sidebar';
import { AuthProvider } from '@/lib/contexts/AuthContext';
import ProtectedRoute from '@/components/ProtectedRoute';

// Layout principal pour les pages du réseau social (feed, profil, notifications, etc.)
export default function MainLayout({ children }: { children: React.ReactNode }) {
  return (
    <AuthProvider>
      <ProtectedRoute>
        <div className="min-h-screen flex bg-gray-50 dark:bg-gray-900">
          {/* Sidebar gauche */}
          <aside className="hidden md:flex flex-col w-64 p-4 border-r border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900">
            <Sidebar />
          </aside>
          {/* Colonne centrale (timeline) */}
          <main className="flex-1 flex flex-col items-center px-2 md:px-0 max-w-2xl mx-auto">
            {children}
          </main>
          {/* Colonne droite (suggestions, tendances) */}
          <aside className="hidden lg:flex flex-col w-80 p-4 border-l border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900">
            {/* Suggestions/tendances à venir */}
          </aside>
        </div>
      </ProtectedRoute>
    </AuthProvider>
  );
}
