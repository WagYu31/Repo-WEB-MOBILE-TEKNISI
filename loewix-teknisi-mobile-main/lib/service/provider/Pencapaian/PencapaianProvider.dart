import 'dart:convert';
import 'package:flutter/foundation.dart';
import 'package:http/http.dart' as http;
import '../../api/ApiLink.dart';
import '../../model/pencapaian/PencapaianResponse.dart';
import '../../model/pencapaian/PendapatanResponse.dart';

enum PencapaianState { initial, loading, loaded, error }

class PencapaianProvider extends ChangeNotifier {
  PencapaianState _pencapaianState = PencapaianState.initial;
  PencapaianState _pendapatanState = PencapaianState.initial;

  PencapaianResponse? _pencapaianData;
  PendapatanResponse? _pendapatanData;

  String _pencapaianError = '';
  String _pendapatanError = '';

  // Getters for pencapaian
  PencapaianState get pencapaianState => _pencapaianState;
  PencapaianResponse? get pencapaianData => _pencapaianData;
  String get pencapaianError => _pencapaianError;

  // Getters for pendapatan
  PencapaianState get pendapatanState => _pendapatanState;
  PendapatanResponse? get pendapatanData => _pendapatanData;
  String get pendapatanError => _pendapatanError;

  // Legacy getters for backward compatibility
  PencapaianState get state => _pencapaianState;
  PencapaianResponse? get data => _pencapaianData;
  String get errorMessage => _pencapaianError;

  Future<void> getPencapaian({
    required int teknisiId,
    required int bulan,
    required int tahun,
  }) async {
    _pencapaianState = PencapaianState.loading;
    notifyListeners();

    try {
      final url = '${Api.Url}/teknisi/pencapaian/$teknisiId/$bulan/$tahun';

      final response = await http.get(
        Uri.parse(url),
        headers: {'Accept': 'application/json'},
      ).timeout(const Duration(seconds: 30));

      if (response.statusCode == 200) {
        final jsonData = json.decode(response.body);
        _pencapaianData = PencapaianResponse.fromJson(jsonData);
        _pencapaianState = PencapaianState.loaded;
      } else if (response.statusCode == 404) {
        _pencapaianError = 'Data pencapaian tidak ditemukan';
        _pencapaianState = PencapaianState.error;
      } else {
        _pencapaianError = 'Gagal mengambil data pencapaian';
        _pencapaianState = PencapaianState.error;
      }
    } catch (e) {
      _pencapaianError = 'Terjadi kesalahan: ${e.toString()}';
      _pencapaianState = PencapaianState.error;
    }

    notifyListeners();
  }

  Future<void> getPendapatan({
    required int teknisiId,
    required int bulan,
    required int tahun,
  }) async {
    _pendapatanState = PencapaianState.loading;
    notifyListeners();

    try {
      final url = '${Api.Url}/teknisi/pendapatan/$teknisiId/$bulan/$tahun';

      final response = await http.get(
        Uri.parse(url),
        headers: {'Accept': 'application/json'},
      ).timeout(const Duration(seconds: 30));

      if (response.statusCode == 200) {
        final jsonData = json.decode(response.body);
        _pendapatanData = PendapatanResponse.fromJson(jsonData);
        _pendapatanState = PencapaianState.loaded;
      } else if (response.statusCode == 404) {
        _pendapatanError = 'Data pendapatan tidak ditemukan';
        _pendapatanState = PencapaianState.error;
      } else {
        _pendapatanError = 'Gagal mengambil data pendapatan';
        _pendapatanState = PencapaianState.error;
      }
    } catch (e) {
      _pendapatanError = 'Terjadi kesalahan: ${e.toString()}';
      _pendapatanState = PencapaianState.error;
    }

    notifyListeners();
  }

  Future<void> loadAll({
    required int teknisiId,
    required int bulan,
    required int tahun,
  }) async {
    await Future.wait([
      getPencapaian(teknisiId: teknisiId, bulan: bulan, tahun: tahun),
      getPendapatan(teknisiId: teknisiId, bulan: bulan, tahun: tahun),
    ]);
  }

  void reset() {
    _pencapaianState = PencapaianState.initial;
    _pendapatanState = PencapaianState.initial;
    _pencapaianData = null;
    _pendapatanData = null;
    _pencapaianError = '';
    _pendapatanError = '';
    notifyListeners();
  }
}
