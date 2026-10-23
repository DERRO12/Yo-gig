import 'package:flutter/material.dart';

import '../../core/app_state.dart';
import '../../core/format.dart';
import '../../core/models.dart';
import '../../core/theme.dart';
import '../../widgets/common.dart';
import '../shared/account_screen.dart';
import 'earnings_screen.dart';
import 'gig_detail_screen.dart';

class MaidShell extends StatefulWidget {
  const MaidShell({super.key});

  @override
  State<MaidShell> createState() => _MaidShellState();
}

class _MaidShellState extends State<MaidShell> {
  int _index = 0;
  final _keys = List.generate(4, (_) => GlobalKey<AsyncViewState<Map<String, dynamic>>>());

  @override
  Widget build(BuildContext context) {
    const titles = ['Dashboard', 'Find gigs', 'My jobs', 'Earnings', 'Account'];

    return Scaffold(
      appBar: AppBar(title: Text(titles[_index])),
      body: IndexedStack(
        index: _index,
        children: [
          MaidDashboard(viewKey: _keys[0], onFindGigs: () => setState(() => _index = 1)),
          GigBoard(viewKey: _keys[1]),
          MyJobs(viewKey: _keys[2]),
          EarningsScreen(viewKey: _keys[3]),
          const AccountScreen(),
        ],
      ),
      bottomNavigationBar: NavigationBar(
        selectedIndex: _index,
        onDestinationSelected: (index) {
          setState(() => _index = index);
          if (index < _keys.length) {
            _keys[index].currentState?.reload();
          }
        },
        destinations: const [
          NavigationDestination(icon: Icon(Icons.dashboard_outlined), selectedIcon: Icon(Icons.dashboard), label: 'Home'),
          NavigationDestination(icon: Icon(Icons.search), label: 'Gigs'),
          NavigationDestination(icon: Icon(Icons.work_outline), selectedIcon: Icon(Icons.work), label: 'My jobs'),
          NavigationDestination(icon: Icon(Icons.account_balance_wallet_outlined), selectedIcon: Icon(Icons.account_balance_wallet), label: 'Earnings'),
          NavigationDestination(icon: Icon(Icons.person_outline), selectedIcon: Icon(Icons.person), label: 'Account'),
        ],
      ),
    );
  }
}

Future<void> openGig(BuildContext context, Booking gig, Future<void> Function() reload) async {
  await Navigator.of(context).push(MaterialPageRoute(builder: (_) => GigDetailScreen(gigId: gig.id)));
  await reload();
}

class MaidDashboard extends StatelessWidget {
  const MaidDashboard({super.key, required this.viewKey, required this.onFindGigs});

  final GlobalKey<AsyncViewState<Map<String, dynamic>>> viewKey;
  final VoidCallback onFindGigs;

  @override
  Widget build(BuildContext context) {
    final state = AppScope.of(context);
    final currency = state.config.currency;

    return AsyncView<Map<String, dynamic>>(
      key: viewKey,
      load: () => state.api.get('/maid/dashboard'),
      builder: (context, data, reload) {
        final stats = data['stats'] as Map<String, dynamic>? ?? {};
        final maid = AppUser.fromJson(data['maid'] as Map<String, dynamic>);
        final active = listOf(data['active_jobs'], Booking.fromJson);
        final awaiting = listOf(data['awaiting_approval'], Booking.fromJson);
        final recent = listOf(data['recent_gigs'], Booking.fromJson);
        final profile = maid.maidProfile;

        return PageList(children: [
          Text('Hello, ${maid.name.split(' ').first}', style: const TextStyle(fontSize: 22, fontWeight: FontWeight.w800, color: BrandColors.tealDark)),
          const SizedBox(height: 12),
          if (data['can_claim'] != true)
            Panel(color: const Color(0xFFFFF8E6), children: [
              Text(
                profile?.verificationStatus == 'rejected'
                    ? 'Your ID verification was not approved. Please contact support.'
                    : 'Your ID is being verified by our team. You can claim gigs once it is approved.',
                style: const TextStyle(fontWeight: FontWeight.w600),
              ),
            ]),
          if (profile != null && !profile.hasPayoutDetails) ...[
            const SizedBox(height: 10),
            Panel(color: const Color(0xFFEFF6FF), children: [
              const Text('Add your mobile money or bank details in Earnings so we can pay you.', style: TextStyle(fontWeight: FontWeight.w600)),
            ]),
          ],
          const SizedBox(height: 12),
          StatGrid(children: [
            StatCard(label: 'Open gigs', value: '${stats['open_gigs'] ?? 0}', icon: Icons.search, onTap: onFindGigs),
            StatCard(label: 'Active jobs', value: '${stats['active_jobs'] ?? 0}', icon: Icons.work_outline, color: BrandColors.warning),
            StatCard(label: 'Pending payouts', value: money(stats['pending_payouts'] as num?, currency), icon: Icons.hourglass_bottom, color: BrandColors.info),
            StatCard(label: 'Total earned', value: money(stats['total_earned'] as num?, currency), icon: Icons.savings_outlined, color: BrandColors.success),
          ]),
          if (active.isNotEmpty) ...[
            const SectionTitle('Your active jobs'),
            for (final gig in active) ...[
              BookingTile(booking: gig, amount: gig.maidEarnings, showClient: true, onTap: () => openGig(context, gig, reload)),
              const SizedBox(height: 10),
            ],
          ],
          if (awaiting.isNotEmpty) ...[
            const SectionTitle('Waiting for client approval'),
            for (final gig in awaiting) ...[
              BookingTile(booking: gig, amount: gig.maidEarnings, onTap: () => openGig(context, gig, reload)),
              const SizedBox(height: 10),
            ],
          ],
          SectionTitle('New gigs', trailing: TextButton(onPressed: onFindGigs, child: const Text('See all'))),
          if (recent.isEmpty) const EmptyState(icon: Icons.inbox_outlined, message: 'No open gigs right now. Check back soon.'),
          for (final gig in recent) ...[
            BookingTile(booking: gig, amount: gig.maidEarnings, onTap: () => openGig(context, gig, reload)),
            const SizedBox(height: 10),
          ],
        ]);
      },
    );
  }
}

/// Funded open gigs, with the ones in the maid's area first.
class GigBoard extends StatefulWidget {
  const GigBoard({super.key, required this.viewKey});

  final GlobalKey<AsyncViewState<Map<String, dynamic>>> viewKey;

  @override
  State<GigBoard> createState() => _GigBoardState();
}

class _GigBoardState extends State<GigBoard> {
  final _search = TextEditingController();

  @override
  void dispose() {
    _search.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final state = AppScope.of(context);

    return Column(children: [
      Padding(
        padding: const EdgeInsets.fromLTRB(16, 16, 16, 0),
        child: TextField(
          controller: _search,
          textInputAction: TextInputAction.search,
          onSubmitted: (_) => widget.viewKey.currentState?.reload(),
          decoration: InputDecoration(
            hintText: 'Search by title or location',
            prefixIcon: const Icon(Icons.search),
            suffixIcon: IconButton(
              icon: const Icon(Icons.clear),
              onPressed: () {
                _search.clear();
                widget.viewKey.currentState?.reload();
              },
            ),
          ),
        ),
      ),
      Expanded(
        child: AsyncView<Map<String, dynamic>>(
          key: widget.viewKey,
          load: () => state.api.get('/maid/gigs', query: {'q': _search.text.trim()}),
          builder: (context, data, reload) {
            final area = data['service_area']?.toString();
            final areaGigs = listOf(data['area_gigs'], Booking.fromJson);
            final otherGigs = listOf(data['other_gigs'], Booking.fromJson);

            return PageList(children: [
              if (areaGigs.isNotEmpty) ...[
                SectionTitle('Near you', subtitle: area == null ? null : 'In $area'),
                for (final gig in areaGigs) ...[
                  BookingTile(booking: gig, amount: gig.maidEarnings, onTap: () => openGig(context, gig, reload)),
                  const SizedBox(height: 10),
                ],
              ],
              if (otherGigs.isNotEmpty) ...[
                SectionTitle(areaGigs.isEmpty ? 'Open gigs' : 'Other areas'),
                for (final gig in otherGigs) ...[
                  BookingTile(booking: gig, amount: gig.maidEarnings, onTap: () => openGig(context, gig, reload)),
                  const SizedBox(height: 10),
                ],
              ],
              if (areaGigs.isEmpty && otherGigs.isEmpty)
                const EmptyState(icon: Icons.inbox_outlined, message: 'No funded gigs match right now. Pull down to refresh.'),
            ]);
          },
        ),
      ),
    ]);
  }
}

class MyJobs extends StatelessWidget {
  const MyJobs({super.key, required this.viewKey});

  final GlobalKey<AsyncViewState<Map<String, dynamic>>> viewKey;

  @override
  Widget build(BuildContext context) {
    final state = AppScope.of(context);

    return AsyncView<Map<String, dynamic>>(
      key: viewKey,
      load: () => state.api.get('/maid/jobs'),
      builder: (context, data, reload) {
        final jobs = listOf(data, Booking.fromJson);
        return PageList(children: [
          if (jobs.isEmpty) const EmptyState(icon: Icons.work_off_outlined, message: 'Gigs you claim will appear here.'),
          for (final job in jobs) ...[
            BookingTile(booking: job, amount: job.maidEarnings, showClient: true, onTap: () => openGig(context, job, reload)),
            const SizedBox(height: 10),
          ],
        ]);
      },
    );
  }
}
