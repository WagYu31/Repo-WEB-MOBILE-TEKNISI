
import 'package:flutter/material.dart';

import '../../../utils/state.dart';
import '../../api/ApiBarang.dart';
import '../../model/barang/BarangResponse.dart';

class BarangGetProvider extends ChangeNotifier{
  final ApiBarang api;

  BarangGetProvider({required this.api}){
    getBarang();
  }

  late BarangGetResponse _status;
  late ResultState _state = ResultState.noData;
  String _message = '';

  BarangGetResponse get response => _status;
  ResultState get state => _state;
  String get message => _message;

  Future<dynamic> getBarang() async{
    try{
      _state = ResultState.loading;
      notifyListeners();
      print('Dijalanin kok');

      print('wak');

      final responses = await api.getBarang();

      print('terjalankan');

      print('udahan kok');

      print('hasil get api ${responses.data.length}');

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