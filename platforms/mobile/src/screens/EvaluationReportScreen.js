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
  const overallScore = report?.overall_score || 86.8;
  const faceScore = report?.face_score || 88.5;
  const voiceScore = report?.voice_score || 85.0;
  const durationSeconds = report?.duration_seconds || 120;
  const aiConclusion = report?.ai_conclusion || 'Latihan selesai dengan artikulasi yang sangat baik.';
  const feedbackNotes = report?.feedback_notes || {};

  const strengths = feedbackNotes.strengths || [
    'Artikulasi kalimat sangat jelas dan terstruktur dengan metode STAR.',
    'Kontak mata konsisten menatap kamera depan saat menjawab pertanyaan kritis.',
  ];

  const improvements = feedbackNotes.improvements || [
    'Kurangi jeda hening saat beralih antar poin penjelasan.',
    'Tingkatkan variasi dinamika intonasi pada bagian kesimpulan.',
  ];

  const verificationUrl = report?.verification_url || `http://10.245.84.82/VOIC-NEW/public/verify/${report?.id || 1}`;

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

  return (
    <View style={styles.container}>
      {/* Top Header */}
      <View style={styles.header}>
        <View>
          <Text style={styles.headerTitle}>Rapor Evaluasi Latihan</Text>
          <Text style={styles.headerSubtitle}>Hasil Analisis Sesi AI & Visual Telemetry</Text>
        </View>
        <TouchableOpacity onPress={onBackToHome} style={styles.closeButton}>
          <Feather name="x" size={20} color="#FFFFFF" />
        </TouchableOpacity>
      </View>

      <ScrollView contentContainerStyle={styles.scrollContent}>
        {/* Big Overall Score Hero Card (Web Executive Navy) */}
        <LinearGradient
          colors={colors.heroCardGradient}
          style={styles.scoreHeroCard}
          start={{ x: 0, y: 0 }}
          end={{ x: 1, y: 1 }}
        >
          <View style={styles.heroGlowOrb} />
          <View style={styles.scoreBadgePill}>
            <View style={[styles.scoreBadgeDot, { backgroundColor: getScoreColor(overallScore) }]} />
            <Text style={styles.scoreHeroLabel}>SKOR AKHIR KOMPETENSI</Text>
          </View>

          <Text style={[styles.scoreHeroValue, { color: getScoreColor(overallScore) }]}>
            {overallScore.toFixed(1)}
          </Text>
          <Text style={styles.scoreHeroGrade}>
            {overallScore >= 80 ? 'Luar Biasa! Kompeten 🏆' : 'Cukup Baik & Berprospek'}
          </Text>

          {/* Sub-Metric Columns */}
          <View style={styles.miniScoresRow}>
            <View style={styles.miniScoreItem}>
              <View style={styles.miniScoreIconWrapper}>
                <Ionicons name="eye" size={16} color={colors.accentCyan} />
              </View>
              <View style={{ marginLeft: 8 }}>
                <Text style={styles.miniScoreLabel}>Skor Wajah & Kontak</Text>
                <Text style={styles.miniScoreValue}>{Math.round(faceScore)}/100</Text>
              </View>
            </View>

            <View style={styles.miniScoreDivider} />

            <View style={styles.miniScoreItem}>
              <View style={styles.miniScoreIconWrapper}>
                <MaterialCommunityIcons name="waveform" size={16} color={colors.primaryLight} />
              </View>
              <View style={{ marginLeft: 8 }}>
                <Text style={styles.miniScoreLabel}>Skor Vokal & Artikulasi</Text>
                <Text style={styles.miniScoreValue}>{Math.round(voiceScore)}/100</Text>
              </View>
            </View>
          </View>
        </LinearGradient>

        {/* Metadata Chips */}
        <View style={styles.metaRow}>
          <View style={styles.metaBox}>
            <Feather name="clock" size={16} color={colors.accentCyan} />
            <View style={{ marginLeft: 8, flex: 1 }}>
              <Text style={styles.metaLabel}>Durasi Latihan</Text>
              <Text style={styles.metaValue}>{formatDuration(durationSeconds)}</Text>
            </View>
          </View>
          <View style={styles.metaBox}>
            <Feather name="user-check" size={16} color={colors.primaryLight} />
            <View style={{ marginLeft: 8, flex: 1 }}>
              <Text style={styles.metaLabel}>Karakter Penguji</Text>
              <Text style={styles.metaValue} numberOfLines={1}>{role?.name || 'AI Coach'}</Text>
            </View>
          </View>
        </View>

        {/* AI Conclusion */}
        <View style={styles.sectionCard}>
          <View style={styles.sectionTitleRow}>
            <MaterialCommunityIcons name="star-shooting" size={18} color={colors.accentCyan} />
            <Text style={styles.sectionTitle}>Kesimpulan AI Coach</Text>
          </View>
          <Text style={styles.conclusionText}>{aiConclusion}</Text>
        </View>

        {/* Strengths */}
        {strengths.length > 0 && (
          <View style={styles.feedbackSection}>
            <Text style={styles.feedbackSectionTitle}>Kekuatan Komunikasi Anda 💪</Text>
            {strengths.map((str, idx) => (
              <View key={idx} style={styles.bulletRow}>
                <Ionicons name="checkmark-circle" size={16} color={colors.success} style={{ marginTop: 2 }} />
                <Text style={styles.bulletText}>{str}</Text>
              </View>
            ))}
          </View>
        )}

        {/* Improvements */}
        {improvements.length > 0 && (
          <View style={styles.feedbackSection}>
            <Text style={styles.feedbackSectionTitle}>Saran Perbaikan Berikutnya 🎯</Text>
            {improvements.map((imp, idx) => (
              <View key={idx} style={styles.bulletRow}>
                <Ionicons name="bulb-outline" size={16} color={colors.warning} style={{ marginTop: 2 }} />
                <Text style={styles.bulletText}>{imp}</Text>
              </View>
            ))}
          </View>
        )}

        {/* Digital Certificate QR Code */}
        <View style={styles.certificateCard}>
          <View style={styles.certBadge}>
            <Feather name="shield" size={13} color={colors.accentCyan} style={{ marginRight: 5 }} />
            <Text style={styles.certBadgeText}>VERIFIED CREDENTIAL</Text>
          </View>
          <Text style={styles.certTitle}>Sertifikat Digital Terverifikasi</Text>
          <Text style={styles.certSubtitle}>
            Pindai QR code ini untuk verifikasi bukti kelayakan & kompetensi digital latihan Anda di web browser.
          </Text>
          <View style={styles.qrContainer}>
            <QRCode
              value={verificationUrl}
              size={130}
              color="#070B14"
              backgroundColor="#FFFFFF"
            />
          </View>
        </View>

        {/* Back to Home CTA Button */}
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
  header: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    paddingHorizontal: 20,
    paddingTop: 46,
    paddingBottom: 14,
    backgroundColor: colors.surface,
    borderBottomWidth: 1,
    borderBottomColor: colors.cardBorderSubtle,
  },
  headerTitle: {
    fontSize: 17,
    fontWeight: 'bold',
    color: '#FFFFFF',
  },
  headerSubtitle: {
    fontSize: 11,
    color: colors.textSecondary,
    marginTop: 2,
  },
  closeButton: {
    width: 36,
    height: 36,
    borderRadius: 18,
    backgroundColor: colors.card,
    borderWidth: 1,
    borderColor: colors.cardBorderSubtle,
    justifyContent: 'center',
    alignItems: 'center',
  },
  scrollContent: {
    padding: 20,
  },
  scoreHeroCard: {
    padding: 24,
    borderRadius: 20,
    borderWidth: 1.5,
    borderColor: colors.cardBorder,
    alignItems: 'center',
    marginBottom: 20,
    overflow: 'hidden',
    shadowColor: colors.primary,
    shadowOffset: { width: 0, height: 8 },
    shadowOpacity: 0.3,
    shadowRadius: 20,
    elevation: 8,
  },
  heroGlowOrb: {
    position: 'absolute',
    top: -40,
    width: 160,
    height: 160,
    borderRadius: 80,
    backgroundColor: 'rgba(37, 99, 235, 0.2)',
  },
  scoreBadgePill: {
    flexDirection: 'row',
    alignItems: 'center',
    paddingHorizontal: 10,
    paddingVertical: 4,
    borderRadius: 20,
    backgroundColor: 'rgba(37, 99, 235, 0.15)',
    borderWidth: 1,
    borderColor: 'rgba(59, 130, 246, 0.3)',
    marginBottom: 8,
  },
  scoreBadgeDot: {
    width: 6,
    height: 6,
    borderRadius: 3,
    marginRight: 6,
  },
  scoreHeroLabel: {
    fontSize: 10,
    fontWeight: '800',
    color: colors.accentCyan,
    letterSpacing: 1,
  },
  scoreHeroValue: {
    fontSize: 52,
    fontWeight: '900',
    letterSpacing: -1,
  },
  scoreHeroGrade: {
    fontSize: 14,
    color: '#FFFFFF',
    fontWeight: '600',
    marginBottom: 20,
  },
  miniScoresRow: {
    flexDirection: 'row',
    width: '100%',
    paddingTop: 16,
    borderTopWidth: 1,
    borderTopColor: colors.cardBorderSubtle,
    justifyContent: 'space-around',
  },
  miniScoreItem: {
    flexDirection: 'row',
    alignItems: 'center',
  },
  miniScoreIconWrapper: {
    width: 32,
    height: 32,
    borderRadius: 16,
    backgroundColor: colors.surface,
    borderWidth: 1,
    borderColor: colors.cardBorderSubtle,
    justifyContent: 'center',
    alignItems: 'center',
  },
  miniScoreDivider: {
    width: 1,
    backgroundColor: colors.cardBorderSubtle,
  },
  miniScoreLabel: {
    fontSize: 10,
    color: colors.textSecondary,
  },
  miniScoreValue: {
    fontSize: 13,
    fontWeight: 'bold',
    color: '#FFFFFF',
    marginTop: 2,
  },
  metaRow: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    marginBottom: 20,
  },
  metaBox: {
    flex: 1,
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: colors.card,
    padding: 12,
    borderRadius: 14,
    borderWidth: 1,
    borderColor: colors.cardBorderSubtle,
    marginHorizontal: 4,
  },
  metaLabel: {
    fontSize: 10,
    color: colors.textSecondary,
  },
  metaValue: {
    fontSize: 12,
    fontWeight: 'bold',
    color: '#FFFFFF',
    marginTop: 2,
  },
  sectionCard: {
    backgroundColor: colors.card,
    padding: 18,
    borderRadius: 16,
    borderWidth: 1,
    borderColor: colors.cardBorderSubtle,
    marginBottom: 20,
  },
  sectionTitleRow: {
    flexDirection: 'row',
    alignItems: 'center',
    marginBottom: 8,
  },
  sectionTitle: {
    fontSize: 14,
    fontWeight: 'bold',
    color: '#FFFFFF',
    marginLeft: 6,
  },
  conclusionText: {
    fontSize: 13,
    color: colors.textSecondary,
    lineHeight: 20,
  },
  feedbackSection: {
    marginBottom: 20,
  },
  feedbackSectionTitle: {
    fontSize: 14,
    fontWeight: 'bold',
    color: '#FFFFFF',
    marginBottom: 10,
  },
  bulletRow: {
    flexDirection: 'row',
    marginBottom: 8,
  },
  bulletText: {
    fontSize: 12,
    color: colors.textSecondary,
    lineHeight: 18,
    marginLeft: 8,
    flex: 1,
  },
  certificateCard: {
    backgroundColor: colors.card,
    padding: 20,
    borderRadius: 18,
    borderWidth: 1.5,
    borderColor: colors.cardBorder,
    alignItems: 'center',
    marginBottom: 24,
    shadowColor: colors.primary,
    shadowOffset: { width: 0, height: 6 },
    shadowOpacity: 0.25,
    shadowRadius: 16,
    elevation: 6,
  },
  certBadge: {
    flexDirection: 'row',
    alignItems: 'center',
    paddingHorizontal: 9,
    paddingVertical: 3,
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
    fontSize: 15,
    fontWeight: 'bold',
    color: '#FFFFFF',
  },
  certSubtitle: {
    fontSize: 11,
    color: colors.textSecondary,
    textAlign: 'center',
    marginTop: 4,
    marginBottom: 16,
    lineHeight: 16,
  },
  qrContainer: {
    padding: 12,
    backgroundColor: '#FFFFFF',
    borderRadius: 14,
  },
  homeButton: {
    borderRadius: 14,
    overflow: 'hidden',
    shadowColor: colors.primary,
    shadowOffset: { width: 0, height: 6 },
    shadowOpacity: 0.45,
    shadowRadius: 16,
    elevation: 8,
  },
  homeGradient: {
    height: 52,
    justifyContent: 'center',
    alignItems: 'center',
  },
  homeButtonText: {
    color: '#FFFFFF',
    fontSize: 15,
    fontWeight: 'bold',
    letterSpacing: 0.5,
  },
});
