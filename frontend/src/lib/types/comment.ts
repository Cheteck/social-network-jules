import { PostAuthor } from './post'; // Réutiliser PostAuthor pour le commentateur

export interface Comment {
  id: number | string;
  body: string; // Le contrôleur utilise 'content' pour la validation, mais le modèle Comment a souvent 'body'. À vérifier.
  commenter: PostAuthor; // Auteur du commentaire
  created_at: string; // Date ISO
  updated_at?: string; // Date ISO
  children?: Comment[]; // Pour les réponses imbriquées (si l'API les fournit ainsi)
  // D'autres champs possibles : likes_count, etc.
}
