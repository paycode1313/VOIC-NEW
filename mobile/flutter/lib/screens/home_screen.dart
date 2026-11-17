import 'package:flutter/material.dart';
import '../constants/colors.dart';
import '../models/ai_role.dart';
import '../services/api_service.dart';
import 'practice_arena_screen.dart';

class HomeScreen extends StatefulWidget {
  const HomeScreen({Key? key}) : super(key: key);

  @override
  State<HomeScreen> createState() => _HomeScreenState();
}

class _HomeScreenState extends State<HomeScreen> {
  bool _isLoading = true;
  List<Map<String, dynamic>> _modes = [];
  Map<String, dynamic>? _selectedMode;
  String? _selectedTopic;
  final TextEditingController _customTopicController = TextEditingController();

  @override
  void initState() {
    super.initState();
    _loadData();
  }

  @override
  void dispose() {
    _customTopicController.dispose();
    super.dispose();
  }

  Future<void> _loadData() async {
    try {
      final modes = await ApiService.fetchExamModes();
      setState(() {
        _modes = modes;
        if (_modes.isNotEmpty) {
          _selectedMode = _modes.first;
          final topics = List<String>.from(_selectedMode!['topics'] ?? []);
          _selectedTopic = topics.isNotEmpty ? topics.first : null;
        }
        _isLoading = false;
      });
    } catch (e) {
      setState(() => _isLoading = false);
    }
  }

  IconData _iconForMode(String key) {
    switch (key) {
      case 'wawancara':
        return Icons.work_outline_rounded;
      case 'pitching':
        return Icons.trending_up_rounded;
      default:
        return Icons.menu_book_rounded;
    }
  }

  Future<void> _startSession() async {
    if (_selectedMode == null) return;

    final modeKey = _selectedMode!['key'] as String;
    final roleType = _selectedMode!['role_type'] as String;
    final examiner = Map<String, dynamic>.from(_selectedMode!['examiner'] ?? {});
    final topic = _customTopicController.text.trim().isNotEmpty
        ? _customTopicController.text.trim()
        : _selectedTopic;

    final roleModel = AiRole(
      id: 0,
      name: (examiner['name'] ?? 'VOIC AI').toString(),
      roleType: roleType,
      difficultyLevel: 'Standar',
      voiceId: 'id-ID-ArdiNeural',
      description: (_selectedMode!['tagline'] ?? '').toString(),
      avatar: (examiner['avatar_url'] ?? '').toString(),
    );

    final scenarioModel = topic != null
        ? PracticeScenario(
            id: topic,
            name: topic,
            icon: '🎯',
            badge: (_selectedMode!['title'] ?? '').toString(),
            badgeColor: 'indigo',
            roleType: roleType,
            description: (_selectedMode!['tagline'] ?? '').toString(),
            promptGuide: '',
            targetDuration: '120 detik',
          )
        : null;

    showDialog(
      context: context,
      barrierDismissible: false,
      builder: (_) => const Center(
        child: CircularProgressIndicator(color: VoicColors.primary),
      ),
    );

    try {
      final res = await ApiService.startExam(
        mode: modeKey,
        topic: topic,
      );

      if (!mounted) return;
      Navigator.pop(context);

      Navigator.push(
        context,
        MaterialPageRoute(
          builder: (_) => PracticeArenaScreen(
            sessionId: res['session_id'],
            role: roleModel,
            scenario: scenarioModel,
            initialGreeting: res['examiner_message']['text'] ?? '',
            initialAudioUrl: res['examiner_message']['audio_url'],
          ),
        ),
      );
    } catch (e) {
      if (!mounted) return;
      Navigator.pop(context);
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text('Gagal memulai sesi: $e')),
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    final topics = List<String>.from(_selectedMode?['topics'] ?? []);
    final topicLabel = (_selectedMode?['topic_label'] ?? 'Pilih Topik').toString();
    final topicPlaceholder =
        (_selectedMode?['topic_placeholder'] ?? 'Tulis topik spesifik...').toString();
    final selectedTitle = (_selectedMode?['title'] ?? 'Simulasi').toString();

    return Scaffold(
      backgroundColor: VoicColors.background,
      appBar: AppBar(
        backgroundColor: VoicColors.surface,
        elevation: 0,
        title: Row(
          children: [
            Container(
              width: 34,
              height: 34,
              decoration: BoxDecoration(
                borderRadius: BorderRadius.circular(10),
                gradient: const LinearGradient(colors: VoicColors.orbGradients),
              ),
              child: const Icon(Icons.graphic_eq_rounded, color: Colors.white, size: 19),
            ),
            const SizedBox(width: 10),
            const Text(
              'VOIC Workspace',
              style: TextStyle(fontWeight: FontWeight.w800, fontSize: 17, color: Colors.white),
            ),
            const Spacer(),
            Container(
              padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
              decoration: BoxDecoration(
                color: VoicColors.success.withOpacity(0.14),
                borderRadius: BorderRadius.circular(999),
                border: Border.all(color: VoicColors.success.withOpacity(0.35)),
              ),
              child: Row(
                children: const [
                  Icon(Icons.circle, size: 7, color: VoicColors.success),
                  SizedBox(width: 5),
                  Text(
                    'ONLINE',
                    style: TextStyle(fontSize: 10, fontWeight: FontWeight.w800, color: Colors.white),
                  ),
                ],
              ),
            ),
          ],
        ),
      ),
      body: _isLoading
          ? const Center(child: CircularProgressIndicator(color: VoicColors.primary))
          : SingleChildScrollView(
              padding: const EdgeInsets.all(18),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  // 1. Executive Hero Card
                  Container(
                    padding: const EdgeInsets.all(20),
                    decoration: BoxDecoration(
                      gradient: const LinearGradient(
                        colors: [Color(0xFF1E1B4B), Color(0xFF0F172A)],
                        begin: Alignment.topLeft,
                        end: Alignment.bottomRight,
                      ),
                      borderRadius: BorderRadius.circular(22),
                      border: Border.all(color: VoicColors.primary.withOpacity(0.35)),
                    ),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Container(
                          padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                          decoration: BoxDecoration(
                            color: VoicColors.primary.withOpacity(0.18),
                            borderRadius: BorderRadius.circular(999),
                          ),
                          child: const Text(
                            'AI VOICE & OPTICS COACH',
                            style: TextStyle(
                              fontSize: 10,
                              fontWeight: FontWeight.w800,
                              letterSpacing: 0.7,
                              color: VoicColors.accentCyan,
                            ),
                          ),
                        ),
                        const SizedBox(height: 12),
                        const Text(
                          'Mau latihan dengan penguji AI mana?',
                          style: TextStyle(
                            fontSize: 21,
                            fontWeight: FontWeight.w800,
                            color: Colors.white,
                          ),
                        ),
                        const SizedBox(height: 6),
                        const Text(
                          'Setiap penguji AI berperan sesuai situasi nyata (HRD, Dosen, atau Investor) dan menanggapi isi jawabanmu.',
                          style: TextStyle(fontSize: 12.5, color: VoicColors.textSecondary, height: 1.4),
                        ),
                      ],
                    ),
                  ),
                  const SizedBox(height: 24),

                  // 2. Langkah 1: Pilih Simulasi & Penguji AI
                  const Text(
                    'LANGKAH 1 · SIMULASI & PENGUJI AI',
                    style: TextStyle(
                      fontSize: 10.5,
                      fontWeight: FontWeight.w800,
                      letterSpacing: 0.9,
                      color: VoicColors.accentCyan,
                    ),
                  ),
                  const SizedBox(height: 4),
                  const Text(
                    'Pilih Mode Latihan',
                    style: TextStyle(fontSize: 16, fontWeight: FontWeight.w800, color: Colors.white),
                  ),
                  const SizedBox(height: 12),
                  ..._modes.map((mode) {
                    final key = (mode['key'] ?? '').toString();
                    final isSelected = _selectedMode?['key'] == key;
                    final examiner = Map<String, dynamic>.from(mode['examiner'] ?? {});
                    return GestureDetector(
                      onTap: () {
                        final nextTopics = List<String>.from(mode['topics'] ?? []);
                        setState(() {
                          _selectedMode = mode;
                          _selectedTopic = nextTopics.isNotEmpty ? nextTopics.first : null;
                          _customTopicController.clear();
                        });
                      },
                      child: Container(
                        margin: const EdgeInsets.only(bottom: 12),
                        padding: const EdgeInsets.all(15),
                        decoration: BoxDecoration(
                          color: isSelected ? VoicColors.primary.withOpacity(0.16) : VoicColors.card,
                          borderRadius: BorderRadius.circular(18),
                          border: Border.all(
                            color: isSelected ? VoicColors.primary : VoicColors.cardBorder,
                            width: isSelected ? 1.5 : 1,
                          ),
                        ),
                        child: Row(
                          children: [
                            Container(
                              width: 48,
                              height: 48,
                              decoration: BoxDecoration(
                                borderRadius: BorderRadius.circular(14),
                                gradient: const LinearGradient(colors: VoicColors.orbGradients),
                              ),
                              child: Icon(_iconForMode(key), color: Colors.white, size: 22),
                            ),
                            const SizedBox(width: 14),
                            Expanded(
                              child: Column(
                                crossAxisAlignment: CrossAxisAlignment.start,
                                children: [
                                  Text(
                                    (mode['title'] ?? '').toString(),
                                    style: const TextStyle(
                                      fontSize: 15,
                                      fontWeight: FontWeight.w800,
                                      color: Colors.white,
                                    ),
                                  ),
                                  const SizedBox(height: 2),
                                  Text(
                                    '${examiner['name']} · ${examiner['title']}',
                                    style: const TextStyle(
                                      fontSize: 11.5,
                                      fontWeight: FontWeight.w700,
                                      color: VoicColors.accentCyan,
                                    ),
                                  ),
                                  const SizedBox(height: 4),
                                  Text(
                                    (mode['tagline'] ?? '').toString(),
                                    style: const TextStyle(
                                      fontSize: 12,
                                      color: VoicColors.textSecondary,
                                      height: 1.35,
                                    ),
                                    maxLines: 2,
                                    overflow: TextOverflow.ellipsis,
                                  ),
                                ],
                              ),
                            ),
                            const SizedBox(width: 10),
                            Icon(
                              isSelected ? Icons.check_circle_rounded : Icons.radio_button_unchecked_rounded,
                              color: isSelected ? VoicColors.primary : VoicColors.textMuted,
                              size: 22,
                            ),
                          ],
                        ),
                      ),
                    );
                  }).toList(),

                  const SizedBox(height: 16),

                  // 3. Langkah 2: Topik Khusus sesuai mode yang dipilih
                  Text(
                    'LANGKAH 2 · TOPIK KHUSUS ${selectedTitle.toUpperCase()}',
                    style: const TextStyle(
                      fontSize: 10.5,
                      fontWeight: FontWeight.w800,
                      letterSpacing: 0.9,
                      color: VoicColors.accentCyan,
                    ),
                  ),
                  const SizedBox(height: 4),
                  Text(
                    topicLabel,
                    style: const TextStyle(fontSize: 16, fontWeight: FontWeight.w800, color: Colors.white),
                  ),
                  const SizedBox(height: 12),
                  Wrap(
                    spacing: 8,
                    runSpacing: 8,
                    children: topics.map((topic) {
                      final isPicked =
                          _customTopicController.text.trim().isEmpty && _selectedTopic == topic;
                      return ChoiceChip(
                        label: Text(topic),
                        selected: isPicked,
                        selectedColor: VoicColors.primary.withOpacity(0.28),
                        backgroundColor: VoicColors.card,
                        labelStyle: TextStyle(
                          fontSize: 12,
                          fontWeight: isPicked ? FontWeight.w700 : FontWeight.w500,
                          color: isPicked ? Colors.white : VoicColors.textSecondary,
                        ),
                        side: BorderSide(
                          color: isPicked ? VoicColors.primary : VoicColors.cardBorder,
                        ),
                        onSelected: (_) {
                          setState(() {
                            _customTopicController.clear();
                            _selectedTopic = topic;
                          });
                        },
                      );
                    }).toList(),
                  ),
                  const SizedBox(height: 14),
                  TextField(
                    controller: _customTopicController,
                    onChanged: (_) => setState(() {}),
                    style: const TextStyle(color: Colors.white, fontSize: 13.5),
                    decoration: InputDecoration(
                      hintText: topicPlaceholder,
                      hintStyle: const TextStyle(color: VoicColors.textMuted, fontSize: 13),
                      filled: true,
                      fillColor: VoicColors.surface,
                      border: OutlineInputBorder(
                        borderRadius: BorderRadius.circular(14),
                        borderSide: const BorderSide(color: VoicColors.cardBorder),
                      ),
                      enabledBorder: OutlineInputBorder(
                        borderRadius: BorderRadius.circular(14),
                        borderSide: const BorderSide(color: VoicColors.cardBorder),
                      ),
                    ),
                  ),
                  const SizedBox(height: 26),

                  // 4. Primary CTA
                  ElevatedButton(
                    onPressed: _startSession,
                    style: ElevatedButton.styleFrom(
                      backgroundColor: VoicColors.primary,
                      foregroundColor: Colors.white,
                      minimumSize: const Size.fromHeight(54),
                      shape: RoundedRectangleBorder(
                        borderRadius: BorderRadius.circular(16),
                      ),
                      elevation: 8,
                    ),
                    child: Row(
                      mainAxisAlignment: MainAxisAlignment.center,
                      children: [
                        const Icon(Icons.videocam_rounded, size: 20),
                        const SizedBox(width: 10),
                        Text(
                          'Mulai $selectedTitle',
                          style: const TextStyle(fontSize: 15.5, fontWeight: FontWeight.w800),
                        ),
                      ],
                    ),
                  ),
                ],
              ),
            ),
    );
  }
}
