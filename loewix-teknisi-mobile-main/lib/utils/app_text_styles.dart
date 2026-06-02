import 'package:flutter/material.dart';

/// Helper class untuk TextStyle dengan font Poppins lokal
/// Gunakan ini sebagai pengganti GoogleFonts.poppins()
class AppTextStyles {
  static const String _fontFamily = 'Poppins';

  static TextStyle poppins({
    double fontSize = 14,
    FontWeight fontWeight = FontWeight.normal,
    Color? color,
    double? height,
    double? letterSpacing,
    TextDecoration? decoration,
    FontStyle? fontStyle,
  }) {
    return TextStyle(
      fontFamily: _fontFamily,
      fontSize: fontSize,
      fontWeight: fontWeight,
      color: color,
      height: height,
      letterSpacing: letterSpacing,
      decoration: decoration,
      fontStyle: fontStyle,
    );
  }

  // Preset styles
  static TextStyle get headlineLarge => poppins(
        fontSize: 28,
        fontWeight: FontWeight.bold,
      );

  static TextStyle get headlineMedium => poppins(
        fontSize: 24,
        fontWeight: FontWeight.bold,
      );

  static TextStyle get headlineSmall => poppins(
        fontSize: 20,
        fontWeight: FontWeight.w600,
      );

  static TextStyle get titleLarge => poppins(
        fontSize: 18,
        fontWeight: FontWeight.w600,
      );

  static TextStyle get titleMedium => poppins(
        fontSize: 16,
        fontWeight: FontWeight.w500,
      );

  static TextStyle get titleSmall => poppins(
        fontSize: 14,
        fontWeight: FontWeight.w500,
      );

  static TextStyle get bodyLarge => poppins(
        fontSize: 16,
        fontWeight: FontWeight.normal,
      );

  static TextStyle get bodyMedium => poppins(
        fontSize: 14,
        fontWeight: FontWeight.normal,
      );

  static TextStyle get bodySmall => poppins(
        fontSize: 12,
        fontWeight: FontWeight.normal,
      );

  static TextStyle get labelLarge => poppins(
        fontSize: 14,
        fontWeight: FontWeight.w500,
      );

  static TextStyle get labelMedium => poppins(
        fontSize: 12,
        fontWeight: FontWeight.w500,
      );

  static TextStyle get labelSmall => poppins(
        fontSize: 10,
        fontWeight: FontWeight.w500,
      );
}
