import 'package:flutter/material.dart';

import '../core/app_state.dart';
import '../core/format.dart';
import '../core/models.dart';
import '../core/theme.dart';
import 'common.dart';

/// Title, reference, amount and status chips for a booking.
class BookingHeader extends StatelessWidget {
  const BookingHeader({super.key, required this.booking, this.amount, this.amountLabel = 'Total'});

  final Booking booking;
  final double? amount;
  final String amountLabel;

  @override
  Widget build(BuildContext context) {
    final currency = AppScope.of(context).config.currency;

    return Panel(children: [
      Text(booking.title, style: const TextStyle(fontSize: 20, fontWeight: FontWeight.w900, color: BrandColors.tealDark)),
      const SizedBox(height: 4),
      Text(booking.reference, style: TextStyle(color: Colors.grey.shade700, letterSpacing: 0.5)),
      const SizedBox(height: 10),
      Row(children: [
        Text('$amountLabel ', style: TextStyle(color: Colors.grey.shade700)),
        Text(money(amount ?? booking.budget, currency), style: const TextStyle(fontSize: 22, fontWeight: FontWeight.w900, color: BrandColors.teal)),
      ]),
      const SizedBox(height: 10),
      Wrap(spacing: 6, runSpacing: 6, children: [
        StatusChip(booking.statusLabel, color: bookingStatusColor(booking)),
        StatusChip(booking.paymentStatusLabel, color: paymentStatusColor(booking.paymentStatus)),
      ]),
    ]);
  }
}

/// The escrow journey: paid, maid assigned, in progress, completed, approved.
class BookingProgress extends StatelessWidget {
  const BookingProgress({super.key, required this.booking});

  final Booking booking;

  @override
  Widget build(BuildContext context) {
    if (booking.isCancelled) {
      return Panel(color: BrandColors.danger.withValues(alpha: 0.06), children: [
        Text('This booking was cancelled. ${booking.paymentStatusLabel}.', style: const TextStyle(fontWeight: FontWeight.w600)),
      ]);
    }

    final steps = <(String, bool)>[
      ('Payment held in escrow', booking.isFunded || booking.paymentStatus == 'released'),
      ('Maid assigned', booking.maid != null || !booking.isOpen),
      ('Work in progress', booking.isInProgress || booking.isCompleted),
      ('Work completed', booking.isCompleted),
      ('Approved and maid paid', booking.paymentStatus == 'released'),
    ];

    return Panel(title: 'Progress', children: [
      for (final (label, done) in steps)
        Padding(
          padding: const EdgeInsets.symmetric(vertical: 4),
          child: Row(children: [
            Icon(done ? Icons.check_circle : Icons.radio_button_unchecked, color: done ? BrandColors.success : Colors.grey.shade400, size: 22),
            const SizedBox(width: 10),
            Text(label, style: TextStyle(fontWeight: done ? FontWeight.w700 : FontWeight.w400, color: done ? Colors.black87 : Colors.grey.shade600)),
          ]),
        ),
    ]);
  }
}

class BookingDetails extends StatelessWidget {
  const BookingDetails({super.key, required this.booking, this.showMoneySplit = false});

  final Booking booking;
  final bool showMoneySplit;

  @override
  Widget build(BuildContext context) {
    final currency = AppScope.of(context).config.currency;

    return Panel(title: 'Details', children: [
      InfoRow('Service', booking.service?.name ?? booking.title, icon: Icons.cleaning_services_outlined),
      InfoRow('Quantity', '${booking.quantity} ${booking.service?.priceUnit ?? ''}'.trim(), icon: Icons.format_list_numbered),
      InfoRow('When', booking.scheduleLabel, icon: Icons.event_outlined),
      InfoRow('Location', booking.location, icon: Icons.place_outlined),
      if (booking.description != null) InfoRow('Notes', booking.description, icon: Icons.notes_outlined),
      if (showMoneySplit && booking.platformFee != null) ...[
        InfoRow('Platform fee', money(booking.platformFee, currency), icon: Icons.account_balance_outlined),
        InfoRow('Maid earns', money(booking.maidEarnings, currency), icon: Icons.savings_outlined),
      ],
      InfoRow('Booked on', shortDate(booking.createdAt), icon: Icons.history),
      if (booking.imageUrls.isNotEmpty) ...[
        const SizedBox(height: 8),
        SizedBox(
          height: 110,
          child: ListView.separated(
            scrollDirection: Axis.horizontal,
            itemCount: booking.imageUrls.length,
            separatorBuilder: (_, _) => const SizedBox(width: 8),
            itemBuilder: (context, index) => GestureDetector(
              onTap: () => showDialog<void>(
                context: context,
                builder: (_) => Dialog(child: InteractiveViewer(child: NetImage(booking.imageUrls[index], fit: BoxFit.contain))),
              ),
              child: NetImage(booking.imageUrls[index], width: 140, height: 110),
            ),
          ),
        ),
      ],
    ]);
  }
}

class PaymentHistory extends StatelessWidget {
  const PaymentHistory({super.key, required this.payments});

  final List<Payment> payments;

  @override
  Widget build(BuildContext context) {
    final currency = AppScope.of(context).config.currency;

    return Panel(title: 'Payments', children: [
      for (final payment in payments)
        ListTile(
          contentPadding: EdgeInsets.zero,
          leading: Icon(Icons.receipt_long_outlined, color: paymentStatusColor(payment.status)),
          title: Text('${payment.methodLabel} · ${money(payment.amount, currency)}'),
          subtitle: Text([
            'Ref ${payment.transactionReference}',
            dateTime(payment.createdAt),
            if (payment.verifier != null) 'Checked by ${payment.verifier!.name}',
            if (payment.notes != null) payment.notes!,
          ].join('\n')),
          trailing: StatusChip(humanize(payment.status), color: paymentStatusColor(payment.status)),
          isThreeLine: true,
        ),
    ]);
  }
}
