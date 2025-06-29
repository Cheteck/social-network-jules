'use client';

import React, { useState, useEffect } from 'react';
import { useAuth } from '@/lib/contexts/AuthContext'; // Assuming AuthContext will provide forgotPassword logic

export default function ForgotPasswordForm() {
  const [email, setEmail] = useState('');
  const [message, setMessage] = useState(''); // For success or error messages specific to this form

  // Assuming useAuth will be extended to include forgotPassword related states and functions
  // For now, let's mock what we might need or adapt based on AuthContext structure
  const { forgotPassword, isLoading, error: authError, clearError } = useAuth();

  useEffect(() => {
    // Clear previous auth errors when email changes or component mounts
    if (authError) {
      clearError();
    }
    setMessage(''); // Clear local messages too
  }, [email, clearError, authError]);

  const handleSubmit = async (event: React.FormEvent<HTMLFormElement>) => {
    event.preventDefault();
    setMessage('');
    if (!email) {
      setMessage('Veuillez entrer votre adresse e-mail.');
      return;
    }
    try {
      // The forgotPassword function in AuthContext will handle API call and global errors
      await forgotPassword(email);
      setMessage('Si un compte avec cet e-mail existe, un lien de réinitialisation de mot de passe a été envoyé.');
      setEmail(''); // Clear email field on success
    } catch {
      // AuthContext will store the error in authError, which can be displayed.
      // Or, we can set a local message if preferred for non-global errors.
      if (!authError) { // If AuthContext didn't set a specific error, use a generic one
        setMessage('Une erreur est survenue. Veuillez réessayer.');
      }
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
        <button
          type="submit"
          disabled={isLoading}
          className="flex w-full justify-center rounded-md border border-transparent bg-x-accent py-2 px-4 text-sm font-medium text-white shadow-sm hover:bg-x-accent-hover focus:outline-none focus:ring-2 focus:ring-x-accent focus:ring-offset-2 focus:ring-offset-x-bg disabled:opacity-50"
        >
          {isLoading ? 'Envoi en cours...' : 'Envoyer le lien de réinitialisation'}
        </button>
      </div>
      {message && <p className="mt-2 text-sm text-center text-x-primary-text">{message}</p>}
      {authError && <p className="mt-2 text-sm text-red-500 text-center">{authError}</p>}
    </form>
  );
}
