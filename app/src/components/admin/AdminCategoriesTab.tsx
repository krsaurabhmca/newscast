import React, { useState } from 'react';
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
} from 'react-native';
import {
  Plus,
  Pencil,
  Trash2,
  Layers,
  FileText,
  X,
  Check,
} from '@/components/ui/icons';
import { CategoryItem, saveCategory, deleteCategory } from '@/utils/api';
import { Spacing } from '@/constants/theme';
import { useTheme } from '@/hooks/use-theme';

interface AdminCategoriesTabProps {
  categories: CategoryItem[];
  portalUrl: string;
  onRefresh: () => void;
}

const PRESET_COLORS = [
  '#dc2626',
  '#2563eb',
  '#16a34a',
  '#d97706',
  '#7c3aed',
  '#db2777',
  '#0d9488',
  '#475569',
];

export function AdminCategoriesTab({
  categories,
  portalUrl,
  onRefresh,
}: AdminCategoriesTabProps) {
  const theme = useTheme();

  const [modalVisible, setModalVisible] = useState(false);
  const [editingCategory, setEditingCategory] = useState<CategoryItem | null>(null);

  const [name, setName] = useState('');
  const [slug, setSlug] = useState('');
  const [color, setColor] = useState('#2563eb');
  const [status, setStatus] = useState<'active' | 'disabled'>('active');
  const [saving, setSaving] = useState(false);
  const [actionInProgress, setActionInProgress] = useState<number | null>(null);

  const handleOpenAdd = () => {
    setEditingCategory(null);
    setName('');
    setSlug('');
    setColor('#2563eb');
    setStatus('active');
    setModalVisible(true);
  };

  const handleOpenEdit = (cat: CategoryItem) => {
    setEditingCategory(cat);
    setName(cat.name);
    setSlug(cat.slug);
    setColor(cat.color || '#2563eb');
    setStatus(cat.status || 'active');
    setModalVisible(true);
  };

  const handleSave = async () => {
    if (!name.trim()) {
      Alert.alert('Required', 'Please enter a category name');
      return;
    }

    setSaving(true);
    try {
      await saveCategory(portalUrl, {
        id: editingCategory?.id || 0,
        name: name.trim(),
        slug: slug.trim(),
        color,
        status,
      });
      setModalVisible(false);
      onRefresh();
    } catch (err: any) {
      Alert.alert('Error', err.message || 'Failed to save category');
    } finally {
      setSaving(false);
    }
  };

  const handleDelete = (cat: CategoryItem) => {
    Alert.alert(
      'Delete Category',
      `Are you sure you want to delete "${cat.name}"? Articles under it will be unassigned.`,
      [
        { text: 'Cancel', style: 'cancel' },
        {
          text: 'Delete',
          style: 'destructive',
          onPress: async () => {
            setActionInProgress(cat.id);
            try {
              await deleteCategory(portalUrl, cat.id);
              onRefresh();
            } catch (err: any) {
              Alert.alert('Error', err.message || 'Failed to delete category');
            } finally {
              setActionInProgress(null);
            }
          },
        },
      ]
    );
  };

  return (
    <View style={styles.container}>
      {/* Header Bar */}
      <View
        style={[
          styles.headerBar,
          { backgroundColor: theme.backgroundElement, borderBottomColor: 'rgba(0,0,0,0.06)' },
        ]}>
        <View style={styles.headerInfo}>
          <View style={styles.titleRow}>
            <Layers size={16} color={theme.text} strokeWidth={2.2} />
            <Text style={[styles.headerTitle, { color: theme.text }]}>
              Categories ({categories.length})
            </Text>
          </View>
          <Text style={[styles.headerSub, { color: theme.textSecondary }]}>
            Sections and editorial taxonomies
          </Text>
        </View>

        <TouchableOpacity style={styles.addBtn} onPress={handleOpenAdd}>
          <Plus size={14} color="#fff" strokeWidth={2.5} style={{ marginRight: 4 }} />
          <Text style={styles.addBtnText}>Add Category</Text>
        </TouchableOpacity>
      </View>

      {/* Grid */}
      <ScrollView contentContainerStyle={styles.scrollList}>
        <View style={styles.grid}>
          {categories.map((cat) => {
            const inAction = actionInProgress === cat.id;
            return (
              <View
                key={cat.id}
                style={[
                  styles.catCard,
                  { backgroundColor: theme.backgroundElement, borderColor: 'rgba(0,0,0,0.06)' },
                ]}>
                <View style={styles.cardTop}>
                  <View style={[styles.colorBar, { backgroundColor: cat.color || '#2563eb' }]} />
                  <View
                    style={[
                      styles.statusPill,
                      cat.status === 'active' ? styles.statusActive : styles.statusDisabled,
                    ]}>
                    <Text
                      style={[
                        styles.statusPillText,
                        { color: cat.status === 'active' ? '#15803d' : '#b91c1c' },
                      ]}>
                      {cat.status.toUpperCase()}
                    </Text>
                  </View>
                </View>

                <Text style={[styles.catName, { color: theme.text }]} numberOfLines={1}>
                  {cat.name}
                </Text>

                <View style={styles.postsBadge}>
                  <FileText size={12} color={theme.textSecondary} style={{ marginRight: 4 }} />
                  <Text style={[styles.postsBadgeText, { color: theme.textSecondary }]}>
                    {cat.post_count ?? 0} articles
                  </Text>
                </View>

                {/* Actions */}
                <View style={styles.cardActions}>
                  <TouchableOpacity
                    style={[styles.cardBtn, { backgroundColor: theme.backgroundSelected }]}
                    onPress={() => handleOpenEdit(cat)}>
                    <Pencil size={11} color={theme.text} style={{ marginRight: 4 }} />
                    <Text style={[styles.btnText, { color: theme.text }]}>Edit</Text>
                  </TouchableOpacity>

                  <TouchableOpacity
                    style={[styles.cardBtn, styles.deleteBtn, inAction && styles.btnDisabled]}
                    onPress={() => handleDelete(cat)}
                    disabled={inAction}>
                    <Trash2 size={12} color="#dc2626" />
                  </TouchableOpacity>
                </View>
              </View>
            );
          })}
        </View>
      </ScrollView>

      {/* Modal */}
      <Modal visible={modalVisible} transparent animationType="fade" onRequestClose={() => setModalVisible(false)}>
        <View style={styles.modalBackdrop}>
          <View style={[styles.modalBox, { backgroundColor: theme.background }]}>
            <View style={styles.modalTitleRow}>
              <Text style={[styles.modalHeading, { color: theme.text }]}>
                {editingCategory ? 'Edit Category' : 'Create Category'}
              </Text>
              <TouchableOpacity onPress={() => setModalVisible(false)}>
                <X size={18} color={theme.textSecondary} />
              </TouchableOpacity>
            </View>

            <Text style={[styles.fieldLabel, { color: theme.text }]}>Category Name *</Text>
            <TextInput
              style={[styles.input, { backgroundColor: theme.backgroundElement, color: theme.text }]}
              placeholder="e.g. National, Technology, Business"
              placeholderTextColor={theme.textSecondary}
              value={name}
              onChangeText={setName}
            />

            {/* Color Palette */}
            <Text style={[styles.fieldLabel, { color: theme.text }]}>Badge Color</Text>
            <View style={styles.colorPalette}>
              {PRESET_COLORS.map((clr) => {
                const isSelected = color === clr;
                return (
                  <TouchableOpacity
                    key={clr}
                    style={[
                      styles.colorDot,
                      { backgroundColor: clr },
                      isSelected && styles.colorDotSelected,
                    ]}
                    onPress={() => setColor(clr)}
                  />
                );
              })}
            </View>

            {/* Status */}
            <Text style={[styles.fieldLabel, { color: theme.text }]}>Status</Text>
            <View style={styles.statusToggleRow}>
              <TouchableOpacity
                style={[
                  styles.statusChoiceBtn,
                  status === 'active' && styles.statusChoiceActive,
                ]}
                onPress={() => setStatus('active')}>
                <Text
                  style={[
                    styles.statusChoiceText,
                    status === 'active' && { color: '#fff' },
                  ]}>
                  Active
                </Text>
              </TouchableOpacity>
              <TouchableOpacity
                style={[
                  styles.statusChoiceBtn,
                  status === 'disabled' && styles.statusChoiceDisabled,
                ]}
                onPress={() => setStatus('disabled')}>
                <Text
                  style={[
                    styles.statusChoiceText,
                    status === 'disabled' && { color: '#fff' },
                  ]}>
                  Disabled
                </Text>
              </TouchableOpacity>
            </View>

            {/* Modal Buttons */}
            <View style={styles.modalButtons}>
              <TouchableOpacity
                style={styles.cancelBtn}
                onPress={() => setModalVisible(false)}
                disabled={saving}>
                <Text style={[styles.cancelBtnText, { color: theme.textSecondary }]}>Cancel</Text>
              </TouchableOpacity>

              <TouchableOpacity
                style={[styles.saveBtn, saving && styles.btnDisabled]}
                onPress={handleSave}
                disabled={saving}>
                {saving ? (
                  <ActivityIndicator size="small" color="#fff" />
                ) : (
                  <View style={{ flexDirection: 'row', alignItems: 'center' }}>
                    <Check size={14} color="#fff" style={{ marginRight: 4 }} />
                    <Text style={styles.saveBtnText}>Save Category</Text>
                  </View>
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
  addBtn: {
    backgroundColor: '#dc2626',
    flexDirection: 'row',
    alignItems: 'center',
    paddingHorizontal: 12,
    paddingVertical: 7,
    borderRadius: 8,
  },
  addBtnText: {
    color: '#fff',
    fontSize: 12,
    fontWeight: '700',
  },
  scrollList: {
    padding: Spacing.three,
    paddingBottom: 90,
  },
  grid: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: 10,
  },
  catCard: {
    flexBasis: '47%',
    flexGrow: 1,
    borderRadius: 14,
    padding: 12,
    minWidth: 145,
    borderWidth: 1,
  },
  cardTop: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    marginBottom: 8,
  },
  colorBar: {
    width: 24,
    height: 6,
    borderRadius: 3,
  },
  statusPill: {
    paddingHorizontal: 6,
    paddingVertical: 2,
    borderRadius: 4,
  },
  statusActive: {
    backgroundColor: '#dcfce7',
  },
  statusDisabled: {
    backgroundColor: '#fee2e2',
  },
  statusPillText: {
    fontSize: 9,
    fontWeight: '800',
    letterSpacing: 0.3,
  },
  catName: {
    fontSize: 14,
    fontWeight: '700',
  },
  catSlug: {
    fontSize: 11,
    marginTop: 2,
  },
  postsBadge: {
    marginTop: 8,
    flexDirection: 'row',
    alignItems: 'center',
  },
  postsBadgeText: {
    fontSize: 11,
    fontWeight: '500',
  },
  cardActions: {
    flexDirection: 'row',
    gap: 6,
    marginTop: 10,
  },
  cardBtn: {
    flex: 1,
    flexDirection: 'row',
    paddingVertical: 6,
    alignItems: 'center',
    justifyContent: 'center',
    borderRadius: 6,
  },
  btnText: {
    fontSize: 11,
    fontWeight: '600',
  },
  deleteBtn: {
    backgroundColor: '#fee2e2',
    flex: 0.4,
  },
  modalBackdrop: {
    flex: 1,
    backgroundColor: 'rgba(0,0,0,0.5)',
    justifyContent: 'center',
    padding: Spacing.four,
  },
  modalBox: {
    borderRadius: 16,
    padding: Spacing.four,
    maxWidth: 440,
    width: '100%',
    alignSelf: 'center',
  },
  modalTitleRow: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    marginBottom: 10,
  },
  modalHeading: {
    fontSize: 16,
    fontWeight: '800',
  },
  fieldLabel: {
    fontSize: 12,
    fontWeight: '700',
    marginTop: 10,
    marginBottom: 4,
  },
  input: {
    borderRadius: 10,
    paddingHorizontal: 12,
    paddingVertical: 9,
    fontSize: 13,
  },
  colorPalette: {
    flexDirection: 'row',
    gap: 8,
    marginTop: 6,
  },
  colorDot: {
    width: 26,
    height: 26,
    borderRadius: 13,
  },
  colorDotSelected: {
    borderWidth: 2.5,
    borderColor: '#0f172a',
  },
  statusToggleRow: {
    flexDirection: 'row',
    gap: 8,
    marginTop: 6,
  },
  statusChoiceBtn: {
    flex: 1,
    paddingVertical: 8,
    borderRadius: 8,
    alignItems: 'center',
    backgroundColor: 'rgba(0,0,0,0.05)',
  },
  statusChoiceActive: {
    backgroundColor: '#16a34a',
  },
  statusChoiceDisabled: {
    backgroundColor: '#dc2626',
  },
  statusChoiceText: {
    fontSize: 12,
    fontWeight: '700',
  },
  modalButtons: {
    flexDirection: 'row',
    gap: 10,
    marginTop: Spacing.four,
  },
  cancelBtn: {
    flex: 1,
    paddingVertical: 11,
    borderRadius: 8,
    alignItems: 'center',
  },
  cancelBtnText: {
    fontWeight: '700',
    fontSize: 13,
  },
  saveBtn: {
    flex: 1,
    backgroundColor: '#dc2626',
    paddingVertical: 11,
    borderRadius: 8,
    alignItems: 'center',
    justifyContent: 'center',
  },
  saveBtnText: {
    color: '#fff',
    fontWeight: '700',
    fontSize: 13,
  },
  btnDisabled: {
    opacity: 0.5,
  },
});
