import 'package:flutter/material.dart';

import '../../config/api_config.dart';
import '../../theme/tich_theme.dart';
import 'tich_cached_network_image.dart';

Uri? employeePhotoUri(Object? value) {
  final raw = value?.toString().trim() ?? '';
  if (raw.isEmpty) return null;

  final uri = Uri.parse(raw);
  if (uri.hasScheme && uri.hasAuthority) return uri;

  return Uri.parse(ApiConfig.baseUrl).resolveUri(uri);
}

class EmployeeAvatar extends StatelessWidget {
  const EmployeeAvatar({
    super.key,
    required this.name,
    required this.radius,
    this.photoUrl,
  });

  final String name;
  final double radius;
  final String? photoUrl;

  String get _initials {
    final parts = name
        .trim()
        .split(RegExp(r'\s+'))
        .where((part) => part.isNotEmpty);
    return parts.take(2).map((part) => part[0].toUpperCase()).join();
  }

  @override
  Widget build(BuildContext context) {
    final uri = employeePhotoUri(photoUrl);
    final fallback = Text(
      _initials,
      style: tichUi(color: TichColors.blueDark, fontWeight: FontWeight.w700),
    );

    final diameter = radius * 2;

    return CircleAvatar(
      radius: radius,
      backgroundColor: TichColors.blueLight,
      child: uri == null
          ? fallback
          : ClipOval(
              child: TichCachedNetworkImage(
                url: uri.toString(),
                width: diameter,
                height: diameter,
                maxDecodePx: 256,
                placeholder: fallback,
                error: fallback,
              ),
            ),
    );
  }
}
