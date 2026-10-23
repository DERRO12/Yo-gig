import 'package:flutter/material.dart';

import '../../core/app_state.dart';
import '../../core/models.dart';
import '../../core/theme.dart';
import '../../widgets/common.dart';
import 'home_screen.dart';

class AboutScreen extends StatelessWidget {
  const AboutScreen({super.key, this.standalone = false});

  final bool standalone;

  @override
  Widget build(BuildContext context) {
    final state = AppScope.of(context);

    final body = AsyncView<PageContent>(
      load: () async => PageContent.fromJson(await state.api.get('/pages/about')),
      builder: (context, page, reload) {
        final header = page.first('hero');
        return PageList(
          padding: EdgeInsets.zero,
          children: [
            HeroBlock(
              height: 300,
              badge: header?.badge,
              title: header?.title ?? 'About ${state.config.siteName}',
              body: header?.body,
              imageUrl: header?.imageUrl,
            ),
            Padding(
              padding: const EdgeInsets.all(16),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.stretch,
                children: [
                  for (final card in page.blocks('mission')) ...[
                    Panel(children: [
                      if (card.badge != null) StatusChip(card.badge!, color: BrandColors.gold),
                      const SizedBox(height: 8),
                      if (card.title != null) Text(card.title!, style: const TextStyle(fontWeight: FontWeight.w800, fontSize: 18)),
                      if (card.body != null) ...[const SizedBox(height: 6), Text(card.body!, style: const TextStyle(height: 1.45))],
                    ]),
                    const SizedBox(height: 12),
                  ],
                  if (page.blocks('pillars').isNotEmpty) ...[
                    const SectionTitle('How we support maids'),
                    StepsList(steps: page.blocks('pillars')),
                  ],
                  if (page.blocks('stats').isNotEmpty) ...[
                    const SectionTitle('Our impact'),
                    StatGrid(children: [
                      for (final stat in page.blocks('stats'))
                        Card(
                          color: BrandColors.teal,
                          child: Padding(
                            padding: const EdgeInsets.all(16),
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                Text(stat.title ?? '', style: const TextStyle(color: BrandColors.gold, fontSize: 26, fontWeight: FontWeight.w900)),
                                const SizedBox(height: 6),
                                Text(stat.body ?? '', style: const TextStyle(color: Colors.white, height: 1.35)),
                              ],
                            ),
                          ),
                        ),
                    ]),
                  ],
                  if (page.first('cta') != null) ...[
                    const SizedBox(height: 20),
                    CallToAction(block: page.first('cta')!),
                  ],
                ],
              ),
            ),
          ],
        );
      },
    );

    return standalone ? Scaffold(appBar: AppBar(title: const Text('About us')), body: body) : body;
  }
}
