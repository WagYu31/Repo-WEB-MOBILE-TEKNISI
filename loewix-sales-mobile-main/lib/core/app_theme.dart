import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:google_fonts/google_fonts.dart';

// ────────────────────────────────────────────────────────
//  COLOR PALETTE — Premium Dark Navy
// ────────────────────────────────────────────────────────
class AppColors {
  AppColors._();

  // Backgrounds (layered depth) - Light Mode Slate
  static const bg      = Color(0xFFF8FAFC); // Slate 50 (clean light bg)
  static const surface = Color(0xFFFFFFFF); // White surface
  static const card    = Color(0xFFFFFFFF); // White card bg
  static const cardAlt = Color(0xFFF1F5F9); // Slate 100 card alt

  // Borders - Light Mode
  static const border      = Color(0xFFE2E8F0); // Slate 200
  static const borderLight = Color(0xFFCBD5E1); // Slate 300

  // Brand — Bright Yellow/Amber (kuning cerah premium)
  static const primary      = Color(0xFFEAB308); // Yellow 500
  static const primaryLight = Color(0xFFFACC15); // Yellow 400
  static const primaryDark  = Color(0xFFCA8A04); // Yellow 600
  static const primaryGlow  = Color(0x25EAB308); // Glow

  // Status — semantic
  static const success    = Color(0xFF16A34A); // Green 600
  static const successBg  = Color(0xFFDCFCE7); // Green 100
  static const warning    = Color(0xFFD97706); // Amber 600
  static const warningBg  = Color(0xFFFEF3C7); // Amber 100
  static const error      = Color(0xFFDC2626); // Red 600
  static const errorBg    = Color(0xFFFEE2E2); // Red 100
  static const pending    = Color(0xFF475569); // Slate 600
  static const pendingBg  = Color(0xFFF1F5F9); // Slate 100
  static const info       = Color(0xFF0891B2); // Cyan 600

  // Text hierarchy - Light Mode Dark Slate
  static const textPrimary   = Color(0xFF0F172A); // Slate 900
  static const textSecondary = Color(0xFF475569); // Slate 600
  static const textMuted     = Color(0xFF64748B); // Slate 500
  static const textHint      = Color(0xFF94A3B8); // Slate 400

  // Utility
  static const divider = Color(0xFFE2E8F0);
  static const overlay = Color(0x50000000);
  static const white   = Colors.white;

  // Gradients - Light Mode
  static const gradientPrimary = LinearGradient(
    colors: [Color(0xFFEAB308), Color(0xFFFDE047)], // Bright yellow gradient
    begin: Alignment.topLeft,
    end: Alignment.bottomRight,
  );
  static const gradientBg = LinearGradient(
    colors: [Color(0xFFF8FAFC), Color(0xFFFFFFFF), Color(0xFFF1F5F9)],
    begin: Alignment.topLeft,
    end: Alignment.bottomRight,
  );
  static const gradientHeader = LinearGradient(
    colors: [Color(0xFFFEF9C3), Color(0xFFF8FAFC)], // Soft yellow-white header
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

  static ThemeData get light => ThemeData(
    brightness: Brightness.light,
    scaffoldBackgroundColor: AppColors.bg,
    colorScheme: ColorScheme.light(
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
        statusBarIconBrightness: Brightness.dark,
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
      fillColor: AppColors.surface,
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

  static ThemeData get dark => light;

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
            color: Colors.black.withOpacity(elevated ? 0.12 : 0.05),
            blurRadius: elevated ? 16 : 8,
            offset: const Offset(0, 4),
          ),
          if (accentColor != null)
            BoxShadow(
              color: accentColor.withOpacity(0.04),
              blurRadius: 16,
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
        color: AppColors.primary.withOpacity(0.25),
        blurRadius: 14,
        offset: const Offset(0, 5),
      ),
    ],
  );

  // ── Glass Card ────────────────────────────────────────
  static BoxDecoration glassDeco({double radius = 20}) => BoxDecoration(
    color: AppColors.card.withOpacity(0.9),
    borderRadius: BorderRadius.circular(radius),
    border: Border.all(color: AppColors.borderLight.withOpacity(0.4)),
    boxShadow: [
      BoxShadow(
        color: Colors.black.withOpacity(0.08),
        blurRadius: 16,
        offset: const Offset(0, 6),
      ),
    ],
  );
}
