import 'package:flutter/material.dart';

import '../../adaptive/adaptive.dart';
import '../../data/api_client.dart';
import '../../theme/tich_theme.dart';

class TichSectionHeader extends StatelessWidget {
  const TichSectionHeader(this.title, {super.key, this.trailing});

  final String title;
  final Widget? trailing;

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 10, top: 4),
      child: Row(
        children: [
          Expanded(
            child: Text(
              title,
              maxLines: 2,
              overflow: TextOverflow.ellipsis,
              style: TichTheme.display(
                fontSize: 16,
                color: Theme.of(context).colorScheme.onSurface,
              ),
            ),
          ),
          if (trailing != null) trailing!,
        ],
      ),
    );
  }
}

class TichCard extends StatelessWidget {
  const TichCard({super.key, required this.child, this.padding, this.onTap});

  final Widget child;
  final EdgeInsetsGeometry? padding;
  final VoidCallback? onTap;

  @override
  Widget build(BuildContext context) {
    final content = Padding(
      padding: padding ?? const EdgeInsets.all(16),
      child: child,
    );
    return Card(
      child: onTap == null
          ? content
          : InkWell(
              onTap: onTap,
              borderRadius: BorderRadius.circular(12),
              child: content,
            ),
    );
  }
}

class TichStatusChip extends StatelessWidget {
  const TichStatusChip(this.label, {super.key, this.tone = TichChipTone.neutral});

  final String label;
  final TichChipTone tone;

  @override
  Widget build(BuildContext context) {
    final colors = switch (tone) {
      TichChipTone.success => (TichColors.successSoft, TichColors.successText),
      TichChipTone.info => (TichColors.cautionSoft, TichColors.cautionText),
      TichChipTone.warning => (TichColors.warningSoft, TichColors.warningText),
      TichChipTone.danger => (TichColors.errorSoft, TichColors.errorDark),
      TichChipTone.neutral => (TichColors.statusSoft, TichColors.statusText),
    };

    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
      decoration: BoxDecoration(
        color: colors.$1,
        borderRadius: BorderRadius.circular(999),
      ),
      child: Text(
        label.replaceAll('_', ' '),
        maxLines: 1,
        overflow: TextOverflow.ellipsis,
        softWrap: false,
        style: tichUi(
          fontSize: 11,
          fontWeight: FontWeight.w700,
          color: colors.$2,
        ),
      ),
    );
  }
}

enum TichChipTone { success, info, warning, danger, neutral }

/// Dropdown with [isExpanded] and ellipsized selected label (avoids overflow stripes).
class TichDropdownFormField<T> extends StatelessWidget {
  const TichDropdownFormField({
    super.key,
    this.value,
    this.initialValue,
    required this.items,
    required this.onChanged,
    this.decoration,
    this.hint,
    this.validator,
  });

  final T? value;
  final T? initialValue;
  final List<DropdownMenuItem<T>> items;
  final ValueChanged<T?>? onChanged;
  final InputDecoration? decoration;
  final Widget? hint;
  final FormFieldValidator<T>? validator;

  static String _labelFromWidget(Widget? child) {
    if (child is Text) {
      return child.data ?? child.textSpan?.toPlainText() ?? '';
    }
    return '';
  }

  @override
  Widget build(BuildContext context) {
    return DropdownButtonFormField<T>(
      isExpanded: true,
      value: value,
      initialValue: initialValue,
      decoration: decoration,
      hint: hint,
      items: items,
      onChanged: onChanged,
      validator: validator,
      selectedItemBuilder: (context) {
        return items
            .map(
              (item) => Align(
                alignment: AlignmentDirectional.centerStart,
                child: Text(
                  _labelFromWidget(item.child),
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis,
                ),
              ),
            )
            .toList();
      },
    );
  }
}

class TichEmpty extends StatelessWidget {
  const TichEmpty(this.message, {super.key});

  final String message;

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 24),
      child: Text(
        message,
        textAlign: TextAlign.center,
        style: tichUi(
          color: Theme.of(context).colorScheme.onSurface.withValues(alpha: 0.65),
        ),
      ),
    );
  }
}

class TichErrorRefresh extends StatelessWidget {
  const TichErrorRefresh({
    super.key,
    required this.message,
    required this.onRetry,
  });

  final String message;
  final VoidCallback onRetry;

  @override
  Widget build(BuildContext context) {
    final scheme = Theme.of(context).colorScheme;

    return Center(
      child: Padding(
        padding: const EdgeInsets.all(24),
        child: TichCard(
          padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 24),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              Icon(Icons.cloud_off_outlined, color: scheme.error, size: 32),
              const SizedBox(height: 12),
              Text(
                'This page did not load',
                textAlign: TextAlign.center,
                style: TichTheme.display(
                  fontSize: 16,
                  color: scheme.onSurface,
                ),
              ),
              const SizedBox(height: 8),
              Text(
                tichDescribeError(message),
                textAlign: TextAlign.center,
                style: tichUi(
                  fontSize: 14,
                  color: TichColors.neutralMuted,
                ),
              ),
              const SizedBox(height: 18),
              AdaptiveFilledButton(
                onPressed: onRetry,
                child: const Text('Try again'),
              ),
            ],
          ),
        ),
      ),
    );
  }
}

TichChipTone toneForStatus(String? status) {
  final s = (status ?? '').toLowerCase();
  if (s.contains('approv') || s.contains('active') || s == 'accepted') {
    return TichChipTone.success;
  }
  if (s.contains('pending') || s.contains('review') || s.contains('expir')) {
    return TichChipTone.warning;
  }
  if (s.contains('reject') || s.contains('cancel') || s.contains('declin')) {
    return TichChipTone.danger;
  }
  return TichChipTone.info;
}
