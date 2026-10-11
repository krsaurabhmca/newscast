import AsyncStorage from '@react-native-async-storage/async-storage';
import { Platform } from 'react-native';

const STORAGE_KEYS = {
  PORTAL_URL: 'newsportal_website_url',
  AUTH_USER: 'newsportal_auth_user',
  SAVED_PORTALS: 'newsportal_saved_portals_history',
};

// Memory fallback for environments where AsyncStorage or localStorage might fail
const memoryCache: Record<string, string> = {};

export async function setItem(key: string, value: string): Promise<void> {
  try {
    memoryCache[key] = value;
    if (Platform.OS === 'web' && typeof window !== 'undefined' && window.localStorage) {
      window.localStorage.setItem(key, value);
    }
    await AsyncStorage.setItem(key, value);
  } catch (error) {
    console.warn(`[Storage] Failed to set ${key}:`, error);
  }
}

export async function getItem(key: string): Promise<string | null> {
  try {
    if (Platform.OS === 'web' && typeof window !== 'undefined' && window.localStorage) {
      const val = window.localStorage.getItem(key);
      if (val !== null) return val;
    }
    const asyncVal = await AsyncStorage.getItem(key);
    if (asyncVal !== null) return asyncVal;
    return memoryCache[key] ?? null;
  } catch (error) {
    console.warn(`[Storage] Failed to get ${key}:`, error);
    return memoryCache[key] ?? null;
  }
}

export async function removeItem(key: string): Promise<void> {
  try {
    delete memoryCache[key];
    if (Platform.OS === 'web' && typeof window !== 'undefined' && window.localStorage) {
      window.localStorage.removeItem(key);
    }
    await AsyncStorage.removeItem(key);
  } catch (error) {
    console.warn(`[Storage] Failed to remove ${key}:`, error);
  }
}

export async function getStoredPortalUrl(): Promise<string | null> {
  return getItem(STORAGE_KEYS.PORTAL_URL);
}

export { STORAGE_KEYS };
