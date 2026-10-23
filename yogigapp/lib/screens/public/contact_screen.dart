import 'package:flutter/material.dart';

import '../../core/api_client.dart';
import '../../core/app_state.dart';
import '../../core/models.dart';
import '../../core/theme.dart';
import '../../widgets/common.dart';
import 'home_screen.dart';
import 'site_links.dart';

class ContactScreen extends StatelessWidget {
  const ContactScreen({super.key, this.standalone = false});

  final bool standalone;

  @override
  Widget build(BuildContext context) {
    final state = AppScope.of(context);

    final body = AsyncView<PageContent>(
      load: () async => PageContent.fromJson(await state.api.get('/pages/contact')),
      builder: (context, page, reload) {
        final header = page.first('hero');
        return PageList(
          padding: EdgeInsets.zero,
          children: [
            HeroBlock(
              height: 240,
              badge: header?.badge,
              title: header?.title ?? 'Get in touch',
              body: header?.body,
              imageUrl: header?.imageUrl,
            ),
            const Padding(
              padding: EdgeInsets.all(16),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.stretch,
                children: [ContactDetails(), SizedBox(height: 16), ContactForm()],
              ),
            ),
          ],
        );
      },
    );

    return standalone ? Scaffold(appBar: AppBar(title: const Text('Contact us')), body: body) : body;
  }
}

/// Phone, WhatsApp, email and address from the admin's contact settings.
class ContactDetails extends StatelessWidget {
  const ContactDetails({super.key});

  @override
  Widget build(BuildContext context) {
    final config = AppScope.of(context).config;
    final phone = config.text('contact_phone');
    final whatsapp = config.text('contact_whatsapp');
    final email = config.text('contact_email');

    Widget tile(IconData icon, String title, String value, String? link) => ListTile(
          contentPadding: EdgeInsets.zero,
          leading: CircleAvatar(backgroundColor: BrandColors.teal.withValues(alpha: 0.1), child: Icon(icon, color: BrandColors.teal)),
          title: Text(title, style: TextStyle(color: Colors.grey.shade700, fontSize: 13)),
          subtitle: Text(value, style: const TextStyle(fontWeight: FontWeight.w700, color: Colors.black87)),
          onTap: link == null ? null : () => openExternal(link),
        );

    final socials = {
      'Facebook': config.text('social_facebook'),
      'X / Twitter': config.text('social_twitter'),
      'Instagram': config.text('social_instagram'),
      'LinkedIn': config.text('social_linkedin'),
    }..removeWhere((_, url) => url.isEmpty);

    return Panel(
      title: 'Contact information',
      children: [
        if (phone.isNotEmpty) tile(Icons.phone_outlined, 'Call us', phone, 'tel:${phone.replaceAll(' ', '')}'),
        if (whatsapp.isNotEmpty) tile(Icons.chat_outlined, 'WhatsApp', whatsapp, 'https://wa.me/${whatsapp.replaceAll(RegExp(r'[^0-9]'), '')}'),
        if (email.isNotEmpty) tile(Icons.email_outlined, 'Email', email, 'mailto:$email'),
        if (config.text('contact_address').isNotEmpty) tile(Icons.place_outlined, 'Office', config.text('contact_address'), null),
        if (config.text('business_hours').isNotEmpty) tile(Icons.schedule_outlined, 'Business hours', config.text('business_hours'), null),
        if (socials.isNotEmpty)
          Wrap(spacing: 8, children: [
            for (final entry in socials.entries) ActionChip(label: Text(entry.key), onPressed: () => openExternal(entry.value)),
          ]),
      ],
    );
  }
}

class ContactForm extends StatefulWidget {
  const ContactForm({super.key});

  @override
  State<ContactForm> createState() => _ContactFormState();
}

class _ContactFormState extends State<ContactForm> {
  final _formKey = GlobalKey<FormState>();
  final _name = TextEditingController();
  final _email = TextEditingController();
  final _phone = TextEditingController();
  final _subject = TextEditingController();
  final _message = TextEditingController();
  ApiException? _error;

  @override
  void initState() {
    super.initState();
    final user = AppScope.read(context).user;
    if (user != null) {
      _name.text = user.name;
      _email.text = user.email;
      _phone.text = user.phoneNumber;
    }
  }

  @override
  void dispose() {
    for (final controller in [_name, _email, _phone, _subject, _message]) {
      controller.dispose();
    }
    super.dispose();
  }

  Future<void> _send() async {
    if (!_formKey.currentState!.validate()) {
      return;
    }
    setState(() => _error = null);
    try {
      final response = await AppScope.read(context).api.post('/contact', {
        'name': _name.text.trim(),
        'email': _email.text.trim(),
        'phone': _phone.text.trim(),
        'subject': _subject.text.trim(),
        'message': _message.text.trim(),
      });
      if (!mounted) {
        return;
      }
      _subject.clear();
      _message.clear();
      showMessage(context, response['message']?.toString() ?? 'Message sent.');
    } on ApiException catch (error) {
      setState(() => _error = error);
    }
  }

  @override
  Widget build(BuildContext context) {
    String? required(String? value) => value == null || value.trim().isEmpty ? 'Required' : null;

    return Panel(
      title: 'Send us a message',
      children: [
        Form(
          key: _formKey,
          child: Column(
            children: [
              TextFormField(controller: _name, decoration: InputDecoration(labelText: 'Your name', errorText: _error?.fieldError('name')), validator: required),
              const SizedBox(height: 12),
              TextFormField(
                controller: _email,
                keyboardType: TextInputType.emailAddress,
                decoration: InputDecoration(labelText: 'Email', errorText: _error?.fieldError('email')),
                validator: required,
              ),
              const SizedBox(height: 12),
              TextFormField(controller: _phone, keyboardType: TextInputType.phone, decoration: const InputDecoration(labelText: 'Phone (optional)')),
              const SizedBox(height: 12),
              TextFormField(controller: _subject, decoration: const InputDecoration(labelText: 'Subject (optional)')),
              const SizedBox(height: 12),
              TextFormField(
                controller: _message,
                maxLines: 5,
                decoration: InputDecoration(labelText: 'Message', alignLabelWithHint: true, errorText: _error?.fieldError('message')),
                validator: required,
              ),
              if (_error != null && _error!.errors.isEmpty)
                Padding(padding: const EdgeInsets.only(top: 12), child: Text(_error!.message, style: const TextStyle(color: BrandColors.danger))),
              const SizedBox(height: 16),
              SizedBox(width: double.infinity, child: BusyButton(label: 'Send message', icon: Icons.send, onPressed: _send)),
            ],
          ),
        ),
      ],
    );
  }
}
