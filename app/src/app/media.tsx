import React, { useState, useEffect, useCallback } from 'react';
import {
  View,
  Text,
  TextInput,
  TouchableOpacity,
  ScrollView,
  Image,
  StyleSheet,
  ActivityIndicator,
  Alert,
  Clipboard,
  Dimensions,
} from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { StatusBar } from 'react-native';
import { useRouter } from 'expo-router';
import * as ImagePicker from 'expo-image-picker';
import {
  ArrowLeft,
  ImageIcon,
  Upload,
  Search,
  X,
  Trash2,
  Copy,
  ChevronLeft,
  ChevronRight,
  ExternalLink,
  Check,
} from '@/components/ui/icons';
import { MediaItem, getMediaList, uploadMediaImage, deleteMediaItem } from '@/utils/api';
import { getStoredPortalUrl } from '@/utils/storage';
import { useTheme } from '@/hooks/use-theme';
import { Spacing } from '@/constants/theme';

const { width } = Dimensions.get('window');
const COLUMN_WIDTH = (width - 48) / 3;

export default function MediaScreen() {
  const router = useRouter();
  const theme = useTheme();

  const [portalUrl, setPortalUrl] = useState('');
  const [items, setItems] = useState<MediaItem[]>([]);
  const [loading, setLoading] = useState(true);
  const [uploading, setUploading] = useState(false);
  const [search, setSearch] = useState('');
  const [page, setPage] = useState(1);
  const [totalPages, setTotalPages] = useState(1);
  const [totalCount, setTotalCount] = useState(0);
  const [copiedId, setCopiedId] = useState<number | null>(null);

  const fetchMedia = useCallback(
    async (url: string, pageNum = 1, query = '') => {
      setLoading(true);
      try {
        const data = await getMediaList(url, pageNum, query);
        setItems(data.items);
        setTotalPages(data.total_pages);
        setTotalCount(data.total);
        setPage(pageNum);
      } catch (err: any) {
        console.warn('Failed to load media', err);
      } finally {
        setLoading(false);
      }
    },
    []
  );

  useEffect(() => {
    getStoredPortalUrl().then((url: string | null) => {
      if (url) {
        setPortalUrl(url);
        fetchMedia(url, 1, '');
      }
    });
  }, [fetchMedia]);

  const handleSearchSubmit = () => {
    if (portalUrl) fetchMedia(portalUrl, 1, search.trim());
  };

  const handleUploadFromDevice = async () => {
    try {
      const permission = await ImagePicker.requestMediaLibraryPermissionsAsync();
      if (!permission.granted) {
        Alert.alert('Permission Required', 'Media library access is needed to upload photos');
        return;
      }

      const result = await ImagePicker.launchImageLibraryAsync({
        mediaTypes: ['images'],
        allowsEditing: false,
        quality: 0.85,
        base64: true,
      });

      if (!result.canceled && result.assets[0]?.base64) {
        setUploading(true);
        const base64Data = `data:image/jpeg;base64,${result.assets[0].base64}`;
        await uploadMediaImage(portalUrl, base64Data);
        Alert.alert('Uploaded', 'Photo uploaded to media library successfully!');
        fetchMedia(portalUrl, 1);
      }
    } catch (err: any) {
      Alert.alert('Upload Failed', err.message || 'Error uploading photo');
    } finally {
      setUploading(false);
    }
  };

  const handleCopyUrl = (item: MediaItem) => {
    Clipboard.setString(item.file_url);
    setCopiedId(item.id);
    setTimeout(() => setCopiedId(null), 2000);
    Alert.alert('Copied', 'Image URL copied to clipboard');
  };

  const handleDelete = (item: MediaItem) => {
    Alert.alert(
      'Delete Image',
      `Delete "${item.filename}" from portal? Articles using this image may show broken thumbnails.`,
      [
        { text: 'Cancel', style: 'cancel' },
        {
          text: 'Delete',
          style: 'destructive',
          onPress: async () => {
            try {
              await deleteMediaItem(portalUrl, item.id, item.filename);
              setItems((prev) => prev.filter((i) => i.id !== item.id));
              setTotalCount((c) => Math.max(0, c - 1));
            } catch (err: any) {
              Alert.alert('Error', err.message || 'Failed to delete image');
            }
          },
        },
      ]
    );
  };

  return (
    <SafeAreaView style={[styles.safeArea, { backgroundColor: theme.background }]}>
      <StatusBar barStyle="dark-content" backgroundColor="#ffffff" animated={true} />

      {/* Header */}
      <View
        style={[
          styles.header,
          { backgroundColor: theme.backgroundElement, borderBottomColor: 'rgba(0,0,0,0.06)' },
        ]}>
        <TouchableOpacity
          style={[styles.backBtn, { backgroundColor: theme.backgroundSelected }]}
          onPress={() => router.back()}
          hitSlop={{ top: 10, bottom: 10, left: 10, right: 10 }}>
          <ArrowLeft size={18} color={theme.text} strokeWidth={2.4} />
        </TouchableOpacity>

        <View style={styles.titleContainer}>
          <ImageIcon size={17} color="#dc2626" strokeWidth={2.4} />
          <Text style={[styles.title, { color: theme.text }]}>Media Library</Text>
          <Text style={[styles.countBadge, { color: theme.textSecondary }]}>
            ({totalCount})
          </Text>
        </View>

        <TouchableOpacity
          style={[styles.uploadBtn, uploading && styles.btnDisabled]}
          onPress={handleUploadFromDevice}
          disabled={uploading}>
          {uploading ? (
            <ActivityIndicator size="small" color="#fff" />
          ) : (
            <>
              <Upload size={13} color="#fff" strokeWidth={2.5} style={{ marginRight: 4 }} />
              <Text style={styles.uploadBtnText}>Upload</Text>
            </>
          )}
        </TouchableOpacity>
      </View>

      {/* Search Input */}
      <View
        style={[
          styles.searchBar,
          { backgroundColor: theme.backgroundElement, borderBottomColor: 'rgba(0,0,0,0.06)' },
        ]}>
        <View style={[styles.searchInputWrapper, { backgroundColor: theme.backgroundSelected }]}>
          <Search size={14} color={theme.textSecondary} style={{ marginRight: 6 }} />
          <TextInput
            style={[styles.searchInput, { color: theme.text }]}
            placeholder="Search images by filename..."
            placeholderTextColor={theme.textSecondary}
            value={search}
            onChangeText={setSearch}
            onSubmitEditing={handleSearchSubmit}
            returnKeyType="search"
          />
          {search.length > 0 && (
            <TouchableOpacity
              onPress={() => {
                setSearch('');
                if (portalUrl) fetchMedia(portalUrl, 1, '');
              }}>
              <X size={14} color={theme.textSecondary} />
            </TouchableOpacity>
          )}
        </View>
      </View>

      {/* Main Grid */}
      {loading ? (
        <View style={styles.centerBox}>
          <ActivityIndicator size="large" color="#dc2626" />
          <Text style={[styles.centerText, { color: theme.textSecondary }]}>
            Loading media files...
          </Text>
        </View>
      ) : items.length === 0 ? (
        <View style={styles.centerBox}>
          <ImageIcon size={48} color={theme.textSecondary} strokeWidth={1.5} />
          <Text style={[styles.emptyHeading, { color: theme.text }]}>No images found</Text>
          <Text style={[styles.emptySub, { color: theme.textSecondary }]}>
            Upload photos from your mobile device to use in articles.
          </Text>
          <TouchableOpacity style={styles.uploadBtn} onPress={handleUploadFromDevice}>
            <Upload size={14} color="#fff" strokeWidth={2.4} style={{ marginRight: 6 }} />
            <Text style={styles.uploadBtnText}>Upload First Image</Text>
          </TouchableOpacity>
        </View>
      ) : (
        <ScrollView contentContainerStyle={styles.scrollList} showsVerticalScrollIndicator={false}>
          <View style={styles.grid}>
            {items.map((item) => {
              const isCopied = copiedId === item.id;
              return (
                <View
                  key={item.id}
                  style={[
                    styles.card,
                    { backgroundColor: theme.backgroundElement, borderColor: 'rgba(0,0,0,0.07)' },
                  ]}>
                  <Image
                    source={{ uri: item.file_url }}
                    style={styles.cardImage}
                    resizeMode="cover"
                  />

                  <View style={styles.cardMeta}>
                    <Text style={[styles.cardTitle, { color: theme.text }]} numberOfLines={1}>
                      {item.original_name || item.filename}
                    </Text>
                    <Text style={[styles.cardSub, { color: theme.textSecondary }]} numberOfLines={1}>
                      {item.file_size ? `${Math.round(item.file_size / 1024)} KB` : 'Image'}
                    </Text>
                  </View>

                  <View style={styles.cardActions}>
                    <TouchableOpacity
                      style={[styles.actionBtn, { backgroundColor: theme.backgroundSelected }]}
                      onPress={() => handleCopyUrl(item)}>
                      {isCopied ? (
                        <Check size={11} color="#16a34a" />
                      ) : (
                        <Copy size={11} color={theme.text} />
                      )}
                    </TouchableOpacity>

                    <TouchableOpacity
                      style={[styles.actionBtn, styles.deleteBtn]}
                      onPress={() => handleDelete(item)}>
                      <Trash2 size={11} color="#ef4444" />
                    </TouchableOpacity>
                  </View>
                </View>
              );
            })}
          </View>

          {/* Pagination */}
          {totalPages > 1 && (
            <View style={styles.paginationRow}>
              <TouchableOpacity
                style={[styles.pageBtn, page <= 1 && styles.btnDisabled]}
                onPress={() => portalUrl && fetchMedia(portalUrl, page - 1, search)}
                disabled={page <= 1}>
                <ChevronLeft size={14} color={theme.text} />
                <Text style={[styles.pageBtnText, { color: theme.text }]}>Previous</Text>
              </TouchableOpacity>

              <Text style={[styles.pageInfo, { color: theme.textSecondary }]}>
                Page {page} of {totalPages}
              </Text>

              <TouchableOpacity
                style={[styles.pageBtn, page >= totalPages && styles.btnDisabled]}
                onPress={() => portalUrl && fetchMedia(portalUrl, page + 1, search)}
                disabled={page >= totalPages}>
                <Text style={[styles.pageBtnText, { color: theme.text }]}>Next</Text>
                <ChevronRight size={14} color={theme.text} />
              </TouchableOpacity>
            </View>
          )}
        </ScrollView>
      )}
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  safeArea: {
    flex: 1,
  },
  header: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    paddingHorizontal: Spacing.four,
    paddingVertical: 10,
    borderBottomWidth: 1,
  },
  backBtn: {
    width: 36,
    height: 36,
    borderRadius: 18,
    alignItems: 'center',
    justifyContent: 'center',
  },
  titleContainer: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 6,
  },
  title: {
    fontSize: 16,
    fontWeight: '800',
  },
  countBadge: {
    fontSize: 12,
  },
  uploadBtn: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: '#dc2626',
    paddingHorizontal: 12,
    paddingVertical: 7,
    borderRadius: 8,
  },
  uploadBtnText: {
    color: '#fff',
    fontSize: 12,
    fontWeight: '700',
  },
  searchBar: {
    paddingHorizontal: Spacing.four,
    paddingVertical: 8,
    borderBottomWidth: 1,
  },
  searchInputWrapper: {
    flexDirection: 'row',
    alignItems: 'center',
    paddingHorizontal: 10,
    paddingVertical: 6,
    borderRadius: 8,
  },
  searchInput: {
    flex: 1,
    fontSize: 13,
    padding: 0,
  },
  centerBox: {
    flex: 1,
    alignItems: 'center',
    justifyContent: 'center',
    padding: 30,
    gap: 10,
  },
  centerText: {
    fontSize: 13,
  },
  emptyHeading: {
    fontSize: 16,
    fontWeight: '700',
  },
  emptySub: {
    fontSize: 13,
    textAlign: 'center',
    maxWidth: 240,
    marginBottom: 12,
  },
  scrollList: {
    padding: Spacing.four,
    paddingBottom: 40,
  },
  grid: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: 12,
  },
  card: {
    width: COLUMN_WIDTH,
    borderRadius: 12,
    borderWidth: 1,
    overflow: 'hidden',
  },
  cardImage: {
    width: '100%',
    height: COLUMN_WIDTH,
    backgroundColor: 'rgba(0,0,0,0.05)',
  },
  cardMeta: {
    padding: 6,
  },
  cardTitle: {
    fontSize: 11,
    fontWeight: '700',
  },
  cardSub: {
    fontSize: 10,
    marginTop: 2,
  },
  cardActions: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    paddingHorizontal: 6,
    paddingBottom: 6,
    gap: 6,
  },
  actionBtn: {
    flex: 1,
    alignItems: 'center',
    justifyContent: 'center',
    paddingVertical: 5,
    borderRadius: 6,
  },
  deleteBtn: {
    backgroundColor: '#fee2e2',
  },
  paginationRow: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    marginTop: 20,
    paddingVertical: 8,
  },
  pageBtn: {
    flexDirection: 'row',
    alignItems: 'center',
    paddingHorizontal: 12,
    paddingVertical: 7,
    borderRadius: 6,
    backgroundColor: 'rgba(0,0,0,0.05)',
    gap: 4,
  },
  pageBtnText: {
    fontSize: 12,
    fontWeight: '600',
  },
  pageInfo: {
    fontSize: 12,
  },
  btnDisabled: {
    opacity: 0.5,
  },
});
