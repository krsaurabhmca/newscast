import React, { useState, useEffect } from 'react';
import {
  Modal,
  View,
  Text,
  TextInput,
  TouchableOpacity,
  ScrollView,
  StyleSheet,
  ActivityIndicator,
  Image,
  Alert,
  Linking,
  Platform,
} from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import {
  X,
  Check,
  Save,
  Send,
  Star,
  FileEdit,
  CheckCircle2,
  AlertCircle,
  ImageIcon,
  Video,
  Maximize2,
  ExternalLink,
} from '@/components/ui/icons';
import { PostItem, CategoryItem, savePost, getPostDetail } from '@/utils/api';
import { AdminMediaPickerModal } from '@/components/admin/AdminMediaPickerModal';
import { AdminArticleEditorModal } from '@/components/admin/AdminArticleEditorModal';
import { Spacing } from '@/constants/theme';
import { useTheme } from '@/hooks/use-theme';

interface AdminPostModalProps {
  visible: boolean;
  post: Partial<PostItem> | null;
  categories: CategoryItem[];
  portalUrl: string;
  onClose: () => void;
  onSuccess: () => void;
}

export function resolveImageUrl(img?: string, portalUrl?: string): string {
  if (!img) return '';
  const trimmed = img.trim();
  if (!trimmed) return '';

  const cleanPortal = (portalUrl || '').replace(/\/+$/, '');

  // If already absolute http or https
  if (/^https?:\/\//i.test(trimmed)) {
    if (cleanPortal && Platform.OS === 'android') {
      try {
        const portalHost = new URL(cleanPortal).hostname;
        const imgUrl = new URL(trimmed);
        if ((imgUrl.hostname === 'localhost' || imgUrl.hostname === '127.0.0.1') && portalHost && portalHost !== imgUrl.hostname) {
          imgUrl.hostname = portalHost;
          return imgUrl.toString();
        }
      } catch (e) {}
    }
    return trimmed;
  }

  // Relative path starting with slash
  if (trimmed.startsWith('/')) {
    return `${cleanPortal}${trimmed}`;
  }

  // Path starting with assets/
  if (trimmed.startsWith('assets/')) {
    return `${cleanPortal}/${trimmed}`;
  }

  // Media library image
  if (trimmed.startsWith('media/') || trimmed.startsWith('media_')) {
    const filename = trimmed.replace(/^media\//, '');
    return `${cleanPortal}/assets/images/media/${filename}`;
  }

  // Default post image
  return `${cleanPortal}/assets/images/posts/${trimmed}`;
}

export function AdminPostModal({
  visible,
  post,
  categories,
  portalUrl,
  onClose,
  onSuccess,
}: AdminPostModalProps) {
  const theme = useTheme();

  const isEditing = !!(post && post.id);

  const [title, setTitle] = useState('');
  const [slug, setSlug] = useState('');
  const [categoryId, setCategoryId] = useState<number | undefined>(undefined);
  const [status, setStatus] = useState<'published' | 'draft'>('published');
  const [isFeatured, setIsFeatured] = useState(false);
  const [featuredImage, setFeaturedImage] = useState('');
  const [videoUrl, setVideoUrl] = useState('');
  const [excerpt, setExcerpt] = useState('');
  const [content, setContent] = useState('');
  const [saving, setSaving] = useState(false);
  const [loadingDetails, setLoadingDetails] = useState(false);
  const [errorMsg, setErrorMsg] = useState('');

  // Media Picker & Rich Formatting Editor State
  const [mediaPickerVisible, setMediaPickerVisible] = useState(false);
  const [editorModalVisible, setEditorModalVisible] = useState(false);

  useEffect(() => {
    if (visible && post) {
      setTitle(post.title || '');
      setSlug(post.slug || '');
      setCategoryId(post.category_id || (categories.length > 0 ? categories[0].id : undefined));
      setStatus(post.status || 'published');
      setIsFeatured(!!post.is_featured);
      const initialImg = (post as any)?.featured_image_url || post.featured_image || (post as any)?.image || '';
      setFeaturedImage(initialImg);
      setVideoUrl(post.video_url || '');
      setExcerpt(post.excerpt || '');
      setContent(post.content || '');
      setErrorMsg('');

      // Fetch full details if editing existing article to ensure content is fully loaded
      if (post.id && portalUrl) {
        setLoadingDetails(true);
        getPostDetail(portalUrl, post.id)
          .then((fullPost) => {
            if (fullPost) {
              if (fullPost.content !== undefined && fullPost.content !== null) {
                setContent(fullPost.content);
              }
              if (fullPost.video_url !== undefined && fullPost.video_url !== null) {
                setVideoUrl(fullPost.video_url);
              }
              if (fullPost.excerpt !== undefined && fullPost.excerpt !== null) {
                setExcerpt(fullPost.excerpt);
              }
              const updatedImg = (fullPost as any)?.featured_image_url || fullPost.featured_image || (fullPost as any)?.image;
              if (updatedImg) {
                setFeaturedImage(updatedImg);
              }
              if (fullPost.category_id) {
                setCategoryId(fullPost.category_id);
              }
            }
          })
          .catch((err) => {
            console.warn('Failed to fetch full article details', err);
          })
          .finally(() => {
            setLoadingDetails(false);
          });
      }
    } else if (visible) {
      setTitle('');
      setSlug('');
      setCategoryId(categories.length > 0 ? categories[0].id : undefined);
      setStatus('published');
      setIsFeatured(false);
      setFeaturedImage('');
      setVideoUrl('');
      setExcerpt('');
      setContent('');
      setErrorMsg('');
      setLoadingDetails(false);
    }
  }, [visible, post, categories, portalUrl]);

  const handleSave = async (forceStatus?: 'published' | 'draft') => {
    if (!title.trim()) {
      setErrorMsg('Article headline is required');
      return;
    }

    const targetStatus = forceStatus || status;

    setSaving(true);
    setErrorMsg('');

    try {
      await savePost(portalUrl, {
        id: post?.id || 0,
        title: title.trim(),
        slug: slug.trim(),
        category_id: categoryId,
        category_ids: categoryId ? [categoryId] : [],
        status: targetStatus,
        is_featured: isFeatured ? 1 : 0,
        featured_image: featuredImage.trim(),
        video_url: videoUrl.trim(),
        excerpt: excerpt.trim(),
        content: content.trim() || title.trim(),
      });

      setSaving(false);
      onSuccess();
    } catch (err: any) {
      setSaving(false);
      setErrorMsg(err.message || 'Failed to save post');
    }
  };

  return (
    <Modal visible={visible} animationType="slide" transparent={false} onRequestClose={onClose}>
      <SafeAreaView style={[styles.container, { backgroundColor: theme.background }]} edges={['top', 'left', 'right']}>
        {/* Header */}
        <View
          style={[
            styles.modalHeader,
            { backgroundColor: theme.backgroundElement, borderBottomColor: 'rgba(0,0,0,0.06)' },
          ]}>
          <TouchableOpacity onPress={onClose} style={styles.closeBtn}>
            <X size={20} color={theme.text} />
          </TouchableOpacity>
          <Text style={[styles.modalTitle, { color: theme.text }]}>
            {isEditing ? 'Edit Article' : 'New Article'}
          </Text>

          <View style={styles.headerRightGroup}>
            {isEditing && post?.slug ? (
              <TouchableOpacity
                style={styles.viewLiveHeaderBtn}
                onPress={() => {
                  const url = `${portalUrl}/article.php?slug=${encodeURIComponent(post.slug!)}`;
                  Linking.openURL(url).catch(() => {
                    Linking.openURL(`${portalUrl}/article/${post.slug}`);
                  });
                }}>
                <ExternalLink size={13} color="#2563eb" style={{ marginRight: 4 }} />
                <Text style={styles.viewLiveHeaderBtnText}>View</Text>
              </TouchableOpacity>
            ) : null}

            <TouchableOpacity
              onPress={() => handleSave()}
              style={[styles.saveHeaderBtn, saving && styles.btnDisabled]}
              disabled={saving}>
              {saving ? (
                <ActivityIndicator size="small" color="#fff" />
              ) : (
                <View style={styles.btnRow}>
                  <Check size={14} color="#fff" strokeWidth={2.5} style={{ marginRight: 4 }} />
                  <Text style={styles.saveHeaderBtnText}>Save</Text>
                </View>
              )}
            </TouchableOpacity>
          </View>
        </View>

        <ScrollView contentContainerStyle={styles.formContent} keyboardShouldPersistTaps="handled">
          {errorMsg ? (
            <View style={styles.errorBox}>
              <AlertCircle size={15} color="#dc2626" style={{ marginRight: 6 }} />
              <Text style={styles.errorText}>{errorMsg}</Text>
            </View>
          ) : null}

          {/* Title */}
          <Text style={[styles.label, { color: theme.text }]}>Article Headline *</Text>
          <TextInput
            style={[
              styles.input,
              styles.titleInput,
              { backgroundColor: theme.backgroundElement, color: theme.text },
            ]}
            placeholder="Type headline here..."
            placeholderTextColor={theme.textSecondary}
            value={title}
            onChangeText={setTitle}
            multiline
          />

          {/* Category Selector */}
          <Text style={[styles.label, { color: theme.text }]}>Category</Text>
          <ScrollView horizontal showsHorizontalScrollIndicator={false} style={styles.catScroll}>
            {categories.map((cat) => {
              const selected = categoryId === cat.id;
              return (
                <TouchableOpacity
                  key={cat.id}
                  style={[
                    styles.catChip,
                    {
                      backgroundColor: selected ? cat.color || '#2563eb' : theme.backgroundElement,
                      borderColor: cat.color || '#2563eb',
                    },
                  ]}
                  onPress={() => setCategoryId(cat.id)}>
                  <Text
                    style={[
                      styles.catChipText,
                      { color: selected ? '#ffffff' : theme.text },
                    ]}>
                    {cat.name}
                  </Text>
                </TouchableOpacity>
              );
            })}
          </ScrollView>

          {/* Status & Featured Toggles */}
          <View style={styles.optionsRow}>
            {/* Status toggle */}
            <View style={styles.optionBox}>
              <Text style={[styles.label, { color: theme.text }]}>Status</Text>
              <View style={styles.statusButtonGroup}>
                <TouchableOpacity
                  style={[
                    styles.statusBtn,
                    status === 'published' && styles.statusBtnPublished,
                  ]}
                  onPress={() => setStatus('published')}>
                  <CheckCircle2
                    size={13}
                    color={status === 'published' ? '#fff' : theme.textSecondary}
                    style={{ marginRight: 4 }}
                  />
                  <Text
                    style={[
                      styles.statusBtnText,
                      status === 'published' ? styles.statusTextActive : { color: theme.textSecondary },
                    ]}>
                    Published
                  </Text>
                </TouchableOpacity>
                <TouchableOpacity
                  style={[
                    styles.statusBtn,
                    status === 'draft' && styles.statusBtnDraft,
                  ]}
                  onPress={() => setStatus('draft')}>
                  <FileEdit
                    size={13}
                    color={status === 'draft' ? '#fff' : theme.textSecondary}
                    style={{ marginRight: 4 }}
                  />
                  <Text
                    style={[
                      styles.statusBtnText,
                      status === 'draft' ? styles.statusTextActive : { color: theme.textSecondary },
                    ]}>
                    Draft
                  </Text>
                </TouchableOpacity>
              </View>
            </View>

            {/* Featured toggle */}
            <View style={styles.optionBox}>
              <Text style={[styles.label, { color: theme.text }]}>Spotlight</Text>
              <TouchableOpacity
                style={[
                  styles.toggleChip,
                  isFeatured
                    ? { backgroundColor: '#fef3c7', borderColor: '#f59e0b' }
                    : { backgroundColor: theme.backgroundElement, borderColor: 'transparent' },
                ]}
                onPress={() => setIsFeatured(!isFeatured)}>
                <Star
                  size={14}
                  color={isFeatured ? '#b45309' : theme.textSecondary}
                  fill={isFeatured ? '#b45309' : 'transparent'}
                  style={{ marginRight: 4 }}
                />
                <Text
                  style={{
                    fontSize: 12,
                    fontWeight: '700',
                    color: isFeatured ? '#b45309' : theme.textSecondary,
                  }}>
                  {isFeatured ? 'Featured Story' : 'Standard'}
                </Text>
              </TouchableOpacity>
            </View>
          </View>

          {/* Featured Image - Only From Media Library */}
          <View style={styles.fieldHeaderRow}>
            <Text style={[styles.label, { color: theme.text, marginBottom: 0 }]}>
              Featured Image
            </Text>
            {featuredImage ? (
              <View style={styles.imageActionButtons}>
                <TouchableOpacity
                  style={[styles.smallActionBtn, { backgroundColor: '#eff6ff' }]}
                  onPress={() => setMediaPickerVisible(true)}>
                  <ImageIcon size={12} color="#2563eb" style={{ marginRight: 4 }} />
                  <Text style={[styles.smallActionBtnText, { color: '#2563eb' }]}>Change</Text>
                </TouchableOpacity>
                <TouchableOpacity
                  style={[styles.smallActionBtn, { backgroundColor: '#fee2e2' }]}
                  onPress={() => setFeaturedImage('')}>
                  <X size={12} color="#dc2626" style={{ marginRight: 4 }} />
                  <Text style={[styles.smallActionBtnText, { color: '#dc2626' }]}>Remove</Text>
                </TouchableOpacity>
              </View>
            ) : null}
          </View>

          {featuredImage ? (
            <TouchableOpacity
              style={styles.imagePreviewContainer}
              onPress={() => setMediaPickerVisible(true)}
              activeOpacity={0.85}>
              <Image
                source={{
                  uri: resolveImageUrl(featuredImage, portalUrl),
                }}
                style={styles.imagePreview}
                resizeMode="cover"
              />
              <View style={styles.changeOverlay}>
                <ImageIcon size={13} color="#fff" style={{ marginRight: 5 }} />
                <Text style={styles.changeOverlayText}>Tap to Change Media</Text>
              </View>
            </TouchableOpacity>
          ) : (
            <TouchableOpacity
              style={[
                styles.selectMediaBox,
                { backgroundColor: theme.backgroundElement, borderColor: 'rgba(0,0,0,0.1)' },
              ]}
              onPress={() => setMediaPickerVisible(true)}
              activeOpacity={0.8}>
              <View style={styles.selectMediaIconCircle}>
                <ImageIcon size={22} color="#2563eb" strokeWidth={2.2} />
              </View>
              <Text style={[styles.selectMediaTitle, { color: theme.text }]}>
                Choose from Media Library
              </Text>
              <Text style={[styles.selectMediaDesc, { color: theme.textSecondary }]}>
                Select an existing uploaded photo as featured image
              </Text>
            </TouchableOpacity>
          )}

          {/* Article Body (Tap to open Formatting Screen mode) */}
          <View style={styles.fieldHeaderRow}>
            <Text style={[styles.label, { color: theme.text, marginBottom: 0 }]}>Article Body *</Text>
            {loadingDetails ? (
              <ActivityIndicator size="small" color="#dc2626" />
            ) : null}
          </View>

          <TouchableOpacity
            style={[
              styles.input,
              styles.contentInputBox,
              { backgroundColor: theme.backgroundElement },
            ]}
            onPress={() => setEditorModalVisible(true)}
            activeOpacity={0.8}>
            {content ? (
              <Text
                style={[styles.contentPreviewText, { color: theme.text }]}
                numberOfLines={6}>
                {content.replace(/<[^>]*>/g, ' ').replace(/\s+/g, ' ').trim()}
              </Text>
            ) : (
              <Text style={[styles.contentPlaceholderText, { color: theme.textSecondary }]}>
                {loadingDetails
                  ? 'Loading article content...'
                  : 'Tap here to write or edit article in Rich Text (RTF) Editor...'}
              </Text>
            )}
            <View style={styles.tapToEditHintRow}>
              <Maximize2 size={11} color="#2563eb" style={{ marginRight: 4 }} />
              <Text style={styles.tapToEditHintText}>Tap to open Rich Text (RTF) Editor with all toolbars</Text>
            </View>
          </TouchableOpacity>

          {/* Summary / Excerpt (Below Article Body) */}
          <Text style={[styles.label, { color: theme.text, marginTop: 12 }]}>Summary / Excerpt (Optional)</Text>
          <TextInput
            style={[
              styles.input,
              styles.excerptInput,
              { backgroundColor: theme.backgroundElement, color: theme.text },
            ]}
            placeholder="Brief snippet shown on news feed cards..."
            placeholderTextColor={theme.textSecondary}
            value={excerpt}
            onChangeText={setExcerpt}
            multiline
          />

          {/* Video URL (Below Article Body) */}
          <Text style={[styles.label, { color: theme.text, marginTop: 12 }]}>Video URL (Optional)</Text>
          <View style={[styles.inputWithIcon, { backgroundColor: theme.backgroundElement }]}>
            <Video size={16} color={theme.textSecondary} style={{ marginRight: 8 }} />
            <TextInput
              style={[styles.inputInner, { color: theme.text }]}
              placeholder="https://youtube.com/watch?v=..."
              placeholderTextColor={theme.textSecondary}
              value={videoUrl}
              onChangeText={setVideoUrl}
              autoCapitalize="none"
            />
          </View>

          {/* Action Buttons */}
          <View style={styles.bottomActions}>
            <TouchableOpacity
              style={[styles.fullActionBtn, styles.publishActionBtn, saving && styles.btnDisabled]}
              onPress={() => handleSave('published')}
              disabled={saving}>
              <Send size={15} color="#fff" style={{ marginRight: 6 }} />
              <Text style={styles.fullActionBtnText}>Publish Now</Text>
            </TouchableOpacity>

            <TouchableOpacity
              style={[styles.fullActionBtn, styles.draftActionBtn, saving && styles.btnDisabled]}
              onPress={() => handleSave('draft')}
              disabled={saving}>
              <Save size={15} color="#334155" style={{ marginRight: 6 }} />
              <Text style={styles.draftActionBtnText}>Save as Draft</Text>
            </TouchableOpacity>
          </View>
        </ScrollView>

        {/* Choose Media Modal */}
        <AdminMediaPickerModal
          visible={mediaPickerVisible}
          portalUrl={portalUrl}
          title="Choose Image"
          onClose={() => setMediaPickerVisible(false)}
          onSelectImage={(media) => {
            setFeaturedImage(media.file_url);
          }}
        />

        {/* Rich Formatting Editor Screen */}
        <AdminArticleEditorModal
          visible={editorModalVisible}
          initialContent={content}
          portalUrl={portalUrl}
          onClose={() => setEditorModalVisible(false)}
          onApply={(formatted) => setContent(formatted)}
        />
      </SafeAreaView>
    </Modal>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
  },
  modalHeader: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    paddingHorizontal: Spacing.four,
    paddingVertical: 12,
    borderBottomWidth: 1,
  },
  closeBtn: {
    padding: 6,
  },
  modalTitle: {
    fontSize: 15,
    fontWeight: '800',
  },
  saveHeaderBtn: {
    backgroundColor: '#dc2626',
    paddingHorizontal: 14,
    paddingVertical: 7,
    borderRadius: 8,
  },
  btnRow: {
    flexDirection: 'row',
    alignItems: 'center',
  },
  saveHeaderBtnText: {
    color: '#fff',
    fontWeight: '700',
    fontSize: 12,
  },
  formContent: {
    padding: Spacing.four,
    maxWidth: 640,
    width: '100%',
    alignSelf: 'center',
    paddingBottom: 80,
  },
  errorBox: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: '#fef2f2',
    padding: 10,
    borderRadius: 8,
    marginBottom: 12,
  },
  errorText: {
    color: '#dc2626',
    fontSize: 12,
    fontWeight: '600',
    flex: 1,
  },
  label: {
    fontSize: 12,
    fontWeight: '700',
    marginBottom: 6,
    marginTop: 12,
  },
  input: {
    borderRadius: 10,
    paddingHorizontal: 12,
    paddingVertical: 10,
    fontSize: 13,
  },
  inputWithIcon: {
    flexDirection: 'row',
    alignItems: 'center',
    borderRadius: 10,
    paddingHorizontal: 12,
    height: 44,
  },
  inputInner: {
    flex: 1,
    fontSize: 13,
    height: '100%',
  },
  titleInput: {
    fontSize: 16,
    fontWeight: '700',
    minHeight: 48,
  },
  catScroll: {
    flexDirection: 'row',
    marginBottom: 4,
  },
  catChip: {
    paddingHorizontal: 12,
    paddingVertical: 6,
    borderRadius: 16,
    marginRight: 6,
    borderWidth: 1,
  },
  catChipText: {
    fontSize: 11,
    fontWeight: '700',
  },
  optionsRow: {
    flexDirection: 'row',
    gap: 12,
    marginTop: 4,
  },
  optionBox: {
    flex: 1,
  },
  statusButtonGroup: {
    flexDirection: 'row',
    backgroundColor: 'rgba(0,0,0,0.05)',
    borderRadius: 8,
    padding: 2,
  },
  statusBtn: {
    flex: 1,
    flexDirection: 'row',
    paddingVertical: 7,
    alignItems: 'center',
    justifyContent: 'center',
    borderRadius: 7,
  },
  statusBtnPublished: {
    backgroundColor: '#16a34a',
  },
  statusBtnDraft: {
    backgroundColor: '#d97706',
  },
  statusBtnText: {
    fontSize: 11,
    fontWeight: '700',
  },
  statusTextActive: {
    color: '#ffffff',
  },
  toggleChip: {
    flexDirection: 'row',
    paddingVertical: 8,
    paddingHorizontal: 12,
    borderRadius: 8,
    borderWidth: 1.5,
    alignItems: 'center',
    justifyContent: 'center',
  },
  imagePreviewContainer: {
    marginTop: 8,
    borderRadius: 10,
    overflow: 'hidden',
    height: 150,
    backgroundColor: '#0f172a',
  },
  imagePreview: {
    width: '100%',
    height: '100%',
  },
  excerptInput: {
    minHeight: 56,
  },
  contentInput: {
    minHeight: 180,
  },
  bottomActions: {
    gap: 10,
    marginTop: Spacing.five,
  },
  fullActionBtn: {
    borderRadius: 10,
    paddingVertical: 13,
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
  },
  publishActionBtn: {
    backgroundColor: '#dc2626',
  },
  fullActionBtnText: {
    color: '#ffffff',
    fontSize: 14,
    fontWeight: '700',
  },
  draftActionBtn: {
    backgroundColor: 'rgba(0,0,0,0.06)',
  },
  draftActionBtnText: {
    color: '#334155',
    fontSize: 13,
    fontWeight: '700',
  },
  btnDisabled: {
    opacity: 0.5,
  },
  headerRightGroup: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 8,
  },
  viewLiveHeaderBtn: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: '#eff6ff',
    paddingHorizontal: 10,
    paddingVertical: 6,
    borderRadius: 8,
  },
  viewLiveHeaderBtnText: {
    color: '#2563eb',
    fontSize: 12,
    fontWeight: '700',
  },
  fieldHeaderRow: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    marginBottom: 6,
    marginTop: 4,
  },
  imageActionButtons: {
    flexDirection: 'row',
    gap: 6,
  },
  smallActionBtn: {
    flexDirection: 'row',
    alignItems: 'center',
    paddingHorizontal: 8,
    paddingVertical: 4,
    borderRadius: 6,
  },
  smallActionBtnText: {
    fontSize: 11,
    fontWeight: '700',
  },
  richEditorHeaderBtn: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: '#dc2626',
    paddingHorizontal: 9,
    paddingVertical: 4,
    borderRadius: 6,
  },
  richEditorHeaderBtnText: {
    color: '#fff',
    fontSize: 11,
    fontWeight: '700',
  },
  selectMediaBox: {
    borderWidth: 1.5,
    borderStyle: 'dashed',
    borderRadius: 12,
    paddingVertical: 20,
    paddingHorizontal: 16,
    alignItems: 'center',
    justifyContent: 'center',
    marginBottom: Spacing.three,
  },
  selectMediaIconCircle: {
    width: 44,
    height: 44,
    borderRadius: 22,
    backgroundColor: '#eff6ff',
    alignItems: 'center',
    justifyContent: 'center',
    marginBottom: 8,
  },
  selectMediaTitle: {
    fontSize: 14,
    fontWeight: '700',
    marginBottom: 3,
  },
  selectMediaDesc: {
    fontSize: 12,
    fontWeight: '500',
  },
  changeOverlay: {
    position: 'absolute',
    bottom: 8,
    right: 8,
    backgroundColor: 'rgba(0,0,0,0.65)',
    borderRadius: 6,
    paddingHorizontal: 8,
    paddingVertical: 4,
    flexDirection: 'row',
    alignItems: 'center',
  },
  changeOverlayText: {
    color: '#ffffff',
    fontSize: 11,
    fontWeight: '700',
  },
  contentInputBox: {
    minHeight: 140,
    padding: 12,
    justifyContent: 'space-between',
  },
  contentPreviewText: {
    fontSize: 14,
    lineHeight: 22,
  },
  contentPlaceholderText: {
    fontSize: 14,
    fontStyle: 'italic',
    lineHeight: 22,
  },
  tapToEditHintRow: {
    flexDirection: 'row',
    alignItems: 'center',
    marginTop: 10,
    paddingTop: 8,
    borderTopWidth: 1,
    borderTopColor: 'rgba(0,0,0,0.06)',
  },
  tapToEditHintText: {
    fontSize: 11,
    fontWeight: '700',
    color: '#2563eb',
  },
});
