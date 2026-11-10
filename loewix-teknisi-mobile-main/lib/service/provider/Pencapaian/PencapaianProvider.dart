import 'dart:convert';
import 'package:flutter/foundation.dart';
import 'package:http/http.dart' as http;
import '../../api/ApiLink.dart';
import '../../model/pencapaian/PencapaianResponse.dart';
import '../../model/pencapaian/PendapatanResponse.dart';
import '../../../utils/demo_teknisi_data.dart';

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
      } else {
        _pencapaianData = DemoTeknisiData.pencapaian;
        _pencapaianState = PencapaianState.loaded;
      }
    } catch (e) {
      _pencapaianData = DemoTeknisiData.pencapaian;
      _pencapaianState = PencapaianState.loaded;
    }

    notifyListeners();
  }

  Future<void> getPendapatan({
    required int teknisiId,
    required int bulan,
    required int tahun,
    String filterType = 'month',
  }) async {
    _pendapatanState = PencapaianState.loading;
    notifyListeners();

    try {
      // PHP file on jadwal.id-giti.com/staff/
      final ts = DateTime.now().millisecondsSinceEpoch;
      final url = 'https://jadwal.id-giti.com/staff/api_teknisi_stats.php?teknisi_id=$teknisiId&bulan=$bulan&tahun=$tahun&filter_type=$filterType&_t=$ts';

      final response = await http.get(
        Uri.parse(url),
        headers: {'Accept': 'application/json', 'Cache-Control': 'no-cache'},
      ).timeout(const Duration(seconds: 30));

      if (response.statusCode == 200) {
        final jsonData = json.decode(response.body);
        _pendapatanData = PendapatanResponse.fromJson(jsonData);
        _pendapatanState = PencapaianState.loaded;
      } else {
        _pendapatanData = DemoTeknisiData.pendapatan;
        _pendapatanState = PencapaianState.loaded;
      }
    } catch (e) {
      _pendapatanData = DemoTeknisiData.pendapatan;
      _pendapatanState = PencapaianState.loaded;
    }

    notifyListeners();
  }

  Future<void> loadAll({
    required int teknisiId,
    required int bulan,
    required int tahun,
    String filterType = 'month',
  }) async {
    await Future.wait([
      getPencapaian(teknisiId: teknisiId, bulan: bulan, tahun: tahun),
      getPendapatan(teknisiId: teknisiId, bulan: bulan, tahun: tahun, filterType: filterType),
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
