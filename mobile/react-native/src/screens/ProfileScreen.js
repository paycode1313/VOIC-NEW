import React, { useEffect, useState } from 'react';
import { ActivityIndicator, Alert, ScrollView, StyleSheet, Text, TouchableOpacity, View } from 'react-native';
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

    apiService.getCurrentUser()
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
      <View style={[styles.header, { paddingTop: Math.max(insets.top, 8) }]}>
        <VoicLogo width={76} height={22} />
        <Text style={styles.headerLabel}>AKUN</Text>
      </View>
      <ScrollView contentContainerStyle={styles.content}>
        <Text style={styles.eyebrow}>PENGATURAN AKUN</Text>
        <Text style={styles.title}>Profil</Text>

        <View style={styles.profileRow}>
          <View style={styles.avatar}>
            <Text style={styles.avatarText}>{(profile.name || 'V').trim().charAt(0).toUpperCase()}</Text>
          </View>
          <View style={styles.profileInfo}>
            <Text style={styles.name} numberOfLines={1}>{profile.name || 'Pengguna VOIC'}</Text>
            <Text style={styles.email} numberOfLines={1}>{profile.email || 'Email belum tersedia'}</Text>
          </View>
          <Feather name="check-circle" size={18} color={colors.success} />
        </View>

        <Text style={styles.sectionTitle}>Ringkasan latihan</Text>
        <View style={styles.statsRow}>
          <View style={styles.stat}>
            <Text style={styles.statValue}>{stats.completed_sessions ?? stats.total_sessions ?? 0}</Text>
            <Text style={styles.statLabel}>SESI SELESAI</Text>
          </View>
          <View style={styles.statDivider} />
          <View style={styles.stat}>
            <Text style={styles.statValue}>{Number(stats.avg_score || 0).toFixed(1)}</Text>
            <Text style={styles.statLabel}>RATA-RATA SKOR</Text>
          </View>
        </View>

        <View style={styles.accountNote}>
          <Feather name="shield" size={17} color={colors.accentCyan} />
          <View style={styles.noteCopy}>
            <Text style={styles.noteTitle}>Akun VOIC Anda</Text>
            <Text style={styles.noteText}>Data latihan tersimpan dan hanya dapat diakses melalui akun Anda.</Text>
          </View>
        </View>

        <TouchableOpacity style={styles.logoutButton} onPress={confirmLogout} disabled={isLoggingOut}>
          {isLoggingOut ? <ActivityIndicator color={colors.danger} /> : <Feather name="log-out" size={17} color={colors.danger} />}
          <Text style={styles.logoutText}>{isLoggingOut ? 'Keluar...' : 'Keluar dari akun'}</Text>
        </TouchableOpacity>
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
  content: { padding: 20, paddingBottom: 32 },
  eyebrow: { color: colors.accentCyan, fontSize: 10, fontWeight: '800', letterSpacing: 1.5, marginTop: 4 },
  title: { color: colors.textPrimary, fontSize: 25, fontWeight: '800', marginTop: 7, marginBottom: 22 },
  profileRow: { flexDirection: 'row', alignItems: 'center', gap: 12, paddingVertical: 18, borderTopWidth: 1, borderBottomWidth: 1, borderColor: colors.cardBorderSubtle },
  avatar: { width: 52, height: 52, borderRadius: 17, backgroundColor: colors.primary, alignItems: 'center', justifyContent: 'center' },
  avatarText: { color: '#FFFFFF', fontSize: 20, fontWeight: '800' },
  profileInfo: { flex: 1, minWidth: 0 },
  name: { color: colors.textPrimary, fontSize: 15, fontWeight: '700' },
  email: { color: colors.textSecondary, fontSize: 12, marginTop: 4 },
  sectionTitle: { color: colors.textPrimary, fontSize: 14, fontWeight: '700', marginTop: 26, marginBottom: 12 },
  statsRow: { flexDirection: 'row', borderTopWidth: 1, borderBottomWidth: 1, borderColor: colors.cardBorderSubtle, paddingVertical: 18 },
  stat: { flex: 1 },
  statValue: { color: colors.textPrimary, fontSize: 22, fontWeight: '800' },
  statLabel: { color: colors.textMuted, fontSize: 9, fontWeight: '800', marginTop: 5 },
  statDivider: { width: 1, backgroundColor: colors.cardBorderSubtle, marginHorizontal: 18 },
  accountNote: { flexDirection: 'row', alignItems: 'flex-start', gap: 11, marginTop: 24, padding: 14, borderRadius: 14, backgroundColor: colors.surface, borderWidth: 1, borderColor: colors.cardBorderSubtle },
  noteCopy: { flex: 1 },
  noteTitle: { color: colors.textPrimary, fontSize: 12, fontWeight: '700' },
  noteText: { color: colors.textSecondary, fontSize: 11, lineHeight: 17, marginTop: 4 },
  logoutButton: { minHeight: 50, flexDirection: 'row', alignItems: 'center', justifyContent: 'center', gap: 9, marginTop: 26, borderRadius: 12, borderWidth: 1, borderColor: 'rgba(239, 68, 68, 0.25)', backgroundColor: 'rgba(239, 68, 68, 0.07)' },
  logoutText: { color: colors.danger, fontSize: 13, fontWeight: '700' },
});