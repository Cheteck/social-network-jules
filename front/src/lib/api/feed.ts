export async function fetchFeed() {
  const res = await fetch('/api/v1/feed', {
    headers: { 'Accept': 'application/json' },
    credentials: 'include',
  });
  if (!res.ok) throw new Error('Erreur lors du chargement du feed');
  return res.json();
}
