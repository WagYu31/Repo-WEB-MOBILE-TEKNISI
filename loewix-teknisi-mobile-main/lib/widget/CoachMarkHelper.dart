import 'package:flutter/material.dart';
import 'package:tutorial_coach_mark/tutorial_coach_mark.dart';

/// Professional speech-bubble coach mark — standardized UI/UX.
///
/// Design standards:
/// - Dark tooltip (#2D2D2D), rounded 16px, box shadow
/// - Arrow pointing toward highlighted target
/// - Bold title + single-line description
/// - Accessible X close button (36px touch target)
/// - Step indicator in muted gray
/// - Global lock: only ONE coach mark active at a time (no stacking)
class CoachMarkHelper {
  /// Global lock — prevents multiple coach marks from stacking.
  static bool _isActive = false;

  /// Check if a coach mark is currently showing.
  static bool get isActive => _isActive;

  /// Builds a speech bubble tooltip widget.
  static Widget buildTooltip({
    required String title,
    required List<String> descriptions,
    String? step,
    String? note,
    String? icon,
    bool arrowUp = false,
    VoidCallback? onClose,
  }) {
    final text = descriptions.where((d) => d.isNotEmpty).join('. ');
    final fullText = note != null ? '$text. $note' : text;

    return Column(
      mainAxisSize: MainAxisSize.min,
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        // Arrow UP
        if (arrowUp)
          Padding(
            padding: const EdgeInsets.only(left: 28),
            child: CustomPaint(
              size: const Size(18, 9),
              painter: _ArrowPainter(isUp: true),
            ),
          ),
        // Bubble
        Container(
          margin: const EdgeInsets.symmetric(horizontal: 8),
          padding: const EdgeInsets.fromLTRB(16, 14, 8, 14),
          constraints: const BoxConstraints(maxWidth: 320),
          decoration: BoxDecoration(
            color: const Color(0xFF2D2D2D),
            borderRadius: BorderRadius.circular(16),
            boxShadow: [
              BoxShadow(
                color: Colors.black.withValues(alpha: 0.25),
                blurRadius: 12,
                offset: const Offset(0, 4),
              ),
            ],
          ),
          child: Row(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  mainAxisSize: MainAxisSize.min,
                  children: [
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
                    Text(
                      fullText,
                      style: const TextStyle(
                        fontFamily: 'Poppins',
                        fontSize: 12.5,
                        fontWeight: FontWeight.w400,
                        color: Color(0xFFCCCCCC),
                        height: 1.45,
                        decoration: TextDecoration.none,
                      ),
                    ),
                    if (step != null) ...[
                      const SizedBox(height: 6),
                      Text(
                        step,
                        style: const TextStyle(
                          fontFamily: 'Poppins',
                          fontSize: 10,
                          fontWeight: FontWeight.w500,
                          color: Color(0xFF888888),
                          decoration: TextDecoration.none,
                        ),
                      ),
                    ],
                  ],
                ),
              ),
              GestureDetector(
                behavior: HitTestBehavior.opaque,
                onTap: onClose,
                child: Container(
                  width: 36,
                  height: 36,
                  alignment: Alignment.center,
                  child: const Icon(
                    Icons.close_rounded,
                    color: Color(0xFFAAAAAA),
                    size: 18,
                  ),
                ),
              ),
            ],
          ),
        ),
        // Arrow DOWN
        if (!arrowUp)
          Padding(
            padding: const EdgeInsets.only(left: 28),
            child: CustomPaint(
              size: const Size(18, 9),
              painter: _ArrowPainter(isUp: false),
            ),
          ),
      ],
    );
  }

  /// Shows the coach mark overlay with global lock.
  /// Returns false if another coach mark is already active.
  static bool show({
    required BuildContext context,
    required List<TargetFocus> targets,
  }) {
    // Prevent stacking — only one at a time
    if (_isActive) return false;
    _isActive = true;

    TutorialCoachMark(
      targets: targets,
      colorShadow: const Color(0xFF000000),
      opacityShadow: 0.72,
      textSkip: '',
      hideSkip: true,
      paddingFocus: 6,
      onFinish: () => _isActive = false,
      onSkip: () {
        _isActive = false;
        return true;
      },
      onClickOverlay: (target) {
        // Allow advancing by tapping overlay
      },
    ).show(context: context);
    return true;
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
      path.moveTo(0, size.height);
      path.lineTo(size.width / 2, 0);
      path.lineTo(size.width, size.height);
    } else {
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
