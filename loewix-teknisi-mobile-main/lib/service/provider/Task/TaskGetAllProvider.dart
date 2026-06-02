import 'package:flutter/material.dart';

import '../../../service/api/ApiTask.dart';
import '../../../service/model/task/TaskAllResponse.dart';
import '../../../utils/state.dart';

class TaskGetAllProvider extends ChangeNotifier {
  final ApiTask api;

  TaskGetAllProvider({required this.api});

  TaskAllResponse? _response;
  ResultState _state = ResultState.noData;
  String _message = '';

  TaskAllResponse get response => _response!;
  bool get hasResponse => _response != null;
  ResultState get state => _state;
  String get message => _message;

  Future<void> getTask() async {
    try {
      _state = ResultState.loading;
      notifyListeners();

      final responses = await api.getAllTask();

      if (responses.data.isNotEmpty) {
        _response = responses;
        _state = ResultState.hasData;
      } else {
        _message = 'Data Kosong';
        _state = ResultState.noData;
      }
      notifyListeners();
    } catch (e) {
      _message = e.toString();
      _state = ResultState.error;
      notifyListeners();
    }
  }
}