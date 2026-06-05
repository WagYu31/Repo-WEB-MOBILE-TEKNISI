import 'dart:convert';
import 'dart:io';

import 'package:http/http.dart' as http;

import '../../../service/model/auth/LoginResponse.dart';
import '../../../service/model/profile/ProfileTeknisi.dart';
import 'ApiLink.dart';

class ApiAuth {
  final String _baseUrl = Api.Url;

  static const Duration _timeout = Duration(seconds: 30);

  Map<String, String> get _defaultHeaders => {
    'Accept': 'application/json',
    'Content-Type': 'application/x-www-form-urlencoded',
    'User-Agent': 'TeknisiLoewix/3.7',
  };

  Future<String> teknisiRegister(
    String email,
    String password,
    String nama,
    String id,
  ) async {
    final data = {
      'username': email.trim(),
      'password': password,
      'nama': nama.trim(),
      'teknisi_id': id,
    };

    try {
      final response = await http
          .post(
            Uri.parse("$_baseUrl/teknisi/register/reg"),
            headers: _defaultHeaders,
            body: data,
          )
          .timeout(_timeout);

      if (response.statusCode == 200 || response.statusCode == 201) {
        return 'Registrasi Berhasil';
      }

      final errorMessage = _extractErrorMessage(response);
      throw Exception(errorMessage);
    } on SocketException {
      throw Exception('Tidak dapat terhubung ke server. Periksa koneksi internet Anda.');
    } on HttpException {
      throw Exception('Terjadi kesalahan pada server.');
    } on FormatException {
      throw Exception('Format respons tidak valid.');
    }
  }

  Future<LoginResponse> loginUser(String username, String password) async {
    final data = {
      'username': username.trim(),
      'password': password,
    };

    try {
      final url = "$_baseUrl/teknisi/login/log";
      print('Login URL: $url');
      print('Login Headers: $_defaultHeaders');
      print('Login Body: $data');

      final response = await http
          .post(
            Uri.parse(url),
            headers: _defaultHeaders,
            body: data,
          )
          .timeout(_timeout);

      print('Login Response Status: ${response.statusCode}');
      print('Login Response Body: ${response.body}');

      if (response.statusCode == 200) {
        final jsonBody = json.decode(response.body);
        return LoginResponse.fromJson(jsonBody);
      }

      if (response.statusCode == 401) {
        throw Exception('Username atau password salah.');
      }

      if (response.statusCode == 422) {
        final errorMessage = _extractValidationError(response);
        throw Exception(errorMessage);
      }

      final errorMessage = _extractErrorMessage(response);
      throw Exception(errorMessage);
    } on SocketException {
      throw Exception('Tidak dapat terhubung ke server. Periksa koneksi internet Anda.');
    } on HttpException {
      throw Exception('Terjadi kesalahan pada server.');
    } on FormatException {
      throw Exception('Format respons tidak valid.');
    }
  }

  Future<ProfileTeknisiResponse> getUser(String id) async {
    final requestHeaders = {
      'Content-type': 'application/json',
      'Accept': 'application/json',
    };

    try {
      final response = await http
          .get(
            Uri.parse("$_baseUrl/teknisi/profile/$id"),
            headers: requestHeaders,
          )
          .timeout(_timeout);

      if (response.statusCode == 200) {
        return ProfileTeknisiResponse.fromJson(json.decode(response.body));
      }

      if (response.statusCode == 404) {
        throw Exception('Data teknisi tidak ditemukan.');
      }

      final errorMessage = _extractErrorMessage(response);
      throw Exception(errorMessage);
    } on SocketException {
      throw Exception('Tidak dapat terhubung ke server. Periksa koneksi internet Anda.');
    } on HttpException {
      throw Exception('Terjadi kesalahan pada server.');
    } on FormatException {
      throw Exception('Format respons tidak valid.');
    }
  }

  String _extractErrorMessage(http.Response response) {
    try {
      final errorData = jsonDecode(response.body) as Map<String, dynamic>;
      return errorData['message']?.toString() ??
             errorData['error']?.toString() ??
             'Terjadi kesalahan. Silakan coba lagi.';
    } catch (_) {
      return 'Terjadi kesalahan. Silakan coba lagi.';
    }
  }

  String _extractValidationError(http.Response response) {
    try {
      final errorData = jsonDecode(response.body) as Map<String, dynamic>;

      if (errorData.containsKey('errors')) {
        final errors = errorData['errors'] as Map<String, dynamic>;
        final firstError = errors.values.first;
        if (firstError is List && firstError.isNotEmpty) {
          return firstError.first.toString();
        }
      }

      return errorData['message']?.toString() ?? 'Validasi gagal.';
    } catch (_) {
      return 'Validasi gagal.';
    }
  }
}
