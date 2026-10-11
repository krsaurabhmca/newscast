import React, { useState, useEffect } from 'react';
import {
  View,
  Text,
  TextInput,
  TouchableOpacity,
  StyleSheet,
  ActivityIndicator,
  ScrollView,
  KeyboardAvoidingView,
  Platform,
} from 'react-native';
import {
  Globe,
  Lock,
  User,
  Eye,
  EyeOff,
  CheckCircle2,
  AlertCircle,
  Newspaper,
  ShieldCheck,
  Server,
  ArrowRight,
} from '@/components/ui/icons';
import { getItem, setItem, STORAGE_KEYS } from '@/utils/storage';
import {
  loginUser,
  testConnection,
  normalizePortalUrl,
  getDefaultPortalUrl,
  UserProfile,
  PortalInfo,
} from '@/utils/api';
import { Spacing } from '@/constants/theme';
import { useTheme } from '@/hooks/use-theme';

interface AdminLoginProps {
  onLoginSuccess: (user: UserProfile, portalUrl: string, portalInfo?: PortalInfo) => void;
}

export function AdminLogin({ onLoginSuccess }: AdminLoginProps) {
  const theme = useTheme();

  const [portalUrl, setPortalUrl] = useState('');
  const [identifier, setIdentifier] = useState('');
  const [password, setPassword] = useState('');
  const [showPassword, setShowPassword] = useState(false);

  const [loading, setLoading] = useState(false);
  const [testingConnection, setTestingConnection] = useState(false);
  const [connectionStatus, setConnectionStatus] = useState<{
    tested: boolean;
    success: boolean;
    message: string;
    siteName?: string;
  }>({ tested: false, success: false, message: '' });

  const [errorMessage, setErrorMessage] = useState('');

  // Load stored website URL on mount
  useEffect(() => {
    async function loadStored() {
      const storedUrl = await getItem(STORAGE_KEYS.PORTAL_URL);
      if (storedUrl) {
        setPortalUrl(storedUrl);
        runTestConnection(storedUrl);
      } else {
        const defaultUrl = getDefaultPortalUrl();
        setPortalUrl(defaultUrl);
        runTestConnection(defaultUrl);
      }
    }
    loadStored();
  }, []);

  const runTestConnection = async (urlToTest?: string) => {
    const url = normalizePortalUrl(urlToTest || portalUrl);
    if (!url) {
      setConnectionStatus({ tested: true, success: false, message: 'Please enter a website URL' });
      return;
    }

    setTestingConnection(true);
    setConnectionStatus({ tested: false, success: false, message: 'Connecting...' });

    const result = await testConnection(url);
    setTestingConnection(false);

    if (result.success) {
      await setItem(STORAGE_KEYS.PORTAL_URL, url);
      setConnectionStatus({
        tested: true,
        success: true,
        message: 'Portal online & reachable',
        siteName: result.data?.site_name,
      });
    } else {
      setConnectionStatus({
        tested: true,
        success: false,
        message: result.message || 'Unable to connect to portal',
      });
    }
  };

  const handleLogin = async () => {
    setErrorMessage('');
    const cleanUrl = normalizePortalUrl(portalUrl);

    if (!cleanUrl) {
      setErrorMessage('Please enter the News Portal Website URL');
      return;
    }

    if (!identifier.trim()) {
      setErrorMessage('Please enter your username or email');
      return;
    }

    if (!password) {
      setErrorMessage('Please enter your password');
      return;
    }

    setLoading(true);
    try {
      await setItem(STORAGE_KEYS.PORTAL_URL, cleanUrl);

      const result = await loginUser(cleanUrl, identifier.trim(), password);
      setLoading(false);

      if (result.success && result.user) {
        onLoginSuccess(result.user, cleanUrl, result.portal);
      } else {
        setErrorMessage(result.message || 'Invalid username or password');
      }
    } catch (err: any) {
      setLoading(false);
      setErrorMessage(err.message || 'Connection failed. Please check the website address.');
    }
  };

  const applyPreset = (presetUrl: string) => {
    setPortalUrl(presetUrl);
    runTestConnection(presetUrl);
  };

  return (
    <KeyboardAvoidingView
      behavior={Platform.OS === 'ios' ? 'padding' : undefined}
      style={[styles.container, { backgroundColor: theme.background }]}>
      <ScrollView contentContainerStyle={styles.scrollContent} keyboardShouldPersistTaps="handled">
        {/* Brand Header */}
        <View style={styles.brandBox}>
          <View style={styles.logoBadge}>
            <Newspaper size={30} color="#ffffff" strokeWidth={2.2} />
          </View>
          <Text style={[styles.appTitle, { color: theme.text }]}>NewsCast Admin</Text>
          <Text style={[styles.appSubtitle, { color: theme.textSecondary }]}>
            Enterprise Content Management System
          </Text>
        </View>

        {/* Card */}
        <View
          style={[
            styles.card,
            { backgroundColor: theme.backgroundElement, borderColor: 'rgba(0,0,0,0.06)' },
          ]}>
          {/* Section: Website URL Input */}
          <View style={styles.sectionHeader}>
            <View style={styles.sectionLabelRow}>
              <Globe size={16} color="#dc2626" />
              <Text style={[styles.sectionTitle, { color: theme.text }]}>Portal Website URL</Text>
            </View>
            <View style={styles.storeBadge}>
              <CheckCircle2 size={11} color="#059669" />
              <Text style={styles.storeBadgeText}>Stored Permanently</Text>
            </View>
          </View>

          <Text style={[styles.fieldHint, { color: theme.textSecondary }]}>
            Configured once and saved locally. You won't need to re-enter this on next launch.
          </Text>

          <View style={styles.urlInputRow}>
            <View
              style={[
                styles.inputWrapper,
                {
                  backgroundColor: theme.backgroundSelected,
                  borderColor: connectionStatus.tested
                    ? connectionStatus.success
                      ? '#10b981'
                      : '#ef4444'
                    : 'transparent',
                },
              ]}>
              <Server size={16} color={theme.textSecondary} style={{ marginRight: 8 }} />
              <TextInput
                style={[styles.inputInner, { color: theme.text }]}
                placeholder="http://localhost/news"
                placeholderTextColor={theme.textSecondary}
                value={portalUrl}
                onChangeText={(text) => {
                  setPortalUrl(text);
                  setConnectionStatus({ tested: false, success: false, message: '' });
                }}
                autoCapitalize="none"
                autoCorrect={false}
              />
            </View>

            <TouchableOpacity
              style={[styles.testButton, testingConnection && styles.buttonDisabled]}
              onPress={() => runTestConnection()}
              disabled={testingConnection}>
              {testingConnection ? (
                <ActivityIndicator size="small" color="#fff" />
              ) : (
                <Text style={styles.testButtonText}>Ping</Text>
              )}
            </TouchableOpacity>
          </View>

          {/* Connection status indicator */}
          {connectionStatus.tested && (
            <View
              style={[
                styles.statusBanner,
                {
                  backgroundColor: connectionStatus.success ? '#ecfdf5' : '#fef2f2',
                  borderColor: connectionStatus.success ? '#a7f3d0' : '#fecaca',
                },
              ]}>
              {connectionStatus.success ? (
                <CheckCircle2 size={15} color="#059669" style={{ marginRight: 6 }} />
              ) : (
                <AlertCircle size={15} color="#dc2626" style={{ marginRight: 6 }} />
              )}
              <Text
                style={[
                  styles.statusBannerText,
                  { color: connectionStatus.success ? '#065f46' : '#991b1b' },
                ]}>
                {connectionStatus.success && connectionStatus.siteName
                  ? `${connectionStatus.siteName} — ${connectionStatus.message}`
                  : connectionStatus.message}
              </Text>
            </View>
          )}

          {/* Presets */}
          <View style={styles.presetsRow}>
            <Text style={[styles.presetLabel, { color: theme.textSecondary }]}>Presets:</Text>
            <TouchableOpacity
              style={styles.presetChip}
              onPress={() => applyPreset('http://localhost/news')}>
              <Text style={styles.presetChipText}>Localhost</Text>
            </TouchableOpacity>
            <TouchableOpacity
              style={styles.presetChip}
              onPress={() => applyPreset('http://10.0.2.2/news')}>
              <Text style={styles.presetChipText}>Android Emulator (10.0.2.2)</Text>
            </TouchableOpacity>
          </View>

          <View style={styles.divider} />

          {/* Section: Admin Authentication */}
          <View style={styles.sectionLabelRow}>
            <ShieldCheck size={16} color="#dc2626" />
            <Text style={[styles.sectionTitle, { color: theme.text }]}>Admin Credentials</Text>
          </View>

          {errorMessage ? (
            <View style={styles.errorBox}>
              <AlertCircle size={16} color="#dc2626" style={{ marginRight: 6 }} />
              <Text style={styles.errorText}>{errorMessage}</Text>
            </View>
          ) : null}

          {/* Username / Email */}
          <Text style={[styles.inputLabel, { color: theme.text }]}>Username or Email</Text>
          <View style={[styles.inputWrapper, { backgroundColor: theme.backgroundSelected }]}>
            <User size={16} color={theme.textSecondary} style={{ marginRight: 8 }} />
            <TextInput
              style={[styles.inputInner, { color: theme.text }]}
              placeholder="e.g. admin or offerplant"
              placeholderTextColor={theme.textSecondary}
              value={identifier}
              onChangeText={setIdentifier}
              autoCapitalize="none"
              autoCorrect={false}
            />
          </View>

          {/* Password */}
          <Text style={[styles.inputLabel, { color: theme.text }]}>Password</Text>
          <View style={[styles.inputWrapper, { backgroundColor: theme.backgroundSelected }]}>
            <Lock size={16} color={theme.textSecondary} style={{ marginRight: 8 }} />
            <TextInput
              style={[styles.inputInner, { color: theme.text }]}
              placeholder="••••••••"
              placeholderTextColor={theme.textSecondary}
              value={password}
              onChangeText={setPassword}
              secureTextEntry={!showPassword}
              autoCapitalize="none"
              autoCorrect={false}
            />
            <TouchableOpacity onPress={() => setShowPassword(!showPassword)} style={{ padding: 4 }}>
              {showPassword ? (
                <EyeOff size={18} color={theme.textSecondary} />
              ) : (
                <Eye size={18} color={theme.textSecondary} />
              )}
            </TouchableOpacity>
          </View>

          {/* Submit Button */}
          <TouchableOpacity
            style={[styles.loginButton, loading && styles.buttonDisabled]}
            onPress={handleLogin}
            disabled={loading}>
            {loading ? (
              <ActivityIndicator size="small" color="#fff" />
            ) : (
              <View style={styles.btnContent}>
                <Text style={styles.loginButtonText}>Sign In to Dashboard</Text>
                <ArrowRight size={18} color="#fff" />
              </View>
            )}
          </TouchableOpacity>
        </View>

        <View style={styles.footerInfo}>
          <Text style={[styles.footerText, { color: theme.textSecondary }]}>
            Powered by NewsCast REST API Engine
          </Text>
        </View>
      </ScrollView>
    </KeyboardAvoidingView>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
  },
  scrollContent: {
    padding: Spacing.four,
    maxWidth: 500,
    width: '100%',
    alignSelf: 'center',
    justifyContent: 'center',
    paddingVertical: Spacing.five,
  },
  brandBox: {
    alignItems: 'center',
    marginBottom: Spacing.four,
  },
  logoBadge: {
    width: 60,
    height: 60,
    borderRadius: 18,
    backgroundColor: '#dc2626',
    alignItems: 'center',
    justifyContent: 'center',
    marginBottom: 12,
    shadowColor: '#dc2626',
    shadowOffset: { width: 0, height: 6 },
    shadowOpacity: 0.35,
    shadowRadius: 10,
    elevation: 8,
  },
  appTitle: {
    fontSize: 22,
    fontWeight: '800',
    letterSpacing: -0.4,
  },
  appSubtitle: {
    fontSize: 13,
    marginTop: 4,
    textAlign: 'center',
  },
  card: {
    borderRadius: 20,
    padding: Spacing.four,
    borderWidth: 1,
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 4 },
    shadowOpacity: 0.05,
    shadowRadius: 14,
    elevation: 4,
  },
  sectionHeader: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    marginBottom: 6,
  },
  sectionLabelRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 6,
  },
  sectionTitle: {
    fontSize: 14,
    fontWeight: '700',
  },
  storeBadge: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 4,
    backgroundColor: '#10b98115',
    paddingHorizontal: 8,
    paddingVertical: 3,
    borderRadius: 8,
  },
  storeBadgeText: {
    color: '#059669',
    fontSize: 11,
    fontWeight: '700',
  },
  fieldHint: {
    fontSize: 12,
    marginBottom: 10,
    lineHeight: 16,
  },
  urlInputRow: {
    flexDirection: 'row',
    gap: 8,
    alignItems: 'center',
  },
  inputWrapper: {
    flex: 1,
    flexDirection: 'row',
    alignItems: 'center',
    borderRadius: 12,
    paddingHorizontal: 12,
    height: 46,
    borderWidth: 1.5,
    borderColor: 'transparent',
  },
  inputInner: {
    flex: 1,
    fontSize: 14,
    height: '100%',
  },
  testButton: {
    backgroundColor: '#0f172a',
    height: 46,
    paddingHorizontal: 16,
    borderRadius: 12,
    justifyContent: 'center',
    alignItems: 'center',
    minWidth: 64,
  },
  testButtonText: {
    color: '#fff',
    fontWeight: '700',
    fontSize: 13,
  },
  statusBanner: {
    flexDirection: 'row',
    alignItems: 'center',
    marginTop: 8,
    paddingHorizontal: 10,
    paddingVertical: 8,
    borderRadius: 10,
    borderWidth: 1,
  },
  statusBannerText: {
    fontSize: 12,
    fontWeight: '600',
    flex: 1,
  },
  presetsRow: {
    flexDirection: 'row',
    alignItems: 'center',
    flexWrap: 'wrap',
    gap: 6,
    marginTop: 10,
  },
  presetLabel: {
    fontSize: 11,
    fontWeight: '600',
    marginRight: 2,
  },
  presetChip: {
    backgroundColor: '#0f172a0a',
    paddingHorizontal: 8,
    paddingVertical: 4,
    borderRadius: 6,
  },
  presetChipText: {
    color: '#334155',
    fontSize: 11,
    fontWeight: '600',
  },
  divider: {
    height: 1,
    backgroundColor: 'rgba(0,0,0,0.06)',
    marginVertical: 14,
  },
  errorBox: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: '#fef2f2',
    borderWidth: 1,
    borderColor: '#fca5a5',
    padding: 10,
    borderRadius: 10,
    marginTop: 10,
  },
  errorText: {
    color: '#dc2626',
    fontSize: 12,
    fontWeight: '600',
    flex: 1,
  },
  inputLabel: {
    fontSize: 12,
    fontWeight: '700',
    marginBottom: 6,
    marginTop: 12,
  },
  loginButton: {
    backgroundColor: '#dc2626',
    borderRadius: 12,
    height: 48,
    alignItems: 'center',
    justifyContent: 'center',
    marginTop: Spacing.four,
    shadowColor: '#dc2626',
    shadowOffset: { width: 0, height: 4 },
    shadowOpacity: 0.25,
    shadowRadius: 8,
    elevation: 4,
  },
  btnContent: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 8,
  },
  loginButtonText: {
    color: '#ffffff',
    fontSize: 14,
    fontWeight: '700',
    letterSpacing: 0.2,
  },
  buttonDisabled: {
    opacity: 0.6,
  },
  footerInfo: {
    alignItems: 'center',
    marginTop: Spacing.four,
  },
  footerText: {
    fontSize: 12,
    fontWeight: '500',
  },
});
