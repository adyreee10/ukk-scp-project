import 'package:flutter/material.dart';
import '../core/theme/app_theme.dart';

class StatusBadge extends StatelessWidget {
  final String status;
  final double fontSize;
  final EdgeInsets padding;

  const StatusBadge({
    super.key,
    required this.status,
    this.fontSize = 11,
    this.padding = const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
  });

  @override
  Widget build(BuildContext context) {
    Color bgColor;
    Color textColor;
    Color borderColor;
    String label = status;

    switch (status.toLowerCase()) {
      case 'hadir':
      case 'approved':
      case 'disetujui':
      case 'aktif':
        bgColor = AppTheme.success.withValues(alpha: 0.15);
        textColor = const Color(0xFF34D399);
        borderColor = AppTheme.success.withValues(alpha: 0.4);
        if (status.toLowerCase() == 'approved') label = 'Disetujui';
        break;

      case 'sakit':
      case 'izin':
      case 'dispensasi':
      case 'pending':
        bgColor = AppTheme.warning.withValues(alpha: 0.15);
        textColor = const Color(0xFFFBBF24);
        borderColor = AppTheme.warning.withValues(alpha: 0.4);
        if (status.toLowerCase() == 'pending') label = 'Menunggu Verifikasi';
        break;

      case 'alfa':
      case 'rejected':
      case 'ditolak':
      case 'lulus':
        if (status.toLowerCase() == 'lulus') {
          bgColor = AppTheme.primaryIndigo.withValues(alpha: 0.15);
          textColor = const Color(0xFF818CF8);
          borderColor = AppTheme.primaryIndigo.withValues(alpha: 0.4);
          label = 'Alumni';
        } else {
          bgColor = AppTheme.danger.withValues(alpha: 0.15);
          textColor = const Color(0xFFFB7185);
          borderColor = AppTheme.danger.withValues(alpha: 0.4);
          if (status.toLowerCase() == 'rejected') label = 'Ditolak';
        }
        break;

      default:
        bgColor = AppTheme.borderDark.withValues(alpha: 0.3);
        textColor = AppTheme.textSecondary;
        borderColor = AppTheme.borderDark;
    }

    return Container(
      padding: padding,
      decoration: BoxDecoration(
        color: bgColor,
        borderRadius: BorderRadius.circular(20),
        border: Border.all(color: borderColor, width: 1),
      ),
      child: Text(
        label,
        style: TextStyle(
          color: textColor,
          fontSize: fontSize,
          fontWeight: FontWeight.bold,
          letterSpacing: 0.3,
        ),
      ),
    );
  }
}
