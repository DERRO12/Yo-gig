import 'package:flutter/material.dart';

import '../../core/api_client.dart';
import '../../core/app_state.dart';
import '../../core/format.dart';
import '../../core/models.dart';
import '../../core/theme.dart';
import '../../widgets/common.dart';

class EarningsScreen extends StatelessWidget {
  const EarningsScreen({super.key, required this.viewKey});

  final GlobalKey<AsyncViewState<Map<String, dynamic>>> viewKey;

  @override
  Widget build(BuildContext context) {
    final state = AppScope.of(context);
    final currency = state.config.currency;

    return AsyncView<Map<String, dynamic>>(
      key: viewKey,
      load: () => state.api.get('/maid/earnings'),
      builder: (context, data, reload) {
        final totals = data['totals'] as Map<String, dynamic>? ?? {};
        final maid = AppUser.fromJson(data['maid'] as Map<String, dynamic>);
        final payouts = listOf(data['payouts'], Payout.fromJson);
        final methods = stringMap(data['payout_methods']);

        return PageList(children: [
          StatGrid(children: [
            StatCard(label: 'Paid to you', value: money(totals['paid'] as num?, currency), icon: Icons.savings_outlined, color: BrandColors.success),
            StatCard(label: 'Pending payouts', value: money(totals['pending'] as num?, currency), icon: Icons.hourglass_bottom, color: BrandColors.info),
          ]),
          const SizedBox(height: 12),
          if (state.config.text('payout_schedule_note').isNotEmpty)
            Panel(color: BrandColors.teal.withValues(alpha: 0.06), children: [Text(state.config.text('payout_schedule_note'))]),
          const SizedBox(height: 12),
          PayoutDetailsForm(profile: maid.maidProfile, methods: methods, onSaved: reload),
          const SectionTitle('Payout history'),
          if (payouts.isEmpty) const EmptyState(icon: Icons.receipt_long_outlined, message: 'Payouts appear here once clients approve your work.'),
          for (final payout in payouts)
            Padding(
              padding: const EdgeInsets.only(bottom: 10),
              child: Card(
                child: ListTile(
                  leading: Icon(payout.isPaid ? Icons.check_circle : Icons.schedule, color: payout.isPaid ? BrandColors.success : BrandColors.warning),
                  title: Text(money(payout.amount, currency), style: const TextStyle(fontWeight: FontWeight.w800)),
                  subtitle: Text([
                    payout.booking?.title ?? 'Booking',
                    '${payout.methodLabel}${payout.accountNumber == null ? '' : ' · ${payout.accountNumber}'}',
                    if (payout.reference != null) 'Ref ${payout.reference}',
                    payout.isPaid ? 'Paid ${dateTime(payout.paidAt)}' : 'Queued ${shortDate(payout.createdAt)}',
                  ].join('\n')),
                  isThreeLine: true,
                  trailing: StatusChip(humanize(payout.status), color: paymentStatusColor(payout.isPaid ? 'paid' : payout.status == 'failed' ? 'failed' : 'pending')),
                ),
              ),
            ),
        ]);
      },
    );
  }
}

class PayoutDetailsForm extends StatefulWidget {
  const PayoutDetailsForm({super.key, required this.profile, required this.methods, required this.onSaved});

  final MaidProfile? profile;
  final Map<String, String> methods;
  final Future<void> Function() onSaved;

  @override
  State<PayoutDetailsForm> createState() => _PayoutDetailsFormState();
}

class _PayoutDetailsFormState extends State<PayoutDetailsForm> {
  late String _method = widget.profile?.payoutMethod ?? widget.methods.keys.firstOrNull ?? 'mtn_momo';
  late final _name = TextEditingController(text: widget.profile?.payoutAccountName ?? '');
  late final _number = TextEditingController(text: widget.profile?.payoutAccountNumber ?? '');
  ApiException? _error;

  @override
  void dispose() {
    _name.dispose();
    _number.dispose();
    super.dispose();
  }

  Future<void> _save() async {
    setState(() => _error = null);
    final state = AppScope.read(context);
    try {
      final response = await state.api.put('/maid/earnings/payout-details', {
        'payout_method': _method,
        'payout_account_name': _name.text.trim(),
        'payout_account_number': _number.text.trim(),
      });
      state.setUser(AppUser.fromJson(response['data'] as Map<String, dynamic>));
      if (mounted) {
        showMessage(context, response['message']?.toString() ?? 'Payout details saved.');
      }
      await widget.onSaved();
    } on ApiException catch (error) {
      setState(() => _error = error);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Panel(title: 'Where we send your money', children: [
      Wrap(spacing: 8, runSpacing: 8, children: [
        for (final entry in widget.methods.entries)
          ChoiceChip(label: Text(entry.value), selected: _method == entry.key, onSelected: (_) => setState(() => _method = entry.key)),
      ]),
      const SizedBox(height: 12),
      TextField(controller: _name, decoration: InputDecoration(labelText: 'Account name', errorText: _error?.fieldError('payout_account_name'))),
      const SizedBox(height: 12),
      TextField(
        controller: _number,
        keyboardType: _method == 'bank_transfer' ? TextInputType.text : TextInputType.phone,
        decoration: InputDecoration(
          labelText: _method == 'bank_transfer' ? 'Bank account number' : 'Mobile money number',
          errorText: _error?.fieldError('payout_account_number'),
        ),
      ),
      if (_error != null && _error!.errors.isEmpty) Text(_error!.message, style: const TextStyle(color: BrandColors.danger)),
      const SizedBox(height: 12),
      BusyButton(label: 'Save payout details', icon: Icons.save_outlined, onPressed: _save),
    ]);
  }
}
