import 'package:flutter/material.dart';

import '../../../utils/state.dart';

class MapsProvider extends ChangeNotifier {
  MapsProvider();

  String _alamat = '';
  ResultState _state = ResultState.loading;
  String _message = '';

  String get response => _alamat;
  String get message => _message;
  ResultState get state => _state;

  void setData(String newData, bool hasError) {
    _alamat = newData;
    _state = hasError ? ResultState.noData : ResultState.hasData;
    notifyListeners();
  }

  void setError(String errorMessage) {
    _message = errorMessage;
    _state = ResultState.error;
    notifyListeners();
  }
}