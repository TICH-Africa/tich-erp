import 'package:flutter/foundation.dart';

import '../../data/tich_screen_cache.dart';
import '../widgets/tich_live_refresh.dart';

Map<String, dynamic>? portalReadCache(String key) =>
    TichScreenCache.read<Map<String, dynamic>>(key);

Future<Map<String, dynamic>> portalEnsure(
  String key,
  Future<Map<String, dynamic>> Function() fetch,
) =>
    TichScreenCache.ensure(key, fetch);

Future<void> portalCachedReload({
  required bool Function() mounted,
  required void Function(VoidCallback fn) setState,
  required Future<Map<String, dynamic>> Function() fetch,
  required void Function(Map<String, dynamic> data) onData,
  String? persistKey,
}) =>
    tichRefreshCached<Map<String, dynamic>>(
      mounted: mounted,
      setState: setState,
      fetch: fetch,
      onData: onData,
      persistKey: persistKey,
    );

void portalPrimeScreen({
  required String persistKey,
  required void Function(Object? cache) setCache,
  required void Function(Future<Map<String, dynamic>> future) setFuture,
  required Future<Map<String, dynamic>> Function() fetch,
}) {
  TichScreenCache.primeMapScreen(
    persistKey: persistKey,
    applyCache: setCache,
    applyFuture: setFuture,
    fetch: fetch,
  );
}
