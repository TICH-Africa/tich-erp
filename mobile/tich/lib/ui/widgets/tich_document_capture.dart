import 'package:cunning_document_scanner/cunning_document_scanner.dart';
import 'package:flutter/material.dart';
import 'package:image_picker/image_picker.dart';
import 'package:permission_handler/permission_handler.dart';

/// CamScanner-style capture: edge detection, crop, and contrast on the sheet.
Future<String?> captureScannedDocument(BuildContext context) async {
  final granted = await _ensureCamera(context);
  if (!granted) return null;

  try {
    final paths = await CunningDocumentScanner.getPictures(
      noOfPages: 1,
      scannerSource: ScannerSource.cameraAndGallery,
      androidScannerMode: AndroidScannerMode.full,
    );
    if (paths != null && paths.isNotEmpty) {
      return paths.first;
    }
  } catch (_) {
    // Fall back to a plain camera photo if the scanner UI is unavailable.
  }

  final file = await ImagePicker().pickImage(
    source: ImageSource.camera,
    preferredCameraDevice: CameraDevice.rear,
    imageQuality: 92,
  );
  return file?.path;
}

/// Regular rear-camera photo (class group shot).
Future<String?> captureClassPhoto(BuildContext context) async {
  final granted = await _ensureCamera(context);
  if (!granted) return null;

  final file = await ImagePicker().pickImage(
    source: ImageSource.camera,
    preferredCameraDevice: CameraDevice.rear,
    imageQuality: 88,
  );
  return file?.path;
}

Future<bool> _ensureCamera(BuildContext context) async {
  var status = await Permission.camera.status;
  if (!status.isGranted) {
    status = await Permission.camera.request();
  }
  if (status.isGranted) return true;
  if (context.mounted) {
    ScaffoldMessenger.of(context).showSnackBar(
      const SnackBar(
        content: Text('Camera permission is required to capture attendance photos.'),
      ),
    );
  }
  return false;
}
