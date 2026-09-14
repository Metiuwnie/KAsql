import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';

/// "Financial Noir" — Premium dark-first design system
/// Deep navy base with teal accent and gold highlights.
/// Distinctive, not generic Material Design.
class AppTheme {
  // ──────────────────── Color Palette ────────────────────
  // Core
  static const Color navy900 = Color(0xFF0B1426);
  static const Color navy800 = Color(0xFF111D35);
  static const Color navy700 = Color(0xFF1A2744);
  static const Color navy600 = Color(0xFF243354);
  static const Color navy500 = Color(0xFF334766);

  // Surface (dark)
  static const Color surfaceDark = Color(0xFF131E33);
  static const Color surfaceCardDark = Color(0xFF182440);
  static const Color surfaceElevatedDark = Color(0xFF1E2D4D);

  // Surface (light)
  static const Color surfaceLight = Color(0xFFF5F7FA);
  static const Color surfaceCardLight = Color(0xFFFFFFFF);
  static const Color surfaceElevatedLight = Color(0xFFF0F2F6);

  // Accent
  static const Color teal = Color(0xFF14B8A6);
  static const Color tealLight = Color(0xFF2DD4BF);
  static const Color tealDark = Color(0xFF0D9488);

  // Gold (for highlights, profit indicators)
  static const Color gold = Color(0xFFF59E0B);
  static const Color goldLight = Color(0xFFFBBF24);
  static const Color goldDark = Color(0xFFD97706);

  // Semantic
  static const Color success = Color(0xFF10B981);
  static const Color danger = Color(0xFFEF4444);
  static const Color warning = Color(0xFFF59E0B);
  static const Color info = Color(0xFF3B82F6);

  // Text
  static const Color textPrimaryDark = Color(0xFFF1F5F9);
  static const Color textSecondaryDark = Color(0xFF94A3B8);
  static const Color textMutedDark = Color(0xFF64748B);

  static const Color textPrimaryLight = Color(0xFF1E293B);
  static const Color textSecondaryLight = Color(0xFF475569);
  static const Color textMutedLight = Color(0xFF94A3B8);

  // Borders
  static const Color borderDark = Color(0xFF1E3050);
  static const Color borderLight = Color(0xFFE2E8F0);

  // ──────────────────── Typography ────────────────────
  static TextTheme _buildTextTheme(Brightness brightness) {
    final Color primary = brightness == Brightness.dark ? textPrimaryDark : textPrimaryLight;
    final Color secondary = brightness == Brightness.dark ? textSecondaryDark : textSecondaryLight;

    return TextTheme(
      displayLarge: GoogleFonts.dmSans(fontSize: 32, fontWeight: FontWeight.w800, color: primary, letterSpacing: -0.5),
      displayMedium: GoogleFonts.dmSans(fontSize: 28, fontWeight: FontWeight.w700, color: primary, letterSpacing: -0.3),
      displaySmall: GoogleFonts.dmSans(fontSize: 24, fontWeight: FontWeight.w700, color: primary),
      headlineLarge: GoogleFonts.dmSans(fontSize: 22, fontWeight: FontWeight.w700, color: primary),
      headlineMedium: GoogleFonts.dmSans(fontSize: 18, fontWeight: FontWeight.w600, color: primary),
      headlineSmall: GoogleFonts.dmSans(fontSize: 16, fontWeight: FontWeight.w600, color: primary),
      titleLarge: GoogleFonts.dmSans(fontSize: 18, fontWeight: FontWeight.w600, color: primary),
      titleMedium: GoogleFonts.dmSans(fontSize: 16, fontWeight: FontWeight.w500, color: primary),
      titleSmall: GoogleFonts.dmSans(fontSize: 14, fontWeight: FontWeight.w500, color: secondary),
      bodyLarge: GoogleFonts.dmSans(fontSize: 16, fontWeight: FontWeight.w400, color: primary),
      bodyMedium: GoogleFonts.dmSans(fontSize: 14, fontWeight: FontWeight.w400, color: secondary),
      bodySmall: GoogleFonts.dmSans(fontSize: 12, fontWeight: FontWeight.w400, color: secondary),
      labelLarge: GoogleFonts.dmSans(fontSize: 14, fontWeight: FontWeight.w600, color: primary, letterSpacing: 0.5),
      labelMedium: GoogleFonts.dmSans(fontSize: 12, fontWeight: FontWeight.w500, color: secondary),
      labelSmall: GoogleFonts.dmSans(fontSize: 10, fontWeight: FontWeight.w500, color: secondary, letterSpacing: 0.5),
    );
  }

  // ──────────────────── Dark Theme ────────────────────
  static ThemeData get darkTheme {
    return ThemeData(
      brightness: Brightness.dark,
      useMaterial3: true,
      textTheme: _buildTextTheme(Brightness.dark),
      scaffoldBackgroundColor: navy900,
      colorScheme: ColorScheme.dark(
        primary: teal,
        onPrimary: Colors.white,
        secondary: gold,
        onSecondary: navy900,
        surface: surfaceDark,
        onSurface: textPrimaryDark,
        error: danger,
        onError: Colors.white,
        outline: borderDark,
      ),
      appBarTheme: AppBarTheme(
        backgroundColor: navy800,
        foregroundColor: textPrimaryDark,
        elevation: 0,
        centerTitle: false,
        titleTextStyle: GoogleFonts.dmSans(
          fontSize: 18, fontWeight: FontWeight.w700, color: textPrimaryDark,
        ),
      ),
      drawerTheme: const DrawerThemeData(
        backgroundColor: navy800,
      ),
      cardTheme: CardThemeData(
        color: surfaceCardDark,
        elevation: 0,
        shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.circular(16),
          side: BorderSide(color: borderDark.withValues(alpha: 0.5)),
        ),
      ),
      bottomNavigationBarTheme: BottomNavigationBarThemeData(
        backgroundColor: navy800,
        selectedItemColor: teal,
        unselectedItemColor: textMutedDark,
        type: BottomNavigationBarType.fixed,
        elevation: 0,
        selectedLabelStyle: GoogleFonts.dmSans(fontSize: 11, fontWeight: FontWeight.w600),
        unselectedLabelStyle: GoogleFonts.dmSans(fontSize: 11, fontWeight: FontWeight.w400),
      ),
      floatingActionButtonTheme: const FloatingActionButtonThemeData(
        backgroundColor: teal,
        foregroundColor: Colors.white,
        elevation: 8,
      ),
      inputDecorationTheme: InputDecorationTheme(
        filled: true,
        fillColor: navy700,
        border: OutlineInputBorder(
          borderRadius: BorderRadius.circular(12),
          borderSide: BorderSide(color: borderDark),
        ),
        enabledBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(12),
          borderSide: BorderSide(color: borderDark),
        ),
        focusedBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(12),
          borderSide: const BorderSide(color: teal, width: 2),
        ),
        contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 14),
        hintStyle: GoogleFonts.dmSans(color: textMutedDark),
        labelStyle: GoogleFonts.dmSans(color: textSecondaryDark),
      ),
      elevatedButtonTheme: ElevatedButtonThemeData(
        style: ElevatedButton.styleFrom(
          backgroundColor: teal,
          foregroundColor: Colors.white,
          elevation: 0,
          padding: const EdgeInsets.symmetric(horizontal: 24, vertical: 14),
          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
          textStyle: GoogleFonts.dmSans(fontSize: 14, fontWeight: FontWeight.w600),
        ),
      ),
      dividerTheme: DividerThemeData(color: borderDark.withValues(alpha: 0.5)),
      chipTheme: ChipThemeData(
        backgroundColor: navy700,
        selectedColor: teal.withValues(alpha: 0.2),
        labelStyle: GoogleFonts.dmSans(fontSize: 12, fontWeight: FontWeight.w500),
        side: BorderSide(color: borderDark),
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
      ),
    );
  }

  // ──────────────────── Light Theme ────────────────────
  static ThemeData get lightTheme {
    return ThemeData(
      brightness: Brightness.light,
      useMaterial3: true,
      textTheme: _buildTextTheme(Brightness.light),
      scaffoldBackgroundColor: surfaceLight,
      colorScheme: ColorScheme.light(
        primary: tealDark,
        onPrimary: Colors.white,
        secondary: goldDark,
        onSecondary: Colors.white,
        surface: surfaceCardLight,
        onSurface: textPrimaryLight,
        error: danger,
        onError: Colors.white,
        outline: borderLight,
      ),
      appBarTheme: AppBarTheme(
        backgroundColor: Colors.white,
        foregroundColor: textPrimaryLight,
        elevation: 0,
        centerTitle: false,
        surfaceTintColor: Colors.transparent,
        titleTextStyle: GoogleFonts.dmSans(
          fontSize: 18, fontWeight: FontWeight.w700, color: textPrimaryLight,
        ),
      ),
      drawerTheme: const DrawerThemeData(
        backgroundColor: Colors.white,
      ),
      cardTheme: CardThemeData(
        color: surfaceCardLight,
        elevation: 0,
        shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.circular(16),
          side: BorderSide(color: borderLight),
        ),
      ),
      bottomNavigationBarTheme: BottomNavigationBarThemeData(
        backgroundColor: Colors.white,
        selectedItemColor: tealDark,
        unselectedItemColor: textMutedLight,
        type: BottomNavigationBarType.fixed,
        elevation: 0,
        selectedLabelStyle: GoogleFonts.dmSans(fontSize: 11, fontWeight: FontWeight.w600),
        unselectedLabelStyle: GoogleFonts.dmSans(fontSize: 11, fontWeight: FontWeight.w400),
      ),
      floatingActionButtonTheme: const FloatingActionButtonThemeData(
        backgroundColor: tealDark,
        foregroundColor: Colors.white,
        elevation: 4,
      ),
      inputDecorationTheme: InputDecorationTheme(
        filled: true,
        fillColor: surfaceElevatedLight,
        border: OutlineInputBorder(
          borderRadius: BorderRadius.circular(12),
          borderSide: BorderSide(color: borderLight),
        ),
        enabledBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(12),
          borderSide: BorderSide(color: borderLight),
        ),
        focusedBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(12),
          borderSide: const BorderSide(color: tealDark, width: 2),
        ),
        contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 14),
        hintStyle: GoogleFonts.dmSans(color: textMutedLight),
        labelStyle: GoogleFonts.dmSans(color: textSecondaryLight),
      ),
      elevatedButtonTheme: ElevatedButtonThemeData(
        style: ElevatedButton.styleFrom(
          backgroundColor: tealDark,
          foregroundColor: Colors.white,
          elevation: 0,
          padding: const EdgeInsets.symmetric(horizontal: 24, vertical: 14),
          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
          textStyle: GoogleFonts.dmSans(fontSize: 14, fontWeight: FontWeight.w600),
        ),
      ),
      dividerTheme: DividerThemeData(color: borderLight),
      chipTheme: ChipThemeData(
        backgroundColor: surfaceElevatedLight,
        selectedColor: tealDark.withValues(alpha: 0.1),
        labelStyle: GoogleFonts.dmSans(fontSize: 12, fontWeight: FontWeight.w500),
        side: BorderSide(color: borderLight),
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
      ),
    );
  }
}
