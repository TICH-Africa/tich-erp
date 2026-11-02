import 'dart:convert';
import 'dart:io';

/// Compact JSON + optional gzip for background sync payloads.
class TichPayloadCodec {
  static const gzipMagic1 = 0x1f;
  static const gzipMagic2 = 0x8b;

  static String encodeJson(Map<String, dynamic> body) {
    return jsonEncode(body);
  }

  static List<int> gzipJson(Map<String, dynamic> body) {
    return gzip.encode(utf8.encode(encodeJson(body)));
  }

  static Map<String, dynamic> decodeJsonBytes(List<int> bytes) {
    if (bytes.length >= 2 &&
        bytes[0] == gzipMagic1 &&
        bytes[1] == gzipMagic2) {
      bytes = gzip.decode(bytes);
    }
    final decoded = jsonDecode(utf8.decode(bytes));
    if (decoded is Map<String, dynamic>) {
      return decoded;
    }
    if (decoded is Map) {
      return Map<String, dynamic>.from(decoded);
    }
    return {'data': decoded};
  }

  static Future<List<int>> readGzipFile(String path) async {
    return File(path).readAsBytes();
  }

  static Future<void> writeGzipFile(String path, Map<String, dynamic> body) async {
    final file = File(path);
    await file.parent.create(recursive: true);
    await file.writeAsBytes(gzipJson(body), flush: true);
  }
}
