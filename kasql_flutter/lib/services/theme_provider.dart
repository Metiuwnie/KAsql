import 'package:flutter/material.dart';
import 'package:sqflite/sqflite.dart';
import 'database_service.dart';

class ThemeProvider extends ChangeNotifier {
  ThemeMode _themeMode = ThemeMode.system;

  ThemeMode get themeMode => _themeMode;

  Future<void> init() async {
    try {
      final db = await DatabaseService().database;
      final result = await db.query('settings', where: 'key = ?', whereArgs: ['theme_mode']);
      if (result.isNotEmpty) {
        final val = result.first['value'] as String?;
        if (val == 'light') {
          _themeMode = ThemeMode.light;
        } else if (val == 'dark') {
          _themeMode = ThemeMode.dark;
        }
      }
    } catch (_) {}
    notifyListeners();
  }

  Future<void> setThemeMode(ThemeMode mode) async {
    if (_themeMode == mode) return;
    _themeMode = mode;
    notifyListeners();

    try {
      String val = 'system';
      if (mode == ThemeMode.light) val = 'light';
      if (mode == ThemeMode.dark) val = 'dark';
      
      final db = await DatabaseService().database;
      await db.insert('settings', {'key': 'theme_mode', 'value': val}, conflictAlgorithm: ConflictAlgorithm.replace);
    } catch (_) {}
  }
}
