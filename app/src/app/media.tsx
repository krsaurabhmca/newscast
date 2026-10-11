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
  Modal,
  Linking,
  RefreshControl,
  Share,
  Platform,
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
  Camera,
  LayoutGrid,
  List,
  Info,
} from '@/components/ui/icons';
import { MediaItem, getMediaList, uploadMediaImage, deleteMediaItem } from '@/utils/api';
import { getStoredPortalUrl } from '@/utils/storage';
import { useTheme } from '@/hooks/use-theme';
import { Spacing } from '@/constants/theme';

const { width } = Dimensions.get('window');
const GRID_CARD_WIDTH = (width - 36) / 2;

type FilterType = 'all' | 'webp' | 'jpg_png';
type ViewMode = 'grid' | 'list';

export default function MediaScreen() {
  const router = useRouter();
  const theme = useTheme();

  const [portalUrl, setPortalUrl] = useState('');
  const [items, setItems] = useState<MediaItem[]>([]);
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [uploading, setUploading] = useState(false);
  const [search, setSearch] = useState('');
  const [filterType, setFilterType] = useState<FilterType>('all');
  const [viewMode, setViewMode] = useState<ViewMode>('grid');

  // Pagination
  const [page, setPage] = useState(1);
  const [totalPages, setTotalPages] = useState(1);
  const [totalCount, setTotalCount] = useState(0);

  // Inspector / Preview Modal
  const [previewItem, setPreviewItem] = useState<MediaItem | null>(null);
  const [copiedId, setCopiedId] = useState<number | null>(null);
  const [toastMessage, setToastMessage] = useState<string | null>(null);

  const showToast = (msg: string) => {
    setToastMessage(msg);
    setTimeout(() => setToastMessage(null), 2500);
  };

  const fetchMedia = useCallback(
    async (url: string, pageNum = 1, query = '', isPull = false) => {
      if (isPull) setRefreshing(true);
      else setLoading(true);

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
        setRefreshing(false);
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

  const handleRefresh = () => {
    if (portalUrl) fetchMedia(portalUrl, 1, search.trim(), true);
  };

  const handleSearchSubmit = () => {
    if (portalUrl) fetchMedia(portalUrl, 1, search.trim());
  };

  // Upload from Device Library
  const handleUploadFromLibrary = async () => {
    try {
      if (Platform.OS === 'ios') {
        const permission = await ImagePicker.requestMediaLibraryPermissionsAsync();
        if (!permission.granted) {
          Alert.alert('Permission Required', 'Access to photos is needed to upload');
          return;
        }
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
        showToast('Photo uploaded successfully!');
        fetchMedia(portalUrl, 1, search.trim());
      }
    } catch (err: any) {
      Alert.alert('Upload Failed', err.message || 'Error uploading photo');
    } finally {
      setUploading(false);
    }
  };

  // Upload from Device Camera
  const handleUploadFromCamera = async () => {
    try {
      const permission = await ImagePicker.requestCameraPermissionsAsync();
      if (!permission.granted) {
        Alert.alert('Permission Required', 'Camera access is needed to take a photo');
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
        await uploadMediaImage(portalUrl, base64Data);
        showToast('Photo captured & uploaded successfully!');
        fetchMedia(portalUrl, 1, search.trim());
      }
    } catch (err: any) {
      Alert.alert('Camera Upload Failed', err.message || 'Error capturing photo');
    } finally {
      setUploading(false);
    }
  };

  const handleCopyUrl = (item: MediaItem) => {
    Clipboard.setString(item.file_url);
    setCopiedId(item.id);
    showToast('Image URL copied to clipboard');
    setTimeout(() => setCopiedId(null), 2000);
  };

  const handleDelete = (item: MediaItem) => {
    Alert.alert(
      'Delete Image',
      `Are you sure you want to delete "${item.filename}"? This will permanently remove the file from the media storage.`,
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
              showToast('Image deleted');
              if (previewItem?.id === item.id) {
                setPreviewItem(null);
              }
            } catch (err: any) {
              Alert.alert('Error', err.message || 'Failed to delete image');
            }
          },
        },
      ]
    );
  };

  const handleShareUrl = async (item: MediaItem) => {
    try {
      await Share.share({
        message: item.file_url,
        url: item.file_url,
        title: item.original_name || item.filename,
      });
    } catch (e) {
      console.warn('Share error', e);
    }
  };

  // Filter items by client-side extension filter
  const filteredItems = items.filter((item) => {
    if (filterType === 'all') return true;
    const ext = (item.filename.split('.').pop() || '').toLowerCase();
    if (filterType === 'webp') return ext === 'webp';
    if (filterType === 'jpg_png') return ['jpg', 'jpeg', 'png'].includes(ext);
    return true;
  });

  return (
    <SafeAreaView style={[styles.safeArea, { backgroundColor: theme.background }]}>
      <StatusBar barStyle="dark-content" backgroundColor="#ffffff" animated={true} />

      {/* Modern Header */}
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
          <Text style={[styles.title, { color: theme.text }]}>Media Gallery</Text>
          <View style={styles.countPill}>
            <Text style={styles.countPillText}>{totalCount}</Text>
          </View>
        </View>

        {/* View Mode Toggle (Grid vs List) */}
        <View style={[styles.viewSwitch, { backgroundColor: theme.backgroundSelected }]}>
          <TouchableOpacity
            style={[styles.viewSwitchBtn, viewMode === 'grid' && styles.viewSwitchActive]}
            onPress={() => setViewMode('grid')}>
            <LayoutGrid size={13} color={viewMode === 'grid' ? '#fff' : theme.textSecondary} />
          </TouchableOpacity>
          <TouchableOpacity
            style={[styles.viewSwitchBtn, viewMode === 'list' && styles.viewSwitchActive]}
            onPress={() => setViewMode('list')}>
            <List size={13} color={viewMode === 'list' ? '#fff' : theme.textSecondary} />
          </TouchableOpacity>
        </View>
      </View>

      {/* Search & Quick Actions Bar */}
      <View
        style={[
          styles.controlsBar,
          { backgroundColor: theme.backgroundElement, borderBottomColor: 'rgba(0,0,0,0.06)' },
        ]}>
        <View style={styles.searchRow}>
          <View style={[styles.searchInputWrapper, { backgroundColor: theme.backgroundSelected }]}>
            <Search size={14} color={theme.textSecondary} style={{ marginRight: 6 }} />
            <TextInput
              style={[styles.searchInput, { color: theme.text }]}
              placeholder="Search images by name..."
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

          {/* Upload Button */}
          <TouchableOpacity
            style={[styles.uploadBtn, uploading && styles.btnDisabled]}
            onPress={handleUploadFromLibrary}
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

          {/* Camera Button */}
          <TouchableOpacity
            style={[styles.cameraBtn, { backgroundColor: theme.backgroundSelected }]}
            onPress={handleUploadFromCamera}
            disabled={uploading}>
            <Camera size={15} color={theme.text} strokeWidth={2.2} />
          </TouchableOpacity>
        </View>

        {/* Filter Pills */}
        <View style={styles.filterPillsRow}>
          {(
            [
              { key: 'all', label: 'All Photos' },
              { key: 'webp', label: 'WebP Format' },
              { key: 'jpg_png', label: 'JPG / PNG' },
            ] as const
          ).map((tab) => {
            const isActive = filterType === tab.key;
            return (
              <TouchableOpacity
                key={tab.key}
                style={[
                  styles.filterChip,
                  isActive
                    ? styles.filterChipActive
                    : { backgroundColor: theme.backgroundSelected },
                ]}
                onPress={() => setFilterType(tab.key)}>
                <Text
                  style={[
                    styles.filterChipText,
                    isActive ? { color: '#fff' } : { color: theme.textSecondary },
                  ]}>
                  {tab.label}
                </Text>
              </TouchableOpacity>
            );
          })}
        </View>
      </View>

      {/* Uploading Banner */}
      {uploading && (
        <View style={styles.uploadBanner}>
          <ActivityIndicator size="small" color="#dc2626" style={{ marginRight: 8 }} />
          <Text style={styles.uploadBannerText}>Optimizing and uploading image...</Text>
        </View>
      )}

      {/* Main Content Area */}
      {loading ? (
        <View style={styles.centerBox}>
          <ActivityIndicator size="large" color="#dc2626" />
          <Text style={[styles.centerText, { color: theme.textSecondary }]}>
            Loading media files...
          </Text>
        </View>
      ) : filteredItems.length === 0 ? (
        <View style={styles.centerBox}>
          <View style={styles.emptyIconCircle}>
            <ImageIcon size={38} color="#dc2626" strokeWidth={1.8} />
          </View>
          <Text style={[styles.emptyHeading, { color: theme.text }]}>
            {search ? 'No matching images' : 'Your Media Gallery is Empty'}
          </Text>
          <Text style={[styles.emptySub, { color: theme.textSecondary }]}>
            {search
              ? 'Try adjusting your search terms or clearing the filter.'
              : 'Upload news photos from your gallery or capture fresh images with the camera.'}
          </Text>
          <View style={styles.emptyActionRow}>
            <TouchableOpacity style={styles.emptyUploadBtn} onPress={handleUploadFromLibrary}>
              <Upload size={14} color="#fff" strokeWidth={2.4} style={{ marginRight: 6 }} />
              <Text style={styles.uploadBtnText}>Upload from Gallery</Text>
            </TouchableOpacity>
            <TouchableOpacity
              style={[styles.emptyCameraBtn, { backgroundColor: theme.backgroundSelected }]}
              onPress={handleUploadFromCamera}>
              <Camera size={14} color={theme.text} strokeWidth={2.2} style={{ marginRight: 6 }} />
              <Text style={[styles.emptyCameraBtnText, { color: theme.text }]}>Take Photo</Text>
            </TouchableOpacity>
          </View>
        </View>
      ) : (
        <ScrollView
          contentContainerStyle={styles.scrollList}
          showsVerticalScrollIndicator={false}
          refreshControl={
            <RefreshControl
              refreshing={refreshing}
              onRefresh={handleRefresh}
              colors={['#dc2626']}
            />
          }>
          {/* View Mode: 2-Column Grid */}
          {viewMode === 'grid' ? (
            <View style={styles.gridContainer}>
              {filteredItems.map((item) => {
                const isCopied = copiedId === item.id;
                const ext = (item.filename.split('.').pop() || 'IMG').toUpperCase();
                return (
                  <View
                    key={item.id}
                    style={[
                      styles.gridCard,
                      {
                        backgroundColor: theme.backgroundElement,
                        borderColor: 'rgba(0,0,0,0.06)',
                      },
                    ]}>
                    {/* Thumbnail (Tap to open full preview) */}
                    <TouchableOpacity
                      activeOpacity={0.85}
                      onPress={() => setPreviewItem(item)}
                      style={styles.gridImageWrapper}>
                      <Image
                        source={{ uri: item.file_url }}
                        style={styles.gridImage}
                        resizeMode="cover"
                      />

                      {/* Format Badge */}
                      <View style={styles.extBadge}>
                        <Text style={styles.extBadgeText}>{ext}</Text>
                      </View>

                      {/* Size Badge */}
                      {item.file_size ? (
                        <View style={styles.sizeBadge}>
                          <Text style={styles.sizeBadgeText}>
                            {Math.round(item.file_size / 1024)} KB
                          </Text>
                        </View>
                      ) : null}
                    </TouchableOpacity>

                    {/* Metadata & Actions */}
                    <View style={styles.gridCardBody}>
                      <Text
                        style={[styles.gridTitle, { color: theme.text }]}
                        numberOfLines={1}>
                        {item.original_name || item.filename}
                      </Text>

                      <View style={styles.gridActionRow}>
                        <TouchableOpacity
                          style={[
                            styles.cardActionBtn,
                            { backgroundColor: theme.backgroundSelected },
                          ]}
                          onPress={() => handleCopyUrl(item)}>
                          {isCopied ? (
                            <Check size={12} color="#16a34a" />
                          ) : (
                            <Copy size={12} color={theme.text} />
                          )}
                        </TouchableOpacity>

                        <TouchableOpacity
                          style={[
                            styles.cardActionBtn,
                            { backgroundColor: '#eff6ff' },
                          ]}
                          onPress={() => setPreviewItem(item)}>
                          <Info size={12} color="#2563eb" />
                        </TouchableOpacity>

                        <TouchableOpacity
                          style={[styles.cardActionBtn, styles.deleteActionBtn]}
                          onPress={() => handleDelete(item)}>
                          <Trash2 size={12} color="#dc2626" />
                        </TouchableOpacity>
                      </View>
                    </View>
                  </View>
                );
              })}
            </View>
          ) : (
            /* View Mode: List View */
            <View style={styles.listContainer}>
              {filteredItems.map((item) => {
                const isCopied = copiedId === item.id;
                const ext = (item.filename.split('.').pop() || 'IMG').toUpperCase();
                const sizeKb = item.file_size ? `${Math.round(item.file_size / 1024)} KB` : '';
                return (
                  <View
                    key={item.id}
                    style={[
                      styles.listCard,
                      {
                        backgroundColor: theme.backgroundElement,
                        borderColor: 'rgba(0,0,0,0.06)',
                      },
                    ]}>
                    <TouchableOpacity
                      activeOpacity={0.85}
                      onPress={() => setPreviewItem(item)}
                      style={styles.listThumbWrapper}>
                      <Image
                        source={{ uri: item.file_url }}
                        style={styles.listThumb}
                        resizeMode="cover"
                      />
                      <View style={styles.listThumbBadge}>
                        <Text style={styles.listThumbBadgeText}>{ext}</Text>
                      </View>
                    </TouchableOpacity>

                    <View style={styles.listInfo}>
                      <Text
                        style={[styles.listTitle, { color: theme.text }]}
                        numberOfLines={1}>
                        {item.original_name || item.filename}
                      </Text>
                      {item.original_name && item.original_name !== item.filename ? (
                        <Text
                          style={[styles.listSubFilename, { color: theme.textSecondary }]}
                          numberOfLines={1}>
                          {item.filename}
                        </Text>
                      ) : null}

                      <View style={styles.listMetaRow}>
                        {sizeKb ? (
                          <View style={[styles.metaPill, { backgroundColor: theme.backgroundSelected }]}>
                            <Text style={[styles.metaPillText, { color: theme.textSecondary }]}>
                              {sizeKb}
                            </Text>
                          </View>
                        ) : null}
                      </View>
                    </View>

                    <View style={styles.listActions}>
                      <TouchableOpacity
                        style={[
                          styles.listActionBtn,
                          isCopied
                            ? { backgroundColor: '#dcfce7' }
                            : { backgroundColor: theme.backgroundSelected },
                        ]}
                        onPress={() => handleCopyUrl(item)}>
                        {isCopied ? (
                          <Check size={14} color="#16a34a" strokeWidth={2.5} />
                        ) : (
                          <Copy size={14} color={theme.text} />
                        )}
                      </TouchableOpacity>

                      <TouchableOpacity
                        style={[styles.listActionBtn, { backgroundColor: '#eff6ff' }]}
                        onPress={() => setPreviewItem(item)}>
                        <Info size={14} color="#2563eb" />
                      </TouchableOpacity>

                      <TouchableOpacity
                        style={[styles.listActionBtn, styles.deleteActionBtn]}
                        onPress={() => handleDelete(item)}>
                        <Trash2 size={14} color="#dc2626" />
                      </TouchableOpacity>
                    </View>
                  </View>
                );
              })}
            </View>
          )}

          {/* Pagination Controls */}
          {totalPages > 1 && (
            <View style={styles.paginationRow}>
              <TouchableOpacity
                style={[
                  styles.pageBtn,
                  page <= 1 && styles.btnDisabled,
                  { backgroundColor: theme.backgroundElement },
                ]}
                onPress={() => portalUrl && fetchMedia(portalUrl, page - 1, search)}
                disabled={page <= 1}>
                <ChevronLeft size={14} color={theme.text} />
                <Text style={[styles.pageBtnText, { color: theme.text }]}>Previous</Text>
              </TouchableOpacity>

              <Text style={[styles.pageInfo, { color: theme.textSecondary }]}>
                Page {page} of {totalPages}
              </Text>

              <TouchableOpacity
                style={[
                  styles.pageBtn,
                  page >= totalPages && styles.btnDisabled,
                  { backgroundColor: theme.backgroundElement },
                ]}
                onPress={() => portalUrl && fetchMedia(portalUrl, page + 1, search)}
                disabled={page >= totalPages}>
                <Text style={[styles.pageBtnText, { color: theme.text }]}>Next</Text>
                <ChevronRight size={14} color={theme.text} />
              </TouchableOpacity>
            </View>
          )}
        </ScrollView>
      )}

      {/* Full-Screen Image Preview & Details Inspector Modal */}
      {previewItem && (
        <Modal
          visible={true}
          transparent
          animationType="fade"
          onRequestClose={() => setPreviewItem(null)}>
          <View style={styles.previewBackdrop}>
            <View style={[styles.previewModalBox, { backgroundColor: theme.background }]}>
              {/* Modal Header */}
              <View style={styles.previewModalHeader}>
                <Text
                  style={[styles.previewModalTitle, { color: theme.text }]}
                  numberOfLines={1}>
                  {previewItem.original_name || previewItem.filename}
                </Text>
                <TouchableOpacity
                  style={[styles.previewCloseBtn, { backgroundColor: theme.backgroundSelected }]}
                  onPress={() => setPreviewItem(null)}>
                  <X size={16} color={theme.text} />
                </TouchableOpacity>
              </View>

              {/* Large Image View */}
              <View style={styles.previewImageBox}>
                <Image
                  source={{ uri: previewItem.file_url }}
                  style={styles.previewFullImage}
                  resizeMode="contain"
                />
              </View>

              {/* Image Details Cards */}
              <View style={styles.previewDetailsBox}>
                <View
                  style={[
                    styles.previewInfoRow,
                    { backgroundColor: theme.backgroundElement },
                  ]}>
                  <View style={styles.previewInfoCol}>
                    <Text style={[styles.previewInfoLabel, { color: theme.textSecondary }]}>
                      File Name
                    </Text>
                    <Text
                      style={[styles.previewInfoVal, { color: theme.text }]}
                      numberOfLines={1}>
                      {previewItem.filename}
                    </Text>
                  </View>

                  <View style={styles.previewInfoCol}>
                    <Text style={[styles.previewInfoLabel, { color: theme.textSecondary }]}>
                      Size
                    </Text>
                    <Text style={[styles.previewInfoVal, { color: theme.text }]}>
                      {previewItem.file_size
                        ? `${Math.round(previewItem.file_size / 1024)} KB`
                        : 'Image file'}
                    </Text>
                  </View>
                </View>

                {/* Direct URL Box */}
                <View
                  style={[
                    styles.urlBox,
                    { backgroundColor: theme.backgroundElement },
                  ]}>
                  <Text
                    style={[styles.urlText, { color: theme.textSecondary }]}
                    numberOfLines={1}>
                    {previewItem.file_url}
                  </Text>
                </View>

                {/* Action Buttons */}
                <View style={styles.previewActionGrid}>
                  <TouchableOpacity
                    style={[styles.previewActionBtn, styles.copyUrlBtn]}
                    onPress={() => handleCopyUrl(previewItem)}>
                    <Copy size={14} color="#fff" style={{ marginRight: 6 }} />
                    <Text style={styles.copyUrlBtnText}>Copy URL</Text>
                  </TouchableOpacity>

                  <TouchableOpacity
                    style={[
                      styles.previewActionBtn,
                      { backgroundColor: theme.backgroundSelected },
                    ]}
                    onPress={() => handleShareUrl(previewItem)}>
                    <ExternalLink size={14} color={theme.text} style={{ marginRight: 6 }} />
                    <Text style={[styles.previewBtnText, { color: theme.text }]}>Share</Text>
                  </TouchableOpacity>

                  <TouchableOpacity
                    style={[
                      styles.previewActionBtn,
                      styles.previewDeleteBtn,
                    ]}
                    onPress={() => handleDelete(previewItem)}>
                    <Trash2 size={14} color="#dc2626" style={{ marginRight: 6 }} />
                    <Text style={styles.previewDeleteBtnText}>Delete</Text>
                  </TouchableOpacity>
                </View>
              </View>
            </View>
          </View>
        </Modal>
      )}

      {/* Floating Action Button (FAB) for Quick Photo Upload */}
      <TouchableOpacity
        style={styles.fabUpload}
        onPress={handleUploadFromLibrary}
        activeOpacity={0.85}>
        <Upload size={22} color="#ffffff" strokeWidth={2.6} />
      </TouchableOpacity>

      {/* Toast Feedback */}
      {toastMessage && (
        <View style={styles.toast}>
          <Check size={14} color="#fff" style={{ marginRight: 6 }} />
          <Text style={styles.toastText}>{toastMessage}</Text>
        </View>
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
    gap: 7,
  },
  title: {
    fontSize: 16,
    fontWeight: '800',
  },
  countPill: {
    backgroundColor: 'rgba(220, 38, 38, 0.1)',
    paddingHorizontal: 7,
    paddingVertical: 2,
    borderRadius: 10,
  },
  countPillText: {
    color: '#dc2626',
    fontSize: 11,
    fontWeight: '700',
  },
  viewSwitch: {
    flexDirection: 'row',
    padding: 3,
    borderRadius: 8,
    gap: 2,
  },
  viewSwitchBtn: {
    paddingHorizontal: 8,
    paddingVertical: 5,
    borderRadius: 6,
  },
  viewSwitchActive: {
    backgroundColor: '#dc2626',
  },
  controlsBar: {
    paddingHorizontal: Spacing.four,
    paddingTop: 10,
    paddingBottom: 8,
    borderBottomWidth: 1,
  },
  searchRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 8,
  },
  searchInputWrapper: {
    flex: 1,
    flexDirection: 'row',
    alignItems: 'center',
    paddingHorizontal: 10,
    height: 38,
    borderRadius: 10,
  },
  searchInput: {
    flex: 1,
    fontSize: 13,
    padding: 0,
  },
  uploadBtn: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: '#dc2626',
    paddingHorizontal: 12,
    height: 38,
    borderRadius: 10,
  },
  uploadBtnText: {
    color: '#fff',
    fontSize: 12,
    fontWeight: '700',
  },
  cameraBtn: {
    width: 38,
    height: 38,
    borderRadius: 10,
    alignItems: 'center',
    justifyContent: 'center',
  },
  filterPillsRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 8,
    marginTop: 10,
  },
  filterChip: {
    paddingHorizontal: 12,
    paddingVertical: 5,
    borderRadius: 14,
  },
  filterChipActive: {
    backgroundColor: '#0f172a',
  },
  filterChipText: {
    fontSize: 11,
    fontWeight: '700',
  },
  uploadBanner: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    backgroundColor: '#fef2f2',
    paddingVertical: 8,
    paddingHorizontal: 14,
    borderBottomWidth: 1,
    borderBottomColor: '#fecaca',
  },
  uploadBannerText: {
    color: '#dc2626',
    fontSize: 12,
    fontWeight: '700',
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
  emptyIconCircle: {
    width: 72,
    height: 72,
    borderRadius: 36,
    backgroundColor: 'rgba(220, 38, 38, 0.08)',
    alignItems: 'center',
    justifyContent: 'center',
    marginBottom: 6,
  },
  emptyHeading: {
    fontSize: 16,
    fontWeight: '800',
    textAlign: 'center',
  },
  emptySub: {
    fontSize: 13,
    textAlign: 'center',
    maxWidth: 270,
    lineHeight: 18,
    marginBottom: 10,
  },
  emptyActionRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 10,
    marginTop: 6,
  },
  emptyUploadBtn: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: '#dc2626',
    paddingHorizontal: 14,
    paddingVertical: 10,
    borderRadius: 10,
  },
  emptyCameraBtn: {
    flexDirection: 'row',
    alignItems: 'center',
    paddingHorizontal: 14,
    paddingVertical: 10,
    borderRadius: 10,
  },
  emptyCameraBtnText: {
    fontSize: 12,
    fontWeight: '700',
  },
  scrollList: {
    padding: 12,
    paddingBottom: 90,
  },
  gridContainer: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: 12,
  },
  gridCard: {
    width: GRID_CARD_WIDTH,
    borderRadius: 14,
    borderWidth: 1,
    overflow: 'hidden',
  },
  gridImageWrapper: {
    width: '100%',
    height: GRID_CARD_WIDTH * 0.9,
    position: 'relative',
    backgroundColor: 'rgba(0,0,0,0.05)',
  },
  gridImage: {
    width: '100%',
    height: '100%',
  },
  extBadge: {
    position: 'absolute',
    top: 6,
    left: 6,
    backgroundColor: 'rgba(15, 23, 42, 0.75)',
    paddingHorizontal: 6,
    paddingVertical: 2,
    borderRadius: 4,
  },
  extBadgeText: {
    color: '#fff',
    fontSize: 9,
    fontWeight: '800',
    letterSpacing: 0.5,
  },
  sizeBadge: {
    position: 'absolute',
    top: 6,
    right: 6,
    backgroundColor: 'rgba(0, 0, 0, 0.55)',
    paddingHorizontal: 5,
    paddingVertical: 2,
    borderRadius: 4,
  },
  sizeBadgeText: {
    color: '#fff',
    fontSize: 9,
    fontWeight: '600',
  },
  gridCardBody: {
    padding: 8,
    gap: 6,
  },
  gridTitle: {
    fontSize: 11,
    fontWeight: '700',
    lineHeight: 14,
  },
  gridActionRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 6,
  },
  cardActionBtn: {
    flex: 1,
    height: 28,
    borderRadius: 6,
    alignItems: 'center',
    justifyContent: 'center',
  },
  deleteActionBtn: {
    backgroundColor: '#fee2e2',
  },
  listContainer: {
    gap: 10,
  },
  listCard: {
    flexDirection: 'row',
    alignItems: 'center',
    padding: 10,
    borderRadius: 14,
    borderWidth: 1,
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 1 },
    shadowOpacity: 0.04,
    shadowRadius: 3,
    elevation: 1,
  },
  listThumbWrapper: {
    width: 68,
    height: 68,
    borderRadius: 10,
    overflow: 'hidden',
    position: 'relative',
    backgroundColor: 'rgba(0,0,0,0.04)',
  },
  listThumb: {
    width: '100%',
    height: '100%',
  },
  listThumbBadge: {
    position: 'absolute',
    bottom: 4,
    left: 4,
    backgroundColor: 'rgba(15, 23, 42, 0.75)',
    paddingHorizontal: 4,
    paddingVertical: 1.5,
    borderRadius: 4,
  },
  listThumbBadgeText: {
    color: '#ffffff',
    fontSize: 8.5,
    fontWeight: '800',
    letterSpacing: 0.3,
  },
  listInfo: {
    flex: 1,
    marginLeft: 12,
    marginRight: 8,
    justifyContent: 'center',
    gap: 3,
  },
  listTitle: {
    fontSize: 13.5,
    fontWeight: '700',
    letterSpacing: -0.2,
  },
  listSubFilename: {
    fontSize: 11,
    fontWeight: '500',
  },
  listMetaRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 6,
    marginTop: 2,
  },
  metaPill: {
    paddingHorizontal: 6,
    paddingVertical: 2,
    borderRadius: 6,
  },
  metaPillText: {
    fontSize: 10.5,
    fontWeight: '600',
  },
  listActions: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 6,
  },
  listActionBtn: {
    width: 32,
    height: 32,
    borderRadius: 8,
    alignItems: 'center',
    justifyContent: 'center',
  },
  paginationRow: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    marginTop: 20,
    paddingHorizontal: 8,
  },
  pageBtn: {
    flexDirection: 'row',
    alignItems: 'center',
    paddingHorizontal: 12,
    paddingVertical: 8,
    borderRadius: 8,
    gap: 4,
  },
  pageBtnText: {
    fontSize: 12,
    fontWeight: '700',
  },
  pageInfo: {
    fontSize: 12,
    fontWeight: '500',
  },
  btnDisabled: {
    opacity: 0.5,
  },
  previewBackdrop: {
    flex: 1,
    backgroundColor: 'rgba(0,0,0,0.7)',
    justifyContent: 'center',
    alignItems: 'center',
    padding: 16,
  },
  previewModalBox: {
    width: '100%',
    maxHeight: '90%',
    borderRadius: 18,
    overflow: 'hidden',
    padding: 16,
  },
  previewModalHeader: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    marginBottom: 12,
  },
  previewModalTitle: {
    fontSize: 15,
    fontWeight: '800',
    flex: 1,
    marginRight: 10,
  },
  previewCloseBtn: {
    width: 32,
    height: 32,
    borderRadius: 16,
    alignItems: 'center',
    justifyContent: 'center',
  },
  previewImageBox: {
    width: '100%',
    height: 250,
    backgroundColor: '#0f172a',
    borderRadius: 12,
    overflow: 'hidden',
    marginBottom: 14,
    alignItems: 'center',
    justifyContent: 'center',
  },
  previewFullImage: {
    width: '100%',
    height: '100%',
  },
  previewDetailsBox: {
    gap: 10,
  },
  previewInfoRow: {
    flexDirection: 'row',
    borderRadius: 10,
    padding: 10,
    justifyContent: 'space-between',
  },
  previewInfoCol: {
    flex: 1,
  },
  previewInfoLabel: {
    fontSize: 10,
    fontWeight: '700',
    marginBottom: 2,
    textTransform: 'uppercase',
  },
  previewInfoVal: {
    fontSize: 12,
    fontWeight: '700',
  },
  urlBox: {
    borderRadius: 8,
    paddingHorizontal: 10,
    paddingVertical: 8,
  },
  urlText: {
    fontSize: 11,
    fontFamily: 'monospace',
  },
  previewActionGrid: {
    flexDirection: 'row',
    gap: 8,
    marginTop: 4,
  },
  previewActionBtn: {
    flex: 1,
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    paddingVertical: 10,
    borderRadius: 8,
  },
  copyUrlBtn: {
    backgroundColor: '#16a34a',
  },
  copyUrlBtnText: {
    color: '#fff',
    fontSize: 12,
    fontWeight: '700',
  },
  previewBtnText: {
    fontSize: 12,
    fontWeight: '700',
  },
  previewDeleteBtn: {
    backgroundColor: '#fee2e2',
  },
  previewDeleteBtnText: {
    color: '#dc2626',
    fontSize: 12,
    fontWeight: '700',
  },
  fabUpload: {
    position: 'absolute',
    bottom: 30,
    right: 20,
    width: 56,
    height: 56,
    borderRadius: 28,
    backgroundColor: '#dc2626',
    alignItems: 'center',
    justifyContent: 'center',
    shadowColor: '#dc2626',
    shadowOffset: { width: 0, height: 6 },
    shadowOpacity: 0.4,
    shadowRadius: 10,
    elevation: 8,
  },
  toast: {
    position: 'absolute',
    bottom: 96,
    alignSelf: 'center',
    backgroundColor: '#0f172a',
    flexDirection: 'row',
    alignItems: 'center',
    paddingHorizontal: 16,
    paddingVertical: 10,
    borderRadius: 20,
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 4 },
    shadowOpacity: 0.25,
    shadowRadius: 6,
    elevation: 6,
  },
  toastText: {
    color: '#fff',
    fontSize: 12,
    fontWeight: '700',
  },
});
