import 'package:flutter/material.dart';
import 'package:tutorial_coach_mark/tutorial_coach_mark.dart';

/// Sleek, minimal coach mark helper — professional & compact.
class CoachMarkHelper {
  // Design tokens
  static const _bg = Color(0xFF1E293B);
  static const _accent = Color(0xFF38BDF8);
  static const _textWhite = Color(0xFFF1F5F9);
  static const _textMuted = Color(0xFF94A3B8);

  /// Compact tooltip — icon + title + bullet list
  static Widget buildTooltip({
    required String title,
    required List<String> descriptions,
    String? step,
    String? note,
    String? icon, // kept for backward compat, ignored in new design
  }) {
    return Container(
      margin: const EdgeInsets.symmetric(horizontal: 8, vertical: 10),
      padding: const EdgeInsets.fromLTRB(16, 14, 16, 12),
      constraints: const BoxConstraints(maxWidth: 300),
      decoration: BoxDecoration(
        color: _bg,
        borderRadius: BorderRadius.circular(14),
        border: Border.all(color: _accent.withValues(alpha: 0.25), width: 1),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.35),
            blurRadius: 16,
            offset: const Offset(0, 6),
          ),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        mainAxisSize: MainAxisSize.min,
        children: [
          // Header: title + step badge
          Row(
            children: [
              Expanded(
                child: Text(
                  title,
                  style: const TextStyle(
                    fontFamily: 'Poppins',
                    fontSize: 14,
                    fontWeight: FontWeight.w600,
                    color: _textWhite,
                    height: 1.3,
                  ),
                ),
              ),
              if (step != null) ...[
                const SizedBox(width: 8),
                Container(
                  padding: const EdgeInsets.symmetric(horizontal: 7, vertical: 2),
                  decoration: BoxDecoration(
                    color: _accent.withValues(alpha: 0.15),
                    borderRadius: BorderRadius.circular(6),
                  ),
                  child: Text(
                    step,
                    style: const TextStyle(
                      fontFamily: 'Poppins',
                      fontSize: 10,
                      fontWeight: FontWeight.w600,
                      color: _accent,
                    ),
                  ),
                ),
              ],
            ],
          ),
          const SizedBox(height: 8),
          // Description bullets
          ...descriptions.where((d) => d.isNotEmpty).map((desc) => Padding(
            padding: const EdgeInsets.only(bottom: 3),
            child: Row(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Container(
                  width: 4, height: 4,
                  margin: const EdgeInsets.only(top: 6, right: 8),
                  decoration: BoxDecoration(
                    color: _accent,
                    borderRadius: BorderRadius.circular(2),
                  ),
                ),
                Expanded(
                  child: Text(
                    desc,
                    style: const TextStyle(
                      fontFamily: 'Poppins',
                      fontSize: 12,
                      fontWeight: FontWeight.w400,
                      color: _textMuted,
                      height: 1.4,
                    ),
                  ),
                ),
              ],
            ),
          )),
          if (note != null) ...[
            const SizedBox(height: 6),
            Row(
              children: [
                Icon(Icons.info_outline_rounded, size: 12, color: _accent.withValues(alpha: 0.7)),
                const SizedBox(width: 5),
                Expanded(
                  child: Text(
                    note,
                    style: TextStyle(
                      fontFamily: 'Poppins',
                      fontSize: 10,
                      fontWeight: FontWeight.w500,
                      color: _accent.withValues(alpha: 0.7),
                    ),
                  ),
                ),
              ],
            ),
          ],
        ],
      ),
    );
  }

  /// Show coach mark with dark pro styling
  static void show({
    required BuildContext context,
    required List<TargetFocus> targets,
  }) {
    TutorialCoachMark(
      targets: targets,
      colorShadow: const Color(0xFF0F172A),
      opacityShadow: 0.82,
      textSkip: 'LEWATI',
      textStyleSkip: const TextStyle(
        fontFamily: 'Poppins',
        fontSize: 13,
        fontWeight: FontWeight.w600,
        color: _accent,
        letterSpacing: 0.5,
      ),
      paddingFocus: 6,
    ).show(context: context);
  }
}
