'use client';

import React, { useEffect, useState } from 'react';
import { useParams } from 'next/navigation';
import TweetCard from '@/components/feed/TweetCard';
import UserProfileHeader from '@/components/profile/UserProfileHeader'; // À créer
import { UserProfile } from '@/lib/types/user';
import { Tweet, TweetAuthor } from '@/lib/types/tweet'; // Pour les tweets mockés

// Données de simulation
const mockUserProfiles: Record<string, UserProfile> = {
  jverne: {
    id: 'user1',
    name: 'Jules Verne',
    username: 'jverne',
    avatar_url: 'https://via.placeholder.com/150/007bff/ffffff?Text=JV',
    bio: 'Écrivain français, auteur de romans d\'aventure et de science-fiction. Passionné par les voyages extraordinaires.',
    followers_count: 1250,
    following_count: 80,
    tweets_count: 35,
    created_at: new Date('1828-02-08').toISOString(),
  },
  mcurie: {
    id: 'user2',
    name: 'Marie Curie',
    username: 'mcurie',
    avatar_url: 'https://via.placeholder.com/150/28a745/ffffff?Text=MC',
    bio: 'Physicienne et chimiste polonaise, naturalisée française. Pionnière dans l\'étude de la radioactivité. Double prix Nobel.',
    followers_count: 2500,
    following_count: 50,
    tweets_count: 15,
    created_at: new Date('1867-11-07').toISOString(),
  }
};

const mockUserTweets: Record<string, Tweet[]> = {
  jverne: [
    { id: 'jv_tweet1', author: mockUserProfiles.jverne as unknown as TweetAuthor, content: 'Le Nautilus est prêt pour une nouvelle expédition !', created_at: new Date().toISOString(), likes_count: 12, retweets_count: 3, comments_count: 2 },
    { id: 'jv_tweet2', author: mockUserProfiles.jverne as unknown as TweetAuthor, content: 'Pensées sur le voyage au centre de la Terre...', created_at: new Date(Date.now() - 1000 * 60 * 60 * 5).toISOString(), likes_count: 25, retweets_count: 5, comments_count: 4  },
  ],
  mcurie: [
    { id: 'mc_tweet1', author: mockUserProfiles.mcurie as unknown as TweetAuthor, content: 'Nouvelle expérience en cours. Les résultats préliminaires sont prometteurs.', created_at: new Date().toISOString(), likes_count: 45, retweets_count: 10, comments_count: 8 },
  ]
};


export default function UserProfilePage() {
  const params = useParams();
  const username = params.username as string;

  const [profile, setProfile] = useState<UserProfile | null>(null);
  const [tweets, setTweets] = useState<Tweet[]>([]);
  const [isLoading, setIsLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    if (username) {
      const fetchProfileData = async () => {
        setIsLoading(true);
        setError(null);
        // TODO: Remplacer par un véritable appel API: GET /api/users/{username}/profile
        // Et un autre pour les tweets de l'utilisateur: GET /api/v1/social/users/{userId}/posts (ou similaire)
        await new Promise(resolve => setTimeout(resolve, 700)); // Simule la latence

        const foundProfile = mockUserProfiles[username];
        const foundTweets = mockUserTweets[username] || [];

        if (foundProfile) {
          setProfile(foundProfile);
          setTweets(foundTweets);
        } else {
          setError('Profil non trouvé.');
        }
        setIsLoading(false);
      };
      fetchProfileData();
    }
  }, [username]);

  if (isLoading) {
    return <div className="text-center py-10 text-white">Chargement du profil...</div>;
  }

  if (error) {
    return <div className="text-center py-10 text-red-500">{error}</div>;
  }

  if (!profile) {
    return <div className="text-center py-10 text-white">Ce profil n'existe pas.</div>;
  }

  return (
    <main className="container mx-auto max-w-2xl py-8 px-4 text-white">
      <UserProfileHeader userProfile={profile} /> {/* À créer */}

      <div className="mt-8">
        <h2 className="text-xl font-semibold mb-4">Tweets</h2>
        {tweets.length > 0 ? (
          <div className="space-y-4">
            {tweets.map(tweet => <TweetCard key={tweet.id} tweet={tweet} />)}
          </div>
        ) : (
          <p>Cet utilisateur n'a encore rien posté.</p>
        )}
      </div>
    </main>
  );
}
