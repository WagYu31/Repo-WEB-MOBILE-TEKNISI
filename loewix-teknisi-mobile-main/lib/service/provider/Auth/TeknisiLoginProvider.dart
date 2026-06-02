import 'dart:io';

import 'package:flutter/material.dart';

import '../../../service/api/ApiAuth.dart';
import '../../../service/model/auth/LoginResponse.dart';
import '../../../utils/state.dart';

class TeknisiLoginProvider extends ChangeNotifier {
  final ApiAuth api;

  TeknisiLoginProvider({required this.api});

  LoginResponse? _loginResponse;
  ResultState _state = ResultState.dll;
  String _message = '';

  LoginResponse? get loginResponse => _loginResponse;
  ResultState get state => _state;
  String get message => _message;

  Future<dynamic> doLogin(String username, String password) async {
    if (username.trim().isEmpty || password.isEmpty) {
      _state = ResultState.error;
      _message = 'Username dan password tidak boleh kosong';
      notifyListeners();
      return _message;
    }

    try {
      _state = ResultState.loading;
      _message = '';
      notifyListeners();

      final response = await api.loginUser(username.trim(), password);

      if (response.token.isNotEmpty) {
        _state = ResultState.hasData;
        _loginResponse = response;
        notifyListeners();
        return response;
      } else {
        _state = ResultState.noData;
        _message = 'Login gagal. Silakan coba lagi.';
        notifyListeners();
        return _message;
      }
    } on SocketException {
      _state = ResultState.error;
      _message = 'Tidak dapat terhubung ke server. Periksa koneksi internet Anda.';
      notifyListeners();
      return _message;
    } on HttpException {
      _state = ResultState.error;
      _message = 'Terjadi kesalahan pada server.';
      notifyListeners();
      return _message;
    } on FormatException {
      _state = ResultState.error;
      _message = 'Format respons tidak valid.';
      notifyListeners();
      return _message;
    } catch (e) {
      _state = ResultState.error;
      _message = _parseErrorMessage(e);
      notifyListeners();
      return _message;
    }
  }

  String _parseErrorMessage(dynamic error) {
    final errorStr = error.toString();

    if (errorStr.contains('Exception:')) {
      return errorStr.replaceAll('Exception:', '').trim();
    }

    if (errorStr.toLowerCase().contains('timeout')) {
      return 'Koneksi timeout. Silakan coba lagi.';
    }

    if (errorStr.toLowerCase().contains('unauthorized') ||
        errorStr.toLowerCase().contains('401')) {
      return 'Username atau password salah.';
    }

    return 'Terjadi kesalahan. Silakan coba lagi.';
  }

  void resetState() {
    _state = ResultState.dll;
    _message = '';
    _loginResponse = null;
    notifyListeners();
  }
}
