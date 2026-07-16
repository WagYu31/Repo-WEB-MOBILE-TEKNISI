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
      await prefs.setString('sales_no_tlp', _profile!.noTlp);
      await prefs.setString('sales_foto', _profile!.foto ?? '');
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
      noTlp: prefs.getString('sales_no_tlp') ?? '',
      jabatan: prefs.getString('sales_jabatan') ?? 'Sales',
      foto: prefs.getString('sales_foto') ?? '',
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
    await prefs.remove('sales_no_tlp');
    await prefs.remove('sales_foto');
    _profile = null;
    _tasks = [];
    notifyListeners();
  }

  // ─── Ubah Foto Profil ─────────────────────────────────────
  Future<bool> changeProfilePhoto(String base64Image) async {
    if (_profile == null) return false;
    _isLoading = true;
    _error = '';
    notifyListeners();
    try {
      final newPhoto = await _api.uploadProfilePhoto(
        salesId: _profile!.id,
        base64Image: base64Image,
      );
      _profile = SalesProfile(
        id: _profile!.id,
        nik: _profile!.nik,
        nama: _profile!.nama,
        noTlp: _profile!.noTlp,
        jabatan: _profile!.jabatan,
        foto: newPhoto,
      );
      final prefs = await SharedPreferences.getInstance();
      await prefs.setString('sales_foto', newPhoto);
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
    String namaClient = '',
    String nomerClient = '',
    String telpCustomer = '',
    String namaCustomer = '',
    String tipeProspek = 'Biasa',
    String noInvoice = '',
    bool isMock = false,
    List<String> base64Images = const [],
  }) async {
    final res = await _api.clockOut(
      kegiatanId: kegiatanId,
      salesId: _profile!.id,
      lat: lat,
      lon: lon,
      catatanVisit: catatan,
      namaClient: namaClient,
      nomerClient: nomerClient,
      telpCustomer: telpCustomer,
      namaCustomer: namaCustomer,
      tipeProspek: tipeProspek,
      noInvoice: noInvoice,
      isMock: isMock,
      base64Images: base64Images,
    );
    await fetchTasks();
    return res['message'] ?? 'Clock Out berhasil';
  }

  // ─── Reschedule ───────────────────────────────────────────
  Future<String> doReschedule({
    required int kegiatanId,
    required String newJadwal,
    required String reason,
  }) async {
    final res = await _api.reschedule(
      kegiatanId: kegiatanId,
      salesId: _profile!.id,
      newJadwal: newJadwal,
      reason: reason,
    );
    await fetchTasks();
    return res['message'] ?? 'Kunjungan berhasil dijadwalkan ulang';
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
