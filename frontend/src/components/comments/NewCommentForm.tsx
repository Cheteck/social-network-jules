'use client';

import React, { useState } from 'react';
import { useAuth } from '@/lib/contexts/AuthContext';
import apiClient from '@/lib/api';
import { Comment } from '@/lib/types/comment'; // Pour le type du callback

interface NewCommentFormProps {
  postId: string | number; // ID du post auquel le commentaire est destiné
  commentableType?: string; // Par défaut 'posts', mais pourrait être configurable
  parentId?: number | string | null; // Pour les réponses aux commentaires
  onCommentPosted: (newComment: Comment) => void; // Callback pour mettre à jour l'UI
}

export default function NewCommentForm({
  postId,
  commentableType = 'posts', // Valeur par défaut
  parentId = null,
  onCommentPosted,
}: NewCommentFormProps) {
  const { user, isLoading: authLoading } = useAuth();
  const [content, setContent] = useState('');
  const [isLoading, setIsLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const handleSubmit = async (event: React.FormEvent<HTMLFormElement>) => {
    event.preventDefault();
    if (!content.trim()) {
      setError("Le commentaire ne peut pas être vide.");
      return;
    }
    if (!user) {
      setError("Vous devez être connecté pour commenter.");
      // Idéalement, ce formulaire ne devrait même pas être affiché si l'utilisateur n'est pas connecté.
      return;
    }

    setIsLoading(true);
    setError(null);

    try {
      const payload: { content: string; parent_id?: number | string | null } = { content };
      if (parentId) {
        payload.parent_id = parentId;
      }

      const response = await apiClient.post(
        `/api/v1/comments/${commentableType}/${postId}`,
        payload
      );

      // Supposer que l'API retourne le commentaire créé, incluant l'objet 'commenter'
      const newComment = response.data.comment || response.data.data || response.data;

      if (newComment) {
        onCommentPosted(newComment);
      }
      setContent(''); // Vider le textarea après succès
    } catch (err: unknown) {
      let message = "Une erreur est survenue lors de la publication du commentaire.";
      if (err instanceof Error) {
        // Check for Axios-like error structure more safely
        if (typeof err === 'object' && err !== null && 'response' in err) {
          const errorResponse = (err as { response?: { data?: { message?: string } } }).response;
          if (errorResponse?.data?.message) {
            message = errorResponse.data.message;
          } else {
            message = err.message; // Fallback to generic error message
          }
        } else {
          message = err.message; // Fallback if not an Axios-like error
        }
      }
      console.error("Failed to post comment:", err);
      setError(message);
    } finally {
      setIsLoading(false);
    }
  };

  if (authLoading) {
    return <p className="text-sm text-x-secondary-text">Chargement...</p>;
  }
  if (!user) {
    return <p className="text-sm text-x-secondary-text">Veuillez vous <a href="/auth_group/login" className="text-x-accent hover:underline">connecter</a> pour commenter.</p>;
  }

  return (
    <form onSubmit={handleSubmit} className="mt-4 space-y-3">
      <textarea
        value={content}
        onChange={(e) => {
          setContent(e.target.value);
          if (error) setError(null);
        }}
        placeholder={parentId ? "Répondre à ce commentaire..." : "Ajouter un commentaire..."}
        rows={3}
        className="w-full p-2 bg-x-bg text-x-primary-text border border-x-border rounded-md focus:ring-2 focus:ring-x-accent focus:border-x-accent resize-none placeholder-x-secondary-text"
      />
      <div className="flex justify-end">
        <button
          type="submit"
          disabled={isLoading || !content.trim()}
          className="bg-x-accent text-white rounded-full px-5 py-2 text-sm font-semibold hover:bg-x-accent-hover disabled:opacity-50 transition-colors"
        >
          {isLoading ? 'Envoi...' : (parentId ? 'Répondre' : 'Commenter')}
        </button>
      </div>
      {error && <p className="text-sm text-red-500 mt-1">{error}</p>}
    </form>
  );
}
