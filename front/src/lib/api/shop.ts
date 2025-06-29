export async function fetchShops() {
  const res = await fetch('/api/v1/shops', {
    headers: { 'Accept': 'application/json' },
    credentials: 'include',
  });
  if (!res.ok) throw new Error('Erreur lors du chargement des shops');
  return res.json();
}

export async function fetchShop(shopId: number) {
  const res = await fetch(`/api/v1/shops/${shopId}`, {
    headers: { 'Accept': 'application/json' },
    credentials: 'include',
  });
  if (!res.ok) throw new Error('Erreur lors du chargement du shop');
  return res.json();
}
