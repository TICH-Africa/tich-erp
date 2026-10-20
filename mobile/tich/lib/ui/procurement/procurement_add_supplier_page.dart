import 'package:file_picker/file_picker.dart';
import 'package:flutter/material.dart';

import '../../adaptive/adaptive.dart';
import '../../data/procurement_api.dart';
import '../../theme/tich_theme.dart';
import '../widgets/tich_widgets.dart';
import 'procurement_helpers.dart';

class ProcurementAddSupplierPage extends StatefulWidget {
  const ProcurementAddSupplierPage({super.key, required this.api});

  final ProcurementApi api;

  @override
  State<ProcurementAddSupplierPage> createState() =>
      _ProcurementAddSupplierPageState();
}

class _ProcurementAddSupplierPageState extends State<ProcurementAddSupplierPage> {
  final _formKey = GlobalKey<FormState>();
  final _name = TextEditingController();
  final _registration = TextEditingController();
  final _contact = TextEditingController();
  final _email = TextEditingController();
  final _phone = TextEditingController();
  final _kraPin = TextEditingController();
  final _postal = TextEditingController();
  final _physical = TextEditingController();
  final _bankName = TextEditingController();
  final _bankAccountName = TextEditingController();
  final _bankAccountNumber = TextEditingController();
  final _bankBranch = TextEditingController();
  final _paybill = TextEditingController();
  final _paybillAccount = TextEditingController();
  final _till = TextEditingController();
  final _mobilePay = TextEditingController();

  PlatformFile? _complianceDoc;
  PlatformFile? _pinCertificate;
  PlatformFile? _cr12;
  PlatformFile? _auditedStatements;

  String _paymentMethod = 'bank';
  bool _busy = false;

  static const _docExtensions = ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png'];

  @override
  void dispose() {
    _name.dispose();
    _registration.dispose();
    _contact.dispose();
    _email.dispose();
    _phone.dispose();
    _kraPin.dispose();
    _postal.dispose();
    _physical.dispose();
    _bankName.dispose();
    _bankAccountName.dispose();
    _bankAccountNumber.dispose();
    _bankBranch.dispose();
    _paybill.dispose();
    _paybillAccount.dispose();
    _till.dispose();
    _mobilePay.dispose();
    super.dispose();
  }

  Future<void> _pickFile(void Function(PlatformFile?) setFile) async {
    final result = await FilePicker.platform.pickFiles(
      type: FileType.custom,
      allowedExtensions: _docExtensions,
    );
    if (result == null || result.files.isEmpty) return;
    final file = result.files.single;
    if (file.path == null) return;
    setState(() => setFile(file));
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

  Map<String, String> _buildFields() {
    final fields = <String, String>{
      'supplier_name': _name.text.trim(),
      'email': _email.text.trim(),
      'phone': _phone.text.trim(),
      'payment_method': _paymentMethod,
    };
    void add(String key, String value) {
      if (value.isNotEmpty) fields[key] = value;
    }

    add('registration_number', _registration.text.trim());
    add('contact_person', _contact.text.trim());
    add('kra_pin', _kraPin.text.trim());
    add('postal_address', _postal.text.trim());
    add('physical_address', _physical.text.trim());

    if (_paymentMethod == 'bank') {
      add('bank_name', _bankName.text.trim());
      add('bank_account_name', _bankAccountName.text.trim());
      add('bank_account_number', _bankAccountNumber.text.trim());
      add('bank_branch', _bankBranch.text.trim());
    } else if (_paymentMethod == 'paybill') {
      add('paybill_number', _paybill.text.trim());
      add('paybill_account_number', _paybillAccount.text.trim());
    } else if (_paymentMethod == 'till') {
      add('till_number', _till.text.trim());
    } else if (_paymentMethod == 'mobile') {
      add('mobile_number', _mobilePay.text.trim());
    }
    return fields;
  }

  List<({String field, String path, String name, String? mimeType})> _buildFiles() {
    final files = <({String field, String path, String name, String? mimeType})>[];
    void add(String field, PlatformFile? file) {
      if (file?.path == null) return;
      final name = file!.name;
      files.add((
        field: field,
        path: file.path!,
        name: name,
        mimeType: _mimeFor(name),
      ));
    }

    add('compliance_doc_path', _complianceDoc);
    add('pin_certificate_path', _pinCertificate);
    add('cr12_path', _cr12);
    add('audited_financial_statements_path', _auditedStatements);
    return files;
  }

  Future<void> _submit() async {
    if (!_formKey.currentState!.validate()) return;
    setState(() => _busy = true);
    try {
      final res = await widget.api.createSupplier(
        fields: _buildFields(),
        files: _buildFiles(),
      );
      if (!mounted) return;
      final item = res['item'] as Map?;
      final id = (item?['id'] as num?)?.toInt();
      procurementNotifyCountsChanged();
      if (id != null) Navigator.pop(context, id);
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

  Widget _fileField({
    required String label,
    required PlatformFile? file,
    required VoidCallback onPick,
    required VoidCallback onClear,
  }) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 12),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(label, style: tichUi(fontSize: 14, fontWeight: FontWeight.w600)),
          const SizedBox(height: 8),
          Row(
            children: [
              OutlinedButton(
                onPressed: onPick,
                child: const Text('Choose file'),
              ),
              const SizedBox(width: 8),
              Expanded(
                child: Text(
                  file?.name ?? 'No file chosen',
                  maxLines: 2,
                  overflow: TextOverflow.ellipsis,
                  style: tichUi(fontSize: 13, color: TichColors.neutralMuted),
                ),
              ),
              if (file != null)
                IconButton(
                  tooltip: 'Remove',
                  onPressed: onClear,
                  icon: const Icon(Icons.close, size: 20),
                ),
            ],
          ),
        ],
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return AdaptiveScaffold(
      title: 'Register supplier',
      body: Form(
        key: _formKey,
        child: ListView(
          padding: const EdgeInsets.all(12),
          children: [
            TichCard(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.stretch,
                children: [
                  _sectionTitle('Business details'),
                  const SizedBox(height: 12),
                  TextFormField(
                    controller: _name,
                    decoration: const InputDecoration(labelText: 'Company name *'),
                    validator: (v) =>
                        (v == null || v.trim().isEmpty) ? 'Required' : null,
                  ),
                  const SizedBox(height: 8),
                  TextFormField(
                    controller: _registration,
                    decoration: const InputDecoration(labelText: 'Registration number'),
                  ),
                  const SizedBox(height: 8),
                  TextFormField(
                    controller: _contact,
                    decoration: const InputDecoration(labelText: 'Contact person'),
                  ),
                  const SizedBox(height: 8),
                  TextFormField(
                    controller: _email,
                    keyboardType: TextInputType.emailAddress,
                    decoration: const InputDecoration(labelText: 'Email *'),
                    validator: (v) {
                      if (v == null || v.trim().isEmpty) return 'Required';
                      if (!v.contains('@')) return 'Invalid email';
                      return null;
                    },
                  ),
                  const SizedBox(height: 8),
                  TextFormField(
                    controller: _phone,
                    keyboardType: TextInputType.phone,
                    decoration: const InputDecoration(labelText: 'Phone *'),
                    validator: (v) =>
                        (v == null || v.trim().isEmpty) ? 'Required' : null,
                  ),
                  const SizedBox(height: 8),
                  TextFormField(
                    controller: _postal,
                    decoration: const InputDecoration(labelText: 'Postal address'),
                  ),
                  const SizedBox(height: 8),
                  TextFormField(
                    controller: _physical,
                    decoration: const InputDecoration(labelText: 'Physical address'),
                  ),
                ],
              ),
            ),
            const SizedBox(height: 12),
            TichCard(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.stretch,
                children: [
                  _sectionTitle('Compliance & tax'),
                  const SizedBox(height: 12),
                  TextFormField(
                    controller: _kraPin,
                    decoration: const InputDecoration(labelText: 'KRA PIN'),
                  ),
                  const SizedBox(height: 12),
                  _fileField(
                    label: 'Compliance certificate',
                    file: _complianceDoc,
                    onPick: () => _pickFile((f) => _complianceDoc = f),
                    onClear: () => setState(() => _complianceDoc = null),
                  ),
                  _fileField(
                    label: 'PIN certificate',
                    file: _pinCertificate,
                    onPick: () => _pickFile((f) => _pinCertificate = f),
                    onClear: () => setState(() => _pinCertificate = null),
                  ),
                  _fileField(
                    label: 'CR12',
                    file: _cr12,
                    onPick: () => _pickFile((f) => _cr12 = f),
                    onClear: () => setState(() => _cr12 = null),
                  ),
                  _fileField(
                    label: 'Audited financial statements (last 2 years)',
                    file: _auditedStatements,
                    onPick: () => _pickFile((f) => _auditedStatements = f),
                    onClear: () => setState(() => _auditedStatements = null),
                  ),
                  Text(
                    'PDF, Word, or image up to 5 MB each.',
                    style: tichUi(fontSize: 12, color: TichColors.neutralMuted),
                  ),
                ],
              ),
            ),
            const SizedBox(height: 12),
            TichCard(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.stretch,
                children: [
                  _sectionTitle('Payment'),
                  const SizedBox(height: 12),
                  DropdownButtonFormField<String>(
                    initialValue: _paymentMethod,
                    decoration: const InputDecoration(labelText: 'Payment method *'),
                    items: const [
                      DropdownMenuItem(value: 'bank', child: Text('Bank transfer')),
                      DropdownMenuItem(value: 'paybill', child: Text('Paybill')),
                      DropdownMenuItem(value: 'till', child: Text('Till number')),
                      DropdownMenuItem(value: 'mobile', child: Text('Mobile money')),
                    ],
                    onChanged: (v) => setState(() => _paymentMethod = v ?? 'bank'),
                  ),
                  if (_paymentMethod == 'bank') ...[
                    const SizedBox(height: 8),
                    TextFormField(
                      controller: _bankName,
                      decoration: const InputDecoration(labelText: 'Bank name *'),
                      validator: (v) => _paymentMethod == 'bank' &&
                              (v == null || v.trim().isEmpty)
                          ? 'Required'
                          : null,
                    ),
                    const SizedBox(height: 8),
                    TextFormField(
                      controller: _bankAccountName,
                      decoration: const InputDecoration(labelText: 'Account name *'),
                      validator: (v) => _paymentMethod == 'bank' &&
                              (v == null || v.trim().isEmpty)
                          ? 'Required'
                          : null,
                    ),
                    const SizedBox(height: 8),
                    TextFormField(
                      controller: _bankAccountNumber,
                      decoration: const InputDecoration(labelText: 'Account number *'),
                      validator: (v) => _paymentMethod == 'bank' &&
                              (v == null || v.trim().isEmpty)
                          ? 'Required'
                          : null,
                    ),
                    const SizedBox(height: 8),
                    TextFormField(
                      controller: _bankBranch,
                      decoration: const InputDecoration(labelText: 'Branch'),
                    ),
                  ],
                  if (_paymentMethod == 'paybill') ...[
                    const SizedBox(height: 8),
                    TextFormField(
                      controller: _paybill,
                      decoration: const InputDecoration(labelText: 'Paybill number *'),
                      validator: (v) => _paymentMethod == 'paybill' &&
                              (v == null || v.trim().isEmpty)
                          ? 'Required'
                          : null,
                    ),
                    const SizedBox(height: 8),
                    TextFormField(
                      controller: _paybillAccount,
                      decoration: const InputDecoration(labelText: 'Paybill account *'),
                      validator: (v) => _paymentMethod == 'paybill' &&
                              (v == null || v.trim().isEmpty)
                          ? 'Required'
                          : null,
                    ),
                  ],
                  if (_paymentMethod == 'till') ...[
                    const SizedBox(height: 8),
                    TextFormField(
                      controller: _till,
                      decoration: const InputDecoration(labelText: 'Till number *'),
                      validator: (v) =>
                          _paymentMethod == 'till' && (v == null || v.trim().isEmpty)
                              ? 'Required'
                              : null,
                    ),
                  ],
                  if (_paymentMethod == 'mobile') ...[
                    const SizedBox(height: 8),
                    TextFormField(
                      controller: _mobilePay,
                      decoration: const InputDecoration(labelText: 'Mobile number *'),
                      validator: (v) => _paymentMethod == 'mobile' &&
                              (v == null || v.trim().isEmpty)
                          ? 'Required'
                          : null,
                    ),
                  ],
                ],
              ),
            ),
            const SizedBox(height: 16),
            FilledButton(
              onPressed: _busy ? null : _submit,
              child: _busy
                  ? const AdaptiveProgress()
                  : const Text('Register supplier'),
            ),
          ],
        ),
      ),
    );
  }
}
