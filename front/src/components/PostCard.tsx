"use client";

import React, { useState } from 'react';
import { Heart, MessageCircle, Repeat2, Share2, MoreHorizontal } from 'lucide-react';
import CommentList from './CommentList';
import NewCommentForm from './NewCommentForm';

interface PostCardProps {
  avatarUrl?: string;
  name: string;
  username: string;
  date: string;
  content: string;
  id: number;
}

export default function PostCard({ avatarUrl, name, username, date, content, id }: PostCardProps) {
  const [showComments, setShowComments] = useState(false);
  const [refresh, setRefresh] = useState(0);

  return (
    <div className="flex flex-col gap-2 p-4 bg-white dark:bg-gray-800 rounded-lg shadow hover:shadow-md transition">
      <div className="flex gap-3">
        <div>
          <img
            src={avatarUrl || '/file.svg'}
            alt={name}
            className="w-12 h-12 rounded-full object-cover bg-gray-200 dark:bg-gray-700"
          />
        </div>
        <div className="flex-1 min-w-0">
          <div className="flex items-center gap-2 text-sm">
            <span className="font-bold truncate">{name}</span>
            <span className="text-gray-500 truncate">@{username}</span>
            <span className="text-gray-400">·</span>
            <span className="text-gray-400">{date}</span>
            <span className="ml-auto">
              <button className="p-1 rounded-full hover:bg-gray-100 dark:hover:bg-gray-700">
                <MoreHorizontal className="w-5 h-5 text-gray-500" />
              </button>
            </span>
          </div>
          <div className="mt-1 text-base whitespace-pre-line break-words">{content}</div>
        </div>
      </div>
      <div className="flex items-center gap-6 mt-2 text-gray-500">
        <button className="flex items-center gap-1 hover:text-blue-500 transition" onClick={() => setShowComments(v => !v)}>
          <MessageCircle className="w-5 h-5" />
          <span className="text-xs">Commentaires</span>
        </button>
        <button className="flex items-center gap-1 hover:text-green-500 transition">
          <Repeat2 className="w-5 h-5" />
        </button>
        <button className="flex items-center gap-1 hover:text-pink-500 transition">
          <Heart className="w-5 h-5" />
        </button>
        <button className="flex items-center gap-1 hover:text-blue-500 transition">
          <Share2 className="w-5 h-5" />
        </button>
      </div>
      {showComments && (
        <div className="mt-2">
          <NewCommentForm postId={id} onCommented={() => setRefresh(r => r + 1)} />
          <CommentList key={refresh} postId={id} />
        </div>
      )}
    </div>
  );
}
