'use client';

'use client';

import React, { useEffect, useState } from 'react';
import { useParams } from 'next/navigation';
import PostCard from '@/components/feed/PostCard'; // Renommé
import UserProfileHeader from '@/components/profile/UserProfileHeader';
import { UserProfile } from '@/lib/types/user';
import { Post } from '@/lib/types/post'; // PostAuthor n'est pas utilisé
import apiClient from '@/lib/api'; // Importer apiClient

// Données de simulation mockUserProfiles ont été complètement supprimées car non utilisées.
// Fin des données de simulation


export default function UserProfilePage() {
  const params = useParams();
  const username = params.username as string; // TODO: Gérer le cas où username est un array

  const [profile, setProfile] = useState<UserProfile | null>(null);
  const [posts, setPosts] = useState<Post[]>([]);
  const [isLoading, setIsLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    if (username) {
      const fetchProfileData = async () => {
        setIsLoading(true);
        setError(null);
        try {
          // 1. Récupérer le profil utilisateur
          // L'endpoint exact /api/users/{username}/profile est une supposition,
          // il pourrait être /api/profile/{username} ou nécessiter un ID.
          // Pour l'instant, on assume que l'API peut résoudre par username.
          const profileResponse = await apiClient.get(`/api/users/${username}/profile`);
          const userProfileData = profileResponse.data.data || profileResponse.data; // Adapter si l'API enveloppe dans 'data'
          setProfile(userProfileData);

          // 2. Si le profil est trouvé, récupérer les posts de cet utilisateur
          // Supposons que userProfileData.id contient l'ID de l'utilisateur
          if (userProfileData && userProfileData.id) {
            // L'endpoint /api/v1/social/posts?author_id={id} est une supposition.
            // Il faudrait vérifier l'API de ijideals/social-posts pour la bonne méthode de filtrage.
            const postsResponse = await apiClient.get(`/api/v1/social/posts?author_id=${userProfileData.id}`);
            setPosts(postsResponse.data.data || postsResponse.data || []);
          } else {
            setPosts([]); // Pas de profil, donc pas de posts
          }
        } catch (err: unknown) {
          let message = "Profil non trouvé ou erreur lors de la récupération des données.";
          if (err instanceof Error) {
            // Check for Axios-like error structure more safely
            if (typeof err === 'object' && err !== null && 'response' in err) {
              const response = (err as { response?: { data?: { message?: string } } }).response;
              if (response?.data?.message) {
                message = response.data.message;
              } else {
                message = err.message; // Fallback to generic error message if no specific one
              }
            } else {
              message = err.message; // Fallback if not an Axios-like error
            }
          }
          console.error("Failed to fetch profile data:", err);
          setError(message);
          setProfile(null);
          setPosts([]);
        } finally {
          setIsLoading(false);
        }
      };
      fetchProfileData();
    }
  }, [username]);

  if (isLoading) {
    return <div className="text-center py-10 text-x-primary-text">Chargement du profil...</div>;
  }

  if (error) {
    return <div className="text-center py-10 text-red-500">{error}</div>;
  }

  if (!profile) {
    return <div className="text-center py-10 text-x-primary-text">Ce profil n&apos;existe pas.</div>;
  }

  return (
    <main className="container mx-auto max-w-2xl py-8 px-4 text-x-primary-text">
      <UserProfileHeader userProfile={profile} />

      <div className="mt-8">
        <h2 className="text-xl font-semibold mb-4 text-x-primary-text">Posts</h2>
        {posts.length > 0 ? (
          <div className="space-y-4">
            {posts.map(post => <PostCard key={post.id} post={post} />)}
          </div>
        ) : (
          <p>Cet utilisateur n&apos;a encore rien posté.</p>
        )}
      </div>
    </main>
  );
}
