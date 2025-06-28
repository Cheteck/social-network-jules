import { Tweet } from '@/lib/types/tweet'; // Ajustez le chemin si types/index.ts est utilisé
import Image from 'next/image';

interface TweetCardProps {
  tweet: Tweet;
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

export default function TweetCard({ tweet }: TweetCardProps) {
  return (
    <article className="border border-x-border bg-x-card-bg p-4 rounded-lg shadow-sm hover:bg-opacity-75 hover:bg-x-card-bg transition-colors duration-150 mb-4">
      <div className="flex items-start space-x-3">
        <Image
          src={tweet.author.avatar_url || '/default-avatar.png'} // Placeholder si pas d'avatar
          alt={`${tweet.author.name}'s avatar`}
          width={48}
          height={48}
          className="rounded-full"
        />
        <div className="flex-1">
          <div className="flex items-center space-x-1 text-sm">
            <span className="font-semibold text-x-primary-text">{tweet.author.name}</span>
            <span className="text-x-secondary-text">@{tweet.author.username}</span>
            <span className="text-x-secondary-text">·</span>
            <time dateTime={tweet.created_at} className="text-x-secondary-text hover:underline cursor-pointer">
              {formatDate(tweet.created_at)}
            </time>
          </div>
          <div className="mt-1 text-x-primary-text whitespace-pre-wrap">
            {tweet.content}
          </div>
          {/* TODO: Afficher les médias si présents */}
        </div>
      </div>
      <div className="mt-3 flex justify-start space-x-6 pl-12"> {/* Aligné avec le contenu du tweet */}
        <button className="text-x-secondary-text hover:text-blue-500 flex items-center space-x-1 text-xs">
          <span>💬</span> <span>{tweet.comments_count ?? 0}</span>
        </button>
        <button className="text-x-secondary-text hover:text-green-500 flex items-center space-x-1 text-xs">
          <span>🔁</span> <span>{tweet.retweets_count ?? 0}</span>
        </button>
        <button className="text-x-secondary-text hover:text-red-500 flex items-center space-x-1 text-xs">
          <span>❤️</span> <span>{tweet.likes_count ?? 0}</span>
        </button>
        {/* TODO: Bouton Partager/Plus d'options */}
      </div>
    </article>
  );
}
