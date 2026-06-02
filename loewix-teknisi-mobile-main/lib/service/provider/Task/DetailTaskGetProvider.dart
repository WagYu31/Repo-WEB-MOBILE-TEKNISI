import '../../../service/api/ApiTask.dart';
import '../../../service/model/task/TaskAllResponse.dart';
import '../../../utils/state.dart';
import 'package:flutter/material.dart';

class DetailTaskGetProvider extends ChangeNotifier {
  final ApiTask api;

  DetailTaskGetProvider({required this.api});

  late TaskAllResponse _status;
  late ResultState _state = ResultState.noData;
  String _message = '';

  TaskAllResponse get response => _status;
  ResultState get state => _state;
  String get message => _message;

  Future<dynamic> getTask(String id) async {
    try {
      _state = ResultState.loading;
      Future.microtask(() => notifyListeners()); // Tunda notifikasi

      final responses = await api.getDetailTask(id);

      if (responses.data.isNotEmpty) {
        _state = ResultState.hasData;
        notifyListeners();
        return _status = responses;
      } else {
        _state = ResultState.noData;
        notifyListeners();
        return _message = 'Data Kosong';
      }
    } catch (e) {
      _state = ResultState.error;
      notifyListeners();
      return _message = e.toString();
    }
  }
}
