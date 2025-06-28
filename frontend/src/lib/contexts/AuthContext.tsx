'use client';

import React, { createContext, useContext, useState, useEffect, ReactNode } from 'react';
import apiClient, { fetchCsrfToken } from '@/lib/api';
import { useRouter } from 'next/navigation'; // Import pour la redirection

// Type pour les données utilisateur (simplifié pour l'instant)
interface User {
  id: number;
  name: string;
  email: string;
  // Ajoutez d'autres champs si nécessaire (ex: avatar_url, profile_bio, etc.)
}

interface AuthContextType {
  user: User | null;
  isLoading: boolean;
  login: (credentials: { email: string; password: string }) => Promise<void>;
  register: (data: any) => Promise<void>; // Les données d'inscription seront plus spécifiques
  logout: () => Promise<void>;
  error: string | null;
  clearError: () => void; // Pour effacer les erreurs
}

const AuthContext = createContext<AuthContextType | undefined>(undefined);

export const AuthProvider = ({ children }: { children: ReactNode }) => {
  const [user, setUser] = useState<User | null>(null);
  const [isLoading, setIsLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const router = useRouter();

  const clearError = () => setError(null);

  const fetchUser = async () => {
    try {
      const response = await apiClient.get('/api/user');
      setUser(response.data as User); // Assumer que response.data est de type User
    } catch (err) {
      setUser(null);
    } finally {
      setIsLoading(false);
    }
  };

  useEffect(() => {
    fetchUser();
  }, []);

  const login = async (credentials: { email: string; password: string }) => {
    setIsLoading(true);
    setError(null);
    try {
      await fetchCsrfToken();
      await apiClient.post('/login', credentials); // Endpoint Fortify pour la connexion
      await fetchUser(); // Récupérer les infos utilisateur et mettre à jour l'état
      // La redirection sera gérée par le composant/page appelant
    } catch (err: any) {
      console.error('Login failed:', err);
      setUser(null);
      const message = err.response?.data?.message ||
                      (err.response?.status === 422 ? 'Veuillez vérifier les champs saisis.' : 'Échec de la connexion.');
      setError(message);
      throw err; // Renvoyer l'erreur pour que le composant puisse aussi réagir
    } finally {
      setIsLoading(false);
    }
  };

  const register = async (data: any) => {
    setIsLoading(true);
    setError(null);
    try {
      await fetchCsrfToken();
      await apiClient.post('/register', data); // Endpoint Fortify pour l'inscription
      await fetchUser(); // Connecter l'utilisateur et récupérer ses infos
      // La redirection sera gérée par le composant/page appelant
    } catch (err: any) {
      console.error('Registration failed:', err);
      setUser(null);
      const message = err.response?.data?.message ||
                      (err.response?.status === 422 ? 'Veuillez vérifier les champs saisis.' : 'Échec de l\'inscription.');
      setError(message);
      throw err;
    } finally {
      setIsLoading(false);
    }
  };

  const logout = async () => {
    setIsLoading(true);
    setError(null);
    try {
      await apiClient.post('/logout'); // Endpoint Fortify pour la déconnexion
      setUser(null);
      router.push('/auth_group/login'); // Rediriger vers login après déconnexion
    } catch (err) {
      console.error('Logout failed:', err);
      // Même si le logout échoue côté serveur, forcer la déconnexion côté client
      setUser(null);
      setError('Échec de la déconnexion. Redirection forcée.');
      router.push('/auth_group/login'); // S'assurer de la redirection
    } finally {
      setIsLoading(false);
    }
  };

  return (
    <AuthContext.Provider value={{ user, isLoading, login, register, logout, error, clearError }}>
      {children}
    </AuthContext.Provider>
  );
};

export const useAuth = () => {
  const context = useContext(AuthContext);
  if (context === undefined) {
    throw new Error('useAuth must be used within an AuthProvider');
  }
  return context;
};
