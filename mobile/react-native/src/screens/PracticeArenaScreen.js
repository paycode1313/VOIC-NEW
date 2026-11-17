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
  KeyboardAvoidingView,
  Platform,
} from 'react-native';
import { LinearGradient } from 'expo-linear-gradient';
import * as Speech from 'expo-speech';
import { CameraView, useCameraPermissions } from 'expo-camera';
import {
  useAudioRecorder,
  useAudioRecorderState,
  useAudioPlayer,
  useAudioPlayerStatus,
  RecordingPresets,
  requestRecordingPermissionsAsync,
  setAudioModeAsync,
} from 'expo-audio';
import { Feather, MaterialCommunityIcons } from '@expo/vector-icons';
import { useSafeAreaInsets } from 'react-native-safe-area-context';
import { colors } from '../constants/theme';
import { apiService } from '../services/apiService';

export default function PracticeArenaScreen({ sessionData, onFinishSession, onBack }) {
  const insets = useSafeAreaInsets();
  const {
    sessionId,
    role,
    scenario,
    initialGreeting,
    initialAudioUrl,
    initialPayload,
    sessionType,
  } = sessionData;
  const isCoach = sessionType === 'coach' || initialPayload?.session_type === 'coach';

  const [secondsElapsed, setSecondsElapsed] = useState(0);
  const [isTtsSpeaking, setIsTtsSpeaking] = useState(false);
  const [isProcessing, setIsProcessing] = useState(false);
  const [isFinishing, setIsFinishing] = useState(false);
  const [currentAiDialogue, setCurrentAiDialogue] = useState(initialGreeting);
  const [currentAudioUrl, setCurrentAudioUrl] = useState(initialAudioUrl || null);
  const [lastUserTranscript, setLastUserTranscript] = useState('');
  const [partTitle, setPartTitle] = useState(
    sessionData.partTitle || initialPayload?.part_title || ''
  );
  const [parts, setParts] = useState(initialPayload?.parts || []);
  const [cueCard, setCueCard] = useState(initialPayload?.cue_card || null);
  const [showCueCard, setShowCueCard] = useState(true);
  const [messages, setMessages] = useState(
    initialGreeting ? [{ id: 'init-ai', sender: 'ai', text: initialGreeting }] : []
  );

  const answerStartRef = useRef(Date.now());
  const recordStartedAtRef = useRef(0);
  const [isMuted, setIsMuted] = useState(false);
  const [cameraOn, setCameraOn] = useState(true);
  const [showCaptions, setShowCaptions] = useState(true);

  const [isAnswerModalOpen, setIsAnswerModalOpen] = useState(false);
  const [isTranscriptOpen, setIsTranscriptOpen] = useState(false);
  const [speechInput, setSpeechInput] = useState('');

  // Camera permission for the bottom Zoom self-video tile
  const [cameraPermission, requestCameraPermission] = useCameraPermissions();

  // Native Audio Recorder & Player (expo-audio)
  const audioRecorder = useAudioRecorder(RecordingPresets.HIGH_QUALITY);
  const recorderState = useAudioRecorderState(audioRecorder);
  const audioPlayer = useAudioPlayer(null);
  const playerStatus = useAudioPlayerStatus(audioPlayer);

  const isRecording = Boolean(recorderState?.isRecording);
  const recordSeconds = Math.max(1, Math.round((recorderState?.durationMillis || 0) / 1000));
  const isAiSpeaking = isTtsSpeaking || Boolean(playerStatus?.playing);

  const pulseAnim = useRef(new Animated.Value(1)).current;
  const speakingBorderAnim = useRef(new Animated.Value(0)).current;

  useEffect(() => {
    if (!cameraOn) return;
    (async () => {
      if (!cameraPermission) {
        await requestCameraPermission();
      } else if (!cameraPermission.granted && cameraPermission.canAskAgain) {
        await requestCameraPermission();
      }
    })();
  }, [cameraPermission, cameraOn]);

  useEffect(() => {
    const pulsing = Animated.loop(
      Animated.sequence([
        Animated.timing(pulseAnim, { toValue: 1.4, duration: 750, useNativeDriver: true }),
        Animated.timing(pulseAnim, { toValue: 1, duration: 750, useNativeDriver: true }),
      ])
    );
    pulsing.start();
    return () => pulsing.stop();
  }, []);

  useEffect(() => {
    if (isAiSpeaking) {
      Animated.loop(
        Animated.sequence([
          Animated.timing(speakingBorderAnim, { toValue: 1, duration: 550, useNativeDriver: false }),
          Animated.timing(speakingBorderAnim, { toValue: 0, duration: 550, useNativeDriver: false }),
        ])
      ).start();
    } else {
      speakingBorderAnim.stopAnimation();
      speakingBorderAnim.setValue(0);
    }
  }, [isAiSpeaking]);

  useEffect(() => {
    if (playerStatus?.didJustFinish) {
      answerStartRef.current = Date.now();
    }
  }, [playerStatus?.didJustFinish]);

  const speakingBorderColor = speakingBorderAnim.interpolate({
    inputRange: [0, 1],
    outputRange: ['rgba(59, 130, 246, 0.45)', 'rgba(56, 189, 248, 1)'],
  });

  const roleType = role?.role_type || 'dosen_penguji';

  const getAvatarSource = () => {
    if (roleType === 'hrd') return require('../../assets/avatars/hrd.jpg');
    if (roleType === 'investor') return require('../../assets/avatars/investor.jpg');
    return require('../../assets/avatars/dosen.jpg');
  };

  const getExaminerSubtitle = () => {
    if (role?.title) return role.title;
    if (roleType === 'hrd') return 'HRD Recruiter';
    if (roleType === 'investor') return 'Investor VC';
    return 'Dosen Penguji';
  };

  const promptSuggestions =
    roleType === 'hrd'
      ? [
          'Saya berpengalaman membangun fitur end-to-end dan terbiasa berkolaborasi lintas tim.',
          'Saat menghadapi tenggat ketat, saya membagi prioritas masalah lalu menyelesaikannya bertahap.',
          'Kekuatan utama saya adalah komunikasi yang jelas dan cepat beradaptasi dengan target baru.',
        ]
      : roleType === 'investor'
        ? [
            'Kami menyelesaikan masalah pencatatan UMKM yang masih manual dengan sistem kasir otomatis.',
            'Saat ini kami memiliki 350 pengguna aktif dengan pertumbuhan transaksi 25% per bulan.',
            'Model bisnis kami berbasis langganan bulanan dengan margin kotor 72% dan retensi tinggi.',
          ]
        : [
            'Penelitian saya berfokus pada integrasi AI untuk melatih komunikasi mahasiswa secara objektif.',
            'Metodologi yang saya gunakan divalidasi dengan pengujian empiris dan metrik terukur.',
            'Kontribusi utama penelitian ini adalah meningkatkan akurasi evaluasi dibanding metode konvensional.',
          ];

  const stopAllAiAudio = () => {
    Speech.stop();
    setIsTtsSpeaking(false);
    try {
      audioPlayer.pause();
    } catch (e) {}
  };

  const playExaminerVoice = async (text, audioUrl) => {
    if (isMuted) return;
    stopAllAiAudio();

    try {
      await setAudioModeAsync({
        allowsRecording: false,
        playsInSilentMode: true,
      });
    } catch (e) {}

    if (audioUrl) {
      try {
        audioPlayer.replace(audioUrl);
        audioPlayer.play();
        return;
      } catch (e) {}
    }

    if (!text) return;
    const isEn = sessionData?.language === 'en';
    setIsTtsSpeaking(true);
    Speech.speak(text, {
      language: isEn ? 'en-US' : 'id-ID',
      rate: 0.96,
      pitch: 1.0,
      onStart: () => setIsTtsSpeaking(true),
      onDone: () => {
        setIsTtsSpeaking(false);
        answerStartRef.current = Date.now();
      },
      onStopped: () => setIsTtsSpeaking(false),
      onError: () => setIsTtsSpeaking(false),
    });
  };

  useEffect(() => {
    const timer = setInterval(() => {
      setSecondsElapsed((prev) => prev + 1);
    }, 1000);

    if (initialGreeting || initialAudioUrl) {
      playExaminerVoice(initialGreeting, initialAudioUrl);
    }

    return () => {
      clearInterval(timer);
      stopAllAiAudio();
    };
  }, []);

  const formatTimer = (totalSeconds) => {
    const m = Math.floor(totalSeconds / 60).toString().padStart(2, '0');
    const s = (totalSeconds % 60).toString().padStart(2, '0');
    return `${m}:${s}`;
  };

  const applyExaminerResponse = (res, userTextShown) => {
    if (userTextShown) {
      setLastUserTranscript(userTextShown);
      setMessages((prev) => [...prev, { id: `u-${Date.now()}`, sender: 'user', text: userTextShown }]);
    }

    const reply = res.examiner_message?.text || '';
    const nextAudioUrl = res.examiner_message?.audio_url || null;
    setCurrentAiDialogue(reply);
    setCurrentAudioUrl(nextAudioUrl);
    if (reply) {
      setMessages((prev) => [...prev, { id: `ai-${Date.now()}`, sender: 'ai', text: reply }]);
    }
    if (res.part_title) setPartTitle(res.part_title);
    if (Array.isArray(res.parts)) setParts(res.parts);
    setCueCard(res.cue_card || null);
    if (res.cue_card) setShowCueCard(true);

    playExaminerVoice(reply, nextAudioUrl);

    if (res.completed) {
      finishSession();
    }
  };

  const startVoiceRecording = async () => {
    if (isProcessing || isFinishing) return;

    try {
      stopAllAiAudio();
      const permission = await requestRecordingPermissionsAsync();
      if (!permission.granted) {
        Alert.alert(
          'Izin Mikrofon Diperlukan',
          'Aktifkan izin mikrofon agar Anda dapat menjawab langsung dengan suara, atau gunakan tombol Ketik.'
        );
        return;
      }

      await setAudioModeAsync({
        allowsRecording: true,
        playsInSilentMode: true,
      });

      await audioRecorder.prepareToRecordAsync();
      recordStartedAtRef.current = Date.now();
      audioRecorder.record();
    } catch (err) {
      Alert.alert(
        'Mikrofon Tidak Tersedia',
        'Gagal memulai perekaman suara. Anda tetap dapat menjawab menggunakan tombol Ketik.'
      );
    }
  };

  const cancelVoiceRecording = async () => {
    try {
      if (isRecording) {
        await audioRecorder.stop();
      }
      await setAudioModeAsync({ allowsRecording: false, playsInSilentMode: true });
    } catch (e) {}
  };

  const stopAndSendVoiceRecording = async () => {
    if (!isRecording) return;

    const elapsedSec = Math.max(
      1,
      Math.round((Date.now() - (recordStartedAtRef.current || Date.now())) / 1000)
    );

    try {
      const stopResult = await audioRecorder.stop();
      await setAudioModeAsync({ allowsRecording: false, playsInSilentMode: true });
      const uri = stopResult?.url || audioRecorder.uri || audioRecorder.getStatus?.()?.url;

      if (!uri) {
        Alert.alert('Rekaman Kosong', 'Suara tidak terekam. Tekan Bicara agak lama sedikit lalu coba kirim lagi.');
        return;
      }

      setIsProcessing(true);
      const res = await apiService.answerExamAudio(sessionId, uri, elapsedSec, secondsElapsed);
      setIsProcessing(false);

      if (!res.success) {
        if (res.code === 'retry') {
          Alert.alert('Suara Kurang Jelas', res.message || 'Coba dekatkan mikrofon dan ucapkan ulang.');
          return;
        }
        if (res.code === 'stt_unavailable') {
          Alert.alert(
            'Transkrip Suara Tidak Aktif',
            'Layanan pengenal suara sedang offline. Silakan gunakan tombol Ketik.'
          );
          return;
        }
        Alert.alert('Respon AI', res.message || 'Gagal memproses rekaman suara.');
        return;
      }

      applyExaminerResponse(res, res.transcript || '(Jawaban suara)');
    } catch (err) {
      setIsProcessing(false);
      Alert.alert(
        'Gagal Mengirim Suara',
        err?.message || 'Periksa koneksi ke server Laravel.'
      );
    }
  };

  const handleSendSpeech = async (spokenText) => {
    const trimmed = spokenText.trim();
    if (!trimmed) return;

    stopAllAiAudio();
    setIsAnswerModalOpen(false);
    setSpeechInput('');
    setIsProcessing(true);
    const answerSeconds = Math.max(1, Math.round((Date.now() - answerStartRef.current) / 1000));

    try {
      const res = await apiService.answerExam(sessionId, trimmed, answerSeconds, secondsElapsed);
      setIsProcessing(false);

      if (!res.success) {
        Alert.alert('Respon AI', res.message || 'Tidak ada balasan dari AI.');
        return;
      }

      applyExaminerResponse(res, trimmed);
    } catch (err) {
      setIsProcessing(false);
      Alert.alert('Gagal Mengirim Jawaban', err?.message || 'Periksa koneksi ke server Laravel.');
    }
  };

  const finishSession = async () => {
    if (isFinishing) return;
    setIsFinishing(true);
    stopAllAiAudio();
    await cancelVoiceRecording();
    try {
      const res = await apiService.finishExam(sessionId, Math.max(secondsElapsed, 1));
      if (res.success && res.evaluation) {
        onFinishSession(res.evaluation);
      } else {
        setIsFinishing(false);
        Alert.alert('Gagal Membuat Rapor', res.message || 'Coba lagi beberapa saat.');
      }
    } catch (err) {
      setIsFinishing(false);
      Alert.alert('Gagal Membuat Rapor', err?.message || 'Periksa koneksi ke server Laravel.');
    }
  };

  const handleFinishPrompt = () => {
    stopAllAiAudio();
    Alert.alert(
      isCoach ? 'Akhiri Sesi Coaching?' : 'Akhiri Sesi & Lihat Rapor?',
      isCoach
        ? 'Catatan ringkasan coaching akan dibuat dari obrolan sejauh ini.'
        : 'Rapor dan Band Score akan dihitung dari seluruh jawaban yang sudah kamu berikan.',
      [
        { text: 'Lanjut Bicara', style: 'cancel' },
        { text: 'Akhiri & Nilai', style: 'default', onPress: finishSession },
      ]
    );
  };

  const handleExit = () => {
    stopAllAiAudio();
    cancelVoiceRecording();
    Alert.alert(
      'Keluar dari Ruang Video Call?',
      'Sesi yang sedang berjalan akan dijeda jika Anda kembali ke halaman utama.',
      [
        { text: 'Batal', style: 'cancel' },
        { text: 'Keluar', style: 'destructive', onPress: onBack },
      ]
    );
  };

  return (
    <View style={styles.container}>
      {/* 1. Compact Zoom Top Bar */}
      <View style={[styles.header, { paddingTop: Math.max(insets.top, 10) }]}>
        <TouchableOpacity onPress={handleExit} style={styles.backButton} activeOpacity={0.8}>
          <Feather name="chevron-left" size={20} color="#FFFFFF" />
        </TouchableOpacity>

        <View style={styles.headerTitleContainer}>
          <View style={styles.headerMeetingRow}>
            <Feather name="shield" size={12} color="#34D399" />
            <Text style={styles.headerRoleName} numberOfLines={1}>
              {scenario?.name || role?.name || 'VOIC Meeting'}
            </Text>
          </View>
          <Text style={styles.headerScenario} numberOfLines={1}>
            {role?.name || 'VOIC AI'} {partTitle ? `· ${partTitle}` : ''}
          </Text>
        </View>

        <TouchableOpacity
          onPress={() => setShowCaptions((prev) => !prev)}
          style={[styles.ccToggleBtn, showCaptions && styles.ccToggleBtnActive]}
          activeOpacity={0.8}
        >
          <Text style={[styles.ccToggleText, showCaptions && { color: '#FFFFFF' }]}>CC</Text>
        </TouchableOpacity>

        <View style={styles.timerBadge}>
          <Animated.View style={[styles.timerDot, { transform: [{ scale: pulseAnim }] }]} />
          <Text style={styles.timerText}>{formatTimer(secondsElapsed)}</Text>
        </View>
      </View>

      {/* 2. Compact 3-Part Indicator Strip */}
      {!isCoach && parts.length > 0 && (
        <View style={styles.partsStrip}>
          {parts.map((part) => {
            const isCurrent = part.status === 'current';
            const isDone = part.status === 'done';
            return (
              <View
                key={part.number}
                style={[
                  styles.partPill,
                  isCurrent && styles.partPillCurrent,
                  isDone && styles.partPillDone,
                ]}
              >
                {isDone && <Feather name="check" size={10} color="#34D399" />}
                <Text
                  style={[
                    styles.partPillText,
                    isCurrent && styles.partPillTextCurrent,
                    isDone && styles.partPillTextDone,
                  ]}
                  numberOfLines={1}
                >
                  {part.number}. {part.title}
                </Text>
              </View>
            );
          })}
        </View>
      )}

      {/* 3. ZOOM 2-WAY SPLIT VIDEO STAGE (Top: Examiner AI, Bottom: YOU!) */}
      <View style={styles.zoomGrid}>
        {/* TOP TILE: AI Examiner Video */}
        <Animated.View
          style={[
            styles.zoomTile,
            isAiSpeaking && { borderColor: speakingBorderColor, borderWidth: 2 },
          ]}
        >
          <Image source={getAvatarSource()} style={styles.fullTileMedia} resizeMode="cover" />

          <LinearGradient
            colors={['rgba(7,11,20,0.45)', 'transparent', 'rgba(7,11,20,0.88)']}
            locations={[0, 0.45, 1]}
            style={StyleSheet.absoluteFillObject}
          />

          {/* Top Left: Examiner Status + Replay Button */}
          <View style={styles.tileTopRow}>
            <View style={styles.statusChip}>
              <Animated.View
                style={[
                  styles.statusDot,
                  {
                    backgroundColor: isAiSpeaking
                      ? colors.accentCyan
                      : isProcessing
                        ? '#FBBF24'
                        : '#34D399',
                    transform: [{ scale: pulseAnim }],
                  },
                ]}
              />
              <Text style={styles.statusChipText}>
                {isAiSpeaking
                  ? 'Berbicara...'
                  : isProcessing
                    ? 'Memproses suara...'
                    : 'Mendengarkan'}
              </Text>
            </View>

            <TouchableOpacity
              onPress={() => playExaminerVoice(currentAiDialogue, currentAudioUrl)}
              style={styles.replayMiniBtn}
              activeOpacity={0.8}
            >
              <Feather name="volume-2" size={12} color="#FFFFFF" />
              <Text style={styles.replayMiniText}>Ulangi</Text>
            </TouchableOpacity>
          </View>

          {/* Compact Live Subtitle Overlay at bottom of AI tile (Zoom CC style) */}
          {showCaptions && (
            <View style={styles.captionOverlayWrap}>
              {isProcessing ? (
                <View style={styles.captionPill}>
                  <ActivityIndicator size="small" color={colors.accentCyan} />
                  <Text style={styles.captionText}>Menyiapkan tanggapan penguji...</Text>
                </View>
              ) : currentAiDialogue ? (
                <View style={styles.captionPill}>
                  <Text style={styles.captionText} numberOfLines={3}>
                    {currentAiDialogue}
                  </Text>
                </View>
              ) : null}
            </View>
          )}

          {/* Zoom Nameplate: Bottom Left */}
          <View style={styles.zoomNameplate}>
            {isAiSpeaking ? (
              <Feather name="mic" size={11} color={colors.accentCyan} />
            ) : (
              <Feather name="user-check" size={11} color="#94A3B8" />
            )}
            <Text style={styles.zoomNameText}>
              {role?.name || 'Penguji AI'} · {getExaminerSubtitle()}
            </Text>
          </View>
        </Animated.View>

        {/* Optional Floating Part 2 Cue Card Pill (between tiles when active) */}
        {cueCard && showCueCard && (
          <View style={styles.cueBanner}>
            <View style={styles.cueBannerTop}>
              <View style={{ flexDirection: 'row', alignItems: 'center', gap: 6, flex: 1 }}>
                <Feather name="clipboard" size={13} color="#FBBF24" />
                <Text style={styles.cueBannerTitle} numberOfLines={1}>
                  KARTU TOPIK: {cueCard.prompt}
                </Text>
              </View>
              <TouchableOpacity onPress={() => setShowCueCard(false)}>
                <Feather name="chevron-up" size={16} color="#FDE68A" />
              </TouchableOpacity>
            </View>
            {Array.isArray(cueCard.points) && (
              <Text style={styles.cueBannerPoints} numberOfLines={2}>
                Poin: {cueCard.points.join(' · ')}
              </Text>
            )}
          </View>
        )}

        {/* BOTTOM TILE: YOUR LIVE ZOOM CAMERA */}
        <View
          style={[
            styles.zoomTile,
            isRecording && { borderColor: '#EF4444', borderWidth: 2 },
          ]}
        >
          {!cameraOn ? (
            <View style={styles.cameraOffStage}>
              <View style={styles.selfAvatarFallback}>
                <Feather name="user" size={32} color="#94A3B8" />
              </View>
              <Text style={styles.cameraOffLabel}>Kamera Anda Dimatikan</Text>
              <TouchableOpacity
                onPress={() => setCameraOn(true)}
                style={styles.enableCamBtn}
                activeOpacity={0.85}
              >
                <Feather name="video" size={13} color="#FFFFFF" />
                <Text style={styles.enableCamText}>Nyalakan Kamera</Text>
              </TouchableOpacity>
            </View>
          ) : !cameraPermission ? (
            <View style={styles.cameraOffStage}>
              <ActivityIndicator size="small" color={colors.accentCyan} />
              <Text style={styles.cameraOffLabel}>Menyiapkan kamera...</Text>
            </View>
          ) : !cameraPermission.granted ? (
            <View style={styles.cameraOffStage}>
              <Feather name="video" size={28} color={colors.accentCyan} />
              <Text style={styles.cameraOffLabel}>Izinkan kamera agar tampil seperti Zoom</Text>
              <TouchableOpacity
                onPress={requestCameraPermission}
                style={styles.enableCamBtn}
                activeOpacity={0.85}
              >
                <Text style={styles.enableCamText}>Izinkan Kamera</Text>
              </TouchableOpacity>
            </View>
          ) : (
            <CameraView style={styles.fullTileMedia} facing="front" mirror={true} />
          )}

          {/* Subtle bottom gradient for self nameplate legibility */}
          <LinearGradient
            colors={['rgba(7,11,20,0.35)', 'transparent', 'rgba(7,11,20,0.8)']}
            locations={[0, 0.45, 1]}
            style={StyleSheet.absoluteFillObject}
            pointerEvents="none"
          />

          {/* Top Left of Your Tile: Active Mic / Recording Badge */}
          <View style={styles.tileTopRow}>
            <View
              style={[
                styles.statusChip,
                isRecording && {
                  backgroundColor: 'rgba(220, 38, 38, 0.85)',
                  borderColor: 'rgba(252, 165, 165, 0.5)',
                },
              ]}
            >
              <Feather
                name={isRecording ? 'mic' : 'mic-off'}
                size={11}
                color={isRecording ? '#FFFFFF' : '#94A3B8'}
              />
              <Text style={styles.statusChipText}>
                {isRecording
                  ? `Merekam (${formatTimer(recordSeconds)})`
                  : 'Ketuk Bicara untuk jawab'}
              </Text>
            </View>

            {cueCard && !showCueCard && (
              <TouchableOpacity
                onPress={() => setShowCueCard(true)}
                style={styles.cuePillRestore}
              >
                <Feather name="clipboard" size={11} color="#FBBF24" />
                <Text style={styles.cuePillRestoreText}>Kartu Topik</Text>
              </TouchableOpacity>
            )}
          </View>

          {/* Show your last spoken transcript cleanly over bottom of your video */}
          {showCaptions && lastUserTranscript ? (
            <View style={styles.captionOverlayWrap}>
              <View style={[styles.captionPill, { backgroundColor: 'rgba(15, 23, 42, 0.82)' }]}>
                <Text style={styles.userCaptionText} numberOfLines={2}>
                  Anda: "{lastUserTranscript}"
                </Text>
              </View>
            </View>
          ) : null}

          {/* Zoom Nameplate: Bottom Left */}
          <View style={styles.zoomNameplate}>
            <Feather
              name={isRecording ? 'mic' : 'user'}
              size={11}
              color={isRecording ? '#34D399' : '#94A3B8'}
            />
            <Text style={styles.zoomNameText}>Anda (Peserta)</Text>
          </View>
        </View>
      </View>

      {/* 4. Zoom Bottom Control Dock */}
      <View style={[styles.controlDock, { paddingBottom: Math.max(insets.bottom, 12) }]}>
        {isRecording ? (
          <View style={styles.recordingDockRow}>
            <TouchableOpacity
              style={styles.cancelRecBtn}
              onPress={cancelVoiceRecording}
              activeOpacity={0.85}
            >
              <Feather name="x" size={18} color="#FCA5A5" />
              <Text style={styles.cancelRecText}>Batal</Text>
            </TouchableOpacity>

            <View style={styles.recordingPulsePill}>
              <Animated.View style={[styles.recordingRedDot, { transform: [{ scale: pulseAnim }] }]} />
              <Text style={styles.recordingTimerText}>{formatTimer(recordSeconds)}</Text>
              <Text style={styles.recordingHintText}>Sedang bicara...</Text>
            </View>

            <TouchableOpacity
              style={styles.sendRecBtn}
              onPress={stopAndSendVoiceRecording}
              activeOpacity={0.88}
            >
              <LinearGradient
                colors={['#10B981', '#059669']}
                start={{ x: 0, y: 0 }}
                end={{ x: 1, y: 0 }}
                style={styles.sendRecGrad}
              >
                <Feather name="send" size={16} color="#FFFFFF" />
                <Text style={styles.sendRecText}>Kirim</Text>
              </LinearGradient>
            </TouchableOpacity>
          </View>
        ) : (
          <>
            <TouchableOpacity
              style={styles.dockIconBtn}
              onPress={() => {
                if (!isMuted) stopAllAiAudio();
                setIsMuted(!isMuted);
              }}
              activeOpacity={0.8}
            >
              <Feather
                name={isMuted ? 'volume-x' : 'volume-2'}
                size={19}
                color={isMuted ? '#FCA5A5' : '#E2E8F0'}
              />
              <Text style={[styles.dockLabel, isMuted && { color: '#FCA5A5' }]}>
                {isMuted ? 'Bisu' : 'Audio'}
              </Text>
            </TouchableOpacity>

            <TouchableOpacity
              style={styles.dockIconBtn}
              onPress={() => setCameraOn(!cameraOn)}
              activeOpacity={0.8}
            >
              <Feather
                name={cameraOn ? 'video' : 'video-off'}
                size={19}
                color={cameraOn ? '#E2E8F0' : colors.textMuted}
              />
              <Text style={styles.dockLabel}>{cameraOn ? 'Kamera' : 'Kam. Off'}</Text>
            </TouchableOpacity>

            <TouchableOpacity
              style={styles.answerPillBtn}
              disabled={isProcessing || isFinishing}
              onPress={startVoiceRecording}
              activeOpacity={0.88}
            >
              <LinearGradient
                colors={['#2563EB', '#1D4ED8']}
                start={{ x: 0, y: 0 }}
                end={{ x: 1, y: 0 }}
                style={styles.answerPillGrad}
              >
                {isFinishing || isProcessing ? (
                  <ActivityIndicator color="#FFFFFF" size="small" />
                ) : (
                  <>
                    <MaterialCommunityIcons name="microphone" size={20} color="#FFFFFF" />
                    <Text style={styles.answerPillText}>Bicara</Text>
                  </>
                )}
              </LinearGradient>
            </TouchableOpacity>

            <TouchableOpacity
              style={styles.dockIconBtn}
              disabled={isProcessing || isFinishing}
              onPress={() => {
                stopAllAiAudio();
                setIsAnswerModalOpen(true);
              }}
              activeOpacity={0.8}
            >
              <Feather name="edit-3" size={18} color="#E2E8F0" />
              <Text style={styles.dockLabel}>Ketik</Text>
            </TouchableOpacity>

            <TouchableOpacity
              style={styles.dockIconBtn}
              onPress={() => setIsTranscriptOpen(true)}
              activeOpacity={0.8}
            >
              <Feather name="message-square" size={18} color="#E2E8F0" />
              <Text style={styles.dockLabel}>Chat</Text>
            </TouchableOpacity>

            <TouchableOpacity
              style={styles.endCallBtn}
              onPress={handleFinishPrompt}
              disabled={isFinishing}
              activeOpacity={0.85}
            >
              <Feather name="phone-off" size={18} color="#FFFFFF" />
            </TouchableOpacity>
          </>
        )}
      </View>

      {/* 5. Keyboard Fallback Sheet Modal */}
      <Modal visible={isAnswerModalOpen} transparent animationType="slide">
        <KeyboardAvoidingView
          behavior={Platform.OS === 'ios' ? 'padding' : 'height'}
          style={styles.modalOverlay}
        >
          <View style={[styles.modalContent, { paddingBottom: Math.max(insets.bottom, 20) }]}>
            <View style={styles.modalHandle} />

            <View style={styles.modalHeaderRow}>
              <View style={styles.modalTitleGroup}>
                <Feather name="edit-3" size={18} color={colors.accentCyan} />
                <Text style={styles.modalTitle}>Ketik Jawaban (Alternatif)</Text>
              </View>
              <TouchableOpacity onPress={() => setIsAnswerModalOpen(false)}>
                <Feather name="x" size={20} color={colors.textSecondary} />
              </TouchableOpacity>
            </View>

            <View style={styles.pinnedQuestionBox}>
              <Text style={styles.pinnedQuestionLabel}>PERTANYAAN PENGUJI:</Text>
              <Text style={styles.pinnedQuestionText} numberOfLines={3}>
                {currentAiDialogue}
              </Text>
            </View>

            <TextInput
              style={styles.modalInput}
              multiline
              placeholder="Ketik jawabanmu di sini..."
              placeholderTextColor={colors.textMuted}
              value={speechInput}
              onChangeText={setSpeechInput}
              autoFocus
            />

            <Text style={styles.suggestionHeading}>Contoh awalan jawaban (ketuk untuk isi cepat):</Text>
            <ScrollView
              horizontal
              showsHorizontalScrollIndicator={false}
              style={styles.suggestionScroll}
            >
              {promptSuggestions.map((sugg, idx) => (
                <TouchableOpacity
                  key={idx}
                  onPress={() => setSpeechInput(sugg)}
                  style={styles.suggestionChip}
                  activeOpacity={0.8}
                >
                  <Text style={styles.suggestionChipText} numberOfLines={2}>
                    {sugg}
                  </Text>
                </TouchableOpacity>
              ))}
            </ScrollView>

            <View style={styles.modalButtonRow}>
              <TouchableOpacity
                style={styles.modalCancelButton}
                onPress={() => setIsAnswerModalOpen(false)}
              >
                <Text style={styles.modalCancelText}>Batal</Text>
              </TouchableOpacity>

              <TouchableOpacity
                style={[styles.modalSendButton, !speechInput.trim() && { opacity: 0.5 }]}
                disabled={!speechInput.trim()}
                onPress={() => handleSendSpeech(speechInput)}
              >
                <LinearGradient
                  colors={['#2563EB', '#1D4ED8']}
                  style={styles.modalSendGradient}
                  start={{ x: 0, y: 0 }}
                  end={{ x: 1, y: 0 }}
                >
                  <Feather name="send" size={16} color="#FFFFFF" />
                  <Text style={styles.modalSendText}>Kirim Jawaban</Text>
                </LinearGradient>
              </TouchableOpacity>
            </View>
          </View>
        </KeyboardAvoidingView>
      </Modal>

      {/* 6. Transcript / Chat Modal */}
      <Modal visible={isTranscriptOpen} transparent animationType="slide">
        <View style={styles.modalOverlay}>
          <View style={[styles.transcriptContent, { paddingBottom: Math.max(insets.bottom, 20) }]}>
            <View style={styles.modalHandle} />
            <View style={styles.modalHeaderRow}>
              <Text style={styles.modalTitle}>Transkrip Percakapan</Text>
              <TouchableOpacity onPress={() => setIsTranscriptOpen(false)}>
                <Feather name="x" size={20} color={colors.textSecondary} />
              </TouchableOpacity>
            </View>
            <ScrollView style={styles.transcriptScroll} showsVerticalScrollIndicator={false}>
              {messages.map((msg) => (
                <View
                  key={msg.id}
                  style={[
                    styles.transcriptBubble,
                    msg.sender === 'user' ? styles.transcriptUser : styles.transcriptAi,
                  ]}
                >
                  <Text style={styles.transcriptSender}>
                    {msg.sender === 'user' ? 'Anda' : role?.name || 'Penguji AI'}
                  </Text>
                  <Text style={styles.transcriptText}>{msg.text}</Text>
                </View>
              ))}
            </ScrollView>
          </View>
        </View>
      </Modal>
    </View>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: '#050811',
  },

  header: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    paddingHorizontal: 12,
    paddingBottom: 10,
    backgroundColor: '#090E1A',
    borderBottomWidth: 1,
    borderBottomColor: 'rgba(255,255,255,0.06)',
    gap: 8,
  },
  backButton: {
    width: 34,
    height: 34,
    borderRadius: 17,
    borderWidth: 1,
    borderColor: 'rgba(255,255,255,0.12)',
    justifyContent: 'center',
    alignItems: 'center',
    backgroundColor: 'rgba(255,255,255,0.05)',
  },
  headerTitleContainer: {
    flex: 1,
  },
  headerMeetingRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 5,
  },
  headerRoleName: {
    fontSize: 14,
    fontWeight: '800',
    color: '#FFFFFF',
    flexShrink: 1,
  },
  headerScenario: {
    fontSize: 11,
    color: colors.textSecondary,
    marginTop: 1,
  },
  ccToggleBtn: {
    paddingHorizontal: 8,
    paddingVertical: 5,
    borderRadius: 8,
    backgroundColor: 'rgba(255,255,255,0.06)',
    borderWidth: 1,
    borderColor: 'rgba(255,255,255,0.12)',
  },
  ccToggleBtnActive: {
    backgroundColor: 'rgba(37, 99, 235, 0.35)',
    borderColor: colors.accentCyan,
  },
  ccToggleText: {
    fontSize: 10.5,
    fontWeight: '800',
    color: colors.textSecondary,
  },
  timerBadge: {
    flexDirection: 'row',
    alignItems: 'center',
    paddingHorizontal: 10,
    paddingVertical: 5,
    borderRadius: 999,
    backgroundColor: 'rgba(239,68,68,0.14)',
    borderWidth: 1,
    borderColor: 'rgba(239,68,68,0.35)',
  },
  timerDot: {
    width: 6,
    height: 6,
    borderRadius: 3,
    backgroundColor: colors.danger,
    marginRight: 5,
  },
  timerText: {
    fontSize: 12,
    fontWeight: '800',
    color: '#FFFFFF',
    fontVariant: ['tabular-nums'],
  },

  partsStrip: {
    flexDirection: 'row',
    paddingHorizontal: 12,
    paddingVertical: 7,
    gap: 6,
    backgroundColor: '#090E1A',
    borderBottomWidth: 1,
    borderBottomColor: 'rgba(255,255,255,0.05)',
  },
  partPill: {
    flex: 1,
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    gap: 4,
    paddingHorizontal: 6,
    paddingVertical: 4.5,
    borderRadius: 999,
    backgroundColor: 'rgba(255,255,255,0.04)',
    borderWidth: 1,
    borderColor: 'rgba(255,255,255,0.06)',
  },
  partPillCurrent: {
    backgroundColor: 'rgba(37, 99, 235, 0.25)',
    borderColor: 'rgba(56, 189, 248, 0.5)',
  },
  partPillDone: {
    backgroundColor: 'rgba(16, 185, 129, 0.14)',
    borderColor: 'rgba(16, 185, 129, 0.35)',
  },
  partPillText: {
    fontSize: 10,
    fontWeight: '700',
    color: colors.textMuted,
  },
  partPillTextCurrent: {
    color: '#FFFFFF',
  },
  partPillTextDone: {
    color: '#6EE7B7',
  },

  // 50/50 Zoom Split Video Grid
  zoomGrid: {
    flex: 1,
    padding: 10,
    gap: 10,
  },
  zoomTile: {
    flex: 1,
    borderRadius: 18,
    overflow: 'hidden',
    backgroundColor: '#0F172A',
    borderWidth: 1,
    borderColor: 'rgba(255,255,255,0.1)',
    position: 'relative',
  },
  fullTileMedia: {
    ...StyleSheet.absoluteFillObject,
    width: '100%',
    height: '100%',
  },
  tileTopRow: {
    position: 'absolute',
    top: 10,
    left: 10,
    right: 10,
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    zIndex: 10,
  },
  statusChip: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 6,
    paddingHorizontal: 9,
    paddingVertical: 4.5,
    borderRadius: 999,
    backgroundColor: 'rgba(5, 8, 17, 0.72)',
    borderWidth: 1,
    borderColor: 'rgba(255,255,255,0.12)',
  },
  statusDot: {
    width: 7,
    height: 7,
    borderRadius: 3.5,
  },
  statusChipText: {
    fontSize: 10.5,
    fontWeight: '700',
    color: '#F8FAFC',
  },
  replayMiniBtn: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 4,
    paddingHorizontal: 9,
    paddingVertical: 4.5,
    borderRadius: 999,
    backgroundColor: 'rgba(37, 99, 235, 0.75)',
  },
  replayMiniText: {
    fontSize: 10.5,
    fontWeight: '700',
    color: '#FFFFFF',
  },

  captionOverlayWrap: {
    position: 'absolute',
    bottom: 38,
    left: 10,
    right: 10,
    alignItems: 'center',
    zIndex: 10,
  },
  captionPill: {
    backgroundColor: 'rgba(5, 8, 17, 0.82)',
    borderRadius: 12,
    paddingHorizontal: 12,
    paddingVertical: 7,
    borderWidth: 1,
    borderColor: 'rgba(255,255,255,0.1)',
    flexDirection: 'row',
    alignItems: 'center',
    gap: 8,
    maxWidth: '100%',
  },
  captionText: {
    fontSize: 12.5,
    lineHeight: 17.5,
    color: '#F8FAFC',
    fontWeight: '500',
    textAlign: 'center',
  },
  userCaptionText: {
    fontSize: 12,
    lineHeight: 16.5,
    color: '#93C5FD',
    fontWeight: '600',
    textAlign: 'center',
  },

  zoomNameplate: {
    position: 'absolute',
    bottom: 10,
    left: 10,
    flexDirection: 'row',
    alignItems: 'center',
    gap: 5,
    backgroundColor: 'rgba(5, 8, 17, 0.75)',
    paddingHorizontal: 9,
    paddingVertical: 4,
    borderRadius: 8,
    zIndex: 10,
  },
  zoomNameText: {
    fontSize: 11,
    fontWeight: '700',
    color: '#FFFFFF',
  },

  cameraOffStage: {
    flex: 1,
    alignItems: 'center',
    justifyContent: 'center',
    backgroundColor: '#0B121E',
    padding: 16,
    gap: 10,
  },
  selfAvatarFallback: {
    width: 68,
    height: 68,
    borderRadius: 34,
    backgroundColor: 'rgba(255,255,255,0.06)',
    borderWidth: 1,
    borderColor: 'rgba(255,255,255,0.12)',
    alignItems: 'center',
    justifyContent: 'center',
  },
  cameraOffLabel: {
    fontSize: 12.5,
    color: colors.textSecondary,
    fontWeight: '600',
    textAlign: 'center',
  },
  enableCamBtn: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 6,
    backgroundColor: colors.primary,
    paddingHorizontal: 14,
    paddingVertical: 8,
    borderRadius: 999,
  },
  enableCamText: {
    fontSize: 12,
    fontWeight: '700',
    color: '#FFFFFF',
  },

  cueBanner: {
    backgroundColor: 'rgba(245, 158, 11, 0.14)',
    borderRadius: 12,
    borderWidth: 1,
    borderColor: 'rgba(245, 158, 11, 0.4)',
    paddingHorizontal: 12,
    paddingVertical: 8,
  },
  cueBannerTop: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    gap: 8,
  },
  cueBannerTitle: {
    fontSize: 11.5,
    fontWeight: '800',
    color: '#FDE68A',
    flex: 1,
  },
  cueBannerPoints: {
    fontSize: 11,
    color: '#FEF3C7',
    marginTop: 4,
  },
  cuePillRestore: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 4,
    backgroundColor: 'rgba(245, 158, 11, 0.25)',
    borderWidth: 1,
    borderColor: 'rgba(245, 158, 11, 0.45)',
    paddingHorizontal: 9,
    paddingVertical: 4.5,
    borderRadius: 999,
  },
  cuePillRestoreText: {
    fontSize: 10.5,
    fontWeight: '700',
    color: '#FDE68A',
  },

  controlDock: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    paddingHorizontal: 12,
    paddingTop: 10,
    backgroundColor: '#090E1A',
    borderTopWidth: 1,
    borderTopColor: 'rgba(255,255,255,0.08)',
  },
  dockIconBtn: {
    alignItems: 'center',
    justifyContent: 'center',
    minWidth: 42,
    gap: 4,
  },
  dockLabel: {
    fontSize: 9.5,
    fontWeight: '600',
    color: colors.textSecondary,
  },
  answerPillBtn: {
    borderRadius: 999,
    overflow: 'hidden',
    shadowColor: colors.primary,
    shadowOffset: { width: 0, height: 6 },
    shadowOpacity: 0.45,
    shadowRadius: 12,
    elevation: 8,
  },
  answerPillGrad: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    paddingHorizontal: 18,
    height: 46,
    gap: 6,
  },
  answerPillText: {
    fontSize: 14,
    fontWeight: '800',
    color: '#FFFFFF',
  },
  endCallBtn: {
    width: 44,
    height: 44,
    borderRadius: 22,
    backgroundColor: '#DC2626',
    alignItems: 'center',
    justifyContent: 'center',
  },

  recordingDockRow: {
    flex: 1,
    flexDirection: 'row',
    alignItems: 'center',
    gap: 10,
  },
  cancelRecBtn: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 4,
    paddingHorizontal: 12,
    height: 46,
    borderRadius: 14,
    backgroundColor: 'rgba(239, 68, 68, 0.14)',
    borderWidth: 1,
    borderColor: 'rgba(239, 68, 68, 0.35)',
  },
  cancelRecText: {
    fontSize: 12.5,
    fontWeight: '700',
    color: '#FCA5A5',
  },
  recordingPulsePill: {
    flex: 1,
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    gap: 8,
    height: 46,
    borderRadius: 14,
    backgroundColor: 'rgba(255, 255, 255, 0.05)',
    borderWidth: 1,
    borderColor: 'rgba(239, 68, 68, 0.4)',
    paddingHorizontal: 10,
  },
  recordingRedDot: {
    width: 8,
    height: 8,
    borderRadius: 4,
    backgroundColor: '#EF4444',
  },
  recordingTimerText: {
    fontSize: 13.5,
    fontWeight: '800',
    color: '#FFFFFF',
    fontVariant: ['tabular-nums'],
  },
  recordingHintText: {
    fontSize: 11.5,
    color: colors.textSecondary,
    fontWeight: '600',
  },
  sendRecBtn: {
    borderRadius: 14,
    overflow: 'hidden',
  },
  sendRecGrad: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    gap: 6,
    paddingHorizontal: 16,
    height: 46,
  },
  sendRecText: {
    fontSize: 13.5,
    fontWeight: '800',
    color: '#FFFFFF',
  },

  modalOverlay: {
    flex: 1,
    backgroundColor: 'rgba(0,0,0,0.75)',
    justifyContent: 'flex-end',
  },
  modalContent: {
    backgroundColor: '#0B121E',
    borderTopLeftRadius: 26,
    borderTopRightRadius: 26,
    paddingHorizontal: 20,
    paddingTop: 10,
    borderTopWidth: 1,
    borderTopColor: 'rgba(56,189,248,0.2)',
  },
  modalHandle: {
    alignSelf: 'center',
    width: 40,
    height: 4,
    borderRadius: 2,
    backgroundColor: 'rgba(255,255,255,0.2)',
    marginBottom: 14,
  },
  modalHeaderRow: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    marginBottom: 12,
  },
  modalTitleGroup: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 8,
  },
  modalTitle: {
    fontSize: 17,
    fontWeight: '800',
    color: '#FFFFFF',
  },
  pinnedQuestionBox: {
    backgroundColor: 'rgba(255,255,255,0.04)',
    borderRadius: 12,
    borderWidth: 1,
    borderColor: 'rgba(255,255,255,0.08)',
    padding: 11,
    marginBottom: 12,
  },
  pinnedQuestionLabel: {
    fontSize: 9.5,
    fontWeight: '800',
    color: colors.accentCyan,
    letterSpacing: 0.8,
    marginBottom: 4,
  },
  pinnedQuestionText: {
    fontSize: 12.5,
    color: '#CBD5E1',
    lineHeight: 17.5,
  },
  modalInput: {
    height: 115,
    backgroundColor: colors.card,
    borderRadius: 14,
    padding: 14,
    color: '#FFFFFF',
    textAlignVertical: 'top',
    fontSize: 14,
    lineHeight: 20,
    borderWidth: 1,
    borderColor: 'rgba(56,189,248,0.28)',
    marginBottom: 12,
  },
  suggestionHeading: {
    fontSize: 11,
    fontWeight: '700',
    color: colors.textMuted,
    marginBottom: 8,
  },
  suggestionScroll: {
    marginBottom: 16,
  },
  suggestionChip: {
    width: 210,
    backgroundColor: 'rgba(37, 99, 235, 0.12)',
    borderWidth: 1,
    borderColor: 'rgba(56, 189, 248, 0.25)',
    borderRadius: 12,
    padding: 10,
    marginRight: 8,
  },
  suggestionChipText: {
    fontSize: 11.5,
    color: '#E2E8F0',
    lineHeight: 16,
  },
  modalButtonRow: {
    flexDirection: 'row',
    gap: 10,
  },
  modalCancelButton: {
    flex: 1,
    height: 48,
    justifyContent: 'center',
    alignItems: 'center',
    borderRadius: 14,
    borderWidth: 1,
    borderColor: 'rgba(255,255,255,0.12)',
    backgroundColor: 'rgba(255,255,255,0.04)',
  },
  modalCancelText: {
    color: colors.textSecondary,
    fontWeight: '700',
    fontSize: 14,
  },
  modalSendButton: {
    flex: 2,
    borderRadius: 14,
    overflow: 'hidden',
  },
  modalSendGradient: {
    height: 48,
    flexDirection: 'row',
    justifyContent: 'center',
    alignItems: 'center',
    gap: 8,
  },
  modalSendText: {
    color: '#FFFFFF',
    fontWeight: '800',
    fontSize: 14.5,
  },

  transcriptContent: {
    backgroundColor: '#0B121E',
    borderTopLeftRadius: 26,
    borderTopRightRadius: 26,
    paddingHorizontal: 20,
    paddingTop: 10,
    maxHeight: '78%',
    borderTopWidth: 1,
    borderTopColor: 'rgba(255,255,255,0.12)',
  },
  transcriptScroll: {
    marginTop: 4,
  },
  transcriptBubble: {
    padding: 12,
    borderRadius: 14,
    marginBottom: 10,
  },
  transcriptAi: {
    backgroundColor: colors.card,
    borderWidth: 1,
    borderColor: 'rgba(255,255,255,0.07)',
  },
  transcriptUser: {
    backgroundColor: 'rgba(37, 99, 235, 0.2)',
    borderWidth: 1,
    borderColor: 'rgba(56, 189, 248, 0.3)',
  },
  transcriptSender: {
    fontSize: 10.5,
    fontWeight: '800',
    color: colors.accentCyan,
    marginBottom: 4,
  },
  transcriptText: {
    fontSize: 13.5,
    color: '#F8FAFC',
    lineHeight: 20,
  },
});
