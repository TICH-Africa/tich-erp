import 'dart:async';

import 'package:flutter/foundation.dart';
import 'package:laravel_reverb/laravel_reverb.dart';

import '../config/api_config.dart';
import 'api_client.dart';
import 'auth_token_store.dart';
import 'tich_realtime_signals.dart';

/// Persistent WebSocket to Laravel Reverb (Pusher protocol).
///
/// Screen **data** (lists, forms, uploads) uses HTTPS via [ApiClient]. Push
/// updates (sidebar counts, future invalidate events) use this socket.
abstract final class TichRealtime {
  static Reverb? _reverb;
  static final List<Subscription> _subscriptions = [];
  static bool _connecting = false;

  static bool get isConnected => _reverb?.state == ReverbState.connected;

  static Future<void> connect(ApiClient api) async {
    if (_connecting || isConnected) return;
    _connecting = true;
    try {
      final config = await api.get('/realtime/config');
      if (config['enabled'] != true) return;

      final key = config['key']?.toString() ?? '';
      if (key.isEmpty) return;

      final host = config['host']?.toString() ?? Uri.parse(ApiConfig.baseUrl).host;
      final port = (config['port'] as num?)?.toInt() ?? 443;
      final useTls = config['use_tls'] == true;
      final eventName = config['event']?.toString() ?? 'sidebar.counts.updated';
      final channels = (config['channels'] as List? ?? const [])
          .map((e) => e.toString())
          .where((c) => c.isNotEmpty)
          .toList();
      if (channels.isEmpty) return;

      final tokenStore = AuthTokenStore();
      final authEndpoint = ApiConfig.uri('broadcasting/auth').toString();

      await disconnect();

      _reverb = Reverb(
        host: host,
        port: port,
        appKey: key,
        useTls: useTls,
        authEndpoint: authEndpoint,
        authHeaders: () async {
          final token = await tokenStore.read();
          if (token == null || token.isEmpty) {
            return const {'Accept': 'application/json'};
          }
          return {
            'Accept': 'application/json',
            'Authorization': 'Bearer $token',
          };
        },
        onError: (error, stack) {
          if (kDebugMode) {
            debugPrint('TichRealtime: $error');
          }
        },
      );

      await _reverb!.connect();

      for (final fullName in channels) {
        final privateName = fullName.startsWith('private-')
            ? fullName.substring('private-'.length)
            : fullName;
        final sub = _reverb!.private(privateName).listen(
          '.$eventName',
          (_) {
            TichRealtimeSignals.notifySidebarCountsUpdated();
            TichRealtimeSignals.notifyDataStale();
          },
        );
        _subscriptions.add(sub);
      }
    } catch (e) {
      if (kDebugMode) {
        debugPrint('TichRealtime connect failed: $e');
      }
    } finally {
      _connecting = false;
    }
  }

  static Future<void> disconnect() async {
    _connecting = false;
    for (final sub in _subscriptions) {
      sub.cancel();
    }
    _subscriptions.clear();
    final client = _reverb;
    _reverb = null;
    if (client != null) {
      await client.disconnect();
    }
  }
}
