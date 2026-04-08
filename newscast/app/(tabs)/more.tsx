import React, { useState } from 'react';
import { View, Text, StyleSheet, ScrollView, TouchableOpacity, Linking, Alert, TextInput, ActivityIndicator, Platform, StatusBar } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { Image } from 'expo-image';
import { Feather } from '@expo/vector-icons';
import { postAction, getFullUrl } from '../../config/api';

export default function More() {
  const [user, setUser] = useState<any>(null);
  const [showLogin, setShowLogin] = useState(false);
  const [username, setUsername] = useState('');
  const [password, setPassword] = useState('');
  const [loginLoading, setLoginLoading] = useState(false);

  const handleLogin = async () => {
    if (!username || !password) return Alert.alert("Error", "Enter credentials");
    setLoginLoading(true);
    const res = await postAction('login', { username, password });
    if (res.success) {
      setUser(res.data);
      setShowLogin(false);
    } else {
      Alert.alert("Error", res.message);
    }
    setLoginLoading(false);
  };

  const MenuItem = ({ icon, title, subtitle, onPress }: any) => (
    <TouchableOpacity style={styles.menuItem} onPress={onPress}>
        <View style={styles.menuIconBox}>
            <Feather name={icon} size={22} color="#ff3c00" />
        </View>
        <View style={styles.menuTextBox}>
            <Text style={styles.menuTitle}>{title}</Text>
            {subtitle && <Text style={styles.menuSubtitle}>{subtitle}</Text>}
        </View>
        <Feather name="chevron-right" size={20} color="#cbd5e1" />
    </TouchableOpacity>
  );

  return (
    <SafeAreaView style={styles.container}>
      <StatusBar barStyle="dark-content" />
      <View style={styles.header}>
          <Text style={styles.headerTitle}>More <Text style={{color: '#ff3c00'}}>Options</Text></Text>
      </View>
      
      <ScrollView contentContainerStyle={styles.content}>
        
        <View style={styles.section}>
            <MenuItem icon="info" title="About Us" subtitle="Who we are and our mission" onPress={() => Linking.openURL('https://panchayatvoice.in/about-us')} />
            <MenuItem icon="phone" title="Contact Us" subtitle="Get in touch with our team" onPress={() => Linking.openURL('https://panchayatvoice.in/contact-us')} />
            <MenuItem icon="heart" title="Donate Us" subtitle="Support independent journalism" onPress={() => Linking.openURL('https://panchayatvoice.in/donate')} />
        </View>

        <View style={styles.sectionHeaderBox}>
            <Text style={styles.sectionHeaderText}>Social Media</Text>
        </View>
        
        <View style={styles.socialRow}>
            <TouchableOpacity style={styles.socialBtn} onPress={() => Linking.openURL('https://facebook.com/panchayatvoice')}>
                <Feather name="facebook" size={24} color="#1877F2" />
                <Text style={styles.socialLabel}>Facebook</Text>
            </TouchableOpacity>
            <TouchableOpacity style={styles.socialBtn} onPress={() => Linking.openURL('https://twitter.com/panchayatvoice')}>
                <Feather name="twitter" size={24} color="#1DA1F2" />
                <Text style={styles.socialLabel}>Twitter</Text>
            </TouchableOpacity>
            <TouchableOpacity style={styles.socialBtn} onPress={() => Linking.openURL('https://youtube.com/panchayatvoice')}>
                <Feather name="youtube" size={24} color="#FF0000" />
                <Text style={styles.socialLabel}>YouTube</Text>
            </TouchableOpacity>
        </View>

        <View style={styles.sectionHeaderBox}>
            <Text style={styles.sectionHeaderText}>Account</Text>
        </View>

        {!user ? (
            <MenuItem icon="lock" title="Admin Login" subtitle="Access portal management" onPress={() => setShowLogin(true)} />
        ) : (
            <View style={styles.profileBox}>
                <Image source={{ uri: getFullUrl(user.profile_image) }} style={styles.avatar} />
                <View style={styles.profileInfo}>
                    <Text style={styles.profileName}>{user.full_name}</Text>
                    <Text style={styles.profileRole}>{user.role}</Text>
                </View>
                <TouchableOpacity onPress={() => setUser(null)}>
                    <Feather name="log-out" size={20} color="#f43f5e" />
                </TouchableOpacity>
            </View>
        )}

        <View style={styles.footer}>
            <Text style={styles.footerText}>App Version 1.3.0</Text>
            <Text style={styles.footerText}>Powered by Panchayat Voice</Text>
        </View>

      </ScrollView>

      {showLogin && (
          <View style={styles.loginModal}>
              <View style={styles.loginCard}>
                  <View style={styles.modalHeader}>
                    <Text style={styles.modalTitle}>Admin Portal</Text>
                    <TouchableOpacity onPress={() => setShowLogin(false)}>
                        <Feather name="x" size={24} color="#1e293b" />
                    </TouchableOpacity>
                  </View>
                  <View style={styles.inputGroup}>
                      <Text style={styles.label}>Username</Text>
                      <TextInput value={username} onChangeText={setUsername} style={styles.input} placeholder="admin..." autoCapitalize="none" />
                  </View>
                  <View style={styles.inputGroup}>
                      <Text style={styles.label}>Password</Text>
                      <TextInput value={password} onChangeText={setPassword} style={styles.input} placeholder="••••••••" secureTextEntry />
                  </View>
                  <TouchableOpacity style={styles.loginBtn} onPress={handleLogin} disabled={loginLoading}>
                      {loginLoading ? <ActivityIndicator color="#fff" /> : <Text style={styles.loginBtnText}>Secure Login</Text>}
                  </TouchableOpacity>
              </View>
          </View>
      )}

    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: '#f8fafc', paddingTop: Platform.OS === 'android' ? StatusBar.currentHeight : 0 },
  header: { padding: 20, backgroundColor: '#fff', borderBottomWidth: 1, borderBottomColor: '#f1f5f9' },
  headerTitle: { fontSize: 24, fontWeight: '900', color: '#1e293b' },
  content: { padding: 20 },
  section: { backgroundColor: '#fff', borderRadius: 20, overflow: 'hidden', borderBottomWidth: 1, borderBottomColor: '#f1f5f9' },
  menuItem: { flexDirection: 'row', alignItems: 'center', padding: 15, borderBottomWidth: 1, borderBottomColor: '#f8fafc' },
  menuIconBox: { width: 44, height: 44, borderRadius: 12, backgroundColor: '#ff3c0015', justifyContent: 'center', alignItems: 'center' },
  menuTextBox: { flex: 1, marginLeft: 15 },
  menuTitle: { fontSize: 16, fontWeight: '800', color: '#334155' },
  menuSubtitle: { fontSize: 12, color: '#94a3b8', marginTop: 2, fontWeight: '600' },
  
  sectionHeaderBox: { marginTop: 30, marginBottom: 15, paddingHorizontal: 5 },
  sectionHeaderText: { fontSize: 13, fontWeight: '800', color: '#94a3b8', textTransform: 'uppercase', letterSpacing: 1 },

  socialRow: { flexDirection: 'row', justifyContent: 'space-between', gap: 15 },
  socialBtn: { flex: 1, backgroundColor: '#fff', padding: 15, borderRadius: 20, alignItems: 'center', justifyContent: 'center', elevation: 2, shadowOpacity: 0.05 },
  socialLabel: { fontSize: 11, fontWeight: '800', color: '#64748b', marginTop: 8 },

  profileBox: { flexDirection: 'row', alignItems: 'center', padding: 20, backgroundColor: '#fff', borderRadius: 20, elevation: 4 },
  avatar: { width: 50, height: 50, borderRadius: 25 },
  profileInfo: { flex: 1, marginLeft: 15 },
  profileName: { fontSize: 18, fontWeight: '900', color: '#1e293b' },
  profileRole: { fontSize: 12, color: '#ff3c00', fontWeight: '800', textTransform: 'uppercase' },

  footer: { marginTop: 40, alignItems: 'center', paddingBottom: 20 },
  footerText: { fontSize: 11, color: '#cbd5e1', fontWeight: '700', marginBottom: 4 },

  loginModal: { ...StyleSheet.absoluteFillObject, backgroundColor: 'rgba(15,23,42,0.8)', justifyContent: 'center', alignItems: 'center', padding: 20, zIndex: 100 },
  loginCard: { width: '100%', backgroundColor: '#fff', borderRadius: 24, padding: 25 },
  modalHeader: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', marginBottom: 25 },
  modalTitle: { fontSize: 20, fontWeight: '900', color: '#1e293b' },
  inputGroup: { marginBottom: 15 },
  label: { fontSize: 12, fontWeight: '800', color: '#64748b', marginBottom: 8, textTransform: 'uppercase' },
  input: { backgroundColor: '#f8fafc', padding: 12, borderRadius: 12, borderWidth: 1, borderBottomColor: '#f1f5f9', fontSize: 15, color: '#1e293b' },
  loginBtn: { backgroundColor: '#1e293b', padding: 15, borderRadius: 12, alignItems: 'center', marginTop: 10 },
  loginBtnText: { color: '#fff', fontWeight: '800', fontSize: 16 }
});
