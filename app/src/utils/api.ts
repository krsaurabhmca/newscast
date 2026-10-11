import { Platform } from 'react-native';
import { getItem, setItem, removeItem, STORAGE_KEYS } from './storage';

export interface UserProfile {
  id: number;
  username: string;
  email: string;
  role: string;
  profile_image?: string;
  token?: string;
}

export interface PortalInfo {
  site_name: string;
  site_logo?: string | null;
  theme_color?: string;
  version?: string;
  base_url?: string;
  server_time?: string;
}

export interface DashboardStats {
  total_posts: number;
  published_posts: number;
  draft_posts: number;
  today_posts: number;
  total_views: number;
  total_categories: number;
  total_users: number;
  unread_feedback: number;
}

export interface PostItem {
  id: number;
  title: string;
  slug: string;
  excerpt?: string;
  content?: string;
  featured_image?: string;
  status: 'published' | 'draft';
  views: number;
  is_featured: number | boolean;
  published_at?: string;
  created_at?: string;
  category_id?: number | null;
  category_name?: string | null;
  category_color?: string | null;
  category_ids?: number[];
  video_url?: string;
  external_link?: string;
  meta_description?: string;
  author_name?: string;
}

export interface CategoryItem {
  id: number;
  name: string;
  slug: string;
  description?: string;
  icon?: string;
  color?: string;
  status: 'active' | 'disabled';
  post_count?: number;
}

export interface FeedbackItem {
  id: number;
  name?: string;
  email?: string;
  phone?: string;
  subject?: string;
  message?: string;
  status: 'new' | 'read' | 'replied';
  created_at: string;
}

export interface PollOption {
  id: number;
  poll_id: number;
  option_text: string;
  votes_count: number;
  percentage?: number;
}

export interface PollItem {
  id: number;
  question: string;
  slug?: string;
  status: 'active' | 'closed';
  starts_at?: string | null;
  expires_at?: string | null;
  created_at?: string;
  total_votes: number;
  options: PollOption[];
}

export interface MediaItem {
  id: number;
  filename: string;
  original_name: string;
  file_size: number;
  file_url: string;
  width?: number;
  height?: number;
  created_at: string;
}

export interface PortalSettings {
  site_name?: string;
  site_tagline?: string;
  theme_color?: string;
  site_logo?: string;
  live_youtube_enabled?: string;
  live_youtube_url?: string;
  live_stream_title?: string;
  breaking_news_enabled?: string;
  contact_email?: string;
  contact_phone?: string;
  address?: string;
  facebook_url?: string;
  twitter_url?: string;
  instagram_url?: string;
  youtube_url?: string;
  whatsapp_number?: string;
}

/**
 * Clean and normalize portal base URL
 */
export function normalizePortalUrl(rawUrl: string): string {
  let cleaned = (rawUrl || '').trim();
  if (!cleaned) return '';

  if (!cleaned.startsWith('http://') && !cleaned.startsWith('https://')) {
    cleaned = 'http://' + cleaned;
  }

  // Remove trailing slashes
  cleaned = cleaned.replace(/\/+$/, '');

  // If user pasted full endpoint (like .../api/v1/api.php), strip that back to portal root
  cleaned = cleaned.replace(/\/api\/v1(\/api\.php)?\/?$/, '');

  return cleaned;
}

/**
 * For Android emulator, convert localhost to 10.0.2.2 if on Android
 */
export function getExecutableUrl(portalUrl: string): string {
  const norm = normalizePortalUrl(portalUrl);
  if (Platform.OS === 'android' && (norm.includes('://localhost') || norm.includes('://127.0.0.1'))) {
    return norm.replace('://localhost', '://10.0.2.2').replace('://127.0.0.1', '://10.0.2.2');
  }
  return norm;
}

export function getDefaultPortalUrl(): string {
  if (Platform.OS === 'android') {
    return 'http://10.0.2.2/news';
  }
  return 'http://localhost/news';
}

/**
 * Send request to portal API
 */
async function requestApi<T = any>(
  portalUrl: string,
  action: string,
  options: {
    method?: 'GET' | 'POST';
    params?: Record<string, any>;
    body?: any;
    timeoutMs?: number;
  } = {}
): Promise<{ success: boolean; message: string; data?: T; [key: string]: any }> {
  const base = getExecutableUrl(portalUrl);
  if (!base) {
    throw new Error('Please enter a valid website URL');
  }

  const method = options.method || 'GET';
  const queryParams = new URLSearchParams();
  queryParams.append('action', action);

  if (options.params) {
    Object.entries(options.params).forEach(([key, val]) => {
      if (val !== undefined && val !== null && val !== '') {
        queryParams.append(key, String(val));
      }
    });
  }

  const endpoint = `${base}/api/v1/api.php?${queryParams.toString()}`;

  const controller = new AbortController();
  const timeoutId = setTimeout(() => controller.abort(), options.timeoutMs || 10000);

  try {
    const fetchOptions: RequestInit = {
      method,
      headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
      },
      signal: controller.signal,
    };

    if (method === 'POST' && options.body) {
      fetchOptions.body = JSON.stringify(options.body);
    }

    const response = await fetch(endpoint, fetchOptions);
    clearTimeout(timeoutId);

    const json = await response.json();
    return json;
  } catch (error: any) {
    clearTimeout(timeoutId);
    if (error.name === 'AbortError') {
      throw new Error(`Connection timed out reaching ${base}. Please check the server.`);
    }
    throw new Error(error.message || `Failed to connect to ${base}`);
  }
}

// ── API Operations ──────────────────────────────────────────────────────────

export async function testConnection(portalUrl: string): Promise<{ success: boolean; data?: PortalInfo; message: string }> {
  try {
    const res = await requestApi<PortalInfo>(portalUrl, 'ping', { timeoutMs: 6000 });
    return {
      success: !!res.success,
      data: res.data,
      message: res.message || (res.success ? 'Connected successfully' : 'Server responded with error'),
    };
  } catch (err: any) {
    return {
      success: false,
      message: err.message || 'Cannot reach website',
    };
  }
}

export async function loginUser(
  portalUrl: string,
  identifier: string,
  password: string
): Promise<{ success: boolean; message: string; user?: UserProfile; portal?: PortalInfo }> {
  const res = await requestApi<{ user: UserProfile; portal: PortalInfo }>(portalUrl, 'login', {
    method: 'POST',
    body: {
      username: identifier,
      email: identifier,
      password,
    },
  });

  if (res.success && res.data?.user) {
    // Store credentials and session
    await setItem(STORAGE_KEYS.PORTAL_URL, normalizePortalUrl(portalUrl));
    await setItem(STORAGE_KEYS.AUTH_USER, JSON.stringify(res.data.user));
    return {
      success: true,
      message: res.message || 'Login successful',
      user: res.data.user,
      portal: res.data.portal,
    };
  }

  return {
    success: false,
    message: res.message || 'Invalid username or password',
  };
}

export async function changePassword(
  portalUrl: string,
  data: {
    userId?: number;
    username?: string;
    currentPassword: string;
    newPassword: string;
    confirmPassword?: string;
  }
): Promise<{ success: boolean; message: string }> {
  const res = await requestApi(portalUrl, 'admin_change_password', {
    method: 'POST',
    body: {
      user_id: data.userId,
      username: data.username,
      current_password: data.currentPassword,
      new_password: data.newPassword,
      confirm_password: data.confirmPassword,
    },
  });
  return {
    success: !!res.success,
    message: res.message || (res.success ? 'Password updated successfully' : 'Failed to update password'),
  };
}

export async function getDashboardData(portalUrl: string): Promise<{
  stats: DashboardStats;
  live_stream: { enabled: boolean; url: string };
  breaking_news_enabled: boolean;
  recent_posts: PostItem[];
}> {
  const res = await requestApi(portalUrl, 'admin_dashboard');
  if (!res.success) throw new Error(res.message || 'Failed to fetch dashboard');
  return res.data;
}

export async function getPosts(
  portalUrl: string,
  params: { page?: number; limit?: number; search?: string; status?: string; category_id?: number } = {}
): Promise<{ posts: PostItem[]; pagination: { total: number; page: number; limit: number; total_pages: number } }> {
  const res = await requestApi<PostItem[]>(portalUrl, 'admin_posts', { params });
  if (!res.success) throw new Error(res.message || 'Failed to fetch posts');
  return {
    posts: res.data || [],
    pagination: res.pagination || { total: 0, page: 1, limit: 15, total_pages: 1 },
  };
}

export async function getPostDetail(portalUrl: string, id: number): Promise<PostItem> {
  const res = await requestApi<PostItem>(portalUrl, 'admin_post_detail', { params: { id } });
  if (!res.success || !res.data) throw new Error(res.message || 'Failed to fetch post detail');
  return res.data;
}

export async function savePost(portalUrl: string, postData: Partial<PostItem>): Promise<any> {
  const res = await requestApi(portalUrl, 'admin_save_post', {
    method: 'POST',
    body: postData,
  });
  if (!res.success) throw new Error(res.message || 'Failed to save post');
  return res.data;
}

export async function deletePost(portalUrl: string, id: number): Promise<void> {
  const res = await requestApi(portalUrl, 'admin_delete_post', {
    method: 'POST',
    body: { id },
  });
  if (!res.success) throw new Error(res.message || 'Failed to delete post');
}

export async function togglePostStatus(portalUrl: string, id: number, status?: 'published' | 'draft'): Promise<string> {
  const res = await requestApi(portalUrl, 'admin_toggle_post_status', {
    method: 'POST',
    body: { id, status },
  });
  if (!res.success) throw new Error(res.message || 'Failed to toggle status');
  return res.data?.status || 'published';
}

export async function getCategories(portalUrl: string): Promise<CategoryItem[]> {
  const res = await requestApi<CategoryItem[]>(portalUrl, 'admin_categories');
  if (!res.success) throw new Error(res.message || 'Failed to fetch categories');
  return res.data || [];
}

export async function saveCategory(portalUrl: string, catData: Partial<CategoryItem>): Promise<any> {
  const res = await requestApi(portalUrl, 'admin_save_category', {
    method: 'POST',
    body: catData,
  });
  if (!res.success) throw new Error(res.message || 'Failed to save category');
  return res.data;
}

export async function deleteCategory(portalUrl: string, id: number): Promise<void> {
  const res = await requestApi(portalUrl, 'admin_delete_category', {
    method: 'POST',
    body: { id },
  });
  if (!res.success) throw new Error(res.message || 'Failed to delete category');
}

export async function getSettings(portalUrl: string): Promise<PortalSettings> {
  const res = await requestApi<PortalSettings>(portalUrl, 'admin_settings');
  if (!res.success) throw new Error(res.message || 'Failed to fetch settings');
  return res.data || {};
}

export async function updateSettings(portalUrl: string, settings: Partial<PortalSettings>): Promise<void> {
  const res = await requestApi(portalUrl, 'admin_update_settings', {
    method: 'POST',
    body: settings,
  });
  if (!res.success) throw new Error(res.message || 'Failed to update settings');
}

export async function toggleLiveStream(portalUrl: string, enabled: boolean, url?: string): Promise<any> {
  const res = await requestApi(portalUrl, 'admin_toggle_live', {
    method: 'POST',
    body: { enabled: enabled ? 1 : 0, url },
  });
  if (!res.success) throw new Error(res.message || 'Failed to toggle live stream');
  return res.data;
}

export async function getFeedback(portalUrl: string): Promise<FeedbackItem[]> {
  const res = await requestApi<FeedbackItem[]>(portalUrl, 'admin_feedback');
  if (!res.success) throw new Error(res.message || 'Failed to fetch feedback');
  return res.data || [];
}

export async function updateFeedback(portalUrl: string, id: number, subaction: 'mark_read' | 'delete'): Promise<void> {
  const res = await requestApi(portalUrl, 'admin_feedback', {
    method: 'POST',
    body: { id, subaction },
  });
  if (!res.success) throw new Error(res.message || 'Failed to update feedback');
}

export async function getPolls(portalUrl: string): Promise<PollItem[]> {
  const res = await requestApi<PollItem[]>(portalUrl, 'admin_polls');
  if (!res.success) throw new Error(res.message || 'Failed to fetch polls');
  return res.data || [];
}

export async function savePoll(
  portalUrl: string,
  pollData: {
    question: string;
    options: string[];
    status?: 'active' | 'closed';
    starts_at?: string | null;
    expires_at?: string | null;
  }
): Promise<any> {
  const res = await requestApi(portalUrl, 'admin_save_poll', {
    method: 'POST',
    body: pollData,
  });
  if (!res.success) throw new Error(res.message || 'Failed to create poll');
  return res.data;
}

export async function togglePollStatus(
  portalUrl: string,
  id: number,
  status: 'active' | 'closed'
): Promise<void> {
  const res = await requestApi(portalUrl, 'admin_toggle_poll_status', {
    method: 'POST',
    body: { id, status },
  });
  if (!res.success) throw new Error(res.message || 'Failed to update poll status');
}

export async function deletePoll(portalUrl: string, id: number): Promise<void> {
  const res = await requestApi(portalUrl, 'admin_delete_poll', {
    method: 'POST',
    body: { id },
  });
  if (!res.success) throw new Error(res.message || 'Failed to delete poll');
}

export async function getMediaList(
  portalUrl: string,
  page = 1,
  search = ''
): Promise<{ items: MediaItem[]; total: number; total_pages: number }> {
  const res = await requestApi<MediaItem[]>(portalUrl, 'admin_media_list', {
    params: {
      page,
      limit: 28,
      search: search ? search.trim() : undefined,
    },
  });
  if (!res.success) throw new Error(res.message || 'Failed to fetch media');

  const baseNorm = getExecutableUrl(portalUrl);
  const items = (res.data || []).map((item) => {
    let url = item.file_url;
    if (url && (url.includes('://localhost') || url.includes('://127.0.0.1'))) {
      if (baseNorm.includes('10.0.2.2')) {
        url = url.replace('://localhost', '://10.0.2.2').replace('://127.0.0.1', '://10.0.2.2');
      }
    }
    return {
      ...item,
      file_url: url,
    };
  });

  return {
    items,
    total: res.pagination?.total ?? res.total ?? 0,
    total_pages: res.pagination?.total_pages ?? res.total_pages ?? 1,
  };
}

export async function uploadMediaImage(
  portalUrl: string,
  imageBase64: string
): Promise<{ file_name: string; file_url: string }> {
  const res = await requestApi<{ file_name: string; file_url: string }>(
    portalUrl,
    'admin_upload_image',
    {
      method: 'POST',
      body: { image_base64: imageBase64 },
    }
  );
  if (!res.success || !res.data) throw new Error(res.message || 'Failed to upload image');

  let url = res.data.file_url;
  const baseNorm = getExecutableUrl(portalUrl);
  if (url && (url.includes('://localhost') || url.includes('://127.0.0.1'))) {
    if (baseNorm.includes('10.0.2.2')) {
      url = url.replace('://localhost', '://10.0.2.2').replace('://127.0.0.1', '://10.0.2.2');
    }
  }

  return {
    file_name: res.data.file_name,
    file_url: url,
  };
}

export async function deleteMediaItem(
  portalUrl: string,
  id: number,
  filename: string
): Promise<void> {
  const res = await requestApi(portalUrl, 'admin_media_delete', {
    method: 'POST',
    body: { id, filename },
  });
  if (!res.success) throw new Error(res.message || 'Failed to delete media');
}

export async function logoutUser(): Promise<void> {
  await removeItem(STORAGE_KEYS.AUTH_USER);
}
