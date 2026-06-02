import '../../../service/api/ApiAuth.dart';
import '../../../utils/state.dart';
import 'package:flutter/material.dart';

class TeknisiRegisterProvider extends ChangeNotifier {
  final ApiAuth api;

  TeknisiRegisterProvider({required this.api});

  late String _registerResponse;
  late ResultState _state;
  String _message = '';

  String get registerResponse => _registerResponse;
  ResultState get state => _state;
  String get message => _message;

  Future<dynamic> doRegis(
      String email, String password, String nama, String id) async {
    try {
      _state = ResultState.loading;
      notifyListeners();

      final response = await api.teknisiRegister(email, password, nama, id);

      if (response.toString() == "Registrasi Berhasil") {
        _state = ResultState.hasData;
        notifyListeners();

        return _registerResponse = response;
      } else {
        _state = ResultState.noData;
        notifyListeners();

        return _message = response;
      }
    } catch (e) {
      _state = ResultState.error;
      notifyListeners();

      return _message = e.toString();
    }
  }
}
