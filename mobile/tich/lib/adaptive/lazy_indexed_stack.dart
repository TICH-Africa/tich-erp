import 'package:flutter/widgets.dart';

import '../ui/widgets/tich_tab_active_scope.dart';

/// Only mounts the **selected** tab (inactive tabs are torn down to save memory).
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

  void _syncActiveTab() {
    for (var i = 0; i < _activated.length; i++) {
      _activated[i] = i == widget.index;
    }
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
      _syncActiveTab();
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
