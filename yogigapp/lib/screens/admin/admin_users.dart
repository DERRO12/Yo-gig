import 'package:flutter/material.dart';

import '../../core/app_state.dart';
import '../../core/format.dart';
import '../../core/models.dart';
import '../../core/theme.dart';
import '../../widgets/common.dart';
import '../../widgets/paged_list.dart';
import 'admin_bookings.dart';

class AdminUsersScreen extends StatefulWidget {
  const AdminUsersScreen({super.key, this.role = '', this.verification = ''});

  final String role;
  final String verification;

  @override
  State<AdminUsersScreen> createState() => _AdminUsersScreenState();
}

class _AdminUsersScreenState extends State<AdminUsersScreen> {
  final _listKey = GlobalKey<PagedListState<AppUser>>();
  final _search = TextEditingController();
  late String _role = widget.role;
  late String _verification = widget.verification;

  @override
  void dispose() {
    _search.dispose();
    super.dispose();
  }

  void _refresh() => _listKey.currentState?.reload();

  @override
  Widget build(BuildContext context) {
    final state = AppScope.of(context);

    return Scaffold(
      appBar: AppBar(title: const Text('Users')),
      body: PagedList<AppUser>(
        key: _listKey,
        fetch: (page) => state.api.get('/admin/users', query: {'page': page, 'role': _role, 'verification': _verification, 'q': _search.text.trim()}),
        parse: AppUser.fromJson,
        emptyMessage: 'No users match.',
        emptyIcon: Icons.people_outline,
        header: Column(crossAxisAlignment: CrossAxisAlignment.stretch, children: [
          TextField(
            controller: _search,
            textInputAction: TextInputAction.search,
            onSubmitted: (_) => _refresh(),
            decoration: const InputDecoration(hintText: 'Search name, email or phone', prefixIcon: Icon(Icons.search)),
          ),
          const SizedBox(height: 10),
          FilterChips(
            options: const {'': 'Everyone', 'client': 'Clients', 'maid': 'Maids', 'admin': 'Admins'},
            selected: _role,
            onSelected: (value) {
              setState(() => _role = value);
              _refresh();
            },
          ),
          if (_role == 'maid') ...[
            const SizedBox(height: 6),
            FilterChips(
              options: const {'': 'Any ID status', 'pending': 'Awaiting check', 'verified': 'Verified', 'rejected': 'Rejected'},
              selected: _verification,
              onSelected: (value) {
                setState(() => _verification = value);
                _refresh();
              },
            ),
          ],
          const SizedBox(height: 12),
        ]),
        itemBuilder: (context, user, reload) => Card(
          child: ListTile(
            leading: Avatar(user),
            title: Text(user.name, style: const TextStyle(fontWeight: FontWeight.w700)),
            subtitle: Text('${user.email}\n${user.phoneNumber}'),
            isThreeLine: true,
            trailing: Column(mainAxisAlignment: MainAxisAlignment.center, crossAxisAlignment: CrossAxisAlignment.end, children: [
              StatusChip(user.roleLabel, color: BrandColors.teal),
              const SizedBox(height: 4),
              if (!user.isActive)
                const StatusChip('Suspended', color: BrandColors.danger)
              else if (user.maidProfile != null)
                StatusChip(humanize(user.maidProfile!.verificationStatus), color: user.maidProfile!.isVerified ? BrandColors.success : BrandColors.warning),
            ]),
            onTap: () async {
              await Navigator.of(context).push(MaterialPageRoute(builder: (_) => AdminUserDetailScreen(userId: user.id)));
              await reload();
            },
          ),
        ),
      ),
    );
  }
}

class AdminUserDetailScreen extends StatelessWidget {
  const AdminUserDetailScreen({super.key, required this.userId});

  final int userId;

  @override
  Widget build(BuildContext context) {
    final state = AppScope.of(context);
    final currency = state.config.currency;

    return Scaffold(
      appBar: AppBar(title: const Text('User')),
      body: AsyncView<Map<String, dynamic>>(
        load: () => state.api.get('/admin/users/$userId'),
        builder: (context, data, reload) {
          final user = AppUser.fromJson(data['data'] as Map<String, dynamic>);
          final profile = user.maidProfile;
          final clientBookings = listOf(data['bookings_as_client'], Booking.fromJson);
          final maidBookings = listOf(data['bookings_as_maid'], Booking.fromJson);
          final payouts = listOf(data['payouts'], Payout.fromJson);
          final isSelf = user.id == state.user?.id;

          Future<void> post(String path, [Map<String, dynamic>? body]) async {
            if (await runAction(context, () => state.api.post(path, body))) {
              await reload();
            }
          }

          void openBooking(Booking booking) =>
              Navigator.of(context).push(MaterialPageRoute(builder: (_) => AdminBookingDetailScreen(bookingId: booking.id))).then((_) => reload());

          return PageList(children: [
            Panel(children: [
              Row(children: [
                Avatar(user, radius: 28),
                const SizedBox(width: 12),
                Expanded(
                  child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                    Text(user.name, style: const TextStyle(fontSize: 18, fontWeight: FontWeight.w800)),
                    Wrap(spacing: 6, children: [
                      StatusChip(user.roleLabel, color: BrandColors.teal),
                      StatusChip(user.isActive ? 'Active' : 'Suspended', color: user.isActive ? BrandColors.success : BrandColors.danger),
                    ]),
                  ]),
                ),
              ]),
              const SizedBox(height: 10),
              InfoRow('Email', user.email, icon: Icons.email_outlined),
              InfoRow('Phone', user.phoneNumber, icon: Icons.phone_outlined),
              InfoRow('Joined', shortDate(user.createdAt), icon: Icons.calendar_today_outlined),
              if (!isSelf) ...[
                const SizedBox(height: 10),
                BusyButton(
                  label: user.isActive ? 'Suspend account' : 'Reactivate account',
                  icon: user.isActive ? Icons.block : Icons.check_circle_outline,
                  outlined: true,
                  color: user.isActive ? BrandColors.danger : BrandColors.success,
                  onPressed: () async {
                    final ok = await confirm(
                      context,
                      title: user.isActive ? 'Suspend ${user.name}?' : 'Reactivate ${user.name}?',
                      message: user.isActive ? 'They will be signed out and cannot use the site or app.' : 'They will be able to sign in again.',
                      destructive: user.isActive,
                    );
                    if (ok) {
                      await post('/admin/users/$userId/toggle-active');
                    }
                  },
                ),
              ],
            ]),
            if (profile != null) ...[
              const SizedBox(height: 12),
              Panel(title: 'Maid profile', children: [
                InfoRow('ID status', humanize(profile.verificationStatus)),
                InfoRow('National ID', profile.nationalIdNumber),
                InfoRow('Service area', profile.serviceArea),
                InfoRow('Available', profile.available ? 'Yes' : 'No'),
                InfoRow('Jobs done', '${profile.completedJobs}'),
                InfoRow('Rating', profile.averageRating?.toStringAsFixed(1)),
                InfoRow('Payout to', profile.hasPayoutDetails ? '${profile.payoutMethodLabel} · ${profile.payoutAccountName} · ${profile.payoutAccountNumber}' : null),
                if (profile.bio != null) InfoRow('Bio', profile.bio),
                if (profile.hasNationalIdPhoto) ...[
                  const SizedBox(height: 8),
                  NetImage('${state.api.baseUrl}/admin/users/$userId/national-id', height: 200, fit: BoxFit.contain, headers: state.api.authHeaders),
                ],
                const SizedBox(height: 12),
                const Text('Verification', style: TextStyle(fontWeight: FontWeight.w700)),
                const SizedBox(height: 6),
                SegmentedButton<String>(
                  segments: const [
                    ButtonSegment(value: 'pending', label: Text('Pending')),
                    ButtonSegment(value: 'verified', label: Text('Verified'), icon: Icon(Icons.verified_outlined)),
                    ButtonSegment(value: 'rejected', label: Text('Rejected')),
                  ],
                  selected: {profile.verificationStatus},
                  onSelectionChanged: (selection) => post('/admin/users/$userId/verification', {'status': selection.first}),
                ),
              ]),
            ],
            if (clientBookings.isNotEmpty) ...[
              const SectionTitle('Recent bookings'),
              for (final booking in clientBookings) ...[BookingTile(booking: booking, onTap: () => openBooking(booking)), const SizedBox(height: 10)],
            ],
            if (maidBookings.isNotEmpty) ...[
              const SectionTitle('Recent gigs'),
              for (final booking in maidBookings) ...[BookingTile(booking: booking, onTap: () => openBooking(booking)), const SizedBox(height: 10)],
            ],
            if (payouts.isNotEmpty) ...[
              const SectionTitle('Payouts'),
              Card(
                child: Column(children: [
                  for (final payout in payouts)
                    ListTile(
                      title: Text(money(payout.amount, currency)),
                      subtitle: Text('${payout.methodLabel} · ${shortDate(payout.createdAt)}'),
                      trailing: StatusChip(humanize(payout.status), color: payout.isPaid ? BrandColors.success : BrandColors.warning),
                    ),
                ]),
              ),
            ],
            const SizedBox(height: 24),
          ]);
        },
      ),
    );
  }
}
