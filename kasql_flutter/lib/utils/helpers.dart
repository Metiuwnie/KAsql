import 'package:intl/intl.dart';
import 'package:flutter/material.dart';

/// Format number as Indonesian Rupiah
String formatRupiah(dynamic value, {bool withSymbol = true}) {
  final num numValue = (value is String) ? (double.tryParse(value) ?? 0) : (value ?? 0);
  final formatter = NumberFormat('#,##0', 'id_ID');
  final formatted = formatter.format(numValue.abs());
  final prefix = numValue < 0 ? '- ' : '';
  return withSymbol ? '${prefix}Rp $formatted' : '$prefix$formatted';
}

/// Format date to Indonesian locale
String formatTanggal(String? date, {String format = 'dd MMM yyyy'}) {
  if (date == null || date.isEmpty) return '-';
  try {
    final dt = DateTime.parse(date);
    return DateFormat(format, 'id_ID').format(dt);
  } catch (_) {
    return date;
  }
}

/// Get color for debit/kredit
Color getDkColor(String dk) {
  return dk.toLowerCase() == 'debit'
      ? const Color(0xFF3B82F6) // Blue
      : const Color(0xFFEF4444); // Red
}

/// Role display labels
String getRoleLabel(String role) {
  switch (role) {
    case 'admin':
      return 'Admin';
    case 'accountant':
      return 'Akuntan';
    case 'cashier':
      return 'Kasir';
    default:
      return role;
  }
}

/// Role badge color
Color getRoleColor(String role) {
  switch (role) {
    case 'admin':
      return const Color(0xFFEF4444);
    case 'accountant':
      return const Color(0xFF3B82F6);
    case 'cashier':
      return const Color(0xFF10B981);
    default:
      return const Color(0xFF64748B);
  }
}

/// Sync status display
String getSyncStatusLabel(String status) {
  switch (status) {
    case 'synced':
      return 'Tersinkron';
    case 'pending_insert':
      return 'Menunggu Sinkronisasi';
    case 'pending_update':
      return 'Menunggu Update';
    default:
      return status;
  }
}

/// Verification status color
Color getVerificationColor(String status) {
  switch (status) {
    case 'sesuai':
      return const Color(0xFF10B981);
    case 'pending':
      return const Color(0xFFF59E0B);
    case 'koreksi':
      return const Color(0xFFEF4444);
    default:
      return const Color(0xFF64748B);
  }
}

/// Verification status label
String getVerificationLabel(String status) {
  switch (status) {
    case 'sesuai':
      return 'Disetujui';
    case 'pending':
      return 'Pending';
    case 'koreksi':
      return 'Koreksi';
    default:
      return status;
  }
}
