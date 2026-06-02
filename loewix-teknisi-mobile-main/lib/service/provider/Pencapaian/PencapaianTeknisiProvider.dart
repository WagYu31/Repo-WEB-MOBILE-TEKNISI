import '../../../service/api/ApiTask.dart';
import '../../../service/model/pencapaian/PencapaianGet.dart';
import '../../../utils/state.dart';
import 'package:flutter/material.dart';

class PencapaianTeknisiProvider extends ChangeNotifier{
  final ApiTask api;

  PencapaianTeknisiProvider({required this.api});

  late PencapaianResponse _status;
  late ResultState _state = ResultState.noData;
  String _message = '';

  PencapaianResponse get response => _status;
  ResultState get state => _state;
  String get message => _message;

  Future<dynamic> getTask(String month, String id) async{
    try{
      _state = ResultState.loading;
      notifyListeners();

      final responses = await api.getPendapatan(month, id);

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