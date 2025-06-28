'use client'; // Assurez-vous que c'est bien un Client Component

import { Post } from '@/lib/types/post';
import Image from 'next/image';
import React, { useState, useEffect } from 'react';
import { useAuth } from '@/lib/contexts/AuthContext';
import apiClient from '@/lib/api';
import { useRouter } from 'next/navigation';
import Link from 'next/link'; // Pour les liens de hashtag

interface PostCardProps {
  post: Post;
  onLikeToggle?: () => void;
}

const formatDate = (dateString: string) => {
  const date = new Date(dateString);
  return date.toLocaleDateString('fr-FR', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  });
};

export default function PostCard({ post: initialPost, onLikeToggle }: PostCardProps) {
  const { user } = useAuth();
  const router = useRouter();

  const [isLiked, setIsLiked] = useState(initialPost.is_liked_by_current_user || false);
  const [likesCount, setLikesCount] = useState(initialPost.likes_count || 0);
  const [post, setPost] = useState<Post>(initialPost);

  useEffect(() => {
    setPost(initialPost);
    setIsLiked(initialPost.is_liked_by_current_user || false);
    setLikesCount(initialPost.likes_count || 0);
  }, [initialPost]);

  const handleLikeToggle = async () => {
    if (!user) {
      router.push('/auth_group/login');
      return;
    }

    const originalIsLiked = isLiked;
    const originalLikesCount = likesCount;

    setIsLiked(!isLiked);
    setLikesCount(isLiked ? likesCount - 1 : likesCount + 1);

    try {
      if (isLiked) { // Si c'était liké, on unlike
        await apiClient.delete(`/api/v1/likeable/posts/${post.id}/unlike`); // Utilisation de l'endpoint polymorphique
      } else { // Sinon, on like
        await apiClient.post(`/api/v1/likeable/posts/${post.id}/like`); // Utilisation de l'endpoint polymorphique
      }
      if (onLikeToggle) onLikeToggle();
    } catch (error) {
      console.error("Failed to toggle like:", error);
      setIsLiked(originalIsLiked);
      setLikesCount(originalLikesCount);
    }
  };

  const renderContentWithHashtags = (content: string) => {
    const hashtagRegex = /#([a-zA-Z0-9_]+)/g;
    const parts = content.split(hashtagRegex);

    return parts.map((part, index) => {
      if (index % 2 === 1) {
        const tagName = part;
        return (
          <Link key={index} href={`/main_group/tags/${tagName.toLowerCase()}`} className="text-x-accent hover:underline">
            #{tagName}
          </Link>
        );
      }
      return part;
    });
  };

  return (
    <article className="border border-x-border bg-x-card-bg p-4 rounded-lg shadow-sm hover:bg-opacity-75 hover:bg-x-card-bg transition-colors duration-150 mb-4">
      <div className="flex items-start space-x-3">
        <Image
          src={post.author.avatar_url || '/default-avatar.png'}
          alt={`${post.author.name}'s avatar`}
          width={48}
          height={48}
          className="rounded-full"
        />
        <div className="flex-1">
          <div className="flex items-center space-x-1 text-sm">
            <span className="font-semibold text-x-primary-text">{post.author.name}</span>
            <span className="text-x-secondary-text">@{post.author.username}</span>
            <span className="text-x-secondary-text">·</span>
            <time dateTime={post.created_at} className="text-x-secondary-text hover:underline cursor-pointer">
              {formatDate(post.created_at)}
            </time>
          </div>
          <div className="mt-1 text-x-primary-text whitespace-pre-wrap">
            {renderContentWithHashtags(post.content)}
          </div>
        </div>
      </div>
      <div className="mt-3 flex justify-start space-x-6 pl-12">
        <button className="text-x-secondary-text hover:text-blue-500 flex items-center space-x-1 text-xs">
          <span>💬</span> <span>{post.comments_count ?? 0}</span>
        </button>
        <button className="text-x-secondary-text hover:text-green-500 flex items-center space-x-1 text-xs">
          <span>🔁</span> <span>{post.retweets_count ?? 0}</span>
        </button>
        <button
          onClick={handleLikeToggle}
          className={`flex items-center space-x-1 text-xs ${isLiked ? 'text-red-500' : 'text-x-secondary-text hover:text-red-500'}`}
        >
          <span>{isLiked ? '❤️' : '🤍'}</span> <span>{likesCount}</span>
        </button>
      </div>
    </article>
  );
}
