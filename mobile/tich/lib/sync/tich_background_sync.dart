import 'dart:async';

import 'package:flutter/foundation.dart';

import '../data/api_client.dart';
import '../data/staff_api.dart';
import 'tich_network_errors.dart';
import 'tich_offline_outbox.dart';
import 'tich_upload_optimizer.dart';

const tichFieldSyncTask = 'tich.field.sync';

/// Flushes compressed offline attendance/grade operations and queued uploads.
class TichBackgroundSync {
  TichBackgroundSync._();
  static final TichBackgroundSync instance = TichBackgroundSync._();

  Future<void>? _inflight;
  final _pendingController = StreamController<int>.broadcast();

  Stream<int> get pendingCountStream => _pendingController.stream;

  Future<void> notifyPendingChanged() async {
    final count = await TichOfflineOutbox.instance.pendingCount();
    if (!_pendingController.isClosed) {
      _pendingController.add(count);
    }
  }

  Future<void> tryFlush(ApiClient client, {bool force = false}) {
    if (_inflight != null) {
      return _inflight!;
    }
    _inflight = _flush(client, force: force).whenComplete(() {
      _inflight = null;
    });
    return _inflight!;
  }

  Future<void> _flush(ApiClient client, {required bool force}) async {
    final outbox = TichOfflineOutbox.instance;
    final snap = await outbox.snapshot();
    if (snap.operations.isEmpty && snap.uploads.isEmpty) {
      await notifyPendingChanged();
      return;
    }

    final staffApi = StaffApi(client);
    final applied = <String>{};
    final failed = <String>{};

    if (snap.operations.isNotEmpty) {
      try {
        final result = await staffApi.syncFieldOperations(
          snap.operations
              .map(
                (o) => {
                  'id': o['id'],
                  'type': o['type'],
                  'payload': o['payload'],
                },
              )
              .toList(),
        );
        for (final id in (result['applied'] as List? ?? const [])) {
          applied.add(id.toString());
        }
        for (final raw in (result['failed'] as List? ?? const [])) {
          if (raw is Map) {
            failed.add(raw['id']?.toString() ?? '');
          }
        }
        for (final id in (result['skipped'] as List? ?? const [])) {
          applied.add(id.toString());
        }
      } catch (e) {
        if (!force && tichIsTransientNetworkError(e)) {
          await notifyPendingChanged();
          return;
        }
        if (kDebugMode) {
          debugPrint('Field sync batch failed: $e');
        }
      }
    }

    final remainingOps = snap.operations
        .where((o) => !applied.contains(o['id']?.toString()))
        .where((o) => !failed.contains(o['id']?.toString()))
        .toList();
    await outbox.replaceAll(operations: remainingOps, uploads: snap.uploads);

    final uploadApplied = <String>{};
    final uploadSnap = await outbox.snapshot();
    for (final upload in uploadSnap.uploads) {
      final id = upload['id']?.toString() ?? '';
      final sessionId = (upload['session_id'] as num?)?.toInt();
      final path = upload['path']?.toString();
      final kind = upload['kind']?.toString() ?? '';
      if (sessionId == null || path == null || path.isEmpty) {
        uploadApplied.add(id);
        continue;
      }
      try {
        final optimized = await TichUploadOptimizer.prepareUploadPath(path);
        if (kind == 'attendance.sheet') {
          await staffApi.uploadSignedSheet(sessionId, optimized);
        } else if (kind == 'attendance.class_photo') {
          await staffApi.uploadClassPhoto(sessionId, optimized);
        } else {
          uploadApplied.add(id);
          continue;
        }
        uploadApplied.add(id);
      } catch (e) {
        if (tichIsTransientNetworkError(e)) {
          break;
        }
        uploadApplied.add(id);
      }
    }

    await outbox.removeUploadIds(uploadApplied);
    await outbox.removeOperationIds(applied);
    await notifyPendingChanged();
  }

  Future<void> enqueueOrRunAttendanceSave(
    StaffApi api,
    ApiClient client, {
    required int sessionId,
    required List<int> presentStudentIds,
    required bool lock,
  }) async {
    final payload = {
      'session_id': sessionId,
      'present': presentStudentIds,
      'lock': lock,
    };
    try {
      await api.saveAttendance(
        sessionId,
        presentStudentIds: presentStudentIds,
        lock: lock,
      );
    } catch (e) {
      if (!tichIsTransientNetworkError(e)) {
        rethrow;
      }
      await TichOfflineOutbox.instance.enqueueOperation(
        type: 'attendance.save',
        payload: payload,
      );
      await notifyPendingChanged();
      unawaited(tryFlush(client));
      throw const TichQueuedOfflineException();
    }
  }

  Future<void> enqueueOrRunCat(
    StaffApi api,
    ApiClient client, {
    required Map<String, dynamic> body,
  }) async {
    try {
      await api.recordCat(body);
    } catch (e) {
      if (!tichIsTransientNetworkError(e)) {
        rethrow;
      }
      await TichOfflineOutbox.instance.enqueueOperation(
        type: 'grading.cat',
        payload: body,
      );
      await notifyPendingChanged();
      unawaited(tryFlush(client));
      throw const TichQueuedOfflineException();
    }
  }

  Future<void> enqueueOrRunSheetUpload(
    StaffApi api,
    ApiClient client, {
    required int sessionId,
    required String filePath,
  }) async {
    try {
      final optimized = await TichUploadOptimizer.prepareUploadPath(filePath);
      await api.uploadSignedSheet(sessionId, optimized);
    } catch (e) {
      if (!tichIsTransientNetworkError(e)) {
        rethrow;
      }
      await TichOfflineOutbox.instance.enqueueUpload(
        kind: 'attendance.sheet',
        sessionId: sessionId,
        sourcePath: filePath,
      );
      await notifyPendingChanged();
      unawaited(tryFlush(client));
      throw const TichQueuedOfflineException();
    }
  }

  Future<void> enqueueOrRunPhotoUpload(
    StaffApi api,
    ApiClient client, {
    required int sessionId,
    required String filePath,
  }) async {
    try {
      final optimized = await TichUploadOptimizer.prepareUploadPath(filePath);
      await api.uploadClassPhoto(sessionId, optimized);
    } catch (e) {
      if (!tichIsTransientNetworkError(e)) {
        rethrow;
      }
      await TichOfflineOutbox.instance.enqueueUpload(
        kind: 'attendance.class_photo',
        sessionId: sessionId,
        sourcePath: filePath,
      );
      await notifyPendingChanged();
      unawaited(tryFlush(client));
      throw const TichQueuedOfflineException();
    }
  }
}

class TichQueuedOfflineException implements Exception {
  const TichQueuedOfflineException();
}

String tichQueuedOfflineMessage() =>
    'Saved offline. It will upload automatically when you are back online.';
