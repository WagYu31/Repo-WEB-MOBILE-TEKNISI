import 'dart:convert';
import 'dart:io';

import 'package:http/http.dart' as http;

import '../../constants/app_constants.dart';
import '../model/pencapaian/PencapaianGet.dart';
import '../model/task/HistoryPaginatedResponse.dart';
import '../model/task/TaskAllResponse.dart';
import 'ApiLink.dart';

class ApiTask {
  final String _baseUrl = Api.Url;

  static const Map<String, String> _defaultHeaders = {
    'Content-type': 'application/json',
    'Accept': 'application/json',
  };

  Future<T> _handleRequest<T>(
    Future<http.Response> Function() request,
    T Function(Map<String, dynamic>) parser,
  ) async {
    try {
      final response = await request().timeout(AppConstants.apiTimeout);

      if (response.statusCode >= 200 && response.statusCode < 300) {
        return parser(json.decode(response.body));
      }

      final errorJson = jsonDecode(response.body) as Map<String, dynamic>;
      throw Exception(errorJson['message'] ?? 'Terjadi kesalahan');
    } on SocketException {
      throw Exception('Tidak dapat terhubung ke server');
    } on FormatException {
      throw Exception('Format respons tidak valid');
    }
  }

  Future<TaskAllResponse> getAllTask() async {
    return _handleRequest(
      () => http.get(Uri.parse('$_baseUrl/teknisitask'), headers: _defaultHeaders),
      (json) => TaskAllResponse.fromJson(json),
    );
  }

  Future<HistoryPaginatedResponse> getHistoryTask(String teknisiId, {int page = 1}) async {
    return _handleRequest(
      () => http.get(
        Uri.parse('$_baseUrl/teknisihistory/$teknisiId?page=$page'),
        headers: _defaultHeaders,
      ),
      (json) => HistoryPaginatedResponse.fromJson(json),
    );
  }

  Future<PencapaianResponse> getPendapatan(String month, String id) async {
    return _handleRequest(
      () => http.get(
        Uri.parse('$_baseUrl/pendapatan/laporan/$month/$id'),
        headers: _defaultHeaders,
      ),
      (json) => PencapaianResponse.fromJson(json),
    );
  }

  Future<TaskAllResponse> getDetailTask(String id) async {
    return _handleRequest(
      () => http.get(Uri.parse('$_baseUrl/teknisitask/$id'), headers: _defaultHeaders),
      (json) => TaskAllResponse.fromJson(json),
    );
  }
}
