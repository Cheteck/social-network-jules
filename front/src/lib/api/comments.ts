export async function fetchComments(postId: number) {
  const res = await fetch(`/api/v1/comments/posts/${postId}`, {
    headers: { 'Accept': 'application/json' },
    credentials: 'include',
  });
  if (!res.ok) throw new Error('Erreur lors du chargement des commentaires');
  return res.json();
}

export async function createComment(postId: number, content: string, parentId?: number) {
  const res = await fetch(`/api/v1/comments/posts/${postId}`, {
    method: 'POST',
    headers: {
      'Accept': 'application/json',
      'Content-Type': 'application/json',
    },
    credentials: 'include',
    body: JSON.stringify({ content, parent_id: parentId || null }),
  });
  if (!res.ok) throw new Error('Erreur lors de la création du commentaire');
  return res.json();
}
