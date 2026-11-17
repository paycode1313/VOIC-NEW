import React, { useState, useEffect } from 'react';
import {
  View,
  Text,
  Image,
  StyleSheet,
  ScrollView,
  TouchableOpacity,
  TextInput,
  ActivityIndicator,
  Alert,
} from 'react-native';
import { LinearGradient } from 'expo-linear-gradient';
import { Feather } from '@expo/vector-icons';
import { useSafeAreaInsets } from 'react-native-safe-area-context';
import { colors } from '../constants/theme';
import { apiService } from '../services/apiService';
import VoicLogo from '../components/VoicLogo';

const MODE_VISUALS = {
  wawancara: {
    grad: ['#D97706', '#B45309'],
    accentColor: '#FBBF24',
    accentBg: 'rgba(245, 158, 11, 0.14)',
    accentBorder: 'rgba(245, 158, 11, 0.35)',
    icon: 'briefcase',
    avatar: require('../../assets/avatars/hrd.jpg'),
    badge: 'KARIR · HRD RECRUITER',
  },
  sidang: {
    grad: ['#2563EB', '#1D4ED8'],
    accentColor: '#38BDF8',
    accentBg: 'rgba(37, 99, 235, 0.16)',
    accentBorder: 'rgba(56, 189, 248, 0.35)',
    icon: 'book-open',
    avatar: require('../../assets/avatars/dosen.jpg'),
    badge: 'AKADEMIK · DOSEN PENGUJI',
  },
  pitching: {
    grad: ['#7C3AED', '#5B21B6'],
    accentColor: '#C4B5FD',
    accentBg: 'rgba(139, 92, 246, 0.16)',
    accentBorder: 'rgba(167, 139, 250, 0.35)',
    icon: 'trending-up',
    avatar: require('../../assets/avatars/investor.jpg'),
    badge: 'VENTURE · INVESTOR VC',
  },
};

function getUserInitials(name) {
  if (!name) return 'V';
  return name
    .split(' ')
    .slice(0, 2)
    .map((w) => w[0]?.toUpperCase() || '')
    .join('');
}

export default function HomeScreen({ user, onStartPractice }) {
  const insets = useSafeAreaInsets();
  const [isLoading, setIsLoading] = useState(true);
  const [modes, setModes] = useState([]);
  const [selectedModeKey, setSelectedModeKey] = useState('wawancara');
  const [sessionType, setSessionType] = useState('exam'); // 'exam' | 'coach'
  const [language, setLanguage] = useState('id'); // 'id' | 'en'
  const [selectedTopic, setSelectedTopic] = useState('');
  const [customTopic, setCustomTopic] = useState('');
  const [contextMaterial, setContextMaterial] = useState('');
  const [topicFocused, setTopicFocused] = useState(false);
  const [contextFocused, setContextFocused] = useState(false);
  const [isStarting, setIsStarting] = useState(false);

  useEffect(() => {
    loadModes();
  }, []);

  const loadModes = async () => {
    try {
      const res = await apiService.getExamModes('id');
      if (res.success && Array.isArray(res.modes) && res.modes.length > 0) {
        setModes(res.modes);
        setSelectedModeKey(res.modes[0].key);
        const firstTopics = res.modes[0].topics_i18n?.id || res.modes[0].topics || [];
        if (firstTopics.length > 0) {
          setSelectedTopic(firstTopics[0]);
        }
      }
    } catch (err) {
      console.log('Error fetching exam modes:', err);
    } finally {
      setIsLoading(false);
    }
  };

  const selectedMode = modes.find((m) => m.key === selectedModeKey) || modes[0] || null;
  const isEn = language === 'en';

  const handleSelectMode = (mode) => {
    setSelectedModeKey(mode.key);
    const nextTopics = mode.topics_i18n?.[language] || mode.topics || [];
    setSelectedTopic(nextTopics[0] || '');
    setCustomTopic('');
  };

  const handleChangeLanguage = (nextLang) => {
    setLanguage(nextLang);
    if (selectedMode) {
      const currentList = selectedMode.topics_i18n?.[language] || selectedMode.topics || [];
      const nextList = selectedMode.topics_i18n?.[nextLang] || selectedMode.topics || [];
      const currentIdx = currentList.indexOf(selectedTopic);
      if (currentIdx >= 0 && nextList[currentIdx]) {
        setSelectedTopic(nextList[currentIdx]);
      } else if (nextList.length > 0 && selectedTopic !== '') {
        setSelectedTopic(nextList[0]);
      }
    }
  };

  const resolvedTopic = customTopic.trim() || selectedTopic || null;

  const handleStart = async () => {
    if (!selectedMode) return;
    setIsStarting(true);
    try {
      const res = await apiService.startExam(selectedMode.key, {
        topic: resolvedTopic,
        language,
        contextMaterial: contextMaterial.trim() || null,
        sessionType,
      });

      if (res.success) {
        onStartPractice({
          sessionId: res.session_id,
          modeKey: selectedMode.key,
          role: {
            name: selectedMode.examiner?.name || 'VOIC AI',
            title: selectedMode.examiner?.title_i18n?.[language] || selectedMode.examiner?.title || '',
            role_type: selectedMode.role_type,
          },
          scenario: {
            name: resolvedTopic || (selectedMode.title_i18n?.[language] || selectedMode.title),
          },
          language,
          sessionType,
          initialGreeting: res.examiner_message?.text || '',
          initialAudioUrl: res.examiner_message?.audio_url,
          partTitle: res.part_title,
          initialPayload: res,
        });
      } else if (res.message && !String(res.message).toLowerCase().includes('unauthenticated')) {
        Alert.alert('Gagal Memulai Sesi', res.message || 'Coba periksa kembali pilihanmu.');
      }
    } catch (err) {
      Alert.alert('Gagal Memulai Sesi', 'Pastikan server Laravel aktif dan koneksi Wi-Fi stabil.');
    } finally {
      setIsStarting(false);
    }
  };

  if (isLoading) {
    return (
      <View style={styles.centerContainer}>
        <ActivityIndicator size="large" color={colors.primary} />
        <Text style={styles.loadingText}>Menyiapkan Penguji AI VOIC...</Text>
      </View>
    );
  }

  const modeTitle = selectedMode?.title_i18n?.[language] || selectedMode?.title || 'Simulasi';
  const modeTopicLabel = selectedMode?.topic_label_i18n?.[language] || selectedMode?.topic_label || 'Topik Latihan';
  const modeTopicPlaceholder = selectedMode?.topic_placeholder_i18n?.[language] || selectedMode?.topic_placeholder || 'Tulis topik spesifik...';
  const modeContextLabel = selectedMode?.context_label_i18n?.[language] || selectedMode?.context_label || 'Catatan konteks tambahan (opsional)';
  const modeTopics = selectedMode?.topics_i18n?.[language] || selectedMode?.topics || [];

  return (
    <View style={styles.container}>
      {/* Top App Bar */}
      <View style={[styles.topBar, { paddingTop: Math.max(insets.top, 14) }]}>
        <View style={styles.brandRow}>
          <VoicLogo width={84} height={24} />
          <View style={styles.liveChip}>
            <View style={styles.liveChipDot} />
            <Text style={styles.liveChipText}>ONLINE</Text>
          </View>
        </View>

        <View style={styles.userProfilePill}>
          <View style={styles.userAvatarCircle}>
            <Text style={styles.userAvatarInitials}>{getUserInitials(user?.name)}</Text>
          </View>
          <Text style={styles.userGreeting} numberOfLines={1}>
            {user?.name?.split(' ')[0] || 'Peserta'}
          </Text>
        </View>
      </View>

      <ScrollView
        contentContainerStyle={styles.scrollContent}
        showsVerticalScrollIndicator={false}
        keyboardShouldPersistTaps="handled"
      >
        {/* Executive Hero Card */}
        <LinearGradient
          colors={['#0F1C2F', '#080F1B']}
          style={styles.heroBanner}
          start={{ x: 0, y: 0 }}
          end={{ x: 1, y: 1 }}
        >
          <View style={styles.heroGlowAccent} />

          <View style={styles.heroTag}>
            <Feather name="cpu" size={11} color={colors.accentCyan} />
            <Text style={styles.heroTagText}>AI VOICE & OPTICS COACH</Text>
          </View>

          <Text style={styles.heroTitle}>
            {isEn ? 'Who will examine you today?' : 'Mau latihan dengan penguji AI mana?'}
          </Text>
          <Text style={styles.heroSubtitle}>
            {isEn
              ? 'Each AI examiner simulates a real oral session, responds to your actual answers, and scores your delivery.'
              : 'Setiap penguji AI berperan sesuai situasi nyata (HRD, Dosen, atau Investor), menanggapi isi jawabanmu, dan menilai penyampaianmu.'}
          </Text>

          <View style={styles.heroStatsGrid}>
            <View style={styles.heroStatCard}>
              <Feather name="layers" size={14} color={colors.accentCyan} />
              <Text style={styles.heroStatValue}>{isEn ? '3 Parts' : '3 Bagian'}</Text>
              <Text style={styles.heroStatLabel}>{isEn ? 'Structured flow' : 'Alur terarah'}</Text>
            </View>
            <View style={styles.heroStatCard}>
              <Feather name="mic" size={14} color={colors.success} />
              <Text style={styles.heroStatValue}>Real-Time</Text>
              <Text style={styles.heroStatLabel}>{isEn ? 'Voice & Gaze' : 'Suara & Tatapan'}</Text>
            </View>
            <View style={styles.heroStatCard}>
              <Feather name="award" size={14} color="#FBBF24" />
              <Text style={styles.heroStatValue}>Band 0–9</Text>
              <Text style={styles.heroStatLabel}>{isEn ? 'Instant report' : 'Rapor instan'}</Text>
            </View>
          </View>
        </LinearGradient>

        {/* STEP 1: Mode & Penguji AI (Unified — no mismatched combinations!) */}
        <View style={styles.sectionHeaderRow}>
          <View>
            <Text style={styles.stepEyebrow}>LANGKAH 1 · MODE & PENGUJI AI</Text>
            <Text style={styles.sectionTitle}>
              {isEn ? 'Choose Simulation & Examiner' : 'Pilih Simulasi & Penguji AI'}
            </Text>
          </View>
          <View style={styles.sectionCountPill}>
            <Text style={styles.sectionCountText}>{modes.length} Mode</Text>
          </View>
        </View>

        {modes.map((mode) => {
          const isSelected = selectedMode?.key === mode.key;
          const visual = MODE_VISUALS[mode.key] || MODE_VISUALS.sidang;
          const localizedTitle = mode.title_i18n?.[language] || mode.title;
          const localizedTagline = mode.tagline_i18n?.[language] || mode.tagline;
          const localizedExaminerTitle = mode.examiner?.title_i18n?.[language] || mode.examiner?.title;
          const partsLabel = (mode.parts || [])
            .map((p) => p.title_i18n?.[language] || p.title)
            .join(' · ');

          return (
            <TouchableOpacity
              key={mode.key}
              onPress={() => handleSelectMode(mode)}
              style={[
                styles.modeCard,
                isSelected && {
                  borderColor: visual.accentColor,
                  backgroundColor: 'rgba(15, 28, 47, 0.98)',
                },
              ]}
              activeOpacity={0.85}
            >
              <View style={styles.modeCardTop}>
                <View style={styles.modeAvatarWrap}>
                  <Image source={visual.avatar} style={styles.modeAvatarImg} />
                  <View style={styles.modeOnlineDot} />
                </View>

                <View style={styles.modeMetaCol}>
                  <View style={[styles.modeBadge, { backgroundColor: visual.accentBg, borderColor: visual.accentBorder }]}>
                    <Feather name={visual.icon} size={10} color={visual.accentColor} />
                    <Text style={[styles.modeBadgeText, { color: visual.accentColor }]}>{visual.badge}</Text>
                  </View>
                  <Text style={styles.modeTitle}>{localizedTitle}</Text>
                  <Text style={styles.modeExaminer}>
                    {mode.examiner?.name} · {localizedExaminerTitle}
                  </Text>
                </View>

                <View style={[styles.radioCircle, isSelected && { backgroundColor: visual.accentColor, borderColor: visual.accentColor }]}>
                  {isSelected && <Feather name="check" size={13} color="#080F1B" />}
                </View>
              </View>

              <Text style={styles.modeTagline}>{localizedTagline}</Text>

              {partsLabel ? (
                <View style={styles.modePartsStrip}>
                  <Feather name="list" size={12} color={colors.textMuted} />
                  <Text style={styles.modePartsText} numberOfLines={1}>
                    {partsLabel}
                  </Text>
                </View>
              ) : null}
            </TouchableOpacity>
          );
        })}

        {/* STEP 2: Gaya Sesi & Bahasa */}
        <View style={styles.sectionCard}>
          <Text style={styles.stepEyebrow}>LANGKAH 2 · GAYA LATIHAN & BAHASA</Text>
          <Text style={styles.cardTitle}>
            {isEn ? 'How do you want to practise?' : 'Mau mode latihan yang seperti apa?'}
          </Text>

          <View style={styles.sessionTypeGrid}>
            <TouchableOpacity
              style={[styles.typeCard, sessionType === 'exam' && styles.typeCardActive]}
              onPress={() => setSessionType('exam')}
              activeOpacity={0.85}
            >
              <View style={styles.typeCardTop}>
                <Feather
                  name="target"
                  size={16}
                  color={sessionType === 'exam' ? colors.accentCyan : colors.textSecondary}
                />
                {sessionType === 'exam' && <Feather name="check-circle" size={14} color={colors.accentCyan} />}
              </View>
              <Text style={[styles.typeCardTitle, sessionType === 'exam' && { color: '#FFFFFF' }]}>
                {isEn ? 'Mock Exam' : 'Simulasi Ujian'}
              </Text>
              <Text style={styles.typeCardDesc}>
                {isEn ? '3 parts · Band score 0–9 & full report' : '3 bagian · Ada nilai Band 0–9 & rapor'}
              </Text>
            </TouchableOpacity>

            <TouchableOpacity
              style={[styles.typeCard, sessionType === 'coach' && styles.typeCardActive]}
              onPress={() => setSessionType('coach')}
              activeOpacity={0.85}
            >
              <View style={styles.typeCardTop}>
                <Feather
                  name="message-circle"
                  size={16}
                  color={sessionType === 'coach' ? colors.accentCyan : colors.textSecondary}
                />
                {sessionType === 'coach' && <Feather name="check-circle" size={14} color={colors.accentCyan} />}
              </View>
              <Text style={[styles.typeCardTitle, sessionType === 'coach' && { color: '#FFFFFF' }]}>
                {isEn ? 'Coach Chat' : 'Ngobrol Coach'}
              </Text>
              <Text style={styles.typeCardDesc}>
                {isEn ? '10 min · Relaxed, guided tips, no score' : '10 mnt · Santai, diajari cara jawab'}
              </Text>
            </TouchableOpacity>
          </View>

          <View style={[styles.segmentedRow, { marginTop: 12 }]}>
            <TouchableOpacity
              style={[styles.segmentBtn, language === 'id' && styles.segmentBtnActive]}
              onPress={() => handleChangeLanguage('id')}
              activeOpacity={0.85}
            >
              <Text style={[styles.segmentCode, language === 'id' && styles.segmentCodeActive]}>ID</Text>
              <Text style={[styles.segmentText, language === 'id' && styles.segmentTextActive]}>
                Bahasa Indonesia
              </Text>
            </TouchableOpacity>

            <TouchableOpacity
              style={[styles.segmentBtn, language === 'en' && styles.segmentBtnActive]}
              onPress={() => handleChangeLanguage('en')}
              activeOpacity={0.85}
            >
              <Text style={[styles.segmentCode, language === 'en' && styles.segmentCodeActive]}>EN</Text>
              <Text style={[styles.segmentText, language === 'en' && styles.segmentTextActive]}>
                English
              </Text>
            </TouchableOpacity>
          </View>
        </View>

        {/* STEP 3: Topik Spesifik (Dynamically matches the selected mode!) */}
        <View style={styles.sectionCard}>
          <View style={styles.cardHeaderRow}>
            <View style={{ flex: 1 }}>
              <Text style={styles.stepEyebrow}>
                LANGKAH 3 · TOPIK KHUSUS {modeTitle.toUpperCase()}
              </Text>
              <Text style={styles.cardTitle}>{modeTopicLabel}</Text>
              <Text style={styles.cardSub}>
                {isEn
                  ? 'Pick a preset below or type your own so examiner questions stay relevant.'
                  : 'Pilih salah satu di bawah atau tulis sendiri agar pertanyaan penguji langsung nyambung.'}
              </Text>
            </View>
          </View>

          <View style={styles.topicChipWrap}>
            {modeTopics.map((topic) => {
              const isPicked = !customTopic.trim() && selectedTopic === topic;
              return (
                <TouchableOpacity
                  key={topic}
                  onPress={() => {
                    setCustomTopic('');
                    setSelectedTopic(topic);
                  }}
                  style={[styles.topicChip, isPicked && styles.topicChipActive]}
                  activeOpacity={0.85}
                >
                  <Text style={[styles.topicChipText, isPicked && styles.topicChipTextActive]}>
                    {topic}
                  </Text>
                </TouchableOpacity>
              );
            })}
            <TouchableOpacity
              onPress={() => {
                setCustomTopic('');
                setSelectedTopic('');
              }}
              style={[
                styles.topicChip,
                !customTopic.trim() && selectedTopic === '' && styles.topicChipActive,
              ]}
              activeOpacity={0.85}
            >
              <Text
                style={[
                  styles.topicChipText,
                  !customTopic.trim() && selectedTopic === '' && styles.topicChipTextActive,
                ]}
              >
                {isEn ? 'General / Any' : 'Bebas / Umum'}
              </Text>
            </TouchableOpacity>
          </View>

          <Text style={[styles.inputLabel, { marginTop: 14 }]}>
            {isEn ? 'OR TYPE YOUR EXACT TOPIC' : 'ATAU TULIS TOPIK SPESIFIKMU'}
          </Text>
          <TextInput
            style={[styles.textInput, topicFocused && styles.textInputFocused]}
            placeholder={modeTopicPlaceholder}
            placeholderTextColor={colors.textMuted}
            value={customTopic}
            onChangeText={setCustomTopic}
            onFocus={() => setTopicFocused(true)}
            onBlur={() => setTopicFocused(false)}
            maxLength={150}
          />

          <View style={styles.contextLabelRow}>
            <Text style={styles.inputLabel}>{modeContextLabel.toUpperCase()}</Text>
            <Text style={styles.charCounter}>{contextMaterial.length}/300</Text>
          </View>
          <TextInput
            style={[styles.textInput, styles.textArea, contextFocused && styles.textInputFocused]}
            placeholder={
              isEn
                ? 'Optional: key points, abstract, or background to guide follow-up questions...'
                : 'Opsional: ringkasan singkat agar penguji bisa bertanya lebih tajam...'
            }
            placeholderTextColor={colors.textMuted}
            value={contextMaterial}
            onChangeText={setContextMaterial}
            onFocus={() => setContextFocused(true)}
            onBlur={() => setContextFocused(false)}
            multiline
            maxLength={300}
          />
        </View>

        {/* STEP 4 / Final Action Card: sits naturally at the bottom after Steps 1, 2, and 3 */}
        <View style={styles.ctaSectionCard}>
          <View style={styles.summaryStrip}>
            <View style={{ flex: 1 }}>
              <Text style={styles.summaryEyebrow}>RINGKASAN SIAP MULAI</Text>
              <Text style={styles.summaryStripText} numberOfLines={2}>
                {modeTitle} ({selectedMode?.examiner?.name}) · {resolvedTopic || (isEn ? 'General topic' : 'Topik umum')}
              </Text>
            </View>
            <Text style={styles.summaryStripBadge}>
              {sessionType === 'coach' ? 'COACH 10M' : 'SIMULASI'}
            </Text>
          </View>

          <TouchableOpacity
            style={styles.startButton}
            onPress={handleStart}
            disabled={isStarting || !selectedMode}
            activeOpacity={0.88}
          >
            <LinearGradient
              colors={['#2563EB', '#1D4ED8', '#1E40AF']}
              start={{ x: 0, y: 0 }}
              end={{ x: 1, y: 0 }}
              style={styles.buttonGradient}
            >
              {isStarting ? (
                <ActivityIndicator color="#FFFFFF" />
              ) : (
                <View style={styles.ctaContent}>
                  <Feather name="video" size={18} color="#FFFFFF" />
                  <Text style={styles.startButtonText}>
                    {sessionType === 'coach'
                      ? (isEn ? `Chat with ${modeTitle} Coach` : `Ngobrol Coach ${modeTitle}`)
                      : (isEn ? `Start ${modeTitle}` : `Mulai ${modeTitle}`)}
                  </Text>
                  <Feather name="arrow-right" size={17} color="#93C5FD" />
                </View>
              )}
            </LinearGradient>
          </TouchableOpacity>
        </View>

        <View style={{ height: 96 + Math.max(insets.bottom, 8) }} />
      </ScrollView>
    </View>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: colors.background,
  },
  centerContainer: {
    flex: 1,
    backgroundColor: colors.background,
    justifyContent: 'center',
    alignItems: 'center',
  },
  loadingText: {
    color: colors.textSecondary,
    fontSize: 13,
    marginTop: 12,
    fontWeight: '500',
  },

  topBar: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    paddingHorizontal: 20,
    paddingBottom: 14,
    backgroundColor: colors.surface,
    borderBottomWidth: 1,
    borderBottomColor: 'rgba(255, 255, 255, 0.06)',
  },
  brandRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 10,
  },
  liveChip: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: 'rgba(16, 185, 129, 0.12)',
    borderWidth: 1,
    borderColor: 'rgba(16, 185, 129, 0.3)',
    paddingHorizontal: 8,
    paddingVertical: 3,
    borderRadius: 999,
  },
  liveChipDot: {
    width: 6,
    height: 6,
    borderRadius: 3,
    backgroundColor: colors.success,
    marginRight: 5,
  },
  liveChipText: {
    fontSize: 9.5,
    fontWeight: '800',
    color: '#6EE7B7',
    letterSpacing: 0.6,
  },
  userProfilePill: {
    flexDirection: 'row',
    alignItems: 'center',
    paddingLeft: 4,
    paddingRight: 11,
    paddingVertical: 4,
    borderRadius: 999,
    backgroundColor: colors.card,
    borderWidth: 1,
    borderColor: 'rgba(255, 255, 255, 0.08)',
  },
  userAvatarCircle: {
    width: 26,
    height: 26,
    borderRadius: 13,
    backgroundColor: colors.primary,
    justifyContent: 'center',
    alignItems: 'center',
    marginRight: 7,
  },
  userAvatarInitials: {
    fontSize: 10.5,
    fontWeight: '800',
    color: '#FFFFFF',
  },
  userGreeting: {
    fontSize: 12,
    fontWeight: '700',
    color: colors.textPrimary,
    maxWidth: 100,
  },

  scrollContent: {
    padding: 18,
  },

  heroBanner: {
    padding: 20,
    borderRadius: 22,
    borderWidth: 1,
    borderColor: 'rgba(59, 130, 246, 0.22)',
    marginBottom: 20,
    overflow: 'hidden',
  },
  heroGlowAccent: {
    position: 'absolute',
    top: -50,
    right: -40,
    width: 160,
    height: 160,
    borderRadius: 80,
    backgroundColor: 'rgba(37, 99, 235, 0.18)',
  },
  heroTag: {
    flexDirection: 'row',
    alignItems: 'center',
    alignSelf: 'flex-start',
    gap: 6,
    paddingHorizontal: 10,
    paddingVertical: 4,
    borderRadius: 999,
    backgroundColor: 'rgba(37, 99, 235, 0.16)',
    borderWidth: 1,
    borderColor: 'rgba(56, 189, 248, 0.28)',
    marginBottom: 10,
  },
  heroTagText: {
    fontSize: 10,
    fontWeight: '800',
    color: colors.accentCyan,
    letterSpacing: 0.7,
  },
  heroTitle: {
    fontSize: 21,
    fontWeight: '800',
    color: '#FFFFFF',
    letterSpacing: -0.3,
    marginBottom: 6,
  },
  heroSubtitle: {
    fontSize: 12.5,
    color: colors.textSecondary,
    lineHeight: 18.5,
    marginBottom: 16,
  },
  heroStatsGrid: {
    flexDirection: 'row',
    gap: 8,
  },
  heroStatCard: {
    flex: 1,
    backgroundColor: 'rgba(255, 255, 255, 0.04)',
    borderWidth: 1,
    borderColor: 'rgba(255, 255, 255, 0.07)',
    borderRadius: 14,
    paddingVertical: 10,
    paddingHorizontal: 10,
  },
  heroStatValue: {
    fontSize: 13,
    fontWeight: '800',
    color: '#F8FAFC',
    marginTop: 6,
  },
  heroStatLabel: {
    fontSize: 10,
    color: colors.textMuted,
    marginTop: 1,
    fontWeight: '600',
  },

  sectionHeaderRow: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'flex-end',
    marginBottom: 12,
  },
  stepEyebrow: {
    fontSize: 10,
    fontWeight: '800',
    color: colors.accentCyan,
    letterSpacing: 0.9,
    marginBottom: 3,
  },
  sectionTitle: {
    fontSize: 17,
    fontWeight: '800',
    color: '#FFFFFF',
    letterSpacing: -0.2,
  },
  sectionCountPill: {
    backgroundColor: 'rgba(37, 99, 235, 0.15)',
    borderWidth: 1,
    borderColor: 'rgba(59, 130, 246, 0.25)',
    paddingHorizontal: 9,
    paddingVertical: 4,
    borderRadius: 999,
  },
  sectionCountText: {
    fontSize: 11,
    fontWeight: '700',
    color: colors.textAccent,
  },

  modeCard: {
    backgroundColor: colors.card,
    borderRadius: 20,
    borderWidth: 1.5,
    borderColor: 'rgba(255, 255, 255, 0.08)',
    padding: 16,
    marginBottom: 12,
  },
  modeCardTop: {
    flexDirection: 'row',
    alignItems: 'center',
  },
  modeAvatarWrap: {
    position: 'relative',
    marginRight: 13,
  },
  modeAvatarImg: {
    width: 54,
    height: 54,
    borderRadius: 16,
    backgroundColor: colors.surface,
  },
  modeOnlineDot: {
    position: 'absolute',
    bottom: -2,
    right: -2,
    width: 13,
    height: 13,
    borderRadius: 7,
    backgroundColor: colors.success,
    borderWidth: 2,
    borderColor: colors.card,
  },
  modeMetaCol: {
    flex: 1,
    marginRight: 8,
  },
  modeBadge: {
    flexDirection: 'row',
    alignItems: 'center',
    alignSelf: 'flex-start',
    gap: 5,
    paddingHorizontal: 8,
    paddingVertical: 3,
    borderRadius: 999,
    borderWidth: 1,
    marginBottom: 5,
  },
  modeBadgeText: {
    fontSize: 9.5,
    fontWeight: '800',
    letterSpacing: 0.6,
  },
  modeTitle: {
    fontSize: 16,
    fontWeight: '800',
    color: '#FFFFFF',
  },
  modeExaminer: {
    fontSize: 11.5,
    color: colors.textSecondary,
    fontWeight: '600',
    marginTop: 2,
  },
  radioCircle: {
    width: 24,
    height: 24,
    borderRadius: 12,
    borderWidth: 1.5,
    borderColor: 'rgba(255, 255, 255, 0.22)',
    alignItems: 'center',
    justifyContent: 'center',
  },
  modeTagline: {
    fontSize: 12.5,
    color: colors.textSecondary,
    lineHeight: 18,
    marginTop: 11,
  },
  modePartsStrip: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 6,
    marginTop: 10,
    paddingTop: 10,
    borderTopWidth: 1,
    borderTopColor: 'rgba(255, 255, 255, 0.06)',
  },
  modePartsText: {
    flex: 1,
    fontSize: 11,
    fontWeight: '600',
    color: colors.textMuted,
  },

  sectionCard: {
    backgroundColor: colors.card,
    borderRadius: 20,
    borderWidth: 1,
    borderColor: 'rgba(255, 255, 255, 0.08)',
    padding: 16,
    marginTop: 8,
    marginBottom: 14,
  },
  cardHeaderRow: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'flex-start',
    marginBottom: 12,
  },
  cardTitle: {
    fontSize: 15.5,
    fontWeight: '800',
    color: '#FFFFFF',
  },
  cardSub: {
    fontSize: 12,
    color: colors.textSecondary,
    marginTop: 3,
    lineHeight: 17,
  },

  sessionTypeGrid: {
    flexDirection: 'row',
    gap: 10,
    marginTop: 12,
  },
  typeCard: {
    flex: 1,
    backgroundColor: '#080F1B',
    borderRadius: 14,
    borderWidth: 1,
    borderColor: 'rgba(255, 255, 255, 0.08)',
    padding: 12,
  },
  typeCardActive: {
    borderColor: colors.primaryLight,
    backgroundColor: 'rgba(37, 99, 235, 0.14)',
  },
  typeCardTop: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    marginBottom: 8,
  },
  typeCardTitle: {
    fontSize: 13.5,
    fontWeight: '800',
    color: '#CBD5E1',
  },
  typeCardDesc: {
    fontSize: 11,
    color: colors.textMuted,
    marginTop: 3,
    lineHeight: 15,
  },

  segmentedRow: {
    flexDirection: 'row',
    backgroundColor: '#080F1B',
    borderRadius: 14,
    padding: 4,
    gap: 6,
    borderWidth: 1,
    borderColor: 'rgba(255, 255, 255, 0.06)',
  },
  segmentBtn: {
    flex: 1,
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    paddingVertical: 9,
    borderRadius: 10,
    gap: 7,
  },
  segmentBtnActive: {
    backgroundColor: '#1B2D47',
    borderWidth: 1,
    borderColor: 'rgba(56, 189, 248, 0.35)',
  },
  segmentCode: {
    fontSize: 10,
    fontWeight: '800',
    color: colors.textMuted,
    backgroundColor: 'rgba(255, 255, 255, 0.06)',
    paddingHorizontal: 6,
    paddingVertical: 2,
    borderRadius: 5,
  },
  segmentCodeActive: {
    color: '#FFFFFF',
    backgroundColor: colors.primary,
  },
  segmentText: {
    fontSize: 12.5,
    fontWeight: '600',
    color: colors.textSecondary,
  },
  segmentTextActive: {
    color: '#FFFFFF',
    fontWeight: '700',
  },

  topicChipWrap: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: 8,
  },
  topicChip: {
    backgroundColor: '#080F1B',
    borderRadius: 999,
    borderWidth: 1,
    borderColor: 'rgba(255, 255, 255, 0.1)',
    paddingHorizontal: 13,
    paddingVertical: 8,
  },
  topicChipActive: {
    backgroundColor: 'rgba(37, 99, 235, 0.22)',
    borderColor: colors.accentCyan,
  },
  topicChipText: {
    fontSize: 12,
    fontWeight: '600',
    color: colors.textSecondary,
  },
  topicChipTextActive: {
    color: '#FFFFFF',
    fontWeight: '700',
  },

  inputLabel: {
    fontSize: 10.5,
    fontWeight: '800',
    color: '#94A3B8',
    letterSpacing: 0.7,
    marginBottom: 6,
  },
  contextLabelRow: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    marginTop: 14,
  },
  charCounter: {
    fontSize: 10.5,
    color: colors.textMuted,
    fontWeight: '600',
    marginBottom: 6,
  },
  textInput: {
    height: 46,
    borderRadius: 12,
    backgroundColor: '#080F1B',
    borderWidth: 1,
    borderColor: 'rgba(255, 255, 255, 0.09)',
    paddingHorizontal: 13,
    color: '#FFFFFF',
    fontSize: 13,
  },
  textArea: {
    height: 76,
    paddingTop: 11,
    textAlignVertical: 'top',
  },
  textInputFocused: {
    borderColor: colors.primaryLight,
  },

  ctaSectionCard: {
    backgroundColor: colors.card,
    borderRadius: 20,
    borderWidth: 1,
    borderColor: 'rgba(56, 189, 248, 0.25)',
    padding: 16,
    marginTop: 4,
  },
  summaryEyebrow: {
    fontSize: 10,
    fontWeight: '800',
    color: colors.accentCyan,
    letterSpacing: 0.8,
    marginBottom: 3,
  },
  summaryStrip: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    marginBottom: 14,
    gap: 10,
  },
  summaryStripText: {
    fontSize: 12.5,
    fontWeight: '700',
    color: '#F8FAFC',
    lineHeight: 17,
  },
  summaryStripBadge: {
    fontSize: 10,
    fontWeight: '800',
    color: colors.accentCyan,
    backgroundColor: 'rgba(37, 99, 235, 0.16)',
    paddingHorizontal: 8,
    paddingVertical: 3,
    borderRadius: 6,
  },
  startButton: {
    borderRadius: 16,
    overflow: 'hidden',
    shadowColor: colors.primary,
    shadowOffset: { width: 0, height: 6 },
    shadowOpacity: 0.45,
    shadowRadius: 14,
    elevation: 8,
  },
  buttonGradient: {
    height: 52,
    justifyContent: 'center',
    alignItems: 'center',
  },
  ctaContent: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 10,
  },
  startButtonText: {
    color: '#FFFFFF',
    fontSize: 15,
    fontWeight: '800',
    letterSpacing: 0.2,
  },
});
