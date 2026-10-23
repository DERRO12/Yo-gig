import 'package:flutter/foundation.dart';
import 'package:flutter/material.dart';
import 'package:image_picker/image_picker.dart';

import '../../core/api_client.dart';
import '../../core/app_state.dart';
import '../../core/theme.dart';
import '../../widgets/common.dart';
import 'login_screen.dart';

/// Sign-up for clients and for maids (maids also send their national ID for verification).
class RegisterScreen extends StatefulWidget {
  const RegisterScreen({super.key, this.asMaid = false});

  final bool asMaid;

  @override
  State<RegisterScreen> createState() => _RegisterScreenState();
}

class _RegisterScreenState extends State<RegisterScreen> {
  final _formKey = GlobalKey<FormState>();
  final _name = TextEditingController();
  final _email = TextEditingController();
  final _phone = TextEditingController();
  final _password = TextEditingController();
  final _confirm = TextEditingController();
  final _nationalId = TextEditingController();
  final _area = TextEditingController();
  late bool _asMaid = widget.asMaid;
  XFile? _idPhoto;
  Uint8List? _idPreview;
  ApiException? _error;

  @override
  void dispose() {
    for (final controller in [_name, _email, _phone, _password, _confirm, _nationalId, _area]) {
      controller.dispose();
    }
    super.dispose();
  }

  Future<void> _pickPhoto(ImageSource source) async {
    final file = await ImagePicker().pickImage(source: source, maxWidth: 1600, imageQuality: 80);
    if (file == null) {
      return;
    }
    final bytes = await file.readAsBytes();
    setState(() {
      _idPhoto = file;
      _idPreview = bytes;
    });
  }

  Future<void> _submit() async {
    if (!_formKey.currentState!.validate()) {
      return;
    }
    if (_asMaid && _idPhoto == null) {
      setState(() => _error = ApiException('Please add a photo of your national ID.'));
      return;
    }

    setState(() => _error = null);
    final state = AppScope.read(context);
    final fields = {
      'name': _name.text.trim(),
      'email': _email.text.trim(),
      'phone_number': _phone.text.trim(),
      'password': _password.text,
      'password_confirmation': _confirm.text,
    };

    try {
      if (_asMaid) {
        await state.registerMaid(
          {...fields, 'national_id_number': _nationalId.text.trim(), 'service_area': _area.text.trim()},
          UploadFile('national_id_photo', _idPhoto!),
        );
      } else {
        await state.registerClient(fields);
      }
      if (mounted) {
        Navigator.of(context).popUntil((route) => route.isFirst);
      }
    } on ApiException catch (error) {
      setState(() => _error = error);
    }
  }

  @override
  Widget build(BuildContext context) {
    final config = AppScope.of(context).config;
    String? required(String? value) => value == null || value.trim().isEmpty ? 'Required' : null;

    return Scaffold(
      appBar: AppBar(title: Text('Join ${config.siteName}')),
      body: Center(
        child: SingleChildScrollView(
          padding: const EdgeInsets.all(20),
          child: ConstrainedBox(
            constraints: const BoxConstraints(maxWidth: 480),
            child: Form(
              key: _formKey,
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.stretch,
                children: [
                  SegmentedButton<bool>(
                    segments: const [
                      ButtonSegment(value: false, icon: Icon(Icons.home_outlined), label: Text('I need help')),
                      ButtonSegment(value: true, icon: Icon(Icons.cleaning_services_outlined), label: Text('I am a maid')),
                    ],
                    selected: {_asMaid},
                    onSelectionChanged: (selection) => setState(() => _asMaid = selection.first),
                  ),
                  const SizedBox(height: 12),
                  Text(
                    _asMaid
                        ? 'Earn from home gigs that are already paid for. Our team verifies your ID before you can take gigs.'
                        : 'Book trusted maids. Your money stays in escrow until you approve the work.',
                    textAlign: TextAlign.center,
                    style: TextStyle(color: Colors.grey.shade700),
                  ),
                  const SizedBox(height: 20),
                  TextFormField(controller: _name, decoration: InputDecoration(labelText: 'Full name', errorText: _error?.fieldError('name')), validator: required),
                  const SizedBox(height: 12),
                  TextFormField(
                    controller: _email,
                    keyboardType: TextInputType.emailAddress,
                    decoration: InputDecoration(labelText: 'Email', errorText: _error?.fieldError('email')),
                    validator: required,
                  ),
                  const SizedBox(height: 12),
                  TextFormField(
                    controller: _phone,
                    keyboardType: TextInputType.phone,
                    decoration: InputDecoration(labelText: 'Phone number', hintText: '+256 7XX XXX XXX', errorText: _error?.fieldError('phone_number')),
                    validator: required,
                  ),
                  const SizedBox(height: 12),
                  TextFormField(
                    controller: _password,
                    obscureText: true,
                    decoration: InputDecoration(labelText: 'Password', errorText: _error?.fieldError('password')),
                    validator: (value) => (value ?? '').length < 8 ? 'Use at least 8 characters' : null,
                  ),
                  const SizedBox(height: 12),
                  TextFormField(
                    controller: _confirm,
                    obscureText: true,
                    decoration: const InputDecoration(labelText: 'Confirm password'),
                    validator: (value) => value != _password.text ? 'Passwords do not match' : null,
                  ),
                  if (_asMaid) ...[
                    const SizedBox(height: 20),
                    const Text('Verification', style: TextStyle(fontWeight: FontWeight.w800, fontSize: 16)),
                    const SizedBox(height: 10),
                    TextFormField(
                      controller: _nationalId,
                      decoration: InputDecoration(labelText: 'National ID number', errorText: _error?.fieldError('national_id_number')),
                      validator: required,
                    ),
                    const SizedBox(height: 12),
                    TextFormField(controller: _area, decoration: const InputDecoration(labelText: 'Area you work in (e.g. Ntinda)')),
                    const SizedBox(height: 12),
                    if (_idPreview != null)
                      ClipRRect(borderRadius: BorderRadius.circular(12), child: Image.memory(_idPreview!, height: 180, fit: BoxFit.cover)),
                    const SizedBox(height: 8),
                    Row(children: [
                      Expanded(
                        child: OutlinedButton.icon(
                          onPressed: () => _pickPhoto(ImageSource.gallery),
                          icon: const Icon(Icons.photo_library_outlined),
                          label: Text(_idPhoto == null ? 'Upload ID photo' : 'Change photo'),
                        ),
                      ),
                      if (!kIsWeb) ...[
                        const SizedBox(width: 10),
                        IconButton.outlined(onPressed: () => _pickPhoto(ImageSource.camera), icon: const Icon(Icons.photo_camera_outlined)),
                      ],
                    ]),
                    if (_error?.fieldError('national_id_photo') != null)
                      Text(_error!.fieldError('national_id_photo')!, style: const TextStyle(color: BrandColors.danger)),
                  ],
                  if (_error != null && _error!.errors.isEmpty) ...[
                    const SizedBox(height: 14),
                    Text(_error!.message, textAlign: TextAlign.center, style: const TextStyle(color: BrandColors.danger)),
                  ],
                  const SizedBox(height: 20),
                  BusyButton(label: 'Create account', icon: Icons.person_add_alt_1, onPressed: _submit),
                  const SizedBox(height: 12),
                  TextButton(
                    onPressed: () => Navigator.of(context).pushReplacement(MaterialPageRoute(builder: (_) => const LoginScreen())),
                    child: const Text('Already have an account? Sign in'),
                  ),
                ],
              ),
            ),
          ),
        ),
      ),
    );
  }
}
