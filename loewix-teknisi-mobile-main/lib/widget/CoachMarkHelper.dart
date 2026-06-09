import 'package:flutter/material.dart';
import 'package:tutorial_coach_mark/tutorial_coach_mark.dart';

/// Speech-bubble style coach mark — like GoJek/Tokopedia tooltip.
class CoachMarkHelper {
  /// Simple speech bubble tooltip with close (X) button.
  static Widget buildTooltip({
    required String title,
    required List<String> descriptions,
    String? step,
    String? note,
    String? icon, // backward compat
    bool arrowUp = false,
    VoidCallback? onClose,
  }) {
    // Combine descriptions into one paragraph
    final text = descriptions.where((d) => d.isNotEmpty).join('. ');
    final fullText = note != null ? '$text. $note' : text;

    return Column(
      mainAxisSize: MainAxisSize.min,
      crossAxisAlignment: arrowUp ? CrossAxisAlignment.start : CrossAxisAlignment.start,
      children: [
        // Arrow pointing up (when tooltip is below target)
        if (arrowUp)
          Padding(
            padding: const EdgeInsets.only(left: 32),
            child: CustomPaint(
              size: const Size(16, 8),
              painter: _ArrowPainter(isUp: true),
            ),
          ),
        // Bubble body
        Container(
          margin: const EdgeInsets.symmetric(horizontal: 4),
          padding: const EdgeInsets.fromLTRB(16, 12, 10, 12),
          decoration: BoxDecoration(
            color: const Color(0xFF2D2D2D),
            borderRadius: BorderRadius.circular(14),
          ),
          child: Row(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    // Title (bold)
                    Text(
                      title,
                      style: const TextStyle(
                        fontFamily: 'Poppins',
                        fontSize: 14,
                        fontWeight: FontWeight.w600,
                        color: Colors.white,
                        height: 1.3,
                        decoration: TextDecoration.none,
                      ),
                    ),
                    const SizedBox(height: 4),
                    // Description text
                    Text(
                      fullText,
                      style: const TextStyle(
                        fontFamily: 'Poppins',
                        fontSize: 12.5,
                        fontWeight: FontWeight.w400,
                        color: Color(0xFFD4D4D4),
                        height: 1.4,
                        decoration: TextDecoration.none,
                      ),
                    ),
                    // Step indicator
                    if (step != null) ...[
                      const SizedBox(height: 6),
                      Text(
                        step,
                        style: const TextStyle(
                          fontFamily: 'Poppins',
                          fontSize: 10,
                          fontWeight: FontWeight.w500,
                          color: Color(0xFF8B8B8B),
                          decoration: TextDecoration.none,
                        ),
                      ),
                    ],
                  ],
                ),
              ),
              const SizedBox(width: 8),
              // Close (X) button
              GestureDetector(
                onTap: onClose,
                child: Container(
                  padding: const EdgeInsets.all(4),
                  child: const Icon(
                    Icons.close_rounded,
                    color: Color(0xFFAAAAAA),
                    size: 20,
                  ),
                ),
              ),
            ],
          ),
        ),
        // Arrow pointing down (when tooltip is above target)
        if (!arrowUp)
          Padding(
            padding: const EdgeInsets.only(left: 32),
            child: CustomPaint(
              size: const Size(16, 8),
              painter: _ArrowPainter(isUp: false),
            ),
          ),
      ],
    );
  }

  /// Show coach mark overlay
  static void show({
    required BuildContext context,
    required List<TargetFocus> targets,
  }) {
    TutorialCoachMark(
      targets: targets,
      colorShadow: const Color(0xFF000000),
      opacityShadow: 0.75,
      textSkip: '',
      hideSkip: true,
      paddingFocus: 4,
    ).show(context: context);
  }
}

/// Paints a small triangle arrow for the speech bubble.
class _ArrowPainter extends CustomPainter {
  final bool isUp;
  _ArrowPainter({required this.isUp});

  @override
  void paint(Canvas canvas, Size size) {
    final paint = Paint()
      ..color = const Color(0xFF2D2D2D)
      ..style = PaintingStyle.fill;

    final path = Path();
    if (isUp) {
      // ▲ pointing up
      path.moveTo(0, size.height);
      path.lineTo(size.width / 2, 0);
      path.lineTo(size.width, size.height);
    } else {
      // ▼ pointing down
      path.moveTo(0, 0);
      path.lineTo(size.width / 2, size.height);
      path.lineTo(size.width, 0);
    }
    path.close();
    canvas.drawPath(path, paint);
  }

  @override
  bool shouldRepaint(covariant CustomPainter oldDelegate) => false;
}
