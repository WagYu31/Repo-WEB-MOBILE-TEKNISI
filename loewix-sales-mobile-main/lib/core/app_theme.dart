import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:google_fonts/google_fonts.dart';

// ────────────────────────────────────────────────────────
//  COLOR PALETTE — Premium Dark Navy
// ────────────────────────────────────────────────────────
class AppColors {
  AppColors._();

  // Backgrounds (layered depth)
  static const bg      = Color(0xFF070D1A); // deepest bg
  static const surface = Color(0xFF0C1826); // elevated surface
  static const card    = Color(0xFF0F1F36); // card bg
  static const cardAlt = Color(0xFF132540); // slightly lighter card

  // Borders
  static const border      = Color(0xFF1A3155);
  static const borderLight = Color(0xFF244468);

  // Brand — Premium Gold/Amber
  static const primary      = Color(0xFFF59E0B);
  static const primaryLight = Color(0xFFFBBF24);
  static const primaryDark  = Color(0xFFD97706);
  static const primaryGlow  = Color(0x40F59E0B);

  // Status — semantic
  static const success    = Color(0xFF22C55E);
  static const successBg  = Color(0xFF052E16);
  static const warning    = Color(0xFFF59E0B);
  static const warningBg  = Color(0xFF1C1505);
  static const error      = Color(0xFFEF4444);
  static const errorBg    = Color(0xFF1F0808);
  static const pending    = Color(0xFF64748B);
  static const pendingBg  = Color(0xFF0F172A);
  static const info       = Color(0xFF06B6D4);

  // Text hierarchy
  static const textPrimary   = Color(0xFFEDF3FF);
  static const textSecondary = Color(0xFF8BA0C2);
  static const textMuted     = Color(0xFF4A637E);
  static const textHint      = Color(0xFF2D4260);

  // Utility
  static const divider = Color(0xFF132035);
  static const overlay = Color(0xCC070D1A);
  static const white   = Colors.white;

  // Gradients
  static const gradientPrimary = LinearGradient(
    colors: [Color(0xFFE58E08), Color(0xFFF7B731)],
    begin: Alignment.topLeft,
    end: Alignment.bottomRight,
  );
  static const gradientBg = LinearGradient(
    colors: [Color(0xFF080F1E), Color(0xFF0B1828), Color(0xFF070F1B)],
    begin: Alignment.topLeft,
    end: Alignment.bottomRight,
  );
  static const gradientHeader = LinearGradient(
    colors: [Color(0xFF1A160A), Color(0xFF070D1A)],
    begin: Alignment.topCenter,
    end: Alignment.bottomCenter,
  );
}

// ────────────────────────────────────────────────────────
//  TYPOGRAPHY — Inter (ISO Corporate Standard)
// ────────────────────────────────────────────────────────
class S {
  S._();

  static TextStyle display([Color? c]) => GoogleFonts.inter(
    fontSize: 28, fontWeight: FontWeight.w700,
    color: c ?? AppColors.textPrimary,
    letterSpacing: -0.8, height: 1.15,
  );

  static TextStyle h1([Color? c]) => GoogleFonts.inter(
    fontSize: 22, fontWeight: FontWeight.w700,
    color: c ?? AppColors.textPrimary,
    letterSpacing: -0.5, height: 1.2,
  );

  static TextStyle h2([Color? c]) => GoogleFonts.inter(
    fontSize: 18, fontWeight: FontWeight.w600,
    color: c ?? AppColors.textPrimary,
    letterSpacing: -0.3, height: 1.3,
  );

  static TextStyle h3([Color? c]) => GoogleFonts.inter(
    fontSize: 15, fontWeight: FontWeight.w600,
    color: c ?? AppColors.textPrimary, height: 1.35,
  );

  static TextStyle bodyLg([Color? c]) => GoogleFonts.inter(
    fontSize: 15, fontWeight: FontWeight.w500,
    color: c ?? AppColors.textPrimary, height: 1.5,
  );

  static TextStyle body([Color? c]) => GoogleFonts.inter(
    fontSize: 14, fontWeight: FontWeight.w400,
    color: c ?? AppColors.textSecondary, height: 1.5,
  );

  static TextStyle bodySm([Color? c]) => GoogleFonts.inter(
    fontSize: 13, fontWeight: FontWeight.w400,
    color: c ?? AppColors.textSecondary, height: 1.4,
  );

  static TextStyle caption([Color? c]) => GoogleFonts.inter(
    fontSize: 12, fontWeight: FontWeight.w400,
    color: c ?? AppColors.textMuted, height: 1.4,
  );

  static TextStyle micro([Color? c]) => GoogleFonts.inter(
    fontSize: 11, fontWeight: FontWeight.w500,
    color: c ?? AppColors.textMuted, letterSpacing: 0.2,
  );

  static TextStyle label([Color? c]) => GoogleFonts.inter(
    fontSize: 10, fontWeight: FontWeight.w600,
    color: c ?? AppColors.textMuted, letterSpacing: 0.5,
  );

  static TextStyle btn([Color? c]) => GoogleFonts.inter(
    fontSize: 15, fontWeight: FontWeight.w600,
    color: c ?? AppColors.white, letterSpacing: 0.2,
  );

  static TextStyle btnSm([Color? c]) => GoogleFonts.inter(
    fontSize: 13, fontWeight: FontWeight.w600,
    color: c ?? AppColors.white,
  );
}

// ────────────────────────────────────────────────────────
//  THEME & DECORATION HELPERS
// ────────────────────────────────────────────────────────
class AppTheme {
  AppTheme._();

  static ThemeData get dark => ThemeData(
    brightness: Brightness.dark,
    scaffoldBackgroundColor: AppColors.bg,
    colorScheme: ColorScheme.dark(
      primary: AppColors.primary,
      secondary: AppColors.primaryLight,
      surface: AppColors.surface,
      error: AppColors.error,
    ),
    useMaterial3: true,
    fontFamily: GoogleFonts.inter().fontFamily,
    appBarTheme: AppBarTheme(
      backgroundColor: Colors.transparent,
      elevation: 0,
      scrolledUnderElevation: 0,
      centerTitle: false,
      titleTextStyle: S.h3(),
      iconTheme: const IconThemeData(color: AppColors.textPrimary, size: 22),
      systemOverlayStyle: const SystemUiOverlayStyle(
        statusBarColor: Colors.transparent,
        statusBarIconBrightness: Brightness.light,
      ),
    ),
    bottomNavigationBarTheme: BottomNavigationBarThemeData(
      backgroundColor: AppColors.surface,
      selectedItemColor: AppColors.primary,
      unselectedItemColor: AppColors.textMuted,
      selectedLabelStyle: S.label(AppColors.primary),
      unselectedLabelStyle: S.label(AppColors.textMuted),
      type: BottomNavigationBarType.fixed,
      elevation: 0,
      showSelectedLabels: true,
      showUnselectedLabels: true,
    ),
    inputDecorationTheme: InputDecorationTheme(
      filled: true,
      fillColor: AppColors.bg,
      hintStyle: S.body(AppColors.textHint),
      contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 14),
      border: OutlineInputBorder(
        borderRadius: BorderRadius.circular(14),
        borderSide: const BorderSide(color: AppColors.border),
      ),
      enabledBorder: OutlineInputBorder(
        borderRadius: BorderRadius.circular(14),
        borderSide: const BorderSide(color: AppColors.border),
      ),
      focusedBorder: OutlineInputBorder(
        borderRadius: BorderRadius.circular(14),
        borderSide: const BorderSide(color: AppColors.primary, width: 1.8),
      ),
    ),
    progressIndicatorTheme: const ProgressIndicatorThemeData(
      color: AppColors.primary,
    ),
    snackBarTheme: SnackBarThemeData(
      backgroundColor: AppColors.cardAlt,
      contentTextStyle: S.body(AppColors.textPrimary),
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
      behavior: SnackBarBehavior.floating,
    ),
  );

  // ── Card Decoration ───────────────────────────────────
  static BoxDecoration cardDeco({
    Color? accentColor,
    double radius = 16,
    bool elevated = false,
  }) =>
      BoxDecoration(
        color: AppColors.card,
        borderRadius: BorderRadius.circular(radius),
        border: Border.all(
          color: accentColor?.withOpacity(0.35) ?? AppColors.border,
        ),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withOpacity(elevated ? 0.35 : 0.22),
            blurRadius: elevated ? 20 : 12,
            offset: const Offset(0, 4),
          ),
          if (accentColor != null)
            BoxShadow(
              color: accentColor.withOpacity(0.08),
              blurRadius: 24,
              spreadRadius: 1,
            ),
        ],
      );

  // ── Gradient Button Decoration ────────────────────────
  static BoxDecoration btnDeco({double radius = 14}) => BoxDecoration(
    gradient: AppColors.gradientPrimary,
    borderRadius: BorderRadius.circular(radius),
    boxShadow: [
      BoxShadow(
        color: AppColors.primary.withOpacity(0.4),
        blurRadius: 18,
        offset: const Offset(0, 6),
      ),
    ],
  );

  // ── Glass Card ────────────────────────────────────────
  static BoxDecoration glassDeco({double radius = 20}) => BoxDecoration(
    color: AppColors.card.withOpacity(0.8),
    borderRadius: BorderRadius.circular(radius),
    border: Border.all(color: AppColors.borderLight.withOpacity(0.6)),
    boxShadow: [
      BoxShadow(
        color: Colors.black.withOpacity(0.3),
        blurRadius: 20,
        offset: const Offset(0, 8),
      ),
    ],
  );
}
