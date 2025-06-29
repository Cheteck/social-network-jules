'use client';

import React, { useState, useEffect } from 'react';
import { useAuth } from '@/lib/contexts/AuthContext';
import { useRouter } from 'next/navigation';

interface ResetPasswordFormProps {
  token: string;
}

export default function ResetPasswordForm({ token }: ResetPasswordFormProps) {
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [passwordConfirmation, setPasswordConfirmation] = useState('');
  const [message, setMessage] = useState('');

  const { resetPassword, isLoading, error: authError, clearError } = useAuth(); // Assuming resetPassword will be added to AuthContext
  const router = useRouter();

  useEffect(() => {
    if (authError) {
      clearError();
    }
    setMessage('');
  }, [email, password, passwordConfirmation, clearError, authError]);

  const handleSubmit = async (event: React.FormEvent<HTMLFormElement>) => {
    event.preventDefault();
    setMessage('');

    if (password !== passwordConfirmation) {
      setMessage('Les mots de passe ne correspondent pas.');
      return;
    }
    if (!token) {
        setMessage('Jeton de réinitialisation manquant ou invalide.');
        return;
    }

    try {
      // The resetPassword function in AuthContext will handle the API call
      await resetPassword({ email, password, password_confirmation: passwordConfirmation, token });
      setMessage('Votre mot de passe a été réinitialisé avec succès. Vous allez être redirigé vers la page de connexion.');
      setEmail('');
      setPassword('');
      setPasswordConfirmation('');
      setTimeout(() => {
        router.push('/auth_group/login?status=password-reset-success');
      }, 3000);
    } catch {
      if (!authError) { // If AuthContext didn't set a specific error from API
        setMessage('Une erreur est survenue lors de la réinitialisation. Veuillez réessayer.');
      }
      // authError from AuthContext will be displayed by the form
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
          Nouveau mot de passe
        </label>
        <div className="mt-1">
          <input
            id="password"
            name="password"
            type="password"
            autoComplete="new-password"
            required
            value={password}
            onChange={(e) => setPassword(e.target.value)}
            className="block w-full appearance-none rounded-md border border-x-border bg-x-card-bg px-3 py-2 placeholder-x-secondary-text text-x-primary-text shadow-sm focus:border-x-accent focus:outline-none focus:ring-x-accent sm:text-sm"
          />
        </div>
      </div>

      <div>
        <label htmlFor="password_confirmation" className="block text-sm font-medium text-x-secondary-text">
          Confirmez le nouveau mot de passe
        </label>
        <div className="mt-1">
          <input
            id="password_confirmation"
            name="password_confirmation"
            type="password"
            autoComplete="new-password"
            required
            value={passwordConfirmation}
            onChange={(e) => setPasswordConfirmation(e.target.value)}
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
          {isLoading ? 'Réinitialisation en cours...' : 'Réinitialiser le mot de passe'}
        </button>
      </div>
      {message && <p className="mt-2 text-sm text-center text-x-primary-text">{message}</p>}
      {authError && <p className="mt-2 text-sm text-red-500 text-center">{authError}</p>}
    </form>
  );
}
