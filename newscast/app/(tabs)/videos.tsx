import React, { useState, useEffect } from 'react';
import { View, Text, FlatList, Image, TouchableOpacity, StyleSheet, ActivityIndicator, Linking } from 'react-native';
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
        <Image source={{ uri: item.featured_image }} style={styles.thumb} />
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

  if (loading) return <View style={styles.center}><ActivityIndicator color="#ff3c00" size="large" /></View>;

  return (
    <View style={styles.container}>
      <FlatList
        data={videos}
        renderItem={renderVideo}
        keyExtractor={(item) => item.id.toString()}
        contentContainerStyle={{ padding: 15 }}
      />
    </View>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: '#0f172a' }, // Dark theme for video section
  center: { flex: 1, justifyContent: 'center', alignItems: 'center' },
  card: { backgroundColor: '#1e293b', borderRadius: 20, marginBottom: 20, overflow: 'hidden' },
  thumbWrap: { width: '100%', height: 220, position: 'relative' },
  thumb: { width: '100%', height: '100%', objectFit: 'cover' },
  playOverlay: { position: 'absolute', inset: 0, backgroundColor: 'rgba(0,0,0,0.3)', justifyContent: 'center', alignItems: 'center' },
  playCircle: { width: 60, height: 60, borderRadius: 30, backgroundColor: 'rgba(255, 60, 0, 0.9)', justifyContent: 'center', alignItems: 'center', shadowColor: '#ff3c00', shadowOpacity: 0.5, shadowRadius: 15 },
  info: { padding: 15 },
  title: { fontSize: 16, fontWeight: '800', color: '#fff', lineHeight: 22, marginBottom: 5 },
  sub: { fontSize: 12, color: '#94a3b8', fontWeight: '600' }
});
