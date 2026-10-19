import 'package:flutter/material.dart';

import '../../config/api_config.dart';
import '../../theme/tich_theme.dart';

/// The server's own brand mark — the same file the web login renders — with the
/// wordmark under it while it is arriving, or if the server cannot be reached.
class TichBrandLogo extends StatelessWidget {
  const TichBrandLogo({
    super.key,
    this.markSize = 72,
    this.wordmark = 'TICH in Africa',
    this.wordmarkFontSize = 32,
  });

  final double markSize;
  final String wordmark;
  final double wordmarkFontSize;

  /// The circular badge the web uses, falling back to the wide logo there.
  static String markUrl({String file = 'logo-mark.png'}) =>
      '${ApiConfig.baseUrl}/images/$file';

  @override
  Widget build(BuildContext context) {
    return Column(
      mainAxisSize: MainAxisSize.min,
      children: [
        Image.network(
          markUrl(),
          width: markSize,
          height: markSize,
          fit: BoxFit.contain,
          // Offline or a server without the file: the wordmark alone still reads.
          errorBuilder: (context, error, stackTrace) => const SizedBox.shrink(),
          loadingBuilder: (context, child, progress) =>
              progress == null ? child : SizedBox(height: markSize),
        ),
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
    );
  }
}
