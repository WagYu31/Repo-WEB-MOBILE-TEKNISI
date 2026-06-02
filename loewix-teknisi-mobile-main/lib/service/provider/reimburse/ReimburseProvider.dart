import 'package:flutter/material.dart';
import 'package:teknisi_loewix/service/api/ApiReimburse.dart';
import 'package:teknisi_loewix/service/model/reimburse/ReimburseModel.dart';

class ReimburseProvider with ChangeNotifier {
  final ApiReimburse _api = ApiReimburse();

  List<DataReimburse> _reimburseList = [];
  bool _isLoading = false;
  String? _error;

  List<DataReimburse> get reimburseList => _reimburseList;
  bool get isLoading => _isLoading;
  String? get error => _error;

  Future<void> fetchReimburse(int teknisiId) async {
    _isLoading = true;
    _error = null;
    notifyListeners();

    try {
      final response = await _api.getReimburse(teknisiId);
      _reimburseList = response.data;
    } catch (e) {
      _error = e.toString();
    }

    _isLoading = false;
    notifyListeners();
  }

  void clear() {
    _reimburseList = [];
    _error = null;
    notifyListeners();
  }
}