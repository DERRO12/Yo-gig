import 'package:flutter/material.dart';
import 'package:url_launcher/url_launcher.dart';

import '../../core/app_state.dart';
import '../auth/login_screen.dart';
import '../auth/register_screen.dart';
import '../client/booking_form_screen.dart';
import 'about_screen.dart';
import 'contact_screen.dart';
import 'services_screen.dart';

/// Opens a button link stored in website content ("/services", "/register?role=maid", "#book", "https://…")
/// inside the app where there is a matching screen, and in the browser otherwise.
Future<void> openSiteLink(BuildContext context, String? url) async {
  if (url == null || url.isEmpty || url == '#') {
    return;
  }

  final state = AppScope.read(context);
  final link = url.toLowerCase();
  Widget? screen;

  if (link.contains('register')) {
    screen = state.isSignedIn ? null : RegisterScreen(asMaid: link.contains('maid'));
  } else if (link.contains('login')) {
    screen = state.isSignedIn ? null : const LoginScreen();
  } else if (link.contains('book') || link.contains('client')) {
    screen = state.user?.isClient == true ? const BookingFormScreen() : (state.isSignedIn ? null : const LoginScreen());
  } else if (link.contains('maid')) {
    screen = state.isSignedIn ? null : const RegisterScreen(asMaid: true);
  } else if (link.contains('services')) {
    screen = const ServicesScreen(standalone: true);
  } else if (link.contains('about')) {
    screen = const AboutScreen(standalone: true);
  } else if (link.contains('contact')) {
    screen = const ContactScreen(standalone: true);
  }

  if (screen != null) {
    await Navigator.of(context).push(MaterialPageRoute(builder: (_) => screen!));
    return;
  }

  if (link.startsWith('http') || link.startsWith('mailto:') || link.startsWith('tel:')) {
    await launchUrl(Uri.parse(url), mode: LaunchMode.externalApplication);
  }
}

Future<void> openExternal(String url) async {
  await launchUrl(Uri.parse(url), mode: LaunchMode.externalApplication);
}
