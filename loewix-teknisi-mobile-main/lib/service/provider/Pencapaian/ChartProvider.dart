import '../../../service/api/ApiTeknisi.dart';
import '../../../service/model/pencapaian/PencapaianChartGet.dart';
import '../../../service/model/pencapaian/PencapaianChartGet.dart';
import 'package:flutter/material.dart';

import '../../../utils/state.dart';

class ChartsProvider extends ChangeNotifier{
  final ApiTeknisi api;

  ChartsProvider({required this.api});

  late ChartResponse _status;
  late ResultState _state = ResultState.noData;
  String _message = '';

  ChartResponse get response => _status;
  ResultState get state => _state;
  String get message => _message;

  Future<dynamic> getPencapaian(String id) async{
    try{
      _state = ResultState.loading;
      notifyListeners();

      final responses = await api.getPencapaian(id);

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