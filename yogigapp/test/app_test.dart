import 'dart:convert';

import 'package:flutter_test/flutter_test.dart';
import 'package:http/http.dart' as http;
import 'package:http/testing.dart';
import 'package:shared_preferences/shared_preferences.dart';
import 'package:yogig_house_manager/core/api_client.dart';
import 'package:yogig_house_manager/core/app_state.dart';
import 'package:yogig_house_manager/core/models.dart';
import 'package:yogig_house_manager/main.dart';

/// Fake Laravel API answering the endpoints the visitor screens call.
http.Client fakeApi({Map<String, dynamic>? loginResponse}) {
  return MockClient((request) async {
    final path = request.url.path;
    Map<String, dynamic> body;

    if (path.endsWith('/config')) {
      body = {
        'settings': {'site_name': 'Yo-gig House Manager', 'site_tagline': 'Trusted home help', 'currency': 'UGX'},
        'payment_methods': {'mtn_momo': 'MTN Mobile Money'},
        'booking_statuses': {'open': 'Open'},
        'payment_statuses': {'unpaid': 'Awaiting payment'},
        'pages': {'home': {'hero': 'Hero slides'}},
      };
    } else if (path.contains('/pages/')) {
      body = {
        'page': 'home',
        'sections': {
          'hero': [
            {'id': 1, 'page': 'home', 'section': 'hero', 'title': 'Spotless homes, safe payments', 'button_text': 'Book now', 'button_url': '/client/dashboard'},
          ],
        },
        'featured_services': [],
        'fresh_gigs': [],
        'total_open_gigs': 3,
        'testimonials': [],
        'services': [],
      };
    } else if (path.endsWith('/auth/login') && loginResponse != null) {
      body = loginResponse;
    } else if (path.endsWith('/auth/login')) {
      return http.Response(jsonEncode({'message': 'The provided credentials do not match our records.', 'errors': {'login': ['The provided credentials do not match our records.']}}), 422);
    } else {
      body = {'data': []};
    }

    return http.Response(jsonEncode(body), 200, headers: {'content-type': 'application/json'});
  });
}

void main() {
  setUp(() => SharedPreferences.setMockInitialValues({}));

  testWidgets('visitors see the website home page from the API', (tester) async {
    final state = AppState(await SharedPreferences.getInstance(), httpClient: fakeApi());
    await state.bootstrap();

    await tester.pumpWidget(YogigApp(state: state));
    await tester.pumpAndSettle();

    expect(find.text('Spotless homes, safe payments'), findsOneWidget);
    expect(find.text('Sign in'), findsOneWidget);
    expect(find.textContaining('3 funded gigs'), findsOneWidget);
  });

  test('a failed login surfaces the validation message', () async {
    final state = AppState(await SharedPreferences.getInstance(), httpClient: fakeApi());

    expect(
      () => state.login('someone@example.com', 'wrong'),
      throwsA(isA<ApiException>().having((e) => e.fieldError('login'), 'login error', contains('do not match'))),
    );
  });

  test('signing in stores the token and user', () async {
    final state = AppState(
      await SharedPreferences.getInstance(),
      httpClient: fakeApi(loginResponse: {
        'token': 'abc123',
        'user': {'id': 7, 'name': 'Grace Akello', 'email': 'grace@example.com', 'role': 'maid', 'maid_profile': {'verification_status': 'verified', 'is_verified': true}},
      }),
    );

    await state.login('grace@example.com', 'password');

    expect(state.user?.isMaid, isTrue);
    expect(state.user?.initials, 'GA');
    expect(state.user?.maidProfile?.isVerified, isTrue);
    expect(state.api.token, 'abc123');
    expect((await SharedPreferences.getInstance()).getString('auth_token'), 'abc123');
  });

  test('bookings parse amounts, flags and nested records', () {
    final booking = Booking.fromJson({
      'id': 4,
      'reference': 'YG-ABC123',
      'title': 'Deep clean',
      'status': 'completed',
      'payment_status': 'paid',
      'budget': 60000,
      'maid_earnings': '54000.00',
      'is_awaiting_client_approval': true,
      'service': {'id': 1, 'name': 'Residential Housekeeping', 'base_price': 30000, 'has_fixed_price': true},
      'payments': [
        {'id': 9, 'amount': 60000, 'method': 'mtn_momo', 'status': 'successful', 'transaction_reference': 'MP1'},
      ],
    });

    expect(booking.budget, 60000);
    expect(booking.maidEarnings, 54000);
    expect(booking.isCompleted, isTrue);
    expect(booking.isAwaitingClientApproval, isTrue);
    expect(booking.service?.hasFixedPrice, isTrue);
    expect(booking.payments.single.status, 'successful');
  });
}
