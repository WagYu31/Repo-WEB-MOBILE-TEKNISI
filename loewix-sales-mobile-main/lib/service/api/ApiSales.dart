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
    List<String> base64Images = const [],
  }) async {
    final Map<String, dynamic> body = {
      'kegiatan_id': kegiatanId,
      'sales_id': salesId,
      'latitude': lat,
      'longitude': lon,
      'catatan_visit': catatanVisit,
      'is_mock': isMock ? 1 : 0,
    };
    
    final listFields = ['image_satu', 'image_dua', 'image_tiga', 'image_empat', 'image_lima'];
    for (int i = 0; i < base64Images.length && i < listFields.length; i++) {
      body[listFields[i]] = base64Images[i];
    }

    // Use client with extended timeout for large video uploads
    final client = http.Client();
    try {
      final request = http.Request('POST', Uri.parse('$_base/api_sales_clockout.php'));
      request.headers['Content-Type'] = 'application/json';
      request.body = jsonEncode(body);
      
      final streamedRes = await client.send(request).timeout(
        const Duration(minutes: 5),
      );
      final resBody = await streamedRes.stream.bytesToString();
      final data = jsonDecode(resBody);
      
      if (streamedRes.statusCode == 200 && data['status'] == 'success') {
        return data;
      }
      if (streamedRes.statusCode == 403 && data['code'] == 'FAKE_GPS_DETECTED') {
        throw Exception(
            '⚠️ Fake GPS Terdeteksi!\n\nHarap matikan aplikasi fake GPS dan gunakan lokasi asli perangkat Anda.');
      }
      throw Exception(data['message'] ?? 'Clock Out gagal');
    } finally {
      client.close();
    }
  }

  Future<Map<String, dynamic>> updateReport({
    required int kegiatanId,
    required int salesId,
    required String catatanVisit,
    required List<String> images,
  }) async {
    final Map<String, dynamic> body = {
      'kegiatan_id': kegiatanId,
      'sales_id': salesId,
      'catatan_visit': catatanVisit,
    };

    final listFields = ['image_satu', 'image_dua', 'image_tiga', 'image_empat', 'image_lima'];
    for (int i = 0; i < 5; i++) {
      if (i < images.length) {
        body[listFields[i]] = images[i];
      } else {
        body[listFields[i]] = '';
      }
    }

    final res = await http.post(
      Uri.parse('$_base/api_sales_update_report.php'),
      headers: {'Content-Type': 'application/json'},
      body: jsonEncode(body),
    );
    final data = jsonDecode(res.body);
    if (res.statusCode == 200 && data['status'] == 'success') {
      return data;
    }
    throw Exception(data['message'] ?? 'Gagal memperbarui laporan');
  }
}
