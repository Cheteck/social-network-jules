'use client';

import React, { useState } from 'react';
import { useAuth } from '@/lib/contexts/AuthContext';
import apiClient from '@/lib/api';
import { Post } from '@/lib/types/post'; // Importer le type Post

interface NewPostFormProps { // Renommage de l'interface
  onPostPosted?: (newPost: Post) => void; // Callback avec le type Post
}

export default function NewPostForm({ onPostPosted }: NewPostFormProps) { // Renommage du composant et de la prop
  const { user, isLoading: authLoading } = useAuth();
  const [content, setContent] = useState('');
  const [isLoading, setIsLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const MAX_TWEET_LENGTH = 280; // Limite de caractères typique

  const handleSubmit = async (event: React.FormEvent<HTMLFormElement>) => {
    event.preventDefault();
    if (!content.trim() || content.length > MAX_TWEET_LENGTH) {
      setError(`Le post ne peut pas être vide et doit faire au maximum ${MAX_TWEET_LENGTH} caractères.`);
      return;
    }
    if (!user) {
      setError("Vous devez être connecté pour poster.");
      return;
    }

    setIsLoading(true);
    setError(null);

    try {
      // L'API /api/v1/social/posts attend un champ 'content' (confirmé par analyse du PostController)
      const response = await apiClient.post('/api/v1/social/posts', { content: content });

      // Si l'API retourne le tweet créé (ce qui est une bonne pratique)
      if (onPostPosted && response.data) {
        onPostPosted(response.data.data || response.data); // Supposer que le post est dans response.data ou response.data.data
      }
      setContent(''); // Vider le textarea après succès
    } catch (err: any) {
      console.error("Failed to post:", err);
      setError(err.response?.data?.message || "Une erreur est survenue lors de la publication du post.");
    } finally {
      setIsLoading(false);
    }
  };

  if (authLoading || !user) {
    // Ne pas afficher le formulaire si l'utilisateur n'est pas chargé ou pas connecté
    // Ou afficher un message invitant à se connecter
    return null;
  }

  const charsLeft = MAX_TWEET_LENGTH - content.length;

  return (
    <div className="border-b border-x-border p-4 mb-4">
      <form onSubmit={handleSubmit} className="space-y-3">
        <textarea
          value={content}
          onChange={(e) => {
            setContent(e.target.value);
            if (error) setError(null); // Effacer l'erreur en tapant
          }}
          placeholder="Quoi de neuf ?"
          rows={3}
          className="w-full p-2 bg-x-bg text-x-primary-text border border-x-border rounded-md focus:ring-2 focus:ring-x-accent focus:border-x-accent resize-none placeholder-x-secondary-text"
          maxLength={MAX_TWEET_LENGTH}
        />
        <div className="flex justify-between items-center">
          <span className={`text-sm ${charsLeft < 0 ? 'text-red-500' : 'text-x-secondary-text'}`}>
            {charsLeft} / {MAX_TWEET_LENGTH}
          </span>
          <button
            type="submit"
            disabled={isLoading || !content.trim() || content.length > MAX_TWEET_LENGTH}
            className="bg-x-accent text-white rounded-full px-6 py-2 text-sm font-bold hover:bg-x-accent-hover disabled:opacity-50 transition-colors"
          >
            {isLoading ? 'Publication...' : 'Poster'}
          </button>
        </div>
        {error && <p className="text-sm text-red-500 mt-2">{error}</p>}
      </form>
    </div>
  );
}
