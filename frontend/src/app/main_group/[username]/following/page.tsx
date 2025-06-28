'use client';

import React, { useEffect, useState } from 'react';
import { useParams, notFound, useRouter } from 'next/navigation';
import apiClient from '@/lib/api';
import { UserProfile } from '@/lib/types/user';
import UserCard from '@/components/profile/UserCard';
import Link from 'next/link';

export default function FollowingPage() {
  const params = useParams();
  const router = useRouter();
  const username = params.username as string;

  const [following, setFollowing] = useState<Partial<UserProfile>[]>([]);
  const [profileUser, setProfileUser] = useState<UserProfile | null>(null);
  const [isLoading, setIsLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    if (username) {
      const fetchData = async () => {
        setIsLoading(true);
        setError(null);
        try {
          let userIdToFetchFollowing: string | number | null = null;
          try {
            const profileResponse = await apiClient.get(`/api/users/${username}/profile`);
            const userProfileData = profileResponse.data.data || profileResponse.data;
            setProfileUser(userProfileData);
            userIdToFetchFollowing = userProfileData.id;
          } catch (profileError: any) {
            console.error(`Failed to fetch profile for ${username}:`, profileError);
            setError(`Impossible de trouver le profil pour @${username}.`);
            setIsLoading(false);
            return;
          }

          if (!userIdToFetchFollowing) {
            setError(`ID utilisateur non trouvé pour @${username}.`);
            setIsLoading(false);
            return;
          }

          const followingResponse = await apiClient.get(`/api/users/${userIdToFetchFollowing}/followings`);
          setFollowing(followingResponse.data || []);

        } catch (err: any) {
          console.error(`Failed to fetch followings for @${username}:`, err);
          setError(`Impossible de charger la liste des abonnements pour @${username}.`);
          setFollowing([]);
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
        Chargement des abonnements...
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
            Abonnements de <Link href={`/main_group/${profileUser.username}`} className="text-x-accent hover:underline">@{profileUser.username}</Link>
          </h1>
        )}
        {!profileUser && !error && (
           <h1 className="text-xl font-bold text-x-primary-text">Abonnements</h1>
         )}
      </div>

      {error && <div className="p-4 text-red-500 text-center">{error}</div>}

      {!error && following.length === 0 && !isLoading && (
        <p className="p-4 text-x-secondary-text text-center">@{username} ne suit aucun utilisateur pour le moment.</p>
      )}

      <div className="divide-y divide-x-border">
        {following.map(user => (
          <UserCard key={user.id} user={user} />
        ))}
      </div>
    </main>
  );
}
