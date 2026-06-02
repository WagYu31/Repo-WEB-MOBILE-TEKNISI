import '../../../service/api/ApiTask.dart';
import '../../../service/model/task/TaskAllResponse.dart';
import '../../../service/model/task/HistoryPaginatedResponse.dart';
import '../../../utils/state.dart';
import 'package:flutter/material.dart';

class HistoryGetAllProvider extends ChangeNotifier {
  final ApiTask api;

  HistoryGetAllProvider({required this.api});

  // Data list yang akan di-accumulate
  List<DataTask> _historyList = [];

  // Pagination state
  int _currentPage = 1;
  int _lastPage = 1;
  int _total = 0;
  bool _hasNextPage = false;
  bool _isLoadingMore = false;

  ResultState _state = ResultState.dll;
  String _message = '';
  String? _teknisiId;

  // Getters
  List<DataTask> get historyList => _historyList;
  int get currentPage => _currentPage;
  int get lastPage => _lastPage;
  int get total => _total;
  bool get hasNextPage => _hasNextPage;
  bool get isLoadingMore => _isLoadingMore;
  ResultState get state => _state;
  String get message => _message;

  /// Mengambil data history pertama kali (reset pagination)
  Future<void> getTask(String teknisiId) async {
    try {
      _teknisiId = teknisiId;
      _currentPage = 1;
      _historyList.clear();

      _state = ResultState.loading;
      notifyListeners();

      final response = await api.getHistoryTask(teknisiId, page: 1);

      if (response.data.isNotEmpty) {
        _historyList = response.data;
        _currentPage = response.currentPage;
        _lastPage = response.lastPage;
        _total = response.total;
        _hasNextPage = response.hasNextPage;

        _state = ResultState.hasData;
      } else {
        _state = ResultState.noData;
        _message = 'Tidak ada riwayat tugas';
      }

      notifyListeners();
    } catch (e) {
      _state = ResultState.error;
      _message = e.toString();
      notifyListeners();
    }
  }

  /// Memuat data halaman berikutnya (load more)
  Future<void> loadMore() async {
    // Cegah multiple load
    if (_isLoadingMore || !_hasNextPage || _teknisiId == null) {
      return;
    }

    try {
      _isLoadingMore = true;
      notifyListeners();

      final nextPage = _currentPage + 1;
      final response = await api.getHistoryTask(_teknisiId!, page: nextPage);

      if (response.data.isNotEmpty) {
        _historyList.addAll(response.data);
        _currentPage = response.currentPage;
        _lastPage = response.lastPage;
        _total = response.total;
        _hasNextPage = response.hasNextPage;
      }

      _isLoadingMore = false;
      notifyListeners();
    } catch (e) {
      _isLoadingMore = false;
      _message = e.toString();
      notifyListeners();
    }
  }

  /// Refresh data (pull to refresh)
  Future<void> refresh() async {
    if (_teknisiId != null) {
      await getTask(_teknisiId!);
    }
  }

  /// Reset semua state
  void reset() {
    _historyList.clear();
    _currentPage = 1;
    _lastPage = 1;
    _total = 0;
    _hasNextPage = false;
    _isLoadingMore = false;
    _state = ResultState.dll;
    _message = '';
    _teknisiId = null;
    notifyListeners();
  }
}
