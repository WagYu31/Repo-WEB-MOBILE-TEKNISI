import 'package:flutter/material.dart';
import 'package:tutorial_coach_mark/tutorial_coach_mark.dart';

/// Shared helper for building consistent coach mark tooltips across all pages.
class CoachMarkHelper {
  /// Build a styled tooltip widget for coach marks
  static Widget buildTooltip({
    required String icon,
    required String title,
    required List<String> descriptions,
    String? note,
    String? step,
  }) {
    return Container(
      margin: const EdgeInsets.only(top: 16),
      padding: const EdgeInsets.all(20),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(16),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.15),
            blurRadius: 20,
            offset: const Offset(0, 8),
          ),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        mainAxisSize: MainAxisSize.min,
        children: [
          Row(
            children: [
              Text(icon, style: const TextStyle(fontSize: 24)),
              const SizedBox(width: 10),
              Expanded(
                child: Text(
                  title,
                  style: const TextStyle(
                    fontFamily: 'Poppins',
                    fontSize: 16,
                    fontWeight: FontWeight.w700,
                    color: Color(0xFF1E293B),
                  ),
                ),
              ),
              if (step != null)
                Container(
                  padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                  decoration: BoxDecoration(
                    color: const Color(0xFF0EA5E9).withValues(alpha: 0.1),
                    borderRadius: BorderRadius.circular(8),
                  ),
                  child: Text(
                    step,
                    style: const TextStyle(
                      fontFamily: 'Poppins',
                      fontSize: 11,
                      fontWeight: FontWeight.w600,
                      color: Color(0xFF0EA5E9),
                    ),
                  ),
                ),
            ],
          ),
          const SizedBox(height: 12),
          ...descriptions.map((desc) => Padding(
            padding: const EdgeInsets.only(bottom: 4),
            child: Text(
              desc,
              style: const TextStyle(
                fontFamily: 'Poppins',
                fontSize: 13,
                fontWeight: FontWeight.w400,
                color: Color(0xFF475569),
                height: 1.5,
              ),
            ),
          )),
          if (note != null) ...[
            const SizedBox(height: 8),
            Container(
              padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
              decoration: BoxDecoration(
                color: const Color(0xFFFFF7ED),
                borderRadius: BorderRadius.circular(8),
                border: Border.all(color: const Color(0xFFF97316).withValues(alpha: 0.2)),
              ),
              child: Row(
                children: [
                  const Text('\u{1F4A1}', style: TextStyle(fontSize: 14)),
                  const SizedBox(width: 6),
                  Expanded(
                    child: Text(
                      note,
                      style: const TextStyle(
                        fontFamily: 'Poppins',
                        fontSize: 11,
                        fontWeight: FontWeight.w500,
                        color: Color(0xFFF97316),
                      ),
                    ),
                  ),
                ],
              ),
            ),
          ],
          const SizedBox(height: 12),
          const Row(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              Text(
                'Tap di mana saja untuk lanjut \u2192',
                style: TextStyle(
                  fontFamily: 'Poppins',
                  fontSize: 11,
                  fontWeight: FontWeight.w500,
                  color: Color(0xFF94A3B8),
                ),
              ),
            ],
          ),
        ],
      ),
    );
  }

  /// Show a coach mark with consistent styling
  static void show({
    required BuildContext context,
    required List<TargetFocus> targets,
  }) {
    TutorialCoachMark(
      targets: targets,
      colorShadow: const Color(0xFF0F172A),
      opacityShadow: 0.85,
      textSkip: 'LEWATI',
      textStyleSkip: const TextStyle(
        fontFamily: 'Poppins',
        fontSize: 14,
        fontWeight: FontWeight.w600,
        color: Colors.white70,
      ),
      paddingFocus: 8,
    ).show(context: context);
  }
}
