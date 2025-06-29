export async function fetchMyProfile() {
  const res = await fetch('/api/profile', {
    headers: { 'Accept': 'application/json' },
    credentials: 'include',
  });
  if (!res.ok) throw new Error('Erreur lors du chargement du profil');
  return res.json();
}

export async function fetchUserProfile(userId: number) {
  const res = await fetch(`/api/users/${userId}/profile`, {
    headers: { 'Accept': 'application/json' },
    credentials: 'include',
  });
  if (!res.ok) throw new Error('Erreur lors du chargement du profil public');
  return res.json();
}
