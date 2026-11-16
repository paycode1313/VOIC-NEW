import React, { useState, useEffect, useRef } from 'react';
import {
  View,
  Text,
  Image,
  StyleSheet,
  TouchableOpacity,
  ScrollView,
  Modal,
  TextInput,
  ActivityIndicator,
  Alert,
  Animated,
} from 'react-native';
import { LinearGradient } from 'expo-linear-gradient';
import * as Speech from 'expo-speech';
import { MaterialCommunityIcons, Feather, Ionicons } from '@expo/vector-icons';
import { colors } from '../constants/theme';
import { apiService } from '../services/apiService';
import AudioOrb from '../components/AudioOrb';
import CameraPip from '../components/CameraPip';

export default function PracticeArenaScreen({ sessionData, onFinishSession, onBack }) {
  const { sessionId, role, scenario, initialGreeting } = sessionData;

  const [secondsElapsed, setSecondsElapsed] = useState(0);
  const [isAiSpeaking, setIsAiSpeaking] = useState(false);
  const [isUserSpeaking, setIsUserSpeaking] = useState(false);
  const [isProcessing, setIsProcessing] = useState(false);
  const [currentAiDialogue, setCurrentAiDialogue] = useState(initialGreeting);
  const [lastFacialCritique, setLastFacialCritique] = useState(null);
  const [partTitle, setPartTitle] = useState(sessionData.partTitle || '');
  const answerStartRef = useRef(Date.now());
  const [isMuted, setIsMuted] = useState(false);

  // Live Telemetry simulation
  const [eyeContactRatio, setEyeContactRatio] = useState(0.92);
  const [isSmiling, setIsSmiling] = useState(true);
  const [faceDetected, setFaceDetected] = useState(true);

  // Modal Input Bicara
  const [isModalOpen, setIsModalOpen] = useState(false);
  const [speechInput, setSpeechInput] = useState('');

  // ── Animations ──────────────────────────────────────────────
  // Pulsing status dot
  const pulseAnim = useRef(new Animated.Value(1)).current;
  // Mic ring glow
  const micRingScale = useRef(new Animated.Value(1)).current;
  const micRingOpacity = useRef(new Animated.Value(0.6)).current;
  // Speaking border glow
  const speakingBorderAnim = useRef(new Animated.Value(0)).current;

  useEffect(() => {
    // Pulsing dot loop
    const pulsing = Animated.loop(
      Animated.sequence([
        Animated.timing(pulseAnim, { toValue: 1.6, duration: 700, useNativeDriver: true }),
        Animated.timing(pulseAnim, { toValue: 1, duration: 700, useNativeDriver: true }),
      ])
    );
    pulsing.start();

    // Mic ring glow loop
    const micGlow = Animated.loop(
      Animated.sequence([
        Animated.parallel([
          Animated.timing(micRingScale, { toValue: 1.35, duration: 900, useNativeDriver: true }),
          Animated.timing(micRingOpacity, { toValue: 0, duration: 900, useNativeDriver: true }),
        ]),
        Animated.parallel([
          Animated.timing(micRingScale, { toValue: 1, duration: 0, useNativeDriver: true }),
          Animated.timing(micRingOpacity, { toValue: 0.5, duration: 0, useNativeDriver: true }),
        ]),
      ])
    );
    micGlow.start();

    return () => {
      pulsing.stop();
      micGlow.stop();
    };
  }, []);

  // Speaking border flash
  useEffect(() => {
    if (isAiSpeaking) {
      Animated.loop(
        Animated.sequence([
          Animated.timing(speakingBorderAnim, { toValue: 1, duration: 600, useNativeDriver: false }),
          Animated.timing(speakingBorderAnim, { toValue: 0, duration: 600, useNativeDriver: false }),
        ])
      ).start();
    } else {
      speakingBorderAnim.stopAnimation();
      speakingBorderAnim.setValue(0);
    }
  }, [isAiSpeaking]);

  const speakingBorderColor = speakingBorderAnim.interpolate({
    inputRange: [0, 1],
    outputRange: ['rgba(59,130,246,0.3)', 'rgba(56,189,248,0.95)'],
  });

  // ── Business logic (unchanged) ───────────────────────────────
  const getAvatarSource = () => {
    const roleType = role?.role_type || 'dosen_penguji';
    if (roleType === 'hrd') {
      return require('../../assets/avatars/hrd.jpg');
    }
    if (roleType === 'investor') {
      return require('../../assets/avatars/investor.jpg');
    }
    return require('../../assets/avatars/dosen.jpg');
  };

  const promptSuggestions = [
    'Penelitian saya berfokus pada integrasi AI untuk melatih komunikasi mahasiswa.',
    'Metodologi yang saya gunakan divalidasi dengan pengujian empiris dan metrik terukur.',
    'Kekuatan utama saya adalah kepemimpinan adaptif dan ketenangan menghadapi tekanan.',
  ];

  const speakDialogue = (text) => {
    if (!text || isMuted) return;
    Speech.stop();
    const isEn = sessionData?.language === 'en';
    setIsAiSpeaking(true);
    Speech.speak(text, {
      language: isEn ? 'en-US' : 'id-ID',
      rate: 0.95,
      pitch: 1.0,
      onStart: () => setIsAiSpeaking(true),
      onDone: () => {
        setIsAiSpeaking(false);
        answerStartRef.current = Date.now();
      },
      onStopped: () => setIsAiSpeaking(false),
      onError: () => setIsAiSpeaking(false),
    });
  };

  useEffect(() => {
    const timer = setInterval(() => {
      setSecondsElapsed((prev) => prev + 1);
    }, 1000);

    if (initialGreeting) {
      speakDialogue(initialGreeting);
    }

    return () => {
      clearInterval(timer);
      Speech.stop();
    };
  }, []);

  const formatTimer = (totalSeconds) => {
    const m = Math.floor(totalSeconds / 60).toString().padStart(2, '0');
    const s = (totalSeconds % 60).toString().padStart(2, '0');
    return `${m}:${s}`;
  };

  const handleSendSpeech = async (spokenText) => {
    if (!spokenText.trim()) return;

    Speech.stop();
    setIsModalOpen(false);
    setIsProcessing(true);
    const answerSeconds = (Date.now() - answerStartRef.current) / 1000;

    try {
      const res = await apiService.answerExam(sessionId, spokenText, answerSeconds, secondsElapsed);
      setIsProcessing(false);

      if (!res.success) {
        Alert.alert('Respon AI', res.message || 'Tidak ada balasan dari AI.');
        return;
      }

      const reply = res.examiner_message?.text || '';
      setCurrentAiDialogue(reply);
      if (res.part_title) setPartTitle(res.part_title);
      speakDialogue(reply);

      if (res.completed) {
        finishSession();
      }
    } catch (err) {
      setIsProcessing(false);
      Alert.alert('Gagal Mengirim Jawaban', 'Periksa koneksi ke server Laravel.');
    }
  };

  const finishSession = async () => {
    try {
      const res = await apiService.finishExam(sessionId, Math.max(secondsElapsed, 1));
      if (res.success && res.evaluation) {
        onFinishSession(res.evaluation);
      } else {
        Alert.alert('Gagal Membuat Rapor', res.message || 'Coba lagi beberapa saat.');
      }
    } catch (err) {
      Alert.alert('Gagal Membuat Rapor', 'Periksa koneksi ke server Laravel.');
    }
  };

  const handleFinishPrompt = () => {
    Speech.stop();
    Alert.alert(
      'Selesaikan Sesi Latihan?',
      'Rapor dibuat dari jawaban yang sudah kamu berikan sejauh ini.',
      [
        { text: 'Lanjut Latihan', style: 'cancel' },
        { text: 'Selesai & Nilai', style: 'default', onPress: finishSession },
      ]
    );
  };

  const handleExit = () => {
    Speech.stop();
    onBack();
  };

  const getAiStatusInfo = () => {
    if (isAiSpeaking) {
      return {
        text: 'Sedang Berbicara...',
        badgeText: 'AI Bicara (Audio Aktif)',
        color: colors.accentCyan,
        dotColor: colors.accentCyan,
      };
    }
    if (isProcessing) {
      return {
        text: 'Menelaah Jawaban & Gestur...',
        badgeText: 'Menganalisis Jawaban',
        color: colors.warning,
        dotColor: colors.warning,
      };
    }
    if (isUserSpeaking) {
      return {
        text: 'Mendengarkan Anda...',
        badgeText: 'Mendengarkan Argumen',
        color: colors.success,
        dotColor: colors.success,
      };
    }
    return {
      text: 'Giliran Anda Berbicara (Tap Mic)',
      badgeText: 'Standby / Siap',
      color: colors.textSecondary,
      dotColor: colors.cardBorder,
    };
  };

  const statusInfo = getAiStatusInfo();

  // Camera PiP border color based on eye contact
  const pipBorderColor = eyeContactRatio >= 0.8 ? colors.success : colors.danger;

  return (
    <View style={styles.container}>

      {/* ── 1. HEADER ─────────────────────────────────────────── */}
      <LinearGradient
        colors={['#030712', '#070B14']}
        style={styles.header}
      >
        <TouchableOpacity onPress={handleExit} style={styles.backButton}>
          <Feather name="chevron-left" size={22} color="#FFFFFF" />
        </TouchableOpacity>

        <View style={styles.headerTitleContainer}>
          <Text style={styles.headerRoleName} numberOfLines={1}>
            {role?.name || 'VOIC - Penguji'}
          </Text>
          <Text style={styles.headerScenario} numberOfLines={1}>
            {partTitle || scenario?.name || 'Simulasi Ujian'}
          </Text>
        </View>

        {/* Timer badge — larger and prominent */}
        <View style={styles.timerBadge}>
          <View style={styles.timerDot} />
          <Text style={styles.timerText}>{formatTimer(secondsElapsed)}</Text>
        </View>
      </LinearGradient>

      <ScrollView contentContainerStyle={styles.scrollStage}>

        {/* ── 2. EXAMINER VIDEO CARD ───────────────────────────── */}
        <Animated.View
          style={[
            styles.examinerVideoCard,
            isAiSpeaking && { borderColor: speakingBorderColor },
          ]}
        >
          <Image
            source={getAvatarSource()}
            style={styles.examinerImage}
            resizeMode="cover"
          />

          {/* Dark glass vignette */}
          <LinearGradient
            colors={['rgba(7,11,20,0.15)', 'rgba(7,11,20,0.90)']}
            style={styles.vignetteOverlay}
          />

          {/* Top status badge row */}
          <View style={styles.callBadgeRow}>
            <View style={[styles.callStatusBadge, { borderColor: statusInfo.dotColor }]}>
              <Animated.View
                style={[
                  styles.statusDot,
                  { backgroundColor: statusInfo.dotColor, transform: [{ scale: pulseAnim }] },
                ]}
              />
              <Text style={[styles.callBadgeText, { color: statusInfo.color }]}>
                {statusInfo.badgeText}
              </Text>
            </View>

            <View style={styles.hdBadge}>
              <Text style={styles.hdText}>HD LIVE</Text>
            </View>
          </View>

          {/* Audio Orb — bottom 20% of card, centred */}
          <View style={styles.orbCenterWrapper}>
            <AudioOrb
              isSpeaking={isAiSpeaking}
              isListening={isUserSpeaking}
              isThinking={isProcessing}
              size={88}
            />
          </View>

          {/* Examiner name / title */}
          <View style={styles.examinerBottomInfo}>
            <Text style={styles.examinerNameText}>{role?.name || 'AI Penguji'}</Text>
            <Text style={styles.examinerRoleDesc} numberOfLines={1}>
              {role?.role_type === 'dosen_penguji'
                ? 'Dosen Penguji Sidang Utama'
                : role?.role_type === 'hrd'
                ? 'Senior HR Recruiter & Talent Acquisition'
                : 'Venture Capitalist & Angel Investor'}
            </Text>
          </View>
        </Animated.View>

        {/* ── 3. SPEECH BUBBLE ─────────────────────────────────── */}
        <View style={styles.speechBubble}>
          {/* Premium header strip */}
          <View style={styles.bubbleHeader}>
            <MaterialCommunityIcons name="format-quote-open" size={18} color={colors.accentCyan} />
            <Text style={styles.bubbleRoleLabel}>{role?.name || 'AI Penguji'} Berkata:</Text>
            {isAiSpeaking && (
              <View style={styles.speakingPill}>
                <Animated.View style={[styles.speakingPillDot, { transform: [{ scale: pulseAnim }] }]} />
                <Text style={styles.speakingPillText}>Live</Text>
              </View>
            )}
          </View>

          {/* Dialogue text */}
          <LinearGradient
            colors={['#0F1C2F', '#080F1B']}
            style={styles.bubbleBody}
          >
            <Text style={styles.dialogueText}>{currentAiDialogue}</Text>
          </LinearGradient>
        </View>

        {/* Facial critique toast */}
        {lastFacialCritique && (
          <View style={styles.critiqueToast}>
            <Ionicons name="eye" size={15} color={colors.warning} />
            <Text style={styles.critiqueText}>{lastFacialCritique}</Text>
          </View>
        )}

        <View style={{ height: 185 }} />
      </ScrollView>

      {/* ── 4. CAMERA PiP ────────────────────────────────────── */}
      <View style={[styles.pipWrapper, { borderColor: pipBorderColor }]}>
        <CameraPip
          eyeContactRatio={eyeContactRatio}
          isSmiling={isSmiling}
          faceDetected={faceDetected}
          onToggleState={() => {
            setIsSmiling(!isSmiling);
            setEyeContactRatio(!isSmiling ? 0.96 : 0.68);
          }}
        />
      </View>

      {/* ── 5. BOTTOM THUMB ZONE ─────────────────────────────── */}
      <View style={styles.thumbZone}>

        {/* Three-button row: mute | mic | camera */}
        <View style={styles.controlRow}>

          {/* Mute toggle */}
          <TouchableOpacity
            style={styles.sideControlButton}
            onPress={() => {
              if (!isMuted) Speech.stop();
              setIsMuted(!isMuted);
            }}
            activeOpacity={0.8}
          >
            <Feather
              name={isMuted ? 'volume-x' : 'volume-2'}
              size={20}
              color={isMuted ? colors.danger : colors.accentCyan}
            />
            <Text style={[styles.sideControlLabel, isMuted && { color: colors.danger }]}>
              {isMuted ? 'Muted' : 'Audio'}
            </Text>
          </TouchableOpacity>

          {/* Mic button with ring glow */}
          <View style={styles.micWrapper}>
            {/* Animated ring */}
            <Animated.View
              style={[
                styles.micRing,
                { transform: [{ scale: micRingScale }], opacity: micRingOpacity },
              ]}
            />
            <TouchableOpacity
              style={styles.micCircleButton}
              onPress={() => {
                Speech.stop();
                setSpeechInput('');
                setIsModalOpen(true);
              }}
              activeOpacity={0.85}
            >
              <LinearGradient
                colors={colors.buttonGradient}
                style={styles.micGradient}
                start={{ x: 0, y: 0 }}
                end={{ x: 1, y: 1 }}
              >
                <MaterialCommunityIcons name="microphone" size={36} color="#FFFFFF" />
              </LinearGradient>
            </TouchableOpacity>
          </View>

          {/* Camera toggle (cosmetic) */}
          <TouchableOpacity
            style={styles.sideControlButton}
            onPress={() => setFaceDetected(!faceDetected)}
            activeOpacity={0.8}
          >
            <Feather
              name={faceDetected ? 'camera' : 'camera-off'}
              size={20}
              color={faceDetected ? colors.accentCyan : colors.textMuted}
            />
            <Text style={[styles.sideControlLabel, !faceDetected && { color: colors.textMuted }]}>
              {faceDetected ? 'Kamera' : 'Off'}
            </Text>
          </TouchableOpacity>
        </View>

        {/* Mic label */}
        <View style={styles.micLabelRow}>
          <Feather name="mic" size={12} color={colors.accentCyan} style={{ marginRight: 4 }} />
          <Text style={styles.micLabel}>Tap untuk Berbicara</Text>
        </View>

        {/* Finish — outline only */}
        <TouchableOpacity
          style={styles.finishButton}
          onPress={handleFinishPrompt}
          activeOpacity={0.8}
        >
          <Text style={styles.finishButtonText}>Selesaikan Sesi Latihan</Text>
        </TouchableOpacity>
      </View>

      {/* ── 6. MODAL INPUT ───────────────────────────────────── */}
      <Modal visible={isModalOpen} transparent animationType="slide">
        <View style={styles.modalOverlay}>
          <View style={styles.modalContent}>

            <View style={styles.modalHeaderRow}>
              <MaterialCommunityIcons name="microphone-outline" size={22} color={colors.accentCyan} />
              <Text style={styles.modalTitle}>Sampaikan Argumen Anda</Text>
            </View>
            <Text style={styles.modalSubtitle}>
              Ketik jawaban lengkap Anda untuk penguji di bawah ini.
            </Text>

            <TextInput
              style={styles.modalInput}
              multiline
              numberOfLines={6}
              placeholder="Ketik jawaban Anda kepada penguji di sini..."
              placeholderTextColor={colors.textMuted}
              value={speechInput}
              onChangeText={setSpeechInput}
              autoFocus
            />

            <View style={styles.modalButtonRow}>
              <TouchableOpacity
                style={styles.modalCancelButton}
                onPress={() => setIsModalOpen(false)}
              >
                <Text style={styles.modalCancelText}>Batal</Text>
              </TouchableOpacity>

              <TouchableOpacity
                style={styles.modalSendButton}
                onPress={() => handleSendSpeech(speechInput)}
              >
                <LinearGradient
                  colors={['#2563EB', '#1D4ED8']}
                  style={styles.modalSendGradient}
                  start={{ x: 0, y: 0 }}
                  end={{ x: 1, y: 0 }}
                >
                  <Feather name="send" size={16} color="#FFFFFF" style={{ marginRight: 8 }} />
                  <Text style={styles.modalSendText}>Kirim Jawaban</Text>
                </LinearGradient>
              </TouchableOpacity>
            </View>
          </View>
        </View>
      </Modal>
    </View>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: colors.background,
  },

  // ── Header ────────────────────────────────────────────────
  header: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    paddingHorizontal: 14,
    paddingTop: 50,
    paddingBottom: 16,
    borderBottomWidth: 1,
    borderBottomColor: 'rgba(255,255,255,0.06)',
  },
  backButton: {
    width: 38,
    height: 38,
    borderRadius: 19,
    borderWidth: 1,
    borderColor: 'rgba(255,255,255,0.15)',
    justifyContent: 'center',
    alignItems: 'center',
    backgroundColor: 'rgba(255,255,255,0.05)',
  },
  headerTitleContainer: {
    flex: 1,
    marginHorizontal: 10,
  },
  headerRoleName: {
    fontSize: 15,
    fontWeight: 'bold',
    color: '#FFFFFF',
    letterSpacing: 0.2,
  },
  headerScenario: {
    fontSize: 11,
    color: colors.textSecondary,
    marginTop: 2,
  },
  timerBadge: {
    flexDirection: 'row',
    alignItems: 'center',
    paddingHorizontal: 14,
    paddingVertical: 8,
    borderRadius: 22,
    backgroundColor: 'rgba(239,68,68,0.12)',
    borderWidth: 1.5,
    borderColor: 'rgba(239,68,68,0.45)',
  },
  timerDot: {
    width: 8,
    height: 8,
    borderRadius: 4,
    backgroundColor: colors.danger,
    marginRight: 7,
  },
  timerText: {
    fontSize: 15,
    fontWeight: 'bold',
    color: '#FFFFFF',
    letterSpacing: 1,
  },

  // ── Scroll ────────────────────────────────────────────────
  scrollStage: {
    paddingHorizontal: 14,
    paddingTop: 14,
  },

  // ── Examiner Video Card ───────────────────────────────────
  examinerVideoCard: {
    width: '100%',
    height: 320,
    borderRadius: 24,
    overflow: 'hidden',
    backgroundColor: '#0F1C2F',
    borderWidth: 2,
    borderColor: colors.cardBorder,
    shadowColor: colors.primary,
    shadowOffset: { width: 0, height: 10 },
    shadowOpacity: 0.4,
    shadowRadius: 24,
    elevation: 10,
    marginBottom: 14,
  },
  examinerImage: {
    ...StyleSheet.absoluteFillObject,
    width: '100%',
    height: '100%',
  },
  vignetteOverlay: {
    ...StyleSheet.absoluteFillObject,
  },

  // Status badge row
  callBadgeRow: {
    position: 'absolute',
    top: 14,
    left: 14,
    right: 14,
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    zIndex: 10,
  },
  callStatusBadge: {
    flexDirection: 'row',
    alignItems: 'center',
    paddingHorizontal: 12,
    paddingVertical: 6,
    borderRadius: 20,
    backgroundColor: 'rgba(7,11,20,0.82)',
    borderWidth: 1.5,
  },
  statusDot: {
    width: 8,
    height: 8,
    borderRadius: 4,
    marginRight: 7,
  },
  callBadgeText: {
    fontSize: 11,
    fontWeight: '700',
    letterSpacing: 0.3,
  },
  hdBadge: {
    paddingHorizontal: 8,
    paddingVertical: 3,
    borderRadius: 7,
    backgroundColor: 'rgba(37,99,235,0.3)',
    borderWidth: 1,
    borderColor: 'rgba(56,189,248,0.5)',
  },
  hdText: {
    fontSize: 9,
    fontWeight: 'bold',
    color: colors.accentCyan,
    letterSpacing: 1,
  },

  // Audio Orb — bottom 20% of card
  orbCenterWrapper: {
    position: 'absolute',
    bottom: '20%',
    alignSelf: 'center',
    zIndex: 5,
  },

  // Examiner name / title
  examinerBottomInfo: {
    position: 'absolute',
    bottom: 16,
    left: 16,
    right: 16,
    zIndex: 10,
  },
  examinerNameText: {
    fontSize: 18,
    fontWeight: 'bold',
    color: '#FFFFFF',
    letterSpacing: 0.3,
  },
  examinerRoleDesc: {
    fontSize: 12,
    color: 'rgba(148,163,184,0.9)',
    marginTop: 3,
    letterSpacing: 0.1,
  },

  // ── Speech Bubble ─────────────────────────────────────────
  speechBubble: {
    width: '100%',
    borderRadius: 18,
    overflow: 'hidden',
    borderWidth: 1.5,
    borderColor: 'rgba(56,189,248,0.25)',
    shadowColor: colors.primary,
    shadowOffset: { width: 0, height: 6 },
    shadowOpacity: 0.3,
    shadowRadius: 14,
    elevation: 5,
    marginBottom: 10,
  },
  bubbleHeader: {
    flexDirection: 'row',
    alignItems: 'center',
    paddingHorizontal: 14,
    paddingVertical: 10,
    backgroundColor: 'rgba(56,189,248,0.07)',
    borderBottomWidth: 1,
    borderBottomColor: 'rgba(56,189,248,0.2)',
    gap: 6,
  },
  bubbleRoleLabel: {
    fontSize: 12,
    fontWeight: 'bold',
    color: colors.accentCyan,
    flex: 1,
  },
  speakingPill: {
    flexDirection: 'row',
    alignItems: 'center',
    paddingHorizontal: 8,
    paddingVertical: 2,
    borderRadius: 10,
    backgroundColor: 'rgba(56,189,248,0.15)',
    borderWidth: 1,
    borderColor: 'rgba(56,189,248,0.4)',
    gap: 4,
  },
  speakingPillDot: {
    width: 5,
    height: 5,
    borderRadius: 2.5,
    backgroundColor: colors.accentCyan,
  },
  speakingPillText: {
    fontSize: 9,
    fontWeight: 'bold',
    color: colors.accentCyan,
    letterSpacing: 0.5,
  },
  bubbleBody: {
    padding: 16,
  },
  dialogueText: {
    fontSize: 15,
    color: '#F0F6FF',
    lineHeight: 24,
    letterSpacing: 0.1,
  },

  // Critique toast
  critiqueToast: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: 'rgba(245,158,11,0.10)',
    borderWidth: 1,
    borderColor: 'rgba(245,158,11,0.3)',
    borderRadius: 12,
    paddingHorizontal: 12,
    paddingVertical: 8,
    marginBottom: 10,
    width: '100%',
  },
  critiqueText: {
    fontSize: 11,
    color: '#FDE68A',
    marginLeft: 7,
    flex: 1,
  },

  // ── Camera PiP ────────────────────────────────────────────
  pipWrapper: {
    position: 'absolute',
    right: 14,
    bottom: 198,
    zIndex: 25,
    borderRadius: 14,
    borderWidth: 2.5,
    overflow: 'hidden',
  },

  // ── Thumb Zone ────────────────────────────────────────────
  thumbZone: {
    position: 'absolute',
    bottom: 0,
    left: 0,
    right: 0,
    paddingHorizontal: 24,
    paddingTop: 16,
    paddingBottom: 26,
    backgroundColor: colors.surface,
    borderTopLeftRadius: 26,
    borderTopRightRadius: 26,
    borderTopWidth: 1,
    borderTopColor: 'rgba(255,255,255,0.07)',
    alignItems: 'center',
    zIndex: 20,
  },

  // Three-button control row
  controlRow: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    width: '100%',
    marginBottom: 10,
    gap: 24,
  },
  sideControlButton: {
    width: 56,
    height: 56,
    borderRadius: 28,
    backgroundColor: 'rgba(255,255,255,0.05)',
    borderWidth: 1,
    borderColor: 'rgba(255,255,255,0.1)',
    justifyContent: 'center',
    alignItems: 'center',
    gap: 2,
  },
  sideControlLabel: {
    fontSize: 9,
    color: colors.textSecondary,
    fontWeight: '600',
    marginTop: 2,
  },

  // Mic with animated ring
  micWrapper: {
    width: 80,
    height: 80,
    justifyContent: 'center',
    alignItems: 'center',
  },
  micRing: {
    position: 'absolute',
    width: 80,
    height: 80,
    borderRadius: 40,
    borderWidth: 3,
    borderColor: 'rgba(37,99,235,0.6)',
  },
  micCircleButton: {
    width: 80,
    height: 80,
    borderRadius: 40,
    overflow: 'hidden',
    shadowColor: colors.primary,
    shadowOffset: { width: 0, height: 8 },
    shadowOpacity: 0.6,
    shadowRadius: 22,
    elevation: 14,
  },
  micGradient: {
    flex: 1,
    justifyContent: 'center',
    alignItems: 'center',
  },

  // Mic label
  micLabelRow: {
    flexDirection: 'row',
    alignItems: 'center',
    marginBottom: 12,
  },
  micLabel: {
    fontSize: 12,
    fontWeight: '600',
    color: colors.textSecondary,
  },

  // Finish button — outline only
  finishButton: {
    width: '100%',
    height: 44,
    borderRadius: 12,
    borderWidth: 1,
    borderColor: 'rgba(239,68,68,0.3)',
    justifyContent: 'center',
    alignItems: 'center',
    backgroundColor: 'transparent',
  },
  finishButtonText: {
    color: 'rgba(252,165,165,0.8)',
    fontSize: 13,
    fontWeight: 'bold',
    letterSpacing: 0.2,
  },

  // ── Modal ─────────────────────────────────────────────────
  modalOverlay: {
    flex: 1,
    backgroundColor: 'rgba(0,0,0,0.80)',
    justifyContent: 'flex-end',
  },
  modalContent: {
    backgroundColor: '#0B121E',
    borderTopLeftRadius: 28,
    borderTopRightRadius: 28,
    padding: 26,
    borderTopWidth: 1.5,
    borderTopColor: 'rgba(56,189,248,0.2)',
  },
  modalHeaderRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 10,
    marginBottom: 6,
  },
  modalTitle: {
    fontSize: 18,
    fontWeight: 'bold',
    color: '#FFFFFF',
  },
  modalSubtitle: {
    fontSize: 12,
    color: colors.textSecondary,
    marginBottom: 16,
    lineHeight: 18,
  },
  modalInput: {
    height: 140,
    backgroundColor: colors.card,
    borderRadius: 16,
    padding: 14,
    color: '#FFFFFF',
    textAlignVertical: 'top',
    fontSize: 14,
    lineHeight: 22,
    marginBottom: 18,
    borderWidth: 1.5,
    borderColor: 'rgba(56,189,248,0.2)',
  },
  modalButtonRow: {
    flexDirection: 'row',
    gap: 10,
  },
  modalCancelButton: {
    flex: 1,
    height: 52,
    justifyContent: 'center',
    alignItems: 'center',
    borderRadius: 14,
    borderWidth: 1,
    borderColor: 'rgba(255,255,255,0.1)',
    backgroundColor: 'rgba(255,255,255,0.03)',
  },
  modalCancelText: {
    color: colors.textSecondary,
    fontWeight: 'bold',
    fontSize: 14,
  },
  modalSendButton: {
    flex: 2,
    borderRadius: 14,
    overflow: 'hidden',
  },
  modalSendGradient: {
    height: 52,
    flexDirection: 'row',
    justifyContent: 'center',
    alignItems: 'center',
  },
  modalSendText: {
    color: '#FFFFFF',
    fontWeight: 'bold',
    fontSize: 15,
  },
});
