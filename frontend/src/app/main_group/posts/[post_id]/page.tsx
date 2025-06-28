'use client';

import React, { useEffect, useState } from 'react';
import { useParams } from 'next/navigation';
import PostCard from '@/components/feed/PostCard';
import { Post } from '@/lib/types/post';
import apiClient from '@/lib/api';
import Link from 'next/link';

export default function PostDetailPage() {
  const params = useParams();
  const postId = params.post_id as string; // post_id vient du nom du dossier [post_id]

  const [post, setPost] = useState<Post | null>(null);
  const [isLoading, setIsLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    if (postId) {
      const fetchPostDetails = async () => {
        setIsLoading(true);
        setError(null);
        try {
          // L'API pour récupérer un post spécifique est /api/v1/social/posts/{post}
          const response = await apiClient.get(`/api/v1/social/posts/${postId}`);
          // Supposons que la réponse directe est l'objet Post, ou qu'il est dans response.data.data
          const fetchedPost = response.data.data || response.data;

          // S'assurer que l'auteur est bien inclus, sinon le charger ou ajuster le type/PostCard
          if (fetchedPost && !fetchedPost.author && fetchedPost.user_id) {
             // Ce cas est moins probable si PostController->show() fait ->load('author')
             // Mais par précaution, on pourrait imaginer un appel séparé si besoin.
             // Pour l'instant, on assume que 'author' est inclus.
          }
          setPost(fetchedPost);

        } catch (err: any) {
          console.error("Failed to fetch post details:", err);
          setError(err.response?.data?.message || `Impossible de charger le post (ID: ${postId}).`);
          setPost(null);
        } finally {
          setIsLoading(false);
        }
      };
      fetchPostDetails();
    }
  }, [postId]);

  if (isLoading) {
    return (
      <div className="flex justify-center items-center min-h-screen text-x-primary-text">
        Chargement du post...
      </div>
    );
  }

  if (error) {
    return <div className="p-4 text-red-500 text-center">{error}</div>;
  }

  if (!post) {
    return <div className="p-4 text-x-secondary-text text-center">Post non trouvé.</div>;
  }

  return (
    <main className="w-full max-w-xl mx-auto">
      <div className="p-4 border-b border-x-border">
        <Link href="/main_group/home" className="text-x-accent hover:underline mb-4 inline-block">
          &larr; Retour au fil
        </Link>
        <PostCard post={post} />
      </div>
      {/* TODO: Section des commentaires ici */}
      <div className="p-4 border-t border-x-border mt-4">
        <h2 className="text-lg font-semibold text-x-primary-text mb-3">Commentaires</h2>
        {/* Placeholder pour la liste des commentaires et le formulaire de nouveau commentaire */}
        <p className="text-x-secondary-text">Les commentaires seront affichés ici bientôt.</p>
      </div>
    </main>
  );
}
