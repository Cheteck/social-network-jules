'use client';

import React, { useEffect, useState } from 'react';
import TweetCard from '@/components/feed/TweetCard';
import { Tweet, TweetAuthor } from '@/lib/types/tweet';
import { useAuth } from '@/lib/contexts/AuthContext'; // Pourrait être utilisé pour la redirection ou des messages spécifiques

// Données de simulation pour les tweets
const mockTweets: Tweet[] = [
  {
    id: '1',
    author: {
      name: 'Jules Verne',
      username: 'jverne',
      avatar_url: 'https://via.placeholder.com/48/007bff/ffffff?Text=JV',
    },
    content: 'Exploration des fonds marins à bord du Nautilus. Vingt mille lieues sous les mers, une aventure incroyable ! 🌊🐠 #aventure #sciencefiction',
    created_at: new Date(Date.now() - 1000 * 60 * 15).toISOString(), // Il y a 15 minutes
    likes_count: 150,
    retweets_count: 30,
    comments_count: 12,
  },
  {
    id: '2',
    author: {
      name: 'Marie Curie',
      username: 'mcurie',
      avatar_url: 'https://via.placeholder.com/48/28a745/ffffff?Text=MC',
    },
    content: 'Découvertes passionnantes sur la radioactivité. Le travail acharné porte ses fruits. La science est une lumière dans l\'obscurité. ✨🔬 #science #recherche',
    created_at: new Date(Date.now() - 1000 * 60 * 60 * 2).toISOString(), // Il y a 2 heures
    likes_count: 275,
    retweets_count: 55,
    comments_count: 25,
  },
  {
    id: '3',
    author: {
      name: 'Elon Musk',
      username: 'elon',
      avatar_url: 'https://via.placeholder.com/48/ffc107/000000?Text=EM',
    },
    content: 'To the moon! 🚀🌕 #SpaceX #Future',
    created_at: new Date(Date.now() - 1000 * 60 * 60 * 24).toISOString(), // Il y a 1 jour
    likes_count: 1200,
    retweets_count: 400,
    comments_count: 150,
  },
];

export default function HomePage() {
  const { user, isLoading: authLoading } = useAuth();
  const [tweets, setTweets] = useState<Tweet[]>([]);
  const [isLoadingTweets, setIsLoadingTweets] = useState(true);

  useEffect(() => {
    // Simuler la récupération des tweets
    const fetchTweets = async () => {
      setIsLoadingTweets(true);
      // TODO: Remplacer par un véritable appel API à GET /api/v1/social/posts ou /api/v1/feed
      await new Promise(resolve => setTimeout(resolve, 1000)); // Simule la latence réseau
      setTweets(mockTweets);
      setIsLoadingTweets(false);
    };

    fetchTweets();
  }, []);

  if (authLoading || isLoadingTweets) {
    return (
      <div className="flex justify-center items-center min-h-screen text-white">
        Chargement du fil d'actualité...
      </div>
    );
  }

  // Optionnel: Rediriger vers login si pas d'utilisateur et chargement terminé
  // if (!user) {
  //   // router.push('/login'); // Nécessite useRouter de next/navigation
  //   return <div className="text-white text-center p-8">Veuillez vous connecter pour voir le fil.</div>;
  // }

  return (
    <main className="container mx-auto max-w-2xl py-8 px-4 text-white">
      <h1 className="text-3xl font-bold mb-6">Fil d'actualité</h1>

      {/* TODO: Ajouter le formulaire NewTweetForm ici plus tard */}

      <div className="space-y-4">
        {tweets.length === 0 && !isLoadingTweets ? (
          <p>Aucun tweet à afficher pour le moment.</p>
        ) : (
          tweets.map(tweet => <TweetCard key={tweet.id} tweet={tweet} />)
        )}
      </div>
    </main>
  );
}
