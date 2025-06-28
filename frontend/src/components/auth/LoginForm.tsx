'use client';

import React, { useState } from 'react';
// import { useAuth } from '@/lib/contexts/AuthContext'; // Sera ajouté plus tard

export default function LoginForm() {
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [error, setError] = useState<string | null>(null);
  const [isLoading, setIsLoading] = useState(false);
  // const { login } = useAuth(); // Sera ajouté plus tard

  const handleSubmit = async (event: React.FormEvent<HTMLFormElement>) => {
    event.preventDefault();
    setError(null);
    setIsLoading(true);

    await new Promise(resolve => setTimeout(resolve, 1000));
    console.log('Login attempt with:', { email, password });
    // try {
    //   await login(email, password);
    // } catch (err) {
    //   setError('Failed to login. Please check your credentials.');
    // }

    setIsLoading(false);
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
      {error && <p className="text-sm text-red-500 text-center">{error}</p>}
    </form>
  );
}
