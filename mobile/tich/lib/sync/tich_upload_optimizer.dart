import 'dart:io';

import 'package:flutter/foundation.dart';
import 'package:flutter_image_compress/flutter_image_compress.dart';
import 'package:path_provider/path_provider.dart';

/// Shrinks photos before multipart upload (attendance sheets, certificates, etc.).
class TichUploadOptimizer {
  static const maxPhotoEdge = 2048;
  static const jpegQuality = 78;

  static Future<String> prepareUploadPath(String sourcePath) async {
    if (kIsWeb) {
      return sourcePath;
    }

    final ext = _ext(sourcePath);
    if (!_isRasterImage(ext)) {
      return sourcePath;
    }

    final source = File(sourcePath);
    if (!await source.exists()) {
      return sourcePath;
    }

    final originalSize = await source.length();
    if (originalSize < 350 * 1024 && ext != 'png') {
      return sourcePath;
    }

    final dir = await getTemporaryDirectory();
    final target = '${dir.path}/tich_up_${DateTime.now().millisecondsSinceEpoch}.jpg';

    try {
      final result = await FlutterImageCompress.compressAndGetFile(
        sourcePath,
        target,
        quality: jpegQuality,
        minWidth: maxPhotoEdge,
        minHeight: maxPhotoEdge,
        format: CompressFormat.jpeg,
      );
      if (result == null) {
        return sourcePath;
      }
      final out = File(result.path);
      if (!await out.exists()) {
        return sourcePath;
      }
      final compressedSize = await out.length();
      if (compressedSize >= originalSize) {
        await out.delete().catchError((_) => out);
        return sourcePath;
      }
      return result.path;
    } catch (_) {
      return sourcePath;
    }
  }

  static String _ext(String path) {
    final dot = path.lastIndexOf('.');
    if (dot < 0) {
      return '';
    }
    return path.substring(dot + 1).toLowerCase();
  }

  static bool _isRasterImage(String ext) {
    return ext == 'jpg' ||
        ext == 'jpeg' ||
        ext == 'png' ||
        ext == 'webp' ||
        ext == 'heic';
  }
}
