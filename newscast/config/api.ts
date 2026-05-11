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
  cleanPath = cleanPath.replace(/^https?:\/\/(www\.)?(panchayatvoice\.in|localhost|192\.168\.\d+\.\d+)\/(news\/)?/i, '');
  
  if (cleanPath.startsWith('/')) cleanPath = cleanPath.substring(1);
  
  // If it's another domain (like YouTube), keep it as is
  if (cleanPath.startsWith('http')) return cleanPath;
  
  return `${BASE_URL}${cleanPath}`;
};

/**
 * Generic Fetch-based GET action
 */
export const getAction = async (action: string, params: any = {}) => {
  try {
    const queryString = new URLSearchParams({ action, ...params }).toString();
    const url = `${API_URL}?${queryString}`;
    
    console.log('Fetching GET:', url);

    const response = await fetch(url, {
      method: 'GET',
      headers: {
        'Accept': 'application/json',
        'Cache-Control': 'no-cache',
      },
    });

    if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`);
    }

    const data = await response.json();
    return data;
  } catch (error: any) {
    console.error('API GET Error Details:', {
      action,
      message: error.message,
      url: API_URL
    });
    return { success: false, message: 'Connection error' };
  }
};

/**
 * Generic Fetch-based POST action
 */
export const postAction = async (action: string, data = {}) => {
  try {
    const url = `${API_URL}?action=${action}`;
    
    console.log('Fetching POST:', url);

    const response = await fetch(url, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
      },
      body: JSON.stringify(data),
    });

    if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`);
    }

    const resData = await response.json();
    return resData;
  } catch (error: any) {
    console.error('API POST Error Details:', {
      action,
      message: error.message,
      url: API_URL
    });
    return { success: false, message: 'Connection error' };
  }
};

export default { getAction, postAction, getFullUrl, BASE_URL };
