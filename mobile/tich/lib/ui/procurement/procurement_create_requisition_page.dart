import 'package:file_picker/file_picker.dart';
import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:intl/intl.dart';

import '../../adaptive/adaptive.dart';
import '../../data/api_client.dart';
import '../../data/procurement_api.dart';
import '../../theme/tich_theme.dart';
import '../widgets/date_time_pickers.dart';
import '../widgets/tich_widgets.dart';
import 'procurement_helpers.dart';

class ProcurementCreateRequisitionPage extends StatefulWidget {
  const ProcurementCreateRequisitionPage({super.key, required this.api});

  final ProcurementApi api;

  @override
  State<ProcurementCreateRequisitionPage> createState() =>
      _ProcurementCreateRequisitionPageState();
}

class _ProcurementCreateRequisitionPageState
    extends State<ProcurementCreateRequisitionPage> {
  static const _stepTitles = ['Request details', 'Line items & submit'];

  final _requestDateApi = DateFormat('dd/MM/yyyy');
  final _money = NumberFormat('#,##0.00');

  int _step = 0;
  bool _busy = false;
  bool _loadingBudgetLines = false;

  bool _optionsLoading = true;
  Object? _optionsError;
  List<Map<String, dynamic>> _departments = [];
  List<Map<String, dynamic>> _budgets = [];

  int? _departmentId;
  DateTime _requestDate = DateTime.now();
  String? _budgetKey;
  String? _budgetCode;
  String _budgetHint =
      'Leave budget empty to add items yourself, or select a budget to load its line items below (you can still edit them).';

  final _justification = TextEditingController();
  final List<PlatformFile> _attachments = [];
  final List<_RequisitionLineRow> _lines = [_RequisitionLineRow()];

  @override
  void initState() {
    super.initState();
    for (final row in _lines) {
      row.attachRecalc(_recalcTotals);
    }
    _loadOptions();
  }

  Future<void> _loadOptions() async {
    setState(() {
      _optionsLoading = true;
      _optionsError = null;
    });
    try {
      final data = await widget.api.requisitionFormOptions();
      if (!mounted) return;
      setState(() {
        _departments = (data['departments'] as List? ?? [])
            .map((e) => Map<String, dynamic>.from(e as Map))
            .toList();
        _budgets = (data['budgets'] as List? ?? [])
            .map((e) => Map<String, dynamic>.from(e as Map))
            .toList();
        _optionsLoading = false;
      });
    } catch (e) {
      if (!mounted) return;
      setState(() {
        _optionsError = e;
        _optionsLoading = false;
      });
    }
  }

  @override
  void dispose() {
    _justification.dispose();
    for (final row in _lines) {
      row.dispose();
    }
    super.dispose();
  }

  void _recalcTotals() {
    if (mounted) setState(() {});
  }

  double get _grandTotal {
    var sum = 0.0;
    for (final row in _lines) {
      sum += row.lineTotal;
    }
    return sum;
  }

  Widget _sectionHead(String title) {
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

  Widget _heroCard() {
    return TichCard(
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  'REQ · Requisition',
                  style: tichUi(
                    fontSize: 12,
                    color: TichColors.neutralMuted,
                    letterSpacing: 0.4,
                  ),
                ),
                const SizedBox(height: 4),
                Text(
                  'Purchase request (budget optional)',
                  style: TichTheme.display(fontSize: 17),
                ),
              ],
            ),
          ),
          Container(
            padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
            decoration: BoxDecoration(
              color: TichColors.blueDark.withValues(alpha: 0.25),
              borderRadius: BorderRadius.circular(999),
              border: Border.all(color: TichColors.blueDark),
            ),
            child: Text(
              'Draft',
              style: tichUi(
                fontSize: 12,
                fontWeight: FontWeight.w600,
                color: TichColors.blueDark,
              ),
            ),
          ),
        ],
      ),
    );
  }

  Future<void> _pickAttachments() async {
    if (_attachments.length >= 5) {
      procurementShowError(context, 'Maximum 5 attachments.');
      return;
    }
    final result = await FilePicker.platform.pickFiles(
      allowMultiple: true,
      type: FileType.custom,
      allowedExtensions: ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png'],
    );
    if (result == null) return;
    setState(() {
      for (final f in result.files) {
        if (_attachments.length >= 5) break;
        if (f.path != null) _attachments.add(f);
      }
    });
  }

  String? _mimeFor(String name) {
    final lower = name.toLowerCase();
    if (lower.endsWith('.pdf')) return 'application/pdf';
    if (lower.endsWith('.png')) return 'image/png';
    if (lower.endsWith('.jpg') || lower.endsWith('.jpeg')) return 'image/jpeg';
    if (lower.endsWith('.doc')) return 'application/msword';
    if (lower.endsWith('.docx')) {
      return 'application/vnd.openxmlformats-officedocument.wordprocessingml.document';
    }
    return null;
  }

  void _applyBudgetMeta(Map<String, dynamic>? budget) {
    if (budget == null) {
      setState(() {
        _budgetKey = null;
        _budgetCode = null;
        _budgetHint =
            'No budget selected. Enter line items in the table below.';
      });
      return;
    }
    setState(() {
      _budgetKey = budget['key']?.toString();
      _budgetCode = budget['code']?.toString();
      final deptId = (budget['department_id'] as num?)?.toInt();
      if (deptId != null) _departmentId = deptId;
    });
  }

  Future<void> _onBudgetChanged(String? key) async {
    if (key == null || key.isEmpty) {
      _applyBudgetMeta(null);
      if (_lines.isEmpty) {
        setState(() => _lines.add(_RequisitionLineRow()..attachRecalc(_recalcTotals)));
      }
      return;
    }

    Map<String, dynamic>? selected;
    for (final b in _budgets) {
      if (b['key']?.toString() == key) {
        selected = b;
        break;
      }
    }
    if (selected == null) return;

    _applyBudgetMeta(selected);
    setState(() {
      _loadingBudgetLines = true;
      _budgetHint = 'Loading budget line items…';
    });

    try {
      final data = await widget.api.requisitionBudgetLines(
        source: selected['source'].toString(),
        id: (selected['id'] as num).toInt(),
      );
      if (!mounted) return;
      final rawLines = data['lines'] as List? ?? [];
      for (final row in _lines) {
        row.dispose();
      }
      _lines.clear();
      if (rawLines.isEmpty) {
        _lines.add(_RequisitionLineRow()..attachRecalc(_recalcTotals));
        setState(() {
          _loadingBudgetLines = false;
          _budgetHint =
              'This budget has no saved line items. Add them manually below.';
        });
        return;
      }
      for (final raw in rawLines) {
        final line = Map<String, dynamic>.from(raw as Map);
        _lines.add(
          _RequisitionLineRow.fromMap(line)..attachRecalc(_recalcTotals),
        );
      }
      setState(() {
        _loadingBudgetLines = false;
        _budgetHint =
            'Loaded ${rawLines.length} line item${rawLines.length == 1 ? '' : 's'} from ${data['budget_code'] ?? 'budget'}. Edit as needed.';
      });
    } catch (e) {
      if (!mounted) return;
      setState(() {
        _loadingBudgetLines = false;
        _budgetHint =
            'Could not load budget lines. You can still enter items manually.';
      });
      if (_lines.isEmpty) {
        _lines.add(_RequisitionLineRow()..attachRecalc(_recalcTotals));
      }
      procurementShowError(context, e);
    }
  }

  bool _validateStep(int step) {
    if (step == 0) {
      if (_departmentId == null) {
        procurementShowError(context, 'Select a requesting department.');
        return false;
      }
      if (_justification.text.trim().isEmpty) {
        procurementShowError(context, 'Justification is required.');
        return false;
      }
      return true;
    }
    for (final row in _lines) {
      if (!row.isValid) {
        procurementShowError(
          context,
          'Each line needs an item, quantity, and unit price.',
        );
        return false;
      }
    }
    if (_grandTotal <= 0) {
      procurementShowError(context, 'Grand total must be greater than zero.');
      return false;
    }
    return true;
  }

  Future<void> _saveDraft() async {
    if (!_validateStep(0)) {
      setState(() => _step = 0);
      return;
    }
    if (!_validateStep(1)) return;

    setState(() => _busy = true);
    try {
      final attachmentPayload = [
        for (final f in _attachments)
          if (f.path != null)
            (path: f.path!, name: f.name, mimeType: _mimeFor(f.name)),
      ];
      final res = await widget.api.createRequisition(
        requestingDepartmentId: _departmentId!,
        requestDate: _requestDateApi.format(_requestDate),
        justification: _justification.text.trim(),
        budgetCode: _budgetCode,
        lines: [for (final row in _lines) row.toMap()],
        attachments: attachmentPayload,
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

  Widget _buildStepOne() {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.stretch,
      children: [
        _heroCard(),
        const SizedBox(height: 12),
        TichCard(
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              _sectionHead('Request details'),
              const SizedBox(height: 12),
              TichDropdownFormField<int>(
                value: _departmentId,
                decoration: const InputDecoration(
                  labelText: 'Requesting department *',
                ),
                items: [
                  const DropdownMenuItem<int>(
                    value: null,
                    child: Text('Select department'),
                  ),
                  for (final d in _departments)
                    DropdownMenuItem<int>(
                      value: (d['id'] as num).toInt(),
                      child: Text(
                        (d['name'] ?? '').toString(),
                        overflow: TextOverflow.ellipsis,
                      ),
                    ),
                ],
                onChanged: _busy
                    ? null
                    : (v) => setState(() => _departmentId = v),
              ),
              const SizedBox(height: 12),
              ListTile(
                contentPadding: EdgeInsets.zero,
                title: const Text('Request date *'),
                subtitle: Text(_requestDateApi.format(_requestDate)),
                trailing: const Icon(Icons.calendar_today_outlined),
                onTap: _busy
                    ? null
                    : () async {
                        final picked = await pickDateOnly(
                          context,
                          initial: _requestDate,
                        );
                        if (picked != null) {
                          setState(() => _requestDate = picked);
                        }
                      },
              ),
              const SizedBox(height: 8),
              TichDropdownFormField<String>(
                value: _budgetKey != null && _budgetKey!.isNotEmpty
                    ? _budgetKey
                    : null,
                decoration: const InputDecoration(
                  labelText: 'Budget (optional)',
                ),
                items: [
                  const DropdownMenuItem<String>(
                    value: null,
                    child: Text('No budget — enter line items manually'),
                  ),
                  for (final b in _budgets)
                    DropdownMenuItem<String>(
                      value: b['key']?.toString(),
                      child: Text(
                        (b['label'] ?? '').toString(),
                        overflow: TextOverflow.ellipsis,
                      ),
                    ),
                ],
                onChanged: _busy || _loadingBudgetLines
                    ? null
                    : (v) => _onBudgetChanged(v),
              ),
              const SizedBox(height: 6),
              Text(
                _budgetHint,
                style: tichUi(fontSize: 12, color: TichColors.neutralMuted),
              ),
              const SizedBox(height: 12),
              TextField(
                controller: _justification,
                minLines: 4,
                maxLines: 8,
                decoration: const InputDecoration(
                  labelText: 'Justification *',
                  hintText:
                      'Describe the purpose and business need for this requisition…',
                  alignLabelWithHint: true,
                ),
              ),
              const SizedBox(height: 12),
              Text(
                'Attachments',
                style: tichUi(fontSize: 14, fontWeight: FontWeight.w600),
              ),
              const SizedBox(height: 8),
              OutlinedButton(
                onPressed: _busy ? null : _pickAttachments,
                child: const Text('Choose files'),
              ),
              const SizedBox(height: 6),
              Text(
                _attachments.isEmpty
                    ? 'No file chosen'
                    : _attachments.map((f) => f.name).join(', '),
                style: tichUi(fontSize: 13, color: TichColors.neutralMuted),
              ),
              Text(
                'Max 5 files, 5MB each. Accepted: PDF, DOC, DOCX, JPG, JPEG, PNG.',
                style: tichUi(fontSize: 12, color: TichColors.neutralMuted),
              ),
            ],
          ),
        ),
      ],
    );
  }

  Widget _buildStepTwo() {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.stretch,
      children: [
        TichCard(
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              _sectionHead('Requisition line items'),
              const SizedBox(height: 8),
              Text(
                'Row total = quantity × unit price. Add and edit lines below.',
                style: tichUi(fontSize: 12, color: TichColors.neutralMuted),
              ),
              const SizedBox(height: 12),
              for (var i = 0; i < _lines.length; i++) ...[
                if (i > 0) const SizedBox(height: 10),
                TichCard(
                  padding: const EdgeInsets.all(12),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.stretch,
                    children: [
                      Row(
                        children: [
                          Expanded(
                            child: Text(
                              'Line ${i + 1}',
                              style: tichUi(fontWeight: FontWeight.w700),
                            ),
                          ),
                          IconButton(
                            tooltip: 'Remove line',
                            onPressed: _busy
                                ? null
                                : () {
                                    setState(() {
                                      if (_lines.length <= 1) {
                                        _lines[i].clear();
                                      } else {
                                        _lines[i].dispose();
                                        _lines.removeAt(i);
                                      }
                                    });
                                  },
                            icon: const Icon(Icons.close, size: 22),
                          ),
                        ],
                      ),
                      TextField(
                        controller: _lines[i].item,
                        decoration: const InputDecoration(labelText: 'Item *'),
                        textInputAction: TextInputAction.next,
                      ),
                      const SizedBox(height: 10),
                      TextField(
                        controller: _lines[i].quantity,
                        decoration: const InputDecoration(labelText: 'Quantity *'),
                        keyboardType: const TextInputType.numberWithOptions(
                          decimal: true,
                        ),
                        inputFormatters: [
                          FilteringTextInputFormatter.allow(
                            RegExp(r'^\d*\.?\d{0,4}'),
                          ),
                        ],
                      ),
                      const SizedBox(height: 10),
                      TextField(
                        controller: _lines[i].description,
                        decoration: const InputDecoration(
                          labelText: 'Description',
                          hintText: 'Optional',
                        ),
                        minLines: 2,
                        maxLines: 4,
                      ),
                      const SizedBox(height: 10),
                      TextField(
                        controller: _lines[i].unitPrice,
                        decoration: const InputDecoration(labelText: 'Unit price *'),
                        keyboardType: const TextInputType.numberWithOptions(
                          decimal: true,
                        ),
                        inputFormatters: [
                          FilteringTextInputFormatter.allow(
                            RegExp(r'^\d*\.?\d{0,2}'),
                          ),
                        ],
                      ),
                      const SizedBox(height: 10),
                      TextField(
                        controller: _lines[i].unit,
                        decoration: const InputDecoration(
                          labelText: 'Unit',
                          hintText: 'e.g. pcs',
                        ),
                      ),
                      const SizedBox(height: 10),
                      InputDecorator(
                        decoration: const InputDecoration(
                          labelText: 'Total',
                        ),
                        child: Text(
                          _money.format(_lines[i].lineTotal),
                          style: tichUi(fontWeight: FontWeight.w600),
                        ),
                      ),
                    ],
                  ),
                ),
              ],
              const SizedBox(height: 8),
              Align(
                alignment: Alignment.centerLeft,
                child: OutlinedButton.icon(
                  onPressed: _busy
                      ? null
                      : () {
                          setState(() {
                            _lines.add(
                              _RequisitionLineRow()..attachRecalc(_recalcTotals),
                            );
                          });
                        },
                  icon: const Icon(Icons.add, size: 18),
                  label: const Text('Add line'),
                ),
              ),
              const SizedBox(height: 16),
              Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  Text(
                    'Grand total (KES)',
                    style: tichUi(fontWeight: FontWeight.w700),
                  ),
                  Text(
                    _money.format(_grandTotal),
                    style: TichTheme.display(fontSize: 18),
                  ),
                ],
              ),
            ],
          ),
        ),
        const SizedBox(height: 12),
        TichCard(
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              _sectionHead('Submit'),
              const SizedBox(height: 12),
              FilledButton(
                onPressed: _busy ? null : _saveDraft,
                child: _busy
                    ? const SizedBox(
                        height: 22,
                        width: 22,
                        child: CircularProgressIndicator(strokeWidth: 2),
                      )
                    : const Text('Save requisition draft'),
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
    );
  }

  @override
  Widget build(BuildContext context) {
    return AdaptiveScaffold(
      title: 'New requisition',
      body: _optionsLoading
          ? const Center(child: AdaptiveProgress())
          : _optionsError != null
              ? Center(
                  child: Column(
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      Text(tichDescribeError(_optionsError)),
                      TextButton(
                        onPressed: _loadOptions,
                        child: const Text('Retry'),
                      ),
                    ],
                  ),
                )
              : Column(
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              Padding(
                padding: const EdgeInsets.fromLTRB(16, 12, 16, 8),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      'Section ${_step + 1} of 2 · ${_stepTitles[_step]}',
                      style: tichUi(
                        fontSize: 12,
                        fontWeight: FontWeight.w600,
                        color: TichColors.neutralMuted,
                      ),
                    ),
                    const SizedBox(height: 8),
                    ClipRRect(
                      borderRadius: TichRadius.borderRadiusSm,
                      child: LinearProgressIndicator(
                        value: (_step + 1) / 2,
                        minHeight: 6,
                        backgroundColor: TichColors.neutralBorder,
                        color: TichColors.green,
                      ),
                    ),
                  ],
                ),
              ),
              if (_loadingBudgetLines)
                const LinearProgressIndicator(minHeight: 2),
              Expanded(
                child: SingleChildScrollView(
                  padding: const EdgeInsets.fromLTRB(16, 8, 16, 16),
                  child: _step == 0 ? _buildStepOne() : _buildStepTwo(),
                ),
              ),
              SafeArea(
                top: false,
                child: Padding(
                  padding: const EdgeInsets.fromLTRB(16, 8, 16, 12),
                  child: _step == 0
                      ? FilledButton(
                          onPressed: _busy
                              ? null
                              : () {
                                  if (!_validateStep(0)) return;
                                  setState(() => _step = 1);
                                },
                          child: const Text('Next'),
                        )
                      : OutlinedButton(
                          onPressed: _busy
                              ? null
                              : () => setState(() => _step -= 1),
                          child: const Text('Previous'),
                        ),
                ),
              ),
            ],
          ),
    );
  }
}

class _RequisitionLineRow {
  _RequisitionLineRow()
      : item = TextEditingController(),
        quantity = TextEditingController(text: '1'),
        description = TextEditingController(),
        unitPrice = TextEditingController(),
        unit = TextEditingController();

  _RequisitionLineRow.fromMap(Map<String, dynamic> map)
      : item = TextEditingController(text: (map['item'] ?? '').toString()),
        quantity = TextEditingController(
          text: (map['quantity'] ?? '1').toString(),
        ),
        description = TextEditingController(
          text: (map['description'] ?? '').toString(),
        ),
        unitPrice = TextEditingController(
          text: (map['unit_price'] ?? '').toString(),
        ),
        unit = TextEditingController(
          text: (map['unit_of_measure'] ?? '').toString(),
        );

  final TextEditingController item;
  final TextEditingController quantity;
  final TextEditingController description;
  final TextEditingController unitPrice;
  final TextEditingController unit;

  VoidCallback? _recalc;

  void attachRecalc(VoidCallback recalc) {
    _recalc = recalc;
    for (final c in [item, quantity, unitPrice]) {
      c.addListener(recalc);
    }
  }

  double get lineTotal {
    final qty = double.tryParse(quantity.text.trim()) ?? 0;
    final price = double.tryParse(unitPrice.text.trim()) ?? 0;
    return qty * price;
  }

  bool get isValid {
    if (item.text.trim().isEmpty) return false;
    final qty = double.tryParse(quantity.text.trim());
    final price = double.tryParse(unitPrice.text.trim());
    return qty != null && qty > 0 && price != null && price >= 0;
  }

  void clear() {
    item.clear();
    quantity.text = '1';
    description.clear();
    unitPrice.clear();
    unit.clear();
    _recalc?.call();
  }

  Map<String, dynamic> toMap() => {
        'item': item.text.trim(),
        'quantity': double.parse(quantity.text.trim()),
        'description': description.text.trim(),
        'unit_price': double.parse(unitPrice.text.trim()),
        if (unit.text.trim().isNotEmpty) 'unit_of_measure': unit.text.trim(),
      };

  void dispose() {
    for (final c in [item, quantity, description, unitPrice, unit]) {
      c.dispose();
    }
  }
}
