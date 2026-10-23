import 'package:flutter/material.dart';

import '../../core/app_state.dart';
import '../../core/format.dart';
import '../../core/models.dart';
import '../../core/theme.dart';
import '../../widgets/common.dart';
import '../shared/account_screen.dart';
import 'admin_bookings.dart';
import 'admin_content.dart';
import 'admin_messages.dart';
import 'admin_money.dart';
import 'admin_settings.dart';
import 'admin_users.dart';

/// The super admin panel: the same controls as the website's /admin area.
class AdminShell extends StatefulWidget {
  const AdminShell({super.key});

  @override
  State<AdminShell> createState() => _AdminShellState();
}

class _AdminShellState extends State<AdminShell> {
  int _index = 0;
  final _dashboardKey = GlobalKey<AsyncViewState<Map<String, dynamic>>>();

  void _go(int index) => setState(() => _index = index);

  @override
  Widget build(BuildContext context) {
    const titles = ['Admin dashboard', 'Bookings', 'Payments', 'Payouts', 'Manage'];

    return Scaffold(
      appBar: AppBar(title: Text(titles[_index])),
      body: IndexedStack(
        index: _index,
        children: [
          AdminDashboard(viewKey: _dashboardKey, onNavigate: _go),
          const AdminBookingsScreen(),
          const AdminPaymentsScreen(),
          const AdminPayoutsScreen(),
          const AdminMoreScreen(),
        ],
      ),
      bottomNavigationBar: NavigationBar(
        selectedIndex: _index,
        onDestinationSelected: (index) {
          _go(index);
          if (index == 0) {
            _dashboardKey.currentState?.reload();
          }
        },
        destinations: const [
          NavigationDestination(icon: Icon(Icons.space_dashboard_outlined), selectedIcon: Icon(Icons.space_dashboard), label: 'Overview'),
          NavigationDestination(icon: Icon(Icons.event_note_outlined), selectedIcon: Icon(Icons.event_note), label: 'Bookings'),
          NavigationDestination(icon: Icon(Icons.payments_outlined), selectedIcon: Icon(Icons.payments), label: 'Payments'),
          NavigationDestination(icon: Icon(Icons.outbox_outlined), selectedIcon: Icon(Icons.outbox), label: 'Payouts'),
          NavigationDestination(icon: Icon(Icons.tune), label: 'Manage'),
        ],
      ),
    );
  }
}

class AdminDashboard extends StatelessWidget {
  const AdminDashboard({super.key, required this.viewKey, required this.onNavigate});

  final GlobalKey<AsyncViewState<Map<String, dynamic>>> viewKey;
  final ValueChanged<int> onNavigate;

  @override
  Widget build(BuildContext context) {
    final state = AppScope.of(context);
    final currency = state.config.currency;

    return AsyncView<Map<String, dynamic>>(
      key: viewKey,
      load: () => state.api.get('/admin/dashboard'),
      builder: (context, data, reload) {
        final stats = data['stats'] as Map<String, dynamic>? ?? {};
        final todo = countMap(data['todo']);
        final statusCounts = countMap(data['status_counts']);
        final recent = listOf(data['recent_bookings'], Booking.fromJson);

        void push(Widget screen) => Navigator.of(context).push(MaterialPageRoute(builder: (_) => screen)).then((_) => reload());

        final tasks = [
          (todo['payments'] ?? 0, 'Payments to verify', Icons.fact_check_outlined, () => onNavigate(2)),
          (todo['payouts'] ?? 0, 'Payouts to send', Icons.outbox_outlined, () => onNavigate(3)),
          (todo['maids'] ?? 0, 'Maids awaiting ID check', Icons.badge_outlined, () => push(const AdminUsersScreen(role: 'maid', verification: 'pending'))),
          (todo['refunds'] ?? 0, 'Refunds due', Icons.currency_exchange, () => push(const AdminBookingsScreen(standalone: true, payment: 'refund_due'))),
          (todo['messages'] ?? 0, 'Unread messages', Icons.mail_outline, () => push(const AdminMessagesScreen())),
        ];

        return PageList(children: [
          StatGrid(children: [
            StatCard(label: 'Client payments received', value: money(stats['revenue'] as num?, currency), icon: Icons.trending_up, color: BrandColors.success),
            StatCard(label: 'Held in escrow', value: money(stats['escrow'] as num?, currency), icon: Icons.lock_outline),
            StatCard(label: 'Platform commission', value: money(stats['commission'] as num?, currency), icon: Icons.account_balance_outlined, color: BrandColors.gold),
            StatCard(label: 'Pending payouts', value: money(stats['pending_payouts'] as num?, currency), icon: Icons.hourglass_bottom, color: BrandColors.info),
            StatCard(label: 'Paid to maids', value: money(stats['paid_out'] as num?, currency), icon: Icons.savings_outlined, color: BrandColors.sage),
            StatCard(label: 'Bookings', value: '${stats['bookings'] ?? 0}', icon: Icons.event_note_outlined, onTap: () => onNavigate(1)),
            StatCard(label: 'Clients', value: '${stats['clients'] ?? 0}', icon: Icons.people_outline, onTap: () => push(const AdminUsersScreen(role: 'client'))),
            StatCard(label: 'Maids', value: '${stats['maids'] ?? 0}', icon: Icons.cleaning_services_outlined, onTap: () => push(const AdminUsersScreen(role: 'maid'))),
          ]),
          const SectionTitle('Needs your attention'),
          Card(
            child: Column(children: [
              for (final (count, label, icon, onTap) in tasks)
                ListTile(
                  leading: Icon(icon, color: count > 0 ? BrandColors.warning : BrandColors.sage),
                  title: Text(label),
                  trailing: StatusChip('$count', color: count > 0 ? BrandColors.warning : Colors.grey),
                  onTap: onTap,
                ),
            ]),
          ),
          if (statusCounts.isNotEmpty) ...[
            const SectionTitle('Bookings by status'),
            Wrap(spacing: 8, runSpacing: 8, children: [
              for (final entry in statusCounts.entries)
                Chip(label: Text('${state.config.bookingStatuses[entry.key] ?? humanize(entry.key)}: ${entry.value}')),
            ]),
          ],
          const SectionTitle('Recent bookings'),
          for (final booking in recent) ...[
            BookingTile(booking: booking, showClient: true, onTap: () => push(AdminBookingDetailScreen(bookingId: booking.id))),
            const SizedBox(height: 10),
          ],
        ]);
      },
    );
  }
}

class AdminMoreScreen extends StatelessWidget {
  const AdminMoreScreen({super.key});

  @override
  Widget build(BuildContext context) {
    void push(Widget screen) => Navigator.of(context).push(MaterialPageRoute(builder: (_) => screen));

    Widget tile(IconData icon, String title, String subtitle, Widget screen) => ListTile(
          leading: CircleAvatar(backgroundColor: BrandColors.teal.withValues(alpha: 0.1), child: Icon(icon, color: BrandColors.teal)),
          title: Text(title, style: const TextStyle(fontWeight: FontWeight.w600)),
          subtitle: Text(subtitle),
          trailing: const Icon(Icons.chevron_right),
          onTap: () => push(screen),
        );

    return AccountScreen(extraTiles: [
      tile(Icons.people_outline, 'Users', 'Clients, maids, ID verification and suspensions', const AdminUsersScreen()),
      tile(Icons.cleaning_services_outlined, 'Services', 'Prices, descriptions and photos', const AdminServicesScreen()),
      tile(Icons.web_outlined, 'Website content', 'Hero slides, steps, about page and banners', const AdminSectionsScreen()),
      tile(Icons.format_quote_outlined, 'Testimonials', 'Reviews shown on the home page', const AdminTestimonialsScreen()),
      tile(Icons.settings_outlined, 'Site settings', 'Branding, contact, payment accounts and rules', const AdminSettingsScreen()),
      tile(Icons.mail_outline, 'Messages', 'Contact form enquiries', const AdminMessagesScreen()),
      const Divider(height: 1),
    ]);
  }
}
