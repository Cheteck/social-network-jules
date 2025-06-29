'use client';

import React, { useEffect, useState } from 'react';
import PostCard from '@/components/feed/PostCard'; // Renommé
import { Post } from '@/lib/types/post'; // Renommé, PostAuthor non utilisé ici
import NewPostForm from '@/components/feed/NewPostForm'; // Renommé
import { useAuth } from '@/lib/contexts/AuthContext';
import apiClient from '@/lib/api'; // Import du client API

// Données de simulation pour les tweets - SERONT SUPPRIMÉES
// const mockPosts: Post[] = [ ... ]; // Supprimé

export default function HomePage() {
  const { user, isLoading: authLoading } = useAuth();
  const [posts, setPosts] = useState<Post[]>([]); // Renommé et typé avec Post
  const [isLoadingTweets, setIsLoadingTweets] = useState(true);
  const [fetchError, setFetchError] = useState<string | null>(null);

  useEffect(() => {
    const fetchTweets = async () => {
      if (!user || authLoading) {
        // Attendre que l'utilisateur soit chargé et authentifié
        // Si authLoading est true, ou si user est null après chargement, ne rien faire.
        // ProtectedRouteWrapper devrait gérer la redirection si user est null après authLoading.
        if (!authLoading && !user) {
          setIsLoadingTweets(false); // Pas de tweets à charger si pas d'utilisateur
        }
        return;
      }

      setIsLoadingTweets(true);
      setFetchError(null);
      try {
        // L'API /api/v1/feed semble être la plus appropriée pour un fil d'actualité personnalisé
        const response = await apiClient.get('/api/v1/feed');
        // La structure exacte des données de l'API doit être vérifiée.
        // Supposons que response.data est un tableau de tweets ou un objet avec une clé 'data'
        setPosts(response.data.data || response.data || []); // Renommé
      } catch (error) {
        const errorMessage = error instanceof Error ? error.message : String(error);
        console.error("Failed to fetch tweets:", errorMessage);
        setFetchError(errorMessage || "Impossible de charger le fil d'actualité.");
        setPosts([]); // Renommé
      } finally {
        setIsLoadingTweets(false);
      }
    };

    fetchTweets();
  }, [user, authLoading]); // Déclencher si user ou authLoading change

  const handlePostPosted = (newPost: Post) => { // Renommé
    // Ajouter le nouveau tweet en haut de la liste existante
    // ou re-fetcher toute la liste pour une approche plus simple au début
    // Pour l'instant, ajout en haut :
    setPosts(prevPosts => [newPost, ...prevPosts]); // Renommé
  };

  if (authLoading || isLoadingTweets) {
    return (
      <div className="flex justify-center items-center min-h-screen text-white">
        Chargement du fil d&apos;actualité...
      </div>
    );
  }

  // ProtectedRouteWrapper devrait déjà gérer la redirection si !user après chargement auth.
  // Mais on peut ajouter un message si l'utilisateur est null et que les chargements sont terminés.
  if (!user) {
     // Ensuring this line is clean and uses &apos;
     return <div className="text-center py-10 text-x-secondary-text">Veuillez vous connecter pour voir votre fil d&apos;actualité.</div>;
  }

  return (
    <main className="w-full max-w-xl mx-auto"> {/* Similaire au template.html pour le conteneur principal */}
      <div className="border-b border-x-border px-4 py-3">
        <h1 className="text-xl font-bold text-x-primary-text">Accueil</h1>
      </div>

      <NewPostForm onPostPosted={handlePostPosted} /> {/* Renommé */}

      <div className="space-y-4">
        {fetchError && <div className="p-4 text-red-500 text-center">{fetchError}</div>}
        {posts.length === 0 && !isLoadingTweets && !fetchError ? ( // Ajout de !fetchError ici pour éviter double message
          <p className="p-4 text-x-secondary-text text-center">Aucun post à afficher pour le moment. Essayez de suivre d&apos;autres utilisateurs ou revenez plus tard !</p>
        ) : (
          !fetchError && posts.map(post => <PostCard key={post.id} post={post} />) // Renommé
        )}
      </div>
    </main>
  );
}
