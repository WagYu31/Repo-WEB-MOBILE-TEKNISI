import '../../../service/api/ApiPelaksanaan.dart';
import '../../../utils/state.dart';
import 'package:flutter/material.dart';

class RescheduleProvider extends ChangeNotifier {
  final ApiPelaksanaan api;

  RescheduleProvider({required this.api});

  late String _rescheduleResponse;
  late ResultState _state;
  String _message = '';

  String get rescheduleResponse => _rescheduleResponse;
  ResultState get state => _state;
  String get message => _message;

  Future<dynamic> doReschedule(String jadwal, String id) async {
    try {
      _state = ResultState.loading;
      notifyListeners();

      final response = await api.reschedule(jadwal, id);

      if (response.toString() == "Reschedule jadwal berhasil") {
        _state = ResultState.hasData;
        notifyListeners();

        return _rescheduleResponse = response;
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
