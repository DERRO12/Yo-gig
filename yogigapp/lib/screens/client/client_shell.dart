import 'package:flutter/material.dart';

import '../../core/app_state.dart';
import '../../core/format.dart';
import '../../core/models.dart';
import '../../core/theme.dart';
import '../../widgets/common.dart';
import '../public/home_screen.dart';
import '../public/services_screen.dart';
import '../shared/account_screen.dart';
import 'booking_detail_screen.dart';
import 'booking_form_screen.dart';

class ClientShell extends StatefulWidget {
  const ClientShell({super.key});

  @override
  State<ClientShell> createState() => _ClientShellState();
}

class _ClientShellState extends State<ClientShell> {
  int _index = 0;
  final _bookingsKey = GlobalKey<AsyncViewState<Map<String, dynamic>>>();

  Future<void> _book() async {
    await Navigator.of(context).push(MaterialPageRoute(builder: (_) => const BookingFormScreen()));
    _bookingsKey.currentState?.reload();
  }

  @override
  Widget build(BuildContext context) {
    const titles = ['My bookings', 'Home', 'Services', 'Account'];

    return Scaffold(
      appBar: AppBar(title: Text(titles[_index])),
      body: IndexedStack(
        index: _index,
        children: [
          ClientDashboard(viewKey: _bookingsKey),
          const HomeScreen(),
          const ServicesScreen(),
          const AccountScreen(),
        ],
      ),
      floatingActionButton: _index <= 1
          ? FloatingActionButton.extended(onPressed: _book, icon: const Icon(Icons.add), label: const Text('Book a maid'))
          : null,
      bottomNavigationBar: NavigationBar(
        selectedIndex: _index,
        onDestinationSelected: (index) {
          setState(() => _index = index);
          if (index == 0) {
            _bookingsKey.currentState?.reload();
          }
        },
        destinations: const [
          NavigationDestination(icon: Icon(Icons.dashboard_outlined), selectedIcon: Icon(Icons.dashboard), label: 'Bookings'),
          NavigationDestination(icon: Icon(Icons.home_outlined), selectedIcon: Icon(Icons.home), label: 'Home'),
          NavigationDestination(icon: Icon(Icons.cleaning_services_outlined), selectedIcon: Icon(Icons.cleaning_services), label: 'Services'),
          NavigationDestination(icon: Icon(Icons.person_outline), selectedIcon: Icon(Icons.person), label: 'Account'),
        ],
      ),
    );
  }
}

/// Booking stats and every booking the client has made.
class ClientDashboard extends StatelessWidget {
  const ClientDashboard({super.key, required this.viewKey});

  final GlobalKey<AsyncViewState<Map<String, dynamic>>> viewKey;

  @override
  Widget build(BuildContext context) {
    final state = AppScope.of(context);
    final currency = state.config.currency;

    return AsyncView<Map<String, dynamic>>(
      key: viewKey,
      load: () => state.api.get('/client/dashboard'),
      builder: (context, data, reload) {
        final stats = data['stats'] as Map<String, dynamic>? ?? {};
        final bookings = listOf(data['bookings'], Booking.fromJson);

        Future<void> open(Booking booking) async {
          await Navigator.of(context).push(MaterialPageRoute(builder: (_) => BookingDetailScreen(bookingId: booking.id)));
          await reload();
        }

        return PageList(children: [
          Text('Hello, ${state.user!.name.split(' ').first}', style: const TextStyle(fontSize: 22, fontWeight: FontWeight.w800, color: BrandColors.tealDark)),
          const SizedBox(height: 4),
          Text('Track your bookings, payments and approvals.', style: TextStyle(color: Colors.grey.shade700)),
          const SizedBox(height: 16),
          StatGrid(children: [
            StatCard(label: 'Active bookings', value: '${stats['active'] ?? 0}', icon: Icons.event_note_outlined),
            StatCard(label: 'Awaiting payment', value: '${stats['awaiting_payment'] ?? 0}', icon: Icons.payments_outlined, color: BrandColors.danger),
            StatCard(label: 'Awaiting your approval', value: '${stats['awaiting_approval'] ?? 0}', icon: Icons.task_alt, color: BrandColors.warning),
            StatCard(label: 'Held in escrow', value: money(stats['in_escrow'] as num?, currency), icon: Icons.lock_outline, color: BrandColors.success),
          ]),
          const SectionTitle('Your bookings'),
          if (bookings.isEmpty)
            const EmptyState(icon: Icons.event_busy_outlined, message: 'You have no bookings yet. Tap "Book a maid" to get started.'),
          for (final booking in bookings) ...[
            BookingTile(booking: booking, onTap: () => open(booking)),
            const SizedBox(height: 10),
          ],
          const SizedBox(height: 70),
        ]);
      },
    );
  }
}
