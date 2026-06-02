import 'package:flutter/material.dart';

import '../../../service/db/auth_repository.dart';
import '../../../utils/state.dart';

class PreferencesIDProvider extends ChangeNotifier {
  final AuthRepository repository;

  PreferencesIDProvider({required this.repository});

  String _isUserRole = '';
  ResultState _state = ResultState.dll;

  String get isUserRole => _isUserRole;
  ResultState get state => _state;

  Future<void> getUserRolePreferences() async {
    try {
      _state = ResultState.loading;
      notifyListeners();

      final responsePreferences = await repository.isUserID;

      if (responsePreferences != 'no role' && responsePreferences.isNotEmpty) {
        _isUserRole = responsePreferences;
        _state = ResultState.hasData;
      } else {
        _state = ResultState.noData;
      }
      notifyListeners();
    } catch (e) {
      _state = ResultState.error;
      notifyListeners();
      rethrow;
    }
  }

  Future<void> setUserRole(String value) async {
    await repository.setUserID(value);
  }

  Future<void> deleteRole() async {
    await repository.deleteUserID();
    await getUserRolePreferences();
  }
}