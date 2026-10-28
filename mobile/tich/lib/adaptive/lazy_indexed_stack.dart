import 'package:flutter/widgets.dart';

import '../ui/widgets/tich_tab_active_scope.dart';

/// Mounts the selected tab; once a tab has been opened it stays in the tree so
/// switching back does not replay cold loads (API, PDF decode, form state).
class LazyIndexedStack extends StatefulWidget {
  const LazyIndexedStack({
    super.key,
    required this.index,
    required this.children,
  });

  final int index;
  final List<Widget> children;

  @override
  State<LazyIndexedStack> createState() => _LazyIndexedStackState();
}

class _LazyIndexedStackState extends State<LazyIndexedStack> {
  late List<bool> _activated;

  @override
  void initState() {
    super.initState();
    _activated = List<bool>.generate(
      widget.children.length,
      (i) => i == widget.index,
    );
  }

  void _markTabVisited() {
    _activated[widget.index] = true;
  }

  @override
  void didUpdateWidget(covariant LazyIndexedStack oldWidget) {
    super.didUpdateWidget(oldWidget);
    if (oldWidget.children.length != widget.children.length) {
      _activated = List<bool>.generate(
        widget.children.length,
        (i) => i == widget.index,
      );
    } else if (oldWidget.index != widget.index) {
      _markTabVisited();
    }
  }

  @override
  Widget build(BuildContext context) {
    return IndexedStack(
      index: widget.index,
      sizing: StackFit.expand,
      children: [
        for (var i = 0; i < widget.children.length; i++)
          _activated[i]
              ? RepaintBoundary(
                  child: TichTabActiveScope(
                    activeIndex: widget.index,
                    tabIndex: i,
                    child: widget.children[i],
                  ),
                )
              : const SizedBox.shrink(),
      ],
    );
  }
}
