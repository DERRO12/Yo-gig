import 'package:flutter/material.dart';

import '../../core/app_state.dart';
import '../../core/format.dart';
import '../../core/models.dart';
import '../../core/theme.dart';
import '../../widgets/booking_widgets.dart';
import '../../widgets/common.dart';
import '../../widgets/paged_list.dart';
import 'admin_users.dart';

class AdminBookingsScreen extends StatefulWidget {
  const AdminBookingsScreen({super.key, this.standalone = false, this.payment = ''});

  final bool standalone;
  final String payment;

  @override
  State<AdminBookingsScreen> createState() => _AdminBookingsScreenState();
}

class _AdminBookingsScreenState extends State<AdminBookingsScreen> {
  final _listKey = GlobalKey<PagedListState<Booking>>();
  final _search = TextEditingController();
  String _status = '';
  late String _payment = widget.payment;

  @override
  void dispose() {
    _search.dispose();
    super.dispose();
  }

  void _refresh() => _listKey.currentState?.reload();

  @override
  Widget build(BuildContext context) {
    final state = AppScope.of(context);
    final config = state.config;

    final list = PagedList<Booking>(
      key: _listKey,
      fetch: (page) => state.api.get('/admin/bookings', query: {'page': page, 'status': _status, 'payment': _payment, 'q': _search.text.trim()}),
      parse: Booking.fromJson,
      emptyMessage: 'No bookings match these filters.',
      header: Column(crossAxisAlignment: CrossAxisAlignment.stretch, children: [
        TextField(
          controller: _search,
          textInputAction: TextInputAction.search,
          onSubmitted: (_) => _refresh(),
          decoration: const InputDecoration(hintText: 'Search reference, title, location or client', prefixIcon: Icon(Icons.search)),
        ),
        const SizedBox(height: 10),
        FilterChips(
          options: {'': 'All statuses', ...config.bookingStatuses},
          selected: _status,
          onSelected: (value) {
            setState(() => _status = value);
            _refresh();
          },
        ),
        const SizedBox(height: 6),
        FilterChips(
          options: {'': 'All payments', ...config.paymentStatuses},
          selected: _payment,
          onSelected: (value) {
            setState(() => _payment = value);
            _refresh();
          },
        ),
        const SizedBox(height: 12),
      ]),
      itemBuilder: (context, booking, reload) => BookingTile(
        booking: booking,
        showClient: true,
        onTap: () async {
          await Navigator.of(context).push(MaterialPageRoute(builder: (_) => AdminBookingDetailScreen(bookingId: booking.id)));
          await reload();
        },
      ),
    );

    return widget.standalone ? Scaffold(appBar: AppBar(title: const Text('Bookings')), body: list) : list;
  }
}

class AdminBookingDetailScreen extends StatelessWidget {
  const AdminBookingDetailScreen({super.key, required this.bookingId});

  final int bookingId;

  @override
  Widget build(BuildContext context) {
    final state = AppScope.of(context);
    final currency = state.config.currency;

    return Scaffold(
      appBar: AppBar(title: const Text('Booking')),
      body: AsyncView<Map<String, dynamic>>(
        load: () => state.api.get('/admin/bookings/$bookingId'),
        builder: (context, data, reload) {
          final booking = Booking.fromJson(data['data'] as Map<String, dynamic>);
          final maids = listOf(data['assignable_maids'], AppUser.fromJson);

          Future<void> post(String path, [Map<String, dynamic>? body]) async {
            if (await runAction(context, () => state.api.post(path, body))) {
              await reload();
            }
          }

          Future<void> statusAction(String action, String title, String message, {bool destructive = false}) async {
            if (await confirm(context, title: title, message: message, action: humanize(action), destructive: destructive)) {
              await post('/admin/bookings/$bookingId/status', {'action': action});
            }
          }

          final actions = <Widget>[
            if (booking.isMatched) ...[
              BusyButton(label: 'Mark started', icon: Icons.play_arrow, onPressed: () => statusAction('start', 'Start this booking?', 'Use this when the maid has begun but could not update the app.')),
              BusyButton(label: 'Unassign maid', icon: Icons.person_remove_outlined, outlined: true, onPressed: () => statusAction('unassign', 'Unassign the maid?', 'The gig goes back to the gig board.')),
            ],
            if (booking.isInProgress)
              BusyButton(label: 'Mark completed', icon: Icons.task_alt, onPressed: () => statusAction('complete', 'Mark completed?', 'The client will be asked to approve.')),
            if (booking.isAwaitingClientApproval)
              BusyButton(
                label: 'Release escrow to maid',
                icon: Icons.lock_open,
                color: BrandColors.success,
                onPressed: () => statusAction('approve', 'Release the payment?', 'Approve on the client\'s behalf and queue the maid\'s payout.'),
              ),
            if (booking.paymentStatus == 'refund_due')
              BusyButton(label: 'Mark refunded', icon: Icons.currency_exchange, onPressed: () => statusAction('refund', 'Mark as refunded?', 'Confirm you have returned the money to the client.')),
            if (!booking.isCompleted && !booking.isCancelled)
              BusyButton(
                label: 'Cancel booking',
                icon: Icons.cancel_outlined,
                outlined: true,
                color: BrandColors.danger,
                onPressed: () => statusAction('cancel', 'Cancel this booking?', 'Any payment received becomes a refund due.', destructive: true),
              ),
          ];

          return PageList(children: [
            BookingHeader(booking: booking),
            const SizedBox(height: 12),
            BookingProgress(booking: booking),
            const SizedBox(height: 12),
            if (booking.client != null)
              _PersonPanel(title: 'Client', user: booking.client!),
            const SizedBox(height: 12),
            if (booking.maid != null) ...[
              _PersonPanel(title: 'Assigned maid', user: booking.maid!),
              const SizedBox(height: 12),
            ],
            if (booking.isOpen || booking.isMatched) ...[
              _AssignMaidPanel(maids: maids, current: booking.maid?.id, onAssign: (id) => post('/admin/bookings/$bookingId/assign', {'maid_id': id})),
              const SizedBox(height: 12),
            ],
            if (actions.isNotEmpty) ...[
              Panel(title: 'Actions', children: [for (final action in actions) Padding(padding: const EdgeInsets.only(bottom: 8), child: action)]),
              const SizedBox(height: 12),
            ],
            BookingDetails(booking: booking, showMoneySplit: true),
            if (booking.payments.isNotEmpty) ...[
              const SizedBox(height: 12),
              PaymentHistory(payments: booking.payments),
            ],
            if (booking.payout != null) ...[
              const SizedBox(height: 12),
              Panel(title: 'Maid payout', children: [
                InfoRow('Amount', money(booking.payout!.amount, currency)),
                InfoRow('Status', humanize(booking.payout!.status)),
                InfoRow('Method', booking.payout!.methodLabel),
                InfoRow('Account', [booking.payout!.accountName, booking.payout!.accountNumber].whereType<String>().join(' · ')),
                if (booking.payout!.reference != null) InfoRow('Reference', booking.payout!.reference),
              ]),
            ],
            const SizedBox(height: 16),
            BusyButton(
              label: 'Delete booking',
              icon: Icons.delete_outline,
              color: BrandColors.danger,
              outlined: true,
              onPressed: () async {
                if (await confirm(context, title: 'Delete this booking?', message: 'This removes the booking and its payments permanently.', action: 'Delete', destructive: true)) {
                  if (!context.mounted) {
                    return;
                  }
                  if (await runAction(context, () => state.api.delete('/admin/bookings/$bookingId')) && context.mounted) {
                    Navigator.pop(context);
                  }
                }
              },
            ),
            const SizedBox(height: 24),
          ]);
        },
      ),
    );
  }
}

class _PersonPanel extends StatelessWidget {
  const _PersonPanel({required this.title, required this.user});

  final String title;
  final AppUser user;

  @override
  Widget build(BuildContext context) {
    return Panel(title: title, children: [
      ListTile(
        contentPadding: EdgeInsets.zero,
        leading: Avatar(user),
        title: Text(user.name, style: const TextStyle(fontWeight: FontWeight.w700)),
        subtitle: Text('${user.phoneNumber}\n${user.email}'),
        isThreeLine: true,
        trailing: const Icon(Icons.chevron_right),
        onTap: () => Navigator.of(context).push(MaterialPageRoute(builder: (_) => AdminUserDetailScreen(userId: user.id))),
      ),
    ]);
  }
}

class _AssignMaidPanel extends StatefulWidget {
  const _AssignMaidPanel({required this.maids, required this.current, required this.onAssign});

  final List<AppUser> maids;
  final int? current;
  final Future<void> Function(int maidId) onAssign;

  @override
  State<_AssignMaidPanel> createState() => _AssignMaidPanelState();
}

class _AssignMaidPanelState extends State<_AssignMaidPanel> {
  late int? _selected = widget.current;

  @override
  Widget build(BuildContext context) {
    if (widget.maids.isEmpty) {
      return const Panel(title: 'Assign a maid', children: [Text('There are no verified, active maids yet.')]);
    }

    return Panel(title: widget.current == null ? 'Assign a maid' : 'Reassign maid', children: [
      DropdownButtonFormField<int>(
        initialValue: widget.maids.any((maid) => maid.id == _selected) ? _selected : null,
        isExpanded: true,
        decoration: const InputDecoration(labelText: 'Verified maid'),
        items: [
          for (final maid in widget.maids)
            DropdownMenuItem(
              value: maid.id,
              child: Text('${maid.name}${maid.maidProfile?.serviceArea == null ? '' : ' · ${maid.maidProfile!.serviceArea}'}', overflow: TextOverflow.ellipsis),
            ),
        ],
        onChanged: (value) => setState(() => _selected = value),
      ),
      const SizedBox(height: 10),
      BusyButton(
        label: 'Assign',
        icon: Icons.person_add_alt,
        onPressed: _selected == null || _selected == widget.current ? null : () => widget.onAssign(_selected!),
      ),
    ]);
  }
}
