import React, { useState, useEffect } from 'react';
import { View, Text, FlatList, TouchableOpacity, StyleSheet, ActivityIndicator, ScrollView, Platform, StatusBar } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { Image } from 'expo-image';
import { Feather } from '@expo/vector-icons';
import { getAction, getFullUrl } from '../../config/api';

export default function Digital() {
  const [data, setData] = useState<any>(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    fetchMedia();
  }, []);

  const fetchMedia = async () => {
    const res = await getAction('digital_media');
    if (res.success) {
      setData(res.data);
    }
    setLoading(false);
  };

  if (loading) return <View style={styles.center}><ActivityIndicator color="#ff3c00" size="large" /></View>;

  const renderEpaper = ({ item }: { item: any }) => (
    <TouchableOpacity style={styles.paperCard}>
      <Image 
        source={{ uri: getFullUrl(item.thumbnail) }} 
        style={styles.paperThumb} 
        contentFit="cover"
        transition={300}
      />
      <View style={styles.paperInfo}>
        <Text style={styles.paperDate}>{new Date(item.paper_date).toDateString()}</Text>
        <Text style={styles.paperTitle} numberOfLines={1}>{item.title}</Text>
        <View style={styles.btnRead}>
          <Feather name="eye" size={14} color="#fff" />
          <Text style={styles.btnText}>Read Now</Text>
        </View>
      </View>
    </TouchableOpacity>
  );

  const renderMagazine = ({ item }: { item: any }) => (
    <TouchableOpacity style={styles.magCard}>
      <Image 
        source={{ uri: getFullUrl(item.cover_image) }} 
        style={styles.magThumb} 
        contentFit="cover"
        transition={300}
      />
      <View style={styles.magInfo}>
        <Text style={styles.magMonth}>{new Date(item.issue_month).toLocaleDateString('en-US', { month: 'long', year: 'numeric' })}</Text>
        <Text style={styles.magTitle} numberOfLines={1}>{item.title}</Text>
      </View>
    </TouchableOpacity>
  );

  return (
    <SafeAreaView style={styles.container}>
      <StatusBar barStyle="dark-content" />
      <View style={styles.appHeader}>
          <Text style={styles.logoText}>Digital <Text style={{color: '#ff3c00'}}>Media</Text></Text>
      </View>
      <ScrollView contentContainerStyle={{ padding: 20 }}>
        {data?.epapers?.length > 0 && (
          <View style={{ marginBottom: 30 }}>
            <View style={styles.header}>
              <Text style={styles.sectionTitle}>Daily E-Papers</Text>
              <TouchableOpacity><Text style={styles.seeAll}>See All</Text></TouchableOpacity>
            </View>
            <FlatList
              data={data.epapers}
              renderItem={renderEpaper}
              horizontal
              showsHorizontalScrollIndicator={false}
              keyExtractor={(item) => item.id.toString()}
            />
          </View>
        )}

        {data?.magazines?.length > 0 && (
          <View>
            <View style={styles.header}>
              <Text style={styles.sectionTitle}>Monthly Magazines</Text>
              <TouchableOpacity><Text style={styles.seeAll}>See All</Text></TouchableOpacity>
            </View>
            <FlatList
              data={data.magazines}
              renderItem={renderMagazine}
              horizontal
              showsHorizontalScrollIndicator={false}
              keyExtractor={(item) => item.id.toString()}
            />
          </View>
        )}

        {!data?.epapers?.length && !data?.magazines?.length && (
          <View style={styles.empty}>
              <Feather name="book-open" size={60} color="#cbd5e1" />
              <Text style={styles.emptyText}>No digital media available</Text>
          </View>
        )}
      </ScrollView>
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
  header: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', marginBottom: 15 },
  sectionTitle: { fontSize: 18, fontWeight: '800', color: '#1e293b' },
  seeAll: { fontSize: 13, color: '#ff3c00', fontWeight: '800' },
  
  paperCard: { width: 180, marginRight: 20, backgroundColor: '#fff', borderRadius: 15, elevation: 3, shadowOpacity: 0.05, overflow: 'hidden' },
  paperThumb: { width: '100%', height: 240 },
  paperInfo: { padding: 12 },
  paperDate: { fontSize: 10, color: '#94a3b8', fontWeight: '700', textTransform: 'uppercase', marginBottom: 2 },
  paperTitle: { fontSize: 14, fontWeight: '800', color: '#1e293b', marginBottom: 10 },
  btnRead: { backgroundColor: '#ff3c00', paddingVertical: 6, paddingHorizontal: 10, borderRadius: 8, flexDirection: 'row', alignItems: 'center', gap: 5, justifyContent: 'center' },
  btnText: { color: '#fff', fontSize: 11, fontWeight: '800' },

  magCard: { width: 140, marginRight: 15 },
  magThumb: { width: '100%', height: 180, borderRadius: 12, shadowColor: '#000', shadowOpacity: 0.1, shadowRadius: 10 },
  magInfo: { marginTop: 10 },
  magMonth: { fontSize: 10, fontWeight: '800', color: '#ff3c00', textTransform: 'uppercase' },
  magTitle: { fontSize: 13, fontWeight: '700', color: '#1e293b' },

  empty: { flex: 1, alignItems: 'center', justifyContent: 'center', paddingVertical: 100 },
  emptyText: { marginTop: 15, fontSize: 16, color: '#94a3b8', fontWeight: '700' }
});
