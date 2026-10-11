import React, { useState, useEffect, useCallback } from 'react';
import {
  View,
  Text,
  TextInput,
  TouchableOpacity,
  ScrollView,
  StyleSheet,
  ActivityIndicator,
  Modal,
  Alert,
  RefreshControl,
} from 'react-native';
import {
  BarChart3,
  Plus,
  Trash2,
  CheckCircle2,
  Clock,
  Users,
  X,
  PieChart,
  RefreshCw,
  Zap,
} from '@/components/ui/icons';
import {
  PollItem,
  getPolls,
  savePoll,
  togglePollStatus,
  deletePoll,
} from '@/utils/api';
import { Spacing } from '@/constants/theme';
import { useTheme } from '@/hooks/use-theme';

interface AdminPollsTabProps {
  portalUrl: string;
  onRefreshStats?: () => void;
}

export function AdminPollsTab({ portalUrl, onRefreshStats }: AdminPollsTabProps) {
  const theme = useTheme();

  const [polls, setPolls] = useState<PollItem[]>([]);
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [filter, setFilter] = useState<'all' | 'active' | 'closed'>('all');

  // Modal State
  const [modalVisible, setModalVisible] = useState(false);
  const [question, setQuestion] = useState('');
  const [options, setOptions] = useState<string[]>(['', '']);
  const [status, setStatus] = useState<'active' | 'closed'>('active');
  const [submitting, setSubmitting] = useState(false);
  const [togglingId, setTogglingId] = useState<number | null>(null);
  const [deletingId, setDeletingId] = useState<number | null>(null);

  const fetchPollsList = useCallback(async () => {
    if (!portalUrl) return;
    try {
      const data = await getPolls(portalUrl);
      setPolls(data);
    } catch (err: any) {
      console.warn('Failed to load polls', err);
    } finally {
      setLoading(false);
      setRefreshing(false);
    }
  }, [portalUrl]);

  useEffect(() => {
    fetchPollsList();
  }, [fetchPollsList]);

  const onPullRefresh = () => {
    setRefreshing(true);
    fetchPollsList();
    if (onRefreshStats) onRefreshStats();
  };

  const handleOpenCreateModal = () => {
    setQuestion('');
    setOptions(['', '']);
    setStatus('active');
    setModalVisible(true);
  };

  const handleAddOptionField = () => {
    if (options.length >= 6) {
      Alert.alert('Limit Reached', 'A poll can have maximum 6 options');
      return;
    }
    setOptions([...options, '']);
  };

  const handleRemoveOptionField = (idx: number) => {
    if (options.length <= 2) {
      Alert.alert('Required', 'A poll requires at least 2 options');
      return;
    }
    setOptions(options.filter((_, i) => i !== idx));
  };

  const handleOptionChange = (text: string, idx: number) => {
    const updated = [...options];
    updated[idx] = text;
    setOptions(updated);
  };

  const handleCreatePoll = async () => {
    if (!question.trim()) {
      Alert.alert('Required', 'Please enter a poll question');
      return;
    }

    const validOptions = options.map((o) => o.trim()).filter((o) => o.length > 0);
    if (validOptions.length < 2) {
      Alert.alert('Required', 'Please provide at least 2 non-empty options');
      return;
    }

    setSubmitting(true);
    try {
      await savePoll(portalUrl, {
        question: question.trim(),
        options: validOptions,
        status,
      });
      setModalVisible(false);
      fetchPollsList();
      if (onRefreshStats) onRefreshStats();
      Alert.alert('Success', 'Poll created successfully!');
    } catch (err: any) {
      Alert.alert('Error', err.message || 'Failed to create poll');
    } finally {
      setSubmitting(false);
    }
  };

  const handleToggleStatus = async (poll: PollItem) => {
    const newStatus = poll.status === 'active' ? 'closed' : 'active';
    setTogglingId(poll.id);
    try {
      await togglePollStatus(portalUrl, poll.id, newStatus);
      setPolls((prev) =>
        prev.map((p) => (p.id === poll.id ? { ...p, status: newStatus } : p))
      );
      if (onRefreshStats) onRefreshStats();
    } catch (err: any) {
      Alert.alert('Error', err.message || 'Failed to toggle status');
    } finally {
      setTogglingId(null);
    }
  };

  const handleDelete = (poll: PollItem) => {
    Alert.alert(
      'Delete Poll',
      `Are you sure you want to delete this poll? All recorded votes will be removed permanently.`,
      [
        { text: 'Cancel', style: 'cancel' },
        {
          text: 'Delete',
          style: 'destructive',
          onPress: async () => {
            setDeletingId(poll.id);
            try {
              await deletePoll(portalUrl, poll.id);
              setPolls((prev) => prev.filter((p) => p.id !== poll.id));
              if (onRefreshStats) onRefreshStats();
            } catch (err: any) {
              Alert.alert('Error', err.message || 'Failed to delete poll');
            } finally {
              setDeletingId(null);
            }
          },
        },
      ]
    );
  };

  const filteredPolls = polls.filter((p) => {
    if (filter === 'active') return p.status === 'active';
    if (filter === 'closed') return p.status === 'closed';
    return true;
  });

  return (
    <View style={styles.container}>
      {/* Top Filter & Action Bar */}
      <View
        style={[
          styles.actionHeader,
          { backgroundColor: theme.backgroundElement, borderBottomColor: 'rgba(0,0,0,0.06)' },
        ]}>
        <View style={styles.filterTabs}>
          {(['all', 'active', 'closed'] as const).map((tab) => {
            const isActive = filter === tab;
            return (
              <TouchableOpacity
                key={tab}
                style={[
                  styles.filterTabBtn,
                  isActive && { backgroundColor: theme.text },
                ]}
                onPress={() => setFilter(tab)}>
                <Text
                  style={[
                    styles.filterTabText,
                    { color: isActive ? theme.background : theme.textSecondary },
                  ]}>
                  {tab === 'all' ? `All (${polls.length})` : tab.toUpperCase()}
                </Text>
              </TouchableOpacity>
            );
          })}
        </View>

        <TouchableOpacity style={styles.addBtn} onPress={handleOpenCreateModal}>
          <Plus size={14} color="#ffffff" strokeWidth={2.5} style={{ marginRight: 4 }} />
          <Text style={styles.addBtnText}>New Poll</Text>
        </TouchableOpacity>
      </View>

      {/* Main List */}
      {loading ? (
        <View style={styles.centerBox}>
          <ActivityIndicator size="large" color="#dc2626" />
          <Text style={[styles.loadingText, { color: theme.textSecondary }]}>Loading polls...</Text>
        </View>
      ) : (
        <ScrollView
          contentContainerStyle={styles.scrollContent}
          refreshControl={
            <RefreshControl refreshing={refreshing} onRefresh={onPullRefresh} colors={['#dc2626']} />
          }>
          {filteredPolls.length === 0 ? (
            <View style={styles.emptyState}>
              <PieChart size={48} color={theme.textSecondary} strokeWidth={1.5} />
              <Text style={[styles.emptyHeading, { color: theme.text }]}>No polls found</Text>
              <Text style={[styles.emptySub, { color: theme.textSecondary }]}>
                {filter !== 'all' ? `No ${filter} polls available.` : 'Create a poll to gather opinions from your readers.'}
              </Text>
              <TouchableOpacity style={styles.emptyBtn} onPress={handleOpenCreateModal}>
                <Plus size={14} color="#fff" strokeWidth={2.5} style={{ marginRight: 6 }} />
                <Text style={styles.emptyBtnText}>Create First Poll</Text>
              </TouchableOpacity>
            </View>
          ) : (
            filteredPolls.map((poll) => {
              const isActive = poll.status === 'active';
              const isToggling = togglingId === poll.id;
              const isDeleting = deletingId === poll.id;

              return (
                <View
                  key={poll.id}
                  style={[
                    styles.pollCard,
                    { backgroundColor: theme.backgroundElement, borderColor: 'rgba(0,0,0,0.06)' },
                  ]}>
                  {/* Card Header */}
                  <View style={styles.cardTopRow}>
                    <View style={styles.badgeRow}>
                      <View
                        style={[
                          styles.statusBadge,
                          { backgroundColor: isActive ? '#ecfdf5' : '#fef2f2' },
                        ]}>
                        <View
                          style={[
                            styles.statusDot,
                            { backgroundColor: isActive ? '#10b981' : '#ef4444' },
                          ]}
                        />
                        <Text
                          style={[
                            styles.statusText,
                            { color: isActive ? '#065f46' : '#991b1b' },
                          ]}>
                          {isActive ? 'Active' : 'Closed'}
                        </Text>
                      </View>

                      <View style={styles.votesBadge}>
                        <Users size={12} color={theme.textSecondary} style={{ marginRight: 4 }} />
                        <Text style={[styles.votesCountText, { color: theme.textSecondary }]}>
                          {poll.total_votes} votes
                        </Text>
                      </View>
                    </View>

                    {/* Actions */}
                    <View style={styles.topActions}>
                      <TouchableOpacity
                        style={[
                          styles.statusToggleBtn,
                          { backgroundColor: isActive ? '#fef3c7' : '#ecfdf5' },
                        ]}
                        onPress={() => handleToggleStatus(poll)}
                        disabled={isToggling}>
                        {isToggling ? (
                          <ActivityIndicator size="small" color="#dc2626" />
                        ) : (
                          <Text
                            style={[
                              styles.statusToggleBtnText,
                              { color: isActive ? '#b45309' : '#047857' },
                            ]}>
                            {isActive ? 'Close' : 'Activate'}
                          </Text>
                        )}
                      </TouchableOpacity>

                      <TouchableOpacity
                        style={styles.deleteIconBtn}
                        onPress={() => handleDelete(poll)}
                        disabled={isDeleting}>
                        {isDeleting ? (
                          <ActivityIndicator size="small" color="#dc2626" />
                        ) : (
                          <Trash2 size={14} color="#ef4444" />
                        )}
                      </TouchableOpacity>
                    </View>
                  </View>

                  {/* Poll Question */}
                  <Text style={[styles.questionText, { color: theme.text }]}>
                    {poll.question}
                  </Text>

                  {/* Options & Progress Bars */}
                  <View style={styles.optionsList}>
                    {poll.options.map((opt) => {
                      const pct = opt.percentage ?? (poll.total_votes > 0 ? Math.round((opt.votes_count / poll.total_votes) * 100) : 0);
                      return (
                        <View key={opt.id} style={styles.optionItem}>
                          <View style={styles.optionHeader}>
                            <Text style={[styles.optionTitle, { color: theme.text }]}>
                              {opt.option_text}
                            </Text>
                            <Text style={[styles.optionMeta, { color: theme.textSecondary }]}>
                              {opt.votes_count} ({pct}%)
                            </Text>
                          </View>
                          <View style={[styles.progressBarTrack, { backgroundColor: theme.backgroundSelected }]}>
                            <View
                              style={[
                                styles.progressBarFill,
                                { width: `${Math.min(pct, 100)}%` },
                              ]}
                            />
                          </View>
                        </View>
                      );
                    })}
                  </View>

                  {/* Footer Meta */}
                  {poll.created_at && (
                    <View style={styles.cardFooter}>
                      <Clock size={11} color={theme.textSecondary} style={{ marginRight: 4 }} />
                      <Text style={[styles.footerDate, { color: theme.textSecondary }]}>
                        Created {new Date(poll.created_at).toLocaleDateString()}
                      </Text>
                    </View>
                  )}
                </View>
              );
            })
          )}
        </ScrollView>
      )}

      {/* Create Poll Modal */}
      <Modal visible={modalVisible} transparent animationType="fade" onRequestClose={() => setModalVisible(false)}>
        <View style={styles.modalBackdrop}>
          <View style={[styles.modalCard, { backgroundColor: theme.background }]}>
            <View style={styles.modalHeaderRow}>
              <View style={{ flexDirection: 'row', alignItems: 'center', gap: 6 }}>
                <BarChart3 size={18} color="#dc2626" strokeWidth={2.2} />
                <Text style={[styles.modalHeading, { color: theme.text }]}>Create New Poll</Text>
              </View>
              <TouchableOpacity onPress={() => setModalVisible(false)}>
                <X size={18} color={theme.textSecondary} />
              </TouchableOpacity>
            </View>

            <ScrollView showsVerticalScrollIndicator={false} keyboardShouldPersistTaps="handled">
              <Text style={[styles.fieldLabel, { color: theme.text }]}>Poll Question *</Text>
              <TextInput
                style={[
                  styles.questionInput,
                  { backgroundColor: theme.backgroundElement, color: theme.text },
                ]}
                placeholder="Ask a question for your readers..."
                placeholderTextColor={theme.textSecondary}
                value={question}
                onChangeText={setQuestion}
                multiline
                numberOfLines={3}
              />

              <View style={styles.optionsHeaderRow}>
                <Text style={[styles.fieldLabel, { color: theme.text, marginBottom: 0 }]}>
                  Options ({options.length}/6)
                </Text>
                {options.length < 6 && (
                  <TouchableOpacity onPress={handleAddOptionField} style={styles.addOptionSmallBtn}>
                    <Plus size={12} color="#dc2626" style={{ marginRight: 2 }} />
                    <Text style={styles.addOptionSmallText}>Add Option</Text>
                  </TouchableOpacity>
                )}
              </View>

              {options.map((opt, idx) => (
                <View key={idx} style={styles.optionInputRow}>
                  <TextInput
                    style={[
                      styles.optionInput,
                      { backgroundColor: theme.backgroundElement, color: theme.text },
                    ]}
                    placeholder={`Option ${idx + 1}`}
                    placeholderTextColor={theme.textSecondary}
                    value={opt}
                    onChangeText={(val) => handleOptionChange(val, idx)}
                  />
                  {options.length > 2 && (
                    <TouchableOpacity
                      style={styles.removeOptionBtn}
                      onPress={() => handleRemoveOptionField(idx)}>
                      <X size={14} color="#ef4444" />
                    </TouchableOpacity>
                  )}
                </View>
              ))}

              <Text style={[styles.fieldLabel, { color: theme.text, marginTop: 12 }]}>Initial Status</Text>
              <View style={styles.statusToggleContainer}>
                <TouchableOpacity
                  style={[
                    styles.statusSelectBtn,
                    status === 'active' && styles.statusSelectBtnActive,
                  ]}
                  onPress={() => setStatus('active')}>
                  <Text
                    style={[
                      styles.statusSelectText,
                      status === 'active' && styles.statusSelectTextActive,
                    ]}>
                    Active (Open to vote)
                  </Text>
                </TouchableOpacity>

                <TouchableOpacity
                  style={[
                    styles.statusSelectBtn,
                    status === 'closed' && styles.statusSelectBtnClosed,
                  ]}
                  onPress={() => setStatus('closed')}>
                  <Text
                    style={[
                      styles.statusSelectText,
                      status === 'closed' && styles.statusSelectTextClosed,
                    ]}>
                    Closed (View only)
                  </Text>
                </TouchableOpacity>
              </View>
            </ScrollView>

            {/* Modal Bottom Actions */}
            <View style={styles.modalBottomActions}>
              <TouchableOpacity
                style={[styles.cancelBtn, { backgroundColor: theme.backgroundElement }]}
                onPress={() => setModalVisible(false)}>
                <Text style={[styles.cancelBtnText, { color: theme.text }]}>Cancel</Text>
              </TouchableOpacity>

              <TouchableOpacity
                style={[styles.submitBtn, submitting && styles.btnDisabled]}
                onPress={handleCreatePoll}
                disabled={submitting}>
                {submitting ? (
                  <ActivityIndicator size="small" color="#fff" />
                ) : (
                  <>
                    <CheckCircle2 size={14} color="#fff" style={{ marginRight: 6 }} />
                    <Text style={styles.submitBtnText}>Publish Poll</Text>
                  </>
                )}
              </TouchableOpacity>
            </View>
          </View>
        </View>
      </Modal>
    </View>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
  },
  actionHeader: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    paddingHorizontal: Spacing.four,
    paddingVertical: 10,
    borderBottomWidth: 1,
  },
  filterTabs: {
    flexDirection: 'row',
    gap: 6,
  },
  filterTabBtn: {
    paddingHorizontal: 10,
    paddingVertical: 6,
    borderRadius: 8,
    backgroundColor: 'transparent',
  },
  filterTabText: {
    fontSize: 12,
    fontWeight: '700',
  },
  addBtn: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: '#dc2626',
    paddingHorizontal: 12,
    paddingVertical: 7,
    borderRadius: 8,
  },
  addBtnText: {
    color: '#ffffff',
    fontSize: 12,
    fontWeight: '700',
  },
  scrollContent: {
    padding: Spacing.four,
    paddingBottom: 90,
  },
  centerBox: {
    flex: 1,
    justifyContent: 'center',
    alignItems: 'center',
    padding: 30,
    gap: 10,
  },
  loadingText: {
    fontSize: 13,
  },
  emptyState: {
    paddingVertical: 60,
    alignItems: 'center',
    justifyContent: 'center',
  },
  emptyHeading: {
    fontSize: 16,
    fontWeight: '700',
    marginTop: 12,
    marginBottom: 4,
  },
  emptySub: {
    fontSize: 13,
    textAlign: 'center',
    maxWidth: 260,
    marginBottom: 16,
  },
  emptyBtn: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: '#dc2626',
    paddingHorizontal: 16,
    paddingVertical: 9,
    borderRadius: 8,
  },
  emptyBtnText: {
    color: '#fff',
    fontSize: 13,
    fontWeight: '700',
  },
  pollCard: {
    borderRadius: 14,
    padding: 14,
    marginBottom: 12,
    borderWidth: 1,
  },
  cardTopRow: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    marginBottom: 10,
  },
  badgeRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 8,
  },
  statusBadge: {
    flexDirection: 'row',
    alignItems: 'center',
    paddingHorizontal: 8,
    paddingVertical: 3,
    borderRadius: 6,
    gap: 5,
  },
  statusDot: {
    width: 6,
    height: 6,
    borderRadius: 3,
  },
  statusText: {
    fontSize: 11,
    fontWeight: '700',
  },
  votesBadge: {
    flexDirection: 'row',
    alignItems: 'center',
  },
  votesCountText: {
    fontSize: 11,
    fontWeight: '500',
  },
  topActions: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 8,
  },
  statusToggleBtn: {
    paddingHorizontal: 8,
    paddingVertical: 4,
    borderRadius: 6,
  },
  statusToggleBtnText: {
    fontSize: 11,
    fontWeight: '700',
  },
  deleteIconBtn: {
    padding: 4,
  },
  questionText: {
    fontSize: 15,
    fontWeight: '700',
    lineHeight: 22,
    marginBottom: 12,
  },
  optionsList: {
    gap: 10,
  },
  optionItem: {
    gap: 4,
  },
  optionHeader: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
  },
  optionTitle: {
    fontSize: 13,
    fontWeight: '600',
    flex: 1,
    marginRight: 8,
  },
  optionMeta: {
    fontSize: 12,
    fontWeight: '600',
  },
  progressBarTrack: {
    height: 7,
    borderRadius: 4,
    overflow: 'hidden',
  },
  progressBarFill: {
    height: '100%',
    backgroundColor: '#dc2626',
    borderRadius: 4,
  },
  cardFooter: {
    flexDirection: 'row',
    alignItems: 'center',
    marginTop: 12,
    paddingTop: 8,
    borderTopWidth: StyleSheet.hairlineWidth,
    borderTopColor: 'rgba(0,0,0,0.06)',
  },
  footerDate: {
    fontSize: 11,
  },
  modalBackdrop: {
    flex: 1,
    backgroundColor: 'rgba(0,0,0,0.6)',
    justifyContent: 'center',
    alignItems: 'center',
    padding: 20,
  },
  modalCard: {
    width: '100%',
    maxHeight: '90%',
    borderRadius: 16,
    padding: 20,
  },
  modalHeaderRow: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    marginBottom: 16,
  },
  modalHeading: {
    fontSize: 16,
    fontWeight: '800',
  },
  fieldLabel: {
    fontSize: 12,
    fontWeight: '700',
    marginBottom: 6,
  },
  questionInput: {
    borderRadius: 10,
    padding: 12,
    fontSize: 14,
    textAlignVertical: 'top',
    marginBottom: 14,
    minHeight: 70,
  },
  optionsHeaderRow: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    marginBottom: 8,
  },
  addOptionSmallBtn: {
    flexDirection: 'row',
    alignItems: 'center',
  },
  addOptionSmallText: {
    color: '#dc2626',
    fontSize: 12,
    fontWeight: '700',
  },
  optionInputRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 8,
    marginBottom: 8,
  },
  optionInput: {
    flex: 1,
    borderRadius: 8,
    paddingHorizontal: 12,
    paddingVertical: 9,
    fontSize: 13,
  },
  removeOptionBtn: {
    padding: 6,
  },
  statusToggleContainer: {
    flexDirection: 'row',
    gap: 8,
    marginTop: 4,
    marginBottom: 16,
  },
  statusSelectBtn: {
    flex: 1,
    paddingVertical: 9,
    borderRadius: 8,
    borderWidth: 1,
    borderColor: 'rgba(0,0,0,0.1)',
    alignItems: 'center',
  },
  statusSelectBtnActive: {
    backgroundColor: '#ecfdf5',
    borderColor: '#10b981',
  },
  statusSelectBtnClosed: {
    backgroundColor: '#fef2f2',
    borderColor: '#ef4444',
  },
  statusSelectText: {
    fontSize: 11,
    fontWeight: '600',
    color: '#6b7280',
  },
  statusSelectTextActive: {
    color: '#065f46',
    fontWeight: '700',
  },
  statusSelectTextClosed: {
    color: '#991b1b',
    fontWeight: '700',
  },
  modalBottomActions: {
    flexDirection: 'row',
    gap: 10,
    marginTop: 10,
  },
  cancelBtn: {
    flex: 1,
    paddingVertical: 11,
    borderRadius: 10,
    alignItems: 'center',
  },
  cancelBtnText: {
    fontSize: 13,
    fontWeight: '600',
  },
  submitBtn: {
    flex: 1.5,
    flexDirection: 'row',
    paddingVertical: 11,
    borderRadius: 10,
    backgroundColor: '#dc2626',
    alignItems: 'center',
    justifyContent: 'center',
  },
  submitBtnText: {
    color: '#fff',
    fontSize: 13,
    fontWeight: '700',
  },
  btnDisabled: {
    opacity: 0.6,
  },
});
