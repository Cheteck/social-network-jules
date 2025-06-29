"use client";

import React, { useState } from 'react';
import { createPost } from '@/lib/api/posts';

interface NewPostFormProps {
  onPostCreated?: () => void;
}

export default function NewPostForm({ onPostCreated }: NewPostFormProps) {
  const [content, setContent] = useState('');
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);

  async function handleSubmit(e: React.FormEvent) {
    e.preventDefault();
    setLoading(true);
    setError(null);
    try {
      await createPost(content);
      setContent('');
      onPostCreated?.();
    } catch {
      setError('Erreur lors de la publication');
    } finally {
      setLoading(false);
    }
  }

  return (
    <form onSubmit={handleSubmit} className="flex flex-col gap-2 bg-white dark:bg-gray-800 rounded-lg shadow p-4 mb-4">
      <textarea
        className="w-full resize-none border-none bg-transparent focus:ring-0 text-base min-h-[60px] placeholder-gray-400 dark:placeholder-gray-500"
        placeholder="Quoi de neuf ?"
        value={content}
        onChange={e => setContent(e.target.value)}
        maxLength={280}
        disabled={loading}
      />
      {error && <div className="text-red-500 text-sm">{error}</div>}
      <div className="flex justify-end">
        <button
          type="submit"
          className="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-6 rounded-full disabled:opacity-50 transition"
          disabled={!content.trim() || loading}
        >
          {loading ? 'Publication…' : 'Poster'}
        </button>
      </div>
    </form>
  );
}
