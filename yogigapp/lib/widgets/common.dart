import 'package:flutter/material.dart';

import '../core/api_client.dart';
import '../core/app_state.dart';
import '../core/format.dart';
import '../core/models.dart';
import '../core/theme.dart';

/// Loads data with [load] and rebuilds with [builder]; handles loading, errors and pull-to-refresh.
class AsyncView<T> extends StatefulWidget {
  const AsyncView({super.key, required this.load, required this.builder});

  final Future<T> Function() load;
  final Widget Function(BuildContext context, T data, Future<void> Function() reload) builder;

  @override
  State<AsyncView<T>> createState() => AsyncViewState<T>();
}

class AsyncViewState<T> extends State<AsyncView<T>> {
  T? _data;
  Object? _error;
  bool _loading = true;

  @override
  void initState() {
    super.initState();
    reload();
  }

  Future<void> reload() async {
    setState(() {
      _loading = _data == null;
      _error = null;
    });
    try {
      final data = await widget.load();
      if (!mounted) {
        return;
      }
      setState(() {
        _data = data;
        _loading = false;
      });
    } catch (error) {
      if (!mounted) {
        return;
      }
      setState(() {
        _error = error;
        _loading = false;
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    if (_loading) {
      return const Center(child: CircularProgressIndicator());
    }
    if (_error != null && _data == null) {
      return ErrorState(message: _error.toString(), onRetry: reload);
    }
    return RefreshIndicator(
      onRefresh: reload,
      child: widget.builder(context, _data as T, reload),
    );
  }
}

class ErrorState extends StatelessWidget {
  const ErrorState({super.key, required this.message, this.onRetry});

  final String message;
  final VoidCallback? onRetry;

  @override
  Widget build(BuildContext context) {
    return Center(
      child: Padding(
        padding: const EdgeInsets.all(32),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            const Icon(Icons.cloud_off_rounded, size: 56, color: BrandColors.sage),
            const SizedBox(height: 16),
            Text(message, textAlign: TextAlign.center, style: Theme.of(context).textTheme.bodyLarge),
            if (onRetry != null) ...[
              const SizedBox(height: 16),
              FilledButton.icon(onPressed: onRetry, icon: const Icon(Icons.refresh), label: const Text('Try again')),
            ],
          ],
        ),
      ),
    );
  }
}

class EmptyState extends StatelessWidget {
  const EmptyState({super.key, required this.icon, required this.message, this.action});

  final IconData icon;
  final String message;
  final Widget? action;

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 40, horizontal: 24),
      child: Column(
        children: [
          Icon(icon, size: 48, color: BrandColors.sage.withValues(alpha: 0.7)),
          const SizedBox(height: 12),
          Text(message, textAlign: TextAlign.center, style: TextStyle(color: Colors.grey.shade700)),
          if (action != null) ...[const SizedBox(height: 16), action!],
        ],
      ),
    );
  }
}

/// A scrollable page body that works with [RefreshIndicator] even when content is short.
class PageList extends StatelessWidget {
  const PageList({super.key, required this.children, this.padding = const EdgeInsets.all(16)});

  final List<Widget> children;
  final EdgeInsets padding;

  @override
  Widget build(BuildContext context) {
    return ListView(
      physics: const AlwaysScrollableScrollPhysics(),
      padding: padding,
      children: children,
    );
  }
}

class NetImage extends StatelessWidget {
  const NetImage(this.url, {super.key, this.height, this.width, this.radius = 12, this.fit = BoxFit.cover, this.headers});

  final String? url;
  final double? height;
  final double? width;
  final double radius;
  final BoxFit fit;
  final Map<String, String>? headers;

  @override
  Widget build(BuildContext context) {
    final placeholder = Container(
      height: height,
      width: width,
      color: BrandColors.teal.withValues(alpha: 0.08),
      child: const Icon(Icons.home_work_outlined, color: BrandColors.sage, size: 36),
    );

    if (url == null || url!.isEmpty || url!.toLowerCase().endsWith('.svg')) {
      return ClipRRect(borderRadius: BorderRadius.circular(radius), child: placeholder);
    }

    return ClipRRect(
      borderRadius: BorderRadius.circular(radius),
      child: Image.network(
        url!,
        height: height,
        width: width,
        fit: fit,
        headers: headers,
        // On the web, fall back to an <img> element for images served without CORS headers.
        webHtmlElementStrategy: WebHtmlElementStrategy.fallback,
        errorBuilder: (_, _, _) => placeholder,
        loadingBuilder: (context, child, progress) => progress == null ? child : placeholder,
      ),
    );
  }
}

/// The brand mark: the admin-uploaded logo when it is a bitmap, otherwise the wordmark.
class BrandLogo extends StatelessWidget {
  const BrandLogo({super.key, this.light = false, this.size = 1});

  final bool light;
  final double size;

  @override
  Widget build(BuildContext context) {
    final config = AppScope.of(context).config;
    final logo = config.logoUrl;

    if (logo != null && !logo.toLowerCase().endsWith('.svg')) {
      return Image.network(logo, height: 40 * size, webHtmlElementStrategy: WebHtmlElementStrategy.fallback, errorBuilder: (_, _, _) => _wordmark(config));
    }
    return _wordmark(config);
  }

  Widget _wordmark(SiteConfig config) {
    final name = config.siteName;
    final split = name.indexOf(' ');
    final first = split > 0 ? name.substring(0, split) : name;
    final rest = split > 0 ? name.substring(split + 1) : '';

    return Row(
      mainAxisSize: MainAxisSize.min,
      children: [
        Container(
          width: 38 * size,
          height: 38 * size,
          decoration: BoxDecoration(
            color: light ? Colors.white : BrandColors.teal,
            borderRadius: BorderRadius.circular(10 * size),
          ),
          child: Icon(Icons.cleaning_services_rounded, color: BrandColors.gold, size: 22 * size),
        ),
        SizedBox(width: 10 * size),
        Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          mainAxisSize: MainAxisSize.min,
          children: [
            Text(first,
                style: TextStyle(
                  fontWeight: FontWeight.w900,
                  fontSize: 20 * size,
                  height: 1,
                  color: light ? Colors.white : BrandColors.teal,
                )),
            if (rest.isNotEmpty)
              Text(rest,
                  style: TextStyle(
                    fontWeight: FontWeight.w600,
                    fontSize: 12 * size,
                    color: BrandColors.gold,
                    letterSpacing: 0.4,
                  )),
          ],
        ),
      ],
    );
  }
}

class StatusChip extends StatelessWidget {
  const StatusChip(this.label, {super.key, this.color = BrandColors.sage});

  final String label;
  final Color color;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
      decoration: BoxDecoration(color: color.withValues(alpha: 0.12), borderRadius: BorderRadius.circular(20)),
      child: Text(label, style: TextStyle(color: color, fontWeight: FontWeight.w600, fontSize: 12)),
    );
  }
}

Color bookingStatusColor(Booking booking) {
  if (booking.isAwaitingClientApproval) {
    return BrandColors.warning;
  }
  return switch (booking.status) {
    'open' => BrandColors.info,
    'matched' => BrandColors.sage,
    'in_progress' => BrandColors.warning,
    'completed' => BrandColors.success,
    'cancelled' => BrandColors.danger,
    _ => Colors.grey,
  };
}

Color paymentStatusColor(String status) => switch (status) {
      'unpaid' || 'refund_due' || 'failed' => BrandColors.danger,
      'pending' => BrandColors.warning,
      'paid' || 'successful' => BrandColors.success,
      'released' => BrandColors.teal,
      'refunded' => Colors.grey,
      _ => Colors.grey,
    };

class StatCard extends StatelessWidget {
  const StatCard({super.key, required this.label, required this.value, required this.icon, this.color = BrandColors.teal, this.onTap});

  final String label;
  final String value;
  final IconData icon;
  final Color color;
  final VoidCallback? onTap;

  @override
  Widget build(BuildContext context) {
    return Card(
      child: InkWell(
        borderRadius: BorderRadius.circular(16),
        onTap: onTap,
        child: Padding(
          padding: const EdgeInsets.all(14),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Container(
                padding: const EdgeInsets.all(8),
                decoration: BoxDecoration(color: color.withValues(alpha: 0.12), borderRadius: BorderRadius.circular(10)),
                child: Icon(icon, color: color, size: 20),
              ),
              const SizedBox(height: 10),
              FittedBox(
                fit: BoxFit.scaleDown,
                alignment: Alignment.centerLeft,
                child: Text(value, style: const TextStyle(fontSize: 20, fontWeight: FontWeight.w800)),
              ),
              const SizedBox(height: 2),
              Text(label, maxLines: 2, overflow: TextOverflow.ellipsis, style: TextStyle(color: Colors.grey.shade700, fontSize: 12)),
            ],
          ),
        ),
      ),
    );
  }
}

/// Two-column grid of [StatCard]s that stays readable on narrow phones.
class StatGrid extends StatelessWidget {
  const StatGrid({super.key, required this.children});

  final List<Widget> children;

  @override
  Widget build(BuildContext context) {
    return LayoutBuilder(builder: (context, constraints) {
      final columns = constraints.maxWidth > 600 ? 4 : 2;
      final width = (constraints.maxWidth - (columns - 1) * 12) / columns;
      return Wrap(
        spacing: 12,
        runSpacing: 12,
        children: [for (final child in children) SizedBox(width: width, child: child)],
      );
    });
  }
}

class SectionTitle extends StatelessWidget {
  const SectionTitle(this.title, {super.key, this.subtitle, this.trailing});

  final String title;
  final String? subtitle;
  final Widget? trailing;

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.only(top: 20, bottom: 10),
      child: Row(
        children: [
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(title, style: Theme.of(context).textTheme.titleLarge?.copyWith(fontWeight: FontWeight.w800, color: BrandColors.tealDark)),
                if (subtitle != null) Text(subtitle!, style: TextStyle(color: Colors.grey.shade700)),
              ],
            ),
          ),
          ?trailing,
        ],
      ),
    );
  }
}

class InfoRow extends StatelessWidget {
  const InfoRow(this.label, this.value, {super.key, this.icon});

  final String label;
  final String? value;
  final IconData? icon;

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 6),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          if (icon != null) ...[Icon(icon, size: 18, color: BrandColors.sage), const SizedBox(width: 8)],
          SizedBox(width: 120, child: Text(label, style: TextStyle(color: Colors.grey.shade700))),
          Expanded(child: SelectableText(value == null || value!.isEmpty ? '-' : value!, style: const TextStyle(fontWeight: FontWeight.w600))),
        ],
      ),
    );
  }
}

class Panel extends StatelessWidget {
  const Panel({super.key, required this.children, this.title, this.color});

  final String? title;
  final List<Widget> children;
  final Color? color;

  @override
  Widget build(BuildContext context) {
    return Card(
      color: color,
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            if (title != null) ...[
              Text(title!, style: const TextStyle(fontWeight: FontWeight.w800, fontSize: 16, color: BrandColors.tealDark)),
              const SizedBox(height: 8),
            ],
            ...children,
          ],
        ),
      ),
    );
  }
}

/// Summary card for a booking or gig in lists.
class BookingTile extends StatelessWidget {
  const BookingTile({super.key, required this.booking, this.onTap, this.showClient = false, this.amount});

  final Booking booking;
  final VoidCallback? onTap;
  final bool showClient;
  final double? amount;

  @override
  Widget build(BuildContext context) {
    final currency = AppScope.of(context).config.currency;

    return Card(
      child: InkWell(
        borderRadius: BorderRadius.circular(16),
        onTap: onTap,
        child: Padding(
          padding: const EdgeInsets.all(14),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Row(
                children: [
                  Expanded(
                    child: Text(booking.title, style: const TextStyle(fontWeight: FontWeight.w700, fontSize: 16), maxLines: 1, overflow: TextOverflow.ellipsis),
                  ),
                  Text(money(amount ?? booking.budget, currency), style: const TextStyle(fontWeight: FontWeight.w800, color: BrandColors.teal)),
                ],
              ),
              const SizedBox(height: 6),
              Row(children: [
                const Icon(Icons.place_outlined, size: 16, color: BrandColors.sage),
                const SizedBox(width: 4),
                Expanded(child: Text(booking.location, maxLines: 1, overflow: TextOverflow.ellipsis)),
              ]),
              if (booking.scheduleLabel != null)
                Padding(
                  padding: const EdgeInsets.only(top: 4),
                  child: Row(children: [
                    const Icon(Icons.event_outlined, size: 16, color: BrandColors.sage),
                    const SizedBox(width: 4),
                    Text(booking.scheduleLabel!),
                  ]),
                ),
              if (showClient && booking.client != null)
                Padding(
                  padding: const EdgeInsets.only(top: 4),
                  child: Row(children: [
                    const Icon(Icons.person_outline, size: 16, color: BrandColors.sage),
                    const SizedBox(width: 4),
                    Text(booking.client!.name),
                  ]),
                ),
              const SizedBox(height: 10),
              Wrap(spacing: 6, runSpacing: 6, children: [
                if (booking.reference.isNotEmpty) StatusChip(booking.reference, color: Colors.blueGrey),
                StatusChip(booking.statusLabel, color: bookingStatusColor(booking)),
                StatusChip(booking.paymentStatusLabel, color: paymentStatusColor(booking.paymentStatus)),
              ]),
            ],
          ),
        ),
      ),
    );
  }
}

void showMessage(BuildContext context, String message, {bool error = false}) {
  ScaffoldMessenger.of(context)
    ..hideCurrentSnackBar()
    ..showSnackBar(SnackBar(
      content: Text(message),
      backgroundColor: error ? BrandColors.danger : BrandColors.tealDark,
      behavior: SnackBarBehavior.floating,
    ));
}

/// Runs an API call, shows its message (or error) and reports whether it succeeded.
Future<bool> runAction(BuildContext context, Future<Map<String, dynamic>> Function() action, {String? success}) async {
  try {
    final response = await action();
    if (context.mounted) {
      showMessage(context, success ?? response['message']?.toString() ?? 'Done.');
    }
    return true;
  } on ApiException catch (error) {
    if (context.mounted) {
      showMessage(context, error.message, error: true);
    }
    return false;
  }
}

Future<bool> confirm(BuildContext context, {required String title, required String message, String action = 'Confirm', bool destructive = false}) async {
  final result = await showDialog<bool>(
    context: context,
    builder: (context) => AlertDialog(
      title: Text(title),
      content: Text(message),
      actions: [
        TextButton(onPressed: () => Navigator.pop(context, false), child: const Text('Cancel')),
        FilledButton(
          style: destructive ? FilledButton.styleFrom(backgroundColor: BrandColors.danger) : null,
          onPressed: () => Navigator.pop(context, true),
          child: Text(action),
        ),
      ],
    ),
  );
  return result ?? false;
}

/// Asks for a single line of text, e.g. a payout reference or rejection note.
Future<String?> promptText(BuildContext context, {required String title, required String label, bool required = false, String action = 'Save'}) {
  final controller = TextEditingController();
  return showDialog<String>(
    context: context,
    builder: (context) => StatefulBuilder(builder: (context, setState) {
      return AlertDialog(
        title: Text(title),
        content: TextField(
          controller: controller,
          autofocus: true,
          decoration: InputDecoration(labelText: label),
          onChanged: (_) => setState(() {}),
        ),
        actions: [
          TextButton(onPressed: () => Navigator.pop(context), child: const Text('Cancel')),
          FilledButton(
            onPressed: required && controller.text.trim().isEmpty ? null : () => Navigator.pop(context, controller.text.trim()),
            child: Text(action),
          ),
        ],
      );
    }),
  );
}

/// Shows a full-width primary button that disables itself and shows progress while [onPressed] runs.
class BusyButton extends StatefulWidget {
  const BusyButton({super.key, required this.label, required this.onPressed, this.icon, this.color, this.outlined = false});

  final String label;
  final Future<void> Function()? onPressed;
  final IconData? icon;
  final Color? color;
  final bool outlined;

  @override
  State<BusyButton> createState() => _BusyButtonState();
}

class _BusyButtonState extends State<BusyButton> {
  bool _busy = false;

  Future<void> _run() async {
    setState(() => _busy = true);
    try {
      await widget.onPressed!();
    } finally {
      if (mounted) {
        setState(() => _busy = false);
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    final onPressed = widget.onPressed == null || _busy ? null : _run;
    final child = _busy
        ? const SizedBox(height: 20, width: 20, child: CircularProgressIndicator(strokeWidth: 2))
        : Row(mainAxisSize: MainAxisSize.min, children: [
            if (widget.icon != null) ...[Icon(widget.icon, size: 20), const SizedBox(width: 8)],
            Flexible(child: Text(widget.label, textAlign: TextAlign.center)),
          ]);

    if (widget.outlined) {
      return OutlinedButton(
        style: widget.color != null ? OutlinedButton.styleFrom(foregroundColor: widget.color, side: BorderSide(color: widget.color!)) : null,
        onPressed: onPressed,
        child: child,
      );
    }
    return FilledButton(
      style: widget.color != null ? FilledButton.styleFrom(backgroundColor: widget.color) : null,
      onPressed: onPressed,
      child: child,
    );
  }
}

class Avatar extends StatelessWidget {
  const Avatar(this.user, {super.key, this.radius = 22});

  final AppUser user;
  final double radius;

  @override
  Widget build(BuildContext context) {
    return CircleAvatar(
      radius: radius,
      backgroundColor: BrandColors.teal.withValues(alpha: 0.12),
      child: Text(user.initials, style: TextStyle(color: BrandColors.teal, fontWeight: FontWeight.w700, fontSize: radius * 0.7)),
    );
  }
}
