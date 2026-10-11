import React, { useState, useEffect } from 'react';
import {
  View,
  Text,
  TextInput,
  TouchableOpacity,
  ScrollView,
  StyleSheet,
  ActivityIndicator,
  Switch,
  Alert,
} from 'react-native';
import {
  Globe,
  Settings,
  LogOut,
  Save,
  CheckCircle2,
  AlertCircle,
  Radio,
  Zap,
  Mail,
  Phone,
  Server,
  Lock,
  Eye,
  EyeOff,
  Check,
} from '@/components/ui/icons';
import {
  PortalSettings,
  UserProfile,
  getSettings,
  updateSettings,
  testConnection,
  normalizePortalUrl,
  isValidPortalUrl,
  changePassword,
} from '@/utils/api';
import { getItem, setItem, STORAGE_KEYS } from '@/utils/storage';
import { Spacing } from '@/constants/theme';
import { useTheme } from '@/hooks/use-theme';

export const THEME_COLOR_PALETTE = [
  { name: 'Red Flagship', hex: '#dc2626' },
  { name: 'Rose Red', hex: '#e11d48' },
  { name: 'Bold Crimson', hex: '#b91c1c' },
  { name: 'Burnt Orange', hex: '#ea580c' },
  { name: 'Amber Gold', hex: '#d97706' },
  { name: 'Warm Yellow', hex: '#ca8a04' },
  { name: 'Emerald Green', hex: '#059669' },
  { name: 'Forest Green', hex: '#16a34a' },
  { name: 'Teal Cyan', hex: '#0d9488' },
  { name: 'Sky Blue', hex: '#0284c7' },
  { name: 'Royal Blue', hex: '#2563eb' },
  { name: 'Deep Indigo', hex: '#4f46e5' },
  { name: 'Electric Violet', hex: '#7c3aed' },
  { name: 'Purple Majesty', hex: '#9333ea' },
  { name: 'Slate Steel', hex: '#475569' },
  { name: 'Midnight Charcoal', hex: '#0f172a' },
];

interface AdminSettingsTabProps {
  portalUrl: string;
  currentUser?: UserProfile | null;
  onLogout: () => void;
  onUpdatePortalUrl: (newUrl: string) => void;
}

export function AdminSettingsTab({
  portalUrl,
  currentUser,
  onLogout,
  onUpdatePortalUrl,
}: AdminSettingsTabProps) {
  const theme = useTheme();

  const [settings, setSettings] = useState<PortalSettings>({});
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);
  const [user, setUser] = useState<UserProfile | null>(currentUser || null);

  // Stored website URL configuration
  const [currentUrl, setCurrentUrl] = useState(portalUrl);
  const [testingUrl, setTestingUrl] = useState(false);
  const [urlStatus, setUrlStatus] = useState<{ success: boolean; message: string } | null>(null);

  // Editable settings fields
  const [siteName, setSiteName] = useState('');
  const [siteTagline, setSiteTagline] = useState('');
  const [themeColor, setThemeColor] = useState('#dc2626');
  const [showColorList, setShowColorList] = useState(false);
  const [contactEmail, setContactEmail] = useState('');
  const [contactPhone, setContactPhone] = useState('');
  const [liveYoutubeUrl, setLiveYoutubeUrl] = useState('');
  const [liveEnabled, setLiveEnabled] = useState(false);
  const [breakingEnabled, setBreakingEnabled] = useState(true);

  // Change Password state
  const [currentPassword, setCurrentPassword] = useState('');
  const [newPassword, setNewPassword] = useState('');
  const [confirmPassword, setConfirmPassword] = useState('');
  const [showCurrentPass, setShowCurrentPass] = useState(false);
  const [showNewPass, setShowNewPass] = useState(false);
  const [changingPass, setChangingPass] = useState(false);
  const [passMessage, setPassMessage] = useState<{ success: boolean; text: string } | null>(null);

  useEffect(() => {
    if (!user) {
      getItem(STORAGE_KEYS.AUTH_USER).then((stored) => {
        if (stored) {
          try {
            setUser(JSON.parse(stored));
          } catch {}
        }
      });
    }
  }, [user]);

  useEffect(() => {
    async function loadSettings() {
      setLoading(true);
      try {
        const data = await getSettings(portalUrl);
        setSettings(data);
        setSiteName(data.site_name || '');
        setSiteTagline(data.site_tagline || '');
        setThemeColor(data.theme_color || '#dc2626');
        setContactEmail(data.contact_email || '');
        setContactPhone(data.contact_phone || '');
        setLiveYoutubeUrl(data.live_youtube_url || '');
        setLiveEnabled(data.live_youtube_enabled === '1');
        setBreakingEnabled(data.breaking_news_enabled !== 'no');
      } catch (err: any) {
        Alert.alert('Error', err.message || 'Failed to load settings');
      } finally {
        setLoading(false);
      }
    }
    loadSettings();
  }, [portalUrl]);

  const handleSaveSettings = async () => {
    setSaving(true);
    try {
      await updateSettings(portalUrl, {
        site_name: siteName.trim(),
        site_tagline: siteTagline.trim(),
        theme_color: themeColor.trim(),
        contact_email: contactEmail.trim(),
        contact_phone: contactPhone.trim(),
        live_youtube_url: liveYoutubeUrl.trim(),
        live_youtube_enabled: liveEnabled ? '1' : '0',
        breaking_news_enabled: breakingEnabled ? 'yes' : 'no',
      });
      Alert.alert('Success', 'Portal settings updated successfully!');
    } catch (err: any) {
      Alert.alert('Error', err.message || 'Failed to save settings');
    } finally {
      setSaving(false);
    }
  };

  const handleUpdateStoredUrl = async () => {
    const raw = currentUrl.trim();
    if (!raw) {
      Alert.alert('Required', 'Please enter a valid website address');
      return;
    }

    if (!isValidPortalUrl(raw)) {
      Alert.alert('Invalid URL', 'Please enter a valid website address (e.g. https://yourwebsite.com/news)');
      return;
    }

    const clean = normalizePortalUrl(raw);
    setTestingUrl(true);
    setUrlStatus(null);
    const result = await testConnection(clean);
    setTestingUrl(false);

    if (result.success) {
      const finalUrl = result.resolvedUrl || clean;
      setCurrentUrl(finalUrl);
      await setItem(STORAGE_KEYS.PORTAL_URL, finalUrl);
      setUrlStatus({ success: true, message: 'Connected & Stored Successfully' });
      Alert.alert('Updated', `Website address updated to: ${finalUrl}`);
      onUpdatePortalUrl(finalUrl);
    } else {
      setUrlStatus({ success: false, message: result.message || 'Connection failed' });
    }
  };

  const handleChangePassword = async () => {
    setPassMessage(null);
    if (!currentPassword) {
      Alert.alert('Required', 'Please enter your current password');
      return;
    }
    if (!newPassword) {
      Alert.alert('Required', 'Please enter a new password');
      return;
    }
    if (newPassword.length < 6) {
      Alert.alert('Weak Password', 'New password must be at least 6 characters long');
      return;
    }
    if (newPassword !== confirmPassword) {
      Alert.alert('Mismatch', 'New password and confirmation do not match');
      return;
    }

    setChangingPass(true);
    try {
      const res = await changePassword(portalUrl, {
        userId: user?.id,
        username: user?.username,
        currentPassword,
        newPassword,
        confirmPassword,
      });

      if (res.success) {
        setPassMessage({ success: true, text: res.message || 'Password changed successfully!' });
        setCurrentPassword('');
        setNewPassword('');
        setConfirmPassword('');
        Alert.alert('Success', 'Your password has been changed successfully.');
      } else {
        setPassMessage({ success: false, text: res.message || 'Failed to update password' });
        Alert.alert('Error', res.message || 'Failed to update password');
      }
    } catch (err: any) {
      setPassMessage({ success: false, text: err.message || 'An error occurred' });
      Alert.alert('Error', err.message || 'An error occurred');
    } finally {
      setChangingPass(false);
    }
  };

  return (
    <ScrollView contentContainerStyle={styles.container} keyboardShouldPersistTaps="handled">
      {/* Section: Website Connection & Storage */}
      <View
        style={[
          styles.card,
          { backgroundColor: theme.backgroundElement, borderColor: 'rgba(0,0,0,0.06)' },
        ]}>
        <View style={styles.cardHeader}>
          <Globe size={16} color="#dc2626" />
          <Text style={[styles.cardTitle, { color: theme.text }]}>
            Stored Website Address
          </Text>
        </View>
        <Text style={[styles.cardDesc, { color: theme.textSecondary }]}>
          This device connects to this website. You can change or point to another news portal here.
        </Text>

        <View style={[styles.inputWrapper, { backgroundColor: theme.backgroundSelected }]}>
          <Server size={15} color={theme.textSecondary} style={{ marginRight: 8 }} />
          <TextInput
            style={[styles.inputInner, { color: theme.text }]}
            value={currentUrl}
            onChangeText={setCurrentUrl}
            placeholder="https://example.com/news"
            placeholderTextColor={theme.textSecondary}
            autoCapitalize="none"
            autoCorrect={false}
          />
        </View>

        {urlStatus ? (
          <View
            style={[
              styles.statusBanner,
              {
                backgroundColor: urlStatus.success ? '#ecfdf5' : '#fef2f2',
                borderColor: urlStatus.success ? '#a7f3d0' : '#fecaca',
              },
            ]}>
            {urlStatus.success ? (
              <CheckCircle2 size={14} color="#15803d" style={{ marginRight: 6 }} />
            ) : (
              <AlertCircle size={14} color="#dc2626" style={{ marginRight: 6 }} />
            )}
            <Text
              style={[
                styles.statusText,
                { color: urlStatus.success ? '#15803d' : '#dc2626' },
              ]}>
              {urlStatus.message}
            </Text>
          </View>
        ) : null}

        <TouchableOpacity
          style={[styles.testUrlBtn, testingUrl && styles.btnDisabled]}
          onPress={handleUpdateStoredUrl}
          disabled={testingUrl}>
          {testingUrl ? (
            <ActivityIndicator size="small" color="#fff" />
          ) : (
            <Text style={styles.testUrlBtnText}>Test & Save Website Address</Text>
          )}
        </TouchableOpacity>
      </View>

      {/* Section: Live Broadcast & Ticker Options */}
      <View
        style={[
          styles.card,
          { backgroundColor: theme.backgroundElement, borderColor: 'rgba(0,0,0,0.06)' },
        ]}>
        <View style={styles.cardHeader}>
          <Radio size={16} color="#dc2626" />
          <Text style={[styles.cardTitle, { color: theme.text }]}>
            Broadcast & Ticker Controls
          </Text>
        </View>
        <Text style={[styles.cardDesc, { color: theme.textSecondary }]}>
          Configure live video streams and breaking news ticker banner
        </Text>

        {/* Live Broadcast Switch */}
        <View style={styles.toggleRow}>
          <View style={{ flex: 1 }}>
            <Text style={[styles.toggleTitle, { color: theme.text }]}>
              YouTube Live Stream
            </Text>
            <Text style={[styles.toggleDesc, { color: theme.textSecondary }]}>
              Show live stream player on web homepage
            </Text>
          </View>
          <Switch
            value={liveEnabled}
            onValueChange={setLiveEnabled}
            trackColor={{ false: '#767577', true: '#dc2626' }}
            thumbColor="#ffffff"
          />
        </View>

        <Text style={[styles.fieldLabel, { color: theme.text, marginTop: 10 }]}>
          YouTube Live URL
        </Text>
        <View style={[styles.inputWrapper, { backgroundColor: theme.backgroundSelected }]}>
          <Radio size={15} color={theme.textSecondary} style={{ marginRight: 8 }} />
          <TextInput
            style={[styles.inputInner, { color: theme.text }]}
            value={liveYoutubeUrl}
            onChangeText={setLiveYoutubeUrl}
            placeholder="https://www.youtube.com/watch?v=..."
            placeholderTextColor={theme.textSecondary}
            autoCapitalize="none"
          />
        </View>

        <View style={styles.divider} />

        {/* Breaking News Switch */}
        <View style={styles.toggleRow}>
          <View style={{ flex: 1 }}>
            <View style={{ flexDirection: 'row', alignItems: 'center', gap: 6 }}>
              <Zap size={15} color="#f59e0b" />
              <Text style={[styles.toggleTitle, { color: theme.text }]}>
                Breaking News Ticker
              </Text>
            </View>
            <Text style={[styles.toggleDesc, { color: theme.textSecondary }]}>
              Show animated marquee ticker banner across top of website
            </Text>
          </View>
          <Switch
            value={breakingEnabled}
            onValueChange={setBreakingEnabled}
            trackColor={{ false: '#767577', true: '#f59e0b' }}
            thumbColor="#ffffff"
          />
        </View>
      </View>

      {/* Section: Portal Configuration */}
      <View
        style={[
          styles.card,
          { backgroundColor: theme.backgroundElement, borderColor: 'rgba(0,0,0,0.06)' },
        ]}>
        <View style={styles.cardHeader}>
          <Settings size={16} color="#dc2626" />
          <Text style={[styles.cardTitle, { color: theme.text }]}>Portal Configuration</Text>
        </View>

        {loading ? (
          <ActivityIndicator size="small" color="#dc2626" style={{ marginVertical: 20 }} />
        ) : (
          <View>
            <Text style={[styles.fieldLabel, { color: theme.text }]}>Brand Name</Text>
            <TextInput
              style={[
                styles.input,
                { backgroundColor: theme.backgroundSelected, color: theme.text },
              ]}
              value={siteName}
              onChangeText={setSiteName}
              placeholder="e.g. Panchayat Voice"
              placeholderTextColor={theme.textSecondary}
            />

            <Text style={[styles.fieldLabel, { color: theme.text }]}>Tagline</Text>
            <TextInput
              style={[
                styles.input,
                { backgroundColor: theme.backgroundSelected, color: theme.text },
              ]}
              value={siteTagline}
              onChangeText={setSiteTagline}
              placeholder="e.g. Digital News Portal"
              placeholderTextColor={theme.textSecondary}
            />

            <Text style={[styles.fieldLabel, { color: theme.text }]}>Accent Theme Color</Text>
            <TouchableOpacity
              style={[
                styles.colorSelectorRow,
                { backgroundColor: theme.backgroundSelected, borderColor: 'rgba(0,0,0,0.06)' },
              ]}
              onPress={() => setShowColorList(!showColorList)}
              activeOpacity={0.75}>
              <View style={[styles.colorPreview, { backgroundColor: themeColor }]} />
              <View style={{ flex: 1 }}>
                <Text style={[styles.colorSelectedName, { color: theme.text }]}>
                  {THEME_COLOR_PALETTE.find((c) => c.hex.toLowerCase() === themeColor.toLowerCase())?.name || 'Custom Theme Color'}
                </Text>
                <Text style={[styles.colorSelectedHex, { color: theme.textSecondary }]}>
                  {themeColor}
                </Text>
              </View>
              <View style={[styles.colorPickerPill, { backgroundColor: themeColor + '20' }]}>
                <Text style={[styles.colorPickerPillText, { color: themeColor }]}>
                  {showColorList ? 'Hide List ▲' : 'Choose Color ▼'}
                </Text>
              </View>
            </TouchableOpacity>

            {showColorList ? (
              <View
                style={[
                  styles.colorGridBox,
                  { backgroundColor: theme.backgroundSelected, borderColor: 'rgba(0,0,0,0.08)' },
                ]}>
                <Text style={[styles.colorGridTitle, { color: theme.textSecondary }]}>
                  Tap any color to apply:
                </Text>
                <View style={styles.colorPaletteGrid}>
                  {THEME_COLOR_PALETTE.map((item) => {
                    const isSelected = themeColor.toLowerCase() === item.hex.toLowerCase();
                    return (
                      <TouchableOpacity
                        key={item.hex}
                        style={[
                          styles.colorPaletteItem,
                          { backgroundColor: theme.backgroundElement },
                          isSelected && { borderColor: item.hex, borderWidth: 2 },
                        ]}
                        onPress={() => setThemeColor(item.hex)}
                        activeOpacity={0.7}>
                        <View style={[styles.colorSwatchCircle, { backgroundColor: item.hex }]}>
                          {isSelected ? <Check size={12} color="#ffffff" strokeWidth={3} /> : null}
                        </View>
                        <Text
                          style={[
                            styles.colorPaletteName,
                            { color: isSelected ? theme.text : theme.textSecondary, fontWeight: isSelected ? '700' : '500' },
                          ]}
                          numberOfLines={1}>
                          {item.name}
                        </Text>
                      </TouchableOpacity>
                    );
                  })}
                </View>
                <View style={styles.customHexInputRow}>
                  <Text style={[styles.customHexLabel, { color: theme.textSecondary }]}>Custom Hex:</Text>
                  <TextInput
                    style={[styles.customHexInput, { color: theme.text, backgroundColor: theme.backgroundElement }]}
                    value={themeColor}
                    onChangeText={setThemeColor}
                    placeholder="#dc2626"
                    placeholderTextColor={theme.textSecondary}
                    autoCapitalize="none"
                  />
                </View>
              </View>
            ) : null}

            <Text style={[styles.fieldLabel, { color: theme.text }]}>Contact Email</Text>
            <View
              style={[
                styles.inputWrapper,
                { backgroundColor: theme.backgroundSelected, marginTop: 4 },
              ]}>
              <Mail size={15} color={theme.textSecondary} style={{ marginRight: 8 }} />
              <TextInput
                style={[styles.inputInner, { color: theme.text }]}
                value={contactEmail}
                onChangeText={setContactEmail}
                placeholder="editor@example.com"
                placeholderTextColor={theme.textSecondary}
                keyboardType="email-address"
                autoCapitalize="none"
              />
            </View>

            <Text style={[styles.fieldLabel, { color: theme.text }]}>Contact Phone / WhatsApp</Text>
            <View
              style={[
                styles.inputWrapper,
                { backgroundColor: theme.backgroundSelected, marginTop: 4 },
              ]}>
              <Phone size={15} color={theme.textSecondary} style={{ marginRight: 8 }} />
              <TextInput
                style={[styles.inputInner, { color: theme.text }]}
                value={contactPhone}
                onChangeText={setContactPhone}
                placeholder="+91 9876543210"
                placeholderTextColor={theme.textSecondary}
              />
            </View>

            <TouchableOpacity
              style={[styles.saveSettingsBtn, saving && styles.btnDisabled]}
              onPress={handleSaveSettings}
              disabled={saving}>
              {saving ? (
                <ActivityIndicator size="small" color="#fff" />
              ) : (
                <View style={{ flexDirection: 'row', alignItems: 'center' }}>
                  <Save size={15} color="#fff" style={{ marginRight: 6 }} />
                  <Text style={styles.saveSettingsBtnText}>Save Portal Settings</Text>
                </View>
              )}
            </TouchableOpacity>
          </View>
        )}
      </View>

      {/* Section: Change Password */}
      <View
        style={[
          styles.card,
          { backgroundColor: theme.backgroundElement, borderColor: 'rgba(0,0,0,0.06)' },
        ]}>
        <View style={styles.cardHeader}>
          <Lock size={16} color="#dc2626" />
          <Text style={[styles.cardTitle, { color: theme.text }]}>Change Password</Text>
        </View>
        <Text style={[styles.cardDesc, { color: theme.textSecondary }]}>
          Update your account password. You will need your current password to confirm.
        </Text>

        {passMessage ? (
          <View
            style={[
              styles.statusBanner,
              {
                backgroundColor: passMessage.success ? '#ecfdf5' : '#fef2f2',
                borderColor: passMessage.success ? '#a7f3d0' : '#fecaca',
              },
            ]}>
            {passMessage.success ? (
              <CheckCircle2 size={14} color="#15803d" style={{ marginRight: 6 }} />
            ) : (
              <AlertCircle size={14} color="#dc2626" style={{ marginRight: 6 }} />
            )}
            <Text
              style={[
                styles.statusText,
                { color: passMessage.success ? '#15803d' : '#dc2626' },
              ]}>
              {passMessage.text}
            </Text>
          </View>
        ) : null}

        {/* Current Password */}
        <Text style={[styles.fieldLabel, { color: theme.text }]}>Current Password *</Text>
        <View style={[styles.inputWrapper, { backgroundColor: theme.backgroundSelected }]}>
          <Lock size={15} color={theme.textSecondary} style={{ marginRight: 8 }} />
          <TextInput
            style={[styles.inputInner, { color: theme.text }]}
            value={currentPassword}
            onChangeText={setCurrentPassword}
            placeholder="Enter current password"
            placeholderTextColor={theme.textSecondary}
            secureTextEntry={!showCurrentPass}
            autoCapitalize="none"
          />
          <TouchableOpacity onPress={() => setShowCurrentPass(!showCurrentPass)} style={styles.eyeBtn}>
            {showCurrentPass ? (
              <EyeOff size={16} color={theme.textSecondary} />
            ) : (
              <Eye size={16} color={theme.textSecondary} />
            )}
          </TouchableOpacity>
        </View>

        {/* New Password */}
        <Text style={[styles.fieldLabel, { color: theme.text, marginTop: 12 }]}>New Password *</Text>
        <View style={[styles.inputWrapper, { backgroundColor: theme.backgroundSelected }]}>
          <Lock size={15} color={theme.textSecondary} style={{ marginRight: 8 }} />
          <TextInput
            style={[styles.inputInner, { color: theme.text }]}
            value={newPassword}
            onChangeText={setNewPassword}
            placeholder="At least 6 characters"
            placeholderTextColor={theme.textSecondary}
            secureTextEntry={!showNewPass}
            autoCapitalize="none"
          />
          <TouchableOpacity onPress={() => setShowNewPass(!showNewPass)} style={styles.eyeBtn}>
            {showNewPass ? (
              <EyeOff size={16} color={theme.textSecondary} />
            ) : (
              <Eye size={16} color={theme.textSecondary} />
            )}
          </TouchableOpacity>
        </View>

        {/* Confirm New Password */}
        <Text style={[styles.fieldLabel, { color: theme.text, marginTop: 12 }]}>Confirm New Password *</Text>
        <View style={[styles.inputWrapper, { backgroundColor: theme.backgroundSelected }]}>
          <Lock size={15} color={theme.textSecondary} style={{ marginRight: 8 }} />
          <TextInput
            style={[styles.inputInner, { color: theme.text }]}
            value={confirmPassword}
            onChangeText={setConfirmPassword}
            placeholder="Re-enter new password"
            placeholderTextColor={theme.textSecondary}
            secureTextEntry={!showNewPass}
            autoCapitalize="none"
          />
        </View>

        <TouchableOpacity
          style={[styles.changePassBtn, changingPass && styles.btnDisabled]}
          onPress={handleChangePassword}
          disabled={changingPass}>
          {changingPass ? (
            <ActivityIndicator size="small" color="#fff" />
          ) : (
            <View style={{ flexDirection: 'row', alignItems: 'center' }}>
              <Lock size={15} color="#fff" style={{ marginRight: 6 }} />
              <Text style={styles.changePassBtnText}>Update Password</Text>
            </View>
          )}
        </TouchableOpacity>
      </View>

      {/* Disconnect & Logout */}
      <View
        style={[
          styles.card,
          { backgroundColor: theme.backgroundElement, borderColor: 'rgba(0,0,0,0.06)' },
        ]}>
        <View style={styles.cardHeader}>
          <LogOut size={16} color="#dc2626" />
          <Text style={[styles.cardTitle, { color: theme.text }]}>Admin Session</Text>
        </View>
        <Text style={[styles.cardDesc, { color: theme.textSecondary }]}>
          Disconnect and log out from this portal. Stored website address will remain saved for quick re-login.
        </Text>

        <TouchableOpacity style={styles.logoutBtn} onPress={onLogout}>
          <LogOut size={15} color="#dc2626" style={{ marginRight: 6 }} />
          <Text style={styles.logoutBtnText}>Logout & Disconnect</Text>
        </TouchableOpacity>
      </View>
    </ScrollView>
  );
}

const styles = StyleSheet.create({
  container: {
    padding: Spacing.four,
    gap: 14,
    paddingBottom: 110,
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
    marginBottom: 4,
  },
  cardTitle: {
    fontSize: 14,
    fontWeight: '800',
  },
  cardDesc: {
    fontSize: 11,
    lineHeight: 16,
    marginBottom: 10,
  },
  toggleRow: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    marginVertical: 4,
  },
  toggleTitle: {
    fontSize: 13,
    fontWeight: '700',
  },
  toggleDesc: {
    fontSize: 11,
    fontWeight: '500',
    marginTop: 2,
  },
  divider: {
    height: 1,
    backgroundColor: 'rgba(0,0,0,0.06)',
    marginVertical: 12,
  },
  inputWrapper: {
    flexDirection: 'row',
    alignItems: 'center',
    borderRadius: 10,
    paddingHorizontal: 12,
    height: 44,
  },
  inputInner: {
    flex: 1,
    fontSize: 13,
    height: '100%',
  },
  input: {
    borderRadius: 10,
    paddingHorizontal: 12,
    height: 44,
    fontSize: 13,
  },
  statusBanner: {
    flexDirection: 'row',
    alignItems: 'center',
    marginTop: 8,
    paddingHorizontal: 10,
    paddingVertical: 8,
    borderRadius: 8,
    borderWidth: 1,
  },
  statusText: {
    fontSize: 12,
    fontWeight: '600',
    flex: 1,
  },
  testUrlBtn: {
    backgroundColor: '#0f172a',
    borderRadius: 10,
    paddingVertical: 12,
    alignItems: 'center',
    marginTop: 10,
  },
  testUrlBtnText: {
    color: '#fff',
    fontWeight: '700',
    fontSize: 13,
  },
  fieldLabel: {
    fontSize: 12,
    fontWeight: '700',
    marginTop: 10,
    marginBottom: 4,
  },
  colorRow: {
    flexDirection: 'row',
    gap: 8,
    alignItems: 'center',
  },
  colorPreview: {
    width: 36,
    height: 36,
    borderRadius: 8,
  },
  colorSelectorRow: {
    flexDirection: 'row',
    alignItems: 'center',
    padding: 10,
    borderRadius: 10,
    borderWidth: 1,
    gap: 10,
    marginTop: 4,
  },
  colorSelectedName: {
    fontSize: 13,
    fontWeight: '700',
  },
  colorSelectedHex: {
    fontSize: 11,
    fontWeight: '500',
    marginTop: 1,
  },
  colorPickerPill: {
    paddingHorizontal: 9,
    paddingVertical: 5,
    borderRadius: 6,
  },
  colorPickerPillText: {
    fontSize: 11,
    fontWeight: '700',
  },
  colorGridBox: {
    marginTop: 8,
    padding: 12,
    borderRadius: 10,
    borderWidth: 1,
  },
  colorGridTitle: {
    fontSize: 11,
    fontWeight: '700',
    marginBottom: 8,
  },
  colorPaletteGrid: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: 8,
  },
  colorPaletteItem: {
    flexBasis: '47%',
    flexGrow: 1,
    flexDirection: 'row',
    alignItems: 'center',
    padding: 8,
    borderRadius: 8,
    gap: 8,
    borderWidth: 1,
    borderColor: 'rgba(0,0,0,0.06)',
  },
  colorSwatchCircle: {
    width: 22,
    height: 22,
    borderRadius: 11,
    alignItems: 'center',
    justifyContent: 'center',
  },
  colorPaletteName: {
    fontSize: 11,
    flex: 1,
  },
  customHexInputRow: {
    flexDirection: 'row',
    alignItems: 'center',
    marginTop: 10,
    gap: 8,
    paddingTop: 8,
    borderTopWidth: 1,
    borderTopColor: 'rgba(0,0,0,0.06)',
  },
  customHexLabel: {
    fontSize: 11,
    fontWeight: '600',
  },
  customHexInput: {
    flex: 1,
    height: 36,
    borderRadius: 8,
    paddingHorizontal: 10,
    fontSize: 12,
    borderWidth: 1,
    borderColor: 'rgba(0,0,0,0.08)',
  },
  eyeBtn: {
    padding: 6,
  },
  changePassBtn: {
    backgroundColor: '#dc2626',
    borderRadius: 10,
    paddingVertical: 12,
    alignItems: 'center',
    justifyContent: 'center',
    marginTop: Spacing.four,
  },
  changePassBtnText: {
    color: '#fff',
    fontWeight: '700',
    fontSize: 13,
  },
  saveSettingsBtn: {
    backgroundColor: '#dc2626',
    borderRadius: 10,
    paddingVertical: 12,
    alignItems: 'center',
    justifyContent: 'center',
    marginTop: Spacing.four,
  },
  saveSettingsBtnText: {
    color: '#fff',
    fontWeight: '700',
    fontSize: 13,
  },
  logoutBtn: {
    backgroundColor: '#fee2e2',
    borderRadius: 10,
    paddingVertical: 12,
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    marginTop: 6,
  },
  logoutBtnText: {
    color: '#dc2626',
    fontWeight: '700',
    fontSize: 13,
  },
  btnDisabled: {
    opacity: 0.5,
  },
});
