// newscast/config/api.ts
import axios from 'axios';

import { Platform } from 'react-native';

const API_BASE_URL = 'https://panchayatvoice.in/api/v1/api.php';

const api = axios.create({
  baseURL: API_BASE_URL,
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
