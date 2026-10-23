import 'package:flutter/material.dart';
import 'package:image_picker/image_picker.dart';

import '../../core/api_client.dart';
import '../../core/app_state.dart';
import '../../core/format.dart';
import '../../core/models.dart';
import '../../core/theme.dart';
import '../../widgets/common.dart';
import 'booking_detail_screen.dart';

class BookingFormScreen extends StatefulWidget {
  const BookingFormScreen({super.key, this.initialService});

  final Service? initialService;

  @override
  State<BookingFormScreen> createState() => _BookingFormScreenState();
}

class _BookingFormScreenState extends State<BookingFormScreen> {
  late final Future<List<Service>> _services = _loadServices();

  Future<List<Service>> _loadServices() async {
    final response = await AppScope.read(context).api.get('/services');
    return listOf(response, Service.fromJson);
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Book a maid')),
      body: FutureBuilder<List<Service>>(
        future: _services,
        builder: (context, snapshot) {
          if (snapshot.hasError) {
            return ErrorState(message: snapshot.error.toString());
          }
          if (!snapshot.hasData) {
            return const Center(child: CircularProgressIndicator());
          }
          if (snapshot.data!.isEmpty) {
            return const EmptyState(icon: Icons.cleaning_services_outlined, message: 'No services are available for booking right now.');
          }
          return _BookingForm(services: snapshot.data!, initialService: widget.initialService);
        },
      ),
    );
  }
}

class _BookingForm extends StatefulWidget {
  const _BookingForm({required this.services, this.initialService});

  final List<Service> services;
  final Service? initialService;

  @override
  State<_BookingForm> createState() => _BookingFormState();
}

class _BookingFormState extends State<_BookingForm> {
  final _formKey = GlobalKey<FormState>();
  final _title = TextEditingController();
  final _location = TextEditingController();
  final _description = TextEditingController();
  final _budget = TextEditingController();
  late Service _service = widget.services.firstWhere(
    (service) => service.id == widget.initialService?.id,
    orElse: () => widget.services.first,
  );
  int _quantity = 1;
  DateTime _date = DateTime.now().add(const Duration(days: 1));
  TimeOfDay _time = const TimeOfDay(hour: 9, minute: 0);
  final List<XFile> _images = [];
  ApiException? _error;

  @override
  void dispose() {
    for (final controller in [_title, _location, _description, _budget]) {
      controller.dispose();
    }
    super.dispose();
  }

  double get _total => _service.hasFixedPrice ? _service.basePrice * _quantity : double.tryParse(_budget.text.replaceAll(',', '')) ?? 0;

  Future<void> _pickDate() async {
    final now = DateTime.now();
    final picked = await showDatePicker(
      context: context,
      initialDate: _date,
      firstDate: DateTime(now.year, now.month, now.day),
      lastDate: now.add(const Duration(days: 365)),
    );
    if (picked != null) {
      setState(() => _date = picked);
    }
  }

  Future<void> _pickTime() async {
    final picked = await showTimePicker(context: context, initialTime: _time);
    if (picked != null) {
      setState(() => _time = picked);
    }
  }

  Future<void> _pickImages() async {
    final files = await ImagePicker().pickMultiImage(maxWidth: 1600, imageQuality: 80, limit: 5);
    setState(() {
      _images
        ..addAll(files)
        ..removeRange(5.clamp(0, _images.length), _images.length);
    });
  }

  Future<void> _submit() async {
    if (!_formKey.currentState!.validate()) {
      return;
    }
    setState(() => _error = null);

    final state = AppScope.read(context);
    final time = '${_time.hour.toString().padLeft(2, '0')}:${_time.minute.toString().padLeft(2, '0')}';

    try {
      final response = await state.api.multipart(
        '/client/bookings',
        fields: {
          'service_id': _service.id,
          'quantity': _quantity,
          if (!_service.hasFixedPrice) 'budget': _budget.text.replaceAll(',', ''),
          'title': _title.text.trim(),
          'location': _location.text.trim(),
          'description': _description.text.trim(),
          'scheduled_date': apiDate(_date),
          'scheduled_time': time,
        },
        files: [for (final image in _images) UploadFile('images[]', image)],
      );
      final booking = Booking.fromJson(response['data'] as Map<String, dynamic>);
      if (!mounted) {
        return;
      }
      showMessage(context, response['message']?.toString() ?? 'Booking created.');
      await Navigator.of(context).pushReplacement(MaterialPageRoute(builder: (_) => BookingDetailScreen(bookingId: booking.id)));
    } on ApiException catch (error) {
      setState(() => _error = error);
    }
  }

  @override
  Widget build(BuildContext context) {
    final config = AppScope.of(context).config;
    final currency = config.currency;
    final minimum = config.settings['min_booking_amount'];

    return Form(
      key: _formKey,
      child: ListView(
        padding: const EdgeInsets.all(16),
        children: [
          DropdownButtonFormField<int>(
            initialValue: _service.id,
            isExpanded: true,
            decoration: InputDecoration(labelText: 'Service', errorText: _error?.fieldError('service_id')),
            items: [
              for (final service in widget.services)
                DropdownMenuItem(value: service.id, child: Text(service.name, overflow: TextOverflow.ellipsis)),
            ],
            onChanged: (id) => setState(() => _service = widget.services.firstWhere((service) => service.id == id)),
          ),
          const SizedBox(height: 8),
          Text(
            _service.hasFixedPrice ? '${money(_service.basePrice, currency)} ${_service.priceUnit}' : 'This service is priced on your budget.',
            style: const TextStyle(color: BrandColors.teal, fontWeight: FontWeight.w600),
          ),
          const SizedBox(height: 16),
          Row(children: [
            Expanded(child: Text('Quantity (${_service.priceUnit})', style: const TextStyle(fontWeight: FontWeight.w600))),
            IconButton.outlined(onPressed: _quantity > 1 ? () => setState(() => _quantity--) : null, icon: const Icon(Icons.remove)),
            Padding(padding: const EdgeInsets.symmetric(horizontal: 14), child: Text('$_quantity', style: const TextStyle(fontSize: 18, fontWeight: FontWeight.w800))),
            IconButton.outlined(onPressed: _quantity < 50 ? () => setState(() => _quantity++) : null, icon: const Icon(Icons.add)),
          ]),
          if (!_service.hasFixedPrice) ...[
            const SizedBox(height: 16),
            TextFormField(
              controller: _budget,
              keyboardType: TextInputType.number,
              onChanged: (_) => setState(() {}),
              decoration: InputDecoration(labelText: 'Your budget ($currency)', errorText: _error?.fieldError('budget')),
              validator: (value) => (double.tryParse((value ?? '').replaceAll(',', '')) ?? 0) <= 0 ? 'Enter your budget' : null,
            ),
          ],
          const SizedBox(height: 16),
          TextFormField(
            controller: _location,
            decoration: InputDecoration(labelText: 'Location / address', prefixIcon: const Icon(Icons.place_outlined), errorText: _error?.fieldError('location')),
            validator: (value) => value == null || value.trim().isEmpty ? 'Where should the maid go?' : null,
          ),
          const SizedBox(height: 16),
          Row(children: [
            Expanded(
              child: OutlinedButton.icon(onPressed: _pickDate, icon: const Icon(Icons.event), label: Text(shortDate(_date))),
            ),
            const SizedBox(width: 10),
            Expanded(
              child: OutlinedButton.icon(onPressed: _pickTime, icon: const Icon(Icons.schedule), label: Text(_time.format(context))),
            ),
          ]),
          if (_error?.fieldError('scheduled_date') != null)
            Text(_error!.fieldError('scheduled_date')!, style: const TextStyle(color: BrandColors.danger)),
          const SizedBox(height: 16),
          TextFormField(controller: _title, decoration: const InputDecoration(labelText: 'Title (optional)', hintText: 'e.g. Deep clean before guests arrive')),
          const SizedBox(height: 16),
          TextFormField(
            controller: _description,
            maxLines: 4,
            decoration: const InputDecoration(labelText: 'Details for the maid (optional)', alignLabelWithHint: true),
          ),
          const SizedBox(height: 16),
          Row(children: [
            const Expanded(child: Text('Photos of the space (optional, up to 5)', style: TextStyle(fontWeight: FontWeight.w600))),
            TextButton.icon(onPressed: _images.length >= 5 ? null : _pickImages, icon: const Icon(Icons.add_photo_alternate_outlined), label: const Text('Add')),
          ]),
          if (_images.isNotEmpty)
            Wrap(spacing: 8, runSpacing: 8, children: [
              for (final image in _images)
                InputChip(label: Text(image.name, overflow: TextOverflow.ellipsis), onDeleted: () => setState(() => _images.remove(image))),
            ]),
          const SizedBox(height: 20),
          Panel(color: BrandColors.teal.withValues(alpha: 0.06), children: [
            Row(children: [
              const Expanded(child: Text('Total to pay', style: TextStyle(fontWeight: FontWeight.w700))),
              Text(money(_total, currency), style: const TextStyle(fontSize: 20, fontWeight: FontWeight.w900, color: BrandColors.teal)),
            ]),
            if (minimum is num && minimum > 0)
              Text('Minimum booking amount is ${money(minimum, currency)}.', style: TextStyle(color: Colors.grey.shade700, fontSize: 12)),
            const SizedBox(height: 4),
            Text('Your payment is held in escrow and only released to the maid after you approve the work.', style: TextStyle(color: Colors.grey.shade700, fontSize: 12)),
          ]),
          if (_error != null && _error!.errors.isEmpty) ...[
            const SizedBox(height: 12),
            Text(_error!.message, style: const TextStyle(color: BrandColors.danger)),
          ],
          const SizedBox(height: 16),
          BusyButton(label: 'Create booking', icon: Icons.check_circle_outline, onPressed: _submit),
          const SizedBox(height: 24),
        ],
      ),
    );
  }
}
