import React, { useState, useEffect } from 'react';
import {
  View,
  Text,
  StyleSheet,
  ScrollView,
  TouchableOpacity,
  Linking,
  StatusBar,
} from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import {
  Globe,
  ExternalLink,
  Laptop,
  Zap,
  Info,
  RefreshCw,
} from '@/components/ui/icons';
import { getItem, STORAGE_KEYS } from '@/utils/storage';
import { testConnection, PortalInfo, getDefaultPortalUrl } from '@/utils/api';
import { Spacing } from '@/constants/theme';
import { useTheme } from '@/hooks/use-theme';

export default function ExploreScreen() {
  const theme = useTheme();

  const [portalUrl, setPortalUrl] = useState('');
  const [portalInfo, setPortalInfo] = useState<PortalInfo | null>(null);
  const [checking, setChecking] = useState(false);

  const loadInfo = async () => {
    const url = (await getItem(STORAGE_KEYS.PORTAL_URL)) || getDefaultPortalUrl();
    setPortalUrl(url);
    setChecking(true);
    const res = await testConnection(url);
    setChecking(false);
    if (res.success && res.data) {
      setPortalInfo(res.data);
    }
  };

  useEffect(() => {
    loadInfo();
  }, []);

  const openWebsite = () => {
    if (portalUrl) {
      Linking.openURL(portalUrl).catch((err) => console.warn('Cannot open URL', err));
    }
  };

  const openAdminPanel = () => {
    if (portalUrl) {
      Linking.openURL(`${portalUrl}/admin`).catch((err) => console.warn('Cannot open URL', err));
    }
  };

  const openApiDoc = () => {
    if (portalUrl) {
      Linking.openURL(`${portalUrl}/api/v1/api.php?action=ping`).catch((err) =>
        console.warn('Cannot open URL', err)
      );
    }
  };

  return (
    <SafeAreaView style={[styles.container, { backgroundColor: theme.background }]}>
      <StatusBar barStyle="dark-content" backgroundColor="#ffffff" animated={true} />
      <ScrollView contentContainerStyle={styles.scrollContent}>
        <View style={styles.header}>
          <Text style={[styles.title, { color: theme.text }]}>Portal Tools & Links</Text>
          <Text style={[styles.subtitle, { color: theme.textSecondary }]}>
            Live web links, API diagnostics, and connection tips
          </Text>
        </View>

        {/* Portal Status Card */}
        <View
          style={[
            styles.card,
            { backgroundColor: theme.backgroundElement, borderColor: 'rgba(0,0,0,0.06)' },
          ]}>
          <View style={styles.cardHeader}>
            <View style={styles.statusDot} />
            <Text style={[styles.cardTitle, { color: theme.text }]}>
              {portalInfo?.site_name || 'News Portal'}
            </Text>
          </View>
          <Text style={[styles.urlText, { color: theme.textSecondary }]}>{portalUrl}</Text>

          <View style={[styles.detailsGrid, { borderTopColor: 'rgba(0,0,0,0.06)' }]}>
            <View style={styles.detailItem}>
              <Text style={[styles.detailLabel, { color: theme.textSecondary }]}>API Version</Text>
              <Text style={[styles.detailValue, { color: theme.text }]}>
                {portalInfo?.version || '1.0.0'}
              </Text>
            </View>
            <View style={styles.detailItem}>
              <Text style={[styles.detailLabel, { color: theme.textSecondary }]}>Accent Color</Text>
              <View style={styles.colorRow}>
                <View
                  style={[
                    styles.colorBox,
                    { backgroundColor: portalInfo?.theme_color || '#dc2626' },
                  ]}
                />
                <Text style={[styles.detailValue, { color: theme.text }]}>
                  {portalInfo?.theme_color || '#dc2626'}
                </Text>
              </View>
            </View>
          </View>

          <TouchableOpacity style={styles.refreshBtn} onPress={loadInfo}>
            <RefreshCw size={13} color={theme.text} style={{ marginRight: 6 }} />
            <Text style={[styles.refreshBtnText, { color: theme.text }]}>
              {checking ? 'Testing...' : 'Re-test Connection'}
            </Text>
          </TouchableOpacity>
        </View>

        {/* Quick Launch Actions */}
        <Text style={[styles.sectionTitle, { color: theme.text }]}>Quick Launch</Text>
        <View style={styles.actionsList}>
          <TouchableOpacity
            style={[
              styles.actionCard,
              { backgroundColor: theme.backgroundElement, borderColor: 'rgba(0,0,0,0.06)' },
            ]}
            onPress={openWebsite}>
            <View style={[styles.iconCircle, { backgroundColor: '#fee2e2' }]}>
              <Globe size={18} color="#dc2626" />
            </View>
            <View style={styles.actionInfo}>
              <Text style={[styles.actionTitle, { color: theme.text }]}>Public News Portal</Text>
              <Text style={[styles.actionDesc, { color: theme.textSecondary }]}>
                View live news website in browser ({portalUrl})
              </Text>
            </View>
            <ExternalLink size={16} color={theme.textSecondary} />
          </TouchableOpacity>

          <TouchableOpacity
            style={[
              styles.actionCard,
              { backgroundColor: theme.backgroundElement, borderColor: 'rgba(0,0,0,0.06)' },
            ]}
            onPress={openAdminPanel}>
            <View style={[styles.iconCircle, { backgroundColor: '#e0e7ff' }]}>
              <Laptop size={18} color="#4f46e5" />
            </View>
            <View style={styles.actionInfo}>
              <Text style={[styles.actionTitle, { color: theme.text }]}>PHP Admin Panel</Text>
              <Text style={[styles.actionDesc, { color: theme.textSecondary }]}>
                Open desktop admin dashboard in browser (/admin)
              </Text>
            </View>
            <ExternalLink size={16} color={theme.textSecondary} />
          </TouchableOpacity>

          <TouchableOpacity
            style={[
              styles.actionCard,
              { backgroundColor: theme.backgroundElement, borderColor: 'rgba(0,0,0,0.06)' },
            ]}
            onPress={openApiDoc}>
            <View style={[styles.iconCircle, { backgroundColor: '#dcfce7' }]}>
              <Zap size={18} color="#16a34a" />
            </View>
            <View style={styles.actionInfo}>
              <Text style={[styles.actionTitle, { color: theme.text }]}>REST API Health Endpoint</Text>
              <Text style={[styles.actionDesc, { color: theme.textSecondary }]}>
                Ping API v1 endpoint (/api/v1/api.php?action=ping)
              </Text>
            </View>
            <ExternalLink size={16} color={theme.textSecondary} />
          </TouchableOpacity>
        </View>

        {/* Tips */}
        <View
          style={[
            styles.tipCard,
            { backgroundColor: theme.backgroundElement, borderColor: 'rgba(0,0,0,0.06)' },
          ]}>
          <View style={styles.tipHeader}>
            <Info size={15} color="#2563eb" style={{ marginRight: 6 }} />
            <Text style={[styles.tipTitle, { color: theme.text }]}>Connection Tips</Text>
          </View>
          <Text style={[styles.tipText, { color: theme.textSecondary }]}>
            • Android Emulator: use <Text style={{ fontWeight: '700' }}>http://10.0.2.2/news</Text>
          </Text>
          <Text style={[styles.tipText, { color: theme.textSecondary }]}>
            • Physical Device: use your PC's Wi-Fi IP (e.g.{' '}
            <Text style={{ fontWeight: '700' }}>http://192.168.1.X/news</Text>)
          </Text>
          <Text style={[styles.tipText, { color: theme.textSecondary }]}>
            • Production: use your actual domain (e.g.{' '}
            <Text style={{ fontWeight: '700' }}>https://example.com/news</Text>)
          </Text>
        </View>
      </ScrollView>
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
  },
  scrollContent: {
    padding: Spacing.four,
    gap: 14,
    paddingBottom: 90,
    maxWidth: 600,
    width: '100%',
    alignSelf: 'center',
  },
  header: {
    marginBottom: 2,
  },
  title: {
    fontSize: 20,
    fontWeight: '800',
    letterSpacing: -0.3,
  },
  subtitle: {
    fontSize: 12,
    marginTop: 4,
  },
  card: {
    borderRadius: 16,
    padding: Spacing.four,
    borderWidth: 1,
  },
  cardHeader: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 8,
  },
  statusDot: {
    width: 8,
    height: 8,
    borderRadius: 4,
    backgroundColor: '#10b981',
  },
  cardTitle: {
    fontSize: 16,
    fontWeight: '800',
  },
  urlText: {
    fontSize: 12,
    marginTop: 4,
    fontWeight: '500',
  },
  detailsGrid: {
    flexDirection: 'row',
    gap: 20,
    marginTop: 14,
    paddingTop: 12,
    borderTopWidth: 1,
  },
  detailItem: {
    flex: 1,
  },
  detailLabel: {
    fontSize: 11,
    fontWeight: '600',
  },
  detailValue: {
    fontSize: 13,
    fontWeight: '700',
    marginTop: 2,
  },
  colorRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 6,
    marginTop: 2,
  },
  colorBox: {
    width: 14,
    height: 14,
    borderRadius: 3,
  },
  refreshBtn: {
    marginTop: 14,
    backgroundColor: 'rgba(0,0,0,0.04)',
    paddingVertical: 10,
    borderRadius: 8,
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
  },
  refreshBtnText: {
    fontSize: 12,
    fontWeight: '700',
  },
  sectionTitle: {
    fontSize: 15,
    fontWeight: '800',
    marginTop: 6,
  },
  actionsList: {
    gap: 10,
  },
  actionCard: {
    flexDirection: 'row',
    alignItems: 'center',
    padding: 12,
    borderRadius: 14,
    gap: 12,
    borderWidth: 1,
  },
  iconCircle: {
    width: 38,
    height: 38,
    borderRadius: 10,
    alignItems: 'center',
    justifyContent: 'center',
  },
  actionInfo: {
    flex: 1,
  },
  actionTitle: {
    fontSize: 13,
    fontWeight: '700',
  },
  actionDesc: {
    fontSize: 11,
    marginTop: 2,
  },
  tipCard: {
    borderRadius: 14,
    padding: 14,
    gap: 6,
    borderWidth: 1,
  },
  tipHeader: {
    flexDirection: 'row',
    alignItems: 'center',
    marginBottom: 4,
  },
  tipTitle: {
    fontSize: 13,
    fontWeight: '700',
  },
  tipText: {
    fontSize: 12,
    lineHeight: 18,
  },
});
