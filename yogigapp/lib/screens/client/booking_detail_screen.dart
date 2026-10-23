import 'package:flutter/material.dart';

import '../../core/api_client.dart';
import '../../core/app_state.dart';
import '../../core/format.dart';
import '../../core/models.dart';
import '../../core/theme.dart';
import '../../widgets/booking_widgets.dart';
import '../../widgets/common.dart';

/// A client's booking: pay into escrow, follow progress, approve the work or cancel.
class BookingDetailScreen extends StatelessWidget {
  const BookingDetailScreen({super.key, required this.bookingId});

  final int bookingId;

  @override
  Widget build(BuildContext context) {
    final state = AppScope.of(context);

    return Scaffold(
      appBar: AppBar(title: const Text('Booking')),
      body: AsyncView<Booking>(
        load: () async => Booking.fromJson((await state.api.get('/client/bookings/$bookingId'))['data'] as Map<String, dynamic>),
        builder: (context, booking, reload) {
          Future<void> act(String path, [Map<String, dynamic>? body]) async {
            if (await runAction(context, () => state.api.post('/client/bookings/$bookingId/$path', body))) {
              await reload();
            }
          }

          return PageList(children: [
            BookingHeader(booking: booking),
            const SizedBox(height: 12),
            BookingProgress(booking: booking),
            const SizedBox(height: 12),
            if (booking.needsPayment) ...[
              PaymentPanel(booking: booking, onPaid: reload),
              const SizedBox(height: 12),
            ],
            if (booking.paymentStatus == 'pending')
              const Panel(
                color: Color(0xFFFFF8E6),
                children: [Text('We have received your payment details and are confirming the transaction. Your gig opens to maids once it is confirmed.')],
              ),
            if (booking.isAwaitingClientApproval) ...[
              ApprovePanel(onApprove: (rating, review) => act('approve', {'rating': rating, 'review': review})),
              const SizedBox(height: 12),
            ],
            if (booking.maid != null) ...[
              Panel(title: 'Your maid', children: [
                Row(children: [
                  Avatar(booking.maid!),
                  const SizedBox(width: 12),
                  Expanded(
                    child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                      Text(booking.maid!.name, style: const TextStyle(fontWeight: FontWeight.w700)),
                      Text(booking.maid!.phoneNumber),
                      if (booking.maid!.maidProfile?.averageRating != null)
                        Text('Rated ${booking.maid!.maidProfile!.averageRating!.toStringAsFixed(1)} / 5 · ${booking.maid!.maidProfile!.completedJobs} jobs'),
                    ]),
                  ),
                ]),
              ]),
              const SizedBox(height: 12),
            ],
            BookingDetails(booking: booking),
            if (booking.clientRating != null) ...[
              const SizedBox(height: 12),
              Panel(title: 'Your review', children: [
                Row(children: [for (var i = 0; i < 5; i++) Icon(i < booking.clientRating! ? Icons.star_rounded : Icons.star_outline_rounded, color: BrandColors.gold)]),
                if (booking.clientReview != null) Text(booking.clientReview!),
              ]),
            ],
            if (booking.payments.isNotEmpty) ...[
              const SizedBox(height: 12),
              PaymentHistory(payments: booking.payments),
            ],
            if (booking.canBeCancelled) ...[
              const SizedBox(height: 16),
              BusyButton(
                label: 'Cancel booking',
                icon: Icons.cancel_outlined,
                color: BrandColors.danger,
                outlined: true,
                onPressed: () async {
                  final message = booking.isFunded || booking.paymentStatus == 'pending'
                      ? 'Your payment will be refunded by our team.'
                      : 'This booking will be cancelled.';
                  if (await confirm(context, title: 'Cancel this booking?', message: message, action: 'Cancel booking', destructive: true)) {
                    await act('cancel');
                  }
                },
              ),
            ],
            const SizedBox(height: 24),
          ]);
        },
      ),
    );
  }
}

/// Where to send money and the form to report the transaction.
class PaymentPanel extends StatefulWidget {
  const PaymentPanel({super.key, required this.booking, required this.onPaid});

  final Booking booking;
  final Future<void> Function() onPaid;

  @override
  State<PaymentPanel> createState() => _PaymentPanelState();
}

class _PaymentPanelState extends State<PaymentPanel> {
  String _method = 'mtn_momo';
  final _phone = TextEditingController();
  final _reference = TextEditingController();
  ApiException? _error;

  @override
  void initState() {
    super.initState();
    _phone.text = AppScope.read(context).user?.phoneNumber ?? '';
  }

  @override
  void dispose() {
    _phone.dispose();
    _reference.dispose();
    super.dispose();
  }

  Future<void> _submit() async {
    setState(() => _error = null);
    try {
      final response = await AppScope.read(context).api.post('/client/bookings/${widget.booking.id}/payments', {
        'method': _method,
        'payer_phone': _method == 'bank_transfer' ? null : _phone.text.trim(),
        'transaction_reference': _reference.text.trim(),
      });
      if (!mounted) {
        return;
      }
      showMessage(context, response['message']?.toString() ?? 'Payment submitted.');
      await widget.onPaid();
    } on ApiException catch (error) {
      setState(() => _error = error);
    }
  }

  @override
  Widget build(BuildContext context) {
    final config = AppScope.of(context).config;
    final accountName = config.text('payment_account_name');

    final destination = switch (_method) {
      'mtn_momo' => 'Send to MTN Mobile Money: ${config.text('mtn_momo_number')}${accountName.isEmpty ? '' : '\nName: $accountName'}',
      'airtel_money' => 'Send to Airtel Money: ${config.text('airtel_money_number')}${accountName.isEmpty ? '' : '\nName: $accountName'}',
      _ => config.text('bank_details'),
    };

    return Panel(
      title: 'Pay ${money(widget.booking.budget, config.currency)} into escrow',
      color: const Color(0xFFFFFBF2),
      children: [
        Text(config.text('payment_instructions', 'Send the exact amount, then enter the transaction ID below.'), style: const TextStyle(height: 1.4)),
        const SizedBox(height: 12),
        Wrap(spacing: 8, runSpacing: 8, children: [
          for (final entry in config.paymentMethods.entries)
            ChoiceChip(
              label: Text(entry.value),
              selected: _method == entry.key,
              onSelected: (_) => setState(() => _method = entry.key),
            ),
        ]),
        const SizedBox(height: 12),
        Container(
          padding: const EdgeInsets.all(12),
          decoration: BoxDecoration(color: BrandColors.teal.withValues(alpha: 0.07), borderRadius: BorderRadius.circular(12)),
          child: SelectableText(destination, style: const TextStyle(fontWeight: FontWeight.w700, height: 1.5)),
        ),
        const SizedBox(height: 12),
        if (_method != 'bank_transfer') ...[
          TextField(
            controller: _phone,
            keyboardType: TextInputType.phone,
            decoration: InputDecoration(labelText: 'Phone number you paid from', errorText: _error?.fieldError('payer_phone')),
          ),
          const SizedBox(height: 12),
        ],
        TextField(
          controller: _reference,
          decoration: InputDecoration(labelText: 'Transaction ID / reference', errorText: _error?.fieldError('transaction_reference')),
        ),
        if (_error != null && _error!.errors.isEmpty) ...[
          const SizedBox(height: 8),
          Text(_error!.message, style: const TextStyle(color: BrandColors.danger)),
        ],
        const SizedBox(height: 12),
        BusyButton(label: 'I have paid', icon: Icons.verified_outlined, onPressed: _submit),
      ],
    );
  }
}

class ApprovePanel extends StatefulWidget {
  const ApprovePanel({super.key, required this.onApprove});

  final Future<void> Function(int rating, String review) onApprove;

  @override
  State<ApprovePanel> createState() => _ApprovePanelState();
}

class _ApprovePanelState extends State<ApprovePanel> {
  int _rating = 5;
  final _review = TextEditingController();

  @override
  void dispose() {
    _review.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return Panel(
      title: 'The maid has finished. Happy with the work?',
      color: const Color(0xFFF0FAF4),
      children: [
        const Text('Approving releases the payment from escrow to the maid.'),
        const SizedBox(height: 8),
        Row(children: [
          for (var i = 1; i <= 5; i++)
            IconButton(
              onPressed: () => setState(() => _rating = i),
              icon: Icon(i <= _rating ? Icons.star_rounded : Icons.star_outline_rounded, color: BrandColors.gold, size: 32),
            ),
        ]),
        TextField(controller: _review, maxLines: 3, decoration: const InputDecoration(labelText: 'Review (optional)', alignLabelWithHint: true)),
        const SizedBox(height: 12),
        BusyButton(
          label: 'Approve and release payment',
          icon: Icons.task_alt,
          color: BrandColors.success,
          onPressed: () => widget.onApprove(_rating, _review.text.trim()),
        ),
      ],
    );
  }
}
