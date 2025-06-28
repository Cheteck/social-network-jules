'use client';

import Image from 'next/image';
import Link from 'next/link';
import { UserProfile } from '@/lib/types/user'; // Supposons que l'API retourne des objets compatibles UserProfile

interface UserCardProps {
  user: Partial<UserProfile>; // Partial car l'API followers/following pourrait ne pas retourner tout le profil
}

export default function UserCard({ user }: UserCardProps) {
  if (!user.username || !user.name) { // Vérification minimale
    return null;
  }
  return (
    <Link href={`/main_group/${user.username}`} className="block hover:bg-x-border/30">
      <div className="flex items-center space-x-3 p-4 border-b border-x-border last:border-b-0">
        <Image
          src={user.avatar_url || '/default-avatar.png'}
          alt={`${user.name}'s avatar`}
          width={48}
          height={48}
          className="rounded-full"
        />
        <div>
          <p className="font-semibold text-x-primary-text">{user.name}</p>
          <p className="text-sm text-x-secondary-text">@{user.username}</p>
          {user.bio && <p className="text-sm text-x-secondary-text mt-1 truncate">{user.bio}</p>}
        </div>
        {/* TODO: Ajouter un bouton Suivre/Ne plus suivre ici aussi ? */}
      </div>
    </Link>
  );
}
