import axios from 'axios';
window.axios = axios;

window.axios.defaults.baseURL = import.meta.env.VITE_APP_URL || window.location.origin;
window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
window.axios.defaults.withCredentials = true;
window.axios.defaults.xsrfHeaderName = 'X-XSRF-TOKEN';
window.axios.defaults.xsrfCookieName = 'XSRF-TOKEN';

const storedToken = localStorage.getItem('api_token');
if (storedToken) {
    window.axios.defaults.headers.common.Authorization = `Bearer ${storedToken}`;
}
