'use client';

import { UserProfile } from '@/lib/types/user';
import Image from 'next/image';
import { useAuth } from '@/lib/contexts/AuthContext'; // Utilisé pour le bouton Suivre/Modifier profil
import { useRouter } from 'next/navigation'; // Pour la navigation
import React, { useState, useEffect } from 'react'; // Ajout de useState, useEffect
import apiClient from '@/lib/api'; // Pour les appels API

interface UserProfileHeaderProps {
  userProfile: UserProfile;
}

export default function UserProfileHeader({ userProfile }: UserProfileHeaderProps) {
  const { user: currentUser, isLoading: authLoading } = useAuth();
  const router = useRouter();
  const isCurrentUserProfile = currentUser?.id === userProfile.id; // Comparer par ID

  const [isFollowing, setIsFollowing] = useState(false);
  const [isLoadingFollowStatus, setIsLoadingFollowStatus] = useState(true);
  // Utiliser le followers_count de userProfile et le mettre à jour localement pour l'optimisme
  const [localFollowersCount, setLocalFollowersCount] = useState(userProfile.followers_count);

  useEffect(() => {
    setLocalFollowersCount(userProfile.followers_count); // Mettre à jour si la prop change
  }, [userProfile.followers_count]);

  useEffect(() => {
    if (currentUser && !isCurrentUserProfile && userProfile.id) {
      setIsLoadingFollowStatus(true);
      apiClient.get(`/api/users/${userProfile.id}/is-following`)
        .then(response => {
          setIsFollowing(response.data.is_following);
        })
        .catch(error => console.error("Failed to fetch follow status:", error))
        .finally(() => setIsLoadingFollowStatus(false));
    } else if (!currentUser || isCurrentUserProfile) {
      setIsLoadingFollowStatus(false); // Pas besoin de vérifier si c'est son propre profil ou si non connecté
    }
  }, [currentUser, isCurrentUserProfile, userProfile.id]);

  const handleFollowToggle = async () => {
    if (!currentUser) {
      router.push('/auth_group/login');
      return;
    }
    if (isCurrentUserProfile) return;

    // Mise à jour optimiste
    const originalIsFollowing = isFollowing;
    const originalFollowersCount = localFollowersCount;

    setIsFollowing(!originalIsFollowing);
    setLocalFollowersCount(originalIsFollowing ? localFollowersCount - 1 : localFollowersCount + 1);

    try {
      // Utiliser l'endpoint toggle-follow pour simplicité
      await apiClient.post(`/api/users/${userProfile.id}/toggle-follow`);
      // Idéalement, l'API pourrait retourner le nouveau statut/compte pour re-synchroniser,
      // mais pour l'instant, la mise à jour optimiste suffit.
      // On pourrait re-fetch le statut is-following ou le profil pour s'assurer de la cohérence.
    } catch (error) {
      console.error("Failed to toggle follow:", error);
      // Annuler la mise à jour optimiste
      setIsFollowing(originalIsFollowing);
      setLocalFollowersCount(originalFollowersCount);
      // TODO: Afficher une erreur à l'utilisateur
    }
  };

  const handleEditProfile = () => {
    router.push('/main_group/settings'); // Adapter vers la page de paramètres de profil
  };

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
            {authLoading ? null : isCurrentUserProfile ? (
              <button
                onClick={handleEditProfile}
                className="px-4 py-2 text-sm font-semibold border border-x-border rounded-full hover:bg-x-border/50 text-x-primary-text transition-colors"
              >
                Modifier le profil
              </button>
            ) : currentUser && !isLoadingFollowStatus ? ( // Afficher seulement si currentUser est chargé et le statut de suivi aussi
              <button
                onClick={handleFollowToggle}
                className={`px-4 py-2 text-sm font-semibold rounded-full transition-colors
                  ${isFollowing
                    ? 'bg-transparent text-x-primary-text border border-x-border hover:bg-red-600/10 hover:border-red-500 hover:text-red-500'
                    : 'bg-x-primary-text text-x-bg hover:bg-opacity-80'
                  }`}
              >
                {isFollowing ? 'Abonné' : 'Suivre'}
              </button>
            ) : currentUser ? ( // Si currentUser est chargé mais pas encore le statut de suivi
              <div className="px-4 py-2 text-sm font-semibold border border-x-border rounded-full text-x-primary-text opacity-50">Chargement...</div>
            ) : null /* Ne rien afficher si pas d'utilisateur connecté et ce n'est pas son profil */ }
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
            <span className="font-semibold text-x-primary-text">{userProfile.following_count}</span> {/* Ce compteur pourrait aussi être mis à jour localement si on suit/unfollow */}
            <span className="text-x-secondary-text ml-1">Abonnements</span>
          </a>
          <a href={`/${userProfile.username}/followers`} className="hover:underline">
            <span className="font-semibold text-x-primary-text">{localFollowersCount}</span>
            <span className="text-x-secondary-text ml-1">Abonnés</span>
          </a>
        </div>
      </div>
    </div>
  );
}
