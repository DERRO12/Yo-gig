import 'package:flutter/material.dart';
import 'package:image_picker/image_picker.dart';

import '../../core/api_client.dart';
import '../../core/app_state.dart';
import '../../core/models.dart';
import '../../core/theme.dart';
import '../../widgets/common.dart';
import '../../widgets/image_field.dart';

/// Branding, contact details, payment accounts and maid rules, one tab per settings group.
class AdminSettingsScreen extends StatefulWidget {
  const AdminSettingsScreen({super.key});

  @override
  State<AdminSettingsScreen> createState() => _AdminSettingsScreenState();
}

class _AdminSettingsScreenState extends State<AdminSettingsScreen> {
  late final Future<Map<String, dynamic>> _settings = AppScope.read(context).api.get('/admin/settings');

  @override
  Widget build(BuildContext context) {
    return FutureBuilder<Map<String, dynamic>>(
      future: _settings,
      builder: (context, snapshot) {
        if (!snapshot.hasData) {
          return Scaffold(
            appBar: AppBar(title: const Text('Site settings')),
            body: snapshot.hasError ? ErrorState(message: snapshot.error.toString()) : const Center(child: CircularProgressIndicator()),
          );
        }

        final groups = listOf(snapshot.data!['groups'], SettingGroup.fromJson);

        return DefaultTabController(
          length: groups.length,
          child: Scaffold(
            appBar: AppBar(
              title: const Text('Site settings'),
              bottom: TabBar(
                isScrollable: true,
                tabAlignment: TabAlignment.start,
                labelColor: Colors.white,
                unselectedLabelColor: Colors.white70,
                indicatorColor: BrandColors.gold,
                tabs: [for (final group in groups) Tab(text: group.label)],
              ),
            ),
            body: TabBarView(children: [for (final group in groups) _SettingsForm(group: group)]),
          ),
        );
      },
    );
  }
}

class _SettingsForm extends StatefulWidget {
  const _SettingsForm({required this.group});

  final SettingGroup group;

  @override
  State<_SettingsForm> createState() => _SettingsFormState();
}

class _SettingsFormState extends State<_SettingsForm> with AutomaticKeepAliveClientMixin {
  late final Map<String, TextEditingController> _text = {
    for (final field in widget.group.fields)
      if (field.type != 'boolean' && field.type != 'image') field.key: TextEditingController(text: field.value ?? ''),
  };
  late final Map<String, bool> _flags = {
    for (final field in widget.group.fields)
      if (field.type == 'boolean') field.key: field.value == '1' || field.value == 'true',
  };
  final Map<String, XFile> _images = {};
  ApiException? _error;

  @override
  bool get wantKeepAlive => true;

  @override
  void dispose() {
    for (final controller in _text.values) {
      controller.dispose();
    }
    super.dispose();
  }

  Future<void> _save() async {
    setState(() => _error = null);
    final state = AppScope.read(context);
    try {
      final response = await state.api.multipart(
        '/admin/settings/${widget.group.key}',
        method: 'PUT',
        fields: {
          for (final entry in _text.entries) entry.key: entry.value.text.trim(),
          for (final entry in _flags.entries) entry.key: entry.value,
        },
        files: [for (final entry in _images.entries) UploadFile(entry.key, entry.value)],
      );
      await state.refreshConfig();
      if (mounted) {
        showMessage(context, response['message']?.toString() ?? 'Settings saved.');
      }
    } on ApiException catch (error) {
      setState(() => _error = error);
    }
  }

  @override
  Widget build(BuildContext context) {
    super.build(context);

    return ListView(padding: const EdgeInsets.all(16), children: [
      for (final field in widget.group.fields)
        Padding(
          padding: const EdgeInsets.only(bottom: 14),
          child: switch (field.type) {
            'boolean' => SwitchListTile(
                contentPadding: EdgeInsets.zero,
                title: Text(field.label),
                value: _flags[field.key] ?? false,
                onChanged: (value) => setState(() => _flags[field.key] = value),
              ),
            'image' => ImageField(
                label: field.label,
                currentUrl: field.value,
                onPicked: (file) => file == null ? _images.remove(field.key) : _images[field.key] = file,
              ),
            _ => TextField(
                controller: _text[field.key],
                maxLines: field.type == 'textarea' ? 4 : 1,
                keyboardType: switch (field.type) {
                  'number' => TextInputType.number,
                  'email' => TextInputType.emailAddress,
                  'url' => TextInputType.url,
                  'textarea' => TextInputType.multiline,
                  _ => TextInputType.text,
                },
                decoration: InputDecoration(labelText: field.label, alignLabelWithHint: true, errorText: _error?.fieldError(field.key)),
              ),
          },
        ),
      if (_error != null && _error!.errors.isEmpty) Text(_error!.message, style: const TextStyle(color: BrandColors.danger)),
      BusyButton(label: 'Save ${widget.group.label}', icon: Icons.save_outlined, onPressed: _save),
      const SizedBox(height: 24),
    ]);
  }
}
