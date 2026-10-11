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
  isValidPortalUrl,
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
    const raw = (urlToTest || portalUrl).trim();
    if (!raw) {
      setConnectionStatus({ tested: true, success: false, message: 'Please enter a website URL' });
      return;
    }

    if (!isValidPortalUrl(raw)) {
      setConnectionStatus({
        tested: true,
        success: false,
        message: 'Invalid URL. Example: https://yourwebsite.com/news',
      });
      return;
    }

    const url = normalizePortalUrl(raw);
    setPortalUrl(url);
    setTestingConnection(true);
    setConnectionStatus({ tested: false, success: false, message: 'Connecting...' });

    const result = await testConnection(url);
    setTestingConnection(false);

    const finalUrl = result.resolvedUrl || url;
    setPortalUrl(finalUrl);

    if (result.success) {
      await setItem(STORAGE_KEYS.PORTAL_URL, finalUrl);
      setConnectionStatus({
        tested: true,
        success: true,
        message: 'Connected',
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
    const raw = portalUrl.trim();
    if (!raw) {
      setErrorMessage('Please enter the News Portal Website URL');
      return;
    }

    if (!isValidPortalUrl(raw)) {
      setErrorMessage('Invalid URL. Example: https://yourwebsite.com/news');
      return;
    }

    const cleanUrl = normalizePortalUrl(raw);

    if (!identifier.trim()) {
      setErrorMessage('Please enter your username or email');
      return;
    }

    if (!password) {
      setErrorMessage('Please enter your password');
      return;
    }

    setPortalUrl(cleanUrl);
    setLoading(true);
    try {
      const result = await loginUser(cleanUrl, identifier.trim(), password);
      setLoading(false);

      const finalUrl = result.resolvedUrl || cleanUrl;
      setPortalUrl(finalUrl);
      await setItem(STORAGE_KEYS.PORTAL_URL, finalUrl);

      if (result.success && result.user) {
        onLoginSuccess(result.user, finalUrl, result.portal);
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
      behavior={Platform.OS === 'ios' ? 'padding' : 'height'}
      style={[styles.container, { backgroundColor: theme.background }]}
      keyboardVerticalOffset={Platform.OS === 'ios' ? 0 : 25}>
      <ScrollView
        contentContainerStyle={styles.scrollContent}
        keyboardShouldPersistTaps="handled"
        showsVerticalScrollIndicator={false}>
        {/* Brand Header */}
        <View style={styles.brandBox}>
          <View style={styles.logoBadge}>
            <Newspaper size={30} color="#ffffff" strokeWidth={2.2} />
          </View>
          <Text style={[styles.appTitle, { color: theme.text }]}>NewsCast Admin</Text>
          <Text style={[styles.appSubtitle, { color: theme.textSecondary }]}>
            Digital News Management System
          </Text>
        </View>

        {/* Card */}
        <View
          style={[
            styles.card,
            { backgroundColor: theme.backgroundElement, borderColor: 'rgba(0,0,0,0.06)' },
          ]}>
          {errorMessage ? (
            <View style={styles.errorBox}>
              <AlertCircle size={16} color="#dc2626" style={{ marginRight: 6 }} />
              <Text style={styles.errorText}>{errorMessage}</Text>
            </View>
          ) : null}

          {/* 1. Portal URL */}
          <View style={styles.fieldHeader}>
            <Text style={[styles.inputLabel, { color: theme.text }]}>Portal Website URL</Text>
            {connectionStatus.tested && connectionStatus.success ? (
              <View style={styles.inlineStatusBadge}>
                <CheckCircle2 size={12} color="#059669" />
                <Text style={styles.inlineStatusSuccessText}>
                  {connectionStatus.siteName || 'Connected'}
                </Text>
              </View>
            ) : null}
          </View>
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
            <Globe
              size={16}
              color={connectionStatus.tested && connectionStatus.success ? '#059669' : theme.textSecondary}
              style={{ marginRight: 8 }}
            />
            <TextInput
              style={[styles.inputInner, { color: theme.text }]}
              placeholder="https://example.com/news"
              placeholderTextColor={theme.textSecondary}
              value={portalUrl}
              onChangeText={(text) => {
                setPortalUrl(text);
                setConnectionStatus({ tested: false, success: false, message: '' });
              }}
              onBlur={() => {
                if (portalUrl.trim()) {
                  const norm = normalizePortalUrl(portalUrl);
                  if (norm !== portalUrl) setPortalUrl(norm);
                }
              }}
              autoCapitalize="none"
              autoCorrect={false}
            />
            <TouchableOpacity
              style={[
                styles.inlineTestBtn,
                connectionStatus.tested && connectionStatus.success
                  ? styles.inlineTestBtnConnected
                  : styles.inlineTestBtnDefault,
                testingConnection && styles.buttonDisabled,
              ]}
              onPress={() => runTestConnection()}
              disabled={testingConnection}>
              {testingConnection ? (
                <ActivityIndicator size="small" color="#fff" />
              ) : connectionStatus.tested && connectionStatus.success ? (
                <Text style={styles.inlineTestBtnConnectedText}>Verified</Text>
              ) : (
                <Text style={styles.inlineTestBtnDefaultText}>Test</Text>
              )}
            </TouchableOpacity>
          </View>

          {connectionStatus.tested && !connectionStatus.success && connectionStatus.message ? (
            <View style={styles.fieldErrorRow}>
              <AlertCircle size={12} color="#dc2626" style={{ marginRight: 4 }} />
              <Text style={styles.fieldErrorText}>{connectionStatus.message}</Text>
            </View>
          ) : null}

          {/* 2. Username / Email */}
          <Text style={[styles.inputLabel, { color: theme.text, marginTop: 14 }]}>
            Username or Email
          </Text>
          <View style={[styles.inputWrapper, { backgroundColor: theme.backgroundSelected }]}>
            <User size={16} color={theme.textSecondary} style={{ marginRight: 8 }} />
            <TextInput
              style={[styles.inputInner, { color: theme.text }]}
              placeholder="e.g. admin or editor"
              placeholderTextColor={theme.textSecondary}
              value={identifier}
              onChangeText={setIdentifier}
              autoCapitalize="none"
              autoCorrect={false}
            />
          </View>

          {/* 3. Password */}
          <Text style={[styles.inputLabel, { color: theme.text, marginTop: 14 }]}>Password</Text>
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
            Planted by OfferPlant
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
  fieldHeader: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    marginBottom: 6,
  },
  inlineStatusBadge: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 4,
  },
  inlineStatusSuccessText: {
    color: '#059669',
    fontSize: 11.5,
    fontWeight: '700',
  },
  inputWrapper: {
    flexDirection: 'row',
    alignItems: 'center',
    borderRadius: 12,
    paddingHorizontal: 12,
    height: 48,
    borderWidth: 1.5,
    borderColor: 'transparent',
  },
  inputInner: {
    flex: 1,
    fontSize: 14,
    height: '100%',
  },
  inlineTestBtn: {
    paddingHorizontal: 12,
    paddingVertical: 6,
    borderRadius: 8,
    alignItems: 'center',
    justifyContent: 'center',
  },
  inlineTestBtnDefault: {
    backgroundColor: '#0f172a',
  },
  inlineTestBtnDefaultText: {
    color: '#ffffff',
    fontSize: 12,
    fontWeight: '700',
  },
  inlineTestBtnConnected: {
    backgroundColor: '#dcfce7',
  },
  inlineTestBtnConnectedText: {
    color: '#15803d',
    fontSize: 11.5,
    fontWeight: '700',
  },
  fieldErrorRow: {
    flexDirection: 'row',
    alignItems: 'center',
    marginTop: 6,
    paddingHorizontal: 4,
  },
  fieldErrorText: {
    color: '#dc2626',
    fontSize: 11.5,
    fontWeight: '600',
  },
  errorBox: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: '#fef2f2',
    borderWidth: 1,
    borderColor: '#fca5a5',
    padding: 10,
    borderRadius: 10,
    marginBottom: 12,
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
