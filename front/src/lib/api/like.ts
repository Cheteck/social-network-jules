export async function likePost(postId: number) {
  const res = await fetch(`/api/v1/likeable/posts/${postId}/like`, {
    method: 'POST',
    headers: { 'Accept': 'application/json' },
    credentials: 'include',
  });
  if (!res.ok) throw new Error('Erreur lors du like');
  return res.json();
}

export async function unlikePost(postId: number) {
  const res = await fetch(`/api/v1/likeable/posts/${postId}/unlike`, {
    method: 'DELETE',
    headers: { 'Accept': 'application/json' },
    credentials: 'include',
  });
  if (!res.ok) throw new Error('Erreur lors du unlike');
  return res.json();
}
