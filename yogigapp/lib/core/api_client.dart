import 'dart:convert';

import 'package:flutter/foundation.dart';
import 'package:http/http.dart' as http;
import 'package:image_picker/image_picker.dart';

/// Error returned by the Laravel API, with any per-field validation messages.
class ApiException implements Exception {
  ApiException(this.message, {this.statusCode, this.errors = const {}});

  final String message;
  final int? statusCode;
  final Map<String, List<String>> errors;

  bool get isUnauthenticated => statusCode == 401;

  /// The first validation message for [field], if any.
  String? fieldError(String field) {
    final messages = errors[field];
    return messages == null || messages.isEmpty ? null : messages.first;
  }

  @override
  String toString() => message;
}

/// A file picked on the device, sent as part of a multipart request.
class UploadFile {
  UploadFile(this.field, this.file);

  final String field;
  final XFile file;
}

/// Thin JSON client for the Yo-gig House Manager API (`/api/v1`).
class ApiClient {
  ApiClient({required this.baseUrl, this.token, this.onUnauthenticated, http.Client? httpClient}) : _http = httpClient ?? http.Client();

  String baseUrl;
  String? token;
  VoidCallback? onUnauthenticated;

  final http.Client _http;

  /// Origin of the website, used to resolve relative links from page content.
  String get siteUrl => baseUrl.replaceFirst(RegExp(r'/api/v1/?$'), '');

  Map<String, String> get authHeaders => {
        if (token != null) 'Authorization': 'Bearer $token',
      };

  Map<String, String> get _headers => {
        'Accept': 'application/json',
        ...authHeaders,
      };

  Uri _uri(String path, [Map<String, dynamic>? query]) {
    final cleaned = <String, String>{};
    query?.forEach((key, value) {
      if (value != null && value.toString().isNotEmpty) {
        cleaned[key] = value.toString();
      }
    });
    final base = baseUrl.endsWith('/') ? baseUrl.substring(0, baseUrl.length - 1) : baseUrl;
    return Uri.parse('$base$path').replace(queryParameters: cleaned.isEmpty ? null : cleaned);
  }

  Future<Map<String, dynamic>> get(String path, {Map<String, dynamic>? query}) {
    return _send(() => _http.get(_uri(path, query), headers: _headers));
  }

  Future<Map<String, dynamic>> post(String path, [Map<String, dynamic>? body]) {
    return _send(() => _http.post(
          _uri(path),
          headers: {..._headers, 'Content-Type': 'application/json'},
          body: jsonEncode(body ?? {}),
        ));
  }

  Future<Map<String, dynamic>> put(String path, Map<String, dynamic> body) {
    return _send(() => _http.put(
          _uri(path),
          headers: {..._headers, 'Content-Type': 'application/json'},
          body: jsonEncode(body),
        ));
  }

  Future<Map<String, dynamic>> delete(String path) {
    return _send(() => _http.delete(_uri(path), headers: _headers));
  }

  /// Sends form fields and files. Laravel only reads multipart bodies on POST,
  /// so other verbs are spoofed with a `_method` field.
  Future<Map<String, dynamic>> multipart(
    String path, {
    String method = 'POST',
    Map<String, dynamic> fields = const {},
    List<UploadFile> files = const [],
  }) {
    return _send(() async {
      final request = http.MultipartRequest('POST', _uri(path))..headers.addAll(_headers);

      if (method != 'POST') {
        request.fields['_method'] = method;
      }

      fields.forEach((key, value) {
        if (value == null) {
          return;
        }
        request.fields[key] = value is bool ? (value ? '1' : '0') : value.toString();
      });

      for (final upload in files) {
        request.files.add(http.MultipartFile.fromBytes(
          upload.field,
          await upload.file.readAsBytes(),
          filename: upload.file.name,
        ));
      }

      return http.Response.fromStream(await _http.send(request));
    });
  }

  Future<Map<String, dynamic>> _send(Future<http.Response> Function() request) async {
    final http.Response response;

    try {
      response = await request().timeout(const Duration(seconds: 30));
    } catch (error) {
      throw ApiException('Could not reach the server. Check your internet connection and the server address.');
    }

    dynamic decoded;
    if (response.body.isNotEmpty) {
      try {
        decoded = jsonDecode(response.body);
      } catch (_) {
        decoded = null;
      }
    }

    final json = decoded is Map<String, dynamic> ? decoded : <String, dynamic>{'data': decoded};

    if (response.statusCode >= 200 && response.statusCode < 300) {
      return json;
    }

    if (response.statusCode == 401) {
      onUnauthenticated?.call();
    }

    final errors = <String, List<String>>{};
    final rawErrors = json['errors'];
    if (rawErrors is Map) {
      rawErrors.forEach((key, value) {
        errors[key.toString()] = value is List ? value.map((e) => e.toString()).toList() : [value.toString()];
      });
    }

    final message = errors.isNotEmpty
        ? errors.values.first.first
        : (json['message']?.toString().isNotEmpty ?? false)
            ? json['message'].toString()
            : 'Something went wrong (${response.statusCode}). Please try again.';

    throw ApiException(message, statusCode: response.statusCode, errors: errors);
  }
}
