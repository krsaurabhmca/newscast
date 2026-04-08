import React, { useState, useEffect } from 'react';
import { View, Text, FlatList, TouchableOpacity, StyleSheet, ActivityIndicator, Platform, StatusBar } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { Feather } from '@expo/vector-icons';
import { useRouter } from 'expo-router';
import { getAction } from '../../config/api';

export default function Categories() {
  const router = useRouter();
  const [categories, setCategories] = useState<any[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    fetchCategories();
  }, []);

  const fetchCategories = async () => {
    try {
      const res = await getAction('categories');
      if (res && res.success) {
        setCategories(res.data || []);
        setError(null);
      } else {
        setError(res?.message || 'Unable to load categories');
      }
    } catch (err) {
      console.error('Fetch categories error:', err);
      setError('Network error. Check your internet.');
    } finally {
      setLoading(false);
    }
  };

  const renderCategory = ({ item }: { item: any }) => (
    <TouchableOpacity 
        style={[styles.card, { borderLeftColor: item.color }]}
        onPress={() => router.push(`/category/${item.id}` as any)}
    >
      <View style={[styles.iconBox, { backgroundColor: item.color + '15' }]}>
        <Feather name={item.icon || 'folder'} size={24} color={item.color} />
      </View>
      <View style={styles.info}>
        <Text style={styles.catName}>{item.name}</Text>
        <Text style={styles.catSlug}>/ {item.slug}</Text>
      </View>
      <Feather name="chevron-right" size={20} color="#cbd5e1" />
    </TouchableOpacity>
  );

  if (loading) return <View style={styles.center}><ActivityIndicator color="#ff3c00" size="large" /></View>;

  if (error) {
    return (
      <View style={styles.center}>
        <Feather name="alert-circle" size={50} color="#cbd5e1" />
        <Text style={styles.errorText}>{error}</Text>
        <TouchableOpacity style={styles.retryBtn} onPress={() => { setLoading(true); fetchCategories(); }}>
          <Text style={styles.retryText}>Try Again</Text>
        </TouchableOpacity>
      </View>
    );
  }

  return (
    <SafeAreaView style={styles.container}>
      <StatusBar barStyle="dark-content" />
      <View style={styles.appHeader}>
          <Text style={styles.logoText}>Explore <Text style={{color: '#ff3c00'}}>Categories</Text></Text>
      </View>
      <FlatList
        data={categories}
        renderItem={renderCategory}
        keyExtractor={(item) => item.id.toString()}
        contentContainerStyle={{ padding: 15 }}
      />
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: '#f8fafc', paddingTop: Platform.OS === 'android' ? StatusBar.currentHeight : 0 },
  center: { flex: 1, justifyContent: 'center', alignItems: 'center' },
  appHeader: { 
    paddingHorizontal: 20, paddingVertical: 15, backgroundColor: '#fff', borderBottomWidth: 1, borderBottomColor: '#f1f5f9' 
  },
  logoText: { fontSize: 22, fontWeight: '900', color: '#1e293b' },
  card: { 
    flexDirection: 'row', alignItems: 'center', backgroundColor: '#fff', 
    marginBottom: 12, padding: 15, borderRadius: 15, borderLeftWidth: 5,
    elevation: 2, shadowOpacity: 0.05
  },
  iconBox: { width: 50, height: 50, borderRadius: 12, justifyContent: 'center', alignItems: 'center' },
  info: { flex: 1, marginLeft: 15 },
  catName: { fontSize: 16, fontWeight: '800', color: '#1e293b' },
  catSlug: { fontSize: 12, color: '#94a3b8', fontWeight: '700', textTransform: 'uppercase' },
  errorText: { color: '#64748b', fontSize: 16, marginTop: 15, marginBottom: 20, fontWeight: '600' },
  retryBtn: { backgroundColor: '#ff3c00', paddingHorizontal: 30, paddingVertical: 12, borderRadius: 10 },
  retryText: { color: '#fff', fontWeight: '800', fontSize: 15 },
});
