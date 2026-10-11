import React, { useState, useEffect, useCallback } from 'react';
import {
  View,
  Text,
  StyleSheet,
  ActivityIndicator,
  TouchableOpacity,
  StatusBar,
  ScrollView,
} from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { useRouter } from 'expo-router';
import {
  LayoutDashboard,
  FileText,
  Layers,
  MessageSquare,
  Settings,
  Plus,
  BarChart3,
  ImageIcon,
} from '@/components/ui/icons';
import { getItem, STORAGE_KEYS } from '@/utils/storage';
import {
  UserProfile,
  PortalInfo,
  DashboardStats,
  PostItem,
  CategoryItem,
  getDashboardData,
  getCategories,
  logoutUser,
  testConnection,
} from '@/utils/api';
import { AdminLogin } from '@/components/admin/AdminLogin';
import { AdminHeader } from '@/components/admin/AdminHeader';
import { AdminDashboardTab } from '@/components/admin/AdminDashboardTab';
import { AdminPostsTab } from '@/components/admin/AdminPostsTab';
import { AdminCategoriesTab } from '@/components/admin/AdminCategoriesTab';
import { AdminSettingsTab } from '@/components/admin/AdminSettingsTab';
import { AdminFeedbackTab } from '@/components/admin/AdminFeedbackTab';
import { AdminPostModal } from '@/components/admin/AdminPostModal';
import { useTheme } from '@/hooks/use-theme';

type TabKey = 'dashboard' | 'posts' | 'categories' | 'feedback' | 'settings';

export default function HomeScreen() {
  const router = useRouter();
  const theme = useTheme();

  // App Auth & Connection State
  const [initializing, setInitializing] = useState(true);
  const [currentUser, setCurrentUser] = useState<UserProfile | null>(null);
  const [portalUrl, setPortalUrl] = useState<string>('');
  const [portalInfo, setPortalInfo] = useState<PortalInfo | null>(null);

  // Active Navigation Tab
  const [activeTab, setActiveTab] = useState<TabKey>('dashboard');

  // Dashboard & Content State
  const [refreshing, setRefreshing] = useState(false);
  const [stats, setStats] = useState<DashboardStats | null>(null);
  const [liveStream, setLiveStream] = useState<{ enabled: boolean; url: string }>({
    enabled: false,
    url: '',
  });
  const [breakingNewsEnabled, setBreakingNewsEnabled] = useState(true);
  const [recentPosts, setRecentPosts] = useState<PostItem[]>([]);
  const [categories, setCategories] = useState<CategoryItem[]>([]);

  // Post Creator / Editor Modal State
  const [postModalVisible, setPostModalVisible] = useState(false);
  const [editingPost, setEditingPost] = useState<Partial<PostItem> | null>(null);

  // Initialize: Check stored website and session on launch
  useEffect(() => {
    async function initAuth() {
      try {
        const storedUrl = await getItem(STORAGE_KEYS.PORTAL_URL);
        const storedUser = await getItem(STORAGE_KEYS.AUTH_USER);

        if (storedUrl) {
          setPortalUrl(storedUrl);
          const pingRes = await testConnection(storedUrl);
          if (pingRes.success && pingRes.data) {
            setPortalInfo(pingRes.data);
          }
        }

        if (storedUser) {
          try {
            const parsedUser = JSON.parse(storedUser);
            if (parsedUser && parsedUser.id) {
              setCurrentUser(parsedUser);
            }
          } catch (e) {
            console.warn('Failed to parse stored user', e);
          }
        }
      } catch (err) {
        console.warn('Initialization error', err);
      } finally {
        setInitializing(false);
      }
    }
    initAuth();
  }, []);

  // Fetch Portal Data when logged in
  const loadPortalData = useCallback(async () => {
    if (!portalUrl || !currentUser) return;

    setRefreshing(true);
    try {
      const [dashData, catData] = await Promise.all([
        getDashboardData(portalUrl).catch((e) => {
          console.warn('Dashboard fetch failed', e);
          return null;
        }),
        getCategories(portalUrl).catch((e) => {
          console.warn('Categories fetch failed', e);
          return [];
        }),
      ]);

      if (dashData) {
        setStats(dashData.stats);
        setLiveStream(dashData.live_stream || { enabled: false, url: '' });
        setBreakingNewsEnabled(dashData.breaking_news_enabled);
        setRecentPosts(dashData.recent_posts || []);
      }

      setCategories(catData || []);
    } finally {
      setRefreshing(false);
    }
  }, [portalUrl, currentUser]);

  useEffect(() => {
    if (currentUser && portalUrl) {
      loadPortalData();
    }
  }, [currentUser, portalUrl, loadPortalData]);

  // Handle Login Success
  const handleLoginSuccess = (user: UserProfile, url: string, info?: PortalInfo) => {
    setCurrentUser(user);
    setPortalUrl(url);
    if (info) setPortalInfo(info);
    setActiveTab('dashboard');
  };

  // Handle Logout
  const handleLogout = async () => {
    await logoutUser();
    setCurrentUser(null);
    setActiveTab('dashboard');
  };

  // Handle Changing Stored Website
  const handleUpdatePortalUrl = (newUrl: string) => {
    setPortalUrl(newUrl);
    loadPortalData();
  };

  // Open New Post Editor
  const handleOpenNewPost = () => {
    setEditingPost(null);
    setPostModalVisible(true);
  };

  // Open Existing Post Editor
  const handleOpenEditPost = (post: PostItem) => {
    setEditingPost(post);
    setPostModalVisible(true);
  };

  // Post Saved Successfully
  const handlePostSaved = () => {
    setPostModalVisible(false);
    setEditingPost(null);
    loadPortalData();
  };

  if (initializing) {
    return (
      <View style={[styles.loadingScreen, { backgroundColor: theme.background }]}>
        <ActivityIndicator size="large" color="#dc2626" />
        <Text style={[styles.loadingScreenText, { color: theme.textSecondary }]}>
          Connecting to NewsCast Portal...
        </Text>
      </View>
    );
  }

  // If user is not authenticated -> Show Login with Website URL storage
  if (!currentUser) {
    return (
      <SafeAreaView style={[styles.safeArea, { backgroundColor: theme.background }]}>
        <StatusBar barStyle="dark-content" backgroundColor="#ffffff" animated={true} />
        <AdminLogin onLoginSuccess={handleLoginSuccess} />
      </SafeAreaView>
    );
  }

  // Authenticated: Render Admin Control Center
  return (
    <SafeAreaView
      style={[styles.safeArea, { backgroundColor: theme.background }]}
      edges={['top', 'left', 'right']}>
      <StatusBar barStyle="dark-content" backgroundColor="#ffffff" animated={true} />
      {/* Top Header with Portal Name, Badge, User Info, Logout */}
      <AdminHeader
        user={currentUser}
        portalInfo={portalInfo}
        portalUrl={portalUrl}
        onRefresh={loadPortalData}
        onLogout={handleLogout}
        refreshing={refreshing}
      />

      {/* Internal Navigation Tabs (Horizontally Scrollable) */}
      <View
        style={[
          styles.navTabsBar,
          { backgroundColor: theme.backgroundElement, borderBottomColor: 'rgba(0,0,0,0.06)' },
        ]}>
        <ScrollView
          horizontal
          showsHorizontalScrollIndicator={false}
          contentContainerStyle={styles.navTabsScrollContent}>
          <TouchableOpacity
            style={[styles.navTabBtn, activeTab === 'dashboard' && styles.navTabBtnActive]}
            onPress={() => setActiveTab('dashboard')}>
            <LayoutDashboard
              size={13}
              color={activeTab === 'dashboard' ? '#ffffff' : theme.textSecondary}
              strokeWidth={2.2}
              style={{ marginRight: 5 }}
            />
            <Text
              style={[
                styles.navTabText,
                activeTab === 'dashboard' ? styles.navTabTextActive : { color: theme.textSecondary },
              ]}>
              Overview
            </Text>
          </TouchableOpacity>

          {/* Separate Dedicated Screen: Articles */}
          <TouchableOpacity
            style={styles.navTabBtn}
            onPress={() => router.push('/articles')}>
            <FileText
              size={13}
              color={theme.textSecondary}
              strokeWidth={2.2}
              style={{ marginRight: 5 }}
            />
            <Text style={[styles.navTabText, { color: theme.textSecondary }]}>
              Articles
            </Text>
          </TouchableOpacity>

          {/* Separate Dedicated Screen: Categories */}
          <TouchableOpacity
            style={styles.navTabBtn}
            onPress={() => router.push('/categories')}>
            <Layers
              size={13}
              color={theme.textSecondary}
              strokeWidth={2.2}
              style={{ marginRight: 5 }}
            />
            <Text style={[styles.navTabText, { color: theme.textSecondary }]}>
              Categories
            </Text>
          </TouchableOpacity>

          {/* Separate Dedicated Screen: Polls */}
          <TouchableOpacity
            style={styles.navTabBtn}
            onPress={() => router.push('/polls')}>
            <BarChart3
              size={13}
              color={theme.textSecondary}
              strokeWidth={2.2}
              style={{ marginRight: 5 }}
            />
            <Text style={[styles.navTabText, { color: theme.textSecondary }]}>
              Polls
            </Text>
          </TouchableOpacity>

          {/* Separate Dedicated Screen: Media */}
          <TouchableOpacity
            style={styles.navTabBtn}
            onPress={() => router.push('/media')}>
            <ImageIcon
              size={13}
              color={theme.textSecondary}
              strokeWidth={2.2}
              style={{ marginRight: 5 }}
            />
            <Text style={[styles.navTabText, { color: theme.textSecondary }]}>
              Media
            </Text>
          </TouchableOpacity>

          <TouchableOpacity
            style={[styles.navTabBtn, activeTab === 'feedback' && styles.navTabBtnActive]}
            onPress={() => setActiveTab('feedback')}>
            <MessageSquare
              size={13}
              color={activeTab === 'feedback' ? '#ffffff' : theme.textSecondary}
              strokeWidth={2.2}
              style={{ marginRight: 5 }}
            />
            <Text
              style={[
                styles.navTabText,
                activeTab === 'feedback' ? styles.navTabTextActive : { color: theme.textSecondary },
              ]}>
              Messages
            </Text>
          </TouchableOpacity>

          <TouchableOpacity
            style={[styles.navTabBtn, activeTab === 'settings' && styles.navTabBtnActive]}
            onPress={() => setActiveTab('settings')}>
            <Settings
              size={13}
              color={activeTab === 'settings' ? '#ffffff' : theme.textSecondary}
              strokeWidth={2.2}
              style={{ marginRight: 5 }}
            />
            <Text
              style={[
                styles.navTabText,
                activeTab === 'settings' ? styles.navTabTextActive : { color: theme.textSecondary },
              ]}>
              Settings
            </Text>
          </TouchableOpacity>
        </ScrollView>
      </View>

      {/* Main Tab View */}
      <View style={styles.mainContent}>
        {activeTab === 'dashboard' && (
          <AdminDashboardTab
            stats={stats}
            liveStream={liveStream}
            breakingNewsEnabled={breakingNewsEnabled}
            recentPosts={recentPosts}
            portalUrl={portalUrl}
            onEditPost={handleOpenEditPost}
            onNavigateTab={(tab) => {
              if (tab === 'posts') router.push('/articles');
              else if (tab === 'categories') router.push('/categories');
              else if (tab === 'polls') router.push('/polls');
              else if (tab === 'media') router.push('/media');
              else setActiveTab(tab);
            }}
            onRefresh={loadPortalData}
          />
        )}

        {activeTab === 'posts' && (
          <AdminPostsTab
            portalUrl={portalUrl}
            categories={categories}
            onEditPost={handleOpenEditPost}
          />
        )}

        {activeTab === 'categories' && (
          <AdminCategoriesTab
            categories={categories}
            portalUrl={portalUrl}
            onRefresh={loadPortalData}
          />
        )}

        {activeTab === 'feedback' && (
          <AdminFeedbackTab portalUrl={portalUrl} />
        )}

        {activeTab === 'settings' && (
          <AdminSettingsTab
            portalUrl={portalUrl}
            currentUser={currentUser}
            onLogout={handleLogout}
            onUpdatePortalUrl={handleUpdatePortalUrl}
          />
        )}
      </View>

      {/* Floating Action Button in bottom right */}
      <TouchableOpacity
        style={styles.fab}
        onPress={handleOpenNewPost}
        activeOpacity={0.85}>
        <Plus size={24} color="#ffffff" strokeWidth={2.6} />
      </TouchableOpacity>

      {/* Post Creator / Editor Modal */}
      <AdminPostModal
        visible={postModalVisible}
        post={editingPost}
        categories={categories}
        portalUrl={portalUrl}
        onClose={() => setPostModalVisible(false)}
        onSuccess={handlePostSaved}
      />
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  safeArea: {
    flex: 1,
  },
  fab: {
    position: 'absolute',
    bottom: 50,
    right: 20,
    width: 56,
    height: 56,
    borderRadius: 28,
    backgroundColor: '#dc2626',
    alignItems: 'center',
    justifyContent: 'center',
    shadowColor: '#dc2626',
    shadowOffset: { width: 0, height: 6 },
    shadowOpacity: 0.4,
    shadowRadius: 10,
    elevation: 8,
    zIndex: 99,
  },
  loadingScreen: {
    flex: 1,
    justifyContent: 'center',
    alignItems: 'center',
    gap: 12,
  },
  loadingScreenText: {
    fontSize: 14,
    fontWeight: '600',
  },
  navTabsBar: {
    borderBottomWidth: 1,
  },
  navTabsScrollContent: {
    flexDirection: 'row',
    alignItems: 'center',
    paddingHorizontal: 12,
    paddingVertical: 8,
    gap: 8,
  },
  navTabBtn: {
    flexDirection: 'row',
    alignItems: 'center',
    paddingVertical: 7,
    paddingHorizontal: 12,
    borderRadius: 8,
  },
  navTabBtnActive: {
    backgroundColor: '#dc2626',
  },
  navTabText: {
    fontSize: 12,
    fontWeight: '700',
  },
  navTabTextActive: {
    color: '#ffffff',
  },
  mainContent: {
    flex: 1,
  },
});
