'use client';

import React, { useState, useEffect } from 'react';
import { useAuth } from '@/lib/contexts/AuthContext';
import { useRouter } from 'next/navigation';

export default function LoginForm() {
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  // Utiliser l'état d'erreur et de chargement du contexte
  const { login, isLoading, error: authError, clearError, user } = useAuth();
  const router = useRouter();

  // Effacer les erreurs d'authentification précédentes lorsque le composant est monté ou que l'email/password change
  useEffect(() => {
    if (authError) { // Effacer l'erreur seulement si elle est présente et que les champs changent
      clearError();
    }
  }, [email, password, clearError, authError]); // Dépendance à authError pour réagir à son changement

  // Rediriger si l'utilisateur est déjà connecté (par exemple, après un F5 sur la page de login ou après login)
  useEffect(() => {
    if (user && !isLoading) { // S'assurer que isLoading est false pour éviter redirection prématurée
      router.push('/main_group/home');
    }
  }, [user, isLoading, router]);

  const handleSubmit = async (event: React.FormEvent<HTMLFormElement>) => {
    event.preventDefault();
    try {
      await login({ email, password });
      // La redirection se fera via le useEffect ci-dessus après que `user` soit mis à jour.
    } catch (err) {
      // L'erreur est déjà gérée et stockée dans authError par AuthContext.
      // On peut logguer ici si besoin, mais l'affichage se fait via authError.
      console.log("Login component caught error (already set in authError by AuthContext)");
    }
  };

  return (
    <form className="space-y-6" onSubmit={handleSubmit}>
      <div>
        <label htmlFor="email" className="block text-sm font-medium text-x-secondary-text">
          Adresse e-mail
        </label>
        <div className="mt-1">
          <input
            id="email"
            name="email"
            type="email"
            autoComplete="email"
            required
            value={email}
            onChange={(e) => setEmail(e.target.value)}
            className="block w-full appearance-none rounded-md border border-x-border bg-x-card-bg px-3 py-2 placeholder-x-secondary-text text-x-primary-text shadow-sm focus:border-x-accent focus:outline-none focus:ring-x-accent sm:text-sm"
          />
        </div>
      </div>

      <div>
        <label htmlFor="password" className="block text-sm font-medium text-x-secondary-text">
          Mot de passe
        </label>
        <div className="mt-1">
          <input
            id="password"
            name="password"
            type="password"
            autoComplete="current-password"
            required
            value={password}
            onChange={(e) => setPassword(e.target.value)}
            className="block w-full appearance-none rounded-md border border-x-border bg-x-card-bg px-3 py-2 placeholder-x-secondary-text text-x-primary-text shadow-sm focus:border-x-accent focus:outline-none focus:ring-x-accent sm:text-sm"
          />
        </div>
      </div>

      <div>
        <button
          type="submit"
          disabled={isLoading}
          className="flex w-full justify-center rounded-md border border-transparent bg-x-accent py-2 px-4 text-sm font-medium text-white shadow-sm hover:bg-x-accent-hover focus:outline-none focus:ring-2 focus:ring-x-accent focus:ring-offset-2 focus:ring-offset-x-bg disabled:opacity-50"
        >
          {isLoading ? 'Connexion...' : 'Se connecter'}
        </button>
      </div>
      {authError && <p className="mt-2 text-sm text-red-500 text-center">{authError}</p>}
    </form>
  );
}
