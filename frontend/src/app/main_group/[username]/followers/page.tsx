'use client';

import React, { useEffect, useState } from 'react';
import { useParams, notFound, useRouter } from 'next/navigation';
import apiClient from '@/lib/api';
import { UserProfile } from '@/lib/types/user'; // Pour le type des utilisateurs listés
import UserCard from '@/components/profile/UserCard';
import Link from 'next/link';

export default function FollowersPage() {
  const params = useParams();
  const router = useRouter();
  const username = params.username as string;

  const [followers, setFollowers] = useState<Partial<UserProfile>[]>([]);
  const [profileUser, setProfileUser] = useState<UserProfile | null>(null); // Pour afficher le nom de l'utilisateur concerné
  const [isLoading, setIsLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    if (username) {
      const fetchData = async () => {
        setIsLoading(true);
        setError(null);
        try {
          // 1. Obtenir le profil de l'utilisateur pour avoir son ID et son nom
          // (On suppose que l'API /api/users/{username}/profile retourne l'ID)
          let userIdToFetchFollowers: string | number | null = null;
          try {
            const profileResponse = await apiClient.get(`/api/users/${username}/profile`);
            const userProfileData = profileResponse.data.data || profileResponse.data;
            setProfileUser(userProfileData);
            userIdToFetchFollowers = userProfileData.id;
          } catch (profileError: unknown) {
            let message = `Impossible de trouver le profil pour @${username}.`;
            if (profileError instanceof Error) {
              // Check for Axios-like error structure more safely
              if (typeof profileError === 'object' && profileError !== null && 'response' in profileError) {
                const response = (profileError as { response?: { data?: { message?: string } } }).response;
                if (response?.data?.message) {
                  message = `${message} Détail: ${response.data.message}`;
                } else {
                  message = `${message} Erreur: ${profileError.message}`;
                }
              } else {
                message = `${message} Erreur: ${profileError.message}`;
              }
            }
            console.error(`Failed to fetch profile for ${username}:`, profileError);
            setError(message);
            setIsLoading(false);
            return;
          }

          if (!userIdToFetchFollowers) {
            setError(`ID utilisateur non trouvé pour @${username}.`);
            setIsLoading(false);
            return;
          }

          // 2. Obtenir la liste des followers
          const followersResponse = await apiClient.get(`/api/users/${userIdToFetchFollowers}/followers`);
          setFollowers(followersResponse.data || []); // L'API retourne un tableau d'utilisateurs

        } catch (err: unknown) {
          let message = `Impossible de charger la liste des abonnés pour @${username}.`;
          if (err instanceof Error) {
            // Check for Axios-like error structure more safely
            if (typeof err === 'object' && err !== null && 'response' in err) {
              const response = (err as { response?: { data?: { message?: string } } }).response;
              if (response?.data?.message) {
                message = `${message} Détail: ${response.data.message}`;
              } else {
                message = `${message} Erreur: ${err.message}`;
              }
            } else {
              message = `${message} Erreur: ${err.message}`;
            }
          }
          console.error(`Failed to fetch followers for @${username}:`, err);
          setError(message);
          setFollowers([]);
        } finally {
          setIsLoading(false);
        }
      };
      fetchData();
    } else {
      notFound();
    }
  }, [username]);

  if (isLoading) {
    return (
      <div className="flex justify-center items-center min-h-screen text-x-primary-text">
        Chargement des abonnés...
      </div>
    );
  }

  return (
    <main className="w-full max-w-xl mx-auto">
      <div className="p-4 border-b border-x-border">
        <button onClick={() => router.back()} className="text-sm text-x-accent hover:underline mb-2">
          &larr; Retour au profil
        </button>
        {profileUser && (
          <h1 className="text-xl font-bold text-x-primary-text">
            Abonnés de <Link href={`/main_group/${profileUser.username}`} className="text-x-accent hover:underline">@{profileUser.username}</Link>
          </h1>
        )}
         {!profileUser && !error && (
           <h1 className="text-xl font-bold text-x-primary-text">Abonnés</h1>
         )}
      </div>

      {error && <div className="p-4 text-red-500 text-center">{error}</div>}

      {!error && followers.length === 0 && !isLoading && (
        <p className="p-4 text-x-secondary-text text-center">@{username} n&apos;a aucun abonné pour le moment.</p>
      )}

      <div className="divide-y divide-x-border">
        {followers.map(user => (
          <UserCard key={user.id} user={user} />
        ))}
      </div>
    </main>
  );
}
