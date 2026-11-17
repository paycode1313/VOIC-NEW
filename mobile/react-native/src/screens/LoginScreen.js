import React, { useState } from 'react';
import {
  View,
  Text,
  TextInput,
  TouchableOpacity,
  StyleSheet,
  ActivityIndicator,
  KeyboardAvoidingView,
  Platform,
  ScrollView,
} from 'react-native';
import { LinearGradient } from 'expo-linear-gradient';
import { Feather } from '@expo/vector-icons';
import { colors } from '../constants/theme';
import { apiService } from '../services/apiService';
import VoicLogo from '../components/VoicLogo';

export default function LoginScreen({ onLoginSuccess }) {
  const [isRegisterMode, setIsRegisterMode] = useState(false);
  const [name, setName] = useState('');
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [passwordConfirmation, setPasswordConfirmation] = useState('');
  const [showPassword, setShowPassword] = useState(false);
  const [focusedField, setFocusedField] = useState(null);
  const [isLoading, setIsLoading] = useState(false);
  const [error, setError] = useState(null);

  const switchMode = (registerMode) => {
    setError(null);
    setIsRegisterMode(registerMode);
    setPasswordConfirmation('');
  };

  const handleSubmit = async () => {
    if (!email.trim() || !password) {
      setError('Email dan kata sandi wajib diisi.');
      return;
    }

    if (isRegisterMode && password.length < 8) {
      setError('Kata sandi harus memiliki minimal 8 karakter.');
      return;
    }

    if (isRegisterMode && password !== passwordConfirmation) {
      setError('Konfirmasi kata sandi belum sama.');
      return;
    }

    setIsLoading(true);
    setError(null);
    try {
      if (isRegisterMode) {
        if (!name.trim()) {
          setError('Nama lengkap wajib diisi.');
          setIsLoading(false);
          return;
        }
        const res = await apiService.register(name.trim(), email.trim(), password);
        if (res.success && res.token) {
          onLoginSuccess(res.user);
        } else {
          setError(Object.values(res.errors || {}).flat()[0] || res.message || 'Pendaftaran gagal. Periksa kembali data Anda.');
        }
      } else {
        const res = await apiService.login(email.trim(), password);
        if (res.success && res.token) {
          onLoginSuccess(res.user);
        } else {
          setError(Object.values(res.errors || {}).flat()[0] || res.message || 'Login gagal. Periksa email dan kata sandi Anda.');
        }
      }
    } catch (err) {
      setError('Gagal terhubung ke API Laravel. Periksa koneksi jaringan.');
    } finally {
      setIsLoading(false);
    }
  };

  return (
    <KeyboardAvoidingView
      behavior={Platform.OS === 'ios' ? 'padding' : 'height'}
      style={styles.container}
    >
      {/* Ambient Radial Aura */}
      <View style={styles.ambientAuraTop} />
      <View style={styles.ambientAuraBottom} />

      <ScrollView
        contentContainerStyle={styles.scrollContent}
        keyboardShouldPersistTaps="handled"
        showsVerticalScrollIndicator={false}
      >
        <View style={styles.contentMax}>
          {/* Brand Header */}
          <View style={styles.headerSection}>
            <View style={styles.liveBadge}>
              <View style={styles.liveDot} />
              <Text style={styles.liveBadgeText}>AI VOICE & OPTICS COACH</Text>
            </View>

            <View style={styles.logoPedestal}>
              <VoicLogo width={148} height={42} />
            </View>

            <Text style={styles.heroTitle}>
              {isRegisterMode ? 'Buat Akun VOIC Baru' : 'Selamat Datang Kembali'}
            </Text>
            <Text style={styles.heroSubtitle}>
              {isRegisterMode
                ? 'Simpan riwayat rapor & pantau perkembangan band score Anda'
                : 'Masuk untuk melanjutkan latihan wawancara, sidang, & pitching'}
            </Text>
          </View>

          {/* Elevated Glass Auth Card */}
          <View style={styles.card}>
            {/* Segmented Mode Switcher */}
            <View style={styles.segmentedControl}>
              <TouchableOpacity
                accessibilityRole="tab"
                accessibilityState={{ selected: !isRegisterMode }}
                style={[styles.segmentTab, !isRegisterMode && styles.segmentTabActive]}
                onPress={() => switchMode(false)}
                activeOpacity={0.85}
              >
                <Text style={[styles.segmentTabText, !isRegisterMode && styles.segmentTabTextActive]}>
                  Masuk Akun
                </Text>
              </TouchableOpacity>
              <TouchableOpacity
                accessibilityRole="tab"
                accessibilityState={{ selected: isRegisterMode }}
                style={[styles.segmentTab, isRegisterMode && styles.segmentTabActive]}
                onPress={() => switchMode(true)}
                activeOpacity={0.85}
              >
                <Text style={[styles.segmentTabText, isRegisterMode && styles.segmentTabTextActive]}>
                  Daftar Baru
                </Text>
              </TouchableOpacity>
            </View>

            {error && (
              <View style={styles.errorBox}>
                <Feather name="alert-circle" size={16} color="#F87171" style={{ marginRight: 8 }} />
                <Text style={styles.errorText}>{error}</Text>
              </View>
            )}

            {/* Form Fields */}
            {isRegisterMode && (
              <View style={styles.fieldGroup}>
                <Text style={styles.fieldLabel}>NAMA LENGKAP</Text>
                <View
                  style={[
                    styles.inputWrapper,
                    focusedField === 'name' && styles.inputWrapperFocused,
                  ]}
                >
                  <Feather
                    name="user"
                    size={18}
                    color={focusedField === 'name' ? colors.accentCyan : colors.textSecondary}
                    style={styles.inputIcon}
                  />
                  <TextInput
                    style={styles.input}
                    placeholder="Nama lengkap sesuai sertifikat"
                    placeholderTextColor={colors.textMuted}
                    value={name}
                    onChangeText={setName}
                    onFocus={() => setFocusedField('name')}
                    onBlur={() => setFocusedField(null)}
                    autoCapitalize="words"
                  />
                </View>
              </View>
            )}

            <View style={styles.fieldGroup}>
              <Text style={styles.fieldLabel}>ALAMAT EMAIL</Text>
              <View
                style={[
                  styles.inputWrapper,
                  focusedField === 'email' && styles.inputWrapperFocused,
                ]}
              >
                <Feather
                  name="mail"
                  size={18}
                  color={focusedField === 'email' ? colors.accentCyan : colors.textSecondary}
                  style={styles.inputIcon}
                />
                <TextInput
                  style={styles.input}
                  placeholder="nama@email.com"
                  placeholderTextColor={colors.textMuted}
                  value={email}
                  onChangeText={setEmail}
                  onFocus={() => setFocusedField('email')}
                  onBlur={() => setFocusedField(null)}
                  keyboardType="email-address"
                  autoCapitalize="none"
                />
              </View>
            </View>

            <View style={styles.fieldGroup}>
              <Text style={styles.fieldLabel}>KATA SANDI</Text>
              <View
                style={[
                  styles.inputWrapper,
                  focusedField === 'password' && styles.inputWrapperFocused,
                ]}
              >
                <Feather
                  name="lock"
                  size={18}
                  color={focusedField === 'password' ? colors.accentCyan : colors.textSecondary}
                  style={styles.inputIcon}
                />
                <TextInput
                  style={styles.input}
                  placeholder={isRegisterMode ? 'Minimal 8 karakter' : 'Masukkan kata sandi'}
                  placeholderTextColor={colors.textMuted}
                  value={password}
                  onChangeText={setPassword}
                  onFocus={() => setFocusedField('password')}
                  onBlur={() => setFocusedField(null)}
                  secureTextEntry={!showPassword}
                  autoCapitalize="none"
                />
                <TouchableOpacity
                  accessibilityRole="button"
                  accessibilityLabel={showPassword ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi'}
                  onPress={() => setShowPassword((visible) => !visible)}
                  style={styles.passwordToggle}
                >
                  <Feather
                    name={showPassword ? 'eye-off' : 'eye'}
                    size={18}
                    color={colors.textSecondary}
                  />
                </TouchableOpacity>
              </View>
            </View>

            {isRegisterMode && (
              <View style={styles.fieldGroup}>
                <Text style={styles.fieldLabel}>KONFIRMASI KATA SANDI</Text>
                <View
                  style={[
                    styles.inputWrapper,
                    focusedField === 'confirm' && styles.inputWrapperFocused,
                  ]}
                >
                  <Feather
                    name="shield"
                    size={18}
                    color={focusedField === 'confirm' ? colors.accentCyan : colors.textSecondary}
                    style={styles.inputIcon}
                  />
                  <TextInput
                    style={styles.input}
                    placeholder="Ketik ulang kata sandi"
                    placeholderTextColor={colors.textMuted}
                    value={passwordConfirmation}
                    onChangeText={setPasswordConfirmation}
                    onFocus={() => setFocusedField('confirm')}
                    onBlur={() => setFocusedField(null)}
                    secureTextEntry={!showPassword}
                    autoCapitalize="none"
                  />
                </View>
              </View>
            )}

            {/* Primary CTA Button */}
            <TouchableOpacity
              style={styles.loginButton}
              onPress={handleSubmit}
              disabled={isLoading}
              activeOpacity={0.88}
            >
              <LinearGradient
                colors={['#2563EB', '#1D4ED8', '#1E40AF']}
                start={{ x: 0, y: 0 }}
                end={{ x: 1, y: 0 }}
                style={styles.buttonGradient}
              >
                {isLoading ? (
                  <ActivityIndicator color="#FFFFFF" />
                ) : (
                  <View style={styles.buttonInner}>
                    <Text style={styles.buttonText}>
                      {isRegisterMode ? 'Buat Akun Gratis' : 'Masuk ke Akun VOIC'}
                    </Text>
                    <Feather name="arrow-right" size={18} color="#FFFFFF" />
                  </View>
                )}
              </LinearGradient>
            </TouchableOpacity>

            {/* Feature Pills */}
            <View style={styles.featurePillsRow}>
              <View style={styles.featurePill}>
                <Feather name="mic" size={12} color={colors.accentCyan} />
                <Text style={styles.featurePillText}>Wawancara HRD</Text>
              </View>
              <View style={styles.featurePill}>
                <Feather name="award" size={12} color={colors.accentCyan} />
                <Text style={styles.featurePillText}>Sidang Skripsi</Text>
              </View>
              <View style={styles.featurePill}>
                <Feather name="trending-up" size={12} color={colors.accentCyan} />
                <Text style={styles.featurePillText}>Pitching VC</Text>
              </View>
            </View>
          </View>

          {/* Trust & Footer */}
          <View style={styles.trustRow}>
            <Feather name="shield" size={13} color={colors.success} />
            <Text style={styles.trustText}>Enkripsi End-to-End · Privasi Suara & Kamera Terlindungi</Text>
          </View>
        </View>
      </ScrollView>
    </KeyboardAvoidingView>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: colors.background,
  },
  ambientAuraTop: {
    position: 'absolute',
    top: -100,
    alignSelf: 'center',
    width: 340,
    height: 340,
    borderRadius: 170,
    backgroundColor: 'rgba(37, 99, 235, 0.16)',
  },
  ambientAuraBottom: {
    position: 'absolute',
    bottom: -80,
    right: -40,
    width: 260,
    height: 260,
    borderRadius: 130,
    backgroundColor: 'rgba(56, 189, 248, 0.10)',
  },
  scrollContent: {
    flexGrow: 1,
    justifyContent: 'center',
    paddingHorizontal: 22,
    paddingVertical: 32,
  },
  contentMax: {
    width: '100%',
    maxWidth: 420,
    alignSelf: 'center',
  },
  headerSection: {
    alignItems: 'center',
    marginBottom: 24,
  },
  liveBadge: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: 'rgba(37, 99, 235, 0.14)',
    borderWidth: 1,
    borderColor: 'rgba(96, 165, 250, 0.28)',
    borderRadius: 999,
    paddingHorizontal: 12,
    paddingVertical: 5,
    marginBottom: 16,
  },
  liveDot: {
    width: 7,
    height: 7,
    borderRadius: 4,
    backgroundColor: '#10B981',
    marginRight: 7,
  },
  liveBadgeText: {
    color: '#93C5FD',
    fontSize: 10.5,
    fontWeight: '700',
    letterSpacing: 1,
  },
  logoPedestal: {
    marginBottom: 14,
  },
  heroTitle: {
    fontSize: 24,
    fontWeight: '800',
    color: '#F8FAFC',
    textAlign: 'center',
    letterSpacing: -0.4,
  },
  heroSubtitle: {
    fontSize: 13.5,
    color: colors.textSecondary,
    textAlign: 'center',
    marginTop: 6,
    lineHeight: 19,
    paddingHorizontal: 12,
  },
  card: {
    backgroundColor: 'rgba(15, 28, 47, 0.88)',
    borderRadius: 24,
    borderWidth: 1,
    borderColor: 'rgba(255, 255, 255, 0.09)',
    padding: 20,
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 16 },
    shadowOpacity: 0.45,
    shadowRadius: 28,
    elevation: 10,
  },
  segmentedControl: {
    flexDirection: 'row',
    backgroundColor: 'rgba(8, 15, 27, 0.85)',
    borderRadius: 14,
    padding: 4,
    marginBottom: 20,
    borderWidth: 1,
    borderColor: 'rgba(255, 255, 255, 0.06)',
  },
  segmentTab: {
    flex: 1,
    paddingVertical: 10,
    borderRadius: 10,
    alignItems: 'center',
    justifyContent: 'center',
  },
  segmentTabActive: {
    backgroundColor: '#1B2D47',
    borderWidth: 1,
    borderColor: 'rgba(96, 165, 250, 0.25)',
  },
  segmentTabText: {
    fontSize: 13,
    fontWeight: '700',
    color: colors.textMuted,
  },
  segmentTabTextActive: {
    color: '#FFFFFF',
  },
  errorBox: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: 'rgba(239, 68, 68, 0.12)',
    borderWidth: 1,
    borderColor: 'rgba(239, 68, 68, 0.35)',
    borderRadius: 12,
    padding: 12,
    marginBottom: 16,
  },
  errorText: {
    color: '#FCA5A5',
    fontSize: 13,
    flex: 1,
  },
  fieldGroup: {
    marginBottom: 14,
  },
  fieldLabel: {
    fontSize: 11,
    fontWeight: '700',
    color: '#94A3B8',
    letterSpacing: 0.8,
    marginBottom: 6,
    marginLeft: 2,
  },
  inputWrapper: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: '#080F1B',
    borderWidth: 1,
    borderColor: 'rgba(255, 255, 255, 0.10)',
    borderRadius: 14,
    paddingHorizontal: 14,
  },
  inputWrapperFocused: {
    borderColor: colors.primaryLight,
    backgroundColor: '#0B1424',
  },
  inputIcon: {
    marginRight: 10,
  },
  input: {
    flex: 1,
    height: 50,
    color: '#FFFFFF',
    fontSize: 15,
  },
  passwordToggle: {
    padding: 10,
    marginRight: -8,
  },
  loginButton: {
    marginTop: 8,
    borderRadius: 14,
    overflow: 'hidden',
    shadowColor: colors.primary,
    shadowOffset: { width: 0, height: 8 },
    shadowOpacity: 0.45,
    shadowRadius: 16,
    elevation: 8,
  },
  buttonGradient: {
    height: 52,
    justifyContent: 'center',
    alignItems: 'center',
  },
  buttonInner: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 8,
  },
  buttonText: {
    color: '#FFFFFF',
    fontSize: 15,
    fontWeight: '700',
    letterSpacing: 0.3,
    marginRight: 6,
  },
  featurePillsRow: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    marginTop: 18,
    paddingTop: 16,
    borderTopWidth: 1,
    borderTopColor: 'rgba(255, 255, 255, 0.07)',
  },
  featurePill: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: 'rgba(255, 255, 255, 0.04)',
    paddingHorizontal: 9,
    paddingVertical: 6,
    borderRadius: 8,
  },
  featurePillText: {
    color: '#CBD5E1',
    fontSize: 11,
    fontWeight: '600',
    marginLeft: 5,
  },
  trustRow: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    marginTop: 18,
  },
  trustText: {
    color: colors.textMuted,
    fontSize: 11.5,
    fontWeight: '500',
    marginLeft: 6,
  },
});
