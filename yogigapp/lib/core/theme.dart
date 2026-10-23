import 'package:flutter/material.dart';

/// Brand colours shared with the website (public/css/style.css).
class BrandColors {
  static const teal = Color(0xFF0F4C5C);
  static const tealDark = Color(0xFF072A33);
  static const sage = Color(0xFF4E878C);
  static const gold = Color(0xFFE3A857);
  static const cream = Color(0xFFF7F4EE);
  static const success = Color(0xFF2E7D4F);
  static const warning = Color(0xFFB7791F);
  static const danger = Color(0xFFC0392B);
  static const info = Color(0xFF2B6CB0);
}

ThemeData buildTheme() {
  final scheme = ColorScheme.fromSeed(
    seedColor: BrandColors.teal,
    primary: BrandColors.teal,
    secondary: BrandColors.gold,
    surface: Colors.white,
  );

  final rounded = RoundedRectangleBorder(borderRadius: BorderRadius.circular(14));

  return ThemeData(
    colorScheme: scheme,
    useMaterial3: true,
    scaffoldBackgroundColor: BrandColors.cream,
    appBarTheme: const AppBarTheme(
      backgroundColor: BrandColors.teal,
      foregroundColor: Colors.white,
      centerTitle: false,
      elevation: 0,
    ),
    cardTheme: CardThemeData(
      color: Colors.white,
      elevation: 0,
      margin: EdgeInsets.zero,
      shape: RoundedRectangleBorder(
        borderRadius: BorderRadius.circular(16),
        side: BorderSide(color: Colors.black.withValues(alpha: 0.06)),
      ),
    ),
    inputDecorationTheme: InputDecorationTheme(
      filled: true,
      fillColor: Colors.white,
      border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
      enabledBorder: OutlineInputBorder(
        borderRadius: BorderRadius.circular(12),
        borderSide: BorderSide(color: Colors.black.withValues(alpha: 0.12)),
      ),
      contentPadding: const EdgeInsets.symmetric(horizontal: 14, vertical: 14),
    ),
    filledButtonTheme: FilledButtonThemeData(
      style: FilledButton.styleFrom(
        minimumSize: const Size(64, 48),
        shape: rounded,
        textStyle: const TextStyle(fontWeight: FontWeight.w600),
      ),
    ),
    outlinedButtonTheme: OutlinedButtonThemeData(
      style: OutlinedButton.styleFrom(minimumSize: const Size(64, 48), shape: rounded),
    ),
    navigationBarTheme: NavigationBarThemeData(
      backgroundColor: Colors.white,
      indicatorColor: BrandColors.gold.withValues(alpha: 0.35),
    ),
    chipTheme: ChipThemeData(shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20))),
  );
}
