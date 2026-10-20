import 'package:flutter/material.dart';
import 'package:flutter/services.dart';

import '../../adaptive/adaptive.dart';
import '../../data/api_client.dart';
import '../../data/procurement_api.dart';
import '../../theme/tich_theme.dart';
import '../widgets/tich_widgets.dart';
import 'procurement_helpers.dart';

class ProcurementRaiseStockIssuePage extends StatefulWidget {
  const ProcurementRaiseStockIssuePage({super.key, required this.api});

  final ProcurementApi api;

  @override
  State<ProcurementRaiseStockIssuePage> createState() =>
      _ProcurementRaiseStockIssuePageState();
}

class _ProcurementRaiseStockIssuePageState
    extends State<ProcurementRaiseStockIssuePage> {
  final _formKey = GlobalKey<FormState>();
  final _unitCost = TextEditingController();
  final _quantity = TextEditingController();
  final _reason = TextEditingController();

  late Future<Map<String, dynamic>> _optionsFuture;
  int? _itemId;
  int? _departmentId;
  int? _staffId;
  bool _busy = false;

  @override
  void initState() {
    super.initState();
    _optionsFuture = widget.api.stockIssueFormOptions();
  }

  @override
  void dispose() {
    _unitCost.dispose();
    _quantity.dispose();
    _reason.dispose();
    super.dispose();
  }

  void _onItemSelected(int? id, List<Map<String, dynamic>> items) {
    setState(() {
      _itemId = id;
      if (id == null) return;
      for (final row in items) {
        if ((row['id'] as num).toInt() == id) {
          final cost = row['unit_cost'];
          if (cost is num) {
            _unitCost.text = cost.toString();
          }
          break;
        }
      }
    });
  }

  Future<void> _submit() async {
    if (!_formKey.currentState!.validate()) return;
    if (_itemId == null || _departmentId == null || _staffId == null) {
      procurementShowError(context, 'Select item, department, and requester.');
      return;
    }
    final qty = double.tryParse(_quantity.text.trim());
    final cost = double.tryParse(_unitCost.text.trim());
    if (qty == null || qty <= 0 || cost == null || cost < 0) {
      procurementShowError(context, 'Enter valid quantity and unit cost.');
      return;
    }

    setState(() => _busy = true);
    try {
      final res = await widget.api.createStockIssue(
        inventoryItemId: _itemId!,
        departmentId: _departmentId!,
        requestedBy: _staffId!,
        quantity: qty,
        unitCost: cost,
        reason: _reason.text.trim(),
      );
      if (!mounted) return;
      procurementNotifyCountsChanged();
      final item = res['item'] as Map?;
      final id = (item?['id'] as num?)?.toInt();
      Navigator.pop(context, id);
    } catch (e) {
      if (mounted) procurementShowError(context, e);
    } finally {
      if (mounted) setState(() => _busy = false);
    }
  }

  Widget _sectionTitle(String title) {
    return Container(
      width: double.infinity,
      padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
      decoration: BoxDecoration(
        color: TichColors.blueDark,
        borderRadius: BorderRadius.circular(6),
      ),
      child: Text(
        title.toUpperCase(),
        style: tichUi(
          fontSize: 12,
          fontWeight: FontWeight.w700,
          letterSpacing: 0.6,
          color: TichColors.white,
        ),
      ),
    );
  }

  Widget _dropdown({
    required String label,
    required int? value,
    required List<Map<String, dynamic>> options,
    required String labelKey,
    required ValueChanged<int?> onChanged,
  }) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 12),
      child: DropdownButtonFormField<int>(
        value: value,
        isExpanded: true,
        decoration: InputDecoration(labelText: '$label *'),
        items: [
          DropdownMenuItem<int>(
            value: null,
            child: Text(
              'Select ${label.toLowerCase()}',
              style: tichUi(color: TichColors.neutralMuted),
            ),
          ),
          ...options.map((row) {
            final id = (row['id'] as num).toInt();
            final text = (row[labelKey] ?? row['name'] ?? row['label'] ?? '')
                .toString();
            return DropdownMenuItem<int>(
              value: id,
              child: Text(text, overflow: TextOverflow.ellipsis),
            );
          }),
        ],
        onChanged: onChanged,
        validator: (_) => value == null ? 'Required' : null,
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return AdaptiveScaffold(
      title: 'Raise stock issue',
      body: FutureBuilder<Map<String, dynamic>>(
        future: _optionsFuture,
        builder: (context, snap) {
          if (snap.connectionState != ConnectionState.done) {
            return const Center(child: AdaptiveProgress());
          }
          if (snap.hasError) {
            return Center(
              child: Column(
                mainAxisSize: MainAxisSize.min,
                children: [
                  Text(tichDescribeError(snap.error)),
                  TextButton(
                    onPressed: () {
                      setState(() {
                        _optionsFuture = widget.api.stockIssueFormOptions();
                      });
                    },
                    child: const Text('Retry'),
                  ),
                ],
              ),
            );
          }
          final data = snap.data ?? {};
          final items = (data['items'] as List? ?? [])
              .map((e) => Map<String, dynamic>.from(e as Map))
              .toList();
          final departments = (data['departments'] as List? ?? [])
              .map((e) => Map<String, dynamic>.from(e as Map))
              .toList();
          final staff = (data['staff'] as List? ?? [])
              .map((e) => Map<String, dynamic>.from(e as Map))
              .toList();

          return Form(
            key: _formKey,
            child: ListView(
              padding: const EdgeInsets.all(12),
              children: [
                TichCard(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.stretch,
                    children: [
                      Text(
                        'STK · Stock issue',
                        style: tichUi(
                          fontSize: 12,
                          color: TichColors.neutralMuted,
                          letterSpacing: 0.4,
                        ),
                      ),
                      const SizedBox(height: 4),
                      Text(
                        'HOD approval required',
                        style: TichTheme.display(fontSize: 18),
                      ),
                    ],
                  ),
                ),
                const SizedBox(height: 12),
                TichCard(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.stretch,
                    children: [
                      _sectionTitle('Item & requester'),
                      const SizedBox(height: 12),
                      _dropdown(
                        label: 'Item',
                        value: _itemId,
                        options: items,
                        labelKey: 'label',
                        onChanged: (v) => _onItemSelected(v, items),
                      ),
                      _dropdown(
                        label: 'Department',
                        value: _departmentId,
                        options: departments,
                        labelKey: 'name',
                        onChanged: (v) => setState(() => _departmentId = v),
                      ),
                      _dropdown(
                        label: 'Requested by',
                        value: _staffId,
                        options: staff,
                        labelKey: 'name',
                        onChanged: (v) => setState(() => _staffId = v),
                      ),
                    ],
                  ),
                ),
                const SizedBox(height: 12),
                TichCard(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.stretch,
                    children: [
                      _sectionTitle('Quantity & cost'),
                      const SizedBox(height: 12),
                      TextFormField(
                        controller: _unitCost,
                        keyboardType: const TextInputType.numberWithOptions(
                          decimal: true,
                        ),
                        inputFormatters: [
                          FilteringTextInputFormatter.allow(
                            RegExp(r'^\d*\.?\d{0,2}'),
                          ),
                        ],
                        decoration: const InputDecoration(labelText: 'Unit cost *'),
                        validator: (v) {
                          if (v == null || v.trim().isEmpty) return 'Required';
                          if (double.tryParse(v.trim()) == null) {
                            return 'Invalid amount';
                          }
                          return null;
                        },
                      ),
                      const SizedBox(height: 12),
                      TextFormField(
                        controller: _quantity,
                        keyboardType: const TextInputType.numberWithOptions(
                          decimal: true,
                        ),
                        inputFormatters: [
                          FilteringTextInputFormatter.allow(
                            RegExp(r'^\d*\.?\d{0,4}'),
                          ),
                        ],
                        decoration: const InputDecoration(labelText: 'Quantity *'),
                        validator: (v) {
                          if (v == null || v.trim().isEmpty) return 'Required';
                          final n = double.tryParse(v.trim());
                          if (n == null || n <= 0) return 'Must be greater than 0';
                          return null;
                        },
                      ),
                    ],
                  ),
                ),
                const SizedBox(height: 12),
                TichCard(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.stretch,
                    children: [
                      _sectionTitle('Reason & submit'),
                      const SizedBox(height: 12),
                      TextFormField(
                        controller: _reason,
                        minLines: 3,
                        maxLines: 5,
                        decoration: const InputDecoration(
                          labelText: 'Reason *',
                          alignLabelWithHint: true,
                        ),
                        validator: (v) =>
                            (v == null || v.trim().isEmpty) ? 'Required' : null,
                      ),
                      const SizedBox(height: 16),
                      FilledButton(
                        onPressed: _busy ? null : _submit,
                        child: _busy
                            ? const SizedBox(
                                height: 22,
                                width: 22,
                                child: CircularProgressIndicator(strokeWidth: 2),
                              )
                            : const Text('Raise issue'),
                      ),
                      const SizedBox(height: 8),
                      OutlinedButton(
                        onPressed: _busy ? null : () => Navigator.pop(context),
                        child: const Text('Cancel'),
                      ),
                    ],
                  ),
                ),
                const SizedBox(height: 8),
                Text(
                  '* Required field',
                  style: tichUi(fontSize: 12, color: TichColors.neutralMuted),
                ),
              ],
            ),
          );
        },
      ),
    );
  }
}
