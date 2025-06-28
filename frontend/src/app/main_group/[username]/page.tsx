'use client';

'use client';

import React, { useEffect, useState } from 'react';
import { useParams } from 'next/navigation';
import PostCard from '@/components/feed/PostCard'; // Renommé
import UserProfileHeader from '@/components/profile/UserProfileHeader';
import { UserProfile } from '@/lib/types/user';
import { Post, PostAuthor } from '@/lib/types/post'; // Renommé et typé avec Post
import apiClient from '@/lib/api'; // Importer apiClient

// Données de simulation - SERONT SUPPRIMÉES
const mockUserProfiles: Record<string, UserProfile> = {
  jverne: {
    id: 'user1',
    name: 'Jules Verne',
    username: 'jverne',
    avatar_url: 'https://via.placeholder.com/150/007bff/ffffff?Text=JV',
    bio: 'Écrivain français, auteur de romans d\'aventure et de science-fiction. Passionné par les voyages extraordinaires.',
    followers_count: 1250,
    following_count: 80,
    tweets_count: 35, // Ce champ 'tweets_count' sur UserProfile sera probablement 'posts_count' ou similaire
    created_at: new Date('1828-02-08').toISOString(),
  },
  mcurie: {
    id: 'user2',
    name: 'Marie Curie',
    username: 'mcurie',
    avatar_url: 'https://via.placeholder.com/150/28a745/ffffff?Text=MC',
    bio: 'Physicienne et chimiste polonaise, naturalisée française. Pionnière dans l\'étude de la radioactivité. Double prix Nobel.',
    followers_count: 2500,
    following_count: 50,
    tweets_count: 15,
    created_at: new Date('1867-11-07').toISOString(),
  }
};

const mockUserPosts: Record<string, Post[]> = {
  jverne: [
    { id: 'jv_post1', author: mockUserProfiles.jverne as unknown as PostAuthor, content: 'Le Nautilus est prêt pour une nouvelle expédition !', created_at: new Date().toISOString(), likes_count: 12, retweets_count: 3, comments_count: 2 },
    { id: 'jv_post2', author: mockUserProfiles.jverne as unknown as PostAuthor, content: 'Pensées sur le voyage au centre de la Terre...', created_at: new Date(Date.now() - 1000 * 60 * 60 * 5).toISOString(), likes_count: 25, retweets_count: 5, comments_count: 4  },
  ],
  mcurie: [
    { id: 'mc_post1', author: mockUserProfiles.mcurie as unknown as PostAuthor, content: 'Nouvelle expérience en cours. Les résultats préliminaires sont prometteurs.', created_at: new Date().toISOString(), likes_count: 45, retweets_count: 10, comments_count: 8 },
  ]
};
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
        } catch (err: any) {
          console.error("Failed to fetch profile data:", err);
          setError(err.response?.data?.message || "Profil non trouvé ou erreur lors de la récupération des données.");
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
    return <div className="text-center py-10 text-x-primary-text">Ce profil n'existe pas.</div>;
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
          <p>Cet utilisateur n'a encore rien posté.</p>
        )}
      </div>
    </main>
  );
}
