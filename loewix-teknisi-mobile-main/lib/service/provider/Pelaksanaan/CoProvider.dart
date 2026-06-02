import '../../../service/api/ApiPelaksanaan.dart';
import '../../../service/model/pelaksanaan/PelaksanaanSend.dart';
import '../../../utils/state.dart';
import 'package:flutter/material.dart';

class CoProvider extends ChangeNotifier {
  final ApiPelaksanaan api;

  CoProvider({required this.api});

  late PelaksanaanSendResponse _pelaksanaanResponse;
  late ResultState _state;
  String _message = '';

  PelaksanaanSendResponse get pelaksanaanResponse => _pelaksanaanResponse;
  ResultState get state => _state;
  String get message => _message;

  Future<dynamic> doCo(
    String kegiatanId,
    String teknisiId,
    String lat,
    String lon, {
    double? accuracy,
    bool? isMock,
  }) async {
    try {
      _state = ResultState.loading;
      notifyListeners();

      final response = await api.sendClockOut(
        kegiatanId,
        teknisiId,
        lat,
        lon,
        accuracy: accuracy,
        isMock: isMock,
      );

      if (response.message == "Pelaksanaan kegiatan berhasil ditambahkan") {
        _state = ResultState.hasData;
        notifyListeners();

        return _pelaksanaanResponse = response;
      } else {
        _state = ResultState.noData;
        notifyListeners();

        return _message = response.message;
      }
    } catch (e) {
      _state = ResultState.error;
      notifyListeners();

      return _message = e.toString();
    }
  }
}
