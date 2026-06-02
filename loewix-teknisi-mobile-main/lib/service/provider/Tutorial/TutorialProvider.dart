import 'dart:convert';
import 'package:flutter/foundation.dart';
import 'package:http/http.dart' as http;
import '../../api/ApiLink.dart';
import '../../model/tutorial/TutorialResponse.dart';

enum TutorialState { initial, loading, loaded, error }

class TutorialProvider extends ChangeNotifier {
  TutorialState _listState = TutorialState.initial;
  TutorialState _detailState = TutorialState.initial;

  List<Tutorial> _tutorials = [];
  Tutorial? _selectedTutorial;

  String _listError = '';
  String _detailError = '';

  // Getters
  TutorialState get listState => _listState;
  TutorialState get detailState => _detailState;
  List<Tutorial> get tutorials => _tutorials;
  Tutorial? get selectedTutorial => _selectedTutorial;
  String get listError => _listError;
  String get detailError => _detailError;

  Future<void> getAllTutorials() async {
    _listState = TutorialState.loading;
    _listError = '';
    notifyListeners();

    try {
      final url = '${Api.Url}/tutor';

      final response = await http.get(
        Uri.parse(url),
        headers: {'Accept': 'application/json'},
      ).timeout(const Duration(seconds: 30));

      if (response.statusCode == 200) {
        final jsonData = json.decode(response.body);
        final tutorialResponse = TutorialListResponse.fromJson(jsonData);
        _tutorials = tutorialResponse.data;
        _listState = TutorialState.loaded;
      } else {
        _listError = 'Gagal mengambil data tutorial';
        _listState = TutorialState.error;
      }
    } catch (e) {
      _listError = 'Terjadi kesalahan: ${e.toString()}';
      _listState = TutorialState.error;
    }

    notifyListeners();
  }

  Future<void> getTutorialDetail(int id) async {
    _detailState = TutorialState.loading;
    _detailError = '';
    notifyListeners();

    try {
      final url = '${Api.Url}/tutor/$id';

      final response = await http.get(
        Uri.parse(url),
        headers: {'Accept': 'application/json'},
      ).timeout(const Duration(seconds: 30));

      if (response.statusCode == 200) {
        final jsonData = json.decode(response.body);
        final detailResponse = TutorialDetailResponse.fromJson(jsonData);
        _selectedTutorial = detailResponse.data;
        _detailState = TutorialState.loaded;
      } else if (response.statusCode == 404) {
        _detailError = 'Tutorial tidak ditemukan';
        _detailState = TutorialState.error;
      } else {
        _detailError = 'Gagal mengambil detail tutorial';
        _detailState = TutorialState.error;
      }
    } catch (e) {
      _detailError = 'Terjadi kesalahan: ${e.toString()}';
      _detailState = TutorialState.error;
    }

    notifyListeners();
  }

  void clearDetail() {
    _selectedTutorial = null;
    _detailState = TutorialState.initial;
    _detailError = '';
    notifyListeners();
  }

  void reset() {
    _listState = TutorialState.initial;
    _detailState = TutorialState.initial;
    _tutorials = [];
    _selectedTutorial = null;
    _listError = '';
    _detailError = '';
    notifyListeners();
  }
}
