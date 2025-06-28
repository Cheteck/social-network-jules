'use client';

import { useEffect } from 'react';
import { useRouter } from 'next/navigation';
import { useAuth } from '@/lib/contexts/AuthContext';

export default function HomePage() {
  const { user, isLoading } = useAuth();
  const router = useRouter();

  useEffect(() => {
    if (!isLoading) {
      if (user) {
        router.replace('/main_group/home');
      } else {
        router.replace('/auth_group/login');
      }
    }
  }, [user, isLoading, router]);

  // Afficher un loader ou un contenu minimal pendant que la redirection s'effectue
  // pour éviter un flash de contenu si la page racine avait un contenu visible.
  return (
    <div className="flex min-h-screen flex-col items-center justify-center bg-x-bg text-x-primary-text">
      <p>Chargement...</p>
      {/* Ou un spinner/logo plus élaboré */}
    </div>
  );
}
