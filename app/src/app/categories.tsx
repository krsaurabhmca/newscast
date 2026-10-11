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
import { ArrowLeft, Layers } from '@/components/ui/icons';
import { AdminCategoriesTab } from '@/components/admin/AdminCategoriesTab';
import { CategoryItem, getCategories } from '@/utils/api';
import { getStoredPortalUrl } from '@/utils/storage';
import { useTheme } from '@/hooks/use-theme';
import { Spacing } from '@/constants/theme';

export default function CategoriesScreen() {
  const router = useRouter();
  const theme = useTheme();

  const [portalUrl, setPortalUrl] = useState('');
  const [categories, setCategories] = useState<CategoryItem[]>([]);
  const [loading, setLoading] = useState(true);

  const fetchCats = useCallback(async (url: string) => {
    try {
      const data = await getCategories(url);
      setCategories(data);
    } catch (err: any) {
      console.warn('Failed to load categories', err);
    } finally {
      setLoading(false);
    }
  }, []);

  useEffect(() => {
    getStoredPortalUrl().then((url: string | null) => {
      if (url) {
        setPortalUrl(url);
        fetchCats(url);
      }
    });
  }, [fetchCats]);

  const handleRefresh = () => {
    if (portalUrl) fetchCats(portalUrl);
  };

  return (
    <SafeAreaView style={[styles.safeArea, { backgroundColor: theme.background }]}>
      <StatusBar barStyle="dark-content" backgroundColor="#ffffff" animated={true} />

      {/* Minimal Header */}
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
          <Layers size={17} color="#2563eb" strokeWidth={2.4} />
          <Text style={[styles.title, { color: theme.text }]}>Categories</Text>
        </View>

        <View style={{ width: 36 }} />
      </View>

      {/* Full Screen Categories Content */}
      <View style={styles.content}>
        {loading ? (
          <View style={styles.centerBox}>
            <ActivityIndicator size="large" color="#dc2626" />
          </View>
        ) : (
          <AdminCategoriesTab
            categories={categories}
            portalUrl={portalUrl}
            onRefresh={handleRefresh}
          />
        )}
      </View>
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
  content: {
    flex: 1,
  },
  centerBox: {
    flex: 1,
    alignItems: 'center',
    justifyContent: 'center',
  },
});
