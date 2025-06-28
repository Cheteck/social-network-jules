import { Post } from '@/lib/types/post'; // Mise à jour du chemin et du type
import Image from 'next/image';
import React, { useState, useEffect } from 'react'; // Ajout de useState, useEffect
import { useAuth } from '@/lib/contexts/AuthContext'; // Pour vérifier si l'utilisateur est connecté
import apiClient from '@/lib/api'; // Pour les appels API
import { useRouter } from 'next/navigation'; // Pour la redirection si non connecté

interface PostCardProps { // Renommage de l'interface
  post: Post; // Renommage de la prop et utilisation du type Post
  onLikeToggle?: () => void; // Optionnel: Callback pour rafraîchir la liste des posts si nécessaire
}

// Fonction utilitaire pour formater la date (peut être déplacée dans lib/utils.ts)
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

  // État local pour la mise à jour optimiste des likes
  // Initialiser avec les données du post, si l'API fournit 'is_liked_by_current_user'
  const [isLiked, setIsLiked] = useState(initialPost.is_liked_by_current_user || false);
  const [likesCount, setLikesCount] = useState(initialPost.likes_count || 0);
  const [post, setPost] = useState<Post>(initialPost); // Pour gérer le post complet si l'API le retourne après like/unlike

  useEffect(() => {
    setPost(initialPost);
    setIsLiked(initialPost.is_liked_by_current_user || false);
    setLikesCount(initialPost.likes_count || 0);
  }, [initialPost]);


  const handleLikeToggle = async () => {
    if (!user) {
      router.push('/auth_group/login'); // Rediriger vers login si pas connecté
      return;
    }

    // Mise à jour optimiste
    const originalIsLiked = isLiked;
    const originalLikesCount = likesCount;

    setIsLiked(!isLiked);
    setLikesCount(isLiked ? likesCount - 1 : likesCount + 1);

    try {
      if (isLiked) {
        await apiClient.delete(`/api/posts/${post.id}/like`);
      } else {
        await apiClient.post(`/api/posts/${post.id}/like`);
      }
      // Optionnel: si l'API retourne le post mis à jour, on peut mettre à jour l'état `post`
      // ou si un callback onLikeToggle est fourni pour rafraîchir la liste parente
      if (onLikeToggle) onLikeToggle();
    } catch (error) {
      console.error("Failed to toggle like:", error);
      // Annuler la mise à jour optimiste en cas d'erreur
      setIsLiked(originalIsLiked);
      setLikesCount(originalLikesCount);
      // TODO: Afficher une notification d'erreur à l'utilisateur
    }
  };

  return (
    <article className="border border-x-border bg-x-card-bg p-4 rounded-lg shadow-sm hover:bg-opacity-75 hover:bg-x-card-bg transition-colors duration-150 mb-4">
      <div className="flex items-start space-x-3">
        <Image
          src={post.author.avatar_url || '/default-avatar.png'} // Utilisation de post.author
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
            {post.content}
          </div>
          {/* TODO: Afficher les médias si présents */}
        </div>
      </div>
      <div className="mt-3 flex justify-start space-x-6 pl-12"> {/* Aligné avec le contenu du tweet */}
        <button className="text-x-secondary-text hover:text-blue-500 flex items-center space-x-1 text-xs">
          <span>💬</span> <span>{post.comments_count ?? 0}</span>
        </button>
        <button className="text-x-secondary-text hover:text-green-500 flex items-center space-x-1 text-xs">
          <span>🔁</span> <span>{post.retweets_count ?? 0}</span>
        </button>
        <button className="text-x-secondary-text hover:text-red-500 flex items-center space-x-1 text-xs">
          <span>❤️</span> <span>{post.likes_count ?? 0}</span>
        </button>
        {/* TODO: Bouton Partager/Plus d'options */}
      </div>
    </article>
  );
}
