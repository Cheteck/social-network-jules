'use client';

import React, { useEffect, useState } from 'react';
import { useParams } from 'next/navigation';
import PostCard from '@/components/feed/PostCard';
import { Post } from '@/lib/types/post'; // Garder Post pour le post principal
import CommentCard from '@/components/comments/CommentCard'; // Importer CommentCard
import NewCommentForm from '@/components/comments/NewCommentForm'; // Importer NewCommentForm
import { Comment } from '@/lib/types/comment'; // Importer le type Comment
import apiClient from '@/lib/api';
import Link from 'next/link';

export default function PostDetailPage() {
  const params = useParams();
  const postId = params.post_id as string; // post_id vient du nom du dossier [post_id]

  const [post, setPost] = useState<Post | null>(null);
  const [comments, setComments] = useState<Comment[]>([]);
  const [isLoadingPost, setIsLoadingPost] = useState(true);
  const [isLoadingComments, setIsLoadingComments] = useState(true);
  const [postError, setPostError] = useState<string | null>(null);
  const [commentsError, setCommentsError] = useState<string | null>(null);

  useEffect(() => {
    if (postId) {
      const fetchPostDetails = async () => {
        setIsLoadingPost(true);
        setPostError(null);
        try {
          const response = await apiClient.get(`/api/v1/social/posts/${postId}`);
          const fetchedPost = response.data.data || response.data;
          setPost(fetchedPost);

        } catch (err: unknown) {
          let message = `Impossible de charger le post (ID: ${postId}).`;
          if (err instanceof Error) {
            if (typeof err === 'object' && err !== null && 'response' in err) {
              const errorResponse = (err as { response?: { data?: { message?: string } } }).response;
              if (errorResponse?.data?.message) {
                message = errorResponse.data.message;
              } else {
                message = err.message;
              }
            } else {
              message = err.message;
            }
          }
          console.error("Failed to fetch post details:", err);
          setPostError(message);
          setPost(null);
        } finally {
          setIsLoadingPost(false);
        }
      };

      const fetchComments = async () => {
        setIsLoadingComments(true);
        setCommentsError(null);
        try {
          // L'API pour lister les commentaires est /api/v1/comments/{commentable_type}/{commentable_id}
          // Pour les posts, commentable_type sera 'posts' (ou l'alias morphologique défini dans Laravel)
          const response = await apiClient.get(`/api/v1/comments/posts/${postId}`);
          // Supposons que la réponse est paginée et les commentaires sont dans response.data.data
          setComments(response.data.data || response.data || []);
        } catch (err: unknown) {
          let message = "Impossible de charger les commentaires.";
          if (err instanceof Error) {
            if (typeof err === 'object' && err !== null && 'response' in err) {
              const errorResponse = (err as { response?: { data?: { message?: string } } }).response;
              if (errorResponse?.data?.message) {
                message = errorResponse.data.message;
              } else {
                message = err.message;
              }
            } else {
              message = err.message;
            }
          }
          console.error("Failed to fetch comments:", err);
          setCommentsError(message);
          setComments([]);
        } finally {
          setIsLoadingComments(false);
        }
      };

      fetchPostDetails();
      fetchComments(); // Appeler pour récupérer les commentaires
    }
  }, [postId]); // Déclencher les deux fetches lorsque postId change

  const handleCommentPosted = (newComment: Comment) => {
    // Ajoute le nouveau commentaire à la liste existante pour une mise à jour optimiste
    setComments(prevComments => [newComment, ...prevComments]);
  };

  if (isLoadingPost) { // On peut afficher un loader tant que le post principal charge
    return (
      <div className="flex justify-center items-center min-h-screen text-x-primary-text">
        Chargement du post...
      </div>
    );
  }

  if (postError) { // Changé 'error' en 'postError' pour la clarté
    return <div className="p-4 text-red-500 text-center">{postError}</div>;
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

      <div className="p-4 border-t border-x-border mt-4">
        <h2 className="text-lg font-semibold text-x-primary-text mb-3">Commentaires</h2>
        {isLoadingComments && <p className="text-x-secondary-text">Chargement des commentaires...</p>}
        {commentsError && <p className="text-red-500">{commentsError}</p>}
        {!isLoadingComments && !commentsError && comments.length === 0 && (
          <p className="text-x-secondary-text">Aucun commentaire pour le moment.</p>
        )}
        {!isLoadingComments && !commentsError && comments.length > 0 && (
          <div className="space-y-0"> {/* space-y-0 car CommentCard a déjà des marges/padding */}
            {comments.map(comment => <CommentCard key={comment.id} comment={comment} />)}
          </div>
        )}
        {/* Intégrer le formulaire pour ajouter un nouveau commentaire */}
        {post && ( // Afficher le formulaire seulement si le post est chargé
          <div className="mt-6">
            <NewCommentForm postId={post.id} onCommentPosted={handleCommentPosted} />
          </div>
        )}
      </div>
    </main>
  );
}
