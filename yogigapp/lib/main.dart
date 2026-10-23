import 'package:flutter/material.dart';
import 'package:shared_preferences/shared_preferences.dart';

import 'core/app_state.dart';
import 'core/theme.dart';
import 'screens/admin/admin_shell.dart';
import 'screens/client/client_shell.dart';
import 'screens/maid/maid_shell.dart';
import 'screens/public/public_shell.dart';
import 'widgets/common.dart';

Future<void> main() async {
  WidgetsFlutterBinding.ensureInitialized();
  final state = AppState(await SharedPreferences.getInstance());
  runApp(YogigApp(state: state));
  await state.bootstrap();
}

class YogigApp extends StatelessWidget {
  const YogigApp({super.key, required this.state});

  final AppState state;

  @override
  Widget build(BuildContext context) {
    return AppScope(
      state: state,
      child: ListenableBuilder(
        listenable: state,
        builder: (context, _) => MaterialApp(
          title: state.config.siteName,
          debugShowCheckedModeBanner: false,
          theme: buildTheme(),
          home: const RootScreen(),
        ),
      ),
    );
  }
}

/// Picks the experience for whoever is signed in: visitor, client, maid or super admin.
class RootScreen extends StatelessWidget {
  const RootScreen({super.key});

  @override
  Widget build(BuildContext context) {
    final state = AppScope.of(context);

    if (!state.ready) {
      return const Scaffold(body: Center(child: Column(mainAxisSize: MainAxisSize.min, children: [
        BrandLogo(size: 1.4),
        SizedBox(height: 24),
        CircularProgressIndicator(),
      ])));
    }

    final user = state.user;
    if (user == null) {
      return const PublicShell();
    }

    // Keyed by user so switching accounts starts each area fresh.
    return switch (user.role) {
      'admin' => AdminShell(key: ValueKey('admin-${user.id}')),
      'maid' => MaidShell(key: ValueKey('maid-${user.id}')),
      _ => ClientShell(key: ValueKey('client-${user.id}')),
    };
  }
}
