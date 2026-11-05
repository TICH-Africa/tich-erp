import 'package:flutter/material.dart';

import '../../data/api_client.dart';
import '../../data/tich_screen_cache.dart';
import '../widgets/tich_live_refresh.dart';
import '../widgets/tich_widgets.dart';

Map<String, dynamic>? portalReadCache(String key) =>
    TichScreenCache.read<Map<String, dynamic>>(key);

Widget portalLoadingBody({Future<void> Function()? onRefresh}) =>
    tichInstantWaitBody(onRefresh: onRefresh);

void portalWarm(
  String key,
  Future<Map<String, dynamic>> Function() fetch,
) {
  TichScreenCache.warm(key, () => fetch());
}

String portalDetailCacheKey(String namespace, int id) => '$namespace.$id';

Widget portalCachedMapBody({
  required AsyncSnapshot<Map<String, dynamic>> snap,
  required Object? memoryCache,
  required String persistKey,
  required Widget Function(Map<String, dynamic> data) builder,
  Future<void> Function()? onRetry,
}) {
  final merged = tichMergeSnapshot(
    snap,
    memoryCache as dynamic,
    persistKey: persistKey,
  );
  if (tichShowLoadingSpinner(snap, merged)) {
    return portalLoadingBody(onRefresh: onRetry);
  }
  if (merged == null && snap.hasError) {
    return TichErrorRefresh(
      message: tichDescribeError(snap.error),
      onRetry: onRetry ?? () async {},
    );
  }
  return builder(merged! as Map<String, dynamic>);
}

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
