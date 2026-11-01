import 'package:flutter/material.dart';
import 'package:qr_flutter/qr_flutter.dart';
import '../constants/colors.dart';
import '../models/ai_role.dart';
import '../models/practice_session.dart';

class EvaluationReportScreen extends StatelessWidget {
  final SessionEvaluationReport report;
  final AiRole role;

  const EvaluationReportScreen({
    Key? key,
    required this.report,
    required this.role,
  }) : super(key: key);

  String _formatDuration(int seconds) {
    final m = seconds ~/ 60;
    final s = seconds % 60;
    return '${m > 0 ? '$m menit ' : ''}$s detik';
  }

  Color _getScoreColor(double score) {
    if (score >= 80) return VoicColors.success;
    if (score >= 65) return VoicColors.warning;
    return VoicColors.danger;
  }

  @override
  Widget build(BuildContext context) {
    final strengths = (report.feedbackNotes['strengths'] as List<dynamic>?)
            ?.map((e) => e.toString())
            .toList() ??
        [];
    final improvements = (report.feedbackNotes['improvements'] as List<dynamic>?)
            ?.map((e) => e.toString())
            .toList() ??
        [];

    return Scaffold(
      backgroundColor: VoicColors.background,
      appBar: AppBar(
        backgroundColor: VoicColors.surface,
        elevation: 0,
        title: const Text(
          'Rapor Evaluasi Latihan',
          style: TextStyle(fontWeight: FontWeight.bold, fontSize: 16, color: Colors.white),
        ),
        actions: [
          IconButton(
            icon: const Icon(Icons.close_rounded, color: Colors.white70),
            onPressed: () => Navigator.pop(context),
          ),
        ],
      ),
      body: SingleChildScrollView(
        padding: const EdgeInsets.all(20),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            // Overall Score Card
            Container(
              padding: const EdgeInsets.all(24),
              decoration: BoxDecoration(
                gradient: const LinearGradient(
                  colors: [Color(0xFF1E1B4B), Color(0xFF0F172A)],
                  begin: Alignment.topLeft,
                  end: Alignment.bottomRight,
                ),
                borderRadius: BorderRadius.circular(20),
                border: Border.all(color: VoicColors.primary.withOpacity(0.4)),
                boxShadow: [
                  BoxShadow(
                    color: VoicColors.primary.withOpacity(0.2),
                    blurRadius: 20,
                  ),
                ],
              ),
              child: Column(
                children: [
                  const Text(
                    'SKOR KESELURUHAN',
                    style: TextStyle(
                      fontSize: 12,
                      fontWeight: FontWeight.bold,
                      letterSpacing: 1.5,
                      color: VoicColors.textSecondary,
                    ),
                  ),
                  const SizedBox(height: 12),
                  Text(
                    report.overallScore.toStringAsFixed(1),
                    style: TextStyle(
                      fontSize: 56,
                      fontWeight: FontWeight.w900,
                      color: _getScoreColor(report.overallScore),
                    ),
                  ),
                  Text(
                    report.overallScore >= 80 ? 'Luar Biasa! Kompeten 🏆' : 'Cukup Baik & Berprospek',
                    style: const TextStyle(fontSize: 14, fontWeight: FontWeight.bold, color: Colors.white),
                  ),
                  const SizedBox(height: 16),
                  Row(
                    mainAxisAlignment: MainAxisAlignment.center,
                    children: [
                      _buildMiniScore('Skor Wajah', report.faceScore, Icons.face_rounded),
                      const SizedBox(width: 24),
                      _buildMiniScore('Skor Suara', report.voiceScore, Icons.graphic_eq_rounded),
                    ],
                  ),
                ],
              ),
            ),
            const SizedBox(height: 20),

            // Metadata Chips
            Row(
              children: [
                Expanded(
                  child: _buildInfoTile(
                    'Durasi Sesi',
                    _formatDuration(report.durationSeconds),
                    Icons.timer_outlined,
                  ),
                ),
                const SizedBox(width: 12),
                Expanded(
                  child: _buildInfoTile(
                    'Karakter Penguji',
                    role.name,
                    Icons.person_pin_rounded,
                  ),
                ),
              ],
            ),
            const SizedBox(height: 20),

            // AI Conclusion Summary
            Container(
              padding: const EdgeInsets.all(18),
              decoration: BoxDecoration(
                color: VoicColors.surface,
                borderRadius: BorderRadius.circular(16),
                border: Border.all(color: VoicColors.cardBorder),
              ),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Row(
                    children: const [
                      Icon(Icons.auto_awesome_rounded, color: VoicColors.primary, size: 18),
                      SizedBox(width: 8),
                      Text(
                        'Kesimpulan AI Coach',
                        style: TextStyle(fontSize: 14, fontWeight: FontWeight.bold, color: Colors.white),
                      ),
                    ],
                  ),
                  const SizedBox(height: 10),
                  Text(
                    report.aiConclusion.isNotEmpty
                        ? report.aiConclusion
                        : 'Latihan telah diselesaikan dengan performa komunikasi yang terstruktur dan kontak mata yang terjaga.',
                    style: const TextStyle(fontSize: 13, color: VoicColors.textSecondary, height: 1.5),
                  ),
                ],
              ),
            ),
            const SizedBox(height: 20),

            // Strengths
            if (strengths.isNotEmpty) ...[
              const Text('Kekuatan Komunikasi Anda 💪', style: TextStyle(fontSize: 14, fontWeight: FontWeight.bold, color: Colors.white)),
              const SizedBox(height: 10),
              ...strengths.map((str) => _buildBulletPoint(str, VoicColors.success, Icons.check_circle_outline_rounded)),
              const SizedBox(height: 16),
            ],

            // Improvements
            if (improvements.isNotEmpty) ...[
              const Text('Saran Perbaikan Berikutnya 🎯', style: TextStyle(fontSize: 14, fontWeight: FontWeight.bold, color: Colors.white)),
              const SizedBox(height: 10),
              ...improvements.map((imp) => _buildBulletPoint(imp, VoicColors.warning, Icons.lightbulb_outline_rounded)),
              const SizedBox(height: 20),
            ],

            // Digital Certificate QR Code Box
            Container(
              padding: const EdgeInsets.all(20),
              decoration: BoxDecoration(
                color: VoicColors.surface,
                borderRadius: BorderRadius.circular(16),
                border: Border.all(color: VoicColors.primary.withOpacity(0.3)),
              ),
              child: Column(
                children: [
                  const Text(
                    'Sertifikat Digital Terverifikasi',
                    style: TextStyle(fontSize: 14, fontWeight: FontWeight.bold, color: Colors.white),
                  ),
                  const SizedBox(height: 4),
                  const Text(
                    'Pindai QR code ini untuk melihat kredensial digital publik latihan Anda.',
                    textAlign: TextAlign.center,
                    style: TextStyle(fontSize: 11, color: VoicColors.textSecondary),
                  ),
                  const SizedBox(height: 14),
                  Container(
                    padding: const EdgeInsets.all(10),
                    decoration: BoxDecoration(
                      color: Colors.white,
                      borderRadius: BorderRadius.circular(12),
                    ),
                    child: QrImageView(
                      data: report.verificationUrl ?? 'https://voic.app/verify/${report.sessionId}',
                      version: QrVersions.auto,
                      size: 130.0,
                    ),
                  ),
                ],
              ),
            ),
            const SizedBox(height: 24),

            // Back to Dashboard
            ElevatedButton(
              style: ElevatedButton.styleFrom(
                backgroundColor: VoicColors.primary,
                padding: const EdgeInsets.symmetric(vertical: 16),
                shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
              ),
              onPressed: () => Navigator.pop(context),
              child: const Text('Kembali ke Menu Utama', style: TextStyle(fontWeight: FontWeight.bold, color: Colors.white)),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildMiniScore(String label, double score, IconData icon) {
    return Row(
      children: [
        Icon(icon, size: 16, color: VoicColors.accentCyan),
        const SizedBox(width: 6),
        Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(label, style: const TextStyle(fontSize: 10, color: VoicColors.textSecondary)),
            Text(
              '${score.toInt()}/100',
              style: const TextStyle(fontSize: 13, fontWeight: FontWeight.bold, color: Colors.white),
            ),
          ],
        ),
      ],
    );
  }

  Widget _buildInfoTile(String title, String value, IconData icon) {
    return Container(
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        color: VoicColors.surface,
        borderRadius: BorderRadius.circular(12),
        border: Border.all(color: VoicColors.cardBorder),
      ),
      child: Row(
        children: [
          Icon(icon, size: 20, color: VoicColors.primary),
          const SizedBox(width: 8),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(title, style: const TextStyle(fontSize: 10, color: VoicColors.textSecondary)),
                Text(
                  value,
                  style: const TextStyle(fontSize: 12, fontWeight: FontWeight.bold, color: Colors.white),
                  overflow: TextOverflow.ellipsis,
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildBulletPoint(String text, Color color, IconData icon) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 8),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Icon(icon, size: 16, color: color),
          const SizedBox(width: 8),
          Expanded(
            child: Text(
              text,
              style: const TextStyle(fontSize: 12, color: VoicColors.textSecondary, height: 1.4),
            ),
          ),
        ],
      ),
    );
  }
}
