import 'dart:async';
import 'dart:io';

import 'package:path_provider/path_provider.dart';

/// In-memory + temp-disk cache for generated PDF bytes (timetables, exam cards).
abstract final class TichPdfCache {
  static const _maxMemoryEntries = 4;
  static final Map<String, List<int>> _memory = {};
  static final List<String> _lru = [];
  static final Map<String, Future<List<int>>> _inFlight = {};

  static Future<List<int>> load(
    String key,
    Future<List<int>> Function() fetch,
  ) async {
    if (key.isEmpty) {
      return fetch();
    }

    final mem = _memory[key];
    if (mem != null && mem.isNotEmpty) {
      _touch(key);
      return mem;
    }

    final disk = await _readDisk(key);
    if (disk != null && disk.isNotEmpty) {
      _storeMemory(key, disk);
      return disk;
    }

    final existing = _inFlight[key];
    if (existing != null) {
      return existing;
    }

    final future = fetch().then((bytes) async {
      if (bytes.isNotEmpty) {
        _storeMemory(key, bytes);
        await _writeDisk(key, bytes);
      }
      return bytes;
    }).whenComplete(() => _inFlight.remove(key));

    _inFlight[key] = future;
    return future;
  }

  static void _touch(String key) {
    _lru.remove(key);
    _lru.add(key);
  }

  static void _storeMemory(String key, List<int> bytes) {
    _memory[key] = bytes;
    _touch(key);
    while (_lru.length > _maxMemoryEntries) {
      final evict = _lru.removeAt(0);
      _memory.remove(evict);
    }
  }

  static Future<File?> _fileForKey(String key) async {
    final dir = await getTemporaryDirectory();
    final safe = key.replaceAll(RegExp(r'[^a-zA-Z0-9._-]'), '_');
    return File('${dir.path}/tich-pdf-$safe.pdf');
  }

  static Future<List<int>?> _readDisk(String key) async {
    try {
      final file = await _fileForKey(key);
      if (file == null || !await file.exists()) return null;
      return await file.readAsBytes();
    } catch (_) {
      return null;
    }
  }

  static Future<void> _writeDisk(String key, List<int> bytes) async {
    try {
      final file = await _fileForKey(key);
      if (file == null) return;
      await file.writeAsBytes(bytes, flush: true);
    } catch (_) {}
  }
}
