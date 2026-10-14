import 'package:flutter/material.dart';

import '../../adaptive/adaptive.dart';
import '../../data/api_client.dart';
import '../../data/student_api.dart';
import '../widgets/tich_widgets.dart';

class StudentSuggestionsPage extends StatefulWidget {
  const StudentSuggestionsPage({super.key, required this.api});

  final StudentApi api;

  @override
  State<StudentSuggestionsPage> createState() => _StudentSuggestionsPageState();
}

class _StudentSuggestionsPageState extends State<StudentSuggestionsPage> {
  final _subject = TextEditingController();
  final _body = TextEditingController();
  String _category = 'suggestion';
  bool _anonymous = false;
  bool _busy = false;

  @override
  void dispose() {
    _subject.dispose();
    _body.dispose();
    super.dispose();
  }

  Future<void> _submit() async {
    if (_body.text.trim().isEmpty) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Please enter your message.')),
      );
      return;
    }
    setState(() => _busy = true);
    try {
      final res = await widget.api.submitSuggestion(
        category: _category,
        body: _body.text.trim(),
        subject: _subject.text.trim().isEmpty ? null : _subject.text.trim(),
        anonymous: _anonymous,
      );
      if (!mounted) return;
      _subject.clear();
      _body.clear();
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(res['message']?.toString() ?? 'Sent')),
      );
    } on ApiException catch (e) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(e.message)));
    } finally {
      if (mounted) setState(() => _busy = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return AdaptiveScaffold(
      title: 'Suggestion box',
      body: ListView(
        padding: const EdgeInsets.all(16),
        children: [
          const TichSectionHeader('Send feedback'),
          DropdownButtonFormField<String>(
            value: _category,
            items: const [
              DropdownMenuItem(value: 'suggestion', child: Text('Suggestion')),
              DropdownMenuItem(value: 'comment', child: Text('Comment')),
              DropdownMenuItem(value: 'complaint', child: Text('Complaint')),
            ],
            onChanged: _busy
                ? null
                : (v) => setState(() => _category = v ?? 'suggestion'),
            decoration: const InputDecoration(labelText: 'Category'),
          ),
          const SizedBox(height: 10),
          TextField(
            controller: _subject,
            enabled: !_busy,
            decoration: const InputDecoration(labelText: 'Subject (optional)'),
          ),
          const SizedBox(height: 10),
          TextField(
            controller: _body,
            enabled: !_busy,
            maxLines: 6,
            decoration: const InputDecoration(labelText: 'Message'),
          ),
          SwitchListTile(
            contentPadding: EdgeInsets.zero,
            title: const Text('Submit anonymously'),
            value: _anonymous,
            onChanged: _busy ? null : (v) => setState(() => _anonymous = v),
          ),
          const SizedBox(height: 12),
          AdaptiveFilledButton(
            onPressed: _busy ? null : _submit,
            child: _busy
                ? const SizedBox(
                    width: 18,
                    height: 18,
                    child: AdaptiveProgress(color: Colors.white),
                  )
                : const Text('Send'),
          ),
        ],
      ),
    );
  }
}
