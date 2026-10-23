import 'package:flutter/material.dart';

import '../../widgets/common.dart';
import '../auth/login_screen.dart';
import 'about_screen.dart';
import 'contact_screen.dart';
import 'home_screen.dart';
import 'services_screen.dart';

/// The website for visitors who have not signed in.
class PublicShell extends StatefulWidget {
  const PublicShell({super.key});

  @override
  State<PublicShell> createState() => _PublicShellState();
}

class _PublicShellState extends State<PublicShell> {
  int _index = 0;

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        backgroundColor: Colors.white,
        foregroundColor: Colors.black87,
        title: const BrandLogo(),
        actions: [
          Padding(
            padding: const EdgeInsets.only(right: 12),
            child: FilledButton(
              style: FilledButton.styleFrom(minimumSize: const Size(0, 40)),
              onPressed: () => Navigator.of(context).push(MaterialPageRoute(builder: (_) => const LoginScreen())),
              child: const Text('Sign in'),
            ),
          ),
        ],
      ),
      body: IndexedStack(
        index: _index,
        children: const [HomeScreen(), ServicesScreen(), AboutScreen(), ContactScreen()],
      ),
      bottomNavigationBar: NavigationBar(
        selectedIndex: _index,
        onDestinationSelected: (index) => setState(() => _index = index),
        destinations: const [
          NavigationDestination(icon: Icon(Icons.home_outlined), selectedIcon: Icon(Icons.home), label: 'Home'),
          NavigationDestination(icon: Icon(Icons.cleaning_services_outlined), selectedIcon: Icon(Icons.cleaning_services), label: 'Services'),
          NavigationDestination(icon: Icon(Icons.info_outline), selectedIcon: Icon(Icons.info), label: 'About'),
          NavigationDestination(icon: Icon(Icons.support_agent_outlined), selectedIcon: Icon(Icons.support_agent), label: 'Contact'),
        ],
      ),
    );
  }
}
