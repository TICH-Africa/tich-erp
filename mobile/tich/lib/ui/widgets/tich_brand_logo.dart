import 'package:flutter/material.dart';

import '../../theme/tich_theme.dart';

/// Bundled brand mark (same file drives launcher + notification icons).
abstract final class TichBrandAssets {
  static const String logo = 'assets/images/logo.png';

  /// Android small icon resource (generated from [logo] via flutter_launcher_icons).
  static const String androidNotificationIcon = '@mipmap/ic_launcher';
}

/// Square logo only — headers, drawer, notification branding.
class TichBrandMark extends StatelessWidget {
  const TichBrandMark({super.key, this.size = 40});

  final double size;

  @override
  Widget build(BuildContext context) {
    return Image.asset(
      TichBrandAssets.logo,
      width: size,
      height: size,
      fit: BoxFit.contain,
      semanticLabel: 'TICH in Africa',
    );
  }
}

/// Logo mark with wordmark (login, unlock).
class TichBrandLogo extends StatelessWidget {
  const TichBrandLogo({
    super.key,
    this.markSize = 72,
    this.wordmark = 'TICH in Africa',
    this.wordmarkFontSize = 32,
    this.showWordmark = true,
  });

  final double markSize;
  final String wordmark;
  final double wordmarkFontSize;
  final bool showWordmark;

  @override
  Widget build(BuildContext context) {
    return Column(
      mainAxisSize: MainAxisSize.min,
      children: [
        TichBrandMark(size: markSize),
        if (showWordmark) ...[
          const SizedBox(height: 10),
          Text(
            wordmark,
            textAlign: TextAlign.center,
            style: TichTheme.display(
              fontSize: wordmarkFontSize,
              color: TichColors.green,
            ),
          ),
        ],
      ],
    );
  }
}
