'use client';

import { Comment } from '@/lib/types/comment';
import { PostAuthor } from '@/lib/types/post'; // Réutiliser pour l'auteur du commentaire
import Image from 'next/image';
import React from 'react';

interface CommentCardProps {
  comment: Comment;
}

// Fonction utilitaire pour formater la date (peut être partagée depuis un fichier utils)
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

export default function CommentCard({ comment }: CommentCardProps) {
  // S'assurer que 'commenter' existe et a les champs attendus
  const author: PostAuthor = comment.commenter || {
    name: 'Utilisateur inconnu',
    username: 'unknown',
    avatar_url: '/default-avatar.png'
  };

  return (
    <article className="flex space-x-3 border-b border-x-border py-4 last:border-b-0">
      <div>
        <Image
          src={author.avatar_url || '/default-avatar.png'}
          alt={`${author.name}'s avatar`}
          width={40} // Taille légèrement plus petite pour les commentaires
          height={40}
          className="rounded-full"
        />
      </div>
      <div className="flex-1">
        <div className="flex items-center space-x-1 text-sm">
          <span className="font-semibold text-x-primary-text">{author.name}</span>
          <span className="text-x-secondary-text">@{author.username}</span>
          <span className="text-x-secondary-text">·</span>
          <time dateTime={comment.created_at} className="text-x-secondary-text hover:underline cursor-pointer">
            {formatDate(comment.created_at)}
          </time>
        </div>
        <div className="mt-1 text-x-primary-text whitespace-pre-wrap">
          {comment.body}
        </div>
        {/* TODO: Actions sur le commentaire (Répondre, Liker, etc.) */}
        {/* <div className="mt-2 flex space-x-4 text-x-secondary-text text-xs">
          <button className="hover:text-x-accent">Répondre</button>
          <button className="hover:text-red-500">Liker</button>
        </div> */}
      </div>
    </article>
  );
}
