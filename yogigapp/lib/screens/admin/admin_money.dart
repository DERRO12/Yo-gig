import 'package:flutter/material.dart';

import '../../core/app_state.dart';
import '../../core/format.dart';
import '../../core/models.dart';
import '../../core/theme.dart';
import '../../widgets/common.dart';
import '../../widgets/paged_list.dart';
import 'admin_bookings.dart';

/// Client payments to verify against the mobile money or bank statement.
class AdminPaymentsScreen extends StatefulWidget {
  const AdminPaymentsScreen({super.key});

  @override
  State<AdminPaymentsScreen> createState() => _AdminPaymentsScreenState();
}

class _AdminPaymentsScreenState extends State<AdminPaymentsScreen> {
  final _listKey = GlobalKey<PagedListState<Payment>>();
  String _status = 'pending';

  @override
  Widget build(BuildContext context) {
    final state = AppScope.of(context);
    final currency = state.config.currency;

    return PagedList<Payment>(
      key: _listKey,
      fetch: (page) => state.api.get('/admin/payments', query: {'page': page, 'status': _status}),
      parse: Payment.fromJson,
      emptyMessage: 'No payments here.',
      emptyIcon: Icons.payments_outlined,
      header: Padding(
        padding: const EdgeInsets.only(bottom: 12),
        child: FilterChips(
          options: const {'pending': 'To verify', 'successful': 'Confirmed', 'failed': 'Rejected', 'refunded': 'Refunded', 'all': 'All'},
          selected: _status,
          onSelected: (value) {
            setState(() => _status = value);
            _listKey.currentState?.reload();
          },
        ),
      ),
      itemBuilder: (context, payment, reload) => Card(
        child: Padding(
          padding: const EdgeInsets.all(14),
          child: Column(crossAxisAlignment: CrossAxisAlignment.stretch, children: [
            Row(children: [
              Expanded(child: Text(money(payment.amount, currency), style: const TextStyle(fontSize: 18, fontWeight: FontWeight.w900, color: BrandColors.teal))),
              StatusChip(humanize(payment.status), color: paymentStatusColor(payment.status)),
            ]),
            const SizedBox(height: 6),
            InfoRow('Method', payment.methodLabel),
            InfoRow('Reference', payment.transactionReference),
            if (payment.payerPhone != null) InfoRow('Paid from', payment.payerPhone),
            InfoRow('Client', payment.client?.name),
            InfoRow('Booking', payment.booking == null ? null : '${payment.booking!.reference} · ${payment.booking!.title}'),
            InfoRow('Submitted', dateTime(payment.createdAt)),
            if (payment.notes != null) InfoRow('Notes', payment.notes),
            const SizedBox(height: 8),
            Wrap(spacing: 8, runSpacing: 8, children: [
              if (payment.isPending) ...[
                FilledButton.icon(
                  style: FilledButton.styleFrom(backgroundColor: BrandColors.success),
                  onPressed: () async {
                    if (await confirm(context, title: 'Confirm this payment?', message: 'Only confirm once the money is in the account. The gig then opens to maids.') && context.mounted) {
                      if (await runAction(context, () => state.api.post('/admin/payments/${payment.id}/confirm'))) {
                        await reload();
                      }
                    }
                  },
                  icon: const Icon(Icons.check),
                  label: const Text('Confirm'),
                ),
                OutlinedButton.icon(
                  style: OutlinedButton.styleFrom(foregroundColor: BrandColors.danger),
                  onPressed: () async {
                    final notes = await promptText(context, title: 'Reject payment', label: 'Reason shown to the team (optional)', action: 'Reject');
                    if (notes != null && context.mounted && await runAction(context, () => state.api.post('/admin/payments/${payment.id}/reject', {'notes': notes}))) {
                      await reload();
                    }
                  },
                  icon: const Icon(Icons.close),
                  label: const Text('Reject'),
                ),
              ],
              if (payment.booking != null)
                TextButton(
                  onPressed: () => Navigator.of(context).push(MaterialPageRoute(builder: (_) => AdminBookingDetailScreen(bookingId: payment.jobId))),
                  child: const Text('View booking'),
                ),
            ]),
          ]),
        ),
      ),
    );
  }
}

/// Maid payouts queued after clients approve work.
class AdminPayoutsScreen extends StatefulWidget {
  const AdminPayoutsScreen({super.key});

  @override
  State<AdminPayoutsScreen> createState() => _AdminPayoutsScreenState();
}

class _AdminPayoutsScreenState extends State<AdminPayoutsScreen> {
  final _listKey = GlobalKey<PagedListState<Payout>>();
  String _status = 'pending';

  @override
  Widget build(BuildContext context) {
    final state = AppScope.of(context);
    final currency = state.config.currency;

    return PagedList<Payout>(
      key: _listKey,
      fetch: (page) => state.api.get('/admin/payouts', query: {'page': page, 'status': _status}),
      parse: Payout.fromJson,
      emptyMessage: 'No payouts here.',
      emptyIcon: Icons.outbox_outlined,
      header: Padding(
        padding: const EdgeInsets.only(bottom: 12),
        child: FilterChips(
          options: const {'pending': 'To send', 'paid': 'Paid', 'failed': 'Failed', 'all': 'All'},
          selected: _status,
          onSelected: (value) {
            setState(() => _status = value);
            _listKey.currentState?.reload();
          },
        ),
      ),
      itemBuilder: (context, payout, reload) => Card(
        child: Padding(
          padding: const EdgeInsets.all(14),
          child: Column(crossAxisAlignment: CrossAxisAlignment.stretch, children: [
            Row(children: [
              Expanded(child: Text(money(payout.amount, currency), style: const TextStyle(fontSize: 18, fontWeight: FontWeight.w900, color: BrandColors.teal))),
              StatusChip(humanize(payout.status), color: payout.isPaid ? BrandColors.success : payout.status == 'failed' ? BrandColors.danger : BrandColors.warning),
            ]),
            const SizedBox(height: 6),
            InfoRow('Maid', payout.maid?.name),
            InfoRow('Method', payout.methodLabel),
            InfoRow('Account', [payout.accountName, payout.accountNumber].whereType<String>().join(' · ')),
            InfoRow('Booking', payout.booking == null ? null : '${payout.booking!.reference} · ${payout.booking!.title}'),
            if (payout.reference != null) InfoRow('Reference', payout.reference),
            if (payout.paidAt != null) InfoRow('Paid', dateTime(payout.paidAt)),
            if (payout.notes != null) InfoRow('Notes', payout.notes),
            if (payout.accountNumber == null)
              const Padding(
                padding: EdgeInsets.only(top: 4),
                child: Text('The maid has not added payout details yet.', style: TextStyle(color: BrandColors.warning)),
              ),
            if (!payout.isPaid) ...[
              const SizedBox(height: 8),
              Wrap(spacing: 8, runSpacing: 8, children: [
                FilledButton.icon(
                  style: FilledButton.styleFrom(backgroundColor: BrandColors.success),
                  onPressed: () async {
                    final reference = await promptText(context, title: 'Mark as paid', label: 'Transaction reference', required: true, action: 'Mark paid');
                    if (reference != null && context.mounted && await runAction(context, () => state.api.post('/admin/payouts/${payout.id}/paid', {'reference': reference}))) {
                      await reload();
                    }
                  },
                  icon: const Icon(Icons.send),
                  label: const Text('Mark paid'),
                ),
                if (payout.isPending)
                  OutlinedButton.icon(
                    style: OutlinedButton.styleFrom(foregroundColor: BrandColors.danger),
                    onPressed: () async {
                      final notes = await promptText(context, title: 'Payout failed', label: 'What went wrong (optional)', action: 'Mark failed');
                      if (notes != null && context.mounted && await runAction(context, () => state.api.post('/admin/payouts/${payout.id}/failed', {'notes': notes}))) {
                        await reload();
                      }
                    },
                    icon: const Icon(Icons.error_outline),
                    label: const Text('Failed'),
                  ),
              ]),
            ],
          ]),
        ),
      ),
    );
  }
}
