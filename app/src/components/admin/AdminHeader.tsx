import React from 'react';
import { View, Text, StyleSheet, TouchableOpacity, Linking, Alert } from 'react-native';
import { Newspaper, RefreshCw, Globe } from '@/components/ui/icons';
import { UserProfile, PortalInfo } from '@/utils/api';
import { Spacing } from '@/constants/theme';
import { useTheme } from '@/hooks/use-theme';

interface AdminHeaderProps {
  user: UserProfile;
  portalInfo?: PortalInfo | null;
  portalUrl: string;
  onRefresh: () => void;
  onLogout?: () => void;
  refreshing?: boolean;
}

export function AdminHeader({
  user,
  portalInfo,
  portalUrl,
  onRefresh,
  refreshing,
}: AdminHeaderProps) {
  const theme = useTheme();
  const siteName = portalInfo?.site_name || 'News Portal';

  const handleVisitSite = () => {
    if (!portalUrl) return;
    let url = portalUrl.trim();
    if (!url.startsWith('http://') && !url.startsWith('https://')) {
      const isLocal = /^(localhost|127\.0\.0\.1|10\.0\.2\.2|192\.168\.)/i.test(url);
      url = (isLocal ? 'http://' : 'https://') + url;
    }
    Linking.openURL(url).catch((err) => {
      Alert.alert('Error', 'Unable to open website: ' + (err.message || 'Unknown error'));
    });
  };

  return (
    <View
      style={[
        styles.headerContainer,
        { backgroundColor: theme.backgroundElement, borderBottomColor: 'rgba(0,0,0,0.06)' },
      ]}>
      <View style={styles.topRow}>
        {/* Brand & Connection Info */}
        <View style={styles.brandGroup}>
          <View style={styles.badgeIcon}>
            <Newspaper size={18} color="#ffffff" strokeWidth={2.4} />
          </View>
          <View style={{ flex: 1 }}>
            <View style={styles.titleRow}>
              <Text style={[styles.siteTitle, { color: theme.text }]} numberOfLines={1}>
                {siteName}
              </Text>
              <View style={styles.adminPill}>
                <Text style={styles.adminPillText}>{user.role.toUpperCase()}</Text>
              </View>
            </View>
            <TouchableOpacity
              style={styles.urlRow}
              onPress={handleVisitSite}
              activeOpacity={0.7}
              accessibilityLabel="Open Website URL">
              <View style={styles.onlineDot} />
              <Text style={[styles.urlText, { color: theme.textSecondary }]} numberOfLines={1}>
                {portalUrl.replace(/^https?:\/\//, '')}
              </Text>
            </TouchableOpacity>
          </View>
        </View>

        {/* Action Buttons */}
        <View style={styles.actionsGroup}>
          <TouchableOpacity
            style={[styles.actionBtn, { backgroundColor: theme.backgroundSelected }]}
            onPress={handleVisitSite}
            accessibilityLabel="Visit Website">
            <Globe size={15} color={theme.text} strokeWidth={2.2} />
          </TouchableOpacity>

          <TouchableOpacity
            style={[styles.actionBtn, { backgroundColor: theme.backgroundSelected }]}
            onPress={onRefresh}
            disabled={refreshing}
            accessibilityLabel="Refresh Data">
            <RefreshCw
              size={15}
              color={theme.text}
              strokeWidth={2.2}
              style={refreshing ? { transform: [{ rotate: '45deg' }] } : undefined}
            />
          </TouchableOpacity>
        </View>
      </View>

      {/* User Status Bar */}
      <View style={[styles.userStatusBar, { borderTopColor: 'rgba(0,0,0,0.05)' }]}>
        <View style={styles.userInfoRow}>
          <View style={styles.avatarCircle}>
            <Text style={styles.avatarText}>
              {user.username.charAt(0).toUpperCase()}
            </Text>
          </View>
          <Text style={[styles.welcomeText, { color: theme.text }]}>
            Signed in as <Text style={{ fontWeight: '700' }}>{user.username}</Text>
          </Text>
        </View>
        <Text style={[styles.dateText, { color: theme.textSecondary }]}>
          {new Date().toLocaleDateString(undefined, {
            month: 'short',
            day: 'numeric',
            year: 'numeric',
          })}
        </Text>
      </View>
    </View>
  );
}

const styles = StyleSheet.create({
  headerContainer: {
    paddingTop: Spacing.two,
    paddingHorizontal: Spacing.four,
    paddingBottom: 8,
    borderBottomWidth: 1,
  },
  topRow: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    marginBottom: 8,
  },
  brandGroup: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 10,
    flex: 1,
  },
  badgeIcon: {
    width: 36,
    height: 36,
    borderRadius: 10,
    backgroundColor: '#dc2626',
    alignItems: 'center',
    justifyContent: 'center',
  },
  titleRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 6,
  },
  siteTitle: {
    fontSize: 15,
    fontWeight: '800',
    letterSpacing: -0.2,
    maxWidth: 180,
  },
  adminPill: {
    backgroundColor: '#dc262615',
    paddingHorizontal: 6,
    paddingVertical: 1,
    borderRadius: 4,
  },
  adminPillText: {
    color: '#dc2626',
    fontSize: 9,
    fontWeight: '800',
    letterSpacing: 0.5,
  },
  urlRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 4,
    marginTop: 2,
  },
  onlineDot: {
    width: 6,
    height: 6,
    borderRadius: 3,
    backgroundColor: '#10b981',
  },
  urlText: {
    fontSize: 11,
    fontWeight: '500',
    maxWidth: 220,
  },
  actionsGroup: {
    flexDirection: 'row',
    gap: 8,
    alignItems: 'center',
  },
  actionBtn: {
    width: 34,
    height: 34,
    borderRadius: 9,
    alignItems: 'center',
    justifyContent: 'center',
  },
  logoutBtn: {
    backgroundColor: '#fee2e2',
  },
  userStatusBar: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    paddingTop: 6,
    borderTopWidth: 1,
  },
  userInfoRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 6,
  },
  avatarCircle: {
    width: 20,
    height: 20,
    borderRadius: 10,
    backgroundColor: '#0f172a',
    alignItems: 'center',
    justifyContent: 'center',
  },
  avatarText: {
    color: '#fff',
    fontSize: 10,
    fontWeight: '700',
  },
  welcomeText: {
    fontSize: 11,
  },
  dateText: {
    fontSize: 11,
    fontWeight: '500',
  },
});
