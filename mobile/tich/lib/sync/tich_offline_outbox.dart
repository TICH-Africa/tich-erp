import 'dart:io';

import 'package:path_provider/path_provider.dart';
import 'package:uuid/uuid.dart';

import 'tich_payload_codec.dart';

/// Persistent queue for field operations when the network is unavailable.
class TichOfflineOutbox {
  TichOfflineOutbox._();
  static final TichOfflineOutbox instance = TichOfflineOutbox._();

  static const _fileName = 'field_outbox.json.gz';
  final _uuid = const Uuid();

  Future<File> _storeFile() async {
    final dir = await getApplicationDocumentsDirectory();
    return File('${dir.path}/$_fileName');
  }

  Future<Map<String, dynamic>> _readStore() async {
    final file = await _storeFile();
    if (!await file.exists()) {
      return {'operations': <Map<String, dynamic>>[], 'uploads': <Map<String, dynamic>>[]};
    }
    try {
      final bytes = await file.readAsBytes();
      if (bytes.isEmpty) {
        return {'operations': <Map<String, dynamic>>[], 'uploads': <Map<String, dynamic>>[]};
      }
      return TichPayloadCodec.decodeJsonBytes(bytes);
    } catch (_) {
      return {'operations': <Map<String, dynamic>>[], 'uploads': <Map<String, dynamic>>[]};
    }
  }

  Future<void> _writeStore(Map<String, dynamic> store) async {
    final file = await _storeFile();
    await TichPayloadCodec.writeGzipFile(file.path, store);
  }

  Future<int> pendingCount() async {
    final store = await _readStore();
    final ops = store['operations'] as List? ?? const [];
    final uploads = store['uploads'] as List? ?? const [];
    return ops.length + uploads.length;
  }

  Future<void> enqueueOperation({
    required String type,
    required Map<String, dynamic> payload,
    String? clientId,
  }) async {
    final store = await _readStore();
    final ops = List<Map<String, dynamic>>.from(
      (store['operations'] as List? ?? const []).map(
        (e) => Map<String, dynamic>.from(e as Map),
      ),
    );
    ops.add({
      'id': clientId ?? _uuid.v4(),
      'type': type,
      'payload': payload,
      'created_at': DateTime.now().toUtc().toIso8601String(),
    });
    store['operations'] = ops;
    await _writeStore(store);
  }

  Future<void> enqueueUpload({
    required String kind,
    required int sessionId,
    required String sourcePath,
    String? clientId,
  }) async {
    final persisted = await _persistUploadFile(sourcePath, clientId ?? _uuid.v4());
    final store = await _readStore();
    final uploads = List<Map<String, dynamic>>.from(
      (store['uploads'] as List? ?? const []).map(
        (e) => Map<String, dynamic>.from(e as Map),
      ),
    );
    uploads.add({
      'id': clientId ?? _uuid.v4(),
      'kind': kind,
      'session_id': sessionId,
      'path': persisted,
      'created_at': DateTime.now().toUtc().toIso8601String(),
    });
    store['uploads'] = uploads;
    await _writeStore(store);
  }

  Future<String> _persistUploadFile(String sourcePath, String id) async {
    final dir = await getApplicationDocumentsDirectory();
    final box = Directory('${dir.path}/tich_outbox_files');
    await box.create(recursive: true);
    final ext = sourcePath.contains('.') ? sourcePath.split('.').last : 'jpg';
    final target = File('${box.path}/$id.$ext');
    await File(sourcePath).copy(target.path);
    return target.path;
  }

  Future<({List<Map<String, dynamic>> operations, List<Map<String, dynamic>> uploads})>
      snapshot() async {
    final store = await _readStore();
    final ops = List<Map<String, dynamic>>.from(
      (store['operations'] as List? ?? const []).map(
        (e) => Map<String, dynamic>.from(e as Map),
      ),
    );
    final uploads = List<Map<String, dynamic>>.from(
      (store['uploads'] as List? ?? const []).map(
        (e) => Map<String, dynamic>.from(e as Map),
      ),
    );
    return (operations: ops, uploads: uploads);
  }

  Future<void> replaceAll({
    required List<Map<String, dynamic>> operations,
    required List<Map<String, dynamic>> uploads,
  }) async {
    await _writeStore({
      'operations': operations,
      'uploads': uploads,
    });
  }

  Future<void> removeOperationIds(Set<String> ids) async {
    if (ids.isEmpty) {
      return;
    }
    final snap = await snapshot();
    final ops = snap.operations.where((o) => !ids.contains(o['id']?.toString())).toList();
    await replaceAll(operations: ops, uploads: snap.uploads);
  }

  Future<void> removeUploadIds(Set<String> ids) async {
    if (ids.isEmpty) {
      return;
    }
    final snap = await snapshot();
    final kept = <Map<String, dynamic>>[];
    for (final upload in snap.uploads) {
      final id = upload['id']?.toString() ?? '';
      if (ids.contains(id)) {
        final path = upload['path']?.toString();
        if (path != null && path.isNotEmpty) {
          try {
            await File(path).delete();
          } catch (_) {}
        }
        continue;
      }
      kept.add(upload);
    }
    await replaceAll(operations: snap.operations, uploads: kept);
  }
}
