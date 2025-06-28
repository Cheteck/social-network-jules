import axios from 'axios';

const apiClient = axios.create({
  baseURL: process.env.NEXT_PUBLIC_API_URL || 'http://localhost:8000', // L'URL du backend Laravel
  withCredentials: true, // Important pour que Sanctum fonctionne avec les cookies
  headers: {
    'X-Requested-With': 'XMLHttpRequest',
    'Accept': 'application/json',
  },
});

// Fonction pour récupérer le cookie CSRF
// Cette fonction devrait être appelée avant les requêtes POST/PUT/DELETE sensibles
// si ce n'est pas géré automatiquement par Sanctum/Fortify lors du premier chargement.
export const fetchCsrfToken = async () => {
  try {
    await apiClient.get('/sanctum/csrf-cookie');
    console.log('CSRF cookie fetched');
  } catch (error) {
    console.error('Error fetching CSRF cookie:', error);
    // Gérer l'erreur comme il se doit, peut-être informer l'utilisateur
  }
};

// Intercepteur de réponse pour gérer les erreurs globalement (optionnel mais utile)
apiClient.interceptors.response.use(
  response => response,
  error => {
    // TODO: Gérer les erreurs communes ici (ex: 401 Unauthorized, 403 Forbidden, etc.)
    // Par exemple, si 401, déconnecter l'utilisateur et le rediriger vers la page de login.
    if (error.response && error.response.status === 401) {
      // Potentiellement appeler une fonction logout du AuthContext ou rediriger.
      console.error('Unauthorized, logging out or redirecting...', error.response);
      // window.location.href = '/auth_group/login'; // Redirection brutale, mieux via Next Router si possible
    }
    return Promise.reject(error);
  }
);

export default apiClient;
