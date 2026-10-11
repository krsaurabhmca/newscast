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
  MousePointerClick,
  Activity,
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

type TimePeriod = 'today' | '7days' | '30days' | 'lifetime';

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

  const [period, setPeriod] = useState<TimePeriod>('today');
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

  // Helper values calculated based on selected period filter
  const getAdClicksCount = () => {
    if (!stats?.ad_clicks) return 0;
    switch (period) {
      case 'today':
        return stats.ad_clicks.today;
      case '7days':
        return stats.ad_clicks.seven_days;
      case '30days':
        return stats.ad_clicks.thirty_days;
      case 'lifetime':
        return stats.ad_clicks.lifetime;
    }
  };

  const getViewsCount = () => {
    if (!stats?.views_by_period) return stats?.total_views ?? 0;
    switch (period) {
      case 'today':
        return stats.views_by_period.today;
      case '7days':
        return stats.views_by_period.seven_days;
      case '30days':
        return stats.views_by_period.thirty_days;
      case 'lifetime':
        return stats.views_by_period.lifetime || (stats.total_views ?? 0);
    }
  };

  const getPostsCount = () => {
    if (!stats?.posts_by_period) return stats?.today_posts ?? 0;
    switch (period) {
      case 'today':
        return stats.posts_by_period.today ?? (stats.today_posts ?? 0);
      case '7days':
        return stats.posts_by_period.seven_days;
      case '30days':
        return stats.posts_by_period.thirty_days;
      case 'lifetime':
        return stats.posts_by_period.lifetime || (stats.total_posts ?? 0);
    }
  };

  const getPeriodLabel = () => {
    switch (period) {
      case 'today':
        return 'Today';
      case '7days':
        return 'Past 7 Days';
      case '30days':
        return 'Past 30 Days';
      case 'lifetime':
        return 'All Time';
    }
  };

  const liveVisitors = stats?.live_visitors ?? 0;

  return (
    <ScrollView contentContainerStyle={styles.container} showsVerticalScrollIndicator={false}>
      {/* Overview Header with Live Visitor Beacon */}
      <View style={styles.sectionHeaderRow}>
        <View style={styles.sectionTitleRow}>
          <TrendingUp size={16} color={theme.text} strokeWidth={2.2} />
          <Text style={[styles.sectionHeading, { color: theme.text }]}>Portal Overview</Text>
        </View>

        {/* Live Active Visitor Pill */}
        <View style={styles.liveBadgePill}>
          <View style={styles.livePulseDot} />
          <Text style={styles.liveBadgeText}>
            {liveVisitors} Live {liveVisitors === 1 ? 'Visitor' : 'Visitors'}
          </Text>
        </View>
      </View>

      {/* Time Period Filter Tabs: Today (default) | 7 Days | 30 Days | Lifetime */}
      <View
        style={[
          styles.filterTabsWrapper,
          { backgroundColor: theme.backgroundElement, borderColor: 'rgba(0,0,0,0.06)' },
        ]}>
        {(
          [
            { key: 'today', label: 'Today' },
            { key: '7days', label: '7 Days' },
            { key: '30days', label: '30 Days' },
            { key: 'lifetime', label: 'Lifetime' },
          ] as const
        ).map((tab) => {
          const isActive = period === tab.key;
          return (
            <TouchableOpacity
              key={tab.key}
              style={[styles.filterTab, isActive && styles.filterTabActive]}
              onPress={() => setPeriod(tab.key)}
              activeOpacity={0.75}>
              <Text
                style={[
                  styles.filterTabText,
                  isActive ? styles.filterTabTextActive : { color: theme.textSecondary },
                ]}>
                {tab.label}
              </Text>
            </TouchableOpacity>
          );
        })}
      </View>

      {/* Hero Analytics Grid (Live Visitors, Ad Clicks, Views, Articles) */}
      <View style={styles.statsGrid}>
        {/* Card 1: Live Visitors */}
        <View
          style={[
            styles.statCard,
            styles.heroStatCard,
            { backgroundColor: theme.backgroundElement, borderColor: 'rgba(0,0,0,0.06)' },
          ]}>
          <View style={styles.statTopRow}>
            <View style={[styles.statIconBox, { backgroundColor: '#fee2e2' }]}>
              <Activity size={18} color="#dc2626" strokeWidth={2.4} />
            </View>
            <View style={styles.liveDotBadge}>
              <View style={styles.liveDotSmall} />
              <Text style={styles.liveDotSmallText}>Active Now</Text>
            </View>
          </View>
          <Text style={[styles.statNumber, { color: '#dc2626' }]}>
            {liveVisitors.toLocaleString()}
          </Text>
          <Text style={[styles.statLabel, { color: theme.textSecondary }]}>Live Visitors</Text>
          <Text style={[styles.statSubText, { color: theme.textSecondary }]}>Last 5 minutes</Text>
        </View>

        {/* Card 2: Ad Clicks (Filtered by Period) */}
        <View
          style={[
            styles.statCard,
            styles.heroStatCard,
            { backgroundColor: theme.backgroundElement, borderColor: 'rgba(0,0,0,0.06)' },
          ]}>
          <View style={styles.statTopRow}>
            <View style={[styles.statIconBox, { backgroundColor: '#fef3c7' }]}>
              <MousePointerClick size={18} color="#d97706" strokeWidth={2.2} />
            </View>
            <View style={[styles.periodBadge, { backgroundColor: 'rgba(217, 119, 6, 0.1)' }]}>
              <Text style={[styles.periodBadgeText, { color: '#d97706' }]}>{getPeriodLabel()}</Text>
            </View>
          </View>
          <Text style={[styles.statNumber, { color: '#d97706' }]}>
            {getAdClicksCount().toLocaleString()}
          </Text>
          <Text style={[styles.statLabel, { color: theme.textSecondary }]}>Ad Clicks</Text>
          <Text style={[styles.statSubText, { color: theme.textSecondary }]}>
            Interactions in {getPeriodLabel().toLowerCase()}
          </Text>
        </View>

        {/* Card 3: Article Views (Filtered by Period) */}
        <View
          style={[
            styles.statCard,
            styles.heroStatCard,
            { backgroundColor: theme.backgroundElement, borderColor: 'rgba(0,0,0,0.06)' },
          ]}>
          <View style={styles.statTopRow}>
            <View style={[styles.statIconBox, { backgroundColor: '#e0e7ff' }]}>
              <Eye size={18} color="#4f46e5" strokeWidth={2.2} />
            </View>
            <View style={[styles.periodBadge, { backgroundColor: 'rgba(79, 70, 229, 0.1)' }]}>
              <Text style={[styles.periodBadgeText, { color: '#4f46e5' }]}>{getPeriodLabel()}</Text>
            </View>
          </View>
          <Text style={[styles.statNumber, { color: '#4f46e5' }]}>
            {getViewsCount().toLocaleString()}
          </Text>
          <Text style={[styles.statLabel, { color: theme.textSecondary }]}>Article Views</Text>
          <Text style={[styles.statSubText, { color: theme.textSecondary }]}>
            Traffic in {getPeriodLabel().toLowerCase()}
          </Text>
        </View>

        {/* Card 4: Articles (Filtered by Period) */}
        <View
          style={[
            styles.statCard,
            styles.heroStatCard,
            { backgroundColor: theme.backgroundElement, borderColor: 'rgba(0,0,0,0.06)' },
          ]}>
          <View style={styles.statTopRow}>
            <View style={[styles.statIconBox, { backgroundColor: '#dcfce7' }]}>
              <CheckCircle2 size={18} color="#16a34a" strokeWidth={2.2} />
            </View>
            <View style={[styles.periodBadge, { backgroundColor: 'rgba(22, 163, 74, 0.1)' }]}>
              <Text style={[styles.periodBadgeText, { color: '#16a34a' }]}>{getPeriodLabel()}</Text>
            </View>
          </View>
          <Text style={[styles.statNumber, { color: '#16a34a' }]}>
            {getPostsCount().toLocaleString()}
          </Text>
          <Text style={[styles.statLabel, { color: theme.textSecondary }]}>Stories</Text>
          <Text style={[styles.statSubText, { color: theme.textSecondary }]}>
            Created in {getPeriodLabel().toLowerCase()}
          </Text>
        </View>
      </View>

      {/* Secondary Quick Summary Pills */}
      <View style={styles.summaryBar}>
        <View
          style={[
            styles.summaryItem,
            { backgroundColor: theme.backgroundElement, borderColor: 'rgba(0,0,0,0.06)' },
          ]}>
          <View style={[styles.miniDot, { backgroundColor: '#16a34a' }]} />
          <Text style={[styles.summaryVal, { color: theme.text }]}>
            {stats?.published_posts ?? 0}
          </Text>
          <Text style={[styles.summaryLbl, { color: theme.textSecondary }]}>Published</Text>
        </View>

        <View
          style={[
            styles.summaryItem,
            { backgroundColor: theme.backgroundElement, borderColor: 'rgba(0,0,0,0.06)' },
          ]}>
          <View style={[styles.miniDot, { backgroundColor: '#d97706' }]} />
          <Text style={[styles.summaryVal, { color: theme.text }]}>
            {stats?.draft_posts ?? 0}
          </Text>
          <Text style={[styles.summaryLbl, { color: theme.textSecondary }]}>Drafts</Text>
        </View>

        <View
          style={[
            styles.summaryItem,
            { backgroundColor: theme.backgroundElement, borderColor: 'rgba(0,0,0,0.06)' },
          ]}>
          <View style={[styles.miniDot, { backgroundColor: '#7c3aed' }]} />
          <Text style={[styles.summaryVal, { color: theme.text }]}>
            {stats?.total_categories ?? 0}
          </Text>
          <Text style={[styles.summaryLbl, { color: theme.textSecondary }]}>Categories</Text>
        </View>
      </View>

      {/* Broadcast & Live Stream Switches */}
      <View style={styles.bottomSectionHeader}>
        <Text style={[styles.sectionHeading, { color: theme.text }]}>Broadcast & Alerts</Text>
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
                trackColor={{ false: '#e2e8f0', true: '#dc2626' }}
                thumbColor="#ffffff"
              />
            )}
          </View>
        </View>

        <View style={[styles.divider, { backgroundColor: 'rgba(0,0,0,0.06)' }]} />

        <View style={styles.controlRow}>
          <View style={styles.controlInfo}>
            <View style={styles.controlTitleGroup}>
              <Zap size={16} color="#f59e0b" strokeWidth={2.2} />
              <Text style={[styles.controlTitle, { color: theme.text }]}>
                Breaking News Ticker
              </Text>
            </View>
            <Text style={[styles.controlDesc, { color: theme.textSecondary }]}>
              Controls the animated breaking alert bar across the portal
            </Text>
          </View>
          <View style={styles.switchWrapper}>
            {updatingBreaking ? (
              <ActivityIndicator size="small" color="#dc2626" />
            ) : (
              <Switch
                value={breakingEnabled}
                onValueChange={handleToggleBreaking}
                trackColor={{ false: '#e2e8f0', true: '#f59e0b' }}
                thumbColor="#ffffff"
              />
            )}
          </View>
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
    </ScrollView>
  );
}

const styles = StyleSheet.create({
  container: {
    padding: Spacing.four,
    paddingBottom: 90,
    gap: 14,
  },
  sectionHeaderRow: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    marginTop: 4,
  },
  sectionTitleRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 7,
  },
  sectionHeading: {
    fontSize: 15,
    fontWeight: '800',
  },
  liveBadgePill: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: '#ecfdf5',
    borderWidth: 1,
    borderColor: '#a7f3d0',
    paddingHorizontal: 9,
    paddingVertical: 4,
    borderRadius: 14,
    gap: 5,
  },
  livePulseDot: {
    width: 7,
    height: 7,
    borderRadius: 3.5,
    backgroundColor: '#10b981',
  },
  liveBadgeText: {
    color: '#047857',
    fontSize: 11,
    fontWeight: '800',
  },
  filterTabsWrapper: {
    flexDirection: 'row',
    padding: 3,
    borderRadius: 12,
    borderWidth: 1,
    gap: 4,
  },
  filterTab: {
    flex: 1,
    paddingVertical: 7,
    alignItems: 'center',
    justifyContent: 'center',
    borderRadius: 9,
  },
  filterTabActive: {
    backgroundColor: '#0f172a',
  },
  filterTabText: {
    fontSize: 12,
    fontWeight: '700',
  },
  filterTabTextActive: {
    color: '#ffffff',
  },
  statsGrid: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: 10,
  },
  statCard: {
    width: '48%',
    borderRadius: 14,
    padding: 12,
    borderWidth: 1,
  },
  heroStatCard: {
    justifyContent: 'space-between',
    minHeight: 110,
  },
  statTopRow: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    marginBottom: 6,
  },
  statIconBox: {
    width: 34,
    height: 34,
    borderRadius: 9,
    alignItems: 'center',
    justifyContent: 'center',
  },
  liveDotBadge: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: 'rgba(220, 38, 38, 0.08)',
    paddingHorizontal: 6,
    paddingVertical: 2,
    borderRadius: 8,
    gap: 4,
  },
  liveDotSmall: {
    width: 5,
    height: 5,
    borderRadius: 2.5,
    backgroundColor: '#dc2626',
  },
  liveDotSmallText: {
    color: '#dc2626',
    fontSize: 9,
    fontWeight: '800',
  },
  periodBadge: {
    paddingHorizontal: 6,
    paddingVertical: 2,
    borderRadius: 6,
  },
  periodBadgeText: {
    fontSize: 9,
    fontWeight: '800',
  },
  statNumber: {
    fontSize: 22,
    fontWeight: '900',
    letterSpacing: -0.5,
  },
  statLabel: {
    fontSize: 12,
    fontWeight: '700',
    marginTop: 2,
  },
  statSubText: {
    fontSize: 10,
    marginTop: 2,
  },
  summaryBar: {
    flexDirection: 'row',
    gap: 8,
  },
  summaryItem: {
    flex: 1,
    flexDirection: 'row',
    alignItems: 'center',
    paddingVertical: 8,
    paddingHorizontal: 10,
    borderRadius: 10,
    borderWidth: 1,
    gap: 6,
  },
  miniDot: {
    width: 6,
    height: 6,
    borderRadius: 3,
  },
  summaryVal: {
    fontSize: 13,
    fontWeight: '800',
  },
  summaryLbl: {
    fontSize: 10,
    fontWeight: '600',
  },
  bottomSectionHeader: {
    marginTop: 4,
  },
  bottomSectionSub: {
    fontSize: 11,
    marginTop: 2,
  },
  controlCard: {
    borderRadius: 14,
    borderWidth: 1,
    padding: 14,
  },
  controlRow: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
  },
  controlInfo: {
    flex: 1,
    marginRight: 10,
  },
  controlTitleGroup: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 6,
    marginBottom: 4,
  },
  controlTitle: {
    fontSize: 13,
    fontWeight: '700',
  },
  controlDesc: {
    fontSize: 11,
  },
  switchWrapper: {
    width: 50,
    alignItems: 'flex-end',
  },
  divider: {
    height: 1,
    marginVertical: 12,
  },
  viewAllRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 4,
  },
  viewAllText: {
    color: '#2563eb',
    fontSize: 12,
    fontWeight: '700',
  },
  emptyBox: {
    borderRadius: 12,
    borderWidth: 1,
    padding: 20,
    alignItems: 'center',
  },
  emptyText: {
    fontSize: 13,
  },
  recentList: {
    gap: 10,
  },
  postItemCard: {
    borderRadius: 12,
    borderWidth: 1,
    padding: 10,
    flexDirection: 'row',
    gap: 10,
  },
  postThumb: {
    width: 64,
    height: 64,
    borderRadius: 8,
    backgroundColor: '#0f172a',
  },
  postDetails: {
    flex: 1,
    justifyContent: 'space-between',
  },
  postTitle: {
    fontSize: 12,
    fontWeight: '700',
    lineHeight: 16,
  },
  postMetaRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 6,
    marginVertical: 2,
  },
  catBadge: {
    paddingHorizontal: 5,
    paddingVertical: 2,
    borderRadius: 4,
  },
  catBadgeText: {
    fontSize: 9,
    fontWeight: '700',
  },
  viewsBadgeRow: {
    flexDirection: 'row',
    alignItems: 'center',
  },
  viewsText: {
    fontSize: 10,
  },
  postActionRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 6,
    marginTop: 2,
  },
  statusTogglePill: {
    paddingHorizontal: 7,
    paddingVertical: 3,
    borderRadius: 5,
  },
  statusPillContent: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 3,
  },
  statusDot: {
    width: 5,
    height: 5,
    borderRadius: 2.5,
  },
  pillPublished: {
    backgroundColor: '#dcfce7',
  },
  pillDraft: {
    backgroundColor: '#fef3c7',
  },
  statusToggleText: {
    fontSize: 9,
    fontWeight: '700',
  },
  editIconBtn: {
    flexDirection: 'row',
    alignItems: 'center',
    paddingHorizontal: 8,
    paddingVertical: 3,
    borderRadius: 5,
  },
  editBtnText: {
    fontSize: 10,
    fontWeight: '600',
  },
});
