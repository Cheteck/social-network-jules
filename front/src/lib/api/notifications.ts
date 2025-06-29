export async function fetchNotifications() {
  const res = await fetch('/api/v1/notifications', {
    headers: { 'Accept': 'application/json' },
    credentials: 'include',
  });
  if (!res.ok) throw new Error('Erreur lors du chargement des notifications');
  return res.json();
}

export async function markNotificationRead(notificationId: string) {
  const res = await fetch(`/api/v1/notifications/${notificationId}/read`, {
    method: 'PATCH',
    headers: { 'Accept': 'application/json' },
    credentials: 'include',
  });
  if (!res.ok) throw new Error('Erreur lors du marquage comme lu');
  return res.json();
}
