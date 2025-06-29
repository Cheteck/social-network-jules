"use client";

import React, { useEffect, useState } from 'react';
import PostCard from './PostCard';
import { fetchPosts } from '@/lib/api/posts';

export default function Timeline() {
  const [posts, setPosts] = useState<any[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    fetchPosts()
      .then(data => setPosts(data.data || []))
      .catch(() => setError('Erreur de chargement'))
      .finally(() => setLoading(false));
  }, []);

  if (loading) return <div className="text-center py-8 text-gray-400">Chargement…</div>;
  if (error) return <div className="text-center py-8 text-red-500">{error}</div>;

  return (
    <div className="space-y-4">
      {posts.length === 0 && <div className="text-center text-gray-400">Aucun post.</div>}
      {posts.map((post) => (
        <PostCard
          key={post.id}
          avatarUrl={post.author?.avatar_url}
          name={post.author?.name || 'Utilisateur'}
          username={post.author?.username || ''}
          date={post.created_at}
          content={post.content}
        />
      ))}
    </div>
  );
}
