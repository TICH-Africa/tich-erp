import 'package:flutter/material.dart';

import '../../adaptive/adaptive.dart';
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
      TichChipTone.success => (TichColors.greenLight, TichColors.greenDark),
      TichChipTone.info => (TichColors.blueLight, TichColors.blueDark),
      TichChipTone.warning => (const Color(0xFFFFF3CD), const Color(0xFF856404)),
      TichChipTone.danger => (const Color(0xFFFDECEA), const Color(0xFFB3261E)),
      TichChipTone.neutral => (TichColors.neutral, TichColors.neutralMuted),
    };

    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
      decoration: BoxDecoration(
        color: colors.$1,
        borderRadius: BorderRadius.circular(999),
      ),
      child: Text(
        label.replaceAll('_', ' '),
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
    return Center(
      child: Padding(
        padding: const EdgeInsets.all(24),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Text(
              message,
              textAlign: TextAlign.center,
              style: TextStyle(color: Theme.of(context).colorScheme.error),
            ),
            const SizedBox(height: 16),
            AdaptiveFilledButton(onPressed: onRetry, child: const Text('Retry')),
          ],
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
