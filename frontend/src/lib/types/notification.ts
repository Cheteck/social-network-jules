import { PostAuthor } from './post'; // Pour l'acteur d'une notification

// Structure de base pour le champ 'data' d'une notification.
// Chaque type de notification aura une structure de 'data' spécifique.
export interface BaseNotificationData {
  message: string; // Message principal de la notification
  // Peut inclure des liens ou des IDs pour naviguer vers le contenu pertinent
  link?: string;
}

// Exemple pour une notification de nouveau follower
export interface NewFollowerNotificationData extends BaseNotificationData {
  follower_id: string | number;
  follower_name: string;
  // avatar_url?: string; // Peut être récupéré via un autre appel ou inclus
}

// Exemple pour une notification de nouveau like sur un post
export interface NewLikeNotificationData extends BaseNotificationData {
  liker_id: string | number;
  liker_name: string;
  post_id: string | number;
  post_summary?: string; // Un extrait du post pour le contexte
}

// Exemple pour un nouveau commentaire
export interface NewCommentNotificationData extends BaseNotificationData {
  commenter_id: string | number;
  commenter_name: string;
  post_id: string | number;
  comment_id: string | number;
  comment_excerpt?: string;
}

// Union de tous les types de données de notification possibles
export type NotificationData =
  | BaseNotificationData
  | NewFollowerNotificationData
  | NewLikeNotificationData
  | NewCommentNotificationData;
  // Ajoutez d'autres types de données de notification ici

export interface Notification {
  id: string; // UUID
  type: string; // Nom de la classe de la notification (ex: "App\\Notifications\\NewFollower")
  notifiable_type: string;
  notifiable_id: number | string;
  data: NotificationData; // Sera un des types définis ci-dessus
  read_at: string | null; // Date ISO si lue, sinon null
  created_at: string; // Date ISO
  updated_at: string; // Date ISO

  // Propriétés calculées côté client si besoin
  parsedMessage?: string;
  actor?: PostAuthor; // L'utilisateur qui a déclenché la notification
  targetLink?: string; // Lien vers le contenu pertinent
}
