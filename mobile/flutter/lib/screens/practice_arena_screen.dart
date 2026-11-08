import 'dart:async';
import 'package:flutter/material.dart';
import '../constants/colors.dart';
import '../models/ai_role.dart';
import '../models/practice_session.dart';
import '../services/api_service.dart';
import '../widgets/audio_orb.dart';
import '../widgets/camera_pip_overlay.dart';
import 'evaluation_report_screen.dart';

class PracticeArenaScreen extends StatefulWidget {
  final int sessionId;
  final AiRole role;
  final PracticeScenario? scenario;
  final String initialGreeting;
  final String? initialAudioUrl;

  const PracticeArenaScreen({
    Key? key,
    required this.sessionId,
    required this.role,
    this.scenario,
    required this.initialGreeting,
    this.initialAudioUrl,
  }) : super(key: key);

  @override
  State<PracticeArenaScreen> createState() => _PracticeArenaScreenState();
}

class _PracticeArenaScreenState extends State<PracticeArenaScreen> {
  // Session State
  int _secondsElapsed = 0;
  Timer? _sessionTimer;
  bool _isAiSpeaking = false;
  bool _isUserSpeaking = false;
  bool _isProcessingTurn = false;

  // Dialogue & Critique
  String _currentAiDialogue = '';
  DateTime _answerStartedAt = DateTime.now();
  String? _lastFacialCritique;

  // Live Facial Telemetry
  double _eyeContactRatio = 0.92;
  bool _isSmiling = true;
  bool _faceDetected = true;

  // Speech Transcript Controller
  final TextEditingController _speechInputController = TextEditingController();

  @override
  void initState() {
    super.initState();
    _currentAiDialogue = widget.initialGreeting;
    _isAiSpeaking = true;

    // Start 1-second interval timer
    _sessionTimer = Timer.periodic(const Duration(seconds: 1), (timer) {
      if (mounted) {
        setState(() {
          _secondsElapsed++;
        });
      }
    });

    // Simulate opening speech end after 3 seconds
    Future.delayed(const Duration(seconds: 3), () {
      if (mounted) setState(() => _isAiSpeaking = false);
    });
  }

  @override
  void dispose() {
    _sessionTimer?.cancel();
    _speechInputController.dispose();
    super.dispose();
  }

  String _formatTimer(int seconds) {
    final m = (seconds ~/ 60).toString().padLeft(2, '0');
    final s = (seconds % 60).toString().padLeft(2, '0');
    return '$m:$s';
  }

  // Handle User Sending Speech to Laravel AI Brain
  Future<void> _handleSendSpeech(String text) async {
    if (text.trim().isEmpty || _isProcessingTurn) return;

    setState(() {
      _isUserSpeaking = false;
      _isProcessingTurn = true;
    });

    try {
      final res = await ApiService.answerExam(
        sessionId: widget.sessionId,
        answer: text,
        durationSeconds: DateTime.now().difference(_answerStartedAt).inSeconds,
        timestampSeconds: _secondsElapsed,
      );

      if (!mounted) return;

      setState(() {
        _isProcessingTurn = false;
        _isAiSpeaking = true;
        _currentAiDialogue = (res['examiner_message']?['text'] as String?) ?? '';
        _lastFacialCritique = null;
      });

      // Reset AI speaking indicator after short duration, then the next answer starts.
      Future.delayed(const Duration(seconds: 4), () {
        if (mounted) {
          setState(() => _isAiSpeaking = false);
          _answerStartedAt = DateTime.now();
        }
      });

      if (res['completed'] == true) {
        // The examiner just said the closing line: build the report.
        await _finishAndShowReport();
      }
    } catch (e) {
      if (mounted) {
        setState(() => _isProcessingTurn = false);
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text('Koneksi terganggu: $e')),
        );
      }
    }
  }

  // Finish Practice and Navigate to Final Report
  Future<void> _handleFinishSession() async {
    final confirmed = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        backgroundColor: VoicColors.surface,
        title: const Text('Selesaikan Latihan?', style: TextStyle(color: Colors.white)),
        content: const Text(
          'Rapor dibuat dari jawaban yang sudah kamu berikan sejauh ini.',
          style: TextStyle(color: VoicColors.textSecondary),
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(ctx, false),
            child: const Text('Lanjut Bicara', style: TextStyle(color: VoicColors.textMuted)),
          ),
          ElevatedButton(
            style: ElevatedButton.styleFrom(backgroundColor: VoicColors.primary),
            onPressed: () => Navigator.pop(ctx, true),
            child: const Text('Selesaikan & Nilai', style: TextStyle(color: Colors.white)),
          ),
        ],
      ),
    );

    if (confirmed != true) return;
    await _finishAndShowReport();
  }

  /// Ask the server to build the band-score report from the real answers, then show it.
  Future<void> _finishAndShowReport() async {
    showDialog(
      context: context,
      barrierDismissible: false,
      builder: (_) => const Center(
        child: CircularProgressIndicator(color: VoicColors.primary),
      ),
    );

    try {
      final report = await ApiService.finishExam(
        sessionId: widget.sessionId,
        durationSeconds: _secondsElapsed,
      );

      if (!mounted) return;
      Navigator.pop(context); // close dialog

      Navigator.pushReplacement(
        context,
        MaterialPageRoute(
          builder: (_) => EvaluationReportScreen(report: report, role: widget.role),
        ),
      );
    } catch (e) {
      if (!mounted) return;
      Navigator.pop(context);
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text('Gagal menyelesaikan sesi: $e')),
      );
    }
  }

  // Quick Speech Simulation Dialog (Untuk Kemudahan Testing Demo)
  void _showSpeechInputDialog() {
    _speechInputController.clear();
    showModalBottomSheet(
      context: context,
      backgroundColor: VoicColors.surface,
      isScrollControlled: true,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(20)),
      ),
      builder: (ctx) => Padding(
        padding: EdgeInsets.only(
          bottom: MediaQuery.of(ctx).viewInsets.bottom + 16,
          left: 16,
          right: 16,
          top: 20,
        ),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            const Text(
              'Ucapkan Jawaban Anda 🎙️',
              style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold, color: Colors.white),
            ),
            const SizedBox(height: 8),
            const Text(
              'Ketik atau gunakan suara untuk menjawab pertanyaan penguji:',
              style: TextStyle(fontSize: 12, color: VoicColors.textSecondary),
            ),
            const SizedBox(height: 12),
            TextField(
              controller: _speechInputController,
              autofocus: true,
              maxLines: 3,
              style: const TextStyle(color: Colors.white),
              decoration: InputDecoration(
                hintText: 'Contoh: Menurut saya, kebaruan penelitian ini terletak pada integrasi AI lokal...',
                hintStyle: const TextStyle(color: VoicColors.textMuted, fontSize: 13),
                filled: true,
                fillColor: VoicColors.card,
                border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
              ),
            ),
            const SizedBox(height: 14),
            ElevatedButton(
              style: ElevatedButton.styleFrom(
                backgroundColor: VoicColors.primary,
                padding: const EdgeInsets.symmetric(vertical: 14),
                shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
              ),
              onPressed: () {
                final txt = _speechInputController.text;
                Navigator.pop(ctx);
                _handleSendSpeech(txt);
              },
              child: const Text('Kirim Jawaban ke AI', style: TextStyle(fontWeight: FontWeight.bold, color: Colors.white)),
            ),
          ],
        ),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: VoicColors.background,
      body: SafeArea(
        child: Stack(
          children: [
            // Main Vertical Content
            Column(
              children: [
                // Top Header (Role, Skenario, & Timer)
                Container(
                  padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
                  decoration: const BoxDecoration(
                    color: VoicColors.surface,
                    border: Border(bottom: BorderSide(color: VoicColors.cardBorder, width: 0.5)),
                  ),
                  child: Row(
                    children: [
                      GestureDetector(
                        onTap: () => Navigator.pop(context),
                        child: const Icon(Icons.arrow_back_ios_new_rounded, color: Colors.white70, size: 18),
                      ),
                      const SizedBox(width: 12),
                      Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(
                              widget.role.name,
                              style: const TextStyle(
                                fontSize: 14,
                                fontWeight: FontWeight.bold,
                                color: Colors.white,
                              ),
                            ),
                            Text(
                              widget.scenario?.name ?? 'Sesi Interaktif',
                              style: const TextStyle(fontSize: 11, color: VoicColors.textSecondary),
                            ),
                          ],
                        ),
                      ),
                      // Live Session Timer Badge
                      Container(
                        padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 5),
                        decoration: BoxDecoration(
                          color: const Color(0xFF1E293B),
                          borderRadius: BorderRadius.circular(20),
                          border: Border.all(color: VoicColors.danger.withOpacity(0.5)),
                        ),
                        child: Row(
                          children: [
                            Container(
                              width: 8,
                              height: 8,
                              decoration: const BoxDecoration(
                                shape: BoxShape.circle,
                                color: VoicColors.danger,
                              ),
                            ),
                            const SizedBox(width: 6),
                            Text(
                              _formatTimer(_secondsElapsed),
                              style: const TextStyle(
                                fontSize: 12,
                                fontWeight: FontWeight.bold,
                                color: Colors.white,
                              ),
                            ),
                          ],
                        ),
                      ),
                    ],
                  ),
                ),

                // Center Stage: Audio Orb & AI Dialogue Bubble
                Expanded(
                  child: SingleChildScrollView(
                    padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 12),
                    child: Column(
                      mainAxisAlignment: MainAxisAlignment.center,
                      children: [
                        const SizedBox(height: 10),

                        // Animated Audio Orb
                        AudioOrbWidget(
                          isSpeaking: _isAiSpeaking,
                          isListening: _isUserSpeaking || _isProcessingTurn,
                          size: 150,
                        ),
                        const SizedBox(height: 18),

                        // Status AI State
                        Text(
                          _isAiSpeaking
                              ? 'AI sedang berbicara...'
                              : (_isProcessingTurn
                                  ? 'AI sedang berpikir & menganalisis...'
                                  : 'Mendengarkan ucapan Anda...'),
                          style: TextStyle(
                            fontSize: 13,
                            fontWeight: FontWeight.w600,
                            color: _isAiSpeaking ? VoicColors.primary : VoicColors.textSecondary,
                          ),
                        ),
                        const SizedBox(height: 16),

                        // Dialogue Transcript Card
                        Container(
                          width: double.infinity,
                          padding: const EdgeInsets.all(16),
                          decoration: BoxDecoration(
                            color: VoicColors.surface,
                            borderRadius: BorderRadius.circular(16),
                            border: Border.all(color: VoicColors.cardBorder),
                            boxShadow: [
                              BoxShadow(
                                color: Colors.black.withOpacity(0.3),
                                blurRadius: 10,
                                offset: const Offset(0, 4),
                              ),
                            ],
                          ),
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Row(
                                children: [
                                  const Icon(Icons.format_quote_rounded, color: VoicColors.primary, size: 16),
                                  const SizedBox(width: 6),
                                  Text(
                                    widget.role.name,
                                    style: const TextStyle(
                                      fontSize: 11,
                                      fontWeight: FontWeight.bold,
                                      color: VoicColors.primary,
                                    ),
                                  ),
                                ],
                              ),
                              const SizedBox(height: 6),
                              Text(
                                _currentAiDialogue,
                                style: const TextStyle(
                                  fontSize: 14,
                                  color: Colors.white,
                                  height: 1.5,
                                ),
                              ),
                            ],
                          ),
                        ),

                        // Live Critique Toast (Jika AI memberikan koreksi postur/wajah)
                        if (_lastFacialCritique != null) ...[
                          const SizedBox(height: 10),
                          Container(
                            padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
                            decoration: BoxDecoration(
                              color: VoicColors.warning.withOpacity(0.15),
                              borderRadius: BorderRadius.circular(10),
                              border: Border.all(color: VoicColors.warning.withOpacity(0.4)),
                            ),
                            child: Row(
                              children: [
                                const Icon(Icons.remove_red_eye_outlined, color: VoicColors.warning, size: 16),
                                const SizedBox(width: 8),
                                Expanded(
                                  child: Text(
                                    _lastFacialCritique!,
                                    style: const TextStyle(fontSize: 11, color: Color(0xFFFDE68A)),
                                  ),
                                ),
                              ],
                            ),
                          ),
                        ],

                        // Spacer to ensure content doesn't get covered by thumb zone
                        const SizedBox(height: 120),
                      ],
                    ),
                  ),
                ),

                // Bottom Thumb Zone (Ergonomic Phone Controls)
                Container(
                  padding: const EdgeInsets.fromLTRB(20, 14, 20, 20),
                  decoration: const BoxDecoration(
                    color: VoicColors.surface,
                    borderRadius: BorderRadius.vertical(top: Radius.circular(24)),
                    border: Border(top: BorderSide(color: VoicColors.cardBorder, width: 0.5)),
                  ),
                  child: Column(
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      // Large Circular Mic Button
                      GestureDetector(
                        onTap: _showSpeechInputDialog,
                        child: Container(
                          width: 68,
                          height: 68,
                          decoration: BoxDecoration(
                            shape: BoxShape.circle,
                            gradient: const LinearGradient(
                              colors: VoicColors.orbGradients,
                              begin: Alignment.topLeft,
                              end: Alignment.bottomRight,
                            ),
                            boxShadow: [
                              BoxShadow(
                                color: VoicColors.primary.withOpacity(0.6),
                                blurRadius: 20,
                                spreadRadius: 2,
                              ),
                            ],
                          ),
                          child: const Center(
                            child: Icon(Icons.mic_rounded, color: Colors.white, size: 34),
                          ),
                        ),
                      ),
                      const SizedBox(height: 8),
                      const Text(
                        'Tap untuk Berbicara 🎙️',
                        style: TextStyle(
                          fontSize: 12,
                          fontWeight: FontWeight.w600,
                          color: VoicColors.textSecondary,
                        ),
                      ),
                      const SizedBox(height: 14),

                      // Selesaikan Latihan Button
                      OutlinedButton(
                        onPressed: _handleFinishSession,
                        style: OutlinedButton.styleFrom(
                          side: const BorderSide(color: VoicColors.cardBorder),
                          minimumSize: const Size.fromHeight(46),
                          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                        ),
                        child: const Text(
                          'Selesaikan Sesi Latihan',
                          style: TextStyle(
                            fontSize: 13,
                            fontWeight: FontWeight.bold,
                            color: Colors.white,
                          ),
                        ),
                      ),
                    ],
                  ),
                ),
              ],
            ),

            // Floating Picture-in-Picture (PiP) Camera Preview
            Positioned(
              right: 16,
              bottom: 185,
              child: CameraPipOverlay(
                eyeContactRatio: _eyeContactRatio,
                isSmiling: _isSmiling,
                faceDetected: _faceDetected,
                onToggleExpand: () {
                  setState(() {
                    _isSmiling = !_isSmiling;
                    _eyeContactRatio = _isSmiling ? 0.95 : 0.65;
                  });
                },
              ),
            ),
          ],
        ),
      ),
    );
  }
}
