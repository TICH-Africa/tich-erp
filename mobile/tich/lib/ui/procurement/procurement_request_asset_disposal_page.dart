import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:intl/intl.dart';

import '../../adaptive/adaptive.dart';
import '../../data/api_client.dart';
import '../../data/procurement_api.dart';
import '../../theme/tich_theme.dart';
import '../widgets/tich_widgets.dart';
import 'procurement_helpers.dart';

class ProcurementRequestAssetDisposalPage extends StatefulWidget {
  const ProcurementRequestAssetDisposalPage({super.key, required this.api});

  final ProcurementApi api;

  @override
  State<ProcurementRequestAssetDisposalPage> createState() =>
      _ProcurementRequestAssetDisposalPageState();
}

class _ProcurementRequestAssetDisposalPageState
    extends State<ProcurementRequestAssetDisposalPage> {
  final _formKey = GlobalKey<FormState>();
  final _disposedValue = TextEditingController();
  final _disposalDateDisplay = TextEditingController();
  final _reason = TextEditingController();

  late Future<Map<String, dynamic>> _optionsFuture;
  int? _assetId;
  String _disposalType = 'write_off';
  DateTime? _disposalDate;
  bool _busy = false;

  static final _dateFormat = DateFormat('yyyy-MM-dd');
  static final _displayDate = DateFormat('MM/dd/yyyy');

  @override
  void initState() {
    super.initState();
    _optionsFuture = widget.api.assetDisposalFormOptions();
  }

  @override
  void dispose() {
    _disposedValue.dispose();
    _disposalDateDisplay.dispose();
    _reason.dispose();
    super.dispose();
  }

  void _onAssetSelected(int? id, List<Map<String, dynamic>> assets) {
    setState(() {
      _assetId = id;
      if (id == null) return;
      for (final row in assets) {
        if ((row['id'] as num).toInt() == id) {
          final value = row['current_value'];
          if (value is num) {
            _disposedValue.text = value.toStringAsFixed(2);
          }
          break;
        }
      }
    });
  }

  Future<void> _pickDate() async {
    final now = DateTime.now();
    final picked = await showDatePicker(
      context: context,
      initialDate: _disposalDate ?? now,
      firstDate: DateTime(now.year - 10),
      lastDate: DateTime(now.year + 5),
    );
    if (picked != null) {
      setState(() {
        _disposalDate = picked;
        _disposalDateDisplay.text = _displayDate.format(picked);
      });
    }
  }

  Future<void> _submit() async {
    if (!_formKey.currentState!.validate()) return;
    if (_assetId == null) {
      procurementShowError(context, 'Select an asset.');
      return;
    }

    double? value;
    final valueText = _disposedValue.text.trim();
    if (valueText.isNotEmpty) {
      value = double.tryParse(valueText);
      if (value == null || value < 0) {
        procurementShowError(context, 'Enter a valid disposed value.');
        return;
      }
    }

    setState(() => _busy = true);
    try {
      final res = await widget.api.createAssetDisposal(
        assetId: _assetId!,
        disposalType: _disposalType,
        disposalValue: value,
        disposalDate:
            _disposalDate != null ? _dateFormat.format(_disposalDate!) : null,
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

  @override
  Widget build(BuildContext context) {
    return AdaptiveScaffold(
      title: 'Request disposal',
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
                        _optionsFuture = widget.api.assetDisposalFormOptions();
                      });
                    },
                    child: const Text('Retry'),
                  ),
                ],
              ),
            );
          }

          final data = snap.data ?? {};
          final assets = (data['assets'] as List? ?? [])
              .map((e) => Map<String, dynamic>.from(e as Map))
              .toList();
          final types = (data['disposal_types'] as List? ?? [])
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
                        'DSP · Asset disposal',
                        style: tichUi(
                          fontSize: 12,
                          color: TichColors.neutralMuted,
                          letterSpacing: 0.4,
                        ),
                      ),
                      const SizedBox(height: 4),
                      Text(
                        'Finance/HOD approval required',
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
                      _sectionTitle('Asset & disposal'),
                      const SizedBox(height: 12),
                      DropdownButtonFormField<int>(
                        value: _assetId,
                        isExpanded: true,
                        decoration: const InputDecoration(labelText: 'Asset *'),
                        items: [
                          DropdownMenuItem<int>(
                            value: null,
                            child: Text(
                              'Select asset',
                              style: tichUi(color: TichColors.neutralMuted),
                            ),
                          ),
                          ...assets.map((row) {
                            final id = (row['id'] as num).toInt();
                            final label = (row['label'] ?? '').toString();
                            return DropdownMenuItem<int>(
                              value: id,
                              child: Text(label, overflow: TextOverflow.ellipsis),
                            );
                          }),
                        ],
                        onChanged: (v) => _onAssetSelected(v, assets),
                        validator: (_) => _assetId == null ? 'Required' : null,
                      ),
                      const SizedBox(height: 12),
                      DropdownButtonFormField<String>(
                        value: _disposalType,
                        isExpanded: true,
                        decoration: const InputDecoration(labelText: 'Type *'),
                        items: types
                            .map(
                              (row) => DropdownMenuItem<String>(
                                value: (row['value'] ?? '').toString(),
                                child: Text((row['label'] ?? '').toString()),
                              ),
                            )
                            .toList(),
                        onChanged: (v) {
                          if (v != null) setState(() => _disposalType = v);
                        },
                      ),
                      const SizedBox(height: 12),
                      TextFormField(
                        controller: _disposedValue,
                        keyboardType: const TextInputType.numberWithOptions(
                          decimal: true,
                        ),
                        inputFormatters: [
                          FilteringTextInputFormatter.allow(
                            RegExp(r'^\d*\.?\d{0,2}'),
                          ),
                        ],
                        decoration: const InputDecoration(
                          labelText: 'Disposed value',
                        ),
                      ),
                      const SizedBox(height: 12),
                      TextFormField(
                        controller: _disposalDateDisplay,
                        readOnly: true,
                        onTap: _pickDate,
                        decoration: const InputDecoration(
                          labelText: 'Disposal date',
                          hintText: 'mm/dd/yyyy',
                          suffixIcon: Icon(Icons.calendar_today, size: 20),
                        ),
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
                            : const Text('Submit request'),
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
