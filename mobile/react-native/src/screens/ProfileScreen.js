import React, { useEffect, useState } from 'react';
import {
  ActivityIndicator,
  Alert,
  ScrollView,
  StyleSheet,
  Text,
  TouchableOpacity,
  View,
} from 'react-native';
import { LinearGradient } from 'expo-linear-gradient';
import { Feather } from '@expo/vector-icons';
import { useSafeAreaInsets } from 'react-native-safe-area-context';
import { colors } from '../constants/theme';
import { apiService } from '../services/apiService';
import VoicLogo from '../components/VoicLogo';

export default function ProfileScreen({ user, onLogout }) {
  const insets = useSafeAreaInsets();
  const [profile, setProfile] = useState(user || {});
  const [isLoggingOut, setIsLoggingOut] = useState(false);

  useEffect(() => {
    let isMounted = true;

    apiService
      .getCurrentUser()
      .then((response) => {
        if (isMounted && response.success && response.user) {
          setProfile(response.user);
        }
      })
      .catch(() => {});

    return () => {
      isMounted = false;
    };
  }, []);

  const confirmLogout = () => {
    Alert.alert('Keluar dari VOIC?', 'Anda perlu masuk kembali untuk melanjutkan latihan.', [
      { text: 'Batal', style: 'cancel' },
      { text: 'Keluar', style: 'destructive', onPress: handleLogout },
    ]);
  };

  const handleLogout = async () => {
    setIsLoggingOut(true);
    try {
      await apiService.logout();
    } catch (error) {
    } finally {
      setIsLoggingOut(false);
      onLogout();
    }
  };

  const stats = profile.stats || {};

  return (
    <View style={styles.container}>
      <View style={[styles.header, { paddingTop: Math.max(insets.top, 12) }]}>
        <VoicLogo width={84} height={24} />
        <View style={styles.headerBadge}>
          <Text style={styles.headerLabel}>PROFIL PESERTA</Text>
        </View>
      </View>

      <ScrollView contentContainerStyle={styles.content} showsVerticalScrollIndicator={false}>
        {/* Executive Identity Card */}
        <LinearGradient
          colors={['#0F1C2F', '#080F1B']}
          style={styles.identityCard}
          start={{ x: 0, y: 0 }}
          end={{ x: 1, y: 1 }}
        >
          <View style={styles.profileRow}>
            <LinearGradient
              colors={['#2563EB', '#1D4ED8']}
              style={styles.avatar}
              start={{ x: 0, y: 0 }}
              end={{ x: 1, y: 1 }}
            >
              <Text style={styles.avatarText}>
                {(profile.name || 'V').trim().charAt(0).toUpperCase()}
              </Text>
            </LinearGradient>

            <View style={styles.profileInfo}>
              <View style={styles.verifiedRow}>
                <Text style={styles.name} numberOfLines={1}>
                  {profile.name || 'Pengguna VOIC'}
                </Text>
                <View style={styles.verifiedBadge}>
                  <Feather name="check-circle" size={12} color={colors.success} />
                  <Text style={styles.verifiedText}>Aktif</Text>
                </View>
              </View>
              <Text style={styles.email} numberOfLines={1}>
                {profile.email || 'Email belum tersedia'}
              </Text>
            </View>
          </View>

          {/* 2-Column Bento KPI */}
          <View style={styles.statsGrid}>
            <View style={styles.statBox}>
              <View style={styles.statIconWrap}>
                <Feather name="layers" size={14} color={colors.accentCyan} />
              </View>
              <Text style={styles.statValue}>
                {stats.completed_sessions ?? stats.total_sessions ?? 0}
              </Text>
              <Text style={styles.statLabel}>TOTAL SESI SELESAI</Text>
            </View>

            <View style={styles.statBox}>
              <View style={styles.statIconWrap}>
                <Feather name="award" size={14} color="#FBBF24" />
              </View>
              <Text style={styles.statValue}>{Number(stats.avg_score || 0).toFixed(1)}</Text>
              <Text style={styles.statLabel}>RATA-RATA SKOR</Text>
            </View>
          </View>
        </LinearGradient>

        {/* Security & Privacy Card */}
        <View style={styles.infoCard}>
          <View style={styles.infoIconBox}>
            <Feather name="shield" size={18} color={colors.accentCyan} />
          </View>
          <View style={styles.noteCopy}>
            <Text style={styles.noteTitle}>Privasi & Keamanan Data VOIC</Text>
            <Text style={styles.noteText}>
              Rekaman latihan dan rapor evaluasi terhubung secara aman ke akun Anda. Analisis kamera diproses langsung di perangkat tanpa disimpan ke server.
            </Text>
          </View>
        </View>

        <TouchableOpacity
          style={styles.logoutButton}
          onPress={confirmLogout}
          disabled={isLoggingOut}
          activeOpacity={0.85}
        >
          {isLoggingOut ? (
            <ActivityIndicator color={colors.danger} />
          ) : (
            <Feather name="log-out" size={17} color="#F87171" />
          )}
          <Text style={styles.logoutText}>
            {isLoggingOut ? 'Mengakhiri sesi...' : 'Keluar dari Akun VOIC'}
          </Text>
        </TouchableOpacity>

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
  identityCard: {
    borderRadius: 24,
    borderWidth: 1,
    borderColor: 'rgba(59, 130, 246, 0.22)',
    padding: 20,
    marginBottom: 16,
  },
  profileRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 14,
    paddingBottom: 18,
    borderBottomWidth: 1,
    borderBottomColor: 'rgba(255, 255, 255, 0.08)',
  },
  avatar: {
    width: 58,
    height: 58,
    borderRadius: 18,
    alignItems: 'center',
    justifyContent: 'center',
  },
  avatarText: { color: '#FFFFFF', fontSize: 22, fontWeight: '800' },
  profileInfo: { flex: 1, minWidth: 0 },
  verifiedRow: { flexDirection: 'row', alignItems: 'center', gap: 8 },
  name: { color: '#FFFFFF', fontSize: 17, fontWeight: '800', flexShrink: 1 },
  verifiedBadge: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 4,
    backgroundColor: 'rgba(16, 185, 129, 0.14)',
    paddingHorizontal: 8,
    paddingVertical: 2.5,
    borderRadius: 999,
  },
  verifiedText: { color: '#6EE7B7', fontSize: 10, fontWeight: '700' },
  email: { color: colors.textSecondary, fontSize: 13, marginTop: 4 },
  statsGrid: {
    flexDirection: 'row',
    gap: 12,
    marginTop: 18,
  },
  statBox: {
    flex: 1,
    backgroundColor: 'rgba(255, 255, 255, 0.04)',
    borderRadius: 16,
    borderWidth: 1,
    borderColor: 'rgba(255, 255, 255, 0.07)',
    padding: 14,
  },
  statIconWrap: {
    width: 28,
    height: 28,
    borderRadius: 8,
    backgroundColor: 'rgba(255, 255, 255, 0.06)',
    alignItems: 'center',
    justifyContent: 'center',
    marginBottom: 8,
  },
  statValue: { color: '#FFFFFF', fontSize: 22, fontWeight: '800' },
  statLabel: { color: colors.textMuted, fontSize: 9.5, fontWeight: '800', marginTop: 4, letterSpacing: 0.5 },
  infoCard: {
    flexDirection: 'row',
    alignItems: 'flex-start',
    gap: 12,
    padding: 16,
    borderRadius: 20,
    backgroundColor: colors.card,
    borderWidth: 1,
    borderColor: 'rgba(255, 255, 255, 0.08)',
  },
  infoIconBox: {
    width: 36,
    height: 36,
    borderRadius: 12,
    backgroundColor: 'rgba(37, 99, 235, 0.14)',
    alignItems: 'center',
    justifyContent: 'center',
  },
  noteCopy: { flex: 1 },
  noteTitle: { color: colors.textPrimary, fontSize: 13.5, fontWeight: '800' },
  noteText: { color: colors.textSecondary, fontSize: 12, lineHeight: 18, marginTop: 4 },
  logoutButton: {
    minHeight: 52,
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    gap: 10,
    marginTop: 20,
    borderRadius: 16,
    borderWidth: 1,
    borderColor: 'rgba(239, 68, 68, 0.3)',
    backgroundColor: 'rgba(239, 68, 68, 0.10)',
  },
  logoutText: { color: '#FCA5A5', fontSize: 14, fontWeight: '800' },
});
