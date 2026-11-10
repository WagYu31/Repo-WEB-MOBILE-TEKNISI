import 'dart:convert';
import 'dart:io';

import 'package:http/http.dart' as http;

import '../../constants/app_constants.dart';
import '../../utils/demo_teknisi_data.dart';
import '../model/pencapaian/PencapaianGet.dart';
import '../model/task/HistoryPaginatedResponse.dart';
import '../model/task/TaskAllResponse.dart';
import 'ApiLink.dart';

class ApiTask {
  final String _baseUrl = Api.Url;

  static const Map<String, String> _defaultHeaders = {
    'Content-type': 'application/json',
    'Accept': 'application/json',
    'User-Agent': 'Mozilla/5.0 (Linux; Android 13) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Mobile Safari/537.36',
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

  Future<HistoryPaginatedResponse> getHistoryTask(String teknisiId, {int page = 1, String filter = 'semua'}) async {
    try {
      final filterQuery = filter != 'semua' ? '&filter=$filter' : '';
      final res = await http.get(
        Uri.parse('$_baseUrl/teknisihistory/$teknisiId?page=$page$filterQuery'),
        headers: _defaultHeaders,
      ).timeout(const Duration(seconds: 2));
      if (res.statusCode >= 200 && res.statusCode < 300) {
        return HistoryPaginatedResponse.fromJson(json.decode(res.body));
      }
    } catch (_) {}
    return DemoTeknisiData.history;
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
    try {
      final res = await http.get(
        Uri.parse('$_baseUrl/teknisitask/$id'),
        headers: _defaultHeaders,
      ).timeout(const Duration(seconds: 10));
      if (res.statusCode >= 200 && res.statusCode < 300) {
        final parsed = TaskAllResponse.fromJson(json.decode(res.body));
        if (parsed.data.isNotEmpty) return parsed;
      }
    } catch (_) {}
    return DemoTeknisiData.tasks;
  }

  Future<Map<String, dynamic>> requestInvoice(String kode) async {
    return _handleRequest(
      () => http.post(
        Uri.parse('$_baseUrl/task/request-invoice/$kode'),
        headers: _defaultHeaders,
      ),
      (json) => json,
    );
  }
}
