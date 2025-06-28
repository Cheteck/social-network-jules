export interface TweetAuthor {
  name: string;
  username: string;
  avatar_url: string; // Ou un type plus spécifique si vous utilisez une librairie d'images
}

export interface Tweet {
  id: string | number; // L'API pourrait retourner un nombre ou une chaîne
  author: TweetAuthor;
  content: string;
  created_at: string; // Date ISO string, sera formatée pour l'affichage
  likes_count?: number;
  retweets_count?: number;
  comments_count?: number;
  // Ajoutez d'autres champs si l'API les fournit (ex: media, source, etc.)
}
