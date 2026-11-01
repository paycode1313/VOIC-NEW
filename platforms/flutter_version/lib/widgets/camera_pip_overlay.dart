import 'package:flutter/material.dart';
import '../constants/colors.dart';

class CameraPipOverlay extends StatelessWidget {
  final double eyeContactRatio;
  final bool isSmiling;
  final bool faceDetected;
  final VoidCallback? onToggleExpand;

  const CameraPipOverlay({
    Key? key,
    required this.eyeContactRatio,
    required this.isSmiling,
    required this.faceDetected,
    this.onToggleExpand,
  }) : super(key: key);

  @override
  Widget build(BuildContext context) {
    return Container(
      width: 130,
      height: 175,
      decoration: BoxDecoration(
        color: VoicColors.surface,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(
          color: faceDetected ? VoicColors.primary.withOpacity(0.8) : VoicColors.cardBorder,
          width: 2.0,
        ),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withOpacity(0.5),
            blurRadius: 16,
            offset: const Offset(0, 6),
          ),
        ],
      ),
      clipBehavior: Clip.antiAlias,
      child: Stack(
        children: [
          // Camera Placeholder/Feed Background
          Container(
            color: const Color(0xFF0F172A),
            child: Center(
              child: Icon(
                faceDetected ? Icons.face_rounded : Icons.person_off_rounded,
                color: faceDetected ? VoicColors.textSecondary : VoicColors.textMuted,
                size: 48,
              ),
            ),
          ),

          // Top Header: "Kamera Anda"
          Positioned(
            top: 6,
            left: 8,
            right: 8,
            child: Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                Row(
                  children: [
                    Container(
                      width: 6,
                      height: 6,
                      decoration: BoxDecoration(
                        shape: BoxShape.circle,
                        color: faceDetected ? VoicColors.success : VoicColors.danger,
                      ),
                    ),
                    const SizedBox(width: 4),
                    const Text(
                      'Kamera Anda',
                      style: TextStyle(
                        fontSize: 9,
                        color: Colors.white,
                        fontWeight: FontWeight.bold,
                      ),
                    ),
                  ],
                ),
                GestureDetector(
                  onTap: onToggleExpand,
                  child: const Icon(
                    Icons.open_in_full_rounded,
                    color: Colors.white70,
                    size: 12,
                  ),
                ),
              ],
            ),
          ),

          // Bottom Telemetry Overlay
          Positioned(
            bottom: 0,
            left: 0,
            right: 0,
            child: Container(
              padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 4),
              decoration: BoxDecoration(
                gradient: LinearGradient(
                  begin: Alignment.bottomCenter,
                  end: Alignment.topCenter,
                  colors: [
                    Colors.black.withOpacity(0.85),
                    Colors.transparent,
                  ],
                ),
              ),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                mainAxisSize: MainAxisSize.min,
                children: [
                  Row(
                    children: [
                      const Icon(Icons.remove_red_eye_rounded, size: 10, color: VoicColors.accentCyan),
                      const SizedBox(width: 3),
                      Text(
                        'Mata: ${(eyeContactRatio * 100).toInt()}%',
                        style: const TextStyle(fontSize: 10, color: Colors.white, fontWeight: FontWeight.bold),
                      ),
                    ],
                  ),
                  const SizedBox(height: 2),
                  Row(
                    children: [
                      Icon(
                        isSmiling ? Icons.sentiment_very_satisfied_rounded : Icons.sentiment_neutral_rounded,
                        size: 10,
                        color: isSmiling ? VoicColors.success : VoicColors.textMuted,
                      ),
                      const SizedBox(width: 3),
                      Text(
                        isSmiling ? 'Senyum' : 'Rileks',
                        style: TextStyle(
                          fontSize: 9,
                          color: isSmiling ? VoicColors.success : VoicColors.textSecondary,
                        ),
                      ),
                    ],
                  ),
                ],
              ),
            ),
          ),
        ],
      ),
    );
  }
}
