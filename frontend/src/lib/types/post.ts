export interface PostAuthor {
  name: string;
  username: string; // À VÉRIFIER: S'assurer que l'API retourne bien 'username' pour l'auteur
  avatar_url: string; // À VÉRIFIER: Confirmer le nom du champ pour l'URL de l'avatar (ex: profile_photo_url)
}

export interface Post {
  id: string | number; // L'API pourrait retourner un nombre ou une chaîne
  author: PostAuthor; // L'API pourrait retourner un objet 'user' qu'il faudra mapper en 'author'
  content: string; // Devrait correspondre à 'body' du backend
  created_at: string; // Date ISO string, sera formatée pour l'affichage
  likes_count?: number;    // À VÉRIFIER: S'assurer que l'API inclut ces compteurs
  retweets_count?: number; // À VÉRIFIER: Moins probable sans système de retweet dédié
  comments_count?: number; // À VÉRIFIER: S'assurer que l'API inclut ces compteurs
  is_liked_by_current_user?: boolean; // À VÉRIFIER: Si l'API peut fournir cette info (via `with('likers')` et une transformation)
  // Ajoutez d'autres champs si l'API les fournit (ex: media, source, etc.)
}
