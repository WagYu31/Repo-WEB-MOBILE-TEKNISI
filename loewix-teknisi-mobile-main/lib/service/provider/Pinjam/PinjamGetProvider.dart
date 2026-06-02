import 'package:flutter/material.dart';

import '../../../utils/state.dart';
import '../../api/ApiPinjam.dart';
import '../../model/pinjam/PinjamGetModel.dart';

class PinjamGetProvider extends ChangeNotifier{
  final ApiPinjam api;

  PinjamGetProvider({required this.api});

  late PinjamGetResponse _status;
  late ResultState _state = ResultState.noData;
  String _message = '';

  PinjamGetResponse get response => _status;
  ResultState get state => _state;
  String get message => _message;

  Future<dynamic> getPinjam(String id) async{
    try{
      _state = ResultState.loading;
      notifyListeners();
      print('Dijalanin kok');

      print('wak');

      final responses = await api.getActivePinjam(id);

      print('terjalankan');

      print('udahan kok');

      //print('hasil get api ${responses.data.length}');

      if(responses.data.isNotEmpty){
        _state = ResultState.hasData;
        notifyListeners();
        print('berhasil diretun sukses');
        return _status = responses;
      }else{
        _state = ResultState.noData;
        notifyListeners();
        print('gagal diretun sukses');
        return _message = 'Data Kosong';
      }

    }catch(e){
      _state = ResultState.error;
      notifyListeners();
      print('error diretun sukses');
      print(e.toString());
      return _message = 'disini yee $e';
    }
  }
}