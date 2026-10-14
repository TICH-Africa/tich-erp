import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';

import '../../theme/tich_theme.dart';

/// Soft branded header used on portal home screens (not a flat card row).
class PortalHero extends StatelessWidget {
  const PortalHero({
    super.key,
    required this.title,
    this.subtitle,
    this.trailing,
    this.footer,
    this.accent = TichColors.blue,
  });

  final String title;
  final String? subtitle;
  final Widget? trailing;
  final Widget? footer;
  final Color accent;

  @override
  Widget build(BuildContext context) {
    return Container(
      decoration: BoxDecoration(
        borderRadius: BorderRadius.circular(18),
        gradient: LinearGradient(
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
          colors: [
            accent,
            Color.lerp(accent, TichColors.green, 0.45)!,
          ],
        ),
        boxShadow: [
          BoxShadow(
            color: accent.withValues(alpha: 0.28),
            blurRadius: 18,
            offset: const Offset(0, 8),
          ),
        ],
      ),
      child: Stack(
        children: [
          Positioned(
            right: -18,
            top: -22,
            child: Container(
              width: 110,
              height: 110,
              decoration: BoxDecoration(
                shape: BoxShape.circle,
                color: Colors.white.withValues(alpha: 0.10),
              ),
            ),
          ),
          Positioned(
            right: 36,
            bottom: -28,
            child: Container(
              width: 72,
              height: 72,
              decoration: BoxDecoration(
                shape: BoxShape.circle,
                color: Colors.white.withValues(alpha: 0.08),
              ),
            ),
          ),
          Padding(
            padding: const EdgeInsets.fromLTRB(18, 18, 18, 16),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Row(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(
                            title,
                            style: GoogleFonts.merriweather(
                              color: Colors.white,
                              fontSize: 20,
                              fontWeight: FontWeight.w700,
                              height: 1.2,
                            ),
                          ),
                          if (subtitle != null && subtitle!.isNotEmpty) ...[
                            const SizedBox(height: 6),
                            Text(
                              subtitle!,
                              style: tichUi(
                                color: Colors.white.withValues(alpha: 0.88),
                                fontSize: 13,
                                height: 1.35,
                              ),
                            ),
                          ],
                        ],
                      ),
                    ),
                    if (trailing != null) ...[
                      const SizedBox(width: 12),
                      trailing!,
                    ],
                  ],
                ),
                if (footer != null) ...[
                  const SizedBox(height: 14),
                  footer!,
                ],
              ],
            ),
          ),
        ],
      ),
    );
  }
}

class PortalHeroPill extends StatelessWidget {
  const PortalHeroPill({
    super.key,
    required this.label,
    this.icon,
    this.onTap,
  });

  final String label;
  final IconData? icon;
  final VoidCallback? onTap;

  @override
  Widget build(BuildContext context) {
    final child = Container(
      padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
      decoration: BoxDecoration(
        color: Colors.white.withValues(alpha: 0.16),
        borderRadius: BorderRadius.circular(12),
        border: Border.all(color: Colors.white.withValues(alpha: 0.22)),
      ),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          if (icon != null) ...[
            Icon(icon, size: 16, color: Colors.white),
            const SizedBox(width: 6),
          ],
          Flexible(
            child: Text(
              label,
              style: tichUi(
                color: Colors.white,
                fontSize: 12,
                fontWeight: FontWeight.w700,
              ),
            ),
          ),
        ],
      ),
    );
    if (onTap == null) return child;
    return Material(
      color: Colors.transparent,
      child: InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(12),
        child: child,
      ),
    );
  }
}
