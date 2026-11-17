import React, { useCallback, useEffect, useState } from 'react';
import {
  ActivityIndicator,
  RefreshControl,
  ScrollView,
  StyleSheet,
  Text,
  TouchableOpacity,
  View,
} from 'react-native';
import { Feather } from '@expo/vector-icons';
import { useSafeAreaInsets } from 'react-native-safe-area-context';
import { colors } from '../constants/theme';
import { apiService } from '../services/apiService';
import VoicLogo from '../components/VoicLogo';

export default function HistoryScreen({ onSelectSession, onStartPracticeTab }) {
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
      setError(loadError.message || 'Periksa koneksi jaringan, lalu coba lagi.');
    } finally {
      setIsLoading(false);
    }
  }, []);

  useEffect(() => {
    loadHistory();
  }, [loadHistory]);

  return (
    <View style={styles.container}>
      <View style={[styles.header, { paddingTop: Math.max(insets.top, 12) }]}>
        <VoicLogo width={84} height={24} />
        <View style={styles.headerBadge}>
          <Text style={styles.headerLabel}>ARSIP EVALUASI</Text>
        </View>
      </View>

      <ScrollView
        contentContainerStyle={styles.content}
        showsVerticalScrollIndicator={false}
        refreshControl={
          <RefreshControl
            refreshing={isLoading}
            onRefresh={loadHistory}
            tintColor={colors.primaryLight}
          />
        }
      >
        {/* Executive Header Card */}
        <View style={styles.heroCard}>
          <Text style={styles.eyebrow}>PERFORMA & REKAM JEJAK</Text>
          <Text style={styles.title}>Riwayat Sesi Latihan</Text>
          <Text style={styles.subtitle}>
            Ketuk salah satu sesi di bawah untuk membuka rapor lengkap dan catatan per jawaban.
          </Text>
        </View>

        {isLoading && sessions.length === 0 ? (
          <View style={styles.messageState}>
            <ActivityIndicator color={colors.primaryLight} size="large" />
            <Text style={styles.stateText}>Memuat arsip evaluasi...</Text>
          </View>
        ) : error ? (
          <View style={styles.messageState}>
            <Feather name="wifi-off" size={24} color={colors.warning} />
            <Text style={styles.stateText}>{error}</Text>
            <TouchableOpacity onPress={loadHistory} style={styles.retryButton}>
              <Text style={styles.retryText}>Muat Ulang</Text>
            </TouchableOpacity>
          </View>
        ) : sessions.length === 0 ? (
          <View style={styles.emptyState}>
            <View style={styles.emptyIcon}>
              <Feather name="bar-chart-2" size={24} color={colors.accentCyan} />
            </View>
            <Text style={styles.emptyTitle}>Belum Ada Riwayat Sesi</Text>
            <Text style={styles.emptyCopy}>
              Selesaikan satu sesi simulasi bicara dengan penguji AI untuk menyimpan rapor di sini.
            </Text>
            {onStartPracticeTab && (
              <TouchableOpacity
                onPress={onStartPracticeTab}
                style={styles.startCtaButton}
                activeOpacity={0.85}
              >
                <Text style={styles.startCtaText}>Mulai Simulasi Sekarang</Text>
                <Feather name="arrow-right" size={15} color="#FFFFFF" />
              </TouchableOpacity>
            )}
          </View>
        ) : (
          <View style={styles.sessionList}>
            {sessions.map((session) => {
              const roleName = session.ai_role?.name || session.aiRole?.name || 'Penguji AI VOIC';
              const createdAt = session.created_at
                ? new Date(session.created_at).toLocaleDateString('id-ID', {
                    day: 'numeric',
                    month: 'short',
                    year: 'numeric',
                  })
                : 'Baru saja';
              const band = session.feedback_notes?.exam_report?.overall_band ?? null;
              const score = band !== null ? Number(band) : Number(session.overall_score || 0);
              const isHigh = band !== null ? score >= 7 : score >= 80;

              return (
                <TouchableOpacity
                  key={session.id}
                  style={styles.sessionCard}
                  activeOpacity={0.85}
                  onPress={() => onSelectSession && onSelectSession(session)}
                >
                  <View
                    style={[
                      styles.scoreBadge,
                      isHigh ? styles.scoreBadgeHigh : styles.scoreBadgeNormal,
                    ]}
                  >
                    <Text
                      style={[
                        styles.scoreValue,
                        isHigh ? { color: '#34D399' } : { color: colors.accentCyan },
                      ]}
                    >
                      {score.toFixed(1)}
                    </Text>
                    <Text style={styles.scoreUnit}>{band !== null ? 'BAND' : 'SKOR'}</Text>
                  </View>

                  <View style={styles.sessionInfo}>
                    <Text style={styles.sessionScenario} numberOfLines={1}>
                      {session.scenario_type || 'Simulasi AI'}
                    </Text>
                    <Text style={styles.sessionMeta} numberOfLines={1}>
                      {roleName} · {createdAt}
                    </Text>
                    {session.ai_conclusion ? (
                      <Text style={styles.sessionConclusion} numberOfLines={1}>
                        {session.ai_conclusion}
                      </Text>
                    ) : null}
                  </View>

                  <Feather name="chevron-right" size={18} color={colors.textMuted} />
                </TouchableOpacity>
              );
            })}
          </View>
        )}
        <View style={{ height: 90 }} />
      </ScrollView>
    </View>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: colors.background },
  header: {
    paddingHorizontal: 20,
    paddingBottom: 14,
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    backgroundColor: colors.surface,
    borderBottomWidth: 1,
    borderBottomColor: 'rgba(255, 255, 255, 0.06)',
  },
  headerBadge: {
    backgroundColor: 'rgba(37, 99, 235, 0.14)',
    borderWidth: 1,
    borderColor: 'rgba(59, 130, 246, 0.28)',
    paddingHorizontal: 10,
    paddingVertical: 4,
    borderRadius: 999,
  },
  headerLabel: { color: colors.accentCyan, fontSize: 10, fontWeight: '800', letterSpacing: 0.8 },
  content: { padding: 18 },
  heroCard: {
    backgroundColor: colors.card,
    borderRadius: 22,
    borderWidth: 1,
    borderColor: 'rgba(255, 255, 255, 0.08)',
    padding: 20,
    marginBottom: 18,
  },
  eyebrow: { color: colors.accentCyan, fontSize: 10, fontWeight: '800', letterSpacing: 1.2 },
  title: { color: colors.textPrimary, fontSize: 22, fontWeight: '800', marginTop: 6 },
  subtitle: { color: colors.textSecondary, fontSize: 12.5, lineHeight: 18, marginTop: 5 },
  sessionList: { gap: 12 },
  sessionCard: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: colors.card,
    borderRadius: 20,
    borderWidth: 1,
    borderColor: 'rgba(255, 255, 255, 0.08)',
    padding: 14,
    gap: 14,
  },
  scoreBadge: {
    width: 54,
    height: 54,
    borderRadius: 16,
    alignItems: 'center',
    justifyContent: 'center',
    borderWidth: 1,
  },
  scoreBadgeHigh: {
    backgroundColor: 'rgba(16, 185, 129, 0.12)',
    borderColor: 'rgba(16, 185, 129, 0.35)',
  },
  scoreBadgeNormal: {
    backgroundColor: 'rgba(37, 99, 235, 0.14)',
    borderColor: 'rgba(56, 189, 248, 0.3)',
  },
  scoreValue: { fontSize: 16, fontWeight: '800' },
  scoreUnit: { color: colors.textMuted, fontSize: 8.5, fontWeight: '800', marginTop: 2 },
  sessionInfo: { flex: 1, minWidth: 0 },
  sessionScenario: { color: '#FFFFFF', fontSize: 14.5, fontWeight: '800' },
  sessionMeta: { color: colors.textSecondary, fontSize: 11.5, marginTop: 3 },
  sessionConclusion: { color: colors.textMuted, fontSize: 11, marginTop: 4 },
  messageState: {
    alignItems: 'center',
    backgroundColor: colors.card,
    borderRadius: 22,
    borderWidth: 1,
    borderColor: 'rgba(255, 255, 255, 0.07)',
    padding: 32,
    gap: 12,
  },
  stateText: { color: colors.textSecondary, fontSize: 13, textAlign: 'center', lineHeight: 19 },
  retryButton: {
    paddingHorizontal: 18,
    paddingVertical: 10,
    borderRadius: 12,
    backgroundColor: colors.primary,
  },
  retryText: { color: '#FFFFFF', fontSize: 12.5, fontWeight: '700' },
  emptyState: {
    alignItems: 'center',
    backgroundColor: colors.card,
    borderRadius: 22,
    borderWidth: 1,
    borderColor: 'rgba(255, 255, 255, 0.08)',
    padding: 30,
  },
  emptyIcon: {
    width: 54,
    height: 54,
    borderRadius: 18,
    alignItems: 'center',
    justifyContent: 'center',
    backgroundColor: 'rgba(37, 99, 235, 0.14)',
    borderWidth: 1,
    borderColor: 'rgba(56, 189, 248, 0.25)',
  },
  emptyTitle: { color: colors.textPrimary, fontSize: 16, fontWeight: '800', marginTop: 14 },
  emptyCopy: {
    color: colors.textSecondary,
    fontSize: 12.5,
    lineHeight: 18,
    textAlign: 'center',
    marginTop: 6,
  },
  startCtaButton: {
    marginTop: 18,
    flexDirection: 'row',
    alignItems: 'center',
    gap: 8,
    backgroundColor: colors.primary,
    paddingHorizontal: 18,
    paddingVertical: 12,
    borderRadius: 14,
  },
  startCtaText: { color: '#FFFFFF', fontSize: 13, fontWeight: '800' },
});
