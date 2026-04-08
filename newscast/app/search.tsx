import React, { useState, useEffect } from 'react';
import { View, Text, StyleSheet, TextInput, FlatList, TouchableOpacity, ActivityIndicator, Platform, StatusBar } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { Image } from 'expo-image';
import { Feather } from '@expo/vector-icons';
import { useRouter } from 'expo-router';
import { getAction, getFullUrl } from '../config/api';

export default function Search() {
  const router = useRouter();
  const [query, setQuery] = useState('');
  const [results, setResults] = useState([]);
  const [loading, setLoading] = useState(false);

  const handleSearch = async (text: string) => {
    setQuery(text);
    if (text.length > 2) {
      setLoading(true);
      const res = await getAction('search', { q: text });
      if (res.success) {
        setResults(res.data);
      }
      setLoading(false);
    } else {
      setResults([]);
    }
  };

  const renderItem = ({ item }: any) => (
    <TouchableOpacity 
      style={styles.card} 
      onPress={() => router.push(`/article/${item.id}` as any)}
    >
      <Image 
        source={{ uri: getFullUrl(item.featured_image) }} 
        style={styles.image} 
        contentFit="cover"
        transition={200}
      />
      <View style={styles.info}>
        <Text style={styles.title} numberOfLines={2}>{item.title}</Text>
        <Text style={styles.date}>{new Date(item.published_at).toLocaleDateString()}</Text>
      </View>
    </TouchableOpacity>
  );

  return (
    <SafeAreaView style={styles.container}>
      <StatusBar barStyle="dark-content" />
      <View style={styles.header}>
        <TouchableOpacity onPress={() => router.back()} style={styles.backBtn}>
          <Feather name="arrow-left" size={24} color="#1e293b" />
        </TouchableOpacity>
        <View style={styles.searchBar}>
          <Feather name="search" size={18} color="#94a3b8" />
          <TextInput
            style={styles.input}
            placeholder="Search news..."
            value={query}
            onChangeText={handleSearch}
            autoFocus
          />
          {query.length > 0 && (
            <TouchableOpacity onPress={() => handleSearch('')}>
              <Feather name="x" size={18} color="#94a3b8" />
            </TouchableOpacity>
          )}
        </View>
      </View>

      {loading ? (
        <View style={styles.center}>
          <ActivityIndicator size="large" color="#ff3c00" />
        </View>
      ) : (
        <FlatList
          data={results}
          renderItem={renderItem}
          keyExtractor={(item: any) => item.id.toString()}
          contentContainerStyle={styles.list}
          ListEmptyComponent={
            query.length > 2 ? (
              <View style={styles.empty}>
                <Feather name="frown" size={50} color="#cbd5e1" />
                <Text style={styles.emptyText}>No results found for "{query}"</Text>
              </View>
            ) : (
              <View style={styles.empty}>
                <Feather name="search" size={50} color="#cbd5e1" />
                <Text style={styles.emptyText}>Type at least 3 characters to search</Text>
              </View>
            )
          }
        />
      )}
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: '#f8fafc', paddingTop: Platform.OS === 'android' ? StatusBar.currentHeight : 0 },
  header: { 
    flexDirection: 'row', alignItems: 'center', padding: 15, backgroundColor: '#fff', 
    borderBottomWidth: 1, borderBottomColor: '#f1f5f9', gap: 15 
  },
  backBtn: { width: 40, height: 40, borderRadius: 20, backgroundColor: '#f1f5f9', justifyContent: 'center', alignItems: 'center' },
  searchBar: { 
    flex: 1, flexDirection: 'row', alignItems: 'center', backgroundColor: '#f1f5f9', 
    paddingHorizontal: 15, borderRadius: 12, height: 45 
  },
  input: { flex: 1, marginLeft: 10, fontSize: 16, color: '#1e293b', fontWeight: '600' },
  list: { padding: 15 },
  card: { 
    flexDirection: 'row', backgroundColor: '#fff', borderRadius: 12, marginBottom: 15, 
    overflow: 'hidden', elevation: 2, shadowOpacity: 0.05 
  },
  image: { width: 100, height: 80 },
  info: { flex: 1, padding: 10, justifyContent: 'center' },
  title: { fontSize: 14, fontWeight: '800', color: '#1e293b', lineHeight: 20 },
  date: { fontSize: 11, color: '#94a3b8', marginTop: 5, fontWeight: '600' },
  center: { flex: 1, justifyContent: 'center', alignItems: 'center' },
  empty: { flex: 1, alignItems: 'center', justifyContent: 'center', marginTop: 100 },
  emptyText: { marginTop: 15, fontSize: 16, color: '#94a3b8', fontWeight: '700', textAlign: 'center', paddingHorizontal: 40 },
});
