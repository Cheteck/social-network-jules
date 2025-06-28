'use client';

import React, { useEffect } from 'react';
import { useAuth } from '@/lib/contexts/AuthContext';
import { useRouter } from 'next/navigation';

interface ProtectedRouteWrapperProps {
  children: React.ReactNode;
  // Optionnel: Rôle ou permission requis si on veut une logique plus fine plus tard
  // requiredRole?: string;
}

export default function ProtectedRouteWrapper({ children }: ProtectedRouteWrapperProps) {
  const { user, isLoading } = useAuth();
  const router = useRouter();

  useEffect(() => {
    if (!isLoading && !user) {
      router.push('/auth_group/login');
    }
    // Pour une logique de rôle/permission plus tard :
    // if (!isLoading && user && requiredRole && user.role !== requiredRole) {
    //   router.push('/unauthorized'); // Ou une autre page
    // }
  }, [user, isLoading, router]); // Retiré requiredRole des dépendances pour l'instant

  if (isLoading) {
    return (
      <div className="flex items-center justify-center min-h-screen bg-x-bg text-x-primary-text">
        Chargement de la session...
      </div>
    );
  }

  if (!user) {
    // Devrait être déjà géré par le useEffect, mais comme fallback ou si la redirection prend un instant.
    // Ou on peut ne rien rendre ici et laisser le useEffect faire son travail.
    // Pour éviter un flash de contenu non protégé, un loader est mieux.
    return (
        <div className="flex items-center justify-center min-h-screen bg-x-bg text-x-primary-text">
          Redirection vers la connexion...
        </div>
      );
  }

  // Si l'utilisateur est authentifié (et potentiellement a le bon rôle)
  return <>{children}</>;
}
