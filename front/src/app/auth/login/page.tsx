"use client";
import React, { useState } from 'react';
import { useRouter } from 'next/navigation';
import Link from 'next/link';
import { login } from '@/lib/api/auth'; // Import the new login function

// The getCookie function is no longer needed here as apiClient handles XSRF token.

export default function LoginPage() {
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const router = useRouter();

  async function handleSubmit(e: React.FormEvent) {
    e.preventDefault();
    setLoading(true);
    setError(null);
    try {
      // The fetchCsrfToken call and XSRF header are handled inside the login function in auth.ts
      // The login function in auth.ts also handles saving the auth_token if returned.
      await login(email, password);
      // Handle successful login:
      // console.log('Login successful:', responseData); // responseData is returned by login if needed
      router.replace('/feed'); // Or your desired redirect path
    } catch (err: any) {
      // Error handling can be more specific based on error structure from apiClient
      setError(err.response?.data?.message || err.message || 'Erreur de connexion inconnue');
    } finally {
      setLoading(false);
    }
  }

  return (
    <div className="min-h-screen flex items-center justify-center bg-gradient-to-br from-blue-50 to-indigo-100 p-4 sm:p-6">
      <div className="w-full max-w-md bg-white rounded-xl shadow-2xl overflow-hidden mx-2 sm:mx-0">
        <div className="bg-gradient-to-r from-blue-600 to-indigo-700 p-4 sm:p-6 text-white">
          <h1 className="text-xl sm:text-2xl font-bold text-center">Bienvenue</h1>
          <p className="text-center text-blue-100 text-sm sm:text-base">Connectez-vous à votre compte</p>
        </div>

        <form onSubmit={handleSubmit} className="p-6 sm:p-8 space-y-4 sm:space-y-6">
          <div className="space-y-3 sm:space-y-4">
            <div>
              <label htmlFor="email" className="block text-sm font-medium text-gray-700 mb-1">
                Email
              </label>
              <input
                id="email"
                type="email"
                placeholder="votre@email.com"
                className="w-full px-3 py-2 sm:px-4 sm:py-3 rounded-lg border border-gray-300 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition text-sm sm:text-base"
                value={email}
                onChange={e => setEmail(e.target.value)}
                required
                autoFocus
              />
            </div>

            <div>
              <label htmlFor="password" className="block text-sm font-medium text-gray-700 mb-1">
                Mot de passe
              </label>
              <input
                id="password"
                type="password"
                placeholder="••••••••"
                className="w-full px-3 py-2 sm:px-4 sm:py-3 rounded-lg border border-gray-300 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition text-sm sm:text-base"
                value={password}
                onChange={e => setPassword(e.target.value)}
                required
              />
            </div>
          </div>

          {error && (
            <div className="p-2 sm:p-3 bg-red-50 text-red-600 rounded-lg text-xs sm:text-sm">
              {error}
            </div>
          )}

          <button
            type="submit"
            className="w-full bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white font-semibold py-2 sm:py-3 px-4 rounded-lg shadow-md hover:shadow-lg transition-all duration-300 disabled:opacity-70 disabled:cursor-not-allowed text-sm sm:text-base"
            disabled={loading}
          >
            {loading ? (
              <span className="flex items-center justify-center">
                <svg className="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                  <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4"></circle>
                  <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                Connexion en cours...
              </span>
            ) : 'Se connecter'}
          </button>

          <div className="text-center text-xs sm:text-sm text-gray-500">
            Pas encore de compte?{' '}
            <Link href="/auth/register" className="text-blue-600 hover:text-blue-800 font-medium">
              Créer un compte
            </Link>
          </div>
        </form>
      </div>
    </div>
  );
}
