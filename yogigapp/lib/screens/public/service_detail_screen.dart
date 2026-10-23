import 'package:flutter/material.dart';

import '../../core/app_state.dart';
import '../../core/format.dart';
import '../../core/models.dart';
import '../../core/theme.dart';
import '../../widgets/common.dart';
import 'services_screen.dart';

class ServiceDetailScreen extends StatelessWidget {
  const ServiceDetailScreen({super.key, required this.service});

  final Service service;

  @override
  Widget build(BuildContext context) {
    final currency = AppScope.of(context).config.currency;
    final onBook = bookAction(context, service);

    return Scaffold(
      appBar: AppBar(title: Text(service.name)),
      body: ListView(
        children: [
          NetImage(service.imageUrl, height: 240, radius: 0),
          Padding(
            padding: const EdgeInsets.all(16),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                if (service.category != null) StatusChip(service.category!),
                const SizedBox(height: 10),
                Text(service.name, style: const TextStyle(fontSize: 24, fontWeight: FontWeight.w900, color: BrandColors.tealDark)),
                const SizedBox(height: 8),
                Text(
                  service.priceLabel ?? (service.hasFixedPrice ? '${money(service.basePrice, currency)} ${service.priceUnit}' : 'Priced on a custom quote'),
                  style: const TextStyle(fontSize: 18, fontWeight: FontWeight.w800, color: BrandColors.teal),
                ),
                const SizedBox(height: 16),
                Text(service.description ?? service.shortDescription ?? '', style: const TextStyle(fontSize: 15, height: 1.5)),
                const SizedBox(height: 20),
                const Panel(
                  title: 'Protected by escrow',
                  children: [
                    Text('You pay the platform, not a stranger. We hold the money until you approve the finished work, then release it to the maid.'),
                  ],
                ),
              ],
            ),
          ),
        ],
      ),
      bottomNavigationBar: onBook == null
          ? null
          : SafeArea(
              child: Padding(
                padding: const EdgeInsets.all(16),
                child: FilledButton.icon(onPressed: onBook, icon: const Icon(Icons.event_available), label: const Text('Book this service')),
              ),
            ),
    );
  }
}
