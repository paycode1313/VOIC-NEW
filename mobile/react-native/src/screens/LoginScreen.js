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
  const [isLoading, setIsLoading] = useState(false);
  const [error, setError] = useState(null);

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
      {/* Ambient Blue Background Glow (Mirip Web) */}
      <View style={styles.ambientAuraTop} />
      <View style={styles.ambientAuraBottom} />

      <ScrollView contentContainerStyle={styles.scrollContent}>
        <View style={styles.logoContainer}>
          <VoicLogo width={174} height={50} />
          <Text style={styles.brandSubtitle}>Voice & Optics Intelligent Coach</Text>
        </View>

        {error && (
          <View style={styles.errorBox}>
            <Feather name="alert-circle" size={16} color="#F87171" style={{ marginRight: 8 }} />
            <Text style={styles.errorText}>{error}</Text>
          </View>
        )}

        {/* Input Form */}
        <View style={styles.formContainer}>
          {isRegisterMode && (
            <View style={styles.inputWrapper}>
              <Feather name="user" size={18} color={colors.textSecondary} style={styles.inputIcon} />
              <TextInput
                style={styles.input}
                placeholder="Nama Lengkap"
                placeholderTextColor={colors.textMuted}
                value={name}
                onChangeText={setName}
                autoCapitalize="words"
              />
            </View>
          )}

          <View style={styles.inputWrapper}>
            <Feather name="mail" size={18} color={colors.textSecondary} style={styles.inputIcon} />
            <TextInput
              style={styles.input}
              placeholder="Email"
              placeholderTextColor={colors.textMuted}
              value={email}
              onChangeText={setEmail}
              keyboardType="email-address"
              autoCapitalize="none"
            />
          </View>

          <View style={styles.inputWrapper}>
            <Feather name="lock" size={18} color={colors.textSecondary} style={styles.inputIcon} />
            <TextInput
              style={styles.input}
              placeholder="Password (min. 8 karakter)"
              placeholderTextColor={colors.textMuted}
              value={password}
              onChangeText={setPassword}
              secureTextEntry={!showPassword}
              autoCapitalize="none"
            />
            <TouchableOpacity
              accessibilityRole="button"
              accessibilityLabel={showPassword ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi'}
              onPress={() => setShowPassword((visible) => !visible)}
              style={styles.passwordToggle}
            >
              <Feather name={showPassword ? 'eye-off' : 'eye'} size={18} color={colors.textSecondary} />
            </TouchableOpacity>
          </View>

          {isRegisterMode && (
            <View style={styles.inputWrapper}>
              <Feather name="shield" size={18} color={colors.textSecondary} style={styles.inputIcon} />
              <TextInput
                style={styles.input}
                placeholder="Konfirmasi kata sandi"
                placeholderTextColor={colors.textMuted}
                value={passwordConfirmation}
                onChangeText={setPasswordConfirmation}
                secureTextEntry={!showPassword}
                autoCapitalize="none"
              />
            </View>
          )}

          <TouchableOpacity
            style={styles.loginButton}
            onPress={handleSubmit}
            disabled={isLoading}
            activeOpacity={0.85}
          >
            <LinearGradient
              colors={colors.buttonGradient}
              start={{ x: 0, y: 0 }}
              end={{ x: 1, y: 0 }}
              style={styles.buttonGradient}
            >
              {isLoading ? (
                <ActivityIndicator color="#FFFFFF" />
              ) : (
                <Text style={styles.buttonText}>
                  {isRegisterMode ? 'Daftar Akun VOIC' : 'Masuk ke Akun VOIC'}
                </Text>
              )}
            </LinearGradient>
          </TouchableOpacity>

          {/* Mode Switcher */}
          <TouchableOpacity
            style={styles.switchModeButton}
            onPress={() => {
              setError(null);
              setIsRegisterMode(!isRegisterMode);
              setPasswordConfirmation('');
            }}
          >
            <Text style={styles.switchModeText}>
              {isRegisterMode
                ? 'Sudah memiliki akun? Masuk di sini'
                : 'Belum punya akun? Daftar akun baru gratis'}
            </Text>
          </TouchableOpacity>
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
    top: -80,
    alignSelf: 'center',
    width: 280,
    height: 280,
    borderRadius: 140,
    backgroundColor: 'rgba(37, 99, 235, 0.12)',
  },
  ambientAuraBottom: {
    position: 'absolute',
    bottom: -60,
    right: -40,
    width: 240,
    height: 240,
    borderRadius: 120,
    backgroundColor: 'rgba(56, 189, 248, 0.08)',
  },
  scrollContent: {
    flexGrow: 1,
    justifyContent: 'center',
    padding: 24,
  },
  logoContainer: {
    alignItems: 'center',
    marginBottom: 32,
  },
  brandSubtitle: {
    fontSize: 13,
    color: colors.textSecondary,
    marginTop: 4,
  },
  errorBox: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: 'rgba(239, 68, 68, 0.12)',
    borderWidth: 1,
    borderColor: 'rgba(239, 68, 68, 0.35)',
    borderRadius: 12,
    padding: 12,
    marginBottom: 18,
  },
  errorText: {
    color: '#FCA5A5',
    fontSize: 13,
    flex: 1,
  },
  formContainer: {
    width: '100%',
  },
  inputWrapper: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: colors.surfaceElevated,
    borderWidth: 1,
    borderColor: colors.cardBorder,
    borderRadius: 14,
    marginBottom: 14,
    paddingHorizontal: 14,
  },
  inputIcon: {
    marginRight: 10,
  },
  input: {
    flex: 1,
    height: 52,
    color: '#FFFFFF',
    fontSize: 15,
  },
  passwordToggle: {
    padding: 8,
    marginRight: -8,
  },
  loginButton: {
    marginTop: 6,
    borderRadius: 14,
    overflow: 'hidden',
    shadowColor: colors.primary,
    shadowOffset: { width: 0, height: 6 },
    shadowOpacity: 0.45,
    shadowRadius: 16,
    elevation: 8,
  },
  buttonGradient: {
    height: 52,
    justifyContent: 'center',
    alignItems: 'center',
  },
  buttonText: {
    color: '#FFFFFF',
    fontSize: 15,
    fontWeight: 'bold',
    letterSpacing: 0.5,
  },
  switchModeButton: {
    marginTop: 18,
    paddingVertical: 10,
    alignItems: 'center',
  },
  switchModeText: {
    color: colors.accentCyan,
    fontSize: 13,
    fontWeight: '600',
  },
});
