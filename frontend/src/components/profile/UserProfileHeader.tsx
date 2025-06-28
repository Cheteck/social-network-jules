'use client';

import { UserProfile } from '@/lib/types/user';
import Image from 'next/image';
// import { useAuth } from '@/lib/contexts/AuthContext'; // Pourrait être utilisé pour le bouton Suivre/Modifier profil
// import { useRouter } from 'next/navigation'; // Pour la navigation

interface UserProfileHeaderProps {
  userProfile: UserProfile;
}

export default function UserProfileHeader({ userProfile }: UserProfileHeaderProps) {
  // const { user: currentUser } = useAuth(); // Pour vérifier si c'est le profil de l'utilisateur connecté
  // const router = useRouter();
  // const isCurrentUserProfile = currentUser?.username === userProfile.username;

  // const handleFollowToggle = async () => {
  //   // TODO: Implémenter la logique de suivi/non-suivi
  //   console.log('Toggle follow pour', userProfile.username);
  // };

  // const handleEditProfile = () => {
  //   router.push('/settings/profile'); // Ou une page d'édition de profil dédiée
  // };

  return (
    <div className="border-b border-x-border pb-6">
      <div className="relative h-48 bg-x-border rounded-t-lg"> {/* Ou bg-x-card-bg */}
        {/* Image de bannière - à ajouter si le modèle UserProfile le supporte */}
        {/* <Image src={userProfile.banner_url || '/default-banner.jpg'} layout="fill" objectFit="cover" alt={`${userProfile.name}'s banner`} className="rounded-t-lg" /> */}
      </div>
      <div className="px-4 -mt-16">
        <div className="flex items-end space-x-5">
          <Image
            src={userProfile.avatar_url || '/default-avatar.png'}
            alt={`${userProfile.name}'s avatar`}
            width={136} // Taille plus grande pour l'avatar de profil
            height={136}
            className="rounded-full border-4 border-x-bg bg-x-card-bg"
          />
          <div className="flex-grow flex justify-end items-center pb-4">
            {/* {isCurrentUserProfile ? (
              <button
                onClick={handleEditProfile}
                className="px-4 py-2 text-sm font-semibold border border-x-border rounded-full hover:bg-x-border/50 text-x-primary-text transition-colors"
              >
                Modifier le profil
              </button>
            ) : (
              <button
                onClick={handleFollowToggle}
                className="px-4 py-2 text-sm font-semibold bg-x-primary-text text-x-bg rounded-full hover:bg-opacity-80 transition-colors"
              >
                { TODO: Vérifier si déjà suivi } Follow
              </button>
            )} */}
             <button // Bouton placeholder pour l'instant
                className="px-4 py-2 text-sm font-semibold border border-x-border rounded-full hover:bg-x-border/50 text-x-primary-text transition-colors"
              >
                Options
              </button>
          </div>
        </div>

        <div className="mt-3">
          <h1 className="text-2xl font-bold text-x-primary-text">{userProfile.name}</h1>
          <p className="text-sm text-x-secondary-text">@{userProfile.username}</p>
        </div>

        {userProfile.bio && (
          <p className="mt-3 text-base text-x-primary-text whitespace-pre-wrap">
            {userProfile.bio}
          </p>
        )}

        <div className="mt-3 flex items-center space-x-2 text-sm text-x-secondary-text">
          {/* TODO: Ajouter l'icône de localisation et date d'inscription si disponible */}
          {/* <span className="flex items-center"><CalendarDaysIcon className="w-4 h-4 mr-1" /> Inscrit en {new Date(userProfile.created_at).toLocaleDateString('fr-FR', { month: 'long', year: 'numeric' })}</span> */}
          <span>Inscrit le {new Date(userProfile.created_at).toLocaleDateString('fr-FR')}</span>
        </div>

        <div className="mt-3 flex space-x-4 text-sm">
          <a href={`/${userProfile.username}/following`} className="hover:underline">
            <span className="font-semibold text-x-primary-text">{userProfile.following_count}</span>
            <span className="text-x-secondary-text ml-1">Abonnements</span>
          </a>
          <a href={`/${userProfile.username}/followers`} className="hover:underline">
            <span className="font-semibold text-x-primary-text">{userProfile.followers_count}</span>
            <span className="text-x-secondary-text ml-1">Abonnés</span>
          </a>
        </div>
      </div>
    </div>
  );
}
