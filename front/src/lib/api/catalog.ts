export async function fetchShopProducts(shopId: number) {
  const res = await fetch(`/api/v1/shops/${shopId}/products`, {
    headers: { 'Accept': 'application/json' },
    credentials: 'include',
  });
  if (!res.ok) throw new Error('Erreur lors du chargement des produits');
  return res.json();
}
