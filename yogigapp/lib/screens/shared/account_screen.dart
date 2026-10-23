import 'package:flutter/material.dart';

import '../../core/api_client.dart';
import '../../core/app_state.dart';
import '../../core/models.dart';
import '../../core/theme.dart';
import '../../widgets/common.dart';
import '../public/about_screen.dart';
import '../public/contact_screen.dart';

/// Profile, password and sign-out for every role.
class AccountScreen extends StatelessWidget {
  const AccountScreen({super.key, this.extraTiles = const []});

  /// Role-specific shortcuts shown above the shared ones.
  final List<Widget> extraTiles;

  @override
  Widget build(BuildContext context) {
    final state = AppScope.of(context);
    final user = state.user!;

    return PageList(
      children: [
        Panel(children: [
          Row(children: [
            Avatar(user, radius: 30),
            const SizedBox(width: 14),
            Expanded(
              child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                Text(user.name, style: const TextStyle(fontWeight: FontWeight.w800, fontSize: 18)),
                Text(user.email, style: TextStyle(color: Colors.grey.shade700)),
                Text(user.phoneNumber, style: TextStyle(color: Colors.grey.shade700)),
                const SizedBox(height: 6),
                Wrap(spacing: 6, children: [
                  StatusChip(user.roleLabel, color: BrandColors.teal),
                  if (user.maidProfile != null)
                    StatusChip(
                      user.maidProfile!.isVerified ? 'ID verified' : 'ID ${user.maidProfile!.verificationStatus}',
                      color: user.maidProfile!.isVerified ? BrandColors.success : BrandColors.warning,
                    ),
                ]),
              ]),
            ),
          ]),
        ]),
        const SizedBox(height: 12),
        Card(
          child: Column(children: [
            ...extraTiles,
            ListTile(
              leading: const Icon(Icons.edit_outlined),
              title: const Text('Edit profile'),
              trailing: const Icon(Icons.chevron_right),
              onTap: () => Navigator.of(context).push(MaterialPageRoute(builder: (_) => ProfileFormScreen(user: user))),
            ),
            ListTile(
              leading: const Icon(Icons.lock_reset_outlined),
              title: const Text('Change password'),
              trailing: const Icon(Icons.chevron_right),
              onTap: () => Navigator.of(context).push(MaterialPageRoute(builder: (_) => const PasswordScreen())),
            ),
            ListTile(
              leading: const Icon(Icons.info_outline),
              title: Text('About ${state.config.siteName}'),
              trailing: const Icon(Icons.chevron_right),
              onTap: () => Navigator.of(context).push(MaterialPageRoute(builder: (_) => const AboutScreen(standalone: true))),
            ),
            ListTile(
              leading: const Icon(Icons.support_agent_outlined),
              title: const Text('Contact support'),
              trailing: const Icon(Icons.chevron_right),
              onTap: () => Navigator.of(context).push(MaterialPageRoute(builder: (_) => const ContactScreen(standalone: true))),
            ),
          ]),
        ),
        const SizedBox(height: 12),
        BusyButton(
          label: 'Sign out',
          icon: Icons.logout,
          color: BrandColors.danger,
          outlined: true,
          onPressed: () async {
            if (await confirm(context, title: 'Sign out?', message: 'You will need to sign in again to manage your account.', action: 'Sign out')) {
              await state.logout();
            }
          },
        ),
      ],
    );
  }
}

class ProfileFormScreen extends StatefulWidget {
  const ProfileFormScreen({super.key, required this.user});

  final AppUser user;

  @override
  State<ProfileFormScreen> createState() => _ProfileFormScreenState();
}

class _ProfileFormScreenState extends State<ProfileFormScreen> {
  late final _name = TextEditingController(text: widget.user.name);
  late final _email = TextEditingController(text: widget.user.email);
  late final _phone = TextEditingController(text: widget.user.phoneNumber);
  late final _area = TextEditingController(text: widget.user.maidProfile?.serviceArea ?? '');
  late final _bio = TextEditingController(text: widget.user.maidProfile?.bio ?? '');
  late bool _available = widget.user.maidProfile?.available ?? true;
  ApiException? _error;

  @override
  void dispose() {
    for (final controller in [_name, _email, _phone, _area, _bio]) {
      controller.dispose();
    }
    super.dispose();
  }

  Future<void> _save() async {
    final state = AppScope.read(context);
    setState(() => _error = null);
    try {
      final response = await state.api.put('/me', {
        'name': _name.text.trim(),
        'email': _email.text.trim(),
        'phone_number': _phone.text.trim(),
        if (widget.user.isMaid) ...{'service_area': _area.text.trim(), 'bio': _bio.text.trim(), 'available': _available},
      });
      state.setUser(AppUser.fromJson(response['data'] as Map<String, dynamic>));
      if (mounted) {
        showMessage(context, 'Profile updated.');
        Navigator.pop(context);
      }
    } on ApiException catch (error) {
      setState(() => _error = error);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Edit profile')),
      body: ListView(
        padding: const EdgeInsets.all(16),
        children: [
          TextField(controller: _name, decoration: InputDecoration(labelText: 'Full name', errorText: _error?.fieldError('name'))),
          const SizedBox(height: 12),
          TextField(controller: _email, keyboardType: TextInputType.emailAddress, decoration: InputDecoration(labelText: 'Email', errorText: _error?.fieldError('email'))),
          const SizedBox(height: 12),
          TextField(controller: _phone, keyboardType: TextInputType.phone, decoration: InputDecoration(labelText: 'Phone number', errorText: _error?.fieldError('phone_number'))),
          if (widget.user.isMaid) ...[
            const SizedBox(height: 12),
            TextField(controller: _area, decoration: const InputDecoration(labelText: 'Service area')),
            const SizedBox(height: 12),
            TextField(controller: _bio, maxLines: 4, decoration: const InputDecoration(labelText: 'About you', alignLabelWithHint: true)),
            SwitchListTile(
              contentPadding: EdgeInsets.zero,
              title: const Text('Available for new gigs'),
              value: _available,
              onChanged: (value) => setState(() => _available = value),
            ),
          ],
          if (_error != null && _error!.errors.isEmpty) Text(_error!.message, style: const TextStyle(color: BrandColors.danger)),
          const SizedBox(height: 16),
          BusyButton(label: 'Save changes', icon: Icons.save_outlined, onPressed: _save),
        ],
      ),
    );
  }
}

class PasswordScreen extends StatefulWidget {
  const PasswordScreen({super.key});

  @override
  State<PasswordScreen> createState() => _PasswordScreenState();
}

class _PasswordScreenState extends State<PasswordScreen> {
  final _current = TextEditingController();
  final _password = TextEditingController();
  final _confirm = TextEditingController();
  ApiException? _error;

  @override
  void dispose() {
    _current.dispose();
    _password.dispose();
    _confirm.dispose();
    super.dispose();
  }

  Future<void> _save() async {
    setState(() => _error = null);
    try {
      await AppScope.read(context).api.put('/me/password', {
        'current_password': _current.text,
        'password': _password.text,
        'password_confirmation': _confirm.text,
      });
      if (mounted) {
        showMessage(context, 'Password changed.');
        Navigator.pop(context);
      }
    } on ApiException catch (error) {
      setState(() => _error = error);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Change password')),
      body: ListView(
        padding: const EdgeInsets.all(16),
        children: [
          TextField(controller: _current, obscureText: true, decoration: InputDecoration(labelText: 'Current password', errorText: _error?.fieldError('current_password'))),
          const SizedBox(height: 12),
          TextField(controller: _password, obscureText: true, decoration: InputDecoration(labelText: 'New password', errorText: _error?.fieldError('password'))),
          const SizedBox(height: 12),
          TextField(controller: _confirm, obscureText: true, decoration: const InputDecoration(labelText: 'Confirm new password')),
          if (_error != null && _error!.errors.isEmpty) Text(_error!.message, style: const TextStyle(color: BrandColors.danger)),
          const SizedBox(height: 16),
          BusyButton(label: 'Update password', icon: Icons.lock_outline, onPressed: _save),
        ],
      ),
    );
  }
}
