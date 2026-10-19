import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';

import '../../data/api_client.dart';
import '../../data/employee_api.dart';
import '../../theme/tich_theme.dart';
import '../../adaptive/adaptive.dart';
import '../widgets/tich_widgets.dart';
import '../widgets/tich_live_refresh.dart';

class PoliciesPage extends StatefulWidget {
  const PoliciesPage({super.key, required this.api});

  final EmployeeApi api;

  @override
  State<PoliciesPage> createState() => _PoliciesPageState();
}

class _PoliciesPageState extends State<PoliciesPage> {
  late Future<Map<String, dynamic>> _future;
  Object? _dataCache;

  @override
  void initState() {
    super.initState();
    _future = widget.api.policies();
  }

  void _reload() => setState(() => _future = widget.api.policies());

  void _liveRefresh() {
    tichSilentReload(
      mounted: () => mounted,
      setState: setState,
      fetch: () => widget.api.policies(),
      onData: (data) => _dataCache = data,
    );
  }

  Future<void> _ack(Map<String, dynamic> p) async {
    final profile = await widget.api.profile();
    final staff = Map<String, dynamic>.from(profile['profile'] as Map? ?? {});
    try {
      await widget.api.acknowledgePolicy(
        (p['id'] as num).toInt(),
        fullName: staff['full_name']?.toString() ?? '',
        employeeNumber: staff['employee_number']?.toString() ?? '',
      );
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Policy acknowledged.')),
      );
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
      title: 'HR policies',
      body: FutureBuilder<Map<String, dynamic>>(
        future: _future,
        builder: (context, snap) {
          final merged = tichMergeSnapshot(snap, _dataCache as dynamic);
          if (tichShowLoadingSpinner(snap, merged)) {
            return const Center(child: AdaptiveProgress());
          }
          if (merged == null && snap.hasError) {
            return TichErrorRefresh(message: snap.error.toString(), onRetry: _reload);
          }
          final data = merged! as Map<String, dynamic>;
          final items = data['policies'] as List? ?? const [];
          if (items.isEmpty) {
            return const Center(child: TichEmpty('No assigned policies.'));
          }
          return RefreshIndicator(
            onRefresh: () async => _reload(),
            child: ListView.separated(
              padding: const EdgeInsets.all(16),
              itemCount: items.length,
              separatorBuilder: (_, __) => const SizedBox(height: 8),
              itemBuilder: (context, i) {
                final p = Map<String, dynamic>.from(items[i] as Map);
                final done = p['acknowledged'] == true;
                return TichCard(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Row(
                        children: [
                          Expanded(
                            child: Text(
                              p['title']?.toString() ?? 'Policy',
                              style: tichUi(fontWeight: FontWeight.w700),
                            ),
                          ),
                          TichStatusChip(
                            done ? 'acknowledged' : 'pending',
                            tone: done ? TichChipTone.success : TichChipTone.warning,
                          ),
                        ],
                      ),
                      if (p['description'] != null) ...[
                        const SizedBox(height: 6),
                        Text(p['description'].toString()),
                      ],
                      if (!done) ...[
                        const SizedBox(height: 10),
                        FilledButton(
                          onPressed: () => _ack(p),
                          child: const Text('Acknowledge'),
                        ),
                      ],
                    ],
                  ),
                );
              },
            ),
          );
        },
      ),
      ),
    );
  }
}
