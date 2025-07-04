'use client';

import React, { useState, useEffect } from 'react';
import { useAuth } from '@/lib/contexts/AuthContext';
import { useRouter } from 'next/navigation';

export default function RegisterForm() {
  const [name, setName] = useState('');
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [passwordConfirmation, setPasswordConfirmation] = useState('');
  const { register, isLoading, error: authError, clearError, user } = useAuth();
  const router = useRouter();

  useEffect(() => {
    if (authError) {
      clearError();
    }
  }, [name, email, password, passwordConfirmation, clearError, authError]);

  useEffect(() => {
    if (user && !isLoading) {
      router.push('/main_group/home');
    }
  }, [user, isLoading, router]);

  const handleSubmit = async (event: React.FormEvent<HTMLFormElement>) => {
    event.preventDefault();
    if (password !== passwordConfirmation) {
      // Ideally, use the authError state for this, or a separate local error state
      alert("Passwords do not match."); // Replace with a better UI notification
      return;
    }
    try {
      await register({ name, email, password, password_confirmation: passwordConfirmation });
      // Redirection is handled by the useEffect above
    } catch {
      // Error is handled by AuthContext and displayed via authError
    }
  };

  return (
    <form className="space-y-6" onSubmit={handleSubmit}>
      <div>
        <label htmlFor="name" className="block text-sm font-medium text-x-secondary-text">
          Nom complet
        </label>
        <div className="mt-1">
          <input
            id="name"
            name="name"
            type="text"
            autoComplete="name"
            required
            value={name}
            onChange={(e) => setName(e.target.value)}
            className="block w-full appearance-none rounded-md border border-x-border bg-x-card-bg px-3 py-2 placeholder-x-secondary-text text-x-primary-text shadow-sm focus:border-x-accent focus:outline-none focus:ring-x-accent sm:text-sm"
          />
        </div>
      </div>

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
          Confirmez le mot de passe
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
          {isLoading ? 'Création du compte...' : 'S\'inscrire'}
        </button>
      </div>
      {authError && <p className="mt-2 text-sm text-red-500 text-center">{authError}</p>}
    </form>
  );
}
