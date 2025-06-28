import { Tweet } from './tweet'; // Si les tweets sont inclus directement dans le profil

export interface UserProfile {
  id: number | string;
  name: string;
  username: string;
  avatar_url: string;
  bio?: string;
  followers_count: number;
  following_count: number;
  tweets_count: number; // Ou calculer à partir de la liste des tweets
  created_at: string; // Date d'inscription
  // tweets?: Tweet[]; // Optionnel: si l'API retourne les tweets avec le profil
}
