import axios from 'axios';

// Instance Axios pour l'API Laravel
const apiClient = axios.create({
  baseURL: 'http://127.0.0.1:8000',
  withCredentials: true,
  headers: {
    'X-Requested-With': 'XMLHttpRequest',
    'Accept': 'application/json',
  },
});

// Intercepteur pour XSRF-TOKEN et Authorization
apiClient.interceptors.request.use((config) => {
  if (typeof window !== 'undefined') {
    const match = document.cookie.match(new RegExp('(^| )XSRF-TOKEN=([^;]+)'));
    if (match) {
      const xsrfToken = decodeURIComponent(match[2]);
      config.headers['X-XSRF-TOKEN'] = xsrfToken;
    }
    const authToken = localStorage.getItem('auth_token');
    if (authToken && (config.url?.includes('/logout') || config.url?.includes('/api/user'))) {
      config.headers['Authorization'] = `Bearer ${authToken}`;
    }
    if (config.method === 'post') {
      config.headers['Content-Type'] = 'application/json';
    }
  }
  return config;
}, (error) => Promise.reject(error));

// Récupère le cookie CSRF
export const fetchCsrfToken = async () => {
  await apiClient.get('/sanctum/csrf-cookie');
};

// Login
export const login = async (email: string, password: string, deviceName = 'web') => {
  await fetchCsrfToken();
  const response = await apiClient.post('/login', { email, password, device_name: deviceName });
  if (response.data.token) {
    localStorage.setItem('auth_token', response.data.token);
  }
  return response.data;
};

// Register
export const register = async (name: string, email: string, password: string, passwordConfirmation: string) => {
  await fetchCsrfToken();
  const response = await apiClient.post('/register', {
    name,
    email,
    password,
    password_confirmation: passwordConfirmation,
  });
  return response.data;
};

// Logout
export const logout = async () => {
  await fetchCsrfToken();
  await apiClient.post('/logout');
  localStorage.removeItem('auth_token');
};

// Get user
export const getUser = async () => {
  const response = await apiClient.get('/api/user');
  return response.data;
};

export default apiClient; // Exporting the apiClient instance as default as well, if needed by other parts or for direct use.
