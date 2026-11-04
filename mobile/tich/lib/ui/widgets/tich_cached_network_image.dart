import 'package:flutter/material.dart';

/// Network image sized for [Flutter's image cache](https://api.flutter.dev/flutter/widgets/Image/Image.network.html)
/// using DPR-aware decode dimensions (avoids full-resolution decode for avatars/drawer thumbs).
class TichCachedNetworkImage extends StatelessWidget {
  const TichCachedNetworkImage({
    super.key,
    required this.url,
    this.width,
    this.height,
    this.fit = BoxFit.cover,
    this.placeholder,
    this.error,
    this.minDecodePx = 48,
    this.maxDecodePx = 512,
    this.filterQuality = FilterQuality.low,
  });

  final String url;
  final double? width;
  final double? height;
  final BoxFit fit;
  final Widget? placeholder;
  final Widget? error;
  final int minDecodePx;
  final int maxDecodePx;
  final FilterQuality filterQuality;

  @override
  Widget build(BuildContext context) {
    return LayoutBuilder(
      builder: (context, constraints) {
        final dpr = MediaQuery.devicePixelRatioOf(context);
        final layoutW = width ??
            (constraints.maxWidth.isFinite ? constraints.maxWidth : 120.0);
        final layoutH = height ??
            (constraints.maxHeight.isFinite ? constraints.maxHeight : 120.0);
        final cacheW = (layoutW * dpr).round().clamp(minDecodePx, maxDecodePx);
        final cacheH = (layoutH * dpr).round().clamp(minDecodePx, maxDecodePx);

        return Image.network(
          url,
          width: width,
          height: height,
          fit: fit,
          cacheWidth: cacheW,
          cacheHeight: cacheH,
          filterQuality: filterQuality,
          errorBuilder: (_, __, ___) => error ?? const SizedBox.shrink(),
          frameBuilder: (context, child, frame, wasSynchronouslyLoaded) {
            if (wasSynchronouslyLoaded || frame != null) return child;
            return placeholder ?? child;
          },
        );
      },
    );
  }
}
