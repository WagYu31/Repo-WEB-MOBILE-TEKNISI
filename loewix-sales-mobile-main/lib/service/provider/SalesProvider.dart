import 'package:flutter/material.dart';
import 'package:shared_preferences/shared_preferences.dart';
import '../api/ApiSales.dart';
import '../model/SalesModel.dart';

class SalesProvider extends ChangeNotifier {
  final ApiSales _api = ApiSales();

  SalesProfile? _profile;
  List<VisitTask> _tasks = [];
  bool _isLoading = false;
  String _error = '';

  SalesProfile? get profile => _profile;
  List<VisitTask> get tasks => _tasks;
  bool get isLoading => _isLoading;
  String get error => _error;
  bool get isLoggedIn => _profile != null;

  // ─── Login ───────────────────────────────────────────────
  Future<bool> login(String nik, String password) async {
    _isLoading = true;
    _error = '';
    notifyListeners();
    try {
      _profile = await _api.login(nik, password);
      final prefs = await SharedPreferences.getInstance();
      await prefs.setInt('sales_id', _profile!.id);
      await prefs.setString('sales_nama', _profile!.nama);
      await prefs.setString('sales_nik', _profile!.nik);
      await prefs.setString('sales_jabatan', _profile!.jabatan);
      _isLoading = false;
      notifyListeners();
      return true;
    } catch (e) {
      _error = e.toString().replaceAll('Exception: ', '');
      _isLoading = false;
      notifyListeners();
      return false;
    }
  }

  // ─── Load dari SharedPreferences ─────────────────────────
  Future<bool> loadFromPrefs() async {
    final prefs = await SharedPreferences.getInstance();
    final id = prefs.getInt('sales_id');
    if (id == null) return false;
    _profile = SalesProfile(
      id: id,
      nik: prefs.getString('sales_nik') ?? '',
      nama: prefs.getString('sales_nama') ?? '',
      noTlp: '',
      jabatan: prefs.getString('sales_jabatan') ?? 'Sales',
    );
    notifyListeners();
    return true;
  }

  // ─── Logout ───────────────────────────────────────────────
  Future<void> logout() async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.clear();
    _profile = null;
    _tasks = [];
    notifyListeners();
  }

  // ─── Fetch Tasks ──────────────────────────────────────────
  Future<void> fetchTasks({String filter = 'today'}) async {
    if (_profile == null) return;
    _isLoading = true;
    _error = '';
    notifyListeners();
    try {
      _tasks = await _api.getTasks(_profile!.id, filter: filter);
    } catch (e) {
      _error = e.toString().replaceAll('Exception: ', '');
    }
    _isLoading = false;
    notifyListeners();
  }

  // ─── Clock In ─────────────────────────────────────────────
  Future<String> doClockIn({
    required int kegiatanId,
    required String lat,
    required String lon,
    bool isMock = false,
  }) async {
    final res = await _api.clockIn(
      kegiatanId: kegiatanId,
      salesId: _profile!.id,
      lat: lat,
      lon: lon,
      isMock: isMock,
    );
    await fetchTasks();
    return res['message'] ?? 'Clock In berhasil';
  }

  // ─── Clock Out ────────────────────────────────────────────
  Future<String> doClockOut({
    required int kegiatanId,
    required String lat,
    required String lon,
    String catatan = '',
    bool isMock = false,
  }) async {
    final res = await _api.clockOut(
      kegiatanId: kegiatanId,
      salesId: _profile!.id,
      lat: lat,
      lon: lon,
      catatanVisit: catatan,
      isMock: isMock,
    );
    await fetchTasks();
    return res['message'] ?? 'Clock Out berhasil';
  }
}
