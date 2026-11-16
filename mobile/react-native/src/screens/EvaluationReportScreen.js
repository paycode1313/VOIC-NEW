import React from 'react';
import {
  View,
  Text,
  StyleSheet,
  ScrollView,
  TouchableOpacity,
} from 'react-native';
import { LinearGradient } from 'expo-linear-gradient';
import { Feather, Ionicons, MaterialCommunityIcons } from '@expo/vector-icons';
import QRCode from 'react-native-qrcode-svg';
import { colors } from '../constants/theme';

export default function EvaluationReportScreen({ report, role, onBackToHome }) {
  // Everything below comes from the server-side report; nothing is invented on the device.
  const band = report?.report || null;
  const overallScore = Number(report?.overall_score || 0);
  const faceScore = Number(report?.face_score || 0);
  const voiceScore = Number(report?.voice_score || 0);
  const durationSeconds = Number(report?.duration_seconds || 0);
  const aiConclusion = report?.ai_conclusion || 'Belum ada jawaban yang dinilai.';
  const criteria = band ? Object.values(band.criteria || {}) : [];

  const strengths = band?.strengths || [];
  const improvements = (band?.focus || []).map((item) => `${item.title}: ${item.drill}`);

  const verificationUrl = report?.share_url || report?.report_url || '';

  const formatDuration = (totalSec) => {
    const m = Math.floor(totalSec / 60);
    const s = totalSec % 60;
    return `${m > 0 ? `${m} menit ` : ''}${s} detik`;
  };

  const getScoreColor = (score) => {
    if (score >= 80) return colors.success;
    if (score >= 65) return colors.warning;
    return colors.danger;
  };

  // Derive band number for circle display
  const bandNumber = band ? Number(band.overall_band).toFixed(1) : overallScore.toFixed(1);
  const bandLevel = band?.level || (overallScore >= 80 ? 'Sangat Siap' : 'Terus Berlatih');
  const scoreColor = band
    ? (Number(band.overall_band) >= 7 ? colors.success : Number(band.overall_band) >= 5 ? colors.warning : colors.danger)
    : getScoreColor(overallScore);

  return (
    <View style={styles.container}>
      {/* Full-width gradient header */}
      <LinearGradient
        colors={['#0F172A', '#1E3A8A', '#1E40AF']}
        start={{ x: 0, y: 0 }}
        end={{ x: 1, y: 1 }}
        style={styles.header}
      >
        <View style={styles.headerAwardIconWrapper}>
          <Feather name="award" size={36} color="#FBBF24" />
        </View>
        <Text style={styles.headerTitle}>Rapor Anda</Text>
        <Text style={styles.headerSubtitle}>Sesi selesai • Evaluasi AI Lengkap</Text>
        <TouchableOpacity onPress={onBackToHome} style={styles.closeButton}>
          <Feather name="x" size={20} color="#FFFFFF" />
        </TouchableOpacity>
      </LinearGradient>

      <ScrollView contentContainerStyle={styles.scrollContent}>

        {/* Score Hero Card — large circle */}
        <LinearGradient
          colors={colors.heroCardGradient}
          style={styles.scoreHeroCard}
          start={{ x: 0, y: 0 }}
          end={{ x: 1, y: 1 }}
        >
          <View style={styles.heroGlowOrb} />

          {/* Score circle */}
          <View style={[styles.scoreCircle, { borderColor: scoreColor }]}>
            <View style={styles.scoreCircleInner}>
              <Text style={[styles.scoreCircleNumber, { color: scoreColor }]}>{bandNumber}</Text>
              <Text style={styles.scoreCircleUnit}>{band ? '/ 9.0' : '/ 100'}</Text>
            </View>
            <Text style={styles.scoreCircleLevel}>{bandLevel}</Text>
          </View>

          {/* 3 mini chips row */}
          <View style={styles.chipsRow}>
            <View style={styles.chip}>
              <Ionicons name="eye" size={13} color={colors.accentCyan} />
              <View style={styles.chipTextGroup}>
                <Text style={styles.chipLabel}>Delivery</Text>
                <Text style={[styles.chipValue, { color: scoreColor }]}>
                  {band ? Number(band.criteria?.delivery?.band || 0).toFixed(1) : Math.round(faceScore)}
                </Text>
              </View>
            </View>
            <View style={styles.chipDivider} />
            <View style={styles.chip}>
              <MaterialCommunityIcons name="waveform" size={13} color={colors.primaryLight} />
              <View style={styles.chipTextGroup}>
                <Text style={styles.chipLabel}>Kelancaran</Text>
                <Text style={[styles.chipValue, { color: colors.primaryLight }]}>
                  {band ? Number(band.criteria?.fluency?.band || 0).toFixed(1) : Math.round(voiceScore)}
                </Text>
              </View>
            </View>
            <View style={styles.chipDivider} />
            <View style={styles.chip}>
              <Ionicons name="text" size={13} color="#A78BFA" />
              <View style={styles.chipTextGroup}>
                <Text style={styles.chipLabel}>Kosakata</Text>
                <Text style={[styles.chipValue, { color: '#A78BFA' }]}>
                  {band ? Number(band.criteria?.lexical_resource?.band || 0).toFixed(1) : '—'}
                </Text>
              </View>
            </View>
          </View>
        </LinearGradient>

        {/* Metadata — 2 full cards, stacked */}
        <View style={styles.metaCard}>
          <View style={styles.metaCardIcon}>
            <Feather name="clock" size={22} color={colors.accentCyan} />
          </View>
          <View style={styles.metaCardText}>
            <Text style={styles.metaLabel}>Durasi Latihan</Text>
            <Text style={styles.metaValue}>{formatDuration(durationSeconds)}</Text>
          </View>
        </View>

        <View style={styles.metaCard}>
          <View style={styles.metaCardIcon}>
            <Feather name="user-check" size={22} color={colors.primaryLight} />
          </View>
          <View style={styles.metaCardText}>
            <Text style={styles.metaLabel}>Karakter Penguji</Text>
            <Text style={styles.metaValue} numberOfLines={1}>{role?.name || 'AI Coach'}</Text>
          </View>
        </View>

        {/* AI Conclusion — left blue border */}
        <View style={styles.conclusionCard}>
          <View style={styles.sectionTitleRow}>
            <MaterialCommunityIcons name="star-shooting" size={18} color={colors.accentCyan} />
            <Text style={styles.sectionTitle}>Kesimpulan AI Coach</Text>
          </View>
          <Text style={styles.conclusionText}>{aiConclusion}</Text>
          {criteria.map((item) => (
            <Text key={item.label} style={styles.conclusionCriterionText}>
              {`• ${item.label}: ${Number(item.band).toFixed(1)} / 9`}
            </Text>
          ))}
        </View>

        {/* Strengths */}
        {strengths.length > 0 && (
          <View style={styles.strengthsCard}>
            <Text style={styles.feedbackSectionTitle}>💪 Kekuatan Komunikasi Anda</Text>
            {strengths.map((str, idx) => (
              <View key={idx} style={styles.bulletRow}>
                <Ionicons name="checkmark-circle" size={18} color={colors.success} style={{ marginTop: 1 }} />
                <Text style={styles.bulletTextStrength}>{str}</Text>
              </View>
            ))}
          </View>
        )}

        {/* Improvements */}
        {improvements.length > 0 && (
          <View style={styles.improvementsCard}>
            <Text style={styles.feedbackSectionTitle}>🎯 Saran Perbaikan Berikutnya</Text>
            {improvements.map((imp, idx) => (
              <View key={idx} style={styles.bulletRow}>
                <Ionicons name="bulb-outline" size={18} color={colors.warning} style={{ marginTop: 1 }} />
                <Text style={styles.bulletTextImprovement}>{imp}</Text>
              </View>
            ))}
          </View>
        )}

        {/* Certificate Card — luxury navy gradient */}
        <LinearGradient
          colors={['#0F172A', '#1E3A8A', '#0F172A']}
          start={{ x: 0, y: 0 }}
          end={{ x: 1, y: 1 }}
          style={styles.certificateCard}
        >
          <View style={styles.certBadge}>
            <Feather name="shield" size={13} color={colors.accentCyan} style={{ marginRight: 5 }} />
            <Text style={styles.certBadgeText}>VERIFIED CREDENTIAL</Text>
          </View>
          <Text style={styles.certTitle}>Sertifikat Digital Terverifikasi</Text>
          <Text style={styles.certSubtitle}>
            Pindai QR code ini untuk verifikasi bukti kelayakan &amp; kompetensi digital latihan Anda di web browser.
          </Text>

          {/* QR in white frame with shadow */}
          <View style={styles.qrFrame}>
            {verificationUrl ? (
              <QRCode
                value={verificationUrl}
                size={130}
                color="#070B14"
                backgroundColor="#FFFFFF"
              />
            ) : null}
          </View>

          {/* Watermark text */}
          <View style={styles.certWatermarkRow}>
            <Feather name="award" size={12} color="#FBBF24" style={{ marginRight: 5 }} />
            <Text style={styles.certWatermark}>VOIC CERTIFIED</Text>
          </View>
        </LinearGradient>

        {/* CTA Button — gradient full-width with home icon */}
        <TouchableOpacity
          style={styles.homeButton}
          onPress={onBackToHome}
          activeOpacity={0.85}
        >
          <LinearGradient
            colors={colors.buttonGradient}
            start={{ x: 0, y: 0 }}
            end={{ x: 1, y: 0 }}
            style={styles.homeGradient}
          >
            <Feather name="home" size={20} color="#FFFFFF" style={{ marginRight: 10 }} />
            <Text style={styles.homeButtonText}>Kembali ke Beranda VOIC</Text>
          </LinearGradient>
        </TouchableOpacity>

        <View style={{ height: 40 }} />
      </ScrollView>
    </View>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: colors.background,
  },

  /* ── Header ── */
  header: {
    alignItems: 'center',
    paddingTop: 52,
    paddingBottom: 24,
    paddingHorizontal: 20,
    position: 'relative',
  },
  headerAwardIconWrapper: {
    width: 72,
    height: 72,
    borderRadius: 36,
    backgroundColor: 'rgba(251, 191, 36, 0.12)',
    borderWidth: 2,
    borderColor: 'rgba(251, 191, 36, 0.35)',
    justifyContent: 'center',
    alignItems: 'center',
    marginBottom: 12,
  },
  headerTitle: {
    fontSize: 26,
    fontWeight: '900',
    color: '#FFFFFF',
    letterSpacing: 0.5,
  },
  headerSubtitle: {
    fontSize: 12,
    color: 'rgba(203, 213, 225, 0.8)',
    marginTop: 4,
    letterSpacing: 0.3,
  },
  closeButton: {
    position: 'absolute',
    top: 52,
    right: 20,
    width: 36,
    height: 36,
    borderRadius: 18,
    backgroundColor: 'rgba(255,255,255,0.1)',
    borderWidth: 1,
    borderColor: 'rgba(255,255,255,0.2)',
    justifyContent: 'center',
    alignItems: 'center',
  },

  /* ── Scroll ── */
  scrollContent: {
    padding: 20,
    paddingTop: 24,
  },

  /* ── Score Hero Card ── */
  scoreHeroCard: {
    padding: 28,
    borderRadius: 24,
    borderWidth: 1.5,
    borderColor: colors.cardBorder,
    alignItems: 'center',
    marginBottom: 16,
    overflow: 'hidden',
    shadowColor: colors.primary,
    shadowOffset: { width: 0, height: 10 },
    shadowOpacity: 0.35,
    shadowRadius: 24,
    elevation: 10,
  },
  heroGlowOrb: {
    position: 'absolute',
    top: -50,
    width: 200,
    height: 200,
    borderRadius: 100,
    backgroundColor: 'rgba(37, 99, 235, 0.18)',
  },

  /* Score circle */
  scoreCircle: {
    width: 160,
    height: 160,
    borderRadius: 80,
    borderWidth: 5,
    justifyContent: 'center',
    alignItems: 'center',
    backgroundColor: 'rgba(0,0,0,0.25)',
    marginBottom: 20,
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 4 },
    shadowOpacity: 0.4,
    shadowRadius: 12,
    elevation: 8,
  },
  scoreCircleInner: {
    flexDirection: 'row',
    alignItems: 'flex-end',
  },
  scoreCircleNumber: {
    fontSize: 48,
    fontWeight: '900',
    letterSpacing: -1,
    lineHeight: 56,
  },
  scoreCircleUnit: {
    fontSize: 14,
    color: 'rgba(255,255,255,0.5)',
    fontWeight: '600',
    marginBottom: 10,
    marginLeft: 3,
  },
  scoreCircleLevel: {
    fontSize: 12,
    color: 'rgba(255,255,255,0.7)',
    fontWeight: '600',
    marginTop: 4,
    letterSpacing: 0.5,
  },

  /* Mini chips row */
  chipsRow: {
    flexDirection: 'row',
    width: '100%',
    paddingTop: 18,
    borderTopWidth: 1,
    borderTopColor: colors.cardBorderSubtle,
    justifyContent: 'space-around',
    alignItems: 'center',
  },
  chip: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 6,
  },
  chipTextGroup: {
    marginLeft: 6,
  },
  chipLabel: {
    fontSize: 10,
    color: colors.textSecondary,
  },
  chipValue: {
    fontSize: 14,
    fontWeight: '800',
    marginTop: 1,
  },
  chipDivider: {
    width: 1,
    height: 32,
    backgroundColor: colors.cardBorderSubtle,
  },

  /* ── Metadata Cards ── */
  metaCard: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: colors.card,
    padding: 16,
    borderRadius: 16,
    borderWidth: 1,
    borderColor: colors.cardBorderSubtle,
    marginBottom: 12,
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 2 },
    shadowOpacity: 0.15,
    shadowRadius: 6,
    elevation: 3,
  },
  metaCardIcon: {
    width: 48,
    height: 48,
    borderRadius: 24,
    backgroundColor: 'rgba(37, 99, 235, 0.12)',
    borderWidth: 1,
    borderColor: 'rgba(59, 130, 246, 0.25)',
    justifyContent: 'center',
    alignItems: 'center',
    marginRight: 14,
  },
  metaCardText: {
    flex: 1,
  },
  metaLabel: {
    fontSize: 11,
    color: colors.textSecondary,
  },
  metaValue: {
    fontSize: 15,
    fontWeight: 'bold',
    color: '#FFFFFF',
    marginTop: 3,
  },

  /* ── AI Conclusion Card ── */
  conclusionCard: {
    backgroundColor: colors.card,
    padding: 18,
    borderRadius: 16,
    borderWidth: 1,
    borderColor: colors.cardBorderSubtle,
    borderLeftWidth: 4,
    borderLeftColor: '#3B82F6',
    marginBottom: 16,
  },
  sectionTitleRow: {
    flexDirection: 'row',
    alignItems: 'center',
    marginBottom: 10,
  },
  sectionTitle: {
    fontSize: 14,
    fontWeight: 'bold',
    color: '#FFFFFF',
    marginLeft: 7,
  },
  conclusionText: {
    fontSize: 14,
    color: colors.textSecondary,
    lineHeight: 22,
  },
  conclusionCriterionText: {
    fontSize: 13,
    color: colors.textSecondary,
    lineHeight: 20,
    marginTop: 4,
  },

  /* ── Strengths ── */
  strengthsCard: {
    backgroundColor: 'rgba(16, 185, 129, 0.06)',
    padding: 18,
    borderRadius: 16,
    borderWidth: 1,
    borderColor: 'rgba(16, 185, 129, 0.2)',
    marginBottom: 16,
  },

  /* ── Improvements ── */
  improvementsCard: {
    backgroundColor: 'rgba(245, 158, 11, 0.06)',
    padding: 18,
    borderRadius: 16,
    borderWidth: 1,
    borderColor: 'rgba(245, 158, 11, 0.2)',
    marginBottom: 16,
  },

  feedbackSectionTitle: {
    fontSize: 15,
    fontWeight: 'bold',
    color: '#FFFFFF',
    marginBottom: 12,
  },
  bulletRow: {
    flexDirection: 'row',
    marginBottom: 10,
    alignItems: 'flex-start',
  },
  bulletTextStrength: {
    fontSize: 14,
    color: '#D1FAE5',
    lineHeight: 20,
    marginLeft: 10,
    flex: 1,
  },
  bulletTextImprovement: {
    fontSize: 14,
    color: '#FEF3C7',
    lineHeight: 20,
    marginLeft: 10,
    flex: 1,
  },

  /* ── Certificate Card ── */
  certificateCard: {
    padding: 24,
    borderRadius: 20,
    borderWidth: 1.5,
    borderColor: 'rgba(59, 130, 246, 0.35)',
    alignItems: 'center',
    marginBottom: 20,
    shadowColor: '#1E3A8A',
    shadowOffset: { width: 0, height: 8 },
    shadowOpacity: 0.5,
    shadowRadius: 20,
    elevation: 10,
  },
  certBadge: {
    flexDirection: 'row',
    alignItems: 'center',
    paddingHorizontal: 10,
    paddingVertical: 4,
    borderRadius: 12,
    backgroundColor: 'rgba(37, 99, 235, 0.15)',
    borderWidth: 1,
    borderColor: 'rgba(59, 130, 246, 0.3)',
    marginBottom: 10,
  },
  certBadgeText: {
    fontSize: 10,
    fontWeight: '800',
    color: colors.accentCyan,
    letterSpacing: 0.8,
  },
  certTitle: {
    fontSize: 17,
    fontWeight: 'bold',
    color: '#FFFFFF',
  },
  certSubtitle: {
    fontSize: 12,
    color: 'rgba(203, 213, 225, 0.75)',
    textAlign: 'center',
    marginTop: 6,
    marginBottom: 20,
    lineHeight: 18,
  },
  qrFrame: {
    padding: 14,
    backgroundColor: '#FFFFFF',
    borderRadius: 16,
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 6 },
    shadowOpacity: 0.5,
    shadowRadius: 14,
    elevation: 10,
    marginBottom: 16,
  },
  certWatermarkRow: {
    flexDirection: 'row',
    alignItems: 'center',
  },
  certWatermark: {
    fontSize: 11,
    fontWeight: '800',
    color: '#FBBF24',
    letterSpacing: 2,
  },

  /* ── CTA Button ── */
  homeButton: {
    borderRadius: 16,
    overflow: 'hidden',
    shadowColor: colors.primary,
    shadowOffset: { width: 0, height: 6 },
    shadowOpacity: 0.5,
    shadowRadius: 18,
    elevation: 10,
  },
  homeGradient: {
    height: 58,
    flexDirection: 'row',
    justifyContent: 'center',
    alignItems: 'center',
  },
  homeButtonText: {
    color: '#FFFFFF',
    fontSize: 17,
    fontWeight: '800',
    letterSpacing: 0.5,
  },
});
