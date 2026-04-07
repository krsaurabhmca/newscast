import React, { useState, useEffect } from 'react';
import { View, Text, ScrollView, Image, StyleSheet, ActivityIndicator, useWindowDimensions, TouchableOpacity } from 'react-native';
import { useLocalSearchParams, useRouter } from 'expo-router';
import RenderHtml from 'react-native-render-html';
import { Feather } from '@expo/vector-icons';
import { getAction } from '../../config/api';

export default function ArticleDetail() {
  const { id } = useLocalSearchParams();
  const router = useRouter();
  const { width } = useWindowDimensions();
  
  const [post, setPost] = useState<any>(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    fetchDetail();
  }, [id]);

  const fetchDetail = async () => {
    const res = await getAction('post_detail', { id });
    if(res.success) setPost(res.data);
    setLoading(false);
  };

  if(loading) return <View style={styles.center}><ActivityIndicator color="#ff3c00" size="large" /></View>;
  if(!post) return <View style={styles.center}><Text>Post not found</Text></View>;

  return (
    <View style={styles.container}>
      <View style={styles.header}>
        <TouchableOpacity onPress={() => router.back()} style={styles.backBtn}>
            <Feather name="arrow-left" size={24} color="#1e293b" />
        </TouchableOpacity>
        <Text style={styles.headerTitle} numberOfLines={1}>{post.category_name}</Text>
        <TouchableOpacity style={styles.shareBtn}>
            <Feather name="share-2" size={20} color="#64748b" />
        </TouchableOpacity>
      </View>

      <ScrollView contentContainerStyle={styles.content}>
        <Text style={styles.title}>{post.title}</Text>
        
        <View style={styles.metaRow}>
            <View style={styles.metaItem}>
                <Feather name="calendar" size={14} color="#94a3b8" />
                <Text style={styles.metaText}>{new Date(post.published_at).toLocaleDateString()}</Text>
            </View>
            <View style={styles.metaItem}>
                <Feather name="eye" size={14} color="#94a3b8" />
                <Text style={styles.metaText}>{post.views} views</Text>
            </View>
        </View>

        {post.featured_image && (
            <Image source={{ uri: post.featured_image }} style={styles.image} resizeMode="cover" />
        )}

        <View style={styles.htmlWrap}>
            <RenderHtml
                contentWidth={width - 40}
                source={{ html: post.content }}
                tagsStyles={{
                    p: { fontSize: 16, lineHeight: 26, color: '#334155', marginBottom: 15 },
                    h1: { fontSize: 24, fontWeight: '800', color: '#1e293b', marginTop: 10, marginBottom: 15 },
                    h2: { fontSize: 20, fontWeight: '700', color: '#1e293b', marginTop: 10, marginBottom: 15 },
                    img: { borderRadius: 12, marginVertical: 10 }
                }}
            />
        </View>
      </ScrollView>
    </View>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: '#fff' },
  center: { flex: 1, justifyContent: 'center', alignItems: 'center' },
  header: { 
     flexDirection: 'row', alignItems: 'center', paddingVertical: 15, paddingHorizontal: 20,
     borderBottomWidth: 1, borderBottomColor: '#f1f5f9'
  },
  backBtn: { width: 40, height: 40, borderRadius: 20, backgroundColor: '#f1f5f9', justifyContent: 'center', alignItems: 'center' },
  headerTitle: { flex: 1, marginLeft: 15, fontSize: 16, fontWeight: '800', color: '#1e293b', textTransform: 'uppercase' },
  shareBtn: { width: 40, height: 40, justifyContent: 'center', alignItems: 'center' },
  content: { padding: 20 },
  title: { fontSize: 26, fontWeight: '900', color: '#1e293b', lineHeight: 34, marginBottom: 15 },
  metaRow: { flexDirection: 'row', gap: 20, marginBottom: 20 },
  metaItem: { flexDirection: 'row', alignItems: 'center', gap: 6 },
  metaText: { fontSize: 13, color: '#94a3b8', fontWeight: '600' },
  image: { width: '100%', height: 240, borderRadius: 16, marginBottom: 25 },
  htmlWrap: { paddingBottom: 40 }
});
