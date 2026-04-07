import React, { useState, useEffect } from 'react';
import { View, Text, FlatList, Image, TouchableOpacity, StyleSheet, ActivityIndicator, RefreshControl } from 'react-native';
import { useLocalSearchParams, useRouter } from 'expo-router';
import { Feather } from '@expo/vector-icons';
import { getAction } from '../../config/api';

export default function CategoryDetail() {
  const { id } = useLocalSearchParams();
  const router = useRouter();
  const [posts, setPosts] = useState<any[]>([]);
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [page, setPage] = useState(1);

  useEffect(() => {
    fetchCategoryPosts();
  }, [id]);

  const fetchCategoryPosts = async (p = 1, refresh = false) => {
    const res = await getAction('posts', { category_id: id, page: p });
    if (res.success) {
      setPosts(refresh ? res.data : [...posts, ...res.data]);
    }
    setLoading(false);
    setRefreshing(false);
  };

  const onRefresh = () => {
    setRefreshing(true);
    setPage(1);
    fetchCategoryPosts(1, true);
  };

  const loadMore = () => {
    const next = page + 1;
    setPage(next);
    fetchCategoryPosts(next);
  };

  const renderPost = ({ item }: { item: any }) => (
    <TouchableOpacity 
        style={styles.card} 
        onPress={() => router.push(`/article/${item.id}` as any)}
        activeOpacity={0.9}
    >
      {item.featured_image ? (
        <Image source={{ uri: item.featured_image }} style={styles.image} />
      ) : (
        <View style={[styles.image, { backgroundColor: '#f1f5f9', justifyContent: 'center', alignItems: 'center' }]}>
            <Feather name="image" size={40} color="#cbd5e1" />
        </View>
      )}
      <View style={styles.cardInfo}>
        <Text style={styles.dateText}>{new Date(item.published_at).toDateString()}</Text>
        <Text style={styles.title} numberOfLines={2}>{item.title}</Text>
        <Text style={styles.excerpt} numberOfLines={2}>{item.excerpt}</Text>
      </View>
    </TouchableOpacity>
  );

  if (loading && page === 1) return <View style={styles.center}><ActivityIndicator color="#ff3c00" size="large" /></View>;

  return (
    <View style={styles.container}>
      <View style={styles.header}>
        <TouchableOpacity onPress={() => router.back()} style={styles.backBtn}>
            <Feather name="arrow-left" size={24} color="#1e293b" />
        </TouchableOpacity>
        <Text style={styles.headerTitle}>Category News</Text>
      </View>
      
      <FlatList
        data={posts}
        renderItem={renderPost}
        keyExtractor={(item) => item.id.toString()}
        refreshControl={<RefreshControl refreshing={refreshing} onRefresh={onRefresh} />}
        onEndReached={loadMore}
        onEndReachedThreshold={0.5}
        contentContainerStyle={{ padding: 15 }}
      />
    </View>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: '#f8fafc' },
  center: { flex: 1, justifyContent: 'center', alignItems: 'center' },
  header: { 
     flexDirection: 'row', alignItems: 'center', paddingVertical: 15, paddingHorizontal: 20,
     backgroundColor: '#fff', borderBottomWidth: 1, borderBottomColor: '#f1f5f9'
  },
  backBtn: { width: 40, height: 40, borderRadius: 20, backgroundColor: '#f1f5f9', justifyContent: 'center', alignItems: 'center' },
  headerTitle: { marginLeft: 15, fontSize: 18, fontWeight: '800', color: '#1e293b' },
  card: { backgroundColor: '#fff', borderRadius: 16, overflow: 'hidden', marginBottom: 20, elevation: 4 },
  image: { width: '100%', height: 180, objectFit: 'cover' },
  cardInfo: { padding: 15 },
  dateText: { fontSize: 11, color: '#94a3b8', fontWeight: '600', marginBottom: 5 },
  title: { fontSize: 16, fontWeight: '800', color: '#1e293b', marginBottom: 8 },
  excerpt: { fontSize: 13, color: '#64748b', lineHeight: 20 },
});
