import 'package:flutter/foundation.dart';
import 'package:flutter/widgets.dart';
import 'package:http/http.dart' as http;
import 'package:shared_preferences/shared_preferences.dart';

import 'api_client.dart';
import 'models.dart';

/// The server the app talks to. Override at build time with
/// `--dart-define=API_BASE_URL=https://your-domain/api/v1`, or from the sign-in screen.
String defaultApiBaseUrl() {
  const fromEnvironment = String.fromEnvironment('API_BASE_URL');
  if (fromEnvironment.isNotEmpty) {
    return fromEnvironment;
  }
  if (kIsWeb) {
    return 'http://localhost:8000/api/v1';
  }
  // The Android emulator reaches the computer running `php artisan serve` on 10.0.2.2.
  return defaultTargetPlatform == TargetPlatform.android ? 'http://10.0.2.2:8000/api/v1' : 'http://localhost:8000/api/v1';
}

/// Signed-in user, their token and the site settings, shared across the app.
class AppState extends ChangeNotifier {
  AppState(this._prefs, {http.Client? httpClient})
      : api = ApiClient(baseUrl: _prefs.getString(_serverKey) ?? defaultApiBaseUrl(), httpClient: httpClient) {
    api.token = _prefs.getString(_tokenKey);
    api.onUnauthenticated = _handleExpiredSession;
  }

  static const _tokenKey = 'auth_token';
  static const _serverKey = 'server_url';

  final SharedPreferences _prefs;
  final ApiClient api;

  SiteConfig config = SiteConfig.fallback();
  AppUser? user;
  bool ready = false;
  String? startupError;

  bool get isSignedIn => user != null;

  String get serverUrl => api.baseUrl;

  /// Loads the site settings and, when a token is saved, the signed-in user.
  Future<void> bootstrap() async {
    startupError = null;

    try {
      config = SiteConfig.fromJson(await api.get('/config'));
    } on ApiException catch (error) {
      startupError = error.message;
    }

    if (api.token != null) {
      try {
        final response = await api.get('/me');
        user = AppUser.fromJson(response['data'] as Map<String, dynamic>);
      } on ApiException catch (error) {
        if (error.isUnauthenticated || error.statusCode == 403) {
          await _clearSession();
        }
      }
    }

    ready = true;
    notifyListeners();
  }

  Future<void> refreshConfig() async {
    try {
      config = SiteConfig.fromJson(await api.get('/config'));
      notifyListeners();
    } on ApiException {
      // Keep the settings already loaded.
    }
  }

  Future<void> login(String login, String password) async {
    final response = await api.post('/auth/login', {
      'login': login,
      'password': password,
      'device_name': 'yogig-app',
    });
    await _startSession(response);
  }

  Future<void> registerClient(Map<String, dynamic> fields) async {
    await _startSession(await api.post('/auth/register/client', fields));
  }

  Future<void> registerMaid(Map<String, dynamic> fields, UploadFile nationalIdPhoto) async {
    await _startSession(await api.multipart('/auth/register/maid', fields: fields, files: [nationalIdPhoto]));
  }

  Future<void> reloadUser() async {
    final response = await api.get('/me');
    setUser(AppUser.fromJson(response['data'] as Map<String, dynamic>));
  }

  void setUser(AppUser updated) {
    user = updated;
    notifyListeners();
  }

  Future<void> logout() async {
    try {
      await api.post('/auth/logout');
    } on ApiException {
      // The token is dropped locally either way.
    }
    await _clearSession();
    notifyListeners();
  }

  Future<void> setServerUrl(String url) async {
    api.baseUrl = url.trim();
    await _prefs.setString(_serverKey, api.baseUrl);
    await refreshConfig();
  }

  Future<void> _startSession(Map<String, dynamic> response) async {
    api.token = response['token'] as String;
    await _prefs.setString(_tokenKey, api.token!);
    user = AppUser.fromJson(response['user'] as Map<String, dynamic>);
    notifyListeners();
  }

  Future<void> _clearSession() async {
    api.token = null;
    user = null;
    await _prefs.remove(_tokenKey);
  }

  void _handleExpiredSession() {
    if (user == null) {
      return;
    }
    _clearSession().then((_) => notifyListeners());
  }
}

/// Makes [AppState] available to every screen.
class AppScope extends InheritedNotifier<AppState> {
  const AppScope({super.key, required AppState state, required super.child}) : super(notifier: state);

  static AppState of(BuildContext context) {
    final scope = context.dependOnInheritedWidgetOfExactType<AppScope>();
    assert(scope != null, 'AppScope not found');
    return scope!.notifier!;
  }

  /// Reads the state without rebuilding when it changes, for use in callbacks.
  static AppState read(BuildContext context) {
    final scope = context.getInheritedWidgetOfExactType<AppScope>();
    return scope!.notifier!;
  }
}
