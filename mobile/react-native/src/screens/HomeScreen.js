import React, { useState, useEffect, useRef } from 'react';
import {
  View,
  Text,
  StyleSheet,
  ScrollView,
  TouchableOpacity,
  TextInput,
  ActivityIndicator,
  Alert,
  Animated,
} from 'react-native';
import { LinearGradient } from 'expo-linear-gradient';
import { Feather } from '@expo/vector-icons';
import { useSafeAreaInsets } from 'react-native-safe-area-context';
import { colors } from '../constants/theme';
import { apiService } from '../services/apiService';
import VoicLogo from '../components/VoicLogo';

// Avatar color per role_type
const ROLE_AVATAR_COLORS = {
  dosen_penguji: { grad: ['#2563EB', '#38BDF8'], emoji: '🎓' },
  hrd:           { grad: ['#059669', '#10B981'], emoji: '💼' },
  investor:      { grad: ['#D97706', '#F59E0B'], emoji: '🚀' },
};

// Scenario gradient per badge type
const SCENARIO_GRADIENTS = {
  default: ['#0F1C2F', '#1B2D47'],
  Sidang:  ['#1D3B6A', '#0F1C2F'],
  Kerja:   ['#0F2D2A', '#0F1C2F'],
  Bisnis:  ['#2D1A0F', '#0F1C2F'],
};

function getScenarioGradient(badge) {
  if (!badge) return SCENARIO_GRADIENTS.default;
  if (badge.includes('Sidang') || badge.includes('Dosen')) return SCENARIO_GRADIENTS.Sidang;
  if (badge.includes('Kerja') || badge.includes('HRD')) return SCENARIO_GRADIENTS.Kerja;
  if (badge.includes('Bisnis') || badge.includes('Investor')) return SCENARIO_GRADIENTS.Bisnis;
  return SCENARIO_GRADIENTS.default;
}

function getUserInitials(name) {
  if (!name) return 'P';
  return name
    .split(' ')
    .slice(0, 2)
    .map((w) => w[0]?.toUpperCase() || '')
    .join('');
}

export default function HomeScreen({ user, onStartPractice }) {
  const insets = useSafeAreaInsets();
  const [isLoading, setIsLoading] = useState(true);
  const [roles, setRoles] = useState([]);
  const [scenarios, setScenarios] = useState([]);
  const [selectedRole, setSelectedRole] = useState(null);
  const [selectedScenario, setSelectedScenario] = useState(null);
  const [language, setLanguage] = useState('id'); // 'id' | 'en'
  const [contextMaterial, setContextMaterial] = useState('');
  const [isStarting, setIsStarting] = useState(false);
  const [contextFocused, setContextFocused] = useState(false);

  // Animated waveform rotation
  const waveAnim = useRef(new Animated.Value(0)).current;
  // Animated mic pulse
  const micPulse = useRef(new Animated.Value(1)).current;

  useEffect(() => {
    loadRolesAndScenarios();

    // Waveform spin loop
    Animated.loop(
      Animated.timing(waveAnim, {
        toValue: 1,
        duration: 6000,
        useNativeDriver: true,
      })
    ).start();

    // Mic pulse loop
    Animated.loop(
      Animated.sequence([
        Animated.timing(micPulse, { toValue: 1.18, duration: 700, useNativeDriver: true }),
        Animated.timing(micPulse, { toValue: 1, duration: 700, useNativeDriver: true }),
      ])
    ).start();
  }, []);

  const loadRolesAndScenarios = async () => {
    try {
      const res = await apiService.getRoles();
      if (res.success) {
        setRoles(res.roles || []);
        setScenarios(res.scenarios || []);
        if (res.roles && res.roles.length > 0) setSelectedRole(res.roles[0]);
        if (res.scenarios && res.scenarios.length > 0) setSelectedScenario(res.scenarios[0]);
      }
    } catch (err) {
      console.log('Error fetching roles:', err);
    } finally {
      setIsLoading(false);
    }
  };

  const handleStart = async () => {
    if (!selectedRole) return;
    setIsStarting(true);
    try {
      const res = await apiService.startExam(apiService.modeForRole(selectedRole.role_type), {
        topic: selectedScenario ? selectedScenario.name : null,
        language,
        contextMaterial: contextMaterial.trim() || null,
      });
      if (res.success) {
        onStartPractice({
          sessionId: res.session_id,
          role: selectedRole,
          scenario: selectedScenario,
          language: language,
          initialGreeting: res.examiner_message?.text || '',
          initialAudioUrl: res.examiner_message?.audio_url,
          partTitle: res.part_title,
        });
      }
    } catch (err) {
      Alert.alert('Gagal Memulai Sesi', 'Pastikan koneksi jaringan Laravel aktif.');
    } finally {
      setIsStarting(false);
    }
  };

  const waveRotate = waveAnim.interpolate({
    inputRange: [0, 1],
    outputRange: ['0deg', '360deg'],
  });

  if (isLoading) {
    return (
      <View style={styles.centerContainer}>
        <ActivityIndicator size="large" color={colors.primary} />
        <Text style={styles.loadingText}>Menyiapkan Karakter AI VOIC...</Text>
      </View>
    );
  }

  return (
    <View style={styles.container}>
      {/* ── Top Navigation Bar ── */}
      <View style={[styles.topBar, { paddingTop: Math.max(insets.top, 12) }]}>
        <View style={styles.brandRow}>
          <VoicLogo width={76} height={22} />
        </View>

        <View style={styles.rightNavRow}>
          {/* User avatar circle with initials */}
          <View style={styles.userAvatarCircle}>
            <Text style={styles.userAvatarInitials}>{getUserInitials(user?.name)}</Text>
          </View>
          <View style={styles.userProfilePill}>
            <Text style={styles.userGreeting} numberOfLines={1}>
              {user?.name || 'Praktikan'}
            </Text>
          </View>
          {/* Logout icon */}
          <TouchableOpacity style={styles.logoutButton} activeOpacity={0.7}>
            <Feather name="log-out" size={16} color={colors.textSecondary} />
          </TouchableOpacity>
        </View>
      </View>

      {/* Subtle gradient bar under topbar */}
      <LinearGradient
        colors={['rgba(37,99,235,0.18)', 'transparent']}
        style={styles.topBarGradientBar}
        start={{ x: 0, y: 0 }}
        end={{ x: 0, y: 1 }}
      />

      <ScrollView contentContainerStyle={styles.scrollContent} showsVerticalScrollIndicator={false}>
        {/* ── Hero Banner ── */}
        <LinearGradient
          colors={colors.heroCardGradient}
          style={styles.heroBanner}
          start={{ x: 0, y: 0 }}
          end={{ x: 1, y: 1 }}
        >
          <View style={styles.heroGlowAccent} />
          <View style={styles.heroGlowAccent2} />

          {/* Animated waveform emoji */}
          <Animated.View style={[styles.heroWaveform, { transform: [{ rotate: waveRotate }] }]}>
            <Text style={styles.heroWaveEmoji}>〰️</Text>
          </Animated.View>

          <View style={styles.heroTagRow}>
            <View style={styles.heroTag}>
              <View style={styles.heroTagDot} />
              <Text style={styles.heroTagText}>Simulasi Bicara & Gestur AI</Text>
            </View>
          </View>

          <Text style={styles.heroTitle}>Siap Latihan Bicara Hari Ini?</Text>
          <Text style={styles.heroSubtitle}>
            Pilih karakter AI penguji dan skenario latihan untuk melatih artikulasi vokal, kontak mata, dan ketenangan gestur Anda.
          </Text>

          {/* Stats row */}
          <View style={styles.heroStatsRow}>
            <View style={styles.heroStatItem}>
              <Text style={styles.heroStatValue}>3</Text>
              <Text style={styles.heroStatLabel}>Moda Ujian</Text>
            </View>
            <View style={styles.heroStatDivider} />
            <View style={styles.heroStatItem}>
              <Text style={styles.heroStatValue}>AI</Text>
              <Text style={styles.heroStatLabel}>Realtime</Text>
            </View>
            <View style={styles.heroStatDivider} />
            <View style={styles.heroStatItem}>
              <Text style={styles.heroStatValue}>⚡</Text>
              <Text style={styles.heroStatLabel}>Skor Instan</Text>
            </View>
          </View>
        </LinearGradient>

        {/* ── Language Selector (tab-style) ── */}
        <View style={styles.langSelectorContainer}>
          <Text style={styles.langLabel}>Bahasa Simulasi</Text>
          <View style={styles.langTabRow}>
            <TouchableOpacity
              style={[styles.langTab, language === 'id' && styles.langTabActive]}
              onPress={() => setLanguage('id')}
              activeOpacity={0.8}
            >
              <Text style={styles.langTabFlag}>🇮🇩</Text>
              <Text style={[styles.langTabText, language === 'id' && styles.langTabTextActive]}>
                Indonesia
              </Text>
              {language === 'id' && <View style={styles.langActiveIndicator} />}
            </TouchableOpacity>
            <TouchableOpacity
              style={[styles.langTab, language === 'en' && styles.langTabActive]}
              onPress={() => setLanguage('en')}
              activeOpacity={0.8}
            >
              <Text style={styles.langTabFlag}>🇬🇧</Text>
              <Text style={[styles.langTabText, language === 'en' && styles.langTabTextActive]}>
                English
              </Text>
              {language === 'en' && <View style={styles.langActiveIndicator} />}
            </TouchableOpacity>
          </View>
        </View>

        {/* ── Scenario Section ── */}
        <View style={styles.sectionHeaderRow}>
          <View style={styles.sectionTitleGroup}>
            <LinearGradient
              colors={['#2563EB', '#38BDF8']}
              style={styles.sectionTitleBar}
              start={{ x: 0, y: 0 }}
              end={{ x: 1, y: 0 }}
            />
            <Text style={styles.sectionTitle}>Pilih Skenario Latihan</Text>
          </View>
          <Text style={styles.sectionCountBadge}>{scenarios.length} Skenario</Text>
        </View>

        <ScrollView horizontal showsHorizontalScrollIndicator={false} style={styles.scenarioList}>
          {scenarios.map((s) => {
            const isSelected = selectedScenario?.id === s.id;
            return (
              <TouchableOpacity
                key={s.id}
                onPress={() => setSelectedScenario(s)}
                activeOpacity={0.8}
                style={[styles.scenarioCard, isSelected && styles.scenarioCardActive]}
              >
                <LinearGradient
                  colors={isSelected ? ['rgba(37,99,235,0.35)', 'rgba(37,99,235,0.1)'] : getScenarioGradient(s.badge)}
                  style={styles.scenarioGradientFill}
                  start={{ x: 0, y: 0 }}
                  end={{ x: 1, y: 1 }}
                >
                  <Text style={styles.scenarioIcon}>{s.icon}</Text>
                  <Text
                    style={[styles.scenarioName, isSelected && { color: colors.accentCyan }]}
                    numberOfLines={1}
                  >
                    {s.name}
                  </Text>
                  <Text style={styles.scenarioBadge}>{s.badge}</Text>
                  {isSelected && (
                    <View style={styles.scenarioCheckBadge}>
                      <Feather name="check-circle" size={14} color={colors.accentCyan} />
                    </View>
                  )}
                </LinearGradient>
              </TouchableOpacity>
            );
          })}
        </ScrollView>

        {/* ── Context Input ── */}
        <View style={styles.contextContainer}>
          <View style={styles.contextLabelRow}>
            <Feather name="book-open" size={13} color={colors.accentCyan} style={{ marginRight: 6 }} />
            <Text style={styles.contextLabel}>Materi Konteks Latihan (Opsional)</Text>
          </View>
          <TextInput
            style={[styles.contextInput, contextFocused && styles.contextInputFocused]}
            placeholder="Contoh: Judul Skripsi Sistem Rekomendasi AI / Posisi Backend Engineer..."
            placeholderTextColor={colors.textMuted}
            value={contextMaterial}
            onChangeText={setContextMaterial}
            onFocus={() => setContextFocused(true)}
            onBlur={() => setContextFocused(false)}
            multiline={false}
            maxLength={200}
          />
          <Text style={styles.contextCharCounter}>{contextMaterial.length}/200</Text>
        </View>

        {/* ── Role Section ── */}
        <View style={[styles.sectionHeaderRow, { marginTop: 24 }]}>
          <View style={styles.sectionTitleGroup}>
            <LinearGradient
              colors={['#2563EB', '#38BDF8']}
              style={styles.sectionTitleBar}
              start={{ x: 0, y: 0 }}
              end={{ x: 1, y: 0 }}
            />
            <Text style={styles.sectionTitle}>Pilih Karakter AI Penguji</Text>
          </View>
          <Text style={styles.sectionCountBadge}>{roles.length} Karakter</Text>
        </View>

        {roles.map((role) => {
          const isSelected = selectedRole?.id === role.id;
          const avatarConfig = ROLE_AVATAR_COLORS[role.role_type] || {
            grad: ['#2563EB', '#1D4ED8'],
            emoji: '🤖',
          };
          return (
            <TouchableOpacity
              key={role.id}
              onPress={() => setSelectedRole(role)}
              style={[styles.roleCard, isSelected && styles.roleCardActive]}
              activeOpacity={0.8}
            >
              {/* Gradient avatar circle */}
              <LinearGradient
                colors={avatarConfig.grad}
                style={styles.roleAvatar}
                start={{ x: 0, y: 0 }}
                end={{ x: 1, y: 1 }}
              >
                <Text style={{ fontSize: 24 }}>{avatarConfig.emoji}</Text>
              </LinearGradient>

              <View style={styles.roleInfo}>
                <View style={styles.roleHeader}>
                  <Text style={[styles.roleName, isSelected && { color: '#FFFFFF' }]}>
                    {role.name}
                  </Text>
                  <View style={styles.difficultyBadge}>
                    <Text style={styles.difficultyText}>{role.difficulty_level || 'Menengah'}</Text>
                  </View>
                </View>
                <Text style={styles.roleDesc} numberOfLines={3}>
                  {role.description}
                </Text>
              </View>

              {/* Selected badge */}
              {isSelected && (
                <View style={styles.roleSelectedBadge}>
                  <Feather name="check" size={11} color="#fff" />
                  <Text style={styles.roleSelectedBadgeText}>Selected</Text>
                </View>
              )}
            </TouchableOpacity>
          );
        })}

        <View style={{ height: 220 }} />
      </ScrollView>

      {/* ── Fixed Bottom CTA Bar ── */}
      <View style={[styles.bottomBar, { bottom: 66 + Math.max(insets.bottom, 8) }]}>
        <Text style={styles.consentText}>
          Dengan memulai, kamu setuju jawabanmu diproses oleh AI di laptop penyelenggara untuk membuat rapor.
        </Text>

        {/* "AI Siap Menguji" label row */}
        <View style={styles.ctaLabelRow}>
          <Animated.View style={{ transform: [{ scale: micPulse }] }}>
            <Feather name="mic" size={14} color={colors.accentCyan} />
          </Animated.View>
          <Text style={styles.ctaReadyLabel}>AI Siap Menguji</Text>
        </View>

        <TouchableOpacity
          style={styles.startButton}
          onPress={handleStart}
          disabled={isStarting}
          activeOpacity={0.85}
        >
          <LinearGradient
            colors={['#3B82F6', '#1D4ED8', '#1E40AF']}
            start={{ x: 0, y: 0 }}
            end={{ x: 1, y: 0 }}
            style={styles.buttonGradient}
          >
            {isStarting ? (
              <ActivityIndicator color="#FFFFFF" />
            ) : (
              <View style={styles.ctaContent}>
                <Feather name="mic" size={18} color="#FFFFFF" style={{ marginRight: 8 }} />
                <Text style={styles.startButtonText}>Mulai Sesi Latihan</Text>
              </View>
            )}
          </LinearGradient>
        </TouchableOpacity>
      </View>
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
  },

  // ── TopBar ──
  topBar: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    paddingHorizontal: 20,
    paddingTop: 12,
    paddingBottom: 14,
    backgroundColor: colors.surface,
    zIndex: 10,
  },
  topBarGradientBar: {
    height: 6,
    marginTop: -1,
  },
  brandRow: {
    flexDirection: 'row',
    alignItems: 'center',
  },
  rightNavRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 8,
  },
  userAvatarCircle: {
    width: 32,
    height: 32,
    borderRadius: 16,
    backgroundColor: colors.primary,
    justifyContent: 'center',
    alignItems: 'center',
    borderWidth: 1.5,
    borderColor: colors.accentCyan,
  },
  userAvatarInitials: {
    fontSize: 11,
    fontWeight: '800',
    color: '#FFFFFF',
    letterSpacing: 0.5,
  },
  userProfilePill: {
    flexDirection: 'row',
    alignItems: 'center',
    paddingHorizontal: 10,
    paddingVertical: 5,
    borderRadius: 20,
    backgroundColor: colors.card,
    borderWidth: 1,
    borderColor: colors.cardBorderSubtle,
    maxWidth: 110,
  },
  userGreeting: {
    fontSize: 12,
    fontWeight: '600',
    color: colors.textPrimary,
  },
  logoutButton: {
    padding: 7,
    borderRadius: 10,
    backgroundColor: colors.card,
    borderWidth: 1,
    borderColor: colors.cardBorderSubtle,
  },

  // ── Scroll ──
  scrollContent: {
    padding: 20,
  },

  // ── Hero Banner ──
  heroBanner: {
    padding: 22,
    borderRadius: 18,
    borderWidth: 1.5,
    borderColor: colors.cardBorder,
    marginBottom: 18,
    overflow: 'hidden',
    shadowColor: colors.primary,
    shadowOffset: { width: 0, height: 10 },
    shadowOpacity: 0.35,
    shadowRadius: 24,
    elevation: 10,
  },
  heroGlowAccent: {
    position: 'absolute',
    top: -40,
    right: -40,
    width: 140,
    height: 140,
    borderRadius: 70,
    backgroundColor: 'rgba(37, 99, 235, 0.22)',
  },
  heroGlowAccent2: {
    position: 'absolute',
    bottom: -30,
    left: -20,
    width: 100,
    height: 100,
    borderRadius: 50,
    backgroundColor: 'rgba(56, 189, 248, 0.10)',
  },
  heroWaveform: {
    position: 'absolute',
    top: 14,
    right: 16,
    opacity: 0.35,
  },
  heroWaveEmoji: {
    fontSize: 42,
  },
  heroTagRow: {
    marginBottom: 10,
  },
  heroTag: {
    flexDirection: 'row',
    alignItems: 'center',
    alignSelf: 'flex-start',
    paddingHorizontal: 9,
    paddingVertical: 3,
    borderRadius: 12,
    backgroundColor: 'rgba(37, 99, 235, 0.2)',
    borderWidth: 1,
    borderColor: 'rgba(59, 130, 246, 0.35)',
  },
  heroTagDot: {
    width: 5,
    height: 5,
    borderRadius: 2.5,
    backgroundColor: colors.accentCyan,
    marginRight: 6,
  },
  heroTagText: {
    fontSize: 10,
    fontWeight: '700',
    color: colors.accentCyan,
  },
  heroTitle: {
    fontSize: 22,
    fontWeight: 'bold',
    color: '#FFFFFF',
    marginBottom: 8,
    letterSpacing: 0.2,
  },
  heroSubtitle: {
    fontSize: 12,
    color: colors.textSecondary,
    lineHeight: 18,
    marginBottom: 16,
  },
  heroStatsRow: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: 'rgba(255,255,255,0.05)',
    borderRadius: 12,
    paddingVertical: 10,
    paddingHorizontal: 14,
    borderWidth: 1,
    borderColor: 'rgba(59,130,246,0.15)',
  },
  heroStatItem: {
    flex: 1,
    alignItems: 'center',
  },
  heroStatValue: {
    fontSize: 15,
    fontWeight: '800',
    color: colors.accentCyan,
  },
  heroStatLabel: {
    fontSize: 10,
    color: colors.textSecondary,
    marginTop: 2,
  },
  heroStatDivider: {
    width: 1,
    height: 28,
    backgroundColor: 'rgba(59,130,246,0.25)',
  },

  // ── Language Selector ──
  langSelectorContainer: {
    marginBottom: 20,
    backgroundColor: colors.card,
    borderRadius: 18,
    borderWidth: 1,
    borderColor: colors.cardBorderSubtle,
    padding: 14,
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 4 },
    shadowOpacity: 0.2,
    shadowRadius: 10,
    elevation: 4,
  },
  langLabel: {
    fontSize: 12,
    fontWeight: '700',
    color: colors.textSecondary,
    marginBottom: 10,
    textTransform: 'uppercase',
    letterSpacing: 0.6,
  },
  langTabRow: {
    flexDirection: 'row',
    gap: 10,
  },
  langTab: {
    flex: 1,
    alignItems: 'center',
    paddingVertical: 12,
    borderRadius: 12,
    backgroundColor: colors.surface,
    borderWidth: 1,
    borderColor: colors.cardBorderSubtle,
    position: 'relative',
    overflow: 'hidden',
  },
  langTabActive: {
    borderColor: colors.primary,
    backgroundColor: 'rgba(37, 99, 235, 0.15)',
  },
  langTabFlag: {
    fontSize: 24,
    marginBottom: 4,
  },
  langTabText: {
    fontSize: 12,
    fontWeight: '600',
    color: colors.textSecondary,
  },
  langTabTextActive: {
    color: colors.accentCyan,
    fontWeight: '700',
  },
  langActiveIndicator: {
    position: 'absolute',
    bottom: 0,
    left: '20%',
    right: '20%',
    height: 3,
    borderRadius: 2,
    backgroundColor: colors.accentCyan,
  },

  // ── Section Headers ──
  sectionHeaderRow: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    marginBottom: 14,
  },
  sectionTitleGroup: {
    flexDirection: 'row',
    alignItems: 'center',
  },
  sectionTitleBar: {
    width: 4,
    height: 18,
    borderRadius: 2,
    marginRight: 8,
  },
  sectionTitle: {
    fontSize: 16,
    fontWeight: 'bold',
    color: '#FFFFFF',
    letterSpacing: 0.1,
  },
  sectionCountBadge: {
    fontSize: 11,
    color: colors.textAccent,
    fontWeight: '600',
    backgroundColor: 'rgba(37,99,235,0.15)',
    paddingHorizontal: 8,
    paddingVertical: 3,
    borderRadius: 10,
    borderWidth: 1,
    borderColor: 'rgba(59,130,246,0.2)',
  },

  // ── Scenario Cards ──
  scenarioList: {
    flexDirection: 'row',
    marginBottom: 4,
  },
  scenarioCard: {
    width: 140,
    height: 160,
    borderRadius: 18,
    borderWidth: 1.5,
    borderColor: colors.cardBorderSubtle,
    marginRight: 12,
    overflow: 'hidden',
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 4 },
    shadowOpacity: 0.25,
    shadowRadius: 10,
    elevation: 4,
  },
  scenarioCardActive: {
    borderColor: colors.primaryLight,
    shadowColor: colors.primary,
    shadowOffset: { width: 0, height: 6 },
    shadowOpacity: 0.5,
    shadowRadius: 14,
    elevation: 6,
  },
  scenarioGradientFill: {
    flex: 1,
    padding: 14,
    justifyContent: 'flex-end',
    position: 'relative',
  },
  scenarioIcon: {
    fontSize: 28,
    marginBottom: 10,
  },
  scenarioName: {
    fontSize: 13,
    fontWeight: 'bold',
    color: '#FFFFFF',
    marginBottom: 4,
  },
  scenarioBadge: {
    fontSize: 10,
    color: colors.textSecondary,
  },
  scenarioCheckBadge: {
    position: 'absolute',
    top: 10,
    right: 10,
  },

  // ── Context Input ──
  contextContainer: {
    marginTop: 20,
    marginBottom: 4,
  },
  contextLabelRow: {
    flexDirection: 'row',
    alignItems: 'center',
    marginBottom: 8,
  },
  contextLabel: {
    fontSize: 13,
    fontWeight: '700',
    color: '#FFFFFF',
  },
  contextInput: {
    height: 50,
    borderRadius: 18,
    backgroundColor: colors.card,
    borderWidth: 1.5,
    borderColor: colors.cardBorderSubtle,
    paddingHorizontal: 14,
    color: '#FFFFFF',
    fontSize: 13,
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 2 },
    shadowOpacity: 0.15,
    shadowRadius: 6,
    elevation: 2,
  },
  contextInputFocused: {
    borderColor: colors.primary,
    shadowColor: colors.primary,
    shadowOpacity: 0.3,
    shadowRadius: 10,
    elevation: 4,
  },
  contextCharCounter: {
    textAlign: 'right',
    fontSize: 10,
    color: colors.textMuted,
    marginTop: 4,
    marginRight: 4,
  },

  // ── Role Cards ──
  roleCard: {
    flexDirection: 'row',
    padding: 16,
    borderRadius: 18,
    backgroundColor: colors.card,
    borderWidth: 1.5,
    borderColor: colors.cardBorderSubtle,
    marginBottom: 14,
    alignItems: 'center',
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 4 },
    shadowOpacity: 0.2,
    shadowRadius: 10,
    elevation: 4,
  },
  roleCardActive: {
    borderColor: colors.primaryLight,
    backgroundColor: 'rgba(37, 99, 235, 0.14)',
    shadowColor: colors.primary,
    shadowOffset: { width: 0, height: 6 },
    shadowOpacity: 0.4,
    shadowRadius: 16,
    elevation: 7,
  },
  roleAvatar: {
    width: 56,
    height: 56,
    borderRadius: 28,
    justifyContent: 'center',
    alignItems: 'center',
    marginRight: 14,
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 3 },
    shadowOpacity: 0.3,
    shadowRadius: 8,
    elevation: 4,
  },
  roleInfo: {
    flex: 1,
  },
  roleHeader: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    marginBottom: 6,
  },
  roleName: {
    fontSize: 15,
    fontWeight: 'bold',
    color: '#FFFFFF',
    flex: 1,
    marginRight: 6,
  },
  difficultyBadge: {
    paddingHorizontal: 7,
    paddingVertical: 2,
    borderRadius: 6,
    backgroundColor: 'rgba(59, 130, 246, 0.15)',
    borderWidth: 1,
    borderColor: 'rgba(59, 130, 246, 0.3)',
  },
  difficultyText: {
    fontSize: 10,
    fontWeight: '600',
    color: colors.accentCyan,
  },
  roleDesc: {
    fontSize: 12,
    color: colors.textSecondary,
    lineHeight: 17,
  },
  roleSelectedBadge: {
    flexDirection: 'row',
    alignItems: 'center',
    position: 'absolute',
    bottom: 10,
    right: 12,
    backgroundColor: colors.primary,
    paddingHorizontal: 7,
    paddingVertical: 3,
    borderRadius: 10,
    gap: 3,
  },
  roleSelectedBadgeText: {
    fontSize: 10,
    fontWeight: '700',
    color: '#FFFFFF',
  },

  // ── Bottom CTA Bar ──
  bottomBar: {
    position: 'absolute',
    left: 0,
    right: 0,
    paddingHorizontal: 20,
    paddingTop: 12,
    paddingBottom: 12,
    backgroundColor: colors.surface,
    borderTopWidth: 1,
    borderTopColor: colors.cardBorderSubtle,
  },
  ctaLabelRow: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    marginBottom: 8,
    gap: 6,
  },
  ctaReadyLabel: {
    fontSize: 12,
    fontWeight: '700',
    color: colors.accentCyan,
    letterSpacing: 0.4,
  },
  startButton: {
    borderRadius: 18,
    overflow: 'hidden',
    shadowColor: colors.primary,
    shadowOffset: { width: 0, height: 8 },
    shadowOpacity: 0.6,
    shadowRadius: 20,
    elevation: 10,
  },
  buttonGradient: {
    height: 60,
    justifyContent: 'center',
    alignItems: 'center',
  },
  ctaContent: {
    flexDirection: 'row',
    alignItems: 'center',
  },
  startButtonText: {
    color: '#FFFFFF',
    fontSize: 16,
    fontWeight: 'bold',
    letterSpacing: 0.5,
  },
  consentText: {
    color: colors.textSecondary || '#64748B',
    fontSize: 10,
    textAlign: 'center',
    marginBottom: 6,
    lineHeight: 14,
  },
});
