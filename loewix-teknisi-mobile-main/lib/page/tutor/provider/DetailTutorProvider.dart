import '../../../utils/state.dart';
import '../../../page/tutor/api_tutor/ApiTutor.dart';
import '../../../page/tutor/model/DetailTutorResponse.dart';
import 'package:flutter/material.dart';

class DetailTutorProvider extends ChangeNotifier {
  final ApiTutor api;

  DetailTutorProvider({required this.api});

  late DetailTutorResponses _detailTutorResponses;
  ResultState _state = ResultState.loading;
  String _message = '';

  DetailTutorResponses get detailTutorResponse => _detailTutorResponses;
  ResultState get state => _state;
  String get message => _message;

  Future<void> getAllTutor(int id) async {
    try {
      _state = ResultState.loading;
      notifyListeners();

      final response = await api.getDetailTutor(id);

      if (response.data.id != null) {
        _state = ResultState.hasData;
        _detailTutorResponses = response;
      } else {
        _state = ResultState.noData;
        _message = 'Data tidak ditemukan';
      }
    } catch (e) {
      _state = ResultState.error;
      _message = 'Telah terjadi error';
    } finally {
      notifyListeners();
    }
  }
}
