'use client';

import React, { useEffect, useState } from 'react';
import apiClient from '@/lib/api';
import { Notification } from '@/lib/types/notification'; // Importer le type Notification
import NotificationCard from '@/components/notifications/NotificationCard'; // Importer NotificationCard
import { useAuth } from '@/lib/contexts/AuthContext';
import Link from 'next/link';

export default function NotificationsPage() {
  const { user } = useAuth();
  const [notifications, setNotifications] = useState<Notification[]>([]);
  const [isLoading, setIsLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [filter, setFilter] = useState<'all' | 'unread' | 'read'>('all'); // Pour filtrer

  const fetchNotifications = async (currentFilter: typeof filter) => {
    if (!user) {
      setIsLoading(false);
      return;
    }
    setIsLoading(true);
    setError(null);
    try {
      let url = '/api/v1/notifications';
      if (currentFilter !== 'all') {
        url += `?status=${currentFilter}`;
      }
      const response = await apiClient.get(url);
      // Supposer que la réponse est paginée, prendre response.data.data ou response.data
      setNotifications(response.data.data || response.data || []);
    } catch (err: any) {
      console.error("Failed to fetch notifications:", err);
      setError(err.response?.data?.message || "Impossible de charger les notifications.");
      setNotifications([]);
    } finally {
      setIsLoading(false);
    }
  };

  useEffect(() => {
    fetchNotifications(filter);
  }, [user, filter]); // Re-fetch si l'utilisateur ou le filtre change

  const handleNotificationRead = (notificationId: string) => {
    setNotifications(prevNotifications =>
      prevNotifications.map(n =>
        n.id === notificationId ? { ...n, read_at: new Date().toISOString() } : n
      )
    );
  };

  const handleMarkAllAsRead = async () => {
    if (!user || notifications.every(n => n.read_at)) {
      return; // Ne rien faire si pas d'utilisateur ou si tout est déjà lu
    }
    // On pourrait ajouter un état de chargement spécifique pour cette action
    try {
      await apiClient.post('/api/v1/notifications/mark-all-as-read');
      // Mettre à jour l'état local pour refléter que tout est lu
      setNotifications(prevNotifications =>
        prevNotifications.map(n => ({ ...n, read_at: new Date().toISOString() }))
      );
      // Optionnellement, re-fetch si on veut être sûr d'avoir les données serveur exactes
      // fetchNotifications(filter);
    } catch (err) {
      console.error("Failed to mark all as read:", err);
      setError("Impossible de marquer toutes les notifications comme lues."); // Afficher une erreur
    }
  };

  return (
    <main className="w-full max-w-xl mx-auto">
      <div className="p-4 border-b border-x-border flex justify-between items-center">
        <h1 className="text-xl font-bold text-x-primary-text">Notifications</h1>
        {/* TODO: Bouton "Marquer tout comme lu" */}
        <button
            onClick={handleMarkAllAsRead}
            className="text-sm text-x-accent hover:underline"
            disabled={isLoading || notifications.filter(n => !n.read_at).length === 0}
        >
            Marquer tout comme lu
        </button>
      </div>

      {/* TODO: Filtres All/Unread (Read pourrait être moins utile) */}
      {/* <div className="p-4 border-b border-x-border flex space-x-2"> ... boutons de filtre ... </div> */}

      {isLoading && (
        <div className="p-4 text-center text-x-secondary-text">Chargement des notifications...</div>
      )}
      {error && <div className="p-4 text-red-500 text-center">{error}</div>}

      {!isLoading && !error && notifications.length === 0 && (
        <p className="p-4 text-x-secondary-text text-center">Aucune notification pour le moment.</p>
      )}

      {!isLoading && !error && notifications.length > 0 && (
        <div className="divide-y divide-x-border">
          {notifications.map(notification => (
            <NotificationCard
              key={notification.id}
              notification={notification}
              onNotificationRead={handleNotificationRead}
            />
          ))}
        </div>
      )}
    </main>
  );
}
