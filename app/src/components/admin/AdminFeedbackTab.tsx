import React, { useState, useEffect } from 'react';
import {
  View,
  Text,
  TouchableOpacity,
  ScrollView,
  StyleSheet,
  ActivityIndicator,
  Alert,
} from 'react-native';
import {
  RefreshCw,
  Mail,
  Phone,
  Check,
  Trash2,
  Inbox,
  MessageSquare,
} from '@/components/ui/icons';
import { FeedbackItem, getFeedback, updateFeedback } from '@/utils/api';
import { Spacing } from '@/constants/theme';
import { useTheme } from '@/hooks/use-theme';

interface AdminFeedbackTabProps {
  portalUrl: string;
}

export function AdminFeedbackTab({ portalUrl }: AdminFeedbackTabProps) {
  const theme = useTheme();

  const [feedbackList, setFeedbackList] = useState<FeedbackItem[]>([]);
  const [loading, setLoading] = useState(true);
  const [actionInProgress, setActionInProgress] = useState<number | null>(null);

  const fetchList = async () => {
    setLoading(true);
    try {
      const data = await getFeedback(portalUrl);
      setFeedbackList(data);
    } catch (err: any) {
      Alert.alert('Error', err.message || 'Failed to fetch messages');
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchList();
  }, [portalUrl]);

  const handleMarkRead = async (id: number) => {
    setActionInProgress(id);
    try {
      await updateFeedback(portalUrl, id, 'mark_read');
      setFeedbackList((prev) =>
        prev.map((item) => (item.id === id ? { ...item, status: 'read' } : item))
      );
    } catch (err: any) {
      Alert.alert('Error', err.message || 'Failed to mark read');
    } finally {
      setActionInProgress(null);
    }
  };

  const handleDelete = (id: number) => {
    Alert.alert('Delete Message', 'Are you sure you want to delete this message?', [
      { text: 'Cancel', style: 'cancel' },
      {
        text: 'Delete',
        style: 'destructive',
        onPress: async () => {
          setActionInProgress(id);
          try {
            await updateFeedback(portalUrl, id, 'delete');
            setFeedbackList((prev) => prev.filter((item) => item.id !== id));
          } catch (err: any) {
            Alert.alert('Error', err.message || 'Failed to delete message');
          } finally {
            setActionInProgress(null);
          }
        },
      },
    ]);
  };

  return (
    <View style={styles.container}>
      <View
        style={[
          styles.headerBar,
          { backgroundColor: theme.backgroundElement, borderBottomColor: 'rgba(0,0,0,0.06)' },
        ]}>
        <View style={styles.headerInfo}>
          <View style={styles.titleRow}>
            <MessageSquare size={16} color={theme.text} strokeWidth={2.2} />
            <Text style={[styles.headerTitle, { color: theme.text }]}>
              Inquiries & Feedback ({feedbackList.length})
            </Text>
          </View>
          <Text style={[styles.headerSub, { color: theme.textSecondary }]}>
            Reader submissions from website contact forms
          </Text>
        </View>

        <TouchableOpacity style={styles.refreshBtn} onPress={fetchList}>
          <RefreshCw size={15} color={theme.text} />
        </TouchableOpacity>
      </View>

      {loading ? (
        <View style={styles.centerLoading}>
          <ActivityIndicator size="large" color="#dc2626" />
        </View>
      ) : feedbackList.length === 0 ? (
        <View style={styles.emptyContainer}>
          <Inbox size={42} color={theme.textSecondary} strokeWidth={1.5} style={{ marginBottom: 8 }} />
          <Text style={[styles.emptyTitle, { color: theme.text }]}>No reader messages</Text>
          <Text style={[styles.emptySubtitle, { color: theme.textSecondary }]}>
            Reader feedback and contact form submissions will appear here.
          </Text>
        </View>
      ) : (
        <ScrollView contentContainerStyle={styles.listContent}>
          {feedbackList.map((item) => {
            const isNew = item.status === 'new';
            const inAction = actionInProgress === item.id;
            return (
              <View
                key={item.id}
                style={[
                  styles.msgCard,
                  { backgroundColor: theme.backgroundElement, borderColor: 'rgba(0,0,0,0.06)' },
                  isNew && styles.msgCardNew,
                ]}>
                <View style={styles.cardTop}>
                  <View style={styles.senderInfo}>
                    <Text style={[styles.senderName, { color: theme.text }]}>
                      {item.name || 'Anonymous Reader'}
                    </Text>
                    {item.email ? (
                      <View style={styles.contactRow}>
                        <Mail size={12} color={theme.textSecondary} style={{ marginRight: 4 }} />
                        <Text style={[styles.senderContact, { color: theme.textSecondary }]}>
                          {item.email}
                        </Text>
                      </View>
                    ) : null}
                    {item.phone ? (
                      <View style={styles.contactRow}>
                        <Phone size={12} color={theme.textSecondary} style={{ marginRight: 4 }} />
                        <Text style={[styles.senderContact, { color: theme.textSecondary }]}>
                          {item.phone}
                        </Text>
                      </View>
                    ) : null}
                  </View>

                  <View
                    style={[
                      styles.statusPill,
                      isNew ? styles.pillNew : styles.pillRead,
                    ]}>
                    <Text
                      style={[
                        styles.statusPillText,
                        { color: isNew ? '#dc2626' : theme.textSecondary },
                      ]}>
                      {item.status.toUpperCase()}
                    </Text>
                  </View>
                </View>

                {item.subject ? (
                  <Text style={[styles.subjectText, { color: theme.text }]}>
                    {item.subject}
                  </Text>
                ) : null}

                <Text style={[styles.messageText, { color: theme.text }]}>
                  {item.message}
                </Text>

                <View style={styles.cardBottom}>
                  <Text style={[styles.dateText, { color: theme.textSecondary }]}>
                    {item.created_at}
                  </Text>

                  <View style={styles.actionBtns}>
                    {isNew ? (
                      <TouchableOpacity
                        style={[styles.actionBtn, styles.readBtn, inAction && styles.btnDisabled]}
                        onPress={() => handleMarkRead(item.id)}
                        disabled={inAction}>
                        <Check size={12} color="#15803d" strokeWidth={2.5} style={{ marginRight: 4 }} />
                        <Text style={styles.readBtnText}>Mark Read</Text>
                      </TouchableOpacity>
                    ) : null}

                    <TouchableOpacity
                      style={[styles.actionBtn, styles.delBtn, inAction && styles.btnDisabled]}
                      onPress={() => handleDelete(item.id)}
                      disabled={inAction}>
                      <Trash2 size={13} color="#dc2626" />
                    </TouchableOpacity>
                  </View>
                </View>
              </View>
            );
          })}
        </ScrollView>
      )}
    </View>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
  },
  headerBar: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    padding: Spacing.three,
    borderBottomWidth: 1,
  },
  headerInfo: {
    flex: 1,
  },
  titleRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 6,
  },
  headerTitle: {
    fontSize: 15,
    fontWeight: '800',
  },
  headerSub: {
    fontSize: 11,
    marginTop: 2,
  },
  refreshBtn: {
    padding: 8,
  },
  centerLoading: {
    flex: 1,
    justifyContent: 'center',
    alignItems: 'center',
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
  listContent: {
    padding: Spacing.three,
    gap: 10,
    paddingBottom: 90,
  },
  msgCard: {
    borderRadius: 14,
    padding: 14,
    borderWidth: 1,
  },
  msgCardNew: {
    borderLeftWidth: 4,
    borderLeftColor: '#dc2626',
  },
  cardTop: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'flex-start',
    marginBottom: 8,
  },
  senderInfo: {
    flex: 1,
  },
  senderName: {
    fontSize: 14,
    fontWeight: '700',
  },
  contactRow: {
    flexDirection: 'row',
    alignItems: 'center',
    marginTop: 2,
  },
  senderContact: {
    fontSize: 11,
    fontWeight: '500',
  },
  statusPill: {
    paddingHorizontal: 7,
    paddingVertical: 2,
    borderRadius: 5,
  },
  pillNew: {
    backgroundColor: '#fee2e2',
  },
  pillRead: {
    backgroundColor: 'rgba(0,0,0,0.06)',
  },
  statusPillText: {
    fontSize: 9,
    fontWeight: '800',
    letterSpacing: 0.3,
  },
  subjectText: {
    fontSize: 13,
    fontWeight: '700',
    marginBottom: 4,
  },
  messageText: {
    fontSize: 13,
    lineHeight: 18,
    marginBottom: 10,
  },
  cardBottom: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    borderTopWidth: 1,
    borderTopColor: 'rgba(0,0,0,0.06)',
    paddingTop: 8,
  },
  dateText: {
    fontSize: 11,
    fontWeight: '500',
  },
  actionBtns: {
    flexDirection: 'row',
    gap: 8,
  },
  actionBtn: {
    flexDirection: 'row',
    alignItems: 'center',
    paddingHorizontal: 9,
    paddingVertical: 4,
    borderRadius: 6,
  },
  readBtn: {
    backgroundColor: '#dcfce7',
  },
  readBtnText: {
    color: '#15803d',
    fontSize: 11,
    fontWeight: '700',
  },
  delBtn: {
    backgroundColor: '#fee2e2',
  },
  btnDisabled: {
    opacity: 0.5,
  },
});
