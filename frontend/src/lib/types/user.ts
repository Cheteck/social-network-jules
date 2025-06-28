import { Post } from './post'; // Chemin et type mis à jour

export interface UserProfile {
  id: number | string;
  name: string; // À VÉRIFIER: Nom du champ dans l'API
  username: string; // À VÉRIFIER: Nom du champ dans l'API
  avatar_url: string; // À VÉRIFIER: Nom du champ et source (ex: media-uploader)
  bio?: string; // À VÉRIFIER: Nom du champ dans l'API
  followers_count: number; // À VÉRIFIER: S'assurer que l'API fournit ce compteur
  following_count: number; // À VÉRIFIER: S'assurer que l'API fournit ce compteur
  posts_count: number; // RENOMMÉ de tweets_count. À VÉRIFIER: S'assurer que l'API fournit ce compteur
  created_at: string; // À VÉRIFIER: Format de la date et nom du champ
  // posts?: Post[]; // Optionnel: si l'API retourne les posts avec le profil
}
