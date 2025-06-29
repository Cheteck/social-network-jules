export async function fetchHashtags() {
  const res = await fetch('/api/v1/hashtags', {
    headers: { 'Accept': 'application/json' },
    credentials: 'include',
  });
  if (!res.ok) throw new Error('Erreur lors du chargement des hashtags');
  return res.json();
}

export async function fetchPostsByHashtag(tag: string) {
  const res = await fetch(`/api/v1/hashtags/${encodeURIComponent(tag)}/posts`, {
    headers: { 'Accept': 'application/json' },
    credentials: 'include',
  });
  if (!res.ok) throw new Error('Erreur lors du chargement des posts par hashtag');
  return res.json();
}
