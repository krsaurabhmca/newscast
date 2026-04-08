import axios from 'axios';

// ─── ENVIRONMENT CONFIGURATION ────────────────────────────────────────────────
// For LIVE / Production server:
export const BASE_URL = 'https://panchayatvoice.in/';

// For LOCAL development (phone & PC on same WiFi):
// export const BASE_URL = 'http://192.168.1.11/news/';
// ─────────────────────────────────────────────────────────────────────────────

export const API_URL = `${BASE_URL}api/v1/api.php`;

/**
 * Ensures a URL is absolute by prepending the BASE_URL if needed.
 */
export const getFullUrl = (path: string) => {
  if (!path || path === '') return `${BASE_URL}assets/images/default-post.jpg`;
  
  let cleanPath = path.toString().trim();
  
  // Robustly strip any variation of the known domains
  cleanPath = cleanPath.replace(/^https?:\/\/(www\.)?(panchayatvoice\.in|localhost)\/(news\/)?/i, '');
  
  if (cleanPath.startsWith('/')) cleanPath = cleanPath.substring(1);
  
  // If it's another domain (like YouTube), keep it as is
  if (cleanPath.startsWith('http')) return cleanPath;
  
  return `${BASE_URL}${cleanPath}`;
};

const api = axios.create({
  baseURL: API_URL,
  headers: {
    'Content-Type': 'application/json',
  },
});

export const getAction = async (action: string, params = {}) => {
  try {
    const response = await api.get('', {
      params: { action, ...params },
    });
    return response.data;
  } catch (error) {
    console.error('API Error:', action, error);
    return { success: false, message: 'Server error' };
  }
};

export const postAction = async (action: string, data = {}) => {
  try {
    const response = await api.post(`?action=${action}`, data);
    return response.data;
  } catch (error) {
    console.error('API Error:', action, error);
    return { success: false, message: 'Server error' };
  }
};

export default api;
