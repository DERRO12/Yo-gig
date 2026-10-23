import 'package:flutter/material.dart';

import '../core/models.dart';
import 'common.dart';

/// Infinite list over a paginated Laravel resource collection (`data` + `meta`).
class PagedList<T> extends StatefulWidget {
  const PagedList({
    super.key,
    required this.fetch,
    required this.parse,
    required this.itemBuilder,
    this.header,
    this.emptyMessage = 'Nothing here yet.',
    this.emptyIcon = Icons.inbox_outlined,
  });

  final Future<Map<String, dynamic>> Function(int page) fetch;
  final T Function(Map<String, dynamic>) parse;
  final Widget Function(BuildContext context, T item, Future<void> Function() reload) itemBuilder;
  final Widget? header;
  final String emptyMessage;
  final IconData emptyIcon;

  @override
  State<PagedList<T>> createState() => PagedListState<T>();
}

class PagedListState<T> extends State<PagedList<T>> {
  final List<T> _items = [];
  int _page = 0;
  bool _hasMore = true;
  bool _loading = false;
  String? _error;

  @override
  void initState() {
    super.initState();
    reload();
  }

  Future<void> reload() async {
    setState(() {
      _items.clear();
      _page = 0;
      _hasMore = true;
      _error = null;
    });
    await _loadMore();
  }

  Future<void> _loadMore() async {
    if (_loading || !_hasMore) {
      return;
    }
    setState(() => _loading = true);
    try {
      final response = await widget.fetch(_page + 1);
      final items = listOf(response['data'], widget.parse);
      final meta = response['meta'];
      if (!mounted) {
        return;
      }
      setState(() {
        _page++;
        _items.addAll(items);
        _hasMore = meta is Map && (meta['current_page'] as num? ?? 1) < (meta['last_page'] as num? ?? 1);
        _loading = false;
      });
    } catch (error) {
      if (!mounted) {
        return;
      }
      setState(() {
        _error = error.toString();
        _loading = false;
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    return RefreshIndicator(
      onRefresh: reload,
      child: NotificationListener<ScrollNotification>(
        onNotification: (notification) {
          if (notification.metrics.extentAfter < 300) {
            _loadMore();
          }
          return false;
        },
        child: ListView(
          physics: const AlwaysScrollableScrollPhysics(),
          padding: const EdgeInsets.all(16),
          children: [
            ?widget.header,
            for (final item in _items)
              Padding(padding: const EdgeInsets.only(bottom: 10), child: widget.itemBuilder(context, item, reload)),
            if (_loading) const Padding(padding: EdgeInsets.all(24), child: Center(child: CircularProgressIndicator())),
            if (_error != null) ErrorState(message: _error!, onRetry: reload),
            if (!_loading && _error == null && _items.isEmpty) EmptyState(icon: widget.emptyIcon, message: widget.emptyMessage),
          ],
        ),
      ),
    );
  }
}

/// A horizontal row of filter chips; the empty key means "all".
class FilterChips extends StatelessWidget {
  const FilterChips({super.key, required this.options, required this.selected, required this.onSelected});

  final Map<String, String> options;
  final String selected;
  final ValueChanged<String> onSelected;

  @override
  Widget build(BuildContext context) {
    return SingleChildScrollView(
      scrollDirection: Axis.horizontal,
      child: Row(children: [
        for (final entry in options.entries)
          Padding(
            padding: const EdgeInsets.only(right: 8),
            child: ChoiceChip(label: Text(entry.value), selected: selected == entry.key, onSelected: (_) => onSelected(entry.key)),
          ),
      ]),
    );
  }
}
