import 'package:flutter/material.dart';

import '../../theme/tich_theme.dart';
import '../widgets/tich_widgets.dart';

/// Standard padding for procurement record screens.
class ProcurementDetailBody extends StatelessWidget {
  const ProcurementDetailBody({super.key, required this.children});

  final List<Widget> children;

  @override
  Widget build(BuildContext context) {
    return ListView(
      padding: const EdgeInsets.fromLTRB(12, 12, 12, 24),
      children: [
        for (var i = 0; i < children.length; i++) ...[
          if (i > 0) const SizedBox(height: 12),
          children[i],
        ],
      ],
    );
  }
}

Color _statusBackground(String label) {
  final s = label.toLowerCase();
  if (s.contains('reject') ||
      s.contains('cancel') ||
      s.contains('flag') ||
      s.contains('discrep')) {
    return TichColors.error.withValues(alpha: 0.12);
  }
  if (s.contains('pending') ||
      s.contains('review') ||
      s.contains('draft') ||
      s.contains('submitted')) {
    return TichColors.warningSoft;
  }
  if (s.contains('approv') ||
      s.contains('complete') ||
      s.contains('match') ||
      s.contains('verified') ||
      s.contains('active') ||
      s.contains('paid') ||
      s.contains('success')) {
    return TichColors.greenLight;
  }
  return TichColors.blue.withValues(alpha: 0.12);
}

Color _statusForeground(String label) {
  final s = label.toLowerCase();
  if (s.contains('reject') || s.contains('cancel') || s.contains('flag')) {
    return TichColors.error;
  }
  if (s.contains('pending') || s.contains('review') || s.contains('draft')) {
    return TichColors.warningText;
  }
  if (s.contains('approv') ||
      s.contains('complete') ||
      s.contains('verified') ||
      s.contains('paid')) {
    return TichColors.greenDark;
  }
  return TichColors.blue;
}

Widget procurementStatusChip(String label) {
  if (label.isEmpty) return const SizedBox.shrink();
  return Container(
    padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 5),
    decoration: BoxDecoration(
      color: _statusBackground(label),
      borderRadius: BorderRadius.circular(8),
      border: Border.all(color: _statusForeground(label).withValues(alpha: 0.25)),
    ),
    child: Text(
      label,
      style: tichUi(
        fontSize: 11,
        fontWeight: FontWeight.w700,
        color: _statusForeground(label),
      ),
    ),
  );
}

Widget procurementDetailHero({
  required String title,
  String? eyebrow,
  String? statusLabel,
  String? metricLabel,
  String? metricValue,
}) {
  return TichCard(
    child: Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        if (eyebrow != null && eyebrow.isNotEmpty)
          Text(
            eyebrow,
            style: tichUi(fontSize: 12, color: TichColors.neutralMuted),
          ),
        if (eyebrow != null && eyebrow.isNotEmpty) const SizedBox(height: 4),
        Text(title, style: TichTheme.display(fontSize: 20)),
        if (statusLabel != null || metricValue != null) ...[
          const SizedBox(height: 12),
          Row(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              if (statusLabel != null && statusLabel.isNotEmpty)
                procurementStatusChip(statusLabel),
              const Spacer(),
              if (metricValue != null && metricValue.isNotEmpty)
                Column(
                  crossAxisAlignment: CrossAxisAlignment.end,
                  children: [
                    if (metricLabel != null)
                      Text(
                        metricLabel,
                        style: tichUi(fontSize: 11, color: TichColors.neutralMuted),
                      ),
                    Text(
                      metricValue,
                      style: tichUi(fontSize: 15, fontWeight: FontWeight.w700),
                    ),
                  ],
                ),
            ],
          ),
        ],
      ],
    ),
  );
}

Widget procurementDetailSection(String title, List<Widget> fields) {
  final visible = fields.whereType<Widget>().where((w) => w is! SizedBox).toList();
  if (visible.isEmpty) return const SizedBox.shrink();

  return Column(
    crossAxisAlignment: CrossAxisAlignment.start,
    children: [
      Padding(
        padding: const EdgeInsets.only(left: 4, bottom: 6),
        child: Text(
          title.toUpperCase(),
          style: tichUi(
            fontSize: 11,
            fontWeight: FontWeight.w700,
            letterSpacing: 0.8,
            color: TichColors.neutralMuted,
          ),
        ),
      ),
      TichCard(
        padding: EdgeInsets.zero,
        child: Column(
          children: [
            for (var i = 0; i < visible.length; i++) ...[
              if (i > 0)
                Divider(
                  height: 1,
                  color: TichColors.neutralBorder.withValues(alpha: 0.6),
                ),
              Padding(
                padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 4),
                child: visible[i],
              ),
            ],
          ],
        ),
      ),
    ],
  );
}

Widget procurementDetailField(String label, dynamic value) {
  final text = (value ?? '').toString().trim();
  if (text.isEmpty) return const SizedBox.shrink();
  return Padding(
    padding: const EdgeInsets.symmetric(vertical: 10),
    child: Row(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        SizedBox(
          width: 118,
          child: Text(
            label,
            style: tichUi(fontSize: 13, color: TichColors.neutralMuted),
          ),
        ),
        Expanded(
          child: Text(
            text,
            style: tichUi(fontSize: 14, fontWeight: FontWeight.w600),
          ),
        ),
      ],
    ),
  );
}

Widget procurementDetailNote(String text) {
  if (text.trim().isEmpty) return const SizedBox.shrink();
  return Padding(
    padding: const EdgeInsets.symmetric(vertical: 10),
    child: Text(text, style: tichUi(fontSize: 14, height: 1.45)),
  );
}

Widget procurementLineItemTile({
  required String title,
  String? subtitle,
  String? trailing,
}) {
  return Padding(
    padding: const EdgeInsets.symmetric(vertical: 10),
    child: Row(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Expanded(
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(title, style: tichUi(fontSize: 14, fontWeight: FontWeight.w600)),
              if (subtitle != null && subtitle.isNotEmpty)
                Padding(
                  padding: const EdgeInsets.only(top: 2),
                  child: Text(
                    subtitle,
                    style: tichUi(fontSize: 12, color: TichColors.neutralMuted),
                  ),
                ),
            ],
          ),
        ),
        if (trailing != null && trailing.isNotEmpty)
          Text(
            trailing,
            style: tichUi(fontSize: 13, fontWeight: FontWeight.w600),
          ),
      ],
    ),
  );
}

Widget procurementDetailActions(List<Widget> buttons) {
  if (buttons.isEmpty) return const SizedBox.shrink();
  return TichCard(
    child: Column(
      crossAxisAlignment: CrossAxisAlignment.stretch,
      children: [
        Text(
          'ACTIONS',
          style: tichUi(
            fontSize: 11,
            fontWeight: FontWeight.w700,
            letterSpacing: 0.8,
            color: TichColors.neutralMuted,
          ),
        ),
        const SizedBox(height: 12),
        for (var i = 0; i < buttons.length; i++) ...[
          if (i > 0) const SizedBox(height: 8),
          buttons[i],
        ],
      ],
    ),
  );
}

/// Search field with debounced [onQueryChanged].
class ProcurementSearchBar extends StatefulWidget {
  const ProcurementSearchBar({
    super.key,
    required this.hint,
    required this.onQueryChanged,
  });

  final String hint;
  final ValueChanged<String> onQueryChanged;

  @override
  State<ProcurementSearchBar> createState() => _ProcurementSearchBarState();
}

class _ProcurementSearchBarState extends State<ProcurementSearchBar> {
  final _controller = TextEditingController();
  String _lastEmitted = '';

  @override
  void initState() {
    super.initState();
    _controller.addListener(_onChanged);
  }

  void _onChanged() {
    setState(() {});
    final q = _controller.text.trim();
    if (q == _lastEmitted) return;
    _lastEmitted = q;
    widget.onQueryChanged(q);
  }

  @override
  void dispose() {
    _controller.removeListener(_onChanged);
    _controller.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.fromLTRB(12, 12, 12, 0),
      child: TextField(
        controller: _controller,
        decoration: InputDecoration(
          hintText: widget.hint,
          prefixIcon: const Icon(Icons.search, size: 22),
          suffixIcon: _controller.text.isEmpty
              ? null
              : IconButton(
                  icon: const Icon(Icons.clear),
                  onPressed: () => _controller.clear(),
                ),
        ),
      ),
    );
  }
}
