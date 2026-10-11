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
  Modal,
  Alert,
} from 'react-native';
import * as ImagePicker from 'expo-image-picker';
import {
  ImageIcon,
  Upload,
  Search,
  X,
  Check,
  Trash2,
  Copy,
  ChevronLeft,
  ChevronRight,
  ExternalLink,
  Camera,
} from '@/components/ui/icons';
import { MediaItem, getMediaList, uploadMediaImage, deleteMediaItem } from '@/utils/api';
import { Spacing } from '@/constants/theme';
import { useTheme } from '@/hooks/use-theme';

interface AdminMediaPickerModalProps {
  visible: boolean;
  portalUrl: string;
  onClose: () => void;
  onSelectImage?: (image: MediaItem) => void;
  title?: string;
}

export function AdminMediaPickerModal({
  visible,
  portalUrl,
  onClose,
  onSelectImage,
  title = 'Media Library',
}: AdminMediaPickerModalProps) {
  const theme = useTheme();

  const [items, setItems] = useState<MediaItem[]>([]);
  const [loading, setLoading] = useState(true);
  const [uploading, setUploading] = useState(false);
  const [search, setSearch] = useState('');
  const [page, setPage] = useState(1);
  const [totalPages, setTotalPages] = useState(1);
  const [totalCount, setTotalCount] = useState(0);
  const [selectedItem, setSelectedItem] = useState<MediaItem | null>(null);

  const loadMedia = useCallback(
    async (pageNum = 1, searchQuery = '') => {
      if (!portalUrl) return;
      setLoading(true);
      try {
        const data = await getMediaList(portalUrl, pageNum, searchQuery);
        setItems(data.items);
        setTotalPages(data.total_pages);
        setTotalCount(data.total);
        setPage(pageNum);
      } catch (err: any) {
        console.warn('Media fetch error', err);
      } finally {
        setLoading(false);
      }
    },
    [portalUrl]
  );

  useEffect(() => {
    if (visible && portalUrl) {
      loadMedia(1, search);
    }
  }, [visible, portalUrl, loadMedia]);

  const handleSearchSubmit = () => {
    loadMedia(1, search.trim());
  };

  const handlePickFromDevice = async () => {
    try {
      const permission = await ImagePicker.requestMediaLibraryPermissionsAsync();
      if (!permission.granted) {
        Alert.alert('Permission Required', 'Media library access is needed to upload photos');
        return;
      }

      const result = await ImagePicker.launchImageLibraryAsync({
        mediaTypes: ['images'],
        allowsEditing: false,
        quality: 0.8,
        base64: true,
      });

      if (!result.canceled && result.assets[0]?.base64) {
        setUploading(true);
        const base64Data = `data:image/jpeg;base64,${result.assets[0].base64}`;
        const uploaded = await uploadMediaImage(portalUrl, base64Data);

        // Reload list and auto-select
        await loadMedia(1);
        if (onSelectImage && uploaded.file_url) {
          onSelectImage({
            id: 0,
            filename: uploaded.file_name,
            original_name: uploaded.file_name,
            file_url: uploaded.file_url,
            file_size: 0,
            created_at: new Date().toISOString(),
          });
          onClose();
        }
      }
    } catch (err: any) {
      Alert.alert('Upload Failed', err.message || 'Error uploading image');
    } finally {
      setUploading(false);
    }
  };

  const handlePickFromCamera = async () => {
    try {
      const permission = await ImagePicker.requestCameraPermissionsAsync();
      if (!permission.granted) {
        Alert.alert('Permission Required', 'Camera access is needed to capture photos');
        return;
      }

      const result = await ImagePicker.launchCameraAsync({
        allowsEditing: false,
        quality: 0.85,
        base64: true,
      });

      if (!result.canceled && result.assets[0]?.base64) {
        setUploading(true);
        const base64Data = `data:image/jpeg;base64,${result.assets[0].base64}`;
        const uploaded = await uploadMediaImage(portalUrl, base64Data);

        await loadMedia(1);
        if (onSelectImage && uploaded.file_url) {
          onSelectImage({
            id: 0,
            filename: uploaded.file_name,
            original_name: uploaded.file_name,
            file_url: uploaded.file_url,
            file_size: 0,
            created_at: new Date().toISOString(),
          });
          onClose();
        }
      }
    } catch (err: any) {
      Alert.alert('Upload Failed', err.message || 'Error capturing image');
    } finally {
      setUploading(false);
    }
  };

  const handleSelectItem = (item: MediaItem) => {
    setSelectedItem(item);
    if (onSelectImage) {
      onSelectImage(item);
      onClose();
    }
  };

  const handleDeleteItem = (item: MediaItem) => {
    Alert.alert(
      'Delete Image',
      `Are you sure you want to delete "${item.filename}" from portal storage?`,
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
    <Modal visible={visible} animationType="slide" transparent onRequestClose={onClose}>
      <View style={styles.backdrop}>
        <View style={[styles.modalBox, { backgroundColor: theme.background }]}>
          {/* Header */}
          <View
            style={[
              styles.header,
              { borderBottomColor: 'rgba(0,0,0,0.06)', backgroundColor: theme.backgroundElement },
            ]}>
            <View style={styles.headerTitleRow}>
              <ImageIcon size={18} color="#dc2626" strokeWidth={2.2} />
              <Text style={[styles.heading, { color: theme.text }]}>{title}</Text>
              <Text style={[styles.countBadge, { color: theme.textSecondary }]}>
                ({totalCount} items)
              </Text>
            </View>

            <View style={styles.headerActions}>
              <TouchableOpacity
                style={[styles.uploadBtn, uploading && styles.btnDisabled]}
                onPress={handlePickFromDevice}
                disabled={uploading}>
                {uploading ? (
                  <ActivityIndicator size="small" color="#fff" />
                ) : (
                  <>
                    <Upload size={13} color="#fff" strokeWidth={2.4} style={{ marginRight: 4 }} />
                    <Text style={styles.uploadBtnText}>Upload</Text>
                  </>
                )}
              </TouchableOpacity>

              <TouchableOpacity
                style={[styles.cameraActionBtn, { backgroundColor: theme.backgroundSelected }]}
                onPress={handlePickFromCamera}
                disabled={uploading}>
                <Camera size={14} color={theme.text} strokeWidth={2.2} />
              </TouchableOpacity>

              <TouchableOpacity style={styles.closeBtn} onPress={onClose}>
                <X size={18} color={theme.textSecondary} />
              </TouchableOpacity>
            </View>
          </View>

          {/* Search bar */}
          <View style={[styles.searchBar, { backgroundColor: theme.backgroundSelected }]}>
            <Search size={14} color={theme.textSecondary} style={{ marginRight: 6 }} />
            <TextInput
              style={[styles.searchInput, { color: theme.text }]}
              placeholder="Search media by filename..."
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
                  loadMedia(1, '');
                }}>
                <X size={14} color={theme.textSecondary} />
              </TouchableOpacity>
            )}
          </View>

          {/* Media Grid */}
          {loading ? (
            <View style={styles.centerBox}>
              <ActivityIndicator size="large" color="#dc2626" />
              <Text style={[styles.centerText, { color: theme.textSecondary }]}>
                Loading media gallery...
              </Text>
            </View>
          ) : items.length === 0 ? (
            <View style={styles.centerBox}>
              <ImageIcon size={44} color={theme.textSecondary} strokeWidth={1.5} />
              <Text style={[styles.emptyHeading, { color: theme.text }]}>No media found</Text>
              <Text style={[styles.emptySub, { color: theme.textSecondary }]}>
                Upload photos to use across articles and portal pages.
              </Text>
              <View style={{ flexDirection: 'row', gap: 10, marginTop: 6 }}>
                <TouchableOpacity style={styles.uploadBtn} onPress={handlePickFromDevice}>
                  <Upload size={13} color="#fff" strokeWidth={2.4} style={{ marginRight: 5 }} />
                  <Text style={styles.uploadBtnText}>Upload Photos</Text>
                </TouchableOpacity>
                <TouchableOpacity
                  style={[styles.cameraActionBtn, { backgroundColor: theme.backgroundSelected, paddingHorizontal: 12, width: 'auto', flexDirection: 'row', gap: 4 }]}
                  onPress={handlePickFromCamera}>
                  <Camera size={14} color={theme.text} />
                  <Text style={{ fontSize: 12, fontWeight: '700', color: theme.text }}>Camera</Text>
                </TouchableOpacity>
              </View>
            </View>
          ) : (
            <ScrollView contentContainerStyle={styles.gridContent} showsVerticalScrollIndicator={false}>
              <View style={styles.grid}>
                {items.map((item) => {
                  const isSelected = selectedItem?.id === item.id;
                  const ext = (item.filename.split('.').pop() || 'IMG').toUpperCase();
                  return (
                    <TouchableOpacity
                      key={item.id}
                      style={[
                        styles.gridItem,
                        { backgroundColor: theme.backgroundElement, borderColor: isSelected ? '#dc2626' : 'rgba(0,0,0,0.08)' },
                        isSelected && styles.gridItemSelected,
                      ]}
                      onPress={() => handleSelectItem(item)}
                      activeOpacity={0.8}>
                      <View style={{ position: 'relative' }}>
                        <Image
                          source={{ uri: item.file_url }}
                          style={styles.thumbnail}
                          resizeMode="cover"
                        />
                        <View style={styles.extBadgeTag}>
                          <Text style={styles.extBadgeTagText}>{ext}</Text>
                        </View>
                      </View>

                      <View style={styles.itemMeta}>
                        <Text style={[styles.fileName, { color: theme.text }]} numberOfLines={1}>
                          {item.original_name || item.filename}
                        </Text>
                        <Text style={[styles.fileDim, { color: theme.textSecondary }]}>
                          {item.file_size
                            ? `${Math.round(item.file_size / 1024)} KB`
                            : ''}
                        </Text>
                      </View>

                      {/* Quick delete icon on top right */}
                      {!onSelectImage && (
                        <TouchableOpacity
                          style={styles.deleteBadge}
                          onPress={() => handleDeleteItem(item)}>
                          <Trash2 size={11} color="#ef4444" />
                        </TouchableOpacity>
                      )}
                    </TouchableOpacity>
                  );
                })}
              </View>

              {/* Pagination */}
              {totalPages > 1 && (
                <View style={styles.paginationRow}>
                  <TouchableOpacity
                    style={[styles.pageBtn, page <= 1 && styles.btnDisabled]}
                    onPress={() => loadMedia(page - 1, search)}
                    disabled={page <= 1}>
                    <ChevronLeft size={14} color={theme.text} />
                    <Text style={[styles.pageBtnText, { color: theme.text }]}>Previous</Text>
                  </TouchableOpacity>

                  <Text style={[styles.pageInfo, { color: theme.textSecondary }]}>
                    Page {page} of {totalPages}
                  </Text>

                  <TouchableOpacity
                    style={[styles.pageBtn, page >= totalPages && styles.btnDisabled]}
                    onPress={() => loadMedia(page + 1, search)}
                    disabled={page >= totalPages}>
                    <Text style={[styles.pageBtnText, { color: theme.text }]}>Next</Text>
                    <ChevronRight size={14} color={theme.text} />
                  </TouchableOpacity>
                </View>
              )}
            </ScrollView>
          )}
        </View>
      </View>
    </Modal>
  );
}

const styles = StyleSheet.create({
  backdrop: {
    flex: 1,
    backgroundColor: 'rgba(0,0,0,0.5)',
    justifyContent: 'flex-end',
  },
  modalBox: {
    height: '88%',
    borderTopLeftRadius: 20,
    borderTopRightRadius: 20,
    overflow: 'hidden',
  },
  header: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    paddingHorizontal: Spacing.four,
    paddingVertical: 12,
    borderBottomWidth: 1,
  },
  headerTitleRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 6,
  },
  heading: {
    fontSize: 16,
    fontWeight: '800',
  },
  countBadge: {
    fontSize: 12,
  },
  headerActions: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 10,
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
  closeBtn: {
    padding: 6,
  },
  searchBar: {
    flexDirection: 'row',
    alignItems: 'center',
    marginHorizontal: Spacing.four,
    marginTop: 10,
    marginBottom: 8,
    paddingHorizontal: 10,
    paddingVertical: 7,
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
    fontSize: 15,
    fontWeight: '700',
  },
  emptySub: {
    fontSize: 12,
    textAlign: 'center',
    maxWidth: 240,
    marginBottom: 8,
  },
  gridContent: {
    padding: Spacing.four,
    paddingBottom: 40,
  },
  grid: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: 10,
  },
  gridItem: {
    width: '31%',
    borderRadius: 10,
    borderWidth: 1,
    overflow: 'hidden',
    position: 'relative',
  },
  gridItemSelected: {
    borderWidth: 2,
    borderColor: '#dc2626',
  },
  thumbnail: {
    width: '100%',
    height: 85,
    backgroundColor: 'rgba(0,0,0,0.05)',
  },
  itemMeta: {
    padding: 6,
  },
  fileName: {
    fontSize: 11,
    fontWeight: '600',
  },
  fileDim: {
    fontSize: 9,
    marginTop: 2,
  },
  deleteBadge: {
    position: 'absolute',
    top: 4,
    right: 4,
    backgroundColor: 'rgba(255,255,255,0.9)',
    borderRadius: 10,
    padding: 4,
  },
  paginationRow: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    marginTop: 16,
    paddingVertical: 8,
  },
  pageBtn: {
    flexDirection: 'row',
    alignItems: 'center',
    paddingHorizontal: 12,
    paddingVertical: 6,
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
  cameraActionBtn: {
    width: 36,
    height: 36,
    borderRadius: 8,
    alignItems: 'center',
    justifyContent: 'center',
  },
  extBadgeTag: {
    position: 'absolute',
    top: 4,
    left: 4,
    backgroundColor: 'rgba(15, 23, 42, 0.75)',
    paddingHorizontal: 4,
    paddingVertical: 1,
    borderRadius: 3,
  },
  extBadgeTagText: {
    color: '#fff',
    fontSize: 8,
    fontWeight: '800',
  },
  btnDisabled: {
    opacity: 0.5,
  },
});
