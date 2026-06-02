import '../../../service/model/profile/ProfileTeknisi.dart';
import 'package:flutter/material.dart';

import '../../../utils/state.dart';
import '../../api/ApiAuth.dart';

class ProfileProvider extends ChangeNotifier {
  final ApiAuth api;

  ProfileProvider({required this.api});

  late ProfileTeknisiResponse _response;
  late ResultState _state;
  String _message = '';

  ProfileTeknisiResponse get response => _response;
  ResultState get state => _state;
  String get message => _message;

  Future<ProfileTeknisiResponse> getUser(String token) async {
    try {
      _state = ResultState.loading;
      notifyListeners();

      final response = await api.getUser(token);

      print('hasil get provider user : $response');

      if (response.data.nama.isNotEmpty) {
        _state = ResultState.hasData;
        _response = response;
        notifyListeners();
        return response;
      } else {
        _state = ResultState.noData;
        _message = 'gagal ambil data user';
        notifyListeners();
        throw Exception(_message);
      }
    } catch (e) {
      _state = ResultState.error;
      _message = 'Something error $e';
      notifyListeners();
      throw Exception(_message);
    }
  }
}