import React, { useEffect, useState, useCallback } from 'react';
import {
  View,
  Text,
  StyleSheet,
  TouchableOpacity,
  StatusBar,
  ActivityIndicator,
} from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { useRouter } from 'expo-router';
import { ArrowLeft, FileText, Plus } from '@/components/ui/icons';
import { AdminPostsTab } from '@/components/admin/AdminPostsTab';
import { AdminPostModal } from '@/components/admin/AdminPostModal';
import { PostItem, CategoryItem, getCategories } from '@/utils/api';
import { getStoredPortalUrl } from '@/utils/storage';
import { useTheme } from '@/hooks/use-theme';
import { Spacing } from '@/constants/theme';

export default function ArticlesScreen() {
  const router = useRouter();
  const theme = useTheme();

  const [portalUrl, setPortalUrl] = useState('');
  const [categories, setCategories] = useState<CategoryItem[]>([]);
  const [loading, setLoading] = useState(true);

  // Editor Modal
  const [postModalVisible, setPostModalVisible] = useState(false);
  const [editingPost, setEditingPost] = useState<PostItem | null>(null);
  const [refreshKey, setRefreshKey] = useState(0);

  const initData = useCallback(async (url: string) => {
    try {
      const cats = await getCategories(url);
      setCategories(cats);
    } catch (e) {
      console.warn('Failed to load categories', e);
    } finally {
      setLoading(false);
    }
  }, []);

  useEffect(() => {
    getStoredPortalUrl().then((url: string | null) => {
      if (url) {
        setPortalUrl(url);
        initData(url);
      }
    });
  }, [initData]);

  const handleOpenNewPost = () => {
    setEditingPost(null);
    setPostModalVisible(true);
  };

  const handleOpenEditPost = (post: PostItem) => {
    setEditingPost(post);
    setPostModalVisible(true);
  };

  const handlePostSaved = () => {
    setPostModalVisible(false);
    setEditingPost(null);
    setRefreshKey((k) => k + 1);
  };

  return (
    <SafeAreaView style={[styles.safeArea, { backgroundColor: theme.background }]}>
      <StatusBar barStyle="dark-content" backgroundColor="#ffffff" animated={true} />

      {/* Minimal Clean Header - No screen space wastage */}
      <View
        style={[
          styles.header,
          { backgroundColor: theme.backgroundElement, borderBottomColor: 'rgba(0,0,0,0.06)' },
        ]}>
        <TouchableOpacity
          style={[styles.backBtn, { backgroundColor: theme.backgroundSelected }]}
          onPress={() => router.back()}
          hitSlop={{ top: 10, bottom: 10, left: 10, right: 10 }}>
          <ArrowLeft size={18} color={theme.text} strokeWidth={2.4} />
        </TouchableOpacity>

        <View style={styles.titleContainer}>
          <FileText size={17} color="#dc2626" strokeWidth={2.4} />
          <Text style={[styles.title, { color: theme.text }]}>Manage Articles</Text>
        </View>

        <TouchableOpacity style={styles.newBtn} onPress={handleOpenNewPost}>
          <Plus size={14} color="#ffffff" strokeWidth={2.5} style={{ marginRight: 4 }} />
          <Text style={styles.newBtnText}>New</Text>
        </TouchableOpacity>
      </View>

      {/* Full Screen Articles Content */}
      <View style={styles.content}>
        {loading ? (
          <View style={styles.centerBox}>
            <ActivityIndicator size="large" color="#dc2626" />
          </View>
        ) : (
          <AdminPostsTab
            key={refreshKey}
            portalUrl={portalUrl}
            categories={categories}
            onOpenNewPost={handleOpenNewPost}
            onEditPost={handleOpenEditPost}
          />
        )}
      </View>

      {/* Floating Action Button (FAB) moved higher up */}
      <TouchableOpacity
        style={styles.fab}
        onPress={handleOpenNewPost}
        activeOpacity={0.85}>
        <Plus size={24} color="#ffffff" strokeWidth={2.6} />
      </TouchableOpacity>

      {/* Post Modal */}
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
  header: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    paddingHorizontal: Spacing.four,
    paddingVertical: 10,
    borderBottomWidth: 1,
  },
  backBtn: {
    width: 36,
    height: 36,
    borderRadius: 18,
    alignItems: 'center',
    justifyContent: 'center',
  },
  titleContainer: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 8,
  },
  title: {
    fontSize: 16,
    fontWeight: '800',
  },
  newBtn: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: '#dc2626',
    paddingHorizontal: 12,
    paddingVertical: 6,
    borderRadius: 8,
  },
  newBtnText: {
    color: '#ffffff',
    fontSize: 12,
    fontWeight: '700',
  },
  content: {
    flex: 1,
  },
  centerBox: {
    flex: 1,
    alignItems: 'center',
    justifyContent: 'center',
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
});
