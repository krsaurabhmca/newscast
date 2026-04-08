import axios from 'axios';

// ─── ENVIRONMENT CONFIGURATION ────────────────────────────────────────────────
// For LIVE / Production server:
export const BASE_URL = 'https://www.panchayatvoice.in/';
export const API_URL = `${BASE_URL}api/v1/`;

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
  timeout: 30000,
  headers: {
    'Accept': 'application/json',
  },
});

export const getAction = async (action: string, params = {}) => {
  try {
    const response = await api.get('api.php', {
      params: { action, ...params },
    });
    return response.data;
  } catch (error: any) {
    console.error('API GET Error Details:', {
      action,
      message: error.message,
      code: error.code,
      url: error.config?.url,
      baseURL: error.config?.baseURL,
    });
    return { success: false, message: 'Server error' };
  }
};

export const postAction = async (action: string, data = {}) => {
  try {
    const response = await api.post(`api.php?action=${action}`, data);
    return response.data;
  } catch (error: any) {
    console.error('API POST Error Details:', {
      action,
      message: error.message,
      code: error.code,
      url: error.config?.url,
      baseURL: error.config?.baseURL,
    });
    return { success: false, message: 'Server error' };
  }
};

export default api;
