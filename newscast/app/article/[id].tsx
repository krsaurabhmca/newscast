import React, { useState, useEffect } from 'react';
import { View, Text, ScrollView, StyleSheet, ActivityIndicator, useWindowDimensions, TouchableOpacity, Platform, StatusBar, Share } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { Image } from 'expo-image';
import { useLocalSearchParams, useRouter } from 'expo-router';
import RenderHtml from 'react-native-render-html';
import { Feather } from '@expo/vector-icons';
import * as Speech from 'expo-speech';
import { getAction, BASE_URL, getFullUrl } from '../../config/api';

export default function ArticleDetail() {
  const { id } = useLocalSearchParams();
  const router = useRouter();
  const { width } = useWindowDimensions();
  
  const [post, setPost] = useState<any>(null);
  const [ads, setAds] = useState<any[]>([]);
  const [loading, setLoading] = useState(true);
  const [isSpeaking, setIsSpeaking] = useState(false);

  useEffect(() => {
    fetchDetail();
    fetchAds();
  }, [id]);

  const fetchDetail = async () => {
    const res = await getAction('post_detail', { id });
    if(res.success) setPost(res.data);
    setLoading(false);
  };

  const fetchAds = async () => {
    const res = await getAction('ads');
    if(res.success) setAds(res.data);
  };

  const handleShare = async () => {
    try {
      await Share.share({
        message: `${post.title}\n\nRead more at: ${BASE_URL}/article/${post.slug}`,
      });
    } catch (error) {
      console.log(error);
    }
  };

  const toggleSpeech = () => {
    if (isSpeaking) {
      Speech.stop();
      setIsSpeaking(false);
    } else {
      const cleanText = post.content.replace(/<[^>]*>?/gm, '');
      Speech.speak(cleanText, {
        onDone: () => setIsSpeaking(false),
        onStopped: () => setIsSpeaking(false),
      });
      setIsSpeaking(true);
    }
  };

  if(loading) return <View style={styles.center}><ActivityIndicator color="#ff3c00" size="large" /></View>;
  if(!post) return <View style={styles.center}><Text>Post not found</Text></View>;

  return (
    <SafeAreaView style={styles.container}>
      <StatusBar barStyle="dark-content" />
      <View style={styles.header}>
        <TouchableOpacity onPress={() => { Speech.stop(); router.back(); }} style={styles.backBtn}>
            <Feather name="arrow-left" size={24} color="#1e293b" />
        </TouchableOpacity>
        <Text style={styles.headerTitle} numberOfLines={1}>{post.category_name}</Text>
        <TouchableOpacity style={styles.shareBtn} onPress={handleShare}>
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
            <TouchableOpacity style={[styles.metaItem, isSpeaking && styles.speakingBadge]} onPress={toggleSpeech}>
                <Feather name={isSpeaking ? "volume-x" : "volume-2"} size={14} color={isSpeaking ? "#fff" : "#ff3c00"} />
                <Text style={[styles.metaText, { color: isSpeaking ? "#fff" : "#ff3c00" }]}>{isSpeaking ? "Stop" : "Listen"}</Text>
            </TouchableOpacity>
        </View>

        <Image 
            source={{ uri: getFullUrl(post.featured_image) }} 
            style={styles.image} 
            contentFit="cover"
            transition={300}
            cachePolicy="disk"
        />

        {/* In-Article Ad */}
        {ads.length > 0 && (
            <TouchableOpacity style={styles.adBox}>
                <Image 
                    source={{ uri: getFullUrl(ads[0].image_url) }} 
                    style={styles.adImage} 
                    contentFit="cover"
                    transition={300}
                />
                <View style={styles.adBadge}><Text style={styles.adBadgeText}>Ad</Text></View>
            </TouchableOpacity>
        )}

        <View style={styles.htmlWrap}>
            <RenderHtml
                contentWidth={width - 40}
                source={{ html: post.content, baseUrl: BASE_URL }}
                tagsStyles={{
                    p: { fontSize: 16, lineHeight: 26, color: '#334155', marginBottom: 15 },
                    h1: { fontSize: 24, fontWeight: '800', color: '#1e293b', marginTop: 10, marginBottom: 15 },
                    h2: { fontSize: 20, fontWeight: '700', color: '#1e293b', marginTop: 10, marginBottom: 15 },
                    img: { borderRadius: 12, marginVertical: 10 }
                }}
            />
        </View>

        {/* Related News Section */}
        {post.related && post.related.length > 0 && (
          <View style={styles.relatedSection}>
            <Text style={styles.relatedHeader}>Related News</Text>
            {post.related.map((item: any) => (
              <TouchableOpacity 
                key={item.id} 
                style={styles.relatedCard} 
                onPress={() => router.push({ pathname: '/article/[id]', params: { id: item.id } } as any)}
              >
                <Image source={{ uri: getFullUrl(item.featured_image) }} style={styles.relatedImage} />
                <View style={styles.relatedInfo}>
                  <Text style={styles.relatedTitle} numberOfLines={2}>{item.title}</Text>
                  <Text style={styles.relatedDate}>{new Date(item.published_at).toDateString()}</Text>
                </View>
              </TouchableOpacity>
            ))}
          </View>
        )}
      </ScrollView>
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: '#fff', paddingTop: Platform.OS === 'android' ? StatusBar.currentHeight : 0 },
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
  metaRow: { flexDirection: 'row', gap: 15, marginBottom: 20, flexWrap: 'wrap' },
  metaItem: { flexDirection: 'row', alignItems: 'center', gap: 6, backgroundColor: '#f8fafc', paddingVertical: 6, paddingHorizontal: 10, borderRadius: 8 },
  speakingBadge: { backgroundColor: '#ff3c00' },
  metaText: { fontSize: 13, color: '#94a3b8', fontWeight: '600' },
  image: { width: '100%', height: 240, borderRadius: 16, marginBottom: 20, backgroundColor: '#f1f5f9' },
  
  adBox: { width: '100%', height: 100, marginBottom: 20, position: 'relative', borderRadius: 12, overflow: 'hidden', backgroundColor: '#f1f5f9' },
  adImage: { width: '100%', height: '100%' },
  adBadge: { position: 'absolute', top: 5, right: 5, backgroundColor: 'rgba(0,0,0,0.5)', paddingHorizontal: 6, paddingVertical: 2, borderRadius: 4 },
  adBadgeText: { color: '#fff', fontSize: 10, fontWeight: '800' },

  htmlWrap: { paddingBottom: 20 },
  relatedSection: { marginTop: 30, borderTopWidth: 1, borderTopColor: '#f1f5f9', paddingTop: 25, paddingBottom: 40 },
  relatedHeader: { fontSize: 20, fontWeight: '900', color: '#1e293b', marginBottom: 20 },
  relatedCard: { flexDirection: 'row', marginBottom: 15, gap: 12 },
  relatedImage: { width: 100, height: 70, borderRadius: 10 },
  relatedInfo: { flex: 1, justifyContent: 'center' },
  relatedTitle: { fontSize: 14, fontWeight: '800', color: '#1e293b', lineHeight: 20 },
  relatedDate: { fontSize: 11, color: '#94a3b8', marginTop: 4 }
});
