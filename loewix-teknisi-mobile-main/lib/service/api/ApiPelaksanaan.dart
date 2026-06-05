import 'dart:convert';
import 'dart:typed_data';

import 'package:teknisi_loewix/service/model/invoice/DetailInvoiceModel.dart';

import '../../../service/model/pelaksanaan/PelaksanaanSend.dart';
import 'package:http/http.dart' as http;

import '../model/invoice/InvoiceModel.dart';
import 'ApiLink.dart';

class ApiPelaksanaan {
  //static const _baseUrl = 'http://10.0.2.2/loewix/absen/public/api';
  final _baseUrl = Api.Url;

  Future<PelaksanaanSendResponse> sendPelaksanaan(
      String kegiatanId,
      String teknisiId,
      String lat,
      String lon, {
      double? accuracy,
      bool? isMock,
    }) async {
    Map<String, String> requestHeadersToken = {
      //'Content-type': 'application/json',
      'Accept': 'application/json',
      //'Authorization': 'Bearer $token',
    };

    Map<String, String> data = {
      'kegiatan_id': kegiatanId,
      'teknisi_id': teknisiId,
      'latitude': lat,
      'longitude': lon,
    };

    // Tambahkan accuracy dan is_mock jika tersedia
    if (accuracy != null) {
      data['accuracy'] = accuracy.toString();
    }
    if (isMock != null) {
      data['is_mock'] = isMock ? '1' : '0';
    }

    print(jsonEncode(data));

    final response = await http.post(Uri.parse("$_baseUrl/pelaksanaan"),
        headers: requestHeadersToken, body: data);

    

    if (response.statusCode >= 200 && response.statusCode < 300) {
      return PelaksanaanSendResponse.fromJson(json.decode(response.body));
    } else {
      print(response.body.toString());

      String error = response.body.toString();

      try {
        Map<String, dynamic> jsonResponse = jsonDecode(error);
        String message = jsonResponse['message'];
        String? code = jsonResponse['code'];

        // Cek jika status 403 dan code adalah FAKE_GPS_DETECTED
        if (response.statusCode == 403 && code == 'FAKE_GPS_DETECTED') {
          throw Exception('⚠️ Aksi Tidak Diizinkan!\n\nFake GPS Terdeteksi:\n$message\n\nHarap matikan aplikasi fake GPS dan gunakan lokasi asli perangkat Anda.');
        }

        // Cek jika status 403 (radius/lokasi invalid)
        if (response.statusCode == 403) {
          throw Exception('⚠️ Aksi Tidak Diizinkan!\n\n$message');
        }

        throw Exception(message);
      } catch (e) {
        // Jika error parsing adalah Exception yang sudah kita throw, lempar ulang
        if (e is Exception) {
          rethrow;
        }
        // Jika gagal parse JSON, throw error response langsung
        throw Exception('Gagal memulai tugas: ${response.statusCode}');
      }
    }
  }

  Future<PelaksanaanSendResponse> sendClockOut(
      String kegiatanId,
      String teknisiId,
      String lat,
      String lon, {
      double? accuracy,
      bool? isMock,
    }) async {
    Map<String, String> requestHeadersToken = {
      //'Content-type': 'application/json',
      'Accept': 'application/json',
      //'Authorization': 'Bearer $token',
    };

    Map<String, String> data = {
      'kegiatan_id': kegiatanId,
      'teknisi_id': teknisiId,
      'latitude_s': lat,
      'longitude_s': lon,
    };

    // Tambahkan accuracy dan is_mock jika tersedia
    if (accuracy != null) {
      data['accuracy'] = accuracy.toString();
    }
    if (isMock != null) {
      data['is_mock'] = isMock ? '1' : '0';
    }

    final response = await http.post(Uri.parse("$_baseUrl/clockout"),
        headers: requestHeadersToken, body: data);

    if (response.statusCode >= 200 && response.statusCode < 300) {
      return PelaksanaanSendResponse.fromJson(json.decode(response.body));
    } else {
      print(response.body.toString());

      String error = response.body.toString();

      try {
        Map<String, dynamic> jsonResponse = jsonDecode(error);
        String message = jsonResponse['message'];
        String? code = jsonResponse['code'];

        // Cek jika status 403 dan code adalah FAKE_GPS_DETECTED
        if (response.statusCode == 403 && code == 'FAKE_GPS_DETECTED') {
          throw Exception('⚠️ Aksi Tidak Diizinkan!\n\nFake GPS Terdeteksi:\n$message\n\nHarap matikan aplikasi fake GPS dan gunakan lokasi asli perangkat Anda.');
        }

        // Cek jika status 403 (radius/lokasi invalid)
        if (response.statusCode == 403) {
          throw Exception('⚠️ Aksi Tidak Diizinkan!\n\n$message');
        }

        throw Exception(message);
      } catch (e) {
        // Jika error parsing adalah Exception yang sudah kita throw, lempar ulang
        if (e is Exception) {
          rethrow;
        }
        // Jika gagal parse JSON, throw error response langsung
        throw Exception('Gagal menyelesaikan tugas: ${response.statusCode}');
      }
    }
  }

  Future<PelaksanaanSendResponse> updatePelaksanaan(
    List<List<int>> imagesBytes,
    String kegiatanId,
    String teknisiId,
    String? permasalahan,
    String? solusi,
    String? keterangan,
    // List<int>? recordFileBytes, // ← Tambahkan parameter audio
    String? ketGaransi,
  ) async {
    String url = '$_baseUrl/pelaksanaanselesai';
    final uri = Uri.parse(url);
    var request = http.MultipartRequest('POST', uri);

    // Tambahkan file gambar
    for (int i = 0; i < imagesBytes.length; i++) {
      String namaAngka = angkaKeKata(i + 1);
      final multiPartFile = http.MultipartFile.fromBytes(
        "image_$namaAngka",
        imagesBytes[i],
        filename: 'image_$namaAngka.jpg',
      );
      request.files.add(multiPartFile);
    }

    final Map<String, String> fields = {
      "kegiatan_id": kegiatanId,
      "teknisi_id": teknisiId,
      "permasalahan": permasalahan ?? '',
      "solusi": solusi ?? '',
      "keterangan": keterangan ?? '',
      'keterangan_garansi': ketGaransi ?? '',
    };

    // JANGAN set Content-type manual! MultipartRequest otomatis set dengan boundary
    // Set User-Agent custom karena Cloudflare blokir default Dart User-Agent
    request.headers['user-agent'] = 'Mozilla/5.0 (Linux; Android 13) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Mobile Safari/537.36';
    request.headers['accept'] = 'application/json';

    request.fields.addAll(fields);

    print('DEBUG URL: $url');
    print('DEBUG Headers: ${request.headers}');
    print('DEBUG Fields: ${request.fields}');
    print('DEBUG Files: ${request.files.map((f) => '${f.field}=${f.filename}(${f.length}bytes)').toList()}');

    final http.StreamedResponse streamedResponse = await request.send();
    final int statusCode = streamedResponse.statusCode;
    final Map<String, String> responseHeaders = streamedResponse.headers;
    final Uint8List responseList = await streamedResponse.stream.toBytes();
    final String responseData = utf8.decode(responseList, allowMalformed: true);

    print('pelaksanaanselesai status: $statusCode');
    print('pelaksanaanselesai headers: $responseHeaders');
    print('pelaksanaanselesai response (${responseList.length} bytes): $responseData');

    if (statusCode >= 200 && statusCode <= 300) {
      try {
        final PelaksanaanSendResponse uploadResponse =
            PelaksanaanSendResponse.fromJson(
          json.decode(responseData),
        );
        // Verify the response actually indicates success
        if (uploadResponse.message.toLowerCase().contains('berhasil') ||
            uploadResponse.message.toLowerCase().contains('success')) {
          return uploadResponse;
        }
        // Server returned 200 but message doesn't indicate success
        return uploadResponse;
      } catch (e) {
        // Server returned success status but malformed/empty JSON
        print('WARNING: Server returned $statusCode but response is not valid JSON: $responseData');
        String serverInfo = responseHeaders['server'] ?? 'unknown';
        String cfRay = responseHeaders['cf-ray'] ?? 'none';
        throw Exception('Upload gagal ($statusCode, server=$serverInfo, cf=$cfRay, body=${responseList.length}bytes)\n\n${responseData.length > 200 ? responseData.substring(0, 200) : responseData}');
      }
    } else {
      print('Error: $responseData');
      try {
        Map<String, dynamic> jsonResponse = jsonDecode(responseData);
        String message = jsonResponse['message'] ?? 'Upload gagal';
        throw Exception(message);
      } catch (e) {
        if (e is Exception) rethrow;
        throw Exception("Upload file error: $statusCode");
      }
    }
  }

  Future<PelaksanaanSendResponse> uploadViolators(
    List<int> bytes,
    String kegiatanId,
    String teknisiId,
    String? permasalahan,
    String? solusi,
    String? keterangan,
  ) async {
    String url = '$_baseUrl/pelaksanaanselesai';

    final uri = Uri.parse(url);
    var request = http.MultipartRequest('POST', uri);

    final multiPartFile = http.MultipartFile.fromBytes(
      "image_1",
      bytes,
      filename: 'tyty',
    );

    final Map<String, String> fields = {
      "kegiatan_id": kegiatanId,
      "teknisi_id": teknisiId,
      //"": description,
    };
    final Map<String, String> headers = {
      "Content-type": "multipart/form-data",
      'Accept': 'application/json',
      //'Authorization': 'Bearer $token'
    };

    request.files.add(multiPartFile);
    request.fields.addAll(fields);
    request.headers.addAll(headers);

    final http.StreamedResponse streamedResponse = await request.send();
    final int statusCode = streamedResponse.statusCode;

    final Uint8List responseList = await streamedResponse.stream.toBytes();
    final String responseData = String.fromCharCodes(responseList);

    if (statusCode >= 200 && statusCode <= 300) {
      print('iyyuuuu$responseData');
      final PelaksanaanSendResponse uploadResponse =
          PelaksanaanSendResponse.fromJson(
        json.decode(responseData),
      );
      return uploadResponse;
    } else {
      print('plerrrr$responseData');
      throw Exception("Upload file error");
    }
  }

  String angkaKeKata(int angka) {
    List<String> satuan = [
      "",
      "satu",
      "dua",
      "tiga",
      "empat",
      "lima",
      "enam",
      "tujuh",
      "delapan",
      "sembilan"
    ];
    List<String> belasan = [
      "sepuluh",
      "sebelas",
      "dua belas",
      "tiga belas",
      "empat belas",
      "lima belas",
      "enam belas",
      "tujuh belas",
      "delapan belas",
      "sembilan belas"
    ];
    List<String> puluhan = [
      "",
      "",
      "dua puluh",
      "tiga puluh",
      "empat puluh",
      "lima puluh",
      "enam puluh",
      "tujuh puluh",
      "delapan puluh",
      "sembilan puluh"
    ];

    if (angka < 10) {
      return satuan[angka];
    } else if (angka < 20) {
      return belasan[angka - 10];
    } else if (angka < 100) {
      return puluhan[angka ~/ 10] +
          (angka % 10 != 0 ? " ${satuan[angka % 10]}" : "");
    } else {
      return "angka terlalu besar";
    }
  }

  Future<String> reschedule(String jadwal, String id) async {
    Map<String, String> requestHeadersToken = {
      //'Content-type': 'application/json',
      'Accept': 'application/json',
      //'Authorization': 'Bearer $token',
    };

    Map<String, String> data = {
      'status': jadwal,
    };

    final response = await http.put(Uri.parse("$_baseUrl/reschedule/$id"),
        headers: requestHeadersToken, body: data);

    if (response.statusCode >= 200 && response.statusCode < 300) {
      String error = response.body.toString();

      Map<String, dynamic> jsonResponse = jsonDecode(error);

      String message = jsonResponse['message'];
      return message;
    } else {
      print(response.body.toString());

      String error = response.body.toString();

      Map<String, dynamic> jsonResponse = jsonDecode(error);

      String message = jsonResponse['message'];
      throw Exception('error : $message');
    }
  }

  Future<PelaksanaanSendResponse> doLanjutNanti(
    List<List<int>> imagesBytes,
    String kegiatanId,
    String teknisiId,
    String jadwal,
    String? keterangan,
    String? latS,
    String? lonS,
  ) async {
    String url = '$_baseUrl/lanjutnanti';

    final uri = Uri.parse(url);
    var request = http.MultipartRequest('POST', uri);

    // Add multiple images to the request
    for (int i = 0; i < imagesBytes.length; i++) {
      String namaAngka = angkaKeKata(i + 1);
      final multiPartFile = http.MultipartFile.fromBytes(
        "image_$namaAngka",
        imagesBytes[i],
        filename: 'image_$namaAngka.jpg',
      );
      request.files.add(multiPartFile);
    }

    final Map<String, String> fields = {
      "kegiatan_id": kegiatanId,
      "teknisi_id": teknisiId,
      "jadwal": jadwal,
      "keterangan": keterangan ?? "",
      "latitude_s": latS ?? "",
      "longitude_s": lonS ?? "",
    };
    final Map<String, String> headers = {
      "Content-type": "multipart/form-data",
      'Accept': 'application/json',
    };

    request.fields.addAll(fields);
    request.headers.addAll(headers);

    final http.StreamedResponse streamedResponse = await request.send();
    final int statusCode = streamedResponse.statusCode;

    final Uint8List responseList = await streamedResponse.stream.toBytes();
    final String responseData = String.fromCharCodes(responseList);

    if (statusCode >= 200 && statusCode <= 300) {
      print('Success: $responseData');
      final PelaksanaanSendResponse uploadResponse =
          PelaksanaanSendResponse.fromJson(
        json.decode(responseData),
      );
      return uploadResponse;
    } else {
      print('Error: $responseData');
      try {
        Map<String, dynamic> jsonResponse = jsonDecode(responseData);
        String message = jsonResponse['message'];
        String? code = jsonResponse['code'];

        // Cek jika status 403 dan code adalah FAKE_GPS_DETECTED
        if (statusCode == 403 && code == 'FAKE_GPS_DETECTED') {
          throw Exception('⚠️ Aksi Tidak Diizinkan!\n\nFake GPS Terdeteksi:\n$message\n\nHarap matikan aplikasi fake GPS dan gunakan lokasi asli perangkat Anda.');
        }

        // Cek jika status 403 (radius/lokasi invalid)
        if (statusCode == 403) {
          throw Exception('⚠️ Aksi Tidak Diizinkan!\n\n$message');
        }

        throw Exception(message);
      } catch (e) {
        // Jika error parsing adalah Exception yang sudah kita throw, lempar ulang
        if (e is Exception) {
          rethrow;
        }
        // Jika gagal parse JSON, throw error response langsung
        throw Exception('Gagal menyimpan lanjut nanti: $statusCode');
      }
    }
  }

  static const String baseUrl = Api.Url;

  static Future<void> createItems(List<Item> items) async {

    Object b = json.encode({
      'invoice': items.map((item) => item.toJson()).toList(),
      'kegiatan_id': items.first.kegiatanId,
      // Mengambil kegiatan_id dari item pertama
    });

    final response = await http.post(
      Uri.parse('$baseUrl/make-invoice'),
      headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
      },
      body: json.encode({
        'invoice': items.map((item) => item.toJson()).toList(),
        'kegiatan_id': items.first.kegiatanId,
        // Mengambil kegiatan_id dari item pertama
      }),
    );

    print("HASIL RESPONSE : ${response.body}");
    print("HASIL RESPONSE : ${jsonEncode(b)}");
    print("HASIL Link : ${baseUrl}/make-invoice");

    if (response.statusCode != 201) {
      final errorData = json.decode(response.body);
      throw Exception(errorData['message'] ?? 'Failed to create items');
    }
  }

  Future<DetailInvoiceResponse> getDetailInvoice(String id) async {
    Map<String, String> requestHeadersToken = {
      'Content-type': 'application/json',
      'Accept': 'application/json',
      //'Authorization': 'Bearer $token',
    };

    final response = await http.get(
      Uri.parse("$_baseUrl/detail-invoice/$id"),
      headers: requestHeadersToken,
    );

    if (response.statusCode >= 200 && response.statusCode < 300) {
      return DetailInvoiceResponse.fromJson(json.decode(response.body));
    } else {
      print(response.body.toString());

      String error = response.body.toString();

      Map<String, dynamic> jsonResponse = jsonDecode(error);

      String message = jsonResponse['message'];
      throw Exception(message);
    }
  }
}
