import React, { useState, useEffect } from 'react';
import { View, Text, FlatList, TouchableOpacity, StyleSheet, ActivityIndicator, RefreshControl, StatusBar, Dimensions, Platform } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { Image } from 'expo-image';
import { Feather } from '@expo/vector-icons';
import { useRouter } from 'expo-router';
import { getAction, getFullUrl } from '../../config/api';

const { width: SCREEN_WIDTH } = Dimensions.get('window');

export default function Home() {
  const router = useRouter();
  const [posts, setPosts] = useState<any[]>([]);
  const [sliderPosts, setSliderPosts] = useState<any[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [refreshing, setRefreshing] = useState(false);
  const [page, setPage] = useState(1);

  const fetchPosts = async (p = 1, refresh = false) => {
    try {
      const res = await getAction('posts', { page: p });
      if (res && res.success) {
        if (p === 1) {
          setSliderPosts(res.data.slice(0, 5) || []);
          setPosts(res.data.slice(5) || []);
        } else {
          setPosts(prev => [...prev, ...(res.data || [])]);
        }
        setError(null);
      } else {
        if (p === 1) setError(res?.message || 'Failed to fetch content');
      }
    } catch (err: any) {
      console.error('Fetch posts component error:', err);
      if (p === 1) setError('Connection problem. Please try again.');
    } finally {
      setLoading(false);
      setRefreshing(false);
    }
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

  const renderSliderItem = ({ item }: { item: any }) => (
    <TouchableOpacity 
        style={styles.sliderCard} 
        onPress={() => router.push(`/article/${item.id}` as any)}
        activeOpacity={0.9}
    >
        <Image 
            source={{ uri: getFullUrl(item.featured_image) }} 
            style={styles.sliderImage} 
            contentFit="cover"
            transition={300}
        />
        <View style={styles.sliderOverlay}>
            <Text style={styles.sliderCategory}>{item.category_name}</Text>
            <Text style={styles.sliderTitle} numberOfLines={2}>{item.title}</Text>
        </View>
    </TouchableOpacity>
  );

  const renderPost = ({ item }: { item: any }) => (
    <TouchableOpacity 
        style={styles.card} 
        onPress={() => router.push(`/article/${item.id}` as any)}
        activeOpacity={0.9}
    >
      {item.featured_image ? (
        <Image 
            source={{ uri: getFullUrl(item.featured_image) }} 
            style={styles.image} 
            contentFit="cover"
            transition={300}
        />
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

  const renderHeader = () => (
    <View>
        {sliderPosts.length > 0 && (
            <View style={styles.sliderSection}>
                <Text style={styles.sectionHeader}>Featured News</Text>
                <FlatList 
                    data={sliderPosts}
                    renderItem={renderSliderItem}
                    horizontal
                    pagingEnabled
                    showsHorizontalScrollIndicator={false}
                    keyExtractor={(item) => 'slider_' + item.id}
                    snapToInterval={SCREEN_WIDTH - 30}
                    decelerationRate="fast"
                />
            </View>
        )}
        <Text style={[styles.sectionHeader, { marginTop: 20 }]}>Latest Stories</Text>
    </View>
  );

  if (error && page === 1 && !refreshing) {
    return (
      <View style={styles.center}>
        <Feather name="wifi-off" size={50} color="#cbd5e1" />
        <Text style={styles.errorText}>{error}</Text>
        <TouchableOpacity style={styles.retryBtn} onPress={() => { setLoading(true); fetchPosts(1); }}>
          <Text style={styles.retryText}>Retry Again</Text>
        </TouchableOpacity>
      </View>
    );
  }

  if (loading && page === 1) {
    return (
      <View style={styles.center}>
        <ActivityIndicator size="large" color="#ff3c00" />
      </View>
    );
  }

  return (
    <SafeAreaView style={styles.container}>
      <StatusBar barStyle="dark-content" />
      <View style={styles.appHeader}>
        <Text style={styles.logoText}>News<Text style={{color: '#ff3c00'}}>Cast</Text></Text>
        <TouchableOpacity onPress={() => router.push('/search' as any)}>
            <Feather name="search" size={22} color="#1e293b" />
        </TouchableOpacity>
      </View>

      <FlatList
        data={posts}
        ListHeaderComponent={renderHeader}
        renderItem={renderPost}
        keyExtractor={(item) => item.id.toString()}
        refreshControl={<RefreshControl refreshing={refreshing} onRefresh={onRefresh} />}
        onEndReached={loadMore}
        onEndReachedThreshold={0.5}
        contentContainerStyle={{ padding: 15 }}
      />
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: '#f8fafc', paddingTop: Platform.OS === 'android' ? StatusBar.currentHeight : 0 },
  center: { flex: 1, justifyContent: 'center', alignItems: 'center' },
  appHeader: { 
    flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', 
    paddingHorizontal: 20, paddingVertical: 15, backgroundColor: '#fff', borderBottomWidth: 1, borderBottomColor: '#f1f5f9' 
  },
  logoText: { fontSize: 22, fontWeight: '900', color: '#1e293b', letterSpacing: -0.5 },
  sectionHeader: { fontSize: 18, fontWeight: '900', color: '#1e293b', marginBottom: 15, paddingHorizontal: 5 },
  
  sliderSection: { marginBottom: 10 },
  sliderCard: { width: SCREEN_WIDTH - 40, height: 220, marginRight: 10, borderRadius: 20, overflow: 'hidden', position: 'relative' },
  sliderImage: { width: '100%', height: '100%' },
  sliderOverlay: { 
    position: 'absolute', bottom: 0, left: 0, right: 0, padding: 20, 
    backgroundColor: 'rgba(0,0,0,0.5)', justifyContent: 'flex-end' 
  },
  sliderCategory: { color: '#ff3c00', fontSize: 12, fontWeight: '800', textTransform: 'uppercase', marginBottom: 5 },
  sliderTitle: { color: '#fff', fontSize: 18, fontWeight: '800', lineHeight: 24 },

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
  image: { width: '100%', height: 200 },
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
  errorText: { color: '#64748b', fontSize: 16, marginTop: 15, marginBottom: 20, fontWeight: '600' },
  retryBtn: { backgroundColor: '#ff3c00', paddingHorizontal: 30, paddingVertical: 12, borderRadius: 10 },
  retryText: { color: '#fff', fontWeight: '800', fontSize: 15 },
});
