import React, { useState, useEffect } from 'react';
import { View, Text, FlatList, TouchableOpacity, StyleSheet, ActivityIndicator, Linking, Platform, StatusBar } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { Image } from 'expo-image';
import { Feather } from '@expo/vector-icons';
import { getAction } from '../../config/api';

export default function Videos() {
  const [videos, setVideos] = useState<any[]>([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    fetchVideos();
  }, []);

  const fetchVideos = async () => {
    const res = await getAction('videos');
    if (res.success) {
      setVideos(res.data);
    }
    setLoading(false);
  };

  const handlePlay = (url: string | null) => {
    if (url) Linking.openURL(url);
  };

  const renderVideo = ({ item }: { item: any }) => (
    <TouchableOpacity style={styles.card} onPress={() => handlePlay(item.video_url)}>
      <View style={styles.thumbWrap}>
        <Image 
          source={{ uri: item.featured_image }} 
          style={styles.thumb} 
          contentFit="cover"
          transition={300}
        />
        <View style={styles.playOverlay}>
          <View style={styles.playCircle}>
            <Feather name="play" size={24} color="#fff" style={{ marginLeft: 3 }} />
          </View>
        </View>
      </View>
      <View style={styles.info}>
        <Text style={styles.title} numberOfLines={2}>{item.title}</Text>
        <Text style={styles.sub}>Touch to play on YouTube</Text>
      </View>
    </TouchableOpacity>
  );

  if (loading) return <View style={[styles.center, {backgroundColor: '#0f172a'}]}><ActivityIndicator color="#ff3c00" size="large" /></View>;

  return (
    <SafeAreaView style={styles.container}>
      <StatusBar barStyle="light-content" />
      <View style={styles.appHeader}>
          <Text style={styles.logoText}>Video <Text style={{color: '#ff3c00'}}>Feed</Text></Text>
      </View>
      <FlatList
        data={videos}
        renderItem={renderVideo}
        keyExtractor={(item) => item.id.toString()}
        contentContainerStyle={{ padding: 15 }}
      />
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: '#0f172a', paddingTop: Platform.OS === 'android' ? StatusBar.currentHeight : 0 },
  center: { flex: 1, justifyContent: 'center', alignItems: 'center' },
  appHeader: { 
    paddingHorizontal: 20, paddingVertical: 15, backgroundColor: '#1e293b', borderBottomWidth: 1, borderBottomColor: '#334155' 
  },
  logoText: { fontSize: 22, fontWeight: '900', color: '#fff' },
  card: { backgroundColor: '#1e293b', borderRadius: 20, marginBottom: 20, overflow: 'hidden', elevation: 5 },
  thumbWrap: { width: '100%', height: 220, position: 'relative' },
  thumb: { width: '100%', height: '100%' },
  playOverlay: { position: 'absolute', inset: 0, backgroundColor: 'rgba(0,0,0,0.2)', justifyContent: 'center', alignItems: 'center' },
  playCircle: { width: 60, height: 60, borderRadius: 30, backgroundColor: 'rgba(255, 60, 0, 0.9)', justifyContent: 'center', alignItems: 'center', shadowColor: '#ff3c00', shadowOpacity: 0.5, shadowRadius: 15 },
  info: { padding: 15 },
  title: { fontSize: 16, fontWeight: '800', color: '#fff', lineHeight: 22, marginBottom: 5 },
  sub: { fontSize: 12, color: '#94a3b8', fontWeight: '600' }
});
