import React, { useEffect, useState } from 'react';
import {
  View,
  Text,
  StyleSheet,
  TouchableOpacity,
  StatusBar,
} from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { useRouter } from 'expo-router';
import { ArrowLeft, BarChart3, RefreshCw } from '@/components/ui/icons';
import { AdminPollsTab } from '@/components/admin/AdminPollsTab';
import { getStoredPortalUrl } from '@/utils/storage';
import { useTheme } from '@/hooks/use-theme';
import { Spacing } from '@/constants/theme';

export default function PollsScreen() {
  const router = useRouter();
  const theme = useTheme();
  const [portalUrl, setPortalUrl] = useState('');

  useEffect(() => {
    getStoredPortalUrl().then((url: string | null) => {
      if (url) setPortalUrl(url);
    });
  }, []);

  return (
    <SafeAreaView style={[styles.safeArea, { backgroundColor: theme.background }]}>
      <StatusBar barStyle="dark-content" backgroundColor="#ffffff" animated={true} />

      {/* Dedicated Clean Minimal Header */}
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
          <BarChart3 size={17} color="#dc2626" strokeWidth={2.4} />
          <Text style={[styles.title, { color: theme.text }]}>Public Polls</Text>
        </View>

        <View style={{ width: 36 }} />
      </View>

      {/* Full Screen Polls Content */}
      <View style={styles.content}>
        {portalUrl ? (
          <AdminPollsTab portalUrl={portalUrl} />
        ) : null}
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
});
