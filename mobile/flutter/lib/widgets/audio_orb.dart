import 'package:flutter/material.dart';
import '../constants/colors.dart';

class AudioOrbWidget extends StatefulWidget {
  final bool isSpeaking;
  final bool isListening;
  final double size;

  const AudioOrbWidget({
    Key? key,
    this.isSpeaking = false,
    this.isListening = false,
    this.size = 180.0,
  }) : super(key: key);

  @override
  State<AudioOrbWidget> createState() => _AudioOrbWidgetState();
}

class _AudioOrbWidgetState extends State<AudioOrbWidget>
    with SingleTickerProviderStateMixin {
  late AnimationController _controller;
  late Animation<double> _scaleAnimation;

  @override
  void initState() {
    super.initState();
    _controller = AnimationController(
      vsync: this,
      duration: const Duration(milliseconds: 1400),
    )..repeat(reverse: true);

    _scaleAnimation = Tween<double>(begin: 0.92, end: 1.15).animate(
      CurvedAnimation(parent: _controller, curve: Curves.easeInOut),
    );
  }

  @override
  void dispose() {
    _controller.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final activeColor = widget.isSpeaking
        ? VoicColors.primary
        : (widget.isListening ? VoicColors.success : VoicColors.cardBorder);

    return AnimatedBuilder(
      animation: _controller,
      builder: (context, child) {
        final scale = (widget.isSpeaking || widget.isListening)
            ? _scaleAnimation.value
            : 1.0;

        return Stack(
          alignment: Alignment.center,
          children: [
            // Outer Pulsing Glow
            Container(
              width: widget.size * scale * 1.3,
              height: widget.size * scale * 1.3,
              decoration: BoxDecoration(
                shape: BoxShape.circle,
                gradient: RadialGradient(
                  colors: [
                    activeColor.withOpacity(0.25),
                    Colors.transparent,
                  ],
                ),
              ),
            ),
            // Middle Wave Ring
            Container(
              width: widget.size * scale * 1.1,
              height: widget.size * scale * 1.1,
              decoration: BoxDecoration(
                shape: BoxShape.circle,
                border: Border.all(
                  color: activeColor.withOpacity(0.35),
                  width: 2.0,
                ),
              ),
            ),
            // Core Audio Orb
            Container(
              width: widget.size * scale,
              height: widget.size * scale,
              decoration: BoxDecoration(
                shape: BoxShape.circle,
                gradient: const LinearGradient(
                  begin: Alignment.topLeft,
                  end: Alignment.bottomRight,
                  colors: VoicColors.orbGradients,
                ),
                boxShadow: [
                  BoxShadow(
                    color: activeColor.withOpacity(0.5),
                    blurRadius: 30,
                    spreadRadius: 4,
                  ),
                ],
              ),
              child: Center(
                child: Icon(
                  widget.isSpeaking
                      ? Icons.volume_up_rounded
                      : (widget.isListening ? Icons.mic_rounded : Icons.bubble_chart_rounded),
                  color: Colors.white,
                  size: widget.size * 0.35,
                ),
              ),
            ),
          ],
        );
      },
    );
  }
}
