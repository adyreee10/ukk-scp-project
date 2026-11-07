import 'dart:io';
import 'package:flutter/material.dart';
import 'package:image_picker/image_picker.dart';
import '../core/theme/app_theme.dart';

class MediaPickerSheet {
  static Future<File?> show(BuildContext context, {bool allowPdf = false}) async {
    final ImagePicker picker = ImagePicker();

    return showModalBottomSheet<File?>(
      context: context,
      backgroundColor: AppTheme.surfaceDark,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(24)),
      ),
      builder: (BuildContext ctx) {
        return SafeArea(
          child: Padding(
            padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 16),
            child: Column(
              mainAxisSize: MainAxisSize.min,
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Center(
                  child: Container(
                    width: 40,
                    height: 4,
                    decoration: BoxDecoration(
                      color: AppTheme.borderDark,
                      borderRadius: BorderRadius.circular(2),
                    ),
                  ),
                ),
                const SizedBox(height: 16),
                const Text(
                  'Ambil Foto Bukti Real-Time',
                  style: TextStyle(
                    fontSize: 16,
                    fontWeight: FontWeight.bold,
                    color: AppTheme.textPrimary,
                  ),
                ),
                const SizedBox(height: 4),
                const Text(
                  'Wajib memotret langsung via kamera. Lampiran dari galeri/berkas tidak diperbolehkan.',
                  style: TextStyle(
                    fontSize: 12,
                    color: AppTheme.textSecondary,
                  ),
                ),
                const SizedBox(height: 20),

                // Opsi Kamera Realtime
                ListTile(
                  onTap: () async {
                    final XFile? photo = await picker.pickImage(
                      source: ImageSource.camera,
                      preferredCameraDevice: CameraDevice.rear,
                      imageQuality: 80,
                      maxWidth: 1920,
                      maxHeight: 1080,
                    );
                    if (ctx.mounted) {
                      Navigator.pop(ctx, photo != null ? File(photo.path) : null);
                    }
                  },
                  leading: Container(
                    padding: const EdgeInsets.all(10),
                    decoration: BoxDecoration(
                      color: AppTheme.primaryIndigo.withValues(alpha: 0.15),
                      borderRadius: BorderRadius.circular(12),
                    ),
                    child: const Icon(Icons.camera_alt_rounded, color: AppTheme.primaryIndigo, size: 24),
                  ),
                  title: const Text(
                    'Buka Kamera Sekarang',
                    style: TextStyle(fontWeight: FontWeight.bold, color: AppTheme.textPrimary, fontSize: 14),
                  ),
                  subtitle: const Text(
                    'Jepret langsung bukti fisik saat ini juga',
                    style: TextStyle(color: AppTheme.textSecondary, fontSize: 11),
                  ),
                ),
                const SizedBox(height: 12),
              ],
            ),
          ),
        );
      },
    );
  }
}
