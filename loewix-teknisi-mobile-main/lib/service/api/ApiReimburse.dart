import 'dart:convert';
import 'dart:io';
import 'package:http/http.dart' as http;
import 'package:path/path.dart';
import 'package:teknisi_loewix/service/api/ApiLink.dart';
import 'package:teknisi_loewix/service/model/reimburse/ReimburseModel.dart';

class ApiReimburse {
  final _baseUrl = Api.Url;

  Future<ReimburseResponse> getReimburse(int teknisiId) async {
    Map<String, String> requestHeadersToken = {
      'Content-type': 'application/json',
      'Accept': 'application/json',
      // 'Authorization': 'Bearer $token',
    };

    final response = await http.get(
      Uri.parse("$_baseUrl/reimburse/$teknisiId"),
      headers: requestHeadersToken,
    );

    print('response API: ${response.body}');
    print('response STATUS CODE: ${response.statusCode}');

    if (response.statusCode >= 200 && response.statusCode < 300) {
      return ReimburseResponse.fromJson(json.decode(response.body));
    } else {
      throw Exception(json.decode(response.body));
    }
  }

  Future<void> createReimburse({
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
    final uri = Uri.parse("$_baseUrl/reimburse");
    final request = http.MultipartRequest('POST', uri);

    request.fields['kegiatan_id'] = kegiatanId;
    request.fields['teknisi_id'] = teknisiId;
    request.fields['nominal'] = nominal.toString();
    request.fields['tanggal'] = tanggal;
    if (keterangan != null) request.fields['keterangan'] = keterangan;

    request.files.add(await http.MultipartFile.fromPath('file_1', file1.path));
    if (file2 != null) request.files.add(await http.MultipartFile.fromPath('file_2', file2.path));
    if (file3 != null) request.files.add(await http.MultipartFile.fromPath('file_3', file3.path));
    if (file4 != null) request.files.add(await http.MultipartFile.fromPath('file_4', file4.path));
    if (file5 != null) request.files.add(await http.MultipartFile.fromPath('file_5', file5.path));

    final streamedResponse = await request.send();
    final responseBody = await streamedResponse.stream.bytesToString();

    print('response API: $responseBody');
    print('response STATUS CODE: ${streamedResponse.statusCode}');

    if (streamedResponse.statusCode >= 200 && streamedResponse.statusCode < 300) {
      print('✅ Reimburse berhasil dikirim');
    } else {
      print('❌ Gagal kirim reimburse');
      throw Exception(json.decode(responseBody));
    }
  }

  Future<void> deleteReimburse(int id) async {
  final uri = Uri.parse("$_baseUrl/reimburse/$id"); // Sesuaikan dengan route Laravel kamu
  final headers = {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
    // 'Authorization': 'Bearer $token', // Tambahkan jika pakai token
  };

  final response = await http.delete(uri, headers: headers);

  print('response API: ${response.body}');
  print('response STATUS CODE: ${response.statusCode}');

  if (response.statusCode == 200) {
    print('✅ Reimburse berhasil dihapus');
  } else {
    final error = json.decode(response.body);
    print('❌ Gagal hapus reimburse: ${error['message']}');
    throw Exception(error['message']);
  }
}

}