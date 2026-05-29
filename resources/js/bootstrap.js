import axios from 'axios';

window.axios = axios;

const apiBaseUrl = import.meta.env.VITE_API_URL || window.location.origin;

window.axios.defaults.baseURL = apiBaseUrl;
window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';