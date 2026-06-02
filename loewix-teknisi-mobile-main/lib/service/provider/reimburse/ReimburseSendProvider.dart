import 'dart:io';
import 'package:flutter/material.dart';
import 'package:teknisi_loewix/service/api/ApiReimburse.dart';

class ReimburseSendProvider with ChangeNotifier {
  final ApiReimburse _api = ApiReimburse();

  bool _isSending = false;
  String? _error;
  bool _isSuccess = false;

  bool get isSending => _isSending;
  String? get error => _error;
  bool get isSuccess => _isSuccess;

  Future<void> sendReimburse({
    required String kegiatanId,
    required String teknisiId,
    required int nominal,
    required String tanggal,
    String? keterangan,
    required File file1,
    File? file2,
    File? file3,
    File? file4,
    File? file5,
  }) async {
    _isSending = true;
    _error = null;
    _isSuccess = false;
    notifyListeners();

    try {
      await _api.createReimburse(
        kegiatanId: kegiatanId,
        teknisiId: teknisiId,
        nominal: nominal,
        tanggal: tanggal,
        keterangan: keterangan,
        file1: file1,
        file2: file2,
        file3: file3,
        file4: file4,
        file5: file5,
      );
      _isSuccess = true;
    } catch (e) {
      _error = e.toString();
    }

    _isSending = false;
    notifyListeners();
  }

  void resetStatus() {
    _isSending = false;
    _error = null;
    _isSuccess = false;
    notifyListeners();
  }
}