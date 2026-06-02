import '../../../page/tutor/api_tutor/ApiTutor.dart';
import '../../../page/tutor/model/TutorResponse.dart';
import '../../../service/model/auth/LoginResponse.dart';
import '../../../utils/state.dart';
import 'package:flutter/material.dart';

class TutorialAllProvider extends ChangeNotifier{
  final ApiTutor api;

  TutorialAllProvider({required this.api}){
    getAllTutor();
  }

  late TutorResponses _tutorResponse;
  late ResultState _state;
  String _message = '';

  TutorResponses get tutorResponse => _tutorResponse;
  ResultState get state => _state;
  String get message => _message;

  Future<dynamic> getAllTutor() async {
    try{
      _state = ResultState.loading;
      notifyListeners();

      final response = await api.getAllTutor();

      if(response.data.isNotEmpty){
        _state = ResultState.hasData;
        notifyListeners();

        return _tutorResponse = response;
      }else{
        _state = ResultState.noData;
        notifyListeners();

        return _message = 'Data tidak ditemukan';
      }
    }catch(e){
      _state = ResultState.error;
      notifyListeners();

      print('ada masalah:'+e.toString());

      return _message = 'Telah terjadi error';
    }
  }
}