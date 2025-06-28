'use client';

import React, { useEffect, useState } from 'react';
import { useParams, notFound } from 'next/navigation';
import PostCard from '@/components/feed/PostCard';
import { Post } from '@/lib/types/post';
import apiClient from '@/lib/api';
import Link from 'next/link';

export default function TagPage() {
  const params = useParams();
  const tagName = params.tagname as string;

  const [posts, setPosts] = useState<Post[]>([]);
  const [isLoading, setIsLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    if (tagName) {
      const fetchPostsByTag = async () => {
        setIsLoading(true);
        setError(null);
        try {
          // L'API pour récupérer les posts par hashtag est à confirmer.
          // Supposition: GET /api/v1/hashtags/{tagname}/posts
          // ou GET /api/v1/posts?hashtag={tagname}
          // Le package ijideals/hashtag-system a une route GET /api/hashtags/{hashtagSlug}/posts
          // donc on va utiliser cela, en adaptant le préfixe si nécessaire.
          // Si le préfixe global est /api, alors ce sera /api/hashtags/{...}
          // Si le préfixe du package est /api/v1/hashtags, alors /api/v1/hashtags/{...}
          // Je vais supposer /api/hashtags/{tagName}/posts pour l'instant
          const response = await apiClient.get(`/api/hashtags/${tagName.toLowerCase()}/posts`);
          setPosts(response.data.data || response.data || []);
        } catch (err: unknown) {
          const errorMessageBase = `Impossible de charger les posts pour le tag #${tagName}.`;
          let specificMessage = "";

          if (err instanceof Error) {
            specificMessage = err.message; // Default to generic error message
            // Check for Axios-like error structure more safely
            if (typeof err === 'object' && err !== null && 'response' in err) {
              const errorResponse = (err as { response?: { status?: number, data?: { message?: string } } }).response;
              if (errorResponse?.status === 404) {
                setError(`Le tag #${tagName} n'a pas été trouvé ou n'a aucun post associé.`);
                setPosts([]);
                setIsLoading(false);
                return; // Exit early for 404
              }
              if (errorResponse?.data?.message) {
                specificMessage = errorResponse.data.message;
              }
            }
          }

          console.error(`Failed to fetch posts for tag #${tagName}:`, err);
          setError(specificMessage && specificMessage !== err.message ? `${errorMessageBase} Détail: ${specificMessage}` : errorMessageBase);
          setPosts([]);
        } finally {
          setIsLoading(false);
        }
      };
      fetchPostsByTag();
    } else {
      // Gérer le cas où tagName n'est pas défini, bien que Next.js devrait gérer cela avec notFound()
      setIsLoading(false);
      setError("Nom de tag non spécifié.");
    }
  }, [tagName]);

  if (!tagName) {
    // Devrait être intercepté par le routing de Next.js, mais comme garde-fou.
    notFound();
  }

  if (isLoading) {
    return (
      <div className="flex justify-center items-center min-h-screen text-x-primary-text">
        Chargement des posts pour #{tagName}...
      </div>
    );
  }

  return (
    <main className="w-full max-w-xl mx-auto">
      <div className="p-4 border-b border-x-border">
        <Link href="/main_group/home" className="text-sm text-x-accent hover:underline mb-2 inline-block">
          &larr; Retour
        </Link>
        <h1 className="text-2xl font-bold text-x-primary-text">
          Posts avec <span className="text-x-accent">#{tagName}</span>
        </h1>
      </div>

      {error && <div className="p-4 text-red-500 text-center">{error}</div>}

      {!error && posts.length === 0 && (
        <p className="p-4 text-x-secondary-text text-center">Aucun post trouvé pour #{tagName}.</p>
      )}

      <div className="space-y-0"> {/* PostCard a déjà une marge en bas */}
        {posts.map(post => (
          <PostCard key={post.id} post={post} />
        ))}
      </div>
    </main>
  );
}
