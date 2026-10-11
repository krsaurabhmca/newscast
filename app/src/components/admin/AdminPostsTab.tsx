import React, { useState, useEffect } from 'react';
import {
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
} from 'react-native';
import {
  Search,
  Plus,
  Pencil,
  Trash2,
  Eye,
  Star,
  ChevronLeft,
  ChevronRight,
  Inbox,
  ExternalLink,
} from '@/components/ui/icons';
import {
  PostItem,
  CategoryItem,
  getPosts,
  deletePost,
  togglePostStatus,
} from '@/utils/api';
import { Spacing } from '@/constants/theme';
import { useTheme } from '@/hooks/use-theme';

interface AdminPostsTabProps {
  portalUrl: string;
  categories: CategoryItem[];
  onOpenNewPost?: () => void;
  onEditPost: (post: PostItem) => void;
}

export function AdminPostsTab({
  portalUrl,
  categories,
  onOpenNewPost,
  onEditPost,
}: AdminPostsTabProps) {
  const theme = useTheme();

  const [posts, setPosts] = useState<PostItem[]>([]);
  const [loading, setLoading] = useState(true);
  const [search, setSearch] = useState('');
  const [statusFilter, setStatusFilter] = useState<'all' | 'published' | 'draft'>('all');
  const [categoryFilter, setCategoryFilter] = useState<number>(0);
  const [page, setPage] = useState(1);
  const [totalPages, setTotalPages] = useState(1);
  const [totalCount, setTotalCount] = useState(0);
  const [actionInProgress, setActionInProgress] = useState<number | null>(null);

  const fetchPostList = async (pageNum = 1) => {
    setLoading(true);
    try {
      const res = await getPosts(portalUrl, {
        page: pageNum,
        limit: 15,
        search: search.trim(),
        status: statusFilter,
        category_id: categoryFilter,
      });
      setPosts(res.posts);
      setPage(res.pagination.page);
      setTotalPages(res.pagination.total_pages);
      setTotalCount(res.pagination.total);
    } catch (err: any) {
      Alert.alert('Error', err.message || 'Failed to fetch articles');
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchPostList(1);
  }, [statusFilter, categoryFilter]);

  const handleSearchSubmit = () => {
    fetchPostList(1);
  };

  const handleToggleStatus = async (post: PostItem) => {
    setActionInProgress(post.id);
    try {
      const newStatus = await togglePostStatus(portalUrl, post.id);
      setPosts((prev) =>
        prev.map((p) => (p.id === post.id ? { ...p, status: newStatus as any } : p))
      );
    } catch (err: any) {
      Alert.alert('Error', err.message || 'Failed to toggle status');
    } finally {
      setActionInProgress(null);
    }
  };

  const handleDelete = (post: PostItem) => {
    Alert.alert(
      'Delete Article',
      `Are you sure you want to delete "${post.title}"?`,
      [
        { text: 'Cancel', style: 'cancel' },
        {
          text: 'Delete',
          style: 'destructive',
          onPress: async () => {
            setActionInProgress(post.id);
            try {
              await deletePost(portalUrl, post.id);
              setPosts((prev) => prev.filter((p) => p.id !== post.id));
              setTotalCount((c) => Math.max(0, c - 1));
            } catch (err: any) {
              Alert.alert('Error', err.message || 'Failed to delete article');
            } finally {
              setActionInProgress(null);
            }
          },
        },
      ]
    );
  };

  const handleViewArticle = (post: PostItem) => {
    const url = `${portalUrl}/article.php?slug=${encodeURIComponent(post.slug)}`;
    Linking.openURL(url).catch(() => {
      Linking.openURL(`${portalUrl}/article/${post.slug}`).catch((err) => {
        Alert.alert('Error', err.message || 'Cannot open article link');
      });
    });
  };

  return (
    <View style={styles.wrapper}>
      {/* Search & Filters */}
      <View
        style={[
          styles.filterBar,
          { backgroundColor: theme.backgroundElement, borderBottomColor: 'rgba(0,0,0,0.06)' },
        ]}>
        <View style={styles.searchRow}>
          <View
            style={[
              styles.searchInputWrapper,
              { backgroundColor: theme.backgroundSelected },
            ]}>
            <Search size={15} color={theme.textSecondary} style={{ marginRight: 8 }} />
            <TextInput
              style={[styles.searchInput, { color: theme.text }]}
              placeholder="Search by title or keyword..."
              placeholderTextColor={theme.textSecondary}
              value={search}
              onChangeText={setSearch}
              onSubmitEditing={handleSearchSubmit}
              returnKeyType="search"
            />
          </View>
          <TouchableOpacity style={styles.searchBtn} onPress={handleSearchSubmit}>
            <Text style={styles.searchBtnText}>Search</Text>
          </TouchableOpacity>
        </View>

        {/* Filter Pills */}
        <View style={styles.statusFilters}>
          {(['all', 'published', 'draft'] as const).map((st) => {
            const active = statusFilter === st;
            return (
              <TouchableOpacity
                key={st}
                style={[
                  styles.filterPill,
                  active ? styles.filterPillActive : { backgroundColor: theme.backgroundSelected },
                ]}
                onPress={() => setStatusFilter(st)}>
                <Text
                  style={[
                    styles.filterPillText,
                    active ? styles.filterTextActive : { color: theme.textSecondary },
                  ]}>
                  {st.charAt(0).toUpperCase() + st.slice(1)}
                </Text>
              </TouchableOpacity>
            );
          })}
        </View>

        {/* Category Horizontal Filter */}
        <ScrollView horizontal showsHorizontalScrollIndicator={false} style={styles.catScroll}>
          <TouchableOpacity
            style={[
              styles.catChip,
              categoryFilter === 0
                ? styles.catChipActive
                : { backgroundColor: theme.backgroundSelected },
            ]}
            onPress={() => setCategoryFilter(0)}>
            <Text
              style={[
                styles.catChipText,
                categoryFilter === 0 ? styles.catChipTextActive : { color: theme.text },
              ]}>
              All Categories
            </Text>
          </TouchableOpacity>

          {categories.map((cat) => {
            const isSelected = categoryFilter === cat.id;
            return (
              <TouchableOpacity
                key={cat.id}
                style={[
                  styles.catChip,
                  isSelected
                    ? { backgroundColor: cat.color || '#2563eb' }
                    : { backgroundColor: theme.backgroundSelected },
                ]}
                onPress={() => setCategoryFilter(cat.id)}>
                <Text
                  style={[
                    styles.catChipText,
                    isSelected ? styles.catChipTextActive : { color: theme.text },
                  ]}>
                  {cat.name}
                </Text>
              </TouchableOpacity>
            );
          })}
        </ScrollView>
      </View>

      {/* Counter label */}
      <View style={styles.countRow}>
        <Text style={[styles.countText, { color: theme.textSecondary }]}>
          Showing {posts.length} of {totalCount} articles
        </Text>
      </View>

      {/* Posts List */}
      {loading ? (
        <View style={styles.centerLoading}>
          <ActivityIndicator size="large" color="#dc2626" />
          <Text style={[styles.loadingText, { color: theme.textSecondary }]}>
            Loading articles...
          </Text>
        </View>
      ) : posts.length === 0 ? (
        <View style={styles.emptyContainer}>
          <Inbox size={42} color={theme.textSecondary} strokeWidth={1.5} style={{ marginBottom: 8 }} />
          <Text style={[styles.emptyTitle, { color: theme.text }]}>No articles found</Text>
          <Text style={[styles.emptySubtitle, { color: theme.textSecondary }]}>
            Try clearing search filters or create a new article.
          </Text>
          <TouchableOpacity style={styles.createFirstBtn} onPress={onOpenNewPost}>
            <Plus size={15} color="#fff" style={{ marginRight: 6 }} />
            <Text style={styles.createFirstBtnText}>Create Article</Text>
          </TouchableOpacity>
        </View>
      ) : (
        <ScrollView contentContainerStyle={styles.postListContent}>
          {posts.map((post) => {
            const isDraft = post.status === 'draft';
            const inAction = actionInProgress === post.id;
            return (
              <View
                key={post.id}
                style={[
                  styles.postCard,
                  { backgroundColor: theme.backgroundElement, borderColor: 'rgba(0,0,0,0.06)' },
                ]}>
                {/* Top Section: Thumbnail & Article Details */}
                <View style={styles.postCardMain}>
                  <Image
                    source={{
                      uri:
                        post.featured_image ||
                        `${portalUrl}/assets/images/default-post.jpg`,
                    }}
                    style={styles.postImage}
                    resizeMode="cover"
                  />

                  <View style={styles.postInfo}>
                    <Text style={[styles.postTitle, { color: theme.text }]} numberOfLines={2}>
                      {post.title}
                    </Text>

                    {post.excerpt ? (
                      <Text
                        style={[styles.postExcerpt, { color: theme.textSecondary }]}
                        numberOfLines={2}>
                        {post.excerpt}
                      </Text>
                    ) : null}

                    {/* Metadata Tags */}
                    <View style={styles.metaRow}>
                      {post.category_name ? (
                        <View
                          style={[
                            styles.catBadge,
                            { backgroundColor: (post.category_color || '#3b82f6') + '18' },
                          ]}>
                          <Text
                            style={[
                              styles.catBadgeText,
                              { color: post.category_color || '#3b82f6' },
                            ]}>
                            {post.category_name}
                          </Text>
                        </View>
                      ) : null}

                      {post.is_featured ? (
                        <View style={styles.featuredBadge}>
                          <Star size={10} color="#b45309" fill="#b45309" style={{ marginRight: 3 }} />
                          <Text style={styles.featuredBadgeText}>Featured</Text>
                        </View>
                      ) : null}

                      <View style={styles.viewsBadgeRow}>
                        <Eye size={12} color={theme.textSecondary} style={{ marginRight: 3 }} />
                        <Text style={[styles.viewsBadge, { color: theme.textSecondary }]}>
                          {post.views}
                        </Text>
                      </View>
                    </View>
                  </View>
                </View>

                {/* Card Divider */}
                <View style={[styles.cardDivider, { backgroundColor: 'rgba(0,0,0,0.06)' }]} />

                {/* Dedicated Full-Width Action Bar (Delete button is never hidden) */}
                <View style={styles.postActionRow}>
                  {/* Left: Status Toggle Pill */}
                  <TouchableOpacity
                    style={[
                      styles.statusPill,
                      isDraft ? styles.pillDraft : styles.pillPublished,
                      inAction && styles.btnDisabled,
                    ]}
                    onPress={() => handleToggleStatus(post)}
                    disabled={inAction}>
                    <View style={styles.statusPillContent}>
                      <View
                        style={[
                          styles.statusDot,
                          { backgroundColor: isDraft ? '#f59e0b' : '#10b981' },
                        ]}
                      />
                      <Text
                        style={[
                          styles.statusPillText,
                          { color: isDraft ? '#b45309' : '#047857' },
                        ]}>
                        {isDraft ? 'Draft' : 'Published'}
                      </Text>
                    </View>
                  </TouchableOpacity>

                  {/* Right: Action Buttons Group */}
                  <View style={styles.actionBtnsGroup}>
                    <TouchableOpacity
                      style={[styles.actionBtn, { backgroundColor: '#eff6ff' }]}
                      onPress={() => handleViewArticle(post)}>
                      <ExternalLink size={12} color="#2563eb" style={{ marginRight: 4 }} />
                      <Text style={[styles.actionBtnText, { color: '#2563eb' }]}>View</Text>
                    </TouchableOpacity>

                    <TouchableOpacity
                      style={[styles.actionBtn, { backgroundColor: theme.backgroundSelected }]}
                      onPress={() => onEditPost(post)}>
                      <Pencil size={12} color={theme.text} style={{ marginRight: 4 }} />
                      <Text style={[styles.actionBtnText, { color: theme.text }]}>Edit</Text>
                    </TouchableOpacity>

                    <TouchableOpacity
                      style={[styles.actionBtn, styles.deleteBtn]}
                      onPress={() => handleDelete(post)}>
                      <Trash2 size={12} color="#dc2626" style={{ marginRight: 3 }} />
                      <Text style={[styles.actionBtnText, { color: '#dc2626' }]}>Delete</Text>
                    </TouchableOpacity>
                  </View>
                </View>
              </View>
            );
          })}

          {/* Pagination Navigation */}
          {totalPages > 1 && (
            <View style={styles.paginationRow}>
              <TouchableOpacity
                style={[
                  styles.pageNavBtn,
                  page <= 1 && styles.btnDisabled,
                  { backgroundColor: theme.backgroundElement },
                ]}
                onPress={() => fetchPostList(page - 1)}
                disabled={page <= 1}>
                <ChevronLeft size={14} color={theme.text} style={{ marginRight: 2 }} />
                <Text style={{ color: theme.text, fontWeight: '700', fontSize: 12 }}>Prev</Text>
              </TouchableOpacity>

              <Text style={[styles.pageInfoText, { color: theme.textSecondary }]}>
                Page {page} of {totalPages}
              </Text>

              <TouchableOpacity
                style={[
                  styles.pageNavBtn,
                  page >= totalPages && styles.btnDisabled,
                  { backgroundColor: theme.backgroundElement },
                ]}
                onPress={() => fetchPostList(page + 1)}
                disabled={page >= totalPages}>
                <Text style={{ color: theme.text, fontWeight: '700', fontSize: 12 }}>Next</Text>
                <ChevronRight size={14} color={theme.text} style={{ marginLeft: 2 }} />
              </TouchableOpacity>
            </View>
          )}
        </ScrollView>
      )}
    </View>
  );
}

const styles = StyleSheet.create({
  wrapper: {
    flex: 1,
  },
  filterBar: {
    padding: Spacing.three,
    borderBottomWidth: 1,
  },
  searchRow: {
    flexDirection: 'row',
    gap: 8,
    marginBottom: 8,
  },
  searchInputWrapper: {
    flex: 1,
    flexDirection: 'row',
    alignItems: 'center',
    borderRadius: 10,
    paddingHorizontal: 10,
    height: 40,
  },
  searchInput: {
    flex: 1,
    fontSize: 13,
    height: '100%',
  },
  searchBtn: {
    backgroundColor: '#0f172a',
    borderRadius: 10,
    paddingHorizontal: 14,
    justifyContent: 'center',
    alignItems: 'center',
  },
  searchBtnText: {
    color: '#fff',
    fontSize: 12,
    fontWeight: '700',
  },
  statusFilters: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 6,
    marginBottom: 8,
  },
  filterPill: {
    paddingHorizontal: 12,
    paddingVertical: 6,
    borderRadius: 8,
  },
  filterPillActive: {
    backgroundColor: '#dc2626',
  },
  filterPillText: {
    fontSize: 12,
    fontWeight: '700',
  },
  filterTextActive: {
    color: '#ffffff',
  },
  newPostBtn: {
    backgroundColor: '#dc2626',
    flexDirection: 'row',
    alignItems: 'center',
    paddingHorizontal: 12,
    paddingVertical: 6,
    borderRadius: 8,
    marginLeft: 'auto',
  },
  newPostBtnText: {
    color: '#fff',
    fontSize: 12,
    fontWeight: '700',
  },
  catScroll: {
    flexDirection: 'row',
  },
  catChip: {
    paddingHorizontal: 10,
    paddingVertical: 5,
    borderRadius: 14,
    marginRight: 6,
  },
  catChipActive: {
    backgroundColor: '#2563eb',
  },
  catChipText: {
    fontSize: 11,
    fontWeight: '700',
  },
  catChipTextActive: {
    color: '#fff',
  },
  countRow: {
    paddingHorizontal: Spacing.four,
    paddingVertical: 6,
  },
  countText: {
    fontSize: 11,
    fontWeight: '500',
  },
  centerLoading: {
    flex: 1,
    justifyContent: 'center',
    alignItems: 'center',
    padding: 40,
  },
  loadingText: {
    marginTop: 8,
    fontSize: 13,
  },
  emptyContainer: {
    flex: 1,
    justifyContent: 'center',
    alignItems: 'center',
    padding: 40,
  },
  emptyTitle: {
    fontSize: 16,
    fontWeight: '700',
  },
  emptySubtitle: {
    fontSize: 12,
    marginTop: 4,
    textAlign: 'center',
  },
  createFirstBtn: {
    marginTop: 16,
    backgroundColor: '#dc2626',
    flexDirection: 'row',
    alignItems: 'center',
    paddingHorizontal: 16,
    paddingVertical: 10,
    borderRadius: 10,
  },
  createFirstBtnText: {
    color: '#fff',
    fontWeight: '700',
    fontSize: 13,
  },
  postListContent: {
    padding: Spacing.three,
    gap: 10,
    paddingBottom: 90,
  },
  postCard: {
    borderRadius: 14,
    overflow: 'hidden',
    padding: 12,
    flexDirection: 'column',
    gap: 8,
    borderWidth: 1,
  },
  postCardMain: {
    flexDirection: 'row',
    gap: 12,
  },
  postImage: {
    width: 78,
    height: 78,
    borderRadius: 10,
    backgroundColor: '#0f172a',
  },
  postInfo: {
    flex: 1,
    justifyContent: 'space-between',
  },
  postTitle: {
    fontSize: 13,
    fontWeight: '700',
    lineHeight: 18,
  },
  postExcerpt: {
    fontSize: 11,
    marginTop: 2,
    lineHeight: 15,
  },
  metaRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 6,
    marginVertical: 4,
    flexWrap: 'wrap',
  },
  catBadge: {
    paddingHorizontal: 6,
    paddingVertical: 2,
    borderRadius: 4,
  },
  catBadgeText: {
    fontSize: 10,
    fontWeight: '700',
  },
  featuredBadge: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: '#fef3c7',
    paddingHorizontal: 6,
    paddingVertical: 2,
    borderRadius: 4,
  },
  featuredBadgeText: {
    fontSize: 10,
    fontWeight: '700',
    color: '#b45309',
  },
  viewsBadgeRow: {
    flexDirection: 'row',
    alignItems: 'center',
  },
  viewsBadge: {
    fontSize: 11,
    fontWeight: '500',
  },
  cardDivider: {
    height: 1,
  },
  postActionRow: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    paddingTop: 2,
  },
  statusPill: {
    paddingHorizontal: 9,
    paddingVertical: 5,
    borderRadius: 6,
  },
  statusPillContent: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 4,
  },
  statusDot: {
    width: 6,
    height: 6,
    borderRadius: 3,
  },
  pillPublished: {
    backgroundColor: '#dcfce7',
  },
  pillDraft: {
    backgroundColor: '#fef3c7',
  },
  statusPillText: {
    fontSize: 10,
    fontWeight: '700',
  },
  actionBtnsGroup: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 6,
  },
  actionBtn: {
    flexDirection: 'row',
    alignItems: 'center',
    paddingHorizontal: 9,
    paddingVertical: 5,
    borderRadius: 6,
  },
  actionBtnText: {
    fontSize: 11,
    fontWeight: '600',
  },
  deleteBtn: {
    backgroundColor: '#fee2e2',
  },
  btnDisabled: {
    opacity: 0.5,
  },
  paginationRow: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    marginTop: 12,
    paddingHorizontal: 8,
  },
  pageNavBtn: {
    flexDirection: 'row',
    alignItems: 'center',
    paddingHorizontal: 12,
    paddingVertical: 8,
    borderRadius: 8,
  },
  pageInfoText: {
    fontSize: 12,
    fontWeight: '500',
  },
});
