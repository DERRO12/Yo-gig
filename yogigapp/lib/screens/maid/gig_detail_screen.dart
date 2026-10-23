import 'package:flutter/material.dart';

import '../../core/app_state.dart';
import '../../core/format.dart';
import '../../core/models.dart';
import '../../core/theme.dart';
import '../../widgets/booking_widgets.dart';
import '../../widgets/common.dart';
import '../public/site_links.dart';

class GigDetailScreen extends StatelessWidget {
  const GigDetailScreen({super.key, required this.gigId});

  final int gigId;

  @override
  Widget build(BuildContext context) {
    final state = AppScope.of(context);
    final currency = state.config.currency;

    return Scaffold(
      appBar: AppBar(title: const Text('Gig')),
      body: AsyncView<Map<String, dynamic>>(
        load: () => state.api.get('/maid/gigs/$gigId'),
        builder: (context, data, reload) {
          final gig = Booking.fromJson(data['data'] as Map<String, dynamic>);
          final isMine = data['is_mine'] == true;
          final canClaim = data['can_claim'] == true;

          Future<void> act(String action, {bool leave = false}) async {
            if (await runAction(context, () => state.api.post('/maid/gigs/$gigId/$action'))) {
              if (leave && context.mounted) {
                Navigator.pop(context);
              } else {
                await reload();
              }
            }
          }

          return PageList(children: [
            BookingHeader(booking: gig, amount: gig.maidEarnings ?? gig.budget, amountLabel: 'You earn'),
            const SizedBox(height: 12),
            if (!isMine)
              Panel(color: BrandColors.success.withValues(alpha: 0.06), children: [
                const Text('The client has already paid. The money is held in escrow and paid to you after they approve your work.'),
                const SizedBox(height: 12),
                if (canClaim)
                  BusyButton(
                    label: 'Claim this gig',
                    icon: Icons.handshake_outlined,
                    onPressed: () async {
                      if (await confirm(context, title: 'Claim this gig?', message: 'You commit to arrive on ${gig.scheduleLabel ?? 'the scheduled date'} at ${gig.location}.', action: 'Claim')) {
                        await act('claim');
                      }
                    },
                  )
                else
                  const Text('Your ID must be verified before you can claim gigs.', style: TextStyle(color: BrandColors.warning, fontWeight: FontWeight.w600)),
              ]),
            if (isMine) ...[
              if (gig.client != null)
                Panel(title: 'Client', children: [
                  InfoRow('Name', gig.client!.name, icon: Icons.person_outline),
                  InfoRow('Phone', gig.client!.phoneNumber, icon: Icons.phone_outlined),
                  const SizedBox(height: 8),
                  Row(children: [
                    Expanded(
                      child: OutlinedButton.icon(
                        onPressed: () => openExternal('tel:${gig.client!.phoneNumber.replaceAll(' ', '')}'),
                        icon: const Icon(Icons.call),
                        label: const Text('Call'),
                      ),
                    ),
                    const SizedBox(width: 10),
                    Expanded(
                      child: OutlinedButton.icon(
                        onPressed: () => openExternal('https://wa.me/${gig.client!.phoneNumber.replaceAll(RegExp(r'[^0-9]'), '')}'),
                        icon: const Icon(Icons.chat),
                        label: const Text('WhatsApp'),
                      ),
                    ),
                  ]),
                ]),
              const SizedBox(height: 12),
              BookingProgress(booking: gig),
              const SizedBox(height: 12),
              if (gig.isMatched) ...[
                BusyButton(label: 'Start job', icon: Icons.play_arrow_rounded, onPressed: () => act('start')),
                const SizedBox(height: 10),
                BusyButton(
                  label: 'Release gig',
                  icon: Icons.undo,
                  outlined: true,
                  color: BrandColors.danger,
                  onPressed: () async {
                    if (await confirm(context, title: 'Release this gig?', message: 'It will go back to the gig board for other maids.', action: 'Release', destructive: true)) {
                      await act('release', leave: true);
                    }
                  },
                ),
              ],
              if (gig.isInProgress)
                BusyButton(
                  label: 'Mark job complete',
                  icon: Icons.task_alt,
                  color: BrandColors.success,
                  onPressed: () async {
                    if (await confirm(context, title: 'Finished the job?', message: 'The client will be asked to approve so your payout is released.', action: 'Mark complete')) {
                      await act('complete');
                    }
                  },
                ),
              if (gig.isAwaitingClientApproval)
                const Panel(color: Color(0xFFFFF8E6), children: [Text('Waiting for the client to approve your work. Your payout is queued as soon as they do.')]),
              if (gig.payout != null)
                Panel(title: 'Payout', children: [
                  InfoRow('Amount', money(gig.payout!.amount, currency)),
                  InfoRow('Status', humanize(gig.payout!.status)),
                  InfoRow('Method', gig.payout!.methodLabel),
                  if (gig.payout!.reference != null) InfoRow('Reference', gig.payout!.reference),
                ]),
            ],
            const SizedBox(height: 12),
            BookingDetails(booking: gig),
            const SizedBox(height: 24),
          ]);
        },
      ),
    );
  }
}
