import React, { useState, useEffect } from 'react';
import { View, Text, FlatList, TouchableOpacity, StyleSheet, ActivityIndicator } from 'react-native';
import { Feather } from '@expo/vector-icons';
import { getAction } from '../../config/api';

export default function Categories() {
  const [categories, setCategories] = useState<any[]>([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    fetchCategories();
  }, []);

  const fetchCategories = async () => {
    const res = await getAction('categories');
    if (res.success) {
      setCategories(res.data);
    }
    setLoading(false);
  };

  const renderCategory = ({ item }: { item: any }) => (
    <TouchableOpacity style={[styles.card, { borderLeftColor: item.color }]}>
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

  return (
    <View style={styles.container}>
      <FlatList
        data={categories}
        renderItem={renderCategory}
        keyExtractor={(item) => item.id.toString()}
        contentContainerStyle={{ padding: 15 }}
      />
    </View>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: '#f8fafc' },
  center: { flex: 1, justifyContent: 'center', alignItems: 'center' },
  card: { 
    flexDirection: 'row', alignItems: 'center', backgroundColor: '#fff', 
    marginBottom: 12, padding: 15, borderRadius: 15, borderLeftWidth: 5 
  },
  iconBox: { width: 50, height: 50, borderRadius: 12, justifyContent: 'center', alignItems: 'center' },
  info: { flex: 1, marginLeft: 15 },
  catName: { fontSize: 16, fontWeight: '800', color: '#1e293b' },
  catSlug: { fontSize: 12, color: '#94a3b8', fontWeight: '700', textTransform: 'uppercase' }
});
