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
import { useSafeAreaInsets } from 'react-native-safe-area-context';
import QRCode from 'react-native-qrcode-svg';
import { colors } from '../constants/theme';

export default function EvaluationReportScreen({ report, role, onBackToHome }) {
  const insets = useSafeAreaInsets();
  const band = report?.report || report?.feedback_notes?.exam_report || null;
  const coach = report?.coach_report || report?.feedback_notes?.coach_report || null;
  const isCoach = (report?.session_type === 'coach' || Boolean(coach)) && !band;

  const overallScore = Number(report?.overall_score || 0);
  const faceScore = Number(report?.face_score || 0);
  const voiceScore = Number(report?.voice_score || 0);
  const durationSeconds = Number(report?.duration_seconds || 0);
  const aiConclusion = report?.ai_conclusion || 'Belum ada jawaban yang dinilai.';

  const criteriaMap = band?.criteria || {};
  const criteriaList = [
    { key: 'fluency', fallbackLabel: 'Kelancaran & Alur', icon: 'waveform', iconType: 'mci', color: colors.primaryLight },
    { key: 'vocabulary', fallbackLabel: 'Kosakata & Istilah', icon: 'text', iconType: 'ion', color: '#A78BFA' },
    { key: 'structure', fallbackLabel: 'Isi & Struktur', icon: 'git-branch-outline', iconType: 'ion', color: '#34D399' },
    { key: 'delivery', fallbackLabel: 'Delivery & Kepercayaan Diri', icon: 'eye', iconType: 'ion', color: colors.accentCyan },
  ].map((item) => {
    const serverItem = criteriaMap[item.key];
    return {
      ...item,
      label: serverItem?.label || item.fallbackLabel,
      band: serverItem ? Number(serverItem.band || 0) : null,
      description: serverItem?.description || '',
    };
  });

  const stats = band?.stats || coach?.stats || null;
  const answers = Array.isArray(band?.answers) ? band.answers : [];
  const fillerEntries = stats?.fillers ? Object.entries(stats.fillers) : [];

  const strengths = isCoach
    ? coach?.strengths || []
    : band?.strengths || report?.feedback_notes?.strengths || [];
  const improvements = isCoach
    ? coach?.practice || []
    : (band?.focus || []).map((item) => `${item.title}: ${item.drill}`);
  const taughtPhrases = isCoach ? coach?.phrases || [] : [];

  const verificationUrl = report?.share_url || report?.report_url || '';

  const formatDuration = (totalSec) => {
    const m = Math.floor(totalSec / 60);
    const s = totalSec % 60;
    return `${m > 0 ? `${m} menit ` : ''}${s} detik`;
  };

  const getBandColor = (val) => {
    const n = Number(val || 0);
    if (n >= 6.5) return colors.success;
    if (n >= 5.0) return colors.warning;
    return colors.danger;
  };

  const getScoreColor = (score) => {
    if (score >= 80) return colors.success;
    if (score >= 65) return colors.warning;
    return colors.danger;
  };

  const bandNumber = band ? Number(band.overall_band || 0).toFixed(1) : overallScore.toFixed(1);
  const bandLevel = band?.level || (overallScore >= 80 ? 'Sangat Siap' : overallScore >= 55 ? 'Berkembang' : 'Perlu Latihan');
  const scoreColor = band ? getBandColor(band.overall_band) : getScoreColor(overallScore);

  const renderCriterionIcon = (item) => {
    if (item.iconType === 'mci') {
      return <MaterialCommunityIcons name={item.icon} size={15} color={item.color} />;
    }
    return <Ionicons name={item.icon} size={15} color={item.color} />;
  };

  const noteBadgeColor = (type) => {
    if (type === 'good') return '#10B981';
    if (type === 'warn') return '#F59E0B';
    return '#38BDF8';
  };

  return (
    <View style={styles.container}>
      {/* Gradient Header */}
      <LinearGradient
        colors={['#0F172A', '#1E3A8A', '#1E40AF']}
        start={{ x: 0, y: 0 }}
        end={{ x: 1, y: 1 }}
        style={[styles.header, { paddingTop: Math.max(insets.top + 12, 46) }]}
      >
        <View style={styles.headerAwardIconWrapper}>
          <Feather name={isCoach ? 'message-circle' : 'award'} size={34} color="#FBBF24" />
        </View>
        <Text style={styles.headerTitle}>{isCoach ? 'Ringkasan Coaching' : 'Rapor Anda'}</Text>
        <Text style={styles.headerSubtitle}>
          {isCoach ? (coach?.coach_title || 'Latihan Santai 10 Menit') : 'Sesi selesai • Evaluasi AI Lengkap'}
        </Text>
        <TouchableOpacity
          onPress={onBackToHome}
          style={[styles.closeButton, { top: Math.max(insets.top + 12, 46) }]}
        >
          <Feather name="x" size={20} color="#FFFFFF" />
        </TouchableOpacity>
      </LinearGradient>

      <ScrollView contentContainerStyle={styles.scrollContent}>
        {/* Hero Score Card */}
        {isCoach ? (
          <LinearGradient
            colors={colors.heroCardGradient}
            style={styles.scoreHeroCard}
            start={{ x: 0, y: 0 }}
            end={{ x: 1, y: 1 }}
          >
            <View style={styles.heroGlowOrb} />
            <View style={styles.coachHeroBadge}>
              <MaterialCommunityIcons name="school-outline" size={16} color={colors.accentCyan} />
              <Text style={styles.coachHeroBadgeText}>MODE COACHING (NON-UJIAN)</Text>
            </View>
            <Text style={styles.coachHeroTitle}>{coach?.coach_title || 'Sesi Latihan Coaching'}</Text>
            <Text style={styles.coachHeroSubtitle}>
              Fokus sesi ini adalah melatih cara menjawab yang natural tanpa tekanan skor band.
            </Text>

            <View style={styles.chipsGrid}>
              <View style={styles.chipBox}>
                <Text style={styles.chipLabel}>Giliran Jawab</Text>
                <Text style={[styles.chipValue, { color: colors.accentCyan }]}>
                  {`${stats?.turns ?? 0}x`}
                </Text>
              </View>
              <View style={styles.chipBox}>
                <Text style={styles.chipLabel}>Total Kata</Text>
                <Text style={[styles.chipValue, { color: colors.primaryLight }]}>
                  {`${stats?.total_words ?? 0}`}
                </Text>
              </View>
              <View style={styles.chipBox}>
                <Text style={styles.chipLabel}>Tempo Rata-rata</Text>
                <Text style={[styles.chipValue, { color: '#34D399' }]}>
                  {stats?.average_wpm ? `${stats.average_wpm} WPM` : '—'}
                </Text>
              </View>
              <View style={styles.chipBox}>
                <Text style={styles.chipLabel}>Kata Pengisi</Text>
                <Text style={[styles.chipValue, { color: '#FBBF24' }]}>
                  {`${stats?.filler_count ?? 0}`}
                </Text>
              </View>
            </View>
          </LinearGradient>
        ) : (
          <LinearGradient
            colors={colors.heroCardGradient}
            style={styles.scoreHeroCard}
            start={{ x: 0, y: 0 }}
            end={{ x: 1, y: 1 }}
          >
            <View style={styles.heroGlowOrb} />

            <View style={[styles.scoreCircle, { borderColor: scoreColor }]}>
              <View style={styles.scoreCircleInner}>
                <Text style={[styles.scoreCircleNumber, { color: scoreColor }]}>{bandNumber}</Text>
                <Text style={styles.scoreCircleUnit}>{band ? '/ 9.0' : '/ 100'}</Text>
              </View>
              <Text style={styles.scoreCircleLevel}>{bandLevel}</Text>
            </View>

            {/* 4 Criteria Grid (2x2) matching backend fluency, vocabulary, structure, delivery */}
            <View style={styles.chipsGrid}>
              {criteriaList.map((item) => {
                const valueText = item.band !== null
                  ? item.band.toFixed(1)
                  : item.key === 'delivery'
                    ? `${Math.round(faceScore)}`
                    : item.key === 'fluency'
                      ? `${Math.round(voiceScore)}`
                      : '—';
                const valColor = item.band !== null ? getBandColor(item.band) : item.color;
                return (
                  <View key={item.key} style={styles.chipBox}>
                    <View style={styles.chipHeaderRow}>
                      {renderCriterionIcon(item)}
                      <Text style={styles.chipLabel} numberOfLines={1}>{item.label}</Text>
                    </View>
                    <Text style={[styles.chipValue, { color: valColor }]}>
                      {valueText}
                      {item.band !== null ? <Text style={styles.chipUnit}> / 9</Text> : null}
                    </Text>
                  </View>
                );
              })}
            </View>
          </LinearGradient>
        )}

        {/* Metadata Cards */}
        <View style={styles.metaRow}>
          <View style={[styles.metaCard, { flex: 1, marginRight: 6 }]}>
            <View style={styles.metaCardIcon}>
              <Feather name="clock" size={20} color={colors.accentCyan} />
            </View>
            <View style={styles.metaCardText}>
              <Text style={styles.metaLabel}>Durasi Latihan</Text>
              <Text style={styles.metaValue} numberOfLines={1}>{formatDuration(durationSeconds)}</Text>
            </View>
          </View>

          <View style={[styles.metaCard, { flex: 1, marginLeft: 6 }]}>
            <View style={styles.metaCardIcon}>
              <Feather name="user-check" size={20} color={colors.primaryLight} />
            </View>
            <View style={styles.metaCardText}>
              <Text style={styles.metaLabel}>Penguji</Text>
              <Text style={styles.metaValue} numberOfLines={1}>
                {role?.name || role?.title || report?.scenario_type || 'AI Coach'}
              </Text>
            </View>
          </View>
        </View>

        {/* Real Speaking Stats */}
        {stats && (
          <View style={styles.sectionCard}>
            <View style={styles.sectionTitleRow}>
              <Ionicons name="analytics-outline" size={18} color={colors.accentCyan} />
              <Text style={styles.sectionTitle}>Statistik Bicara Anda</Text>
            </View>
            <View style={styles.statsRow}>
              <View style={styles.statPill}>
                <Text style={styles.statValue}>{isCoach ? (stats.turns ?? 0) : (stats.answers ?? 0)}</Text>
                <Text style={styles.statLabel}>Jawaban</Text>
              </View>
              <View style={styles.statPill}>
                <Text style={styles.statValue}>{stats.total_words ?? 0}</Text>
                <Text style={styles.statLabel}>Total Kata</Text>
              </View>
              <View style={styles.statPill}>
                <Text style={styles.statValue}>{stats.average_wpm ? `${stats.average_wpm}` : '—'}</Text>
                <Text style={styles.statLabel}>Kata/Menit</Text>
              </View>
              <View style={styles.statPill}>
                <Text style={styles.statValue}>{stats.filler_count ?? 0}</Text>
                <Text style={styles.statLabel}>Filler</Text>
              </View>
            </View>
            {fillerEntries.length > 0 && (
              <Text style={styles.fillerDetailText}>
                Kata pengisi terdeteksi:{' '}
                {fillerEntries.slice(0, 5).map(([word, count]) => `"${word}" ×${count}`).join(', ')}
              </Text>
            )}
          </View>
        )}

        {/* Detailed 4 Criteria Breakdown */}
        {band && (
          <View style={styles.conclusionCard}>
            <View style={styles.sectionTitleRow}>
              <MaterialCommunityIcons name="star-shooting" size={18} color={colors.accentCyan} />
              <Text style={styles.sectionTitle}>Kesimpulan & Rincian Kriteria</Text>
            </View>
            <Text style={styles.conclusionText}>{aiConclusion}</Text>

            <View style={styles.criteriaBarsWrapper}>
              {criteriaList.map((item) => {
                if (item.band === null) return null;
                const pct = Math.min(100, Math.max(8, (item.band / 9) * 100));
                const barColor = getBandColor(item.band);
                return (
                  <View key={item.key} style={styles.criterionBlock}>
                    <View style={styles.criterionHeader}>
                      <Text style={styles.criterionLabel}>{item.label}</Text>
                      <Text style={[styles.criterionScore, { color: barColor }]}>
                        {item.band.toFixed(1)} / 9
                      </Text>
                    </View>
                    <View style={styles.progressTrack}>
                      <View style={[styles.progressFill, { width: `${pct}%`, backgroundColor: barColor }]} />
                    </View>
                    {!!item.description && (
                      <Text style={styles.criterionDesc}>{item.description}</Text>
                    )}
                  </View>
                );
              })}
            </View>
          </View>
        )}

        {/* Strengths */}
        {strengths.length > 0 && (
          <View style={styles.strengthsCard}>
            <Text style={styles.feedbackSectionTitle}>Kekuatan Komunikasi Anda</Text>
            {strengths.map((str, idx) => (
              <View key={idx} style={styles.bulletRow}>
                <Ionicons name="checkmark-circle" size={18} color={colors.success} style={{ marginTop: 1 }} />
                <Text style={styles.bulletTextStrength}>{str}</Text>
              </View>
            ))}
          </View>
        )}

        {/* Improvements / Next Drills */}
        {improvements.length > 0 && (
          <View style={styles.improvementsCard}>
            <Text style={styles.feedbackSectionTitle}>Fokus Latihan Berikutnya</Text>
            {improvements.map((imp, idx) => (
              <View key={idx} style={styles.bulletRow}>
                <Ionicons name="bulb-outline" size={18} color={colors.warning} style={{ marginTop: 1 }} />
                <Text style={styles.bulletTextImprovement}>{imp}</Text>
              </View>
            ))}
          </View>
        )}

        {/* Coach Taught Phrases */}
        {taughtPhrases.length > 0 && (
          <View style={styles.sectionCard}>
            <View style={styles.sectionTitleRow}>
              <Feather name="bookmark" size={16} color={colors.accentCyan} />
              <Text style={styles.sectionTitle}>Contoh Kalimat dari Coach</Text>
            </View>
            {taughtPhrases.map((phrase, idx) => (
              <View key={idx} style={styles.phraseBox}>
                <Text style={styles.phraseText}>{`"${phrase}"`}</Text>
              </View>
            ))}
          </View>
        )}

        {/* Per-Answer Analysis */}
        {answers.length > 0 && (
          <View style={styles.sectionCard}>
            <View style={styles.sectionTitleRow}>
              <Feather name="list" size={17} color={colors.accentCyan} />
              <Text style={styles.sectionTitle}>{`Evaluasi Per Jawaban (${answers.length})`}</Text>
            </View>

            {answers.map((ans) => {
              const ansBandColor = getBandColor(ans.band);
              return (
                <View key={ans.message_id || ans.index} style={styles.answerItemCard}>
                  <View style={styles.answerItemHeader}>
                    <View style={styles.partPill}>
                      <Text style={styles.partPillText}>{`Part ${ans.part} • #${ans.index}`}</Text>
                    </View>
                    <View style={[styles.bandPill, { borderColor: ansBandColor }]}>
                      <Text style={[styles.bandPillText, { color: ansBandColor }]}>
                        {`Band ${Number(ans.band || 0).toFixed(1)}`}
                      </Text>
                    </View>
                  </View>

                  {!!ans.question && (
                    <Text style={styles.answerQuestion}>{ans.question}</Text>
                  )}

                  <View style={styles.answerTranscriptBox}>
                    <Text style={styles.answerTranscriptLabel}>Jawaban Anda:</Text>
                    <Text style={styles.answerTranscript}>{ans.answer}</Text>
                    <Text style={styles.answerMetricsText}>
                      {`${ans.metrics?.word_count ?? 0} kata`}
                      {ans.metrics?.wpm ? ` • ${ans.metrics.wpm} WPM` : ''}
                      {ans.metrics?.filler_count ? ` • ${ans.metrics.filler_count} filler` : ''}
                    </Text>
                  </View>

                  {Array.isArray(ans.notes) && ans.notes.map((note, nIdx) => (
                    <View key={nIdx} style={styles.noteRow}>
                      <View style={[styles.noteDot, { backgroundColor: noteBadgeColor(note.type) }]} />
                      <Text style={styles.noteText}>{note.text}</Text>
                    </View>
                  ))}

                  {!!ans.improved && (
                    <View style={styles.improvedBox}>
                      <Text style={styles.improvedLabel}>
                        {ans.improved_source === 'ai' ? 'Contoh Jawaban Lebih Kuat:' : 'Pola Jawaban Disarankan:'}
                      </Text>
                      <Text style={styles.improvedText}>{ans.improved}</Text>
                    </View>
                  )}
                </View>
              );
            })}
          </View>
        )}

        {/* Certificate Card */}
        {!!verificationUrl && (
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
              Pindai QR code ini untuk melihat rapor lengkap &amp; verifikasi kompetensi di browser.
            </Text>

            <View style={styles.qrFrame}>
              <QRCode
                value={verificationUrl}
                size={130}
                color="#070B14"
                backgroundColor="#FFFFFF"
              />
            </View>

            <View style={styles.certWatermarkRow}>
              <Feather name="award" size={12} color="#FBBF24" style={{ marginRight: 5 }} />
              <Text style={styles.certWatermark}>VOIC CERTIFIED</Text>
            </View>
          </LinearGradient>
        )}

        {/* CTA Button */}
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

        <View style={{ height: Math.max(insets.bottom + 24, 40) }} />
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
    alignItems: 'center',
    paddingBottom: 22,
    paddingHorizontal: 20,
    position: 'relative',
  },
  headerAwardIconWrapper: {
    width: 66,
    height: 66,
    borderRadius: 33,
    backgroundColor: 'rgba(251, 191, 36, 0.12)',
    borderWidth: 2,
    borderColor: 'rgba(251, 191, 36, 0.35)',
    justifyContent: 'center',
    alignItems: 'center',
    marginBottom: 10,
  },
  headerTitle: {
    fontSize: 24,
    fontWeight: '900',
    color: '#FFFFFF',
    letterSpacing: 0.4,
  },
  headerSubtitle: {
    fontSize: 12,
    color: 'rgba(203, 213, 225, 0.85)',
    marginTop: 4,
    letterSpacing: 0.3,
  },
  closeButton: {
    position: 'absolute',
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
  scrollContent: {
    padding: 16,
    paddingTop: 18,
  },
  scoreHeroCard: {
    padding: 22,
    borderRadius: 24,
    borderWidth: 1.5,
    borderColor: colors.cardBorder,
    alignItems: 'center',
    marginBottom: 14,
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
  coachHeroBadge: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 6,
    paddingHorizontal: 12,
    paddingVertical: 5,
    borderRadius: 999,
    backgroundColor: 'rgba(56, 189, 248, 0.14)',
    borderWidth: 1,
    borderColor: 'rgba(56, 189, 248, 0.3)',
    marginBottom: 12,
  },
  coachHeroBadgeText: {
    fontSize: 10,
    fontWeight: '800',
    color: colors.accentCyan,
    letterSpacing: 0.6,
  },
  coachHeroTitle: {
    fontSize: 20,
    fontWeight: '900',
    color: '#FFFFFF',
    textAlign: 'center',
  },
  coachHeroSubtitle: {
    fontSize: 13,
    color: colors.textSecondary,
    textAlign: 'center',
    lineHeight: 19,
    marginTop: 6,
    marginBottom: 16,
  },
  scoreCircle: {
    width: 152,
    height: 152,
    borderRadius: 76,
    borderWidth: 5,
    justifyContent: 'center',
    alignItems: 'center',
    backgroundColor: 'rgba(0,0,0,0.28)',
    marginBottom: 18,
  },
  scoreCircleInner: {
    flexDirection: 'row',
    alignItems: 'flex-end',
  },
  scoreCircleNumber: {
    fontSize: 46,
    fontWeight: '900',
    letterSpacing: -1,
    lineHeight: 54,
  },
  scoreCircleUnit: {
    fontSize: 14,
    color: 'rgba(255,255,255,0.55)',
    fontWeight: '600',
    marginBottom: 10,
    marginLeft: 3,
  },
  scoreCircleLevel: {
    fontSize: 12,
    color: 'rgba(255,255,255,0.8)',
    fontWeight: '700',
    marginTop: 2,
    letterSpacing: 0.4,
  },
  chipsGrid: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    justifyContent: 'space-between',
    width: '100%',
    gap: 8,
    paddingTop: 14,
    borderTopWidth: 1,
    borderTopColor: colors.cardBorderSubtle,
  },
  chipBox: {
    width: '48.5%',
    backgroundColor: 'rgba(15, 23, 42, 0.55)',
    borderRadius: 14,
    borderWidth: 1,
    borderColor: 'rgba(148, 163, 184, 0.16)',
    paddingVertical: 10,
    paddingHorizontal: 12,
  },
  chipHeaderRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 6,
  },
  chipLabel: {
    fontSize: 11,
    color: colors.textSecondary,
    flex: 1,
  },
  chipValue: {
    fontSize: 17,
    fontWeight: '800',
    marginTop: 4,
  },
  chipUnit: {
    fontSize: 11,
    fontWeight: '600',
    color: colors.textSecondary,
  },
  metaRow: {
    flexDirection: 'row',
    marginBottom: 14,
  },
  metaCard: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: colors.card,
    padding: 13,
    borderRadius: 16,
    borderWidth: 1,
    borderColor: colors.cardBorderSubtle,
  },
  metaCardIcon: {
    width: 40,
    height: 40,
    borderRadius: 20,
    backgroundColor: 'rgba(37, 99, 235, 0.12)',
    borderWidth: 1,
    borderColor: 'rgba(59, 130, 246, 0.25)',
    justifyContent: 'center',
    alignItems: 'center',
    marginRight: 10,
  },
  metaCardText: {
    flex: 1,
  },
  metaLabel: {
    fontSize: 10,
    color: colors.textSecondary,
  },
  metaValue: {
    fontSize: 13,
    fontWeight: 'bold',
    color: '#FFFFFF',
    marginTop: 2,
  },
  sectionCard: {
    backgroundColor: colors.card,
    padding: 16,
    borderRadius: 16,
    borderWidth: 1,
    borderColor: colors.cardBorderSubtle,
    marginBottom: 14,
  },
  statsRow: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    gap: 8,
  },
  statPill: {
    flex: 1,
    alignItems: 'center',
    backgroundColor: 'rgba(15, 23, 42, 0.65)',
    borderRadius: 12,
    paddingVertical: 10,
    borderWidth: 1,
    borderColor: 'rgba(148, 163, 184, 0.15)',
  },
  statValue: {
    fontSize: 16,
    fontWeight: '800',
    color: '#FFFFFF',
  },
  statLabel: {
    fontSize: 10,
    color: colors.textSecondary,
    marginTop: 2,
  },
  fillerDetailText: {
    marginTop: 10,
    fontSize: 12,
    color: '#FCD34D',
    lineHeight: 18,
  },
  conclusionCard: {
    backgroundColor: colors.card,
    padding: 18,
    borderRadius: 16,
    borderWidth: 1,
    borderColor: colors.cardBorderSubtle,
    borderLeftWidth: 4,
    borderLeftColor: '#3B82F6',
    marginBottom: 14,
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
    fontSize: 13.5,
    color: colors.textSecondary,
    lineHeight: 21,
  },
  criteriaBarsWrapper: {
    marginTop: 14,
    gap: 12,
  },
  criterionBlock: {
    gap: 4,
  },
  criterionHeader: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
  },
  criterionLabel: {
    fontSize: 13,
    fontWeight: '700',
    color: '#F1F5F9',
  },
  criterionScore: {
    fontSize: 13,
    fontWeight: '800',
  },
  progressTrack: {
    height: 7,
    borderRadius: 999,
    backgroundColor: 'rgba(148, 163, 184, 0.16)',
    overflow: 'hidden',
  },
  progressFill: {
    height: '100%',
    borderRadius: 999,
  },
  criterionDesc: {
    fontSize: 11.5,
    color: colors.textSecondary,
    lineHeight: 16,
  },
  strengthsCard: {
    backgroundColor: 'rgba(16, 185, 129, 0.07)',
    padding: 16,
    borderRadius: 16,
    borderWidth: 1,
    borderColor: 'rgba(16, 185, 129, 0.22)',
    marginBottom: 14,
  },
  improvementsCard: {
    backgroundColor: 'rgba(245, 158, 11, 0.07)',
    padding: 16,
    borderRadius: 16,
    borderWidth: 1,
    borderColor: 'rgba(245, 158, 11, 0.22)',
    marginBottom: 14,
  },
  feedbackSectionTitle: {
    fontSize: 14.5,
    fontWeight: 'bold',
    color: '#FFFFFF',
    marginBottom: 10,
  },
  bulletRow: {
    flexDirection: 'row',
    marginBottom: 8,
    alignItems: 'flex-start',
  },
  bulletTextStrength: {
    fontSize: 13.5,
    color: '#D1FAE5',
    lineHeight: 20,
    marginLeft: 10,
    flex: 1,
  },
  bulletTextImprovement: {
    fontSize: 13.5,
    color: '#FEF3C7',
    lineHeight: 20,
    marginLeft: 10,
    flex: 1,
  },
  phraseBox: {
    backgroundColor: 'rgba(56, 189, 248, 0.08)',
    borderLeftWidth: 3,
    borderLeftColor: colors.accentCyan,
    padding: 10,
    borderRadius: 10,
    marginBottom: 8,
  },
  phraseText: {
    fontSize: 13,
    color: '#E0F2FE',
    lineHeight: 19,
  },
  answerItemCard: {
    backgroundColor: 'rgba(15, 23, 42, 0.65)',
    borderRadius: 14,
    padding: 14,
    borderWidth: 1,
    borderColor: 'rgba(148, 163, 184, 0.16)',
    marginBottom: 12,
  },
  answerItemHeader: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    marginBottom: 8,
  },
  partPill: {
    paddingHorizontal: 9,
    paddingVertical: 3,
    borderRadius: 999,
    backgroundColor: 'rgba(59, 130, 246, 0.16)',
  },
  partPillText: {
    fontSize: 11,
    fontWeight: '700',
    color: colors.primaryLight,
  },
  bandPill: {
    paddingHorizontal: 9,
    paddingVertical: 3,
    borderRadius: 999,
    borderWidth: 1,
    backgroundColor: 'rgba(15, 23, 42, 0.8)',
  },
  bandPillText: {
    fontSize: 11,
    fontWeight: '800',
  },
  answerQuestion: {
    fontSize: 12.5,
    color: '#CBD5E1',
    fontWeight: '600',
    marginBottom: 8,
    lineHeight: 18,
  },
  answerTranscriptBox: {
    backgroundColor: 'rgba(30, 41, 59, 0.65)',
    borderRadius: 10,
    padding: 10,
    marginBottom: 8,
  },
  answerTranscriptLabel: {
    fontSize: 10.5,
    color: colors.textSecondary,
    fontWeight: '700',
    marginBottom: 3,
  },
  answerTranscript: {
    fontSize: 13,
    color: '#F8FAFC',
    lineHeight: 19,
  },
  answerMetricsText: {
    fontSize: 11,
    color: colors.accentCyan,
    marginTop: 6,
    fontWeight: '600',
  },
  noteRow: {
    flexDirection: 'row',
    alignItems: 'flex-start',
    marginTop: 5,
    gap: 8,
  },
  noteDot: {
    width: 7,
    height: 7,
    borderRadius: 4,
    marginTop: 6,
  },
  noteText: {
    flex: 1,
    fontSize: 12,
    color: '#CBD5E1',
    lineHeight: 17,
  },
  improvedBox: {
    marginTop: 10,
    backgroundColor: 'rgba(56, 189, 248, 0.08)',
    borderRadius: 10,
    padding: 10,
    borderWidth: 1,
    borderColor: 'rgba(56, 189, 248, 0.2)',
  },
  improvedLabel: {
    fontSize: 11,
    fontWeight: '700',
    color: colors.accentCyan,
    marginBottom: 4,
  },
  improvedText: {
    fontSize: 12.5,
    color: '#E0F2FE',
    lineHeight: 18,
  },
  certificateCard: {
    padding: 22,
    borderRadius: 20,
    borderWidth: 1.5,
    borderColor: 'rgba(59, 130, 246, 0.35)',
    alignItems: 'center',
    marginBottom: 18,
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
    marginBottom: 18,
    lineHeight: 18,
  },
  qrFrame: {
    padding: 14,
    backgroundColor: '#FFFFFF',
    borderRadius: 16,
    marginBottom: 14,
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
  homeButton: {
    borderRadius: 16,
    overflow: 'hidden',
  },
  homeGradient: {
    height: 56,
    flexDirection: 'row',
    justifyContent: 'center',
    alignItems: 'center',
  },
  homeButtonText: {
    color: '#FFFFFF',
    fontSize: 16,
    fontWeight: '800',
    letterSpacing: 0.4,
  },
});
