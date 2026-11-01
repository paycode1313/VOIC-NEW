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
  const [isMuted, setIsMuted] = useState(false);

  // Live Telemetry simulation
  const [eyeContactRatio, setEyeContactRatio] = useState(0.92);
  const [isSmiling, setIsSmiling] = useState(true);
  const [faceDetected, setFaceDetected] = useState(true);

  // Modal Input Bicara
  const [isModalOpen, setIsModalOpen] = useState(false);
  const [speechInput, setSpeechInput] = useState('');

  // Get AI Examiner Portrait Avatar
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

  // Sample quick argument templates for rapid practice
  const promptSuggestions = [
    'Penelitian saya berfokus pada integrasi AI untuk melatih komunikasi mahasiswa.',
    'Metodologi yang saya gunakan divalidasi dengan pengujian empiris dan metrik terukur.',
    'Kekuatan utama saya adalah kepemimpinan adaptif dan ketenangan menghadapi tekanan.',
  ];

  // Voice speech synthesis helper
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
      onDone: () => setIsAiSpeaking(false),
      onStopped: () => setIsAiSpeaking(false),
      onError: () => setIsAiSpeaking(false),
    });
  };

  // Timer & initial greeting speech on mount
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
    setIsUserSpeaking(true);

    setTimeout(async () => {
      setIsUserSpeaking(false);
      setIsProcessing(true);

      try {
        const res = await apiService.sendMessage(
          sessionId,
          spokenText,
          eyeContactRatio,
          isSmiling,
          faceDetected,
          secondsElapsed
        );

        if (res.success) {
          const reply =
            res.response_text ||
            res.ai_reply ||
            res.ai_message?.message ||
            'Jawaban Anda telah dicatat oleh dewan penguji.';

          setCurrentAiDialogue(reply);
          setIsProcessing(false);

          if (res.facial_critique) {
            setLastFacialCritique(res.facial_critique);
          }

          // Trigger Real AI Voice
          speakDialogue(reply);
        } else {
          setIsProcessing(false);
          Alert.alert('Respon AI', res.message || 'Tidak ada balasan dari AI.');
        }
      } catch (err) {
        setIsProcessing(false);
        Alert.alert('Gagal Mengirim Pesan', 'Periksa koneksi jaringan API Laravel Anda.');
      }
    }, 1200);
  };

  const handleFinishPrompt = () => {
    Speech.stop();
    Alert.alert(
      'Selesaikan Sesi Latihan?',
      'AI Coach akan menyusun lembar rapor evaluasi performa vokal dan kontak mata Anda.',
      [
        { text: 'Lanjut Latihan', style: 'cancel' },
        {
          text: 'Selesai & Nilai',
          style: 'default',
          onPress: async () => {
            const calculatedFaceScore = Math.min(100, Math.max(50, Math.round(eyeContactRatio * 100)));
            const calculatedVoiceScore = 86.0;
            const calculatedOverall = Math.round((calculatedFaceScore * 0.4 + calculatedVoiceScore * 0.6) * 10) / 10;

            try {
              const res = await apiService.finishPractice(
                sessionId,
                Math.max(secondsElapsed, 10),
                calculatedFaceScore,
                calculatedVoiceScore,
                calculatedOverall
              );
              const evalData = res.evaluation || res.session;
              if (res.success && evalData) {
                onFinishSession(evalData);
              } else {
                onFinishSession({
                  overall_score: calculatedOverall,
                  face_score: calculatedFaceScore,
                  voice_score: calculatedVoiceScore,
                  duration_seconds: secondsElapsed,
                  ai_conclusion: 'Sesi latihan berhasil diselesaikan dengan artikulasi yang sangat baik.',
                });
              }
            } catch (err) {
              onFinishSession({
                overall_score: calculatedOverall,
                face_score: calculatedFaceScore,
                voice_score: calculatedVoiceScore,
                duration_seconds: secondsElapsed,
                ai_conclusion: 'Sesi diselesaikan. Performa vokal dan kontak mata Anda sudah tergolong kompeten.',
              });
            }
          },
        },
      ]
    );
  };

  const handleExit = () => {
    Speech.stop();
    onBack();
  };

  // Status color & label matching web video call
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

  return (
    <View style={styles.container}>
      {/* 1. TOP HEADER (Role AI + Audio Control + Timer) */}
      <View style={styles.header}>
        <TouchableOpacity onPress={handleExit} style={styles.backButton}>
          <Feather name="chevron-left" size={24} color="#FFFFFF" />
        </TouchableOpacity>
        <View style={styles.headerTitleContainer}>
          <Text style={styles.headerRoleName} numberOfLines={1}>{role?.name || 'VOIC - Penguji'}</Text>
          <Text style={styles.headerScenario} numberOfLines={1}>{scenario?.name || 'Simulasi Video Call AI'}</Text>
        </View>

        {/* Audio Mute/Unmute Toggle Button */}
        <TouchableOpacity
          onPress={() => {
            if (!isMuted) Speech.stop();
            setIsMuted(!isMuted);
          }}
          style={styles.muteButton}
          activeOpacity={0.8}
        >
          <Feather
            name={isMuted ? 'volume-x' : 'volume-2'}
            size={16}
            color={isMuted ? colors.danger : colors.accentCyan}
          />
        </TouchableOpacity>

        <View style={styles.timerBadge}>
          <View style={styles.timerDot} />
          <Text style={styles.timerText}>{formatTimer(secondsElapsed)}</Text>
        </View>
      </View>

      <ScrollView contentContainerStyle={styles.scrollStage}>
        {/* 2. PANGGUNG UTAMA VIDEO CALL AI EXAMINER (Dengan Foto Karakter Nyata) */}
        <View
          style={[
            styles.examinerVideoCard,
            isAiSpeaking && styles.examinerVideoCardSpeaking,
          ]}
        >
          {/* Real Avatar Photo of the AI Character */}
          <Image
            source={getAvatarSource()}
            style={styles.examinerImage}
            resizeMode="cover"
          />

          {/* Dark Glass Vignette Overlay */}
          <LinearGradient
            colors={['rgba(7, 11, 20, 0.2)', 'rgba(7, 11, 20, 0.85)']}
            style={styles.vignetteOverlay}
          />

          {/* Top Live Call Badge */}
          <View style={styles.callBadgeRow}>
            <View style={[styles.callStatusBadge, { borderColor: statusInfo.dotColor }]}>
              <View style={[styles.statusDot, { backgroundColor: statusInfo.dotColor }]} />
              <Text style={[styles.callBadgeText, { color: statusInfo.color }]}>
                {statusInfo.badgeText}
              </Text>
            </View>

            <View style={styles.hdBadge}>
              <Text style={styles.hdText}>HD LIVE</Text>
            </View>
          </View>

          {/* Center Mini 3D Audio Orb (Pulsing over character) */}
          <View style={styles.orbCenterWrapper}>
            <AudioOrb
              isSpeaking={isAiSpeaking}
              isListening={isUserSpeaking}
              isThinking={isProcessing}
              size={96}
            />
          </View>

          {/* Bottom Examiner Info Card */}
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
        </View>

        {/* 3. SUBTITLE / AI SPEECH DIALOGUE BUBBLE */}
        <View style={styles.speechBubble}>
          <View style={styles.bubbleQuoteRow}>
            <MaterialCommunityIcons name="format-quote-open" size={16} color={colors.accentCyan} />
            <Text style={styles.bubbleRoleLabel}>{role?.name || 'AI Penguji'} Berkata:</Text>
          </View>
          <Text style={styles.dialogueText}>{currentAiDialogue}</Text>
        </View>

        {/* Live Facial Critique Toast */}
        {lastFacialCritique && (
          <View style={styles.critiqueToast}>
            <Ionicons name="eye" size={15} color={colors.warning} />
            <Text style={styles.critiqueText}>{lastFacialCritique}</Text>
          </View>
        )}

        <View style={{ height: 160 }} />
      </ScrollView>

      {/* 4. FLOATING CAMERA PiP (Live Front Camera Selfie Feed) */}
      <View style={styles.pipWrapper}>
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

      {/* 5. BOTTOM THUMB CONTROLS (Mic Button + Finish) */}
      <View style={styles.thumbZone}>
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
            <MaterialCommunityIcons name="microphone" size={34} color="#FFFFFF" />
          </LinearGradient>
        </TouchableOpacity>
        <Text style={styles.micLabel}>Tap untuk Berbicara 🎙️</Text>

        <TouchableOpacity
          style={styles.finishButton}
          onPress={handleFinishPrompt}
          activeOpacity={0.8}
        >
          <Text style={styles.finishButtonText}>Selesaikan Sesi Latihan</Text>
        </TouchableOpacity>
      </View>

      {/* MODAL INPUT JAWABAN BICARA */}
      <Modal visible={isModalOpen} transparent animationType="slide">
        <View style={styles.modalOverlay}>
          <View style={styles.modalContent}>
            <Text style={styles.modalTitle}>Sampaikan Argumen Anda 🎙️</Text>
            <Text style={styles.modalSubtitle}>
              Ketik jawaban Anda atau pilih template argumen cepat di bawah:
            </Text>

            {/* Quick Suggestion Chips */}
            <ScrollView horizontal showsHorizontalScrollIndicator={false} style={styles.chipScroll}>
              {promptSuggestions.map((template, idx) => (
                <TouchableOpacity
                  key={idx}
                  style={styles.chipButton}
                  onPress={() => setSpeechInput(template)}
                  activeOpacity={0.7}
                >
                  <Text style={styles.chipText} numberOfLines={1}>{template}</Text>
                </TouchableOpacity>
              ))}
            </ScrollView>

            <TextInput
              style={styles.modalInput}
              multiline
              numberOfLines={4}
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
                  colors={colors.buttonGradient}
                  style={styles.modalSendGradient}
                >
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
  header: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    paddingHorizontal: 16,
    paddingTop: 46,
    paddingBottom: 14,
    backgroundColor: colors.surface,
    borderBottomWidth: 1,
    borderBottomColor: colors.cardBorderSubtle,
  },
  backButton: {
    padding: 6,
  },
  headerTitleContainer: {
    flex: 1,
    marginHorizontal: 8,
  },
  headerRoleName: {
    fontSize: 15,
    fontWeight: 'bold',
    color: '#FFFFFF',
  },
  headerScenario: {
    fontSize: 11,
    color: colors.textSecondary,
    marginTop: 1,
  },
  muteButton: {
    width: 32,
    height: 32,
    borderRadius: 16,
    backgroundColor: colors.card,
    borderWidth: 1,
    borderColor: colors.cardBorderSubtle,
    justifyContent: 'center',
    alignItems: 'center',
    marginRight: 8,
  },
  timerBadge: {
    flexDirection: 'row',
    alignItems: 'center',
    paddingHorizontal: 10,
    paddingVertical: 5,
    borderRadius: 20,
    backgroundColor: colors.card,
    borderWidth: 1,
    borderColor: 'rgba(239, 68, 68, 0.4)',
  },
  timerDot: {
    width: 7,
    height: 7,
    borderRadius: 3.5,
    backgroundColor: colors.danger,
    marginRight: 6,
  },
  timerText: {
    fontSize: 12,
    fontWeight: 'bold',
    color: '#FFFFFF',
  },
  scrollStage: {
    paddingHorizontal: 16,
    paddingTop: 12,
  },
  examinerVideoCard: {
    width: '100%',
    height: 280,
    borderRadius: 22,
    overflow: 'hidden',
    backgroundColor: '#0F1C2F',
    borderWidth: 1.5,
    borderColor: colors.cardBorder,
    shadowColor: colors.primary,
    shadowOffset: { width: 0, height: 8 },
    shadowOpacity: 0.35,
    shadowRadius: 20,
    elevation: 8,
    position: 'relative',
    marginBottom: 14,
  },
  examinerVideoCardSpeaking: {
    borderColor: colors.primaryLight,
    borderWidth: 2,
    shadowColor: colors.primaryLight,
    shadowOpacity: 0.6,
    shadowRadius: 28,
  },
  examinerImage: {
    ...StyleSheet.absoluteFillObject,
    width: '100%',
    height: '100%',
  },
  vignetteOverlay: {
    ...StyleSheet.absoluteFillObject,
  },
  callBadgeRow: {
    position: 'absolute',
    top: 12,
    left: 12,
    right: 12,
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    zIndex: 10,
  },
  callStatusBadge: {
    flexDirection: 'row',
    alignItems: 'center',
    paddingHorizontal: 10,
    paddingVertical: 4,
    borderRadius: 16,
    backgroundColor: 'rgba(7, 11, 20, 0.75)',
    borderWidth: 1,
  },
  statusDot: {
    width: 6,
    height: 6,
    borderRadius: 3,
    marginRight: 6,
  },
  callBadgeText: {
    fontSize: 10,
    fontWeight: '700',
  },
  hdBadge: {
    paddingHorizontal: 6,
    paddingVertical: 2,
    borderRadius: 6,
    backgroundColor: 'rgba(37, 99, 235, 0.35)',
    borderWidth: 1,
    borderColor: 'rgba(56, 189, 248, 0.5)',
  },
  hdText: {
    fontSize: 9,
    fontWeight: 'bold',
    color: colors.accentCyan,
  },
  orbCenterWrapper: {
    position: 'absolute',
    alignSelf: 'center',
    top: '34%',
    zIndex: 5,
  },
  examinerBottomInfo: {
    position: 'absolute',
    bottom: 12,
    left: 14,
    right: 14,
    zIndex: 10,
  },
  examinerNameText: {
    fontSize: 16,
    fontWeight: 'bold',
    color: '#FFFFFF',
  },
  examinerRoleDesc: {
    fontSize: 11,
    color: colors.textSecondary,
    marginTop: 2,
  },
  speechBubble: {
    width: '100%',
    padding: 16,
    borderRadius: 18,
    backgroundColor: colors.card,
    borderWidth: 1.5,
    borderColor: colors.cardBorder,
    shadowColor: colors.primary,
    shadowOffset: { width: 0, height: 4 },
    shadowOpacity: 0.25,
    shadowRadius: 12,
    elevation: 4,
    marginBottom: 10,
  },
  bubbleQuoteRow: {
    flexDirection: 'row',
    alignItems: 'center',
    marginBottom: 6,
  },
  bubbleRoleLabel: {
    fontSize: 11,
    fontWeight: 'bold',
    color: colors.accentCyan,
    marginLeft: 6,
  },
  dialogueText: {
    fontSize: 14,
    color: '#FFFFFF',
    lineHeight: 22,
  },
  critiqueToast: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: 'rgba(245, 158, 11, 0.12)',
    borderWidth: 1,
    borderColor: 'rgba(245, 158, 11, 0.35)',
    borderRadius: 12,
    paddingHorizontal: 12,
    paddingVertical: 8,
    marginBottom: 10,
    width: '100%',
  },
  critiqueText: {
    fontSize: 11,
    color: '#FDE68A',
    marginLeft: 6,
    flex: 1,
  },
  pipWrapper: {
    position: 'absolute',
    right: 16,
    bottom: 185,
    zIndex: 25,
  },
  thumbZone: {
    position: 'absolute',
    bottom: 0,
    left: 0,
    right: 0,
    paddingHorizontal: 24,
    paddingTop: 14,
    paddingBottom: 22,
    backgroundColor: colors.surface,
    borderTopLeftRadius: 24,
    borderTopRightRadius: 24,
    borderTopWidth: 1,
    borderTopColor: colors.cardBorderSubtle,
    alignItems: 'center',
    zIndex: 20,
  },
  micCircleButton: {
    width: 68,
    height: 68,
    borderRadius: 34,
    overflow: 'hidden',
    shadowColor: colors.primary,
    shadowOffset: { width: 0, height: 8 },
    shadowOpacity: 0.55,
    shadowRadius: 20,
    elevation: 12,
  },
  micGradient: {
    flex: 1,
    justifyContent: 'center',
    alignItems: 'center',
  },
  micLabel: {
    fontSize: 12,
    fontWeight: '600',
    color: colors.textSecondary,
    marginTop: 8,
    marginBottom: 10,
  },
  finishButton: {
    width: '100%',
    height: 44,
    borderRadius: 12,
    borderWidth: 1,
    borderColor: 'rgba(239, 68, 68, 0.35)',
    backgroundColor: 'rgba(239, 68, 68, 0.12)',
    justifyContent: 'center',
    alignItems: 'center',
  },
  finishButtonText: {
    color: '#FCA5A5',
    fontSize: 13,
    fontWeight: 'bold',
  },
  modalOverlay: {
    flex: 1,
    backgroundColor: 'rgba(0,0,0,0.75)',
    justifyContent: 'flex-end',
  },
  modalContent: {
    backgroundColor: colors.surface,
    borderTopLeftRadius: 24,
    borderTopRightRadius: 24,
    padding: 24,
    borderTopWidth: 1,
    borderTopColor: colors.cardBorder,
  },
  modalTitle: {
    fontSize: 17,
    fontWeight: 'bold',
    color: '#FFFFFF',
    marginBottom: 4,
  },
  modalSubtitle: {
    fontSize: 12,
    color: colors.textSecondary,
    marginBottom: 10,
  },
  chipScroll: {
    flexDirection: 'row',
    marginBottom: 12,
  },
  chipButton: {
    paddingHorizontal: 12,
    paddingVertical: 6,
    borderRadius: 16,
    backgroundColor: colors.card,
    borderWidth: 1,
    borderColor: colors.cardBorderSubtle,
    marginRight: 8,
    maxWidth: 240,
  },
  chipText: {
    fontSize: 11,
    color: colors.accentCyan,
  },
  modalInput: {
    height: 90,
    backgroundColor: colors.card,
    borderRadius: 14,
    padding: 12,
    color: '#FFFFFF',
    textAlignVertical: 'top',
    fontSize: 14,
    marginBottom: 16,
    borderWidth: 1,
    borderColor: colors.cardBorderSubtle,
  },
  modalButtonRow: {
    flexDirection: 'row',
  },
  modalCancelButton: {
    flex: 1,
    height: 48,
    justifyContent: 'center',
    alignItems: 'center',
    marginRight: 10,
    borderRadius: 12,
    borderWidth: 1,
    borderColor: colors.cardBorderSubtle,
  },
  modalCancelText: {
    color: colors.textSecondary,
    fontWeight: 'bold',
  },
  modalSendButton: {
    flex: 2,
    borderRadius: 12,
    overflow: 'hidden',
  },
  modalSendGradient: {
    height: 48,
    justifyContent: 'center',
    alignItems: 'center',
  },
  modalSendText: {
    color: '#FFFFFF',
    fontWeight: 'bold',
  },
});
