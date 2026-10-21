import 'package:flutter/widgets.dart';

import '../ui/widgets/tich_tab_active_scope.dart';

/// Like [IndexedStack], but only mounts a child the first time it is selected.
/// Avoids parallel API loads from every tab on cold start.
class LazyIndexedStack extends StatefulWidget {
  const LazyIndexedStack({
    super.key,
    required this.index,
    required this.children,
    this.preloadAllAfter = const Duration(milliseconds: 350),
  });

  final int index;
  final List<Widget> children;

  /// Mount every tab in the background so the first switch feels instant.
  final Duration preloadAllAfter;

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
    if (widget.preloadAllAfter > Duration.zero) {
      Future<void>.delayed(widget.preloadAllAfter, () {
        if (!mounted) return;
        var changed = false;
        for (var i = 0; i < _activated.length; i++) {
          if (!_activated[i]) {
            _activated[i] = true;
            changed = true;
          }
        }
        if (changed) setState(() {});
      });
    }
  }

  @override
  void didUpdateWidget(covariant LazyIndexedStack oldWidget) {
    super.didUpdateWidget(oldWidget);
    if (oldWidget.children.length != widget.children.length) {
      _activated = List<bool>.generate(
        widget.children.length,
        (i) =>
            (i < _activated.length && _activated[i]) || i == widget.index,
      );
    } else if (!_activated[widget.index]) {
      _activated[widget.index] = true;
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
