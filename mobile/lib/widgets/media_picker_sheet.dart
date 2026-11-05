import 'dart:io';
import 'package:flutter/material.dart';
import 'package:image_picker/image_picker.dart';
import 'package:file_picker/file_picker.dart';
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
                  'Pilih Sumber Dokumen / Bukti',
                  style: TextStyle(
                    fontSize: 16,
                    fontWeight: FontWeight.bold,
                    color: AppTheme.textPrimary,
                  ),
                ),
                const SizedBox(height: 4),
                const Text(
                  'Gunakan kamera langsung atau pilih dari berkas perangkat',
                  style: TextStyle(
                    fontSize: 12,
                    color: AppTheme.textSecondary,
                  ),
                ),
                const SizedBox(height: 20),

                // Opsi 1: Kamera Realtime (Paling Utama Sesuai Request User)
                ListTile(
                  onTap: () async {
                    final XFile? photo = await picker.pickImage(
                      source: ImageSource.camera,
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
                    'Ambil Foto Realtime (Kamera)',
                    style: TextStyle(fontWeight: FontWeight.bold, color: AppTheme.textPrimary, fontSize: 14),
                  ),
                  subtitle: const Text(
                    'Jepret langsung bukti fisik/suasana KBM saat ini',
                    style: TextStyle(color: AppTheme.textSecondary, fontSize: 11),
                  ),
                ),

                const Divider(color: AppTheme.borderDark, height: 1),

                // Opsi 2: Galeri Foto
                ListTile(
                  onTap: () async {
                    final XFile? image = await picker.pickImage(
                      source: ImageSource.gallery,
                      imageQuality: 80,
                    );
                    if (ctx.mounted) {
                      Navigator.pop(ctx, image != null ? File(image.path) : null);
                    }
                  },
                  leading: Container(
                    padding: const EdgeInsets.all(10),
                    decoration: BoxDecoration(
                      color: AppTheme.success.withValues(alpha: 0.15),
                      borderRadius: BorderRadius.circular(12),
                    ),
                    child: const Icon(Icons.photo_library_rounded, color: AppTheme.success, size: 24),
                  ),
                  title: const Text(
                    'Pilih dari Galeri Foto',
                    style: TextStyle(fontWeight: FontWeight.bold, color: AppTheme.textPrimary, fontSize: 14),
                  ),
                  subtitle: const Text(
                    'Pilih gambar JPG/PNG yang sudah tersimpan',
                    style: TextStyle(color: AppTheme.textSecondary, fontSize: 11),
                  ),
                ),

                // Opsi 3: Dokumen PDF (Jika perizinan)
                if (allowPdf) ...[
                  const Divider(color: AppTheme.borderDark, height: 1),
                  ListTile(
                    onTap: () async {
                      final result = await FilePicker.pickFiles(
                        type: FileType.custom,
                        allowedExtensions: ['pdf', 'jpg', 'jpeg', 'png'],
                      );
                      if (ctx.mounted) {
                        Navigator.pop(
                          ctx,
                          result != null && result.files.single.path != null
                              ? File(result.files.single.path!)
                              : null,
                        );
                      }
                    },
                    leading: Container(
                      padding: const EdgeInsets.all(10),
                      decoration: BoxDecoration(
                        color: AppTheme.warning.withValues(alpha: 0.15),
                        borderRadius: BorderRadius.circular(12),
                      ),
                      child: const Icon(Icons.picture_as_pdf_rounded, color: AppTheme.warning, size: 24),
                    ),
                    title: const Text(
                      'Pilih Berkas Dokumen (PDF / Foto)',
                      style: TextStyle(fontWeight: FontWeight.bold, color: AppTheme.textPrimary, fontSize: 14),
                    ),
                    subtitle: const Text(
                      'Dokumen surat dokter atau keterangan resmi (PDF)',
                      style: TextStyle(color: AppTheme.textSecondary, fontSize: 11),
                    ),
                  ),
                ],

                const SizedBox(height: 12),
              ],
            ),
          ),
        );
      },
    );
  }
}
