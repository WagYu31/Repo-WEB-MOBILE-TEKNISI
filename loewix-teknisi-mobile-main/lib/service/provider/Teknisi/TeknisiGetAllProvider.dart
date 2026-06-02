import '../../../service/api/ApiTeknisi.dart';
import '../../../service/model/teknisi/TeknisiGetAllModel.dart';
import '../../../utils/state.dart';
import 'package:flutter/material.dart';

class TeknisiGetAllProvider extends ChangeNotifier{
  final ApiTeknisi api;

  TeknisiGetAllProvider({required this.api}){
    getTeknisi();
  }

  late TeknisiGetAllResponse _status;
  late ResultState _state = ResultState.noData;
  String _message = '';

  TeknisiGetAllResponse get response => _status;
  ResultState get state => _state;
  String get message => _message;

  Future<dynamic> getTeknisi() async{
    try{
      _state = ResultState.loading;
      notifyListeners();

      final responses = await api.getAllTeknisi();

      print('Dijalanin kok');

      print('hasil get api$responses');

      if(responses.data.isNotEmpty){
        _state = ResultState.hasData;
        notifyListeners();

        return _status = responses;
      }else{
        _state = ResultState.noData;
        notifyListeners();
        return _message = 'Data Kosong';
      }

    }catch(e){
      _state = ResultState.error;
        notifyListeners();

        return _message = e.toString();
    }
  }
}