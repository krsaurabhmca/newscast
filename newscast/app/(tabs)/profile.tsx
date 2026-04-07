import React, { useState } from 'react';
import { View, Text, TextInput, TouchableOpacity, StyleSheet, Alert, Image, ScrollView, ActivityIndicator } from 'react-native';
import { Feather } from '@expo/vector-icons';
import { postAction } from '../../config/api';

export default function Profile() {
  const [user, setUser] = useState<any>(null);
  const [username, setUsername] = useState('');
  const [password, setPassword] = useState('');
  const [loading, setLoading] = useState(false);

  const handleLogin = async () => {
    if (!username || !password) return Alert.alert("Error", "Please fill all fields");
    setLoading(true);
    const res = await postAction('login', { username, password });
    if (res.success) {
      setUser(res.data);
    } else {
      Alert.alert("Failed", res.message);
    }
    setLoading(false);
  };

  const handleLogout = () => {
    setUser(null);
    setUsername('');
    setPassword('');
  };

  if (user) {
    return (
      <ScrollView contentContainerStyle={styles.container}>
        <View style={styles.profileHeader}>
          <Image source={{ uri: user.profile_image }} style={styles.avatar} />
          <Text style={styles.userName}>{user.username}</Text>
          <Text style={styles.userRole}>{user.role.toUpperCase()}</Text>
        </View>

        <View style={styles.menuContainer}>
          <TouchableOpacity style={styles.menuItem}>
            <Feather name="settings" size={20} color="#64748b" />
            <Text style={styles.menuText}>App Settings</Text>
          </TouchableOpacity>
          <TouchableOpacity style={styles.menuItem}>
            <Feather name="bell" size={20} color="#64748b" />
            <Text style={styles.menuText}>Notification History</Text>
          </TouchableOpacity>
           <TouchableOpacity style={[styles.menuItem, { borderBottomWidth: 0 }]} onPress={handleLogout}>
            <Feather name="log-out" size={20} color="#ef4444" />
            <Text style={[styles.menuText, { color: '#ef4444' }]}>Logout</Text>
          </TouchableOpacity>
        </View>
      </ScrollView>
    );
  }

  return (
    <View style={styles.loginContainer}>
      <View style={styles.loginCard}>
        <View style={styles.logoCircle}>
          <Feather name="shield" size={40} color="#fff" />
        </View>
        <Text style={styles.loginTitle}>Admin Access</Text>
        <Text style={styles.loginSub}>Enter your credentials to manage the news portal</Text>
        
        <View style={styles.inputWrap}>
          <Feather name="user" size={18} color="#94a3b8" />
          <TextInput 
            placeholder="Username" 
            style={styles.input} 
            value={username} 
            onChangeText={setUsername}
            autoCapitalize="none"
          />
        </View>

        <View style={styles.inputWrap}>
          <Feather name="lock" size={18} color="#94a3b8" />
          <TextInput 
            placeholder="Password" 
            style={styles.input} 
            secureTextEntry 
            value={password} 
            onChangeText={setPassword}
          />
        </View>

        <TouchableOpacity 
          style={styles.loginBtn} 
          onPress={handleLogin}
          disabled={loading}
        >
          {loading ? <ActivityIndicator color="#fff" /> : <Text style={styles.loginBtnText}>Secure Login</Text>}
        </TouchableOpacity>
      </View>
    </View>
  );
}

const styles = StyleSheet.create({
  container: { flexGrow: 1, backgroundColor: '#f8fafc', padding: 20 },
  loginContainer: { flex: 1, backgroundColor: '#f8fafc', padding: 25, justifyContent: 'center' },
  loginCard: { 
    backgroundColor: '#fff', padding: 30, borderRadius: 25, elevation: 5,
    shadowColor: '#000', shadowOpacity: 0.1, shadowRadius: 15, alignItems: 'center' 
  },
  logoCircle: { 
    width: 80, height: 80, borderRadius: 40, backgroundColor: '#ff3c00', 
    justifyContent: 'center', alignItems: 'center', marginBottom: 20,
    shadowColor: '#ff3c00', shadowOpacity: 0.4, shadowRadius: 10, shadowOffset: { width: 0, height: 5 } 
  },
  loginTitle: { fontSize: 24, fontWeight: '900', color: '#1e293b', marginBottom: 5 },
  loginSub: { fontSize: 13, color: '#64748b', textAlign: 'center', marginBottom: 30, lineHeight: 18 },
  inputWrap: { 
    flexDirection: 'row', alignItems: 'center', backgroundColor: '#f1f5f9', 
    width: '100%', paddingHorizontal: 15, borderRadius: 12, marginBottom: 15 
  },
  input: { flex: 1, padding: 12, fontSize: 15, fontWeight: '600', color: '#1e293b' },
  loginBtn: { 
    backgroundColor: '#ff3c00', width: '100%', padding: 16, borderRadius: 12, 
    alignItems: 'center', marginTop: 10, shadowColor: '#ff3c00', shadowOpacity: 0.3, shadowRadius: 8 
  },
  loginBtnText: { color: '#fff', fontSize: 16, fontWeight: '800' },
  
  profileHeader: { alignItems: 'center', marginVertical: 30 },
  avatar: { width: 100, height: 100, borderRadius: 50, borderWidth: 4, borderColor: '#fff' },
  userName: { fontSize: 22, fontWeight: '900', color: '#1e293b', marginTop: 15 },
  userRole: { fontSize: 12, fontWeight: '800', color: '#ff3c00', backgroundColor: '#fff', paddingHorizontal: 10, paddingVertical: 4, borderRadius: 20, marginTop: 5, overflow: 'hidden' },
  menuContainer: { backgroundColor: '#fff', borderRadius: 20, padding: 10 },
  menuItem: { flexDirection: 'row', alignItems: 'center', padding: 15, borderBottomWidth: 1, borderColor: '#f1f5f9', gap: 15 },
  menuText: { fontSize: 15, fontWeight: '700', color: '#1e293b' }
});
