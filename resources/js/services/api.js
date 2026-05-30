import axios from 'axios';

const apiBaseUrl = import.meta.env.VITE_API_URL || window.location.origin;
const tokenKey = 'token';
const roleKey = 'role';

const api = axios.create({
  baseURL: apiBaseUrl,
  timeout: 15000,
  headers: {
    Accept: 'application/json',
    'X-Requested-With': 'XMLHttpRequest',
  },
});

export function getAuthToken() {
  return localStorage.getItem(tokenKey);
}

export function hasAuthToken() {
  return Boolean(getAuthToken());
}

export function getStoredRole() {
  return localStorage.getItem(roleKey);
}

export function setAuthSession(token, role) {
  localStorage.setItem(tokenKey, token);
  localStorage.setItem(roleKey, role);
}

export function clearAuthSession() {
  localStorage.removeItem(tokenKey);
  localStorage.removeItem(roleKey);
}

api.interceptors.request.use((config) => {
  const token = getAuthToken();

  if (token) {
    config.headers.Authorization = `Bearer ${token}`;
  } else {
    delete config.headers.Authorization;
  }

  return config;
});

api.interceptors.response.use(
  (response) => response,
  (error) => {
    if (error.response?.status === 401) {
      clearAuthSession();

      if (window.location.pathname !== '/' && window.location.pathname !== '/login') {
        window.location.href = '/';
      }
    }

    return Promise.reject(error);
  },
);

export default api;
