import 'dart:convert';
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

  // ─── Saved Accounts ──────────────────────────────────────
  static const _kSavedAccounts = 'saved_accounts';

  Future<List<SavedAccount>> getSavedAccounts() async {
    final prefs = await SharedPreferences.getInstance();
    final raw   = prefs.getString(_kSavedAccounts);
    if (raw == null || raw.isEmpty) return [];
    try {
      final list = jsonDecode(raw) as List;
      return list.map((e) => SavedAccount.fromJson(e)).toList();
    } catch (_) {
      return [];
    }
  }

  Future<void> _saveAccount(String nik, String nama) async {
    final prefs    = await SharedPreferences.getInstance();
    final accounts = await getSavedAccounts();
    // Hapus duplikat NIK yang sama
    accounts.removeWhere((a) => a.nik == nik);
    // Tambahkan di posisi pertama (terbaru)
    accounts.insert(0, SavedAccount(nik: nik, nama: nama));
    // Batasi maksimal 5 akun tersimpan
    final trimmed = accounts.take(5).toList();
    await prefs.setString(
        _kSavedAccounts, jsonEncode(trimmed.map((a) => a.toJson()).toList()));
  }

  Future<void> removeAccount(String nik) async {
    final prefs    = await SharedPreferences.getInstance();
    final accounts = await getSavedAccounts();
    accounts.removeWhere((a) => a.nik == nik);
    await prefs.setString(
        _kSavedAccounts, jsonEncode(accounts.map((a) => a.toJson()).toList()));
    notifyListeners();
  }

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
      // Simpan akun ke daftar akun tersimpan
      await _saveAccount(_profile!.nik, _profile!.nama);
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
    // Hapus sesi aktif TAPI jangan hapus saved accounts
    await prefs.remove('sales_id');
    await prefs.remove('sales_nama');
    await prefs.remove('sales_nik');
    await prefs.remove('sales_jabatan');
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
    List<String> base64Images = const [],
  }) async {
    final res = await _api.clockOut(
      kegiatanId: kegiatanId,
      salesId: _profile!.id,
      lat: lat,
      lon: lon,
      catatanVisit: catatan,
      isMock: isMock,
      base64Images: base64Images,
    );
    await fetchTasks();
    return res['message'] ?? 'Clock Out berhasil';
  }

  // ─── Update Report ────────────────────────────────────────
  Future<String> doUpdateReport({
    required int kegiatanId,
    required String catatan,
    required List<String> images,
  }) async {
    final res = await _api.updateReport(
      kegiatanId: kegiatanId,
      salesId: _profile!.id,
      catatanVisit: catatan,
      images: images,
    );
    await fetchTasks();
    return res['message'] ?? 'Laporan berhasil diperbarui';
  }
}

// ─── Model: Akun Tersimpan ────────────────────────────────
class SavedAccount {
  final String nik;
  final String nama;

  const SavedAccount({required this.nik, required this.nama});

  factory SavedAccount.fromJson(Map<String, dynamic> j) =>
      SavedAccount(nik: j['nik'] ?? '', nama: j['nama'] ?? '');

  Map<String, dynamic> toJson() => {'nik': nik, 'nama': nama};
}
