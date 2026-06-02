import 'package:flutter/material.dart';
import 'package:teknisi_loewix/service/api/ApiReimburse.dart';

class ReimburseDeleteProvider with ChangeNotifier {
  final ApiReimburse _api = ApiReimburse();

  bool _isDeleting = false;
  String? _error;
  bool _isDeleted = false;

  bool get isDeleting => _isDeleting;
  String? get error => _error;
  bool get isDeleted => _isDeleted;

  Future<void> deleteReimburse(int id) async {
    _isDeleting = true;
    _error = null;
    _isDeleted = false;
    notifyListeners();

    try {
      await _api.deleteReimburse(id);
      _isDeleted = true;
    } catch (e) {
      _error = e.toString();
    }

    _isDeleting = false;
    notifyListeners();
  }

  void resetStatus() {
    _isDeleting = false;
    _error = null;
    _isDeleted = false;
    notifyListeners();
  }
}