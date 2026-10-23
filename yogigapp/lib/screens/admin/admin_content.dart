import 'package:flutter/material.dart';
import 'package:image_picker/image_picker.dart';

import '../../core/api_client.dart';
import '../../core/app_state.dart';
import '../../core/format.dart';
import '../../core/models.dart';
import '../../core/theme.dart';
import '../../widgets/common.dart';
import '../../widgets/image_field.dart';
import '../../widgets/paged_list.dart';

/// Saves a content form as multipart (so images can be attached) and closes it on success.
Future<void> _saveForm(
  BuildContext context, {
  required String path,
  required bool isNew,
  required Map<String, dynamic> fields,
  required List<UploadFile> files,
  required void Function(ApiException) onError,
}) async {
  try {
    final response = await AppScope.read(context).api.multipart(path, method: isNew ? 'POST' : 'PUT', fields: fields, files: files);
    if (context.mounted) {
      showMessage(context, response['message']?.toString() ?? 'Saved.');
      Navigator.pop(context, true);
    }
  } on ApiException catch (error) {
    onError(error);
  }
}

Future<void> _delete(BuildContext context, String path, String what, Future<void> Function() reload) async {
  if (await confirm(context, title: 'Delete $what?', message: 'This cannot be undone.', action: 'Delete', destructive: true) && context.mounted) {
    if (await runAction(context, () => AppScope.read(context).api.delete(path))) {
      await reload();
    }
  }
}

Widget _errorText(ApiException? error) =>
    error != null && error.errors.isEmpty ? Padding(padding: const EdgeInsets.only(top: 8), child: Text(error.message, style: const TextStyle(color: BrandColors.danger))) : const SizedBox.shrink();

/*
|--------------------------------------------------------------------------
| Services
|--------------------------------------------------------------------------
*/

class AdminServicesScreen extends StatefulWidget {
  const AdminServicesScreen({super.key});

  @override
  State<AdminServicesScreen> createState() => _AdminServicesScreenState();
}

class _AdminServicesScreenState extends State<AdminServicesScreen> {
  final viewKey = GlobalKey<AsyncViewState<Map<String, dynamic>>>();

  @override
  Widget build(BuildContext context) {
    final state = AppScope.of(context);
    final currency = state.config.currency;

    Future<void> openForm([Service? service]) async {
      final saved = await Navigator.of(context).push<bool>(MaterialPageRoute(builder: (_) => ServiceFormScreen(service: service)));
      if (saved == true) {
        await viewKey.currentState?.reload();
      }
    }

    return Scaffold(
      appBar: AppBar(title: const Text('Services')),
      floatingActionButton: FloatingActionButton.extended(onPressed: openForm, icon: const Icon(Icons.add), label: const Text('New service')),
      body: AsyncView<Map<String, dynamic>>(
        key: viewKey,
        load: () => state.api.get('/admin/services'),
        builder: (context, data, reload) {
          final services = listOf(data, Service.fromJson);
          return PageList(children: [
            if (services.isEmpty) const EmptyState(icon: Icons.cleaning_services_outlined, message: 'No services yet.'),
            for (final service in services)
              Padding(
                padding: const EdgeInsets.only(bottom: 10),
                child: Card(
                  child: ListTile(
                    leading: NetImage(service.imageUrl, width: 56, height: 56, radius: 10),
                    title: Text(service.name, style: const TextStyle(fontWeight: FontWeight.w700)),
                    subtitle: Text('${service.hasFixedPrice ? '${money(service.basePrice, currency)} ${service.priceUnit}' : 'Custom quote'}'
                        '\n${service.bookingsCount ?? 0} bookings'),
                    isThreeLine: true,
                    trailing: Column(mainAxisAlignment: MainAxisAlignment.center, crossAxisAlignment: CrossAxisAlignment.end, children: [
                      StatusChip(service.isActive ? 'Live' : 'Hidden', color: service.isActive ? BrandColors.success : Colors.grey),
                      if (service.isFeatured) const Padding(padding: EdgeInsets.only(top: 4), child: StatusChip('Featured', color: BrandColors.gold)),
                    ]),
                    onTap: () => openForm(service),
                    onLongPress: () => _delete(context, '/admin/services/${service.id}', service.name, reload),
                  ),
                ),
              ),
            const Text('Tap to edit, long-press to delete.', textAlign: TextAlign.center, style: TextStyle(color: Colors.grey)),
            const SizedBox(height: 80),
          ]);
        },
      ),
    );
  }
}

class ServiceFormScreen extends StatefulWidget {
  const ServiceFormScreen({super.key, this.service});

  final Service? service;

  @override
  State<ServiceFormScreen> createState() => _ServiceFormScreenState();
}

class _ServiceFormScreenState extends State<ServiceFormScreen> {
  late final _name = TextEditingController(text: widget.service?.name ?? '');
  late final _category = TextEditingController(text: widget.service?.category ?? '');
  late final _short = TextEditingController(text: widget.service?.shortDescription ?? '');
  late final _description = TextEditingController(text: widget.service?.description ?? '');
  late final _price = TextEditingController(text: widget.service == null ? '' : widget.service!.basePrice.toStringAsFixed(0));
  late final _unit = TextEditingController(text: widget.service?.priceUnit ?? 'per visit');
  late final _priceLabel = TextEditingController(text: widget.service?.priceLabel ?? '');
  late final _order = TextEditingController(text: '${widget.service?.sortOrder ?? 0}');
  late bool _active = widget.service?.isActive ?? true;
  late bool _featured = widget.service?.isFeatured ?? false;
  XFile? _image;
  ApiException? _error;

  @override
  void dispose() {
    for (final controller in [_name, _category, _short, _description, _price, _unit, _priceLabel, _order]) {
      controller.dispose();
    }
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final isNew = widget.service == null;

    return Scaffold(
      appBar: AppBar(title: Text(isNew ? 'New service' : 'Edit service')),
      body: ListView(padding: const EdgeInsets.all(16), children: [
        TextField(controller: _name, decoration: InputDecoration(labelText: 'Name', errorText: _error?.fieldError('name') ?? _error?.fieldError('slug'))),
        const SizedBox(height: 12),
        TextField(controller: _category, decoration: const InputDecoration(labelText: 'Category (e.g. Residential)')),
        const SizedBox(height: 12),
        TextField(controller: _short, maxLines: 2, decoration: const InputDecoration(labelText: 'Short description')),
        const SizedBox(height: 12),
        TextField(controller: _description, maxLines: 5, decoration: const InputDecoration(labelText: 'Full description', alignLabelWithHint: true)),
        const SizedBox(height: 12),
        Row(children: [
          Expanded(
            child: TextField(
              controller: _price,
              keyboardType: TextInputType.number,
              decoration: InputDecoration(labelText: 'Base price (0 = quote)', errorText: _error?.fieldError('base_price')),
            ),
          ),
          const SizedBox(width: 10),
          Expanded(child: TextField(controller: _unit, decoration: InputDecoration(labelText: 'Price unit', errorText: _error?.fieldError('price_unit')))),
        ]),
        const SizedBox(height: 12),
        TextField(controller: _priceLabel, decoration: const InputDecoration(labelText: 'Price label override (optional)')),
        const SizedBox(height: 12),
        TextField(controller: _order, keyboardType: TextInputType.number, decoration: const InputDecoration(labelText: 'Sort order')),
        SwitchListTile(contentPadding: EdgeInsets.zero, title: const Text('Visible on the website and app'), value: _active, onChanged: (v) => setState(() => _active = v)),
        SwitchListTile(contentPadding: EdgeInsets.zero, title: const Text('Featured on the home page'), value: _featured, onChanged: (v) => setState(() => _featured = v)),
        ImageField(label: 'Photo', currentUrl: widget.service?.imageUrl, onPicked: (file) => _image = file),
        _errorText(_error),
        const SizedBox(height: 16),
        BusyButton(
          label: 'Save service',
          icon: Icons.save_outlined,
          onPressed: () => _saveForm(
            context,
            path: isNew ? '/admin/services' : '/admin/services/${widget.service!.id}',
            isNew: isNew,
            fields: {
              'name': _name.text.trim(),
              'category': _category.text.trim(),
              'short_description': _short.text.trim(),
              'description': _description.text.trim(),
              'base_price': _price.text.trim().isEmpty ? '0' : _price.text.trim(),
              'price_unit': _unit.text.trim(),
              'price_label': _priceLabel.text.trim(),
              'sort_order': _order.text.trim(),
              'is_active': _active,
              'is_featured': _featured,
            },
            files: [if (_image != null) UploadFile('image', _image!)],
            onError: (error) => setState(() => _error = error),
          ),
        ),
      ]),
    );
  }
}

/*
|--------------------------------------------------------------------------
| Page sections
|--------------------------------------------------------------------------
*/

class AdminSectionsScreen extends StatefulWidget {
  const AdminSectionsScreen({super.key});

  @override
  State<AdminSectionsScreen> createState() => _AdminSectionsScreenState();
}

class _AdminSectionsScreenState extends State<AdminSectionsScreen> {
  String _page = 'home';
  final _viewKey = GlobalKey<AsyncViewState<Map<String, dynamic>>>();

  @override
  Widget build(BuildContext context) {
    final state = AppScope.of(context);
    final pages = state.config.pages;

    Future<void> openForm(PageSection? section, String sectionKey) async {
      final saved = await Navigator.of(context).push<bool>(MaterialPageRoute(
        builder: (_) => SectionFormScreen(section: section, page: _page, sectionKey: sectionKey, sectionLabels: pages[_page] ?? {}),
      ));
      if (saved == true) {
        await _viewKey.currentState?.reload();
      }
    }

    return Scaffold(
      appBar: AppBar(title: const Text('Website content')),
      body: Column(children: [
        Padding(
          padding: const EdgeInsets.fromLTRB(16, 16, 16, 0),
          child: FilterChips(
            options: {for (final page in (pages.isEmpty ? const {'home': <String, String>{}} : pages).keys) page: humanize(page)},
            selected: _page,
            onSelected: (page) {
              setState(() => _page = page);
              _viewKey.currentState?.reload();
            },
          ),
        ),
        Expanded(
          child: AsyncView<Map<String, dynamic>>(
            key: _viewKey,
            load: () => state.api.get('/admin/sections', query: {'page': _page}),
            builder: (context, data, reload) {
              final blocks = listOf(data, PageSection.fromJson);
              final labels = pages[_page] ?? {for (final block in blocks) block.section: humanize(block.section)};

              return PageList(children: [
                const Text('These blocks are shown on the website and in the app. Changes appear on both straight away.', style: TextStyle(color: Colors.grey)),
                for (final entry in labels.entries) ...[
                  SectionTitle(entry.value, trailing: IconButton.filledTonal(onPressed: () => openForm(null, entry.key), icon: const Icon(Icons.add))),
                  for (final block in blocks.where((block) => block.section == entry.key))
                    Padding(
                      padding: const EdgeInsets.only(bottom: 8),
                      child: Card(
                        child: ListTile(
                          leading: block.imageUrl == null ? null : NetImage(block.imageUrl, width: 56, height: 56, radius: 10),
                          title: Text(block.title ?? block.badge ?? '(untitled)', maxLines: 2, overflow: TextOverflow.ellipsis),
                          subtitle: Text(block.body ?? '', maxLines: 2, overflow: TextOverflow.ellipsis),
                          trailing: Row(mainAxisSize: MainAxisSize.min, children: [
                            if (!block.isActive) const StatusChip('Hidden', color: Colors.grey),
                            IconButton(
                              icon: const Icon(Icons.delete_outline, color: BrandColors.danger),
                              onPressed: () => _delete(context, '/admin/sections/${block.id}', 'this block', reload),
                            ),
                          ]),
                          onTap: () => openForm(block, block.section),
                        ),
                      ),
                    ),
                  if (!blocks.any((block) => block.section == entry.key))
                    const Text('No blocks yet. Tap + to add one.', style: TextStyle(color: Colors.grey)),
                ],
                const SizedBox(height: 24),
              ]);
            },
          ),
        ),
      ]),
    );
  }
}

class SectionFormScreen extends StatefulWidget {
  const SectionFormScreen({super.key, this.section, required this.page, required this.sectionKey, required this.sectionLabels});

  final PageSection? section;
  final String page;
  final String sectionKey;
  final Map<String, String> sectionLabels;

  @override
  State<SectionFormScreen> createState() => _SectionFormScreenState();
}

class _SectionFormScreenState extends State<SectionFormScreen> {
  late final _badge = TextEditingController(text: widget.section?.badge ?? '');
  late final _title = TextEditingController(text: widget.section?.title ?? '');
  late final _body = TextEditingController(text: widget.section?.body ?? '');
  late final _button = TextEditingController(text: widget.section?.buttonText ?? '');
  late final _buttonUrl = TextEditingController(text: widget.section?.buttonUrl ?? '');
  late final _secondary = TextEditingController(text: widget.section?.secondaryButtonText ?? '');
  late final _secondaryUrl = TextEditingController(text: widget.section?.secondaryButtonUrl ?? '');
  late final _order = TextEditingController(text: '${widget.section?.sortOrder ?? 0}');
  late String _sectionKey = widget.sectionKey;
  late bool _active = widget.section?.isActive ?? true;
  bool _removeImage = false;
  XFile? _image;
  ApiException? _error;

  @override
  void dispose() {
    for (final controller in [_badge, _title, _body, _button, _buttonUrl, _secondary, _secondaryUrl, _order]) {
      controller.dispose();
    }
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final isNew = widget.section == null;
    final labels = widget.sectionLabels.isEmpty ? {_sectionKey: humanize(_sectionKey)} : widget.sectionLabels;

    return Scaffold(
      appBar: AppBar(title: Text(isNew ? 'New content block' : 'Edit content block')),
      body: ListView(padding: const EdgeInsets.all(16), children: [
        DropdownButtonFormField<String>(
          initialValue: labels.containsKey(_sectionKey) ? _sectionKey : labels.keys.first,
          decoration: InputDecoration(labelText: 'Section on the ${humanize(widget.page)} page', errorText: _error?.fieldError('section')),
          items: [for (final entry in labels.entries) DropdownMenuItem(value: entry.key, child: Text(entry.value))],
          onChanged: (value) => setState(() => _sectionKey = value ?? _sectionKey),
        ),
        const SizedBox(height: 12),
        TextField(controller: _badge, decoration: const InputDecoration(labelText: 'Badge / small label')),
        const SizedBox(height: 12),
        TextField(controller: _title, decoration: InputDecoration(labelText: 'Title', errorText: _error?.fieldError('title'))),
        const SizedBox(height: 12),
        TextField(controller: _body, maxLines: 5, decoration: const InputDecoration(labelText: 'Text', alignLabelWithHint: true)),
        const SizedBox(height: 12),
        Row(children: [
          Expanded(child: TextField(controller: _button, decoration: const InputDecoration(labelText: 'Button text'))),
          const SizedBox(width: 10),
          Expanded(child: TextField(controller: _buttonUrl, decoration: const InputDecoration(labelText: 'Button link', hintText: '/services'))),
        ]),
        const SizedBox(height: 12),
        Row(children: [
          Expanded(child: TextField(controller: _secondary, decoration: const InputDecoration(labelText: 'Second button'))),
          const SizedBox(width: 10),
          Expanded(child: TextField(controller: _secondaryUrl, decoration: const InputDecoration(labelText: 'Second link'))),
        ]),
        const SizedBox(height: 12),
        TextField(controller: _order, keyboardType: TextInputType.number, decoration: const InputDecoration(labelText: 'Sort order')),
        SwitchListTile(contentPadding: EdgeInsets.zero, title: const Text('Visible'), value: _active, onChanged: (v) => setState(() => _active = v)),
        ImageField(
          label: 'Image',
          currentUrl: widget.section?.imageUrl,
          onPicked: (file) {
            _image = file;
            if (file != null) {
              _removeImage = false;
            }
          },
          onRemove: () => _removeImage = true,
        ),
        _errorText(_error),
        const SizedBox(height: 16),
        BusyButton(
          label: 'Save block',
          icon: Icons.save_outlined,
          onPressed: () => _saveForm(
            context,
            path: isNew ? '/admin/sections' : '/admin/sections/${widget.section!.id}',
            isNew: isNew,
            fields: {
              'page': widget.page,
              'section': _sectionKey,
              'badge': _badge.text.trim(),
              'title': _title.text.trim(),
              'body': _body.text.trim(),
              'button_text': _button.text.trim(),
              'button_url': _buttonUrl.text.trim(),
              'secondary_button_text': _secondary.text.trim(),
              'secondary_button_url': _secondaryUrl.text.trim(),
              'sort_order': _order.text.trim(),
              'is_active': _active,
              'remove_image': _removeImage,
            },
            files: [if (_image != null) UploadFile('image', _image!)],
            onError: (error) => setState(() => _error = error),
          ),
        ),
      ]),
    );
  }
}

/*
|--------------------------------------------------------------------------
| Testimonials
|--------------------------------------------------------------------------
*/

class AdminTestimonialsScreen extends StatefulWidget {
  const AdminTestimonialsScreen({super.key});

  @override
  State<AdminTestimonialsScreen> createState() => _AdminTestimonialsScreenState();
}

class _AdminTestimonialsScreenState extends State<AdminTestimonialsScreen> {
  final viewKey = GlobalKey<AsyncViewState<Map<String, dynamic>>>();

  @override
  Widget build(BuildContext context) {
    final state = AppScope.of(context);

    Future<void> openForm([Testimonial? testimonial]) async {
      final saved = await Navigator.of(context).push<bool>(MaterialPageRoute(builder: (_) => TestimonialFormScreen(testimonial: testimonial)));
      if (saved == true) {
        await viewKey.currentState?.reload();
      }
    }

    return Scaffold(
      appBar: AppBar(title: const Text('Testimonials')),
      floatingActionButton: FloatingActionButton.extended(onPressed: openForm, icon: const Icon(Icons.add), label: const Text('New testimonial')),
      body: AsyncView<Map<String, dynamic>>(
        key: viewKey,
        load: () => state.api.get('/admin/testimonials'),
        builder: (context, data, reload) {
          final testimonials = listOf(data, Testimonial.fromJson);
          return PageList(children: [
            if (testimonials.isEmpty) const EmptyState(icon: Icons.format_quote_outlined, message: 'No testimonials yet.'),
            for (final testimonial in testimonials)
              Padding(
                padding: const EdgeInsets.only(bottom: 10),
                child: Card(
                  child: ListTile(
                    leading: testimonial.photoUrl == null ? const Icon(Icons.format_quote) : NetImage(testimonial.photoUrl, width: 48, height: 48, radius: 24),
                    title: Text(testimonial.name, style: const TextStyle(fontWeight: FontWeight.w700)),
                    subtitle: Text('${'★' * testimonial.rating}  ${testimonial.role ?? ''}\n${testimonial.quote}', maxLines: 3, overflow: TextOverflow.ellipsis),
                    isThreeLine: true,
                    trailing: Row(mainAxisSize: MainAxisSize.min, children: [
                      if (!testimonial.isActive) const StatusChip('Hidden', color: Colors.grey),
                      IconButton(
                        icon: const Icon(Icons.delete_outline, color: BrandColors.danger),
                        onPressed: () => _delete(context, '/admin/testimonials/${testimonial.id}', 'this testimonial', reload),
                      ),
                    ]),
                    onTap: () => openForm(testimonial),
                  ),
                ),
              ),
            const SizedBox(height: 80),
          ]);
        },
      ),
    );
  }
}

class TestimonialFormScreen extends StatefulWidget {
  const TestimonialFormScreen({super.key, this.testimonial});

  final Testimonial? testimonial;

  @override
  State<TestimonialFormScreen> createState() => _TestimonialFormScreenState();
}

class _TestimonialFormScreenState extends State<TestimonialFormScreen> {
  late final _name = TextEditingController(text: widget.testimonial?.name ?? '');
  late final _role = TextEditingController(text: widget.testimonial?.role ?? '');
  late final _quote = TextEditingController(text: widget.testimonial?.quote ?? '');
  late final _order = TextEditingController(text: '${widget.testimonial?.sortOrder ?? 0}');
  late int _rating = widget.testimonial?.rating ?? 5;
  late bool _active = widget.testimonial?.isActive ?? true;
  XFile? _photo;
  ApiException? _error;

  @override
  void dispose() {
    for (final controller in [_name, _role, _quote, _order]) {
      controller.dispose();
    }
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final isNew = widget.testimonial == null;

    return Scaffold(
      appBar: AppBar(title: Text(isNew ? 'New testimonial' : 'Edit testimonial')),
      body: ListView(padding: const EdgeInsets.all(16), children: [
        TextField(controller: _name, decoration: InputDecoration(labelText: 'Name', errorText: _error?.fieldError('name'))),
        const SizedBox(height: 12),
        TextField(controller: _role, decoration: const InputDecoration(labelText: 'Role / location (e.g. Homeowner, Kololo)')),
        const SizedBox(height: 12),
        TextField(controller: _quote, maxLines: 4, decoration: InputDecoration(labelText: 'Quote', alignLabelWithHint: true, errorText: _error?.fieldError('quote'))),
        const SizedBox(height: 12),
        Row(children: [
          const Text('Rating', style: TextStyle(fontWeight: FontWeight.w600)),
          for (var i = 1; i <= 5; i++)
            IconButton(
              onPressed: () => setState(() => _rating = i),
              icon: Icon(i <= _rating ? Icons.star_rounded : Icons.star_outline_rounded, color: BrandColors.gold),
            ),
        ]),
        TextField(controller: _order, keyboardType: TextInputType.number, decoration: const InputDecoration(labelText: 'Sort order')),
        SwitchListTile(contentPadding: EdgeInsets.zero, title: const Text('Visible'), value: _active, onChanged: (v) => setState(() => _active = v)),
        ImageField(label: 'Photo', currentUrl: widget.testimonial?.photoUrl, onPicked: (file) => _photo = file),
        _errorText(_error),
        const SizedBox(height: 16),
        BusyButton(
          label: 'Save testimonial',
          icon: Icons.save_outlined,
          onPressed: () => _saveForm(
            context,
            path: isNew ? '/admin/testimonials' : '/admin/testimonials/${widget.testimonial!.id}',
            isNew: isNew,
            fields: {
              'name': _name.text.trim(),
              'role': _role.text.trim(),
              'quote': _quote.text.trim(),
              'rating': _rating,
              'sort_order': _order.text.trim(),
              'is_active': _active,
            },
            files: [if (_photo != null) UploadFile('photo', _photo!)],
            onError: (error) => setState(() => _error = error),
          ),
        ),
      ]),
    );
  }
}
