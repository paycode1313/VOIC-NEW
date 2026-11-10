import React, { useCallback, useEffect, useState } from 'react';
import { ActivityIndicator, RefreshControl, ScrollView, StyleSheet, Text, TouchableOpacity, View } from 'react-native';
import { Feather } from '@expo/vector-icons';
import { useSafeAreaInsets } from 'react-native-safe-area-context';
import { colors } from '../constants/theme';
import { apiService } from '../services/apiService';
import VoicLogo from '../components/VoicLogo';

export default function HistoryScreen() {
  const insets = useSafeAreaInsets();
  const [sessions, setSessions] = useState([]);
  const [isLoading, setIsLoading] = useState(true);
  const [error, setError] = useState('');

  const loadHistory = useCallback(async () => {
    setIsLoading(true);
    setError('');
    try {
      const response = await apiService.getPracticeHistory();
      if (!response.success) {
        throw new Error(response.message || 'Riwayat belum dapat dimuat.');
      }
      setSessions(response.sessions?.data || []);
    } catch (loadError) {
      setError(loadError.message || 'Periksa koneksi, lalu coba lagi.');
    } finally {
      setIsLoading(false);
    }
  }, []);

  useEffect(() => {
    loadHistory();
  }, [loadHistory]);

  return (
    <View style={styles.container}>
      <View style={[styles.header, { paddingTop: Math.max(insets.top, 8) }]}>
        <VoicLogo width={76} height={22} />
        <Text style={styles.headerLabel}>AKTIVITAS</Text>
      </View>
      <ScrollView
        contentContainerStyle={styles.content}
        refreshControl={<RefreshControl refreshing={isLoading} onRefresh={loadHistory} tintColor={colors.primaryLight} />}
      >
        <Text style={styles.eyebrow}>PERFORMA ANDA</Text>
        <Text style={styles.title}>Riwayat sesi</Text>
        <Text style={styles.subtitle}>Tinjau latihan dan hasil evaluasi sebelumnya.</Text>

        {isLoading && sessions.length === 0 ? (
          <View style={styles.messageState}>
            <ActivityIndicator color={colors.primaryLight} />
            <Text style={styles.stateText}>Memuat riwayat sesi...</Text>
          </View>
        ) : error ? (
          <View style={styles.messageState}>
            <Feather name="wifi-off" size={22} color={colors.warning} />
            <Text style={styles.stateText}>{error}</Text>
            <TouchableOpacity onPress={loadHistory} style={styles.retryButton}>
              <Text style={styles.retryText}>Coba lagi</Text>
            </TouchableOpacity>
          </View>
        ) : sessions.length === 0 ? (
          <View style={styles.emptyState}>
            <View style={styles.emptyIcon}>
              <Feather name="activity" size={23} color={colors.primaryLight} />
            </View>
            <Text style={styles.emptyTitle}>Belum ada sesi selesai</Text>
            <Text style={styles.emptyCopy}>Sesi latihan yang sudah diselesaikan akan muncul di sini.</Text>
          </View>
        ) : (
          <View style={styles.sessionList}>
            {sessions.map((session) => {
              const roleName = session.ai_role?.name || session.aiRole?.name || 'Sesi latihan';
              const createdAt = session.created_at
                ? new Date(session.created_at).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' })
                : 'Tanggal tidak tersedia';
              const score = Number(session.overall_score || 0);

              return (
                <View key={session.id} style={styles.sessionRow}>
                  <View style={styles.sessionIcon}>
                    <Feather name="message-square" size={18} color={colors.primaryLight} />
                  </View>
                  <View style={styles.sessionInfo}>
                    <Text style={styles.sessionRole} numberOfLines={1}>{roleName}</Text>
                    <Text style={styles.sessionMeta} numberOfLines={1}>{session.scenario_type || 'Simulasi AI'} · {createdAt}</Text>
                  </View>
                  <View style={styles.scoreBlock}>
                    <Text style={styles.score}>{score.toFixed(1)}</Text>
                    <Text style={styles.scoreLabel}>SKOR</Text>
                  </View>
                </View>
              );
            })}
          </View>
        )}
      </ScrollView>
    </View>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: colors.background },
  header: {
    minHeight: 58,
    paddingHorizontal: 20,
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    backgroundColor: colors.surface,
    borderBottomWidth: 1,
    borderBottomColor: colors.cardBorderSubtle,
  },
  headerLabel: { color: colors.textMuted, fontSize: 10, fontWeight: '800', letterSpacing: 1.2 },
  content: { padding: 20, paddingBottom: 28 },
  eyebrow: { color: colors.accentCyan, fontSize: 10, fontWeight: '800', letterSpacing: 1.5, marginTop: 4 },
  title: { color: colors.textPrimary, fontSize: 25, fontWeight: '800', marginTop: 7 },
  subtitle: { color: colors.textSecondary, fontSize: 13, lineHeight: 19, marginTop: 5, marginBottom: 24 },
  sessionList: { borderTopWidth: 1, borderTopColor: colors.cardBorderSubtle },
  sessionRow: {
    minHeight: 78,
    flexDirection: 'row',
    alignItems: 'center',
    borderBottomWidth: 1,
    borderBottomColor: colors.cardBorderSubtle,
    gap: 12,
  },
  sessionIcon: { width: 40, height: 40, borderRadius: 12, backgroundColor: 'rgba(37, 99, 235, 0.14)', alignItems: 'center', justifyContent: 'center' },
  sessionInfo: { flex: 1, minWidth: 0 },
  sessionRole: { color: colors.textPrimary, fontSize: 13, fontWeight: '700' },
  sessionMeta: { color: colors.textMuted, fontSize: 11, marginTop: 5 },
  scoreBlock: { minWidth: 42, alignItems: 'flex-end' },
  score: { color: colors.success, fontSize: 17, fontWeight: '800' },
  scoreLabel: { color: colors.textMuted, fontSize: 8, fontWeight: '800', marginTop: 1 },
  messageState: { alignItems: 'center', paddingHorizontal: 24, paddingVertical: 36, gap: 12 },
  stateText: { color: colors.textSecondary, fontSize: 13, textAlign: 'center', lineHeight: 19 },
  retryButton: { paddingHorizontal: 16, paddingVertical: 10, borderRadius: 10, backgroundColor: colors.primary },
  retryText: { color: '#FFFFFF', fontSize: 12, fontWeight: '700' },
  emptyState: { alignItems: 'center', paddingHorizontal: 24, paddingVertical: 34 },
  emptyIcon: { width: 52, height: 52, borderRadius: 17, alignItems: 'center', justifyContent: 'center', backgroundColor: 'rgba(37, 99, 235, 0.14)' },
  emptyTitle: { color: colors.textPrimary, fontSize: 15, fontWeight: '700', marginTop: 14 },
  emptyCopy: { color: colors.textSecondary, fontSize: 12, lineHeight: 18, textAlign: 'center', marginTop: 6 },
});