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
  List<AiRole> _roles = [];
  List<PracticeScenario> _scenarios = [];
  AiRole? _selectedRole;
  PracticeScenario? _selectedScenario;

  @override
  void initState() {
    super.initState();
    _loadData();
  }

  Future<void> _loadData() async {
    try {
      final res = await ApiService.fetchRolesAndScenarios();
      setState(() {
        _roles = res['roles'] as List<AiRole>;
        _scenarios = res['scenarios'] as List<PracticeScenario>;
        if (_roles.isNotEmpty) _selectedRole = _roles.first;
        if (_scenarios.isNotEmpty) _selectedScenario = _scenarios.first;
        _isLoading = false;
      });
    } catch (e) {
      setState(() => _isLoading = false);
    }
  }

  Future<void> _startSession() async {
    if (_selectedRole == null) return;

    // Show loading dialog
    showDialog(
      context: context,
      barrierDismissible: false,
      builder: (_) => const Center(
        child: CircularProgressIndicator(color: VoicColors.primary),
      ),
    );

    try {
      final res = await ApiService.startExam(
        mode: ApiService.modeForRole(_selectedRole!.roleType),
        topic: _selectedScenario?.name,
      );

      if (!mounted) return;
      Navigator.pop(context); // close dialog

      Navigator.push(
        context,
        MaterialPageRoute(
          builder: (_) => PracticeArenaScreen(
            sessionId: res['session_id'],
            role: _selectedRole!,
            scenario: _selectedScenario,
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
    return Scaffold(
      backgroundColor: VoicColors.background,
      appBar: AppBar(
        backgroundColor: VoicColors.surface,
        elevation: 0,
        title: Row(
          children: [
            Container(
              width: 32,
              height: 32,
              decoration: const BoxDecoration(
                shape: BoxShape.circle,
                gradient: LinearGradient(colors: VoicColors.orbGradients),
              ),
              child: const Icon(Icons.graphic_eq_rounded, color: Colors.white, size: 18),
            ),
            const SizedBox(width: 10),
            const Text(
              'VOIC Mobile',
              style: TextStyle(fontWeight: FontWeight.bold, fontSize: 18, color: Colors.white),
            ),
          ],
        ),
      ),
      body: _isLoading
          ? const Center(child: CircularProgressIndicator(color: VoicColors.primary))
          : SingleChildScrollView(
              padding: const EdgeInsets.all(16),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  // Banner Sambutan
                  Container(
                    padding: const EdgeInsets.all(18),
                    decoration: BoxDecoration(
                      gradient: const LinearGradient(
                        colors: [Color(0xFF1E1B4B), Color(0xFF131B2E)],
                        begin: Alignment.topLeft,
                        end: Alignment.bottomRight,
                      ),
                      borderRadius: BorderRadius.circular(16),
                      border: Border.all(color: VoicColors.primary.withOpacity(0.3)),
                    ),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: const [
                        Text(
                          'Siap Latihan Berbicara Hari Ini? 🚀',
                          style: TextStyle(
                            fontSize: 16,
                            fontWeight: FontWeight.bold,
                            color: Colors.white,
                          ),
                        ),
                        SizedBox(height: 6),
                        Text(
                          'Pilih karakter AI & skenario latihan untuk mengasah kemampuan komunikasi, kontak mata, dan ekspresi wajah Anda.',
                          style: TextStyle(fontSize: 12, color: VoicColors.textSecondary, height: 1.4),
                        ),
                      ],
                    ),
                  ),
                  const SizedBox(height: 24),

                  // Skenario Pilihan
                  const Text(
                    'Pilih Skenario Latihan',
                    style: TextStyle(fontSize: 15, fontWeight: FontWeight.bold, color: Colors.white),
                  ),
                  const SizedBox(height: 12),
                  SizedBox(
                    height: 110,
                    child: ListView.separated(
                      scrollDirection: Axis.horizontal,
                      itemCount: _scenarios.length,
                      separatorBuilder: (_, __) => const SizedBox(width: 12),
                      itemBuilder: (context, index) {
                        final s = _scenarios[index];
                        final isSelected = _selectedScenario?.id == s.id;
                        return GestureDetector(
                          onTap: () => setState(() => _selectedScenario = s),
                          child: Container(
                            width: 130,
                            padding: const EdgeInsets.all(12),
                            decoration: BoxDecoration(
                              color: isSelected ? VoicColors.primary.withOpacity(0.2) : VoicColors.card,
                              borderRadius: BorderRadius.circular(14),
                              border: Border.all(
                                color: isSelected ? VoicColors.primary : VoicColors.cardBorder,
                                width: isSelected ? 2 : 1,
                              ),
                            ),
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                Text(s.icon, style: const TextStyle(fontSize: 22)),
                                const Spacer(),
                                Text(
                                  s.name,
                                  style: const TextStyle(
                                    fontSize: 12,
                                    fontWeight: FontWeight.bold,
                                    color: Colors.white,
                                  ),
                                  maxLines: 1,
                                  overflow: TextOverflow.ellipsis,
                                ),
                                const SizedBox(height: 2),
                                Text(
                                  s.badge,
                                  style: const TextStyle(fontSize: 10, color: VoicColors.textSecondary),
                                ),
                              ],
                            ),
                          ),
                        );
                      },
                    ),
                  ),
                  const SizedBox(height: 24),

                  // Karakter AI
                  const Text(
                    'Pilih Karakter AI Penguji',
                    style: TextStyle(fontSize: 15, fontWeight: FontWeight.bold, color: Colors.white),
                  ),
                  const SizedBox(height: 12),
                  ..._roles.map((role) {
                    final isSelected = _selectedRole?.id == role.id;
                    return GestureDetector(
                      onTap: () => setState(() => _selectedRole = role),
                      child: Container(
                        margin: const EdgeInsets.only(bottom: 12),
                        padding: const EdgeInsets.all(14),
                        decoration: BoxDecoration(
                          color: isSelected ? VoicColors.primary.withOpacity(0.15) : VoicColors.card,
                          borderRadius: BorderRadius.circular(14),
                          border: Border.all(
                            color: isSelected ? VoicColors.primary : VoicColors.cardBorder,
                            width: isSelected ? 2 : 1,
                          ),
                        ),
                        child: Row(
                          children: [
                            Container(
                              width: 48,
                              height: 48,
                              decoration: BoxDecoration(
                                shape: BoxShape.circle,
                                color: VoicColors.surface,
                                border: Border.all(color: VoicColors.primary.withOpacity(0.5)),
                              ),
                              child: Center(
                                child: Text(
                                  role.roleType == 'dosen_penguji'
                                      ? '🎓'
                                      : (role.roleType == 'hrd' ? '💼' : '🚀'),
                                  style: const TextStyle(fontSize: 24),
                                ),
                              ),
                            ),
                            const SizedBox(width: 14),
                            Expanded(
                              child: Column(
                                crossAxisAlignment: CrossAxisAlignment.start,
                                children: [
                                  Row(
                                    children: [
                                      Text(
                                        role.name,
                                        style: const TextStyle(
                                          fontSize: 14,
                                          fontWeight: FontWeight.bold,
                                          color: Colors.white,
                                        ),
                                      ),
                                      const Spacer(),
                                      Container(
                                        padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 2),
                                        decoration: BoxDecoration(
                                          color: VoicColors.surface,
                                          borderRadius: BorderRadius.circular(6),
                                        ),
                                        child: Text(
                                          role.difficultyLevel,
                                          style: const TextStyle(fontSize: 10, color: VoicColors.warning),
                                        ),
                                      ),
                                    ],
                                  ),
                                  const SizedBox(height: 4),
                                  Text(
                                    role.description,
                                    style: const TextStyle(fontSize: 11, color: VoicColors.textSecondary),
                                    maxLines: 2,
                                    overflow: TextOverflow.ellipsis,
                                  ),
                                ],
                              ),
                            ),
                          ],
                        ),
                      ),
                    );
                  }).toList(),

                  const SizedBox(height: 24),

                  // Tombol Mulai Sesi Latihan
                  ElevatedButton(
                    onPressed: _startSession,
                    style: ElevatedButton.styleFrom(
                      backgroundColor: VoicColors.primary,
                      minimumSize: const Size.fromHeight(54),
                      shape: RoundedRectangleBorder(
                        borderRadius: BorderRadius.circular(14),
                      ),
                      elevation: 6,
                    ),
                    child: const Text(
                      'Mulai Latihan Sekarang 🎙️',
                      style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold, color: Colors.white),
                    ),
                  ),
                ],
              ),
            ),
    );
  }
}
