export async function fetchPosts() {
  const res = await fetch('/api/v1/social/posts', {
    headers: { 'Accept': 'application/json' },
    credentials: 'include',
  });
  if (!res.ok) throw new Error('Erreur lors du chargement des posts');
  return res.json();
}

export async function createPost(content: string) {
  const res = await fetch('/api/v1/social/posts', {
    method: 'POST',
    headers: {
      'Accept': 'application/json',
      'Content-Type': 'application/json',
    },
    credentials: 'include',
    body: JSON.stringify({ content }),
  });
  if (!res.ok) throw new Error('Erreur lors de la création du post');
  return res.json();
}
