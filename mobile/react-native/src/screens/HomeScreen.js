import React, { useState, useEffect } from 'react';
import {
  View,
  Text,
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

  useEffect(() => {
    loadRolesAndScenarios();
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
      {/* Top Navigation Bar (Identik dengan Web Nav) */}
      <View style={[styles.topBar, { paddingTop: Math.max(insets.top, 12) }]}>
        <View style={styles.brandRow}>
          <VoicLogo width={76} height={22} />
        </View>

        <View style={styles.rightNavRow}>
          <View style={styles.userProfilePill}>
            <Feather name="user" size={13} color={colors.accentCyan} style={{ marginRight: 5 }} />
            <Text style={styles.userGreeting} numberOfLines={1}>
              {user?.name || 'Praktikan'}
            </Text>
          </View>
        </View>
      </View>

      <ScrollView contentContainerStyle={styles.scrollContent}>
        {/* Banner Hero (Web Executive Glassmorphism) */}
        <LinearGradient
          colors={colors.heroCardGradient}
          style={styles.heroBanner}
          start={{ x: 0, y: 0 }}
          end={{ x: 1, y: 1 }}
        >
          <View style={styles.heroGlowAccent} />
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
        </LinearGradient>

        {/* Pilihan Bahasa Simulasi (Mirip Web) */}
        <View style={styles.langSelectorRow}>
          <Text style={styles.langLabel}>Bahasa Simulasi:</Text>
          <View style={styles.langPillContainer}>
            <TouchableOpacity
              style={[styles.langChip, language === 'id' && styles.langChipActive]}
              onPress={() => setLanguage('id')}
              activeOpacity={0.8}
            >
              <Text style={[styles.langText, language === 'id' && styles.langTextActive]}>
                🇮🇩 Indonesia
              </Text>
            </TouchableOpacity>
            <TouchableOpacity
              style={[styles.langChip, language === 'en' && styles.langChipActive]}
              onPress={() => setLanguage('en')}
              activeOpacity={0.8}
            >
              <Text style={[styles.langText, language === 'en' && styles.langTextActive]}>
                🇬🇧 English
              </Text>
            </TouchableOpacity>
          </View>
        </View>

        {/* Skenario Horizontal Scroll */}
        <View style={styles.sectionHeaderRow}>
          <Text style={styles.sectionTitle}>Pilih Skenario Latihan</Text>
          <Text style={styles.sectionCountBadge}>{scenarios.length} Skenario</Text>
        </View>

        <ScrollView horizontal showsHorizontalScrollIndicator={false} style={styles.scenarioList}>
          {scenarios.map((s) => {
            const isSelected = selectedScenario?.id === s.id;
            return (
              <TouchableOpacity
                key={s.id}
                onPress={() => setSelectedScenario(s)}
                style={[
                  styles.scenarioCard,
                  isSelected && styles.scenarioCardActive,
                ]}
                activeOpacity={0.8}
              >
                <Text style={styles.scenarioIcon}>{s.icon}</Text>
                <Text style={[styles.scenarioName, isSelected && { color: colors.accentCyan }]} numberOfLines={1}>
                  {s.name}
                </Text>
                <Text style={styles.scenarioBadge}>{s.badge}</Text>
              </TouchableOpacity>
            );
          })}
        </ScrollView>

        {/* Materi Konteks Opsional (Abstrak / Lowongan) */}
        <View style={styles.contextContainer}>
          <Text style={styles.contextLabel}>Materi Konteks Latihan (Opsional):</Text>
          <TextInput
            style={styles.contextInput}
            placeholder="Contoh: Judul Skripsi Sistem Rekomendasi AI / Posisi Backend Engineer..."
            placeholderTextColor={colors.textMuted}
            value={contextMaterial}
            onChangeText={setContextMaterial}
          />
        </View>

        {/* Karakter AI List */}
        <View style={[styles.sectionHeaderRow, { marginTop: 20 }]}>
          <Text style={styles.sectionTitle}>Pilih Karakter AI Penguji</Text>
          <Text style={styles.sectionCountBadge}>{roles.length} Karakter</Text>
        </View>

        {roles.map((role) => {
          const isSelected = selectedRole?.id === role.id;
          return (
            <TouchableOpacity
              key={role.id}
              onPress={() => setSelectedRole(role)}
              style={[
                styles.roleCard,
                isSelected && styles.roleCardActive,
              ]}
              activeOpacity={0.8}
            >
              <View style={[styles.roleAvatar, isSelected && styles.roleAvatarActive]}>
                <Text style={{ fontSize: 24 }}>
                  {role.role_type === 'dosen_penguji' ? '🎓' : role.role_type === 'hrd' ? '💼' : '🚀'}
                </Text>
              </View>
              <View style={styles.roleInfo}>
                <View style={styles.roleHeader}>
                  <Text style={[styles.roleName, isSelected && { color: '#FFFFFF' }]}>{role.name}</Text>
                  <View style={styles.difficultyBadge}>
                    <Text style={styles.difficultyText}>{role.difficulty_level || 'Menengah'}</Text>
                  </View>
                </View>
                <Text style={styles.roleDesc} numberOfLines={2}>
                  {role.description}
                </Text>
              </View>
            </TouchableOpacity>
          );
        })}

        <View style={{ height: 190 }} />
      </ScrollView>

      {/* Fixed Bottom CTA Bar */}
<<<<<<< HEAD
      <View style={[styles.bottomBar, { bottom: 66 + Math.max(insets.bottom, 8) }]}>
=======
      <View style={styles.bottomBar}>
        <Text style={styles.consentText}>
          Dengan memulai, kamu setuju jawabanmu diproses oleh AI di laptop penyelenggara untuk membuat rapor.
        </Text>
>>>>>>> 92db3c8749066e5006acb8ebe90461069399d8f9
        <TouchableOpacity
          style={styles.startButton}
          onPress={handleStart}
          disabled={isStarting}
          activeOpacity={0.85}
        >
          <LinearGradient
            colors={colors.buttonGradient}
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
  topBar: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    paddingHorizontal: 20,
    paddingTop: 12,
    paddingBottom: 14,
    backgroundColor: colors.surface,
    borderBottomWidth: 1,
    borderBottomColor: colors.cardBorderSubtle,
  },
  brandRow: {
    flexDirection: 'row',
    alignItems: 'center',
  },
  rightNavRow: {
    flexDirection: 'row',
    alignItems: 'center',
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
    maxWidth: 125,
  },
  userGreeting: {
    fontSize: 12,
    fontWeight: '600',
    color: colors.textPrimary,
  },
  logoutButton: {
    marginLeft: 8,
    padding: 6,
    borderRadius: 8,
    backgroundColor: colors.card,
    borderWidth: 1,
    borderColor: colors.cardBorderSubtle,
  },
  scrollContent: {
    padding: 20,
  },
  heroBanner: {
    padding: 20,
    borderRadius: 18,
    borderWidth: 1.5,
    borderColor: colors.cardBorder,
    marginBottom: 16,
    overflow: 'hidden',
    shadowColor: colors.primary,
    shadowOffset: { width: 0, height: 8 },
    shadowOpacity: 0.25,
    shadowRadius: 20,
    elevation: 8,
  },
  heroGlowAccent: {
    position: 'absolute',
    top: -30,
    right: -30,
    width: 120,
    height: 120,
    borderRadius: 60,
    backgroundColor: 'rgba(37, 99, 235, 0.18)',
  },
  heroTagRow: {
    marginBottom: 8,
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
    fontSize: 17,
    fontWeight: 'bold',
    color: '#FFFFFF',
    marginBottom: 6,
  },
  heroSubtitle: {
    fontSize: 12,
    color: colors.textSecondary,
    lineHeight: 18,
  },
  langSelectorRow: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    marginBottom: 18,
    padding: 12,
    borderRadius: 14,
    backgroundColor: colors.card,
    borderWidth: 1,
    borderColor: colors.cardBorderSubtle,
  },
  langLabel: {
    fontSize: 12,
    fontWeight: 'bold',
    color: '#FFFFFF',
  },
  langPillContainer: {
    flexDirection: 'row',
  },
  langChip: {
    paddingHorizontal: 10,
    paddingVertical: 4,
    borderRadius: 8,
    backgroundColor: colors.surface,
    marginLeft: 6,
    borderWidth: 1,
    borderColor: colors.cardBorderSubtle,
  },
  langChipActive: {
    borderColor: colors.primary,
    backgroundColor: 'rgba(37, 99, 235, 0.25)',
  },
  langText: {
    fontSize: 11,
    color: colors.textSecondary,
    fontWeight: '600',
  },
  langTextActive: {
    color: colors.accentCyan,
    fontWeight: 'bold',
  },
  sectionHeaderRow: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    marginBottom: 12,
  },
  sectionTitle: {
    fontSize: 15,
    fontWeight: 'bold',
    color: '#FFFFFF',
  },
  sectionCountBadge: {
    fontSize: 11,
    color: colors.textAccent,
    fontWeight: '600',
  },
  scenarioList: {
    flexDirection: 'row',
  },
  scenarioCard: {
    width: 130,
    padding: 14,
    borderRadius: 14,
    backgroundColor: colors.card,
    borderWidth: 1.5,
    borderColor: colors.cardBorderSubtle,
    marginRight: 10,
  },
  scenarioCardActive: {
    borderColor: colors.primaryLight,
    backgroundColor: 'rgba(37, 99, 235, 0.16)',
    shadowColor: colors.primary,
    shadowOffset: { width: 0, height: 4 },
    shadowOpacity: 0.4,
    shadowRadius: 10,
    elevation: 4,
  },
  scenarioIcon: {
    fontSize: 22,
    marginBottom: 8,
  },
  scenarioName: {
    fontSize: 12,
    fontWeight: 'bold',
    color: '#FFFFFF',
  },
  scenarioBadge: {
    fontSize: 10,
    color: colors.textSecondary,
    marginTop: 3,
  },
  contextContainer: {
    marginTop: 18,
  },
  contextLabel: {
    fontSize: 12,
    fontWeight: 'bold',
    color: '#FFFFFF',
    marginBottom: 6,
  },
  contextInput: {
    height: 44,
    borderRadius: 12,
    backgroundColor: colors.card,
    borderWidth: 1,
    borderColor: colors.cardBorderSubtle,
    paddingHorizontal: 12,
    color: '#FFFFFF',
    fontSize: 12,
  },
  roleCard: {
    flexDirection: 'row',
    padding: 14,
    borderRadius: 14,
    backgroundColor: colors.card,
    borderWidth: 1.5,
    borderColor: colors.cardBorderSubtle,
    marginBottom: 12,
    alignItems: 'center',
  },
  roleCardActive: {
    borderColor: colors.primaryLight,
    backgroundColor: 'rgba(37, 99, 235, 0.16)',
    shadowColor: colors.primary,
    shadowOffset: { width: 0, height: 4 },
    shadowOpacity: 0.35,
    shadowRadius: 12,
    elevation: 5,
  },
  roleAvatar: {
    width: 48,
    height: 48,
    borderRadius: 24,
    backgroundColor: colors.surface,
    justifyContent: 'center',
    alignItems: 'center',
    marginRight: 12,
    borderWidth: 1,
    borderColor: colors.cardBorderSubtle,
  },
  roleAvatarActive: {
    borderColor: colors.accentCyan,
    backgroundColor: 'rgba(37, 99, 235, 0.25)',
  },
  roleInfo: {
    flex: 1,
  },
  roleHeader: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    marginBottom: 4,
  },
  roleName: {
    fontSize: 14,
    fontWeight: 'bold',
    color: '#FFFFFF',
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
    fontSize: 11,
    color: colors.textSecondary,
    lineHeight: 16,
  },
  bottomBar: {
    position: 'absolute',
    bottom: 74,
    left: 0,
    right: 0,
    paddingHorizontal: 20,
    paddingTop: 14,
    paddingBottom: 12,
    backgroundColor: colors.surface,
    borderTopWidth: 1,
    borderTopColor: colors.cardBorderSubtle,
  },
  startButton: {
    borderRadius: 14,
    overflow: 'hidden',
    shadowColor: colors.primary,
    shadowOffset: { width: 0, height: 6 },
    shadowOpacity: 0.5,
    shadowRadius: 16,
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
  },
  startButtonText: {
    color: '#FFFFFF',
    fontSize: 15,
    fontWeight: 'bold',
    letterSpacing: 0.5,
  },
  consentText: {
    color: colors.textSecondary || '#64748B',
    fontSize: 11,
    textAlign: 'center',
    marginBottom: 8,
  },
});
