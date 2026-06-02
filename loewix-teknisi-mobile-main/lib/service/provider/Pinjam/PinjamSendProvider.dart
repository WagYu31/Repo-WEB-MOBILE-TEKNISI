import '../../../service/api/ApiPinjam.dart';
import '../../../service/model/pinjam/PinjamSendModel.dart';
import '../../../service/model/pinjam/PinjamSendModel.dart';
import 'package:flutter/material.dart';

import '../../../utils/state.dart';

class PinjamSendProvider extends ChangeNotifier {
  final ApiPinjam api;

  PinjamSendProvider({required this.api});

  late PinjamSendResponse _status;
  late ResultState _state = ResultState.noData;
  String _message = '';

  PinjamSendResponse get response => _status;

  ResultState get state => _state;

  String get message => _message;

  Future<dynamic> postBarang(
      {required String teknisiId,
      required String barangId,
      required int qty,
      required String tglPinjam,
      required String code}) async {
    try {
      _state = ResultState.loading;
      notifyListeners();

      print('kok ga dijalanin');

      final responses = await api.sendPinjam(
          teknisiId: teknisiId,
          barangId: barangId,
          qty: qty,
          tglPinjam: tglPinjam,
          code: code);

      print('Dijalanin kok');

      print('hasil get api$responses');

      if (responses.message == 'Barang berhasil ditambahkan') {
        _state = ResultState.hasData;
        notifyListeners();

        print('lele${responses.message}');

        return _status = responses;
      } else {
        _state = ResultState.noData;
        notifyListeners();
        print('err');
        return _message = 'Error When Uploading';
      }
    } catch (e) {
      _state = ResultState.dll;
      notifyListeners();

      return _message = e.toString();
    }
  }
}
