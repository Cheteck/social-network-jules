'use client';

import React from 'react';
import Link from 'next/link';
import { Notification, NewFollowerNotificationData, NewLikeNotificationData, NewCommentNotificationData } from '@/lib/types/notification';
import { PostAuthor } from '@/lib/types/post'; // Pour l'avatar de l'acteur
import Image from 'next/image';
import apiClient from '@/lib/api';

interface NotificationCardProps {
  notification: Notification;
  onNotificationRead?: (notificationId: string) => void; // Callback pour marquer comme lue
}

// Fonction pour parser le message et l'acteur/lien
const parseNotificationData = (notification: Notification): { message: string; actor?: Partial<PostAuthor>; link?: string } => {
  const type = notification.type.split('\\').pop(); // Obtenir le nom de la classe de notification
  const data = notification.data;

  switch (type) {
    case 'NewFollower': // Supposons que c'est le nom de la classe Notification pour cette action
      const followerData = data as NewFollowerNotificationData;
      return {
        message: followerData.message || `${followerData.follower_name} a commencé à vous suivre.`,
        actor: { name: followerData.follower_name, username: '', avatar_url: '' }, // Avatar à gérer
        link: `/main_group/${followerData.follower_name}`, // Supposant que follower_name est unique ou que l'API UserProfile peut résoudre par nom
      };
    case 'NewLikeOnPost': // Supposons
      const likeData = data as NewLikeNotificationData;
      return {
        message: likeData.message || `${likeData.liker_name} a aimé votre post : "${likeData.post_summary}"`,
        actor: { name: likeData.liker_name, username: '', avatar_url: '' },
        link: `/main_group/posts/${likeData.post_id}`,
      };
    case 'NewCommentOnPost': // Supposons
      const commentData = data as NewCommentNotificationData;
      return {
        message: commentData.message || `${commentData.commenter_name} a commenté votre post : "${commentData.comment_excerpt}"`,
        actor: { name: commentData.commenter_name, username: '', avatar_url: '' },
        link: `/main_group/posts/${commentData.post_id}#comment-${commentData.comment_id}`,
      };
    default:
      return { message: typeof data.message === 'string' ? data.message : "Nouvelle notification." };
  }
};


export default function NotificationCard({ notification, onNotificationRead }: NotificationCardProps) {
  const { message, actor, link } = parseNotificationData(notification);

  const handleMarkAsRead = async (e: React.MouseEvent) => {
    e.preventDefault(); // Empêcher la navigation si c'est un lien
    e.stopPropagation();
    if (!notification.read_at && onNotificationRead) {
        try {
            await apiClient.patch(`/api/v1/notifications/${notification.id}/read`);
            onNotificationRead(notification.id); // Mettre à jour l'état parent
        } catch (error) {
            console.error("Failed to mark notification as read", error);
            // Gérer l'erreur (ex: afficher un toast)
        }
    }
  };

  const content = (
    <div className={`p-4 flex items-start space-x-3 ${!notification.read_at ? 'bg-x-card-bg' : 'bg-x-bg hover:bg-x-border/10'}`}>
      {actor?.avatar_url || actor?.name ? ( // Simple placeholder pour l'avatar
        <Image
            src={actor.avatar_url || '/default-avatar.png'}
            alt={actor.name || 'avatar'}
            width={40} height={40}
            className="rounded-full"
        />
      ) : (
        <div className="w-10 h-10 bg-x-secondary-text rounded-full flex items-center justify-center text-x-bg font-semibold">
          !
        </div>
      )}
      <div className="flex-1">
        <p className="text-sm text-x-primary-text" dangerouslySetInnerHTML={{ __html: message.replace(/\n/g, '<br />') }} />
        <p className="text-xs text-x-secondary-text mt-1">
          {new Date(notification.created_at).toLocaleString('fr-FR', { dateStyle: 'short', timeStyle: 'short' })}
        </p>
      </div>
      {!notification.read_at && (
        <button
            onClick={handleMarkAsRead}
            title="Marquer comme lu"
            className="ml-auto flex-shrink-0 w-3 h-3 bg-x-accent rounded-full hover:opacity-75"
        />
      )}
    </div>
  );

  if (link) {
    return (
      <Link href={link} className="block" onClick={handleMarkAsRead}>
        {content}
      </Link>
    );
  }

  return <div onClick={handleMarkAsRead} className="cursor-pointer">{content}</div>;
}
