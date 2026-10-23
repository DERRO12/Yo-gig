import 'dart:async';

import 'package:flutter/material.dart';

import '../../core/app_state.dart';
import '../../core/format.dart';
import '../../core/models.dart';
import '../../core/theme.dart';
import '../../widgets/common.dart';
import 'service_detail_screen.dart';
import 'site_links.dart';

/// The website home page, built from the hero slides, steps and call to action the admin manages.
class HomeScreen extends StatelessWidget {
  const HomeScreen({super.key});

  @override
  Widget build(BuildContext context) {
    final state = AppScope.of(context);

    return AsyncView<PageContent>(
      load: () async => PageContent.fromJson(await state.api.get('/pages/home')),
      builder: (context, page, reload) => PageList(
        padding: EdgeInsets.zero,
        children: [
          HeroCarousel(slides: page.blocks('hero')),
          Padding(
            padding: const EdgeInsets.symmetric(horizontal: 16),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.stretch,
              children: [
                if (page.totalOpenGigs > 0) ...[
                  const SizedBox(height: 16),
                  _OpenGigsBanner(count: page.totalOpenGigs),
                ],
                if (page.blocks('steps').isNotEmpty) ...[
                  const SectionTitle('How escrow works', subtitle: 'You pay us, we hold it, the maid is paid when you approve.'),
                  StepsList(steps: page.blocks('steps')),
                ],
                if (page.featuredServices.isNotEmpty) ...[
                  const SectionTitle('Popular services'),
                  for (final service in page.featuredServices) ...[
                    ServiceCard(service: service),
                    const SizedBox(height: 12),
                  ],
                ],
                if (page.freshGigs.isNotEmpty) ...[
                  const SectionTitle('Fresh funded gigs', subtitle: 'Paid in escrow and waiting for a maid.'),
                  for (final gig in page.freshGigs) ...[
                    BookingTile(booking: gig),
                    const SizedBox(height: 10),
                  ],
                ],
                if (page.testimonials.isNotEmpty) ...[
                  const SectionTitle('What people say'),
                  TestimonialStrip(testimonials: page.testimonials),
                ],
                if (page.first('cta') != null) ...[
                  const SizedBox(height: 20),
                  CallToAction(block: page.first('cta')!),
                ],
                const SizedBox(height: 24),
              ],
            ),
          ),
        ],
      ),
    );
  }
}

class _OpenGigsBanner extends StatelessWidget {
  const _OpenGigsBanner({required this.count});

  final int count;

  @override
  Widget build(BuildContext context) {
    return Panel(
      color: BrandColors.gold.withValues(alpha: 0.18),
      children: [
        Row(children: [
          const Icon(Icons.work_outline, color: BrandColors.tealDark),
          const SizedBox(width: 10),
          Expanded(
            child: Text('$count funded ${count == 1 ? 'gig is' : 'gigs are'} open for maids right now.',
                style: const TextStyle(fontWeight: FontWeight.w600)),
          ),
        ]),
      ],
    );
  }
}

class HeroCarousel extends StatefulWidget {
  const HeroCarousel({super.key, required this.slides});

  final List<PageSection> slides;

  @override
  State<HeroCarousel> createState() => _HeroCarouselState();
}

class _HeroCarouselState extends State<HeroCarousel> {
  final _controller = PageController();
  Timer? _timer;
  int _page = 0;

  @override
  void initState() {
    super.initState();
    if (widget.slides.length > 1) {
      _timer = Timer.periodic(const Duration(seconds: 6), (_) {
        if (_controller.hasClients) {
          _controller.animateToPage((_page + 1) % widget.slides.length, duration: const Duration(milliseconds: 500), curve: Curves.easeInOut);
        }
      });
    }
  }

  @override
  void dispose() {
    _timer?.cancel();
    _controller.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final config = AppScope.of(context).config;

    if (widget.slides.isEmpty) {
      return HeroBlock(title: config.siteName, body: config.tagline);
    }

    return SizedBox(
      height: 440,
      child: Stack(
        children: [
          PageView(
            controller: _controller,
            onPageChanged: (page) => setState(() => _page = page),
            children: [
              for (final slide in widget.slides)
                HeroBlock(
                  badge: slide.badge,
                  title: slide.title ?? config.siteName,
                  body: slide.body,
                  imageUrl: slide.imageUrl,
                  buttonText: slide.buttonText,
                  buttonUrl: slide.buttonUrl,
                  secondaryText: slide.secondaryButtonText,
                  secondaryUrl: slide.secondaryButtonUrl,
                ),
            ],
          ),
          if (widget.slides.length > 1)
            Positioned(
              bottom: 14,
              left: 0,
              right: 0,
              child: Row(
                mainAxisAlignment: MainAxisAlignment.center,
                children: [
                  for (var i = 0; i < widget.slides.length; i++)
                    AnimatedContainer(
                      duration: const Duration(milliseconds: 250),
                      margin: const EdgeInsets.symmetric(horizontal: 3),
                      width: i == _page ? 22 : 8,
                      height: 8,
                      decoration: BoxDecoration(
                        color: i == _page ? BrandColors.gold : Colors.white70,
                        borderRadius: BorderRadius.circular(4),
                      ),
                    ),
                ],
              ),
            ),
        ],
      ),
    );
  }
}

/// A full-bleed banner with a background image, used for page headers and hero slides.
class HeroBlock extends StatelessWidget {
  const HeroBlock({
    super.key,
    required this.title,
    this.badge,
    this.body,
    this.imageUrl,
    this.buttonText,
    this.buttonUrl,
    this.secondaryText,
    this.secondaryUrl,
    this.height = 440,
  });

  final String title;
  final String? badge;
  final String? body;
  final String? imageUrl;
  final String? buttonText;
  final String? buttonUrl;
  final String? secondaryText;
  final String? secondaryUrl;
  final double height;

  @override
  Widget build(BuildContext context) {
    return SizedBox(
      height: height,
      child: Stack(
        fit: StackFit.expand,
        children: [
          if (imageUrl != null) NetImage(imageUrl, radius: 0),
          DecoratedBox(
            decoration: BoxDecoration(
              gradient: LinearGradient(
                begin: Alignment.topCenter,
                end: Alignment.bottomCenter,
                colors: [
                  BrandColors.tealDark.withValues(alpha: imageUrl == null ? 1 : 0.55),
                  BrandColors.tealDark.withValues(alpha: imageUrl == null ? 1 : 0.9),
                ],
              ),
            ),
          ),
          Padding(
            padding: const EdgeInsets.fromLTRB(20, 24, 20, 40),
            child: Column(
              mainAxisAlignment: MainAxisAlignment.end,
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                if (badge != null)
                  Container(
                    padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                    decoration: BoxDecoration(color: BrandColors.gold, borderRadius: BorderRadius.circular(20)),
                    child: Text(badge!, style: const TextStyle(color: BrandColors.tealDark, fontWeight: FontWeight.w700, fontSize: 12)),
                  ),
                const SizedBox(height: 12),
                Text(title,
                    maxLines: 4,
                    overflow: TextOverflow.ellipsis,
                    style: const TextStyle(color: Colors.white, fontSize: 28, fontWeight: FontWeight.w900, height: 1.15)),
                if (body != null) ...[
                  const SizedBox(height: 10),
                  Text(body!, maxLines: 5, overflow: TextOverflow.ellipsis, style: const TextStyle(color: Colors.white70, fontSize: 15, height: 1.4)),
                ],
                if (buttonText != null || secondaryText != null) ...[
                  const SizedBox(height: 18),
                  Wrap(spacing: 10, runSpacing: 10, children: [
                    if (buttonText != null)
                      FilledButton(
                        style: FilledButton.styleFrom(backgroundColor: BrandColors.gold, foregroundColor: BrandColors.tealDark),
                        onPressed: () => openSiteLink(context, buttonUrl),
                        child: Text(buttonText!),
                      ),
                    if (secondaryText != null)
                      OutlinedButton(
                        style: OutlinedButton.styleFrom(foregroundColor: Colors.white, side: const BorderSide(color: Colors.white70)),
                        onPressed: () => openSiteLink(context, secondaryUrl),
                        child: Text(secondaryText!),
                      ),
                  ]),
                ],
              ],
            ),
          ),
        ],
      ),
    );
  }
}

class StepsList extends StatelessWidget {
  const StepsList({super.key, required this.steps});

  final List<PageSection> steps;

  @override
  Widget build(BuildContext context) {
    return Column(
      children: [
        for (var i = 0; i < steps.length; i++)
          Padding(
            padding: const EdgeInsets.only(bottom: 10),
            child: Card(
              child: Padding(
                padding: const EdgeInsets.all(14),
                child: Row(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    CircleAvatar(
                      backgroundColor: BrandColors.teal,
                      foregroundColor: BrandColors.gold,
                      child: Text(steps[i].badge ?? '${i + 1}', style: const TextStyle(fontWeight: FontWeight.w800)),
                    ),
                    const SizedBox(width: 14),
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          if (steps[i].title != null) Text(steps[i].title!, style: const TextStyle(fontWeight: FontWeight.w700, fontSize: 15)),
                          if (steps[i].body != null) ...[
                            const SizedBox(height: 4),
                            Text(steps[i].body!, style: TextStyle(color: Colors.grey.shade800, height: 1.35)),
                          ],
                        ],
                      ),
                    ),
                  ],
                ),
              ),
            ),
          ),
      ],
    );
  }
}

class ServiceCard extends StatelessWidget {
  const ServiceCard({super.key, required this.service, this.onBook});

  final Service service;
  final VoidCallback? onBook;

  @override
  Widget build(BuildContext context) {
    final currency = AppScope.of(context).config.currency;

    return Card(
      clipBehavior: Clip.antiAlias,
      child: InkWell(
        onTap: () => Navigator.of(context).push(MaterialPageRoute(builder: (_) => ServiceDetailScreen(service: service))),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            NetImage(service.imageUrl, height: 160, radius: 0),
            Padding(
              padding: const EdgeInsets.all(14),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  if (service.category != null) StatusChip(service.category!, color: BrandColors.sage),
                  const SizedBox(height: 8),
                  Text(service.name, style: const TextStyle(fontWeight: FontWeight.w800, fontSize: 17)),
                  if (service.shortDescription != null) ...[
                    const SizedBox(height: 4),
                    Text(service.shortDescription!, maxLines: 3, overflow: TextOverflow.ellipsis, style: TextStyle(color: Colors.grey.shade800)),
                  ],
                  const SizedBox(height: 10),
                  Row(
                    children: [
                      Expanded(
                        child: Text(
                          service.priceLabel ?? (service.hasFixedPrice ? '${money(service.basePrice, currency)} ${service.priceUnit}' : 'Custom quote'),
                          style: const TextStyle(fontWeight: FontWeight.w800, color: BrandColors.teal),
                        ),
                      ),
                      if (onBook != null) FilledButton(style: FilledButton.styleFrom(minimumSize: const Size(0, 40)), onPressed: onBook, child: const Text('Book')),
                    ],
                  ),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }
}

class TestimonialStrip extends StatelessWidget {
  const TestimonialStrip({super.key, required this.testimonials});

  final List<Testimonial> testimonials;

  @override
  Widget build(BuildContext context) {
    return SizedBox(
      height: 210,
      child: ListView.separated(
        scrollDirection: Axis.horizontal,
        itemCount: testimonials.length,
        separatorBuilder: (_, _) => const SizedBox(width: 12),
        itemBuilder: (context, index) {
          final testimonial = testimonials[index];
          return SizedBox(
            width: 280,
            child: Card(
              child: Padding(
                padding: const EdgeInsets.all(16),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Row(children: [
                      for (var i = 0; i < 5; i++)
                        Icon(i < testimonial.rating ? Icons.star_rounded : Icons.star_outline_rounded, color: BrandColors.gold, size: 18),
                    ]),
                    const SizedBox(height: 8),
                    Expanded(
                      child: Text('"${testimonial.quote}"', maxLines: 5, overflow: TextOverflow.ellipsis, style: const TextStyle(height: 1.4)),
                    ),
                    Row(children: [
                      if (testimonial.photoUrl != null) ...[
                        NetImage(testimonial.photoUrl, width: 36, height: 36, radius: 18),
                        const SizedBox(width: 10),
                      ],
                      Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(testimonial.name, style: const TextStyle(fontWeight: FontWeight.w700)),
                            if (testimonial.role != null) Text(testimonial.role!, style: TextStyle(color: Colors.grey.shade700, fontSize: 12)),
                          ],
                        ),
                      ),
                    ]),
                  ],
                ),
              ),
            ),
          );
        },
      ),
    );
  }
}

class CallToAction extends StatelessWidget {
  const CallToAction({super.key, required this.block});

  final PageSection block;

  @override
  Widget build(BuildContext context) {
    return ClipRRect(
      borderRadius: BorderRadius.circular(18),
      child: HeroBlock(
        height: 300,
        title: block.title ?? '',
        badge: block.badge,
        body: block.body,
        imageUrl: block.imageUrl,
        buttonText: block.buttonText,
        buttonUrl: block.buttonUrl,
        secondaryText: block.secondaryButtonText,
        secondaryUrl: block.secondaryButtonUrl,
      ),
    );
  }
}
