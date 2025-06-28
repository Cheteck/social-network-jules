'use client';

import React, { createContext, useContext, useState, useEffect, ReactNode } from 'react';
import apiClient, { fetchCsrfToken } from '@/lib/api';
import { useRouter } from 'next/navigation'; // Import pour la redirection

// Type pour les données utilisateur (simplifié pour l'instant)
interface User {
  id: number;
  name: string;
  email: string;
  username?: string; // Ajouté pour la redirection vers le profil
  // Ajoutez d'autres champs si nécessaire (ex: avatar_url, profile_bio, etc.)
}

// Type pour les identifiants de connexion
interface LoginCredentials {
  email: string;
  password: string;
}

// Type pour les données d'inscription (à compléter selon les besoins)
interface RegisterData {
  name: string;
  email: string;
  password: string;
  password_confirmation: string;
  // Ajoutez d'autres champs si nécessaire (ex: username)
}

interface AuthContextType {
  user: User | null;
  isLoading: boolean;
  login: (credentials: LoginCredentials) => Promise<void>;
  register: (data: RegisterData) => Promise<void>;
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
    } catch { // _fetchUserError removed as it's not used
      setUser(null);
      // console.error('Fetch user failed:'); // Optionnel: log si besoin
    } finally {
      setIsLoading(false);
    }
  };

  useEffect(() => {
    fetchUser();
  }, []);

  const login = async (credentials: LoginCredentials) => {
    setIsLoading(true);
    setError(null);
    try {
      await fetchCsrfToken();
      await apiClient.post('/login', credentials); // Endpoint Fortify pour la connexion
      await fetchUser(); // Récupérer les infos utilisateur et mettre à jour l'état
      // La redirection sera gérée par le composant/page appelant
    } catch (err: unknown) {
      let errorMessage = 'Échec de la connexion.';
      if (err instanceof Error) {
        if (typeof err === 'object' && err !== null && 'response' in err) {
          const errorResponse = (err as { response?: { status?: number, data?: { message?: string } } }).response;
          if (errorResponse?.status === 422) {
            errorMessage = 'Veuillez vérifier les champs saisis.';
          }
          // Utiliser le message spécifique de l'API si disponible, sinon le message d'erreur générique, ou le message par défaut.
          errorMessage = errorResponse?.data?.message || err.message || errorMessage;
        } else {
           errorMessage = err.message || errorMessage; // Fallback si pas une erreur de type Axios
        }
      }
      console.error('Login failed:', err);
      setUser(null);
      setError(errorMessage);
      throw err; // Renvoyer l'erreur pour que le composant puisse aussi réagir
    } finally {
      setIsLoading(false);
    }
  };

  const register = async (data: RegisterData) => {
    setIsLoading(true);
    setError(null);
    try {
      await fetchCsrfToken();
      await apiClient.post('/register', data); // Endpoint Fortify pour l'inscription
      await fetchUser(); // Connecter l'utilisateur et récupérer ses infos
      // La redirection sera gérée par le composant/page appelant
    } catch (err: unknown) {
      let errorMessage = 'Échec de l\'inscription.';
      if (err instanceof Error) {
        if (typeof err === 'object' && err !== null && 'response' in err) {
          const errorResponse = (err as { response?: { status?: number, data?: { message?: string } } }).response;
          if (errorResponse?.status === 422) {
            errorMessage = 'Veuillez vérifier les champs saisis.';
          }
           errorMessage = errorResponse?.data?.message || err.message || errorMessage;
        } else {
           errorMessage = err.message || errorMessage;
        }
      }
      console.error('Registration failed:', err);
      setUser(null);
      setError(errorMessage);
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
    } catch { // _logoutError removed as it's not used
      // console.error('Logout failed:'); // Optionnel
      // Même si le logout échoue côté serveur, forcer la déconnexion côté client
      setUser(null);
      // setError('Échec de la déconnexion. Redirection forcée.'); // Commenté car peut-être pas nécessaire
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
