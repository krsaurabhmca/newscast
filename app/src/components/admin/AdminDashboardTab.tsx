import React, { useState } from 'react';
import {
  View,
  Text,
  StyleSheet,
  TouchableOpacity,
  ScrollView,
  Image,
  Switch,
  ActivityIndicator,
  Alert,
  Linking,
} from 'react-native';
import {
  Layers,
  Radio,
  Zap,
  CheckCircle2,
  FileEdit,
  Eye,
  Pencil,
  Clock,
  ArrowRight,
  TrendingUp,
  ExternalLink,
} from '@/components/ui/icons';
import {
  DashboardStats,
  PostItem,
  CategoryItem,
  toggleLiveStream,
  updateSettings,
  togglePostStatus,
} from '@/utils/api';
import { Spacing } from '@/constants/theme';
import { useTheme } from '@/hooks/use-theme';

interface AdminDashboardTabProps {
  stats: DashboardStats | null;
  liveStream: { enabled: boolean; url: string };
  breakingNewsEnabled: boolean;
  recentPosts: PostItem[];
  portalUrl: string;
  onEditPost: (post: PostItem) => void;
  onNavigateTab: (tab: 'posts' | 'categories' | 'settings' | 'polls' | 'media') => void;
  onRefresh: () => void;
}

export function AdminDashboardTab({
  stats,
  liveStream,
  breakingNewsEnabled,
  recentPosts,
  portalUrl,
  onEditPost,
  onNavigateTab,
  onRefresh,
}: AdminDashboardTabProps) {
  const theme = useTheme();

  const [liveEnabled, setLiveEnabled] = useState(liveStream.enabled);
  const [breakingEnabled, setBreakingEnabled] = useState(breakingNewsEnabled);
  const [updatingLive, setUpdatingLive] = useState(false);
  const [updatingBreaking, setUpdatingBreaking] = useState(false);
  const [togglingPostId, setTogglingPostId] = useState<number | null>(null);

  const handleToggleLive = async (value: boolean) => {
    setLiveEnabled(value);
    setUpdatingLive(true);
    try {
      await toggleLiveStream(portalUrl, value);
      onRefresh();
    } catch (err: any) {
      setLiveEnabled(!value);
      Alert.alert('Error', err.message || 'Failed to update live stream status');
    } finally {
      setUpdatingLive(false);
    }
  };

  const handleToggleBreaking = async (value: boolean) => {
    setBreakingEnabled(value);
    setUpdatingBreaking(true);
    try {
      await updateSettings(portalUrl, {
        breaking_news_enabled: value ? 'yes' : 'no',
      });
      onRefresh();
    } catch (err: any) {
      setBreakingEnabled(!value);
      Alert.alert('Error', err.message || 'Failed to update breaking news');
    } finally {
      setUpdatingBreaking(false);
    }
  };

  const handleToggleStatus = async (post: PostItem) => {
    setTogglingPostId(post.id);
    try {
      await togglePostStatus(portalUrl, post.id);
      onRefresh();
    } catch (err: any) {
      Alert.alert('Error', err.message || 'Failed to update status');
    } finally {
      setTogglingPostId(null);
    }
  };

  return (
    <ScrollView contentContainerStyle={styles.container} showsVerticalScrollIndicator={false}>
      {/* Top Section: Overview Header */}
      <View style={styles.sectionHeaderRow}>
        <View style={styles.sectionTitleRow}>
          <TrendingUp size={16} color={theme.text} strokeWidth={2.2} />
          <Text style={[styles.sectionHeading, { color: theme.text }]}>Portal Overview</Text>
        </View>
      </View>

      {/* Stats Grid (Published, Drafts, Views, Categories) */}
      <View style={styles.statsGrid}>
        {/* Published */}
        <View
          style={[
            styles.statCard,
            { backgroundColor: theme.backgroundElement, borderColor: 'rgba(0,0,0,0.06)' },
          ]}>
          <View style={[styles.statIconBox, { backgroundColor: '#dcfce7' }]}>
            <CheckCircle2 size={18} color="#16a34a" strokeWidth={2.2} />
          </View>
          <Text style={[styles.statNumber, { color: '#16a34a' }]}>
            {stats?.published_posts ?? 0}
          </Text>
          <Text style={[styles.statLabel, { color: theme.textSecondary }]}>Published</Text>
        </View>

        {/* Drafts */}
        <View
          style={[
            styles.statCard,
            { backgroundColor: theme.backgroundElement, borderColor: 'rgba(0,0,0,0.06)' },
          ]}>
          <View style={[styles.statIconBox, { backgroundColor: '#fef3c7' }]}>
            <FileEdit size={18} color="#d97706" strokeWidth={2.2} />
          </View>
          <Text style={[styles.statNumber, { color: '#d97706' }]}>
            {stats?.draft_posts ?? 0}
          </Text>
          <Text style={[styles.statLabel, { color: theme.textSecondary }]}>Drafts</Text>
        </View>

        {/* Total Views */}
        <View
          style={[
            styles.statCard,
            { backgroundColor: theme.backgroundElement, borderColor: 'rgba(0,0,0,0.06)' },
          ]}>
          <View style={[styles.statIconBox, { backgroundColor: '#e0e7ff' }]}>
            <Eye size={18} color="#4f46e5" strokeWidth={2.2} />
          </View>
          <Text style={[styles.statNumber, { color: '#4f46e5' }]}>
            {stats?.total_views ? stats.total_views.toLocaleString() : 0}
          </Text>
          <Text style={[styles.statLabel, { color: theme.textSecondary }]}>Total Views</Text>
        </View>

        {/* Categories */}
        <View
          style={[
            styles.statCard,
            { backgroundColor: theme.backgroundElement, borderColor: 'rgba(0,0,0,0.06)' },
          ]}>
          <View style={[styles.statIconBox, { backgroundColor: '#f3e8ff' }]}>
            <Layers size={18} color="#7c3aed" strokeWidth={2.2} />
          </View>
          <Text style={[styles.statNumber, { color: '#7c3aed' }]}>
            {stats?.total_categories ?? 0}
          </Text>
          <Text style={[styles.statLabel, { color: theme.textSecondary }]}>Categories</Text>
        </View>
      </View>

      {/* Recent Posts Section */}
      <View style={styles.sectionHeaderRow}>
        <View style={styles.sectionTitleRow}>
          <Clock size={16} color={theme.text} strokeWidth={2.2} />
          <Text style={[styles.sectionHeading, { color: theme.text }]}>Recent Articles</Text>
        </View>
        <TouchableOpacity
          style={styles.viewAllRow}
          onPress={() => onNavigateTab('posts')}>
          <Text style={styles.viewAllText}>View All</Text>
          <ArrowRight size={13} color="#2563eb" />
        </TouchableOpacity>
      </View>

      {recentPosts.length === 0 ? (
        <View
          style={[
            styles.emptyBox,
            { backgroundColor: theme.backgroundElement, borderColor: 'rgba(0,0,0,0.06)' },
          ]}>
          <Text style={[styles.emptyText, { color: theme.textSecondary }]}>
            No articles found.
          </Text>
        </View>
      ) : (
        <View style={styles.recentList}>
          {recentPosts.map((post) => {
            const isDraft = post.status === 'draft';
            return (
              <View
                key={post.id}
                style={[
                  styles.postItemCard,
                  { backgroundColor: theme.backgroundElement, borderColor: 'rgba(0,0,0,0.06)' },
                ]}>
                <Image
                  source={{
                    uri:
                      post.featured_image ||
                      `${portalUrl}/assets/images/default-post.jpg`,
                  }}
                  style={styles.postThumb}
                  resizeMode="cover"
                />

                <View style={styles.postDetails}>
                  <Text style={[styles.postTitle, { color: theme.text }]} numberOfLines={2}>
                    {post.title}
                  </Text>

                  <View style={styles.postMetaRow}>
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

                    <View style={styles.viewsBadgeRow}>
                      <Eye size={12} color={theme.textSecondary} style={{ marginRight: 3 }} />
                      <Text style={[styles.viewsText, { color: theme.textSecondary }]}>
                        {post.views}
                      </Text>
                    </View>
                  </View>

                  {/* Actions Row */}
                  <View style={styles.postActionRow}>
                    <TouchableOpacity
                      style={[
                        styles.statusTogglePill,
                        isDraft ? styles.pillDraft : styles.pillPublished,
                      ]}
                      onPress={() => handleToggleStatus(post)}
                      disabled={togglingPostId === post.id}>
                      {togglingPostId === post.id ? (
                        <ActivityIndicator size="small" color="#fff" />
                      ) : (
                        <View style={styles.statusPillContent}>
                          <View
                            style={[
                              styles.statusDot,
                              { backgroundColor: isDraft ? '#f59e0b' : '#10b981' },
                            ]}
                          />
                          <Text
                            style={[
                              styles.statusToggleText,
                              { color: isDraft ? '#b45309' : '#047857' },
                            ]}>
                            {isDraft ? 'Draft' : 'Published'}
                          </Text>
                        </View>
                      )}
                    </TouchableOpacity>

                    <TouchableOpacity
                      style={[
                        styles.editIconBtn,
                        { backgroundColor: theme.backgroundSelected },
                      ]}
                      onPress={() => onEditPost(post)}>
                      <Pencil size={12} color={theme.text} style={{ marginRight: 4 }} />
                      <Text style={[styles.editBtnText, { color: theme.text }]}>Edit</Text>
                    </TouchableOpacity>

                    <TouchableOpacity
                      style={[
                        styles.editIconBtn,
                        { backgroundColor: '#eff6ff' },
                      ]}
                      onPress={() => {
                        const url = `${portalUrl}/article.php?slug=${encodeURIComponent(post.slug)}`;
                        Linking.openURL(url).catch(() => {
                          Linking.openURL(`${portalUrl}/article/${post.slug}`).catch((err) => {
                            Alert.alert('Error', err.message || 'Cannot open article link');
                          });
                        });
                      }}>
                      <ExternalLink size={12} color="#2563eb" style={{ marginRight: 4 }} />
                      <Text style={[styles.editBtnText, { color: '#2563eb' }]}>View</Text>
                    </TouchableOpacity>
                  </View>
                </View>
              </View>
            );
          })}
        </View>
      )}

      {/* Bottom Section: Broadcast & Ticker Controls */}
      <View style={styles.bottomSectionHeader}>
        <Text style={[styles.sectionHeading, { color: theme.text }]}>Broadcast & Live Stream</Text>
        <Text style={[styles.bottomSectionSub, { color: theme.textSecondary }]}>
          Real-time portal switches
        </Text>
      </View>

      <View
        style={[
          styles.controlCard,
          { backgroundColor: theme.backgroundElement, borderColor: 'rgba(0,0,0,0.06)' },
        ]}>
        <View style={styles.controlRow}>
          <View style={styles.controlInfo}>
            <View style={styles.controlTitleGroup}>
              <Radio size={16} color="#dc2626" strokeWidth={2.2} />
              <Text style={[styles.controlTitle, { color: theme.text }]}>
                YouTube Live Broadcast
              </Text>
            </View>
            <Text style={[styles.controlDesc, { color: theme.textSecondary }]} numberOfLines={1}>
              {liveStream.url || 'No stream URL configured in settings'}
            </Text>
          </View>
          <View style={styles.switchWrapper}>
            {updatingLive ? (
              <ActivityIndicator size="small" color="#dc2626" />
            ) : (
              <Switch
                value={liveEnabled}
                onValueChange={handleToggleLive}
                trackColor={{ false: '#767577', true: '#dc2626' }}
                thumbColor="#ffffff"
              />
            )}
          </View>
        </View>

        <View style={styles.divider} />

        <View style={styles.controlRow}>
          <View style={styles.controlInfo}>
            <View style={styles.controlTitleGroup}>
              <Zap size={16} color="#f59e0b" strokeWidth={2.2} />
              <Text style={[styles.controlTitle, { color: theme.text }]}>
                Breaking News Ticker
              </Text>
            </View>
            <Text style={[styles.controlDesc, { color: theme.textSecondary }]}>
              Displays real-time headline banner at top of portal
            </Text>
          </View>
          <View style={styles.switchWrapper}>
            {updatingBreaking ? (
              <ActivityIndicator size="small" color="#dc2626" />
            ) : (
              <Switch
                value={breakingEnabled}
                onValueChange={handleToggleBreaking}
                trackColor={{ false: '#767577', true: '#f59e0b' }}
                thumbColor="#ffffff"
              />
            )}
          </View>
        </View>
      </View>
    </ScrollView>
  );
}

const styles = StyleSheet.create({
  container: {
    padding: Spacing.four,
    paddingBottom: 110,
  },
  sectionHeading: {
    fontSize: 15,
    fontWeight: '800',
    letterSpacing: -0.2,
  },
  sectionTitleRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 6,
  },
  sectionHeaderRow: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    marginTop: Spacing.two,
    marginBottom: 10,
  },
  viewAllRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 2,
  },
  viewAllText: {
    color: '#2563eb',
    fontSize: 12,
    fontWeight: '700',
  },
  statsGrid: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: 10,
    marginBottom: Spacing.three,
  },
  statCard: {
    flexBasis: '47%',
    flexGrow: 1,
    borderRadius: 14,
    padding: 14,
    alignItems: 'center',
    minWidth: 130,
    borderWidth: 1,
  },
  statIconBox: {
    width: 36,
    height: 36,
    borderRadius: 10,
    alignItems: 'center',
    justifyContent: 'center',
    marginBottom: 6,
  },
  statNumber: {
    fontSize: 18,
    fontWeight: '800',
    marginBottom: 2,
    letterSpacing: -0.4,
  },
  statLabel: {
    fontSize: 11,
    fontWeight: '600',
    textAlign: 'center',
  },
  recentList: {
    gap: 10,
    marginBottom: Spacing.four,
  },
  postItemCard: {
    flexDirection: 'row',
    borderRadius: 14,
    overflow: 'hidden',
    padding: 10,
    gap: 12,
    borderWidth: 1,
  },
  postThumb: {
    width: 80,
    height: 80,
    borderRadius: 10,
    backgroundColor: '#0f172a',
  },
  postDetails: {
    flex: 1,
    justifyContent: 'space-between',
  },
  postTitle: {
    fontSize: 13,
    fontWeight: '700',
    lineHeight: 18,
  },
  postMetaRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 8,
    marginVertical: 4,
  },
  catBadge: {
    paddingHorizontal: 7,
    paddingVertical: 2,
    borderRadius: 5,
  },
  catBadgeText: {
    fontSize: 10,
    fontWeight: '700',
  },
  viewsBadgeRow: {
    flexDirection: 'row',
    alignItems: 'center',
  },
  viewsText: {
    fontSize: 11,
    fontWeight: '500',
  },
  postActionRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 8,
  },
  statusTogglePill: {
    paddingHorizontal: 9,
    paddingVertical: 4,
    borderRadius: 6,
    minHeight: 26,
    justifyContent: 'center',
  },
  pillPublished: {
    backgroundColor: '#dcfce7',
  },
  pillDraft: {
    backgroundColor: '#fef3c7',
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
  statusToggleText: {
    fontSize: 10,
    fontWeight: '700',
  },
  editIconBtn: {
    flexDirection: 'row',
    alignItems: 'center',
    paddingHorizontal: 10,
    paddingVertical: 4,
    borderRadius: 6,
  },
  editBtnText: {
    fontSize: 11,
    fontWeight: '600',
  },
  emptyBox: {
    padding: 30,
    borderRadius: 14,
    alignItems: 'center',
    borderWidth: 1,
    marginBottom: Spacing.four,
  },
  emptyText: {
    fontSize: 13,
  },
  bottomSectionHeader: {
    marginTop: Spacing.three,
    marginBottom: 8,
  },
  bottomSectionSub: {
    fontSize: 11,
    fontWeight: '500',
    marginTop: 1,
  },
  controlCard: {
    borderRadius: 16,
    padding: Spacing.three,
    borderWidth: 1,
  },
  controlRow: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
  },
  controlInfo: {
    flex: 1,
    marginRight: 12,
  },
  controlTitleGroup: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 6,
    marginBottom: 2,
  },
  controlTitle: {
    fontSize: 13,
    fontWeight: '700',
  },
  controlDesc: {
    fontSize: 11,
    fontWeight: '500',
  },
  switchWrapper: {
    minWidth: 50,
    alignItems: 'center',
  },
  divider: {
    height: 1,
    backgroundColor: 'rgba(0,0,0,0.06)',
    marginVertical: 10,
  },
});
