import 'package:flutter/material.dart';

import '../../core/app_state.dart';
import '../../core/models.dart';
import '../../widgets/common.dart';
import '../auth/login_screen.dart';
import '../client/booking_form_screen.dart';
import 'home_screen.dart';

class ServicesScreen extends StatelessWidget {
  const ServicesScreen({super.key, this.standalone = false});

  /// Shown as its own page with an app bar rather than as a tab.
  final bool standalone;

  @override
  Widget build(BuildContext context) {
    final state = AppScope.of(context);

    final body = AsyncView<PageContent>(
      load: () async => PageContent.fromJson(await state.api.get('/pages/services')),
      builder: (context, page, reload) {
        final header = page.first('hero');
        return PageList(
          padding: EdgeInsets.zero,
          children: [
            HeroBlock(
              height: 260,
              badge: header?.badge,
              title: header?.title ?? 'Our services',
              body: header?.body,
              imageUrl: header?.imageUrl,
            ),
            Padding(
              padding: const EdgeInsets.all(16),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.stretch,
                children: [
                  if (page.services.isEmpty) const EmptyState(icon: Icons.cleaning_services_outlined, message: 'No services are published yet.'),
                  for (final service in page.services) ...[
                    ServiceCard(service: service, onBook: bookAction(context, service)),
                    const SizedBox(height: 12),
                  ],
                  if (page.blocks('steps').isNotEmpty) ...[
                    const SectionTitle('Our service guarantee'),
                    StepsList(steps: page.blocks('steps')),
                  ],
                  if (page.first('cta') != null) ...[
                    const SizedBox(height: 12),
                    CallToAction(block: page.first('cta')!),
                  ],
                ],
              ),
            ),
          ],
        );
      },
    );

    return standalone ? Scaffold(appBar: AppBar(title: const Text('Services')), body: body) : body;
  }
}

/// What the "Book" button does for whoever is using the app; maids and admins do not book.
VoidCallback? bookAction(BuildContext context, Service service) {
  final user = AppScope.of(context).user;

  if (user == null) {
    return () => Navigator.of(context).push(MaterialPageRoute(builder: (_) => const LoginScreen()));
  }
  if (!user.isClient) {
    return null;
  }
  return () => Navigator.of(context).push(MaterialPageRoute(builder: (_) => BookingFormScreen(initialService: service)));
}
