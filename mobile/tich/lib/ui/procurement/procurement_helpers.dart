import 'dart:async';

import 'package:flutter/material.dart';

import '../../data/api_client.dart';
import '../../data/tich_screen_cache.dart';
import '../shell/procurement_nav_signals.dart';
import '../widgets/tich_live_refresh.dart';
import 'procurement_detail_widgets.dart';

export 'procurement_detail_widgets.dart';

void procurementNotifyCountsChanged() {
  ProcurementPortalNavSignals.onCountsChanged?.call();
}

/// Refreshes procurement data without swapping the [FutureBuilder] future (no flicker).
void procurementUpdateFuture(
  void Function(VoidCallback fn) setState,
  void Function(Future<Map<String, dynamic>> future) assign,
  Future<Map<String, dynamic>> Function() fetch, {
  required bool Function() mounted,
  required void Function(Map<String, dynamic> data) onData,
  String? persistKey,
}) {
  procurementSilentLoad(
    mounted: mounted,
    setState: setState,
    fetch: fetch,
    onData: onData,
    persistKey: persistKey,
  );
}

Widget procurementLoadingBody({Future<void> Function()? onRefresh}) =>
    tichInstantWaitBody(onRefresh: onRefresh);

Future<void> procurementCachedReload({
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

void procurementSilentLoad({
  required bool Function() mounted,
  required void Function(VoidCallback fn) setState,
  required Future<Map<String, dynamic>> Function() fetch,
  required void Function(Map<String, dynamic> data) onData,
  String? persistKey,
}) {
  tichSilentReload<Map<String, dynamic>>(
    mounted: mounted,
    setState: setState,
    fetch: fetch,
    onData: onData,
    persistKey: persistKey,
  );
}

Map<String, dynamic>? procurementReadCache(String key) =>
    TichScreenCache.read<Map<String, dynamic>>(key);

Future<Map<String, dynamic>> procurementEnsure(
  String key,
  Future<Map<String, dynamic>> Function() fetch,
) =>
    TichScreenCache.ensure(key, fetch);

void procurementPrimeScreen({
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

void procurementShowError(BuildContext context, Object error) {
  ScaffoldMessenger.of(context).showSnackBar(
    SnackBar(content: Text(tichDescribeError(error))),
  );
}

Future<String?> procurementPromptText(
  BuildContext context, {
  required String title,
  required String hint,
  bool required = true,
}) async {
  final controller = TextEditingController();
  final result = await showDialog<String>(
    context: context,
    builder: (ctx) => AlertDialog(
      title: Text(title),
      content: TextField(
        controller: controller,
        maxLines: 3,
        decoration: InputDecoration(hintText: hint),
      ),
      actions: [
        TextButton(
          onPressed: () => Navigator.pop(ctx),
          child: const Text('Cancel'),
        ),
        FilledButton(
          onPressed: () {
            final text = controller.text.trim();
            if (required && text.isEmpty) return;
            Navigator.pop(ctx, text);
          },
          child: const Text('OK'),
        ),
      ],
    ),
  );
  controller.dispose();
  return result;
}

Widget procurementDetailRow(String label, dynamic value) =>
    procurementDetailField(label, value);
