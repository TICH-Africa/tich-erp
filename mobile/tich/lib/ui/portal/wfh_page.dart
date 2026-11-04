import 'package:flutter/material.dart';

import '../../adaptive/adaptive.dart';
import '../widgets/tich_widgets.dart';

/// Work from home is disabled server-side; menu entry shows a placeholder only.
class WfhPage extends StatelessWidget {
  const WfhPage({super.key});

  @override
  Widget build(BuildContext context) {
    return AdaptiveScaffold(
      title: 'Work from home',
      body: ListView(
        padding: const EdgeInsets.all(16),
        children: const [
          TichCard(child: TichEmpty('This page is unavailable.')),
        ],
      ),
    );
  }
}
