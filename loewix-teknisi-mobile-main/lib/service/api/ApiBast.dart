import 'dart:convert';
import 'package:http/http.dart' as http;
import 'package:image_picker/image_picker.dart';

class BastPointDraft {
  XFile? newFile;
  String? existingFilename;
  String? existingUrl;
  String keterangan;

  BastPointDraft({
    this.newFile,
    this.existingFilename,
    this.existingUrl,
    this.keterangan = '',
  });
}

class ApiBast {
  static const String _endpoint = 'https://jadwal.id-giti.com/staff/api_bast.php';

  Future<Map<String, dynamic>> getDetail(String kode, {int? customerId}) async {
    final uri = Uri.parse(_endpoint).replace(queryParameters: {
      'action': 'get_detail',
      'kode': kode,
      if (customerId != null && customerId > 0) 'customer_id': '$customerId',
    });
    final res = await http.get(uri).timeout(const Duration(seconds: 15));
    if (res.statusCode == 200) {
      return jsonDecode(res.body) as Map<String, dynamic>;
    }
    throw Exception('Gagal memuat data BAST (${res.statusCode})');
  }

  Future<List<Map<String, dynamic>>> getPendingForTeknisi(int teknisiId) async {
    final uri = Uri.parse(_endpoint).replace(queryParameters: {
      'action': 'pending_for_teknisi',
      'teknisi_id': '$teknisiId',
    });
    final res = await http.get(uri).timeout(const Duration(seconds: 12));
    if (res.statusCode == 200) {
      final decoded = jsonDecode(res.body) as Map<String, dynamic>;
      final raw = decoded['pending'] as List? ?? const [];
      return raw.map((e) => Map<String, dynamic>.from(e as Map)).toList();
    }
    return const [];
  }

  Future<Map<String, dynamic>> submitBast({
    required String kode,
    required int kegiatanId,
    required int customerId,
    required int teknisiId,
    XFile? newDisplayPhoto,
    String? existingDisplayFilename,
    required String ketDisplayUtama,
    required List<BastPointDraft> titikList,
  }) async {
    final req = http.MultipartRequest('POST', Uri.parse(_endpoint));
    req.fields['action'] = 'submit_mobile';
    req.fields['kode'] = kode;
    req.fields['kegiatan_id'] = '$kegiatanId';
    req.fields['customer_id'] = '$customerId';
    req.fields['teknisi_id'] = '$teknisiId';
    req.fields['ket_display_utama'] = ketDisplayUtama.trim();
    if (existingDisplayFilename != null && existingDisplayFilename.isNotEmpty) {
      req.fields['existing_foto_display'] = existingDisplayFilename;
    }
    if (newDisplayPhoto != null) {
      req.files.add(
        await http.MultipartFile.fromPath('foto_display_utama', newDisplayPhoto.path),
      );
    }

    req.fields['titik_count'] = '${titikList.length}';
    for (int i = 0; i < titikList.length; i++) {
      final pt = titikList[i];
      req.fields['ket_titik_$i'] = pt.keterangan.trim();
      if (pt.existingFilename != null && pt.existingFilename!.isNotEmpty) {
        req.fields['existing_foto_titik_$i'] = pt.existingFilename!;
      }
      if (pt.newFile != null) {
        req.files.add(
          await http.MultipartFile.fromPath('foto_titik_$i', pt.newFile!.path),
        );
      }
    }

    final streamed = await req.send().timeout(const Duration(seconds: 90));
    final body = await streamed.stream.bytesToString();
    final decoded = jsonDecode(body) as Map<String, dynamic>;
    if (streamed.statusCode == 200 && decoded['success'] == true) {
      return decoded;
    }
    throw Exception(decoded['message']?.toString() ?? 'Gagal menyimpan BAST');
  }
}

