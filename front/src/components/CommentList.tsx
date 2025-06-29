"use client";

import React, { useEffect, useState } from 'react';
import { fetchComments } from '@/lib/api/comments';

interface CommentListProps {
  postId: number;
}

export default function CommentList({ postId }: CommentListProps) {
  const [comments, setComments] = useState<any[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    fetchComments(postId)
      .then(data => setComments(data.data || []))
      .catch(() => setError('Erreur de chargement'))
      .finally(() => setLoading(false));
  }, [postId]);

  if (loading) return <div className="text-center py-4 text-gray-400">Chargement…</div>;
  if (error) return <div className="text-center py-4 text-red-500">{error}</div>;
  if (comments.length === 0) return <div className="text-center text-gray-400">Aucun commentaire.</div>;

  return (
    <div className="space-y-2">
      {comments.map(comment => (
        <div key={comment.id} className="bg-gray-50 dark:bg-gray-900 rounded p-3">
          <div className="font-semibold">{comment.commenter?.name || 'Utilisateur'}</div>
          <div className="text-sm text-gray-500">{comment.body}</div>
        </div>
      ))}
    </div>
  );
}
