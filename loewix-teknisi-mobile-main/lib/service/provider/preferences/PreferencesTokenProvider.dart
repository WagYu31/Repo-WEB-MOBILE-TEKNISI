import '../../../service/db/auth_repository.dart';
import '../../../utils/state.dart';
import 'package:flutter/material.dart';

class PreferencesTokenProvider extends ChangeNotifier{
  AuthRepository repository;

  PreferencesTokenProvider({required this.repository}){
    getUserTokenPreferences();
  }

  String _isUserToken = "";
  ResultState _state = ResultState.dll;
  String get isUserToken => _isUserToken;
  ResultState get state => _state;

  void getUserTokenPreferences() async {
    try{
      _state = ResultState.loading;
      notifyListeners();

      final responsePreferences = await repository.isUserToken;
      print('hasil providernya$responsePreferences');

      if(responsePreferences != "no token" || responsePreferences != ""){
        _state = ResultState.hasData;
        notifyListeners();

        _isUserToken = responsePreferences;
      }else{
        _state = ResultState.noData;
        notifyListeners();
      }
    }catch(e){
      throw Exception(e);
    }
  }

  void setUserToken(String value) async {
    repository.setUserToken(value);
  }

  void deleteToken(){
    repository.deleteUserToken();
    getUserTokenPreferences();
  }
}