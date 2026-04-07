import React, { useState, useEffect } from 'react';
import { View, Text, FlatList, Image, TouchableOpacity, StyleSheet, ActivityIndicator, RefreshControl, StatusBar, SafeAreaView } from 'react-native';
import { Feather } from '@expo/vector-icons';
import { useRouter } from 'expo-router';
import { getAction } from '../../config/api';

export default function Home() {
  const router = useRouter();
  const [posts, setPosts] = useState<any[]>([]);
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [page, setPage] = useState(1);

  const fetchPosts = async (p = 1, refresh = false) => {
    const res = await getAction('posts', { page: p });
    if (res.success) {
      setPosts(refresh ? res.data : [...posts, ...res.data]);
    }
    setLoading(false);
    setRefreshing(false);
  };

  useEffect(() => {
    fetchPosts();
  }, []);

  const onRefresh = () => {
    setRefreshing(true);
    setPage(1);
    fetchPosts(1, true);
  };

  const loadMore = () => {
    const next = page + 1;
    setPage(next);
    fetchPosts(next);
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
        <View style={styles.badgeRow}>
            <Text style={[styles.categoryBadge, { backgroundColor: item.category_color + '20', color: item.category_color }]}>
                {item.category_name}
            </Text>
            <Text style={styles.dateText}>{new Date(item.published_at).toDateString()}</Text>
        </View>
        <Text style={styles.title} numberOfLines={2}>{item.title}</Text>
        <Text style={styles.excerpt} numberOfLines={2}>{item.excerpt}</Text>
      </View>
    </TouchableOpacity>
  );

  if (loading && page === 1) {
    return (
      <View style={styles.center}>
        <ActivityIndicator size="large" color="#ff3c00" />
      </View>
    );
  }

  return (
    <View style={styles.container}>
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
  card: {
    backgroundColor: '#fff',
    borderRadius: 16,
    overflow: 'hidden',
    marginBottom: 20,
    elevation: 4,
    shadowColor: '#000',
    shadowOpacity: 0.1,
    shadowRadius: 10,
    shadowOffset: { width: 0, height: 4 },
  },
  image: { width: '100%', height: 200, objectFit: 'cover' },
  cardInfo: { padding: 15 },
  badgeRow: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', marginBottom: 10 },
  categoryBadge: {
    fontSize: 11,
    fontWeight: '800',
    paddingVertical: 4,
    paddingHorizontal: 8,
    borderRadius: 6,
    textTransform: 'uppercase',
  },
  dateText: { fontSize: 11, color: '#94a3b8', fontWeight: '600' },
  title: { fontSize: 18, fontWeight: '800', color: '#1e293b', lineHeight: 24, marginBottom: 8 },
  excerpt: { fontSize: 14, color: '#64748b', lineHeight: 20 },
});
