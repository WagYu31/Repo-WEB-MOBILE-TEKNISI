import 'dart:convert';
import 'package:http/http.dart' as http;
import 'ApiLink.dart';
import '../model/SalesModel.dart';

class ApiSales {
  final String _base = Api.Url;

  Future<SalesProfile> login(String username, String password) async {
    final res = await http.post(
      Uri.parse('$_base/api_sales_login.php'),
      body: {'username': username, 'password': password},
    );
    final data = jsonDecode(res.body);
    if (res.statusCode == 200 && data['status'] == 'success') {
      return SalesProfile.fromJson(data['data']);
    }
    throw Exception(data['message'] ?? 'Login gagal');
  }

  Future<List<VisitTask>> getTasks(int salesId, {String filter = 'today'}) async {
    final res = await http.get(
      Uri.parse('$_base/api_sales_task.php?sales_id=$salesId&filter=$filter'),
    );
    final data = jsonDecode(res.body);
    if (res.statusCode == 200 && data['status'] == 'success') {
      return (data['data'] as List)
          .map((e) => VisitTask.fromJson(e))
          .toList();
    }
    throw Exception(data['message'] ?? 'Gagal memuat data kunjungan');
  }

  Future<Map<String, dynamic>> clockIn({
    required int kegiatanId,
    required int salesId,
    required String lat,
    required String lon,
    bool isMock = false,
  }) async {
    final res = await http.post(
      Uri.parse('$_base/api_sales_clockin.php'),
      body: {
        'kegiatan_id': kegiatanId.toString(),
        'sales_id': salesId.toString(),
        'latitude': lat,
        'longitude': lon,
        'is_mock': isMock ? '1' : '0',
      },
    );
    final data = jsonDecode(res.body);
    if ((res.statusCode == 200 || res.statusCode == 201) &&
        data['status'] == 'success') {
      return data;
    }
    // Handle Fake GPS
    if (res.statusCode == 403 && data['code'] == 'FAKE_GPS_DETECTED') {
      throw Exception(
          '⚠️ Fake GPS Terdeteksi!\n\nHarap matikan aplikasi fake GPS dan gunakan lokasi asli perangkat Anda.');
    }
    throw Exception(data['message'] ?? 'Clock In gagal');
  }

  Future<Map<String, dynamic>> clockOut({
    required int kegiatanId,
    required int salesId,
    required String lat,
    required String lon,
    String catatanVisit = '',
    bool isMock = false,
  }) async {
    final res = await http.post(
      Uri.parse('$_base/api_sales_clockout.php'),
      body: {
        'kegiatan_id': kegiatanId.toString(),
        'sales_id': salesId.toString(),
        'latitude': lat,
        'longitude': lon,
        'catatan_visit': catatanVisit,
        'is_mock': isMock ? '1' : '0',
      },
    );
    final data = jsonDecode(res.body);
    if (res.statusCode == 200 && data['status'] == 'success') {
      return data;
    }
    if (res.statusCode == 403 && data['code'] == 'FAKE_GPS_DETECTED') {
      throw Exception(
          '⚠️ Fake GPS Terdeteksi!\n\nHarap matikan aplikasi fake GPS dan gunakan lokasi asli perangkat Anda.');
    }
    throw Exception(data['message'] ?? 'Clock Out gagal');
  }
}
