export async function followUser(userId: number) {
  const res = await fetch(`/api/users/${userId}/follow`, {
    method: 'POST',
    headers: { 'Accept': 'application/json' },
    credentials: 'include',
  });
  if (!res.ok) throw new Error('Erreur lors du follow');
  return res.json();
}

export async function unfollowUser(userId: number) {
  const res = await fetch(`/api/users/${userId}/unfollow`, {
    method: 'DELETE',
    headers: { 'Accept': 'application/json' },
    credentials: 'include',
  });
  if (!res.ok) throw new Error('Erreur lors du unfollow');
  return res.json();
}
