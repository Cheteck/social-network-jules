'use client';

import React, { useEffect, useState, useCallback } from 'react'; // Import useCallback
import apiClient from '@/lib/api';
import { Notification } from '@/lib/types/notification'; // Importer le type Notification
import NotificationCard from '@/components/notifications/NotificationCard'; // Importer NotificationCard
import { useAuth } from '@/lib/contexts/AuthContext';
// import Link from 'next/link'; // Link n'est pas utilisé

export default function NotificationsPage() {
  const { user } = useAuth();
  const [notifications, setNotifications] = useState<Notification[]>([]);
  const [isLoading, setIsLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [filter, ] = useState<'all' | 'unread' | 'read'>('all'); // setFilter n'est pas utilisé pour l'instant

  const fetchNotifications = useCallback(async (currentFilter: typeof filter) => {
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
      setNotifications(response.data.data || response.data || []);
    } catch (err: unknown) {
      let message = "Impossible de charger les notifications.";
      if (err instanceof Error) {
        // Check for Axios-like error structure more safely
        if (typeof err === 'object' && err !== null && 'response' in err) {
          const errorResponse = (err as { response?: { data?: { message?: string } } }).response;
          if (errorResponse?.data?.message) {
            message = errorResponse.data.message;
          } else {
            message = err.message; // Fallback to generic error message
          }
        } else {
          message = err.message; // Fallback if not an Axios-like error
        }
      }
      console.error("Failed to fetch notifications:", err);
      setError(message);
      setNotifications([]);
    } finally {
      setIsLoading(false);
    }
  }, [user]); // user est une dépendance de fetchNotifications

  useEffect(() => {
    fetchNotifications(filter);
  }, [user, filter, fetchNotifications]);

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
    } catch (err: unknown) {
      let message = "Impossible de marquer toutes les notifications comme lues.";
      if (err instanceof Error) {
         // Check for Axios-like error structure more safely (though less likely for a POST error to have detailed message like GET)
        if (typeof err === 'object' && err !== null && 'response' in err) {
          const errorResponse = (err as { response?: { data?: { message?: string } } }).response;
          if (errorResponse?.data?.message) {
            message = errorResponse.data.message;
          } else {
            message = err.message; // Fallback to generic error message
          }
        } else {
          message = err.message; // Fallback if not an Axios-like error
        }
      }
      console.error("Failed to mark all as read:", err);
      setError(message); // Afficher une erreur
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
