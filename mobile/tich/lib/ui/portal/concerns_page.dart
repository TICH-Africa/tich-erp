import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';

import '../../data/api_client.dart';
import '../../data/employee_api.dart';
import '../../theme/tich_theme.dart';
import '../../adaptive/adaptive.dart';
import '../shell/portal_shell.dart';
import '../widgets/tich_widgets.dart';
import '../widgets/tich_live_refresh.dart';

class ConcernsPage extends StatefulWidget {
  const ConcernsPage({super.key, required this.api});

  final EmployeeApi api;

  @override
  State<ConcernsPage> createState() => _ConcernsPageState();
}

class _ConcernsPageState extends State<ConcernsPage> {
  late Future<Map<String, dynamic>> _future;

  @override
  void initState() {
    super.initState();
    _future = widget.api.concerns();
  }

  void _reload() => setState(() => _future = widget.api.concerns());

  void _liveRefresh() {
    tichSilentReload(
      mounted: () => mounted,
      setState: setState,
      fetch: () => widget.api.concerns(),
      apply: (f) => _future = f,
    );
  }

  Future<void> _raise(Map<String, dynamic> data) async {
    final categories = Map<String, dynamic>.from(data['categories'] as Map? ?? {});
    String? category = categories.keys.firstOrNull;
    final subject = TextEditingController();
    final description = TextEditingController();
    final ok = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        title: const Text('Raise a concern'),
        content: SingleChildScrollView(
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              DropdownButtonFormField<String>(
                value: category,
                items: categories.entries
                    .map((e) => DropdownMenuItem(value: e.key, child: Text('${e.value}')))
                    .toList(),
                onChanged: (v) => category = v,
                decoration: const InputDecoration(labelText: 'Category'),
              ),
              TextField(controller: subject, decoration: const InputDecoration(labelText: 'Subject')),
              TextField(controller: description, maxLines: 4, decoration: const InputDecoration(labelText: 'Description')),
            ],
          ),
        ),
        actions: [
          TextButton(onPressed: () => Navigator.pop(ctx, false), child: const Text('Cancel')),
          FilledButton(onPressed: () => Navigator.pop(ctx, true), child: const Text('Submit')),
        ],
      ),
    );
    if (ok != true || category == null) return;
    try {
      await widget.api.submitConcern({
        'concern_category': category,
        'subject': subject.text.trim(),
        'description': description.text.trim(),
      });
      _reload();
    } on ApiException catch (e) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(e.message)));
    }
  }

  @override
  Widget build(BuildContext context) {
    return TichLiveRefreshBinder(
      onLiveRefresh: _liveRefresh,
      child: AdaptiveScaffold(
      title: 'Concerns',
      floatingActionButton: FutureBuilder(
        future: _future,
        builder: (context, snap) {
          if (!snap.hasData) return const SizedBox.shrink();
          return FloatingActionButton.extended(
            onPressed: () => _raise(snap.data!),
            icon: const Icon(Icons.add),
            label: const Text('Raise'),
          );
        },
      ),
      body: FutureBuilder<Map<String, dynamic>>(
        future: _future,
        builder: (context, snap) {
          if (snap.connectionState != ConnectionState.done) {
            return const Center(child: AdaptiveProgress());
          }
          if (snap.hasError) {
            return TichErrorRefresh(message: snap.error.toString(), onRetry: _reload);
          }
          final items = snap.data!['concerns'] as List? ?? const [];
          return RefreshIndicator(
            onRefresh: () async => _reload(),
            child: ListView(
              padding: const EdgeInsets.fromLTRB(16, 16, 16, 88),
              children: [
                if (items.isEmpty)
                  const TichCard(child: TichEmpty('No concerns filed.'))
                else
                  ...items.map((raw) {
                    final c = Map<String, dynamic>.from(raw as Map);
                    return Padding(
                      padding: const EdgeInsets.only(bottom: 8),
                      child: TichCard(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(c['subject']?.toString() ?? '', style: tichUi(fontWeight: FontWeight.w700)),
                            const SizedBox(height: 4),
                            TichStatusChip(c['status']?.toString() ?? '', tone: toneForStatus(c['status']?.toString())),
                          ],
                        ),
                      ),
                    );
                  }),
              ],
            ),
          );
        },
      ),
      ),
    );
  }
}
