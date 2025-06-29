"use client";

import React, { useState } from 'react';
import { createComment } from '@/lib/api/comments';

interface NewCommentFormProps {
  postId: number;
  onCommented?: () => void;
}

export default function NewCommentForm({ postId, onCommented }: NewCommentFormProps) {
  const [content, setContent] = useState('');
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);

  async function handleSubmit(e: React.FormEvent) {
    e.preventDefault();
    setLoading(true);
    setError(null);
    try {
      await createComment(postId, content);
      setContent('');
      onCommented?.();
    } catch {
      setError('Erreur lors de la publication');
    } finally {
      setLoading(false);
    }
  }

  return (
    <form onSubmit={handleSubmit} className="flex flex-col gap-2 mb-2">
      <textarea
        className="w-full resize-none border-none bg-gray-100 dark:bg-gray-800 rounded p-2 text-sm focus:ring-0 placeholder-gray-400 dark:placeholder-gray-500"
        placeholder="Ajouter un commentaire…"
        value={content}
        onChange={e => setContent(e.target.value)}
        maxLength={280}
        disabled={loading}
      />
      {error && <div className="text-red-500 text-sm">{error}</div>}
      <div className="flex justify-end">
        <button
          type="submit"
          className="bg-blue-600 hover:bg-blue-700 text-white font-bold py-1 px-4 rounded-full text-sm disabled:opacity-50 transition"
          disabled={!content.trim() || loading}
        >
          {loading ? 'Publication…' : 'Commenter'}
        </button>
      </div>
    </form>
  );
}
