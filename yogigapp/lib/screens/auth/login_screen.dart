import 'package:flutter/material.dart';

import '../../core/api_client.dart';
import '../../core/app_state.dart';
import '../../core/theme.dart';
import '../../widgets/common.dart';
import 'register_screen.dart';

class LoginScreen extends StatefulWidget {
  const LoginScreen({super.key});

  @override
  State<LoginScreen> createState() => _LoginScreenState();
}

class _LoginScreenState extends State<LoginScreen> {
  final _formKey = GlobalKey<FormState>();
  final _login = TextEditingController();
  final _password = TextEditingController();
  bool _obscure = true;
  String? _error;

  @override
  void dispose() {
    _login.dispose();
    _password.dispose();
    super.dispose();
  }

  Future<void> _submit() async {
    if (!_formKey.currentState!.validate()) {
      return;
    }
    setState(() => _error = null);
    try {
      await AppScope.read(context).login(_login.text.trim(), _password.text);
      if (mounted) {
        Navigator.of(context).popUntil((route) => route.isFirst);
      }
    } on ApiException catch (error) {
      setState(() => _error = error.message);
    }
  }

  Future<void> _editServer() async {
    final state = AppScope.read(context);
    final controller = TextEditingController(text: state.serverUrl);
    final url = await showDialog<String>(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('Server address'),
        content: Column(mainAxisSize: MainAxisSize.min, children: [
          const Text('The address of the website API, ending in /api/v1.'),
          const SizedBox(height: 12),
          TextField(controller: controller, keyboardType: TextInputType.url, decoration: const InputDecoration(labelText: 'API address')),
        ]),
        actions: [
          TextButton(onPressed: () => controller.text = defaultApiBaseUrl(), child: const Text('Reset')),
          FilledButton(onPressed: () => Navigator.pop(context, controller.text), child: const Text('Save')),
        ],
      ),
    );
    if (url != null && url.trim().isNotEmpty) {
      await state.setServerUrl(url);
      if (mounted) {
        showMessage(context, 'Server address saved.');
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    final config = AppScope.of(context).config;

    return Scaffold(
      appBar: AppBar(
        title: const Text('Sign in'),
        actions: [IconButton(tooltip: 'Server address', onPressed: _editServer, icon: const Icon(Icons.dns_outlined))],
      ),
      body: Center(
        child: SingleChildScrollView(
          padding: const EdgeInsets.all(20),
          child: ConstrainedBox(
            constraints: const BoxConstraints(maxWidth: 440),
            child: Form(
              key: _formKey,
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.stretch,
                children: [
                  const Center(child: BrandLogo(size: 1.3)),
                  const SizedBox(height: 24),
                  Text('Welcome back', textAlign: TextAlign.center, style: Theme.of(context).textTheme.headlineSmall?.copyWith(fontWeight: FontWeight.w800)),
                  const SizedBox(height: 6),
                  Text('Sign in to ${config.siteName} with your email or phone number.', textAlign: TextAlign.center),
                  const SizedBox(height: 24),
                  TextFormField(
                    controller: _login,
                    keyboardType: TextInputType.emailAddress,
                    autofillHints: const [AutofillHints.username],
                    decoration: const InputDecoration(labelText: 'Email or phone number', prefixIcon: Icon(Icons.person_outline)),
                    validator: (value) => value == null || value.trim().isEmpty ? 'Enter your email or phone number' : null,
                  ),
                  const SizedBox(height: 14),
                  TextFormField(
                    controller: _password,
                    obscureText: _obscure,
                    autofillHints: const [AutofillHints.password],
                    onFieldSubmitted: (_) => _submit(),
                    decoration: InputDecoration(
                      labelText: 'Password',
                      prefixIcon: const Icon(Icons.lock_outline),
                      suffixIcon: IconButton(
                        icon: Icon(_obscure ? Icons.visibility_outlined : Icons.visibility_off_outlined),
                        onPressed: () => setState(() => _obscure = !_obscure),
                      ),
                    ),
                    validator: (value) => value == null || value.isEmpty ? 'Enter your password' : null,
                  ),
                  if (_error != null) ...[
                    const SizedBox(height: 14),
                    Text(_error!, style: const TextStyle(color: BrandColors.danger), textAlign: TextAlign.center),
                  ],
                  const SizedBox(height: 20),
                  BusyButton(label: 'Sign in', icon: Icons.login, onPressed: _submit),
                  const SizedBox(height: 20),
                  Text('New to ${config.siteName}?', textAlign: TextAlign.center),
                  const SizedBox(height: 8),
                  Row(children: [
                    Expanded(
                      child: OutlinedButton(
                        onPressed: () => Navigator.of(context).pushReplacement(MaterialPageRoute(builder: (_) => const RegisterScreen())),
                        child: const Text('I need a maid'),
                      ),
                    ),
                    const SizedBox(width: 10),
                    Expanded(
                      child: OutlinedButton(
                        onPressed: () => Navigator.of(context).pushReplacement(MaterialPageRoute(builder: (_) => const RegisterScreen(asMaid: true))),
                        child: const Text('I want to work'),
                      ),
                    ),
                  ]),
                ],
              ),
            ),
          ),
        ),
      ),
    );
  }
}
