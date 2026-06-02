import 'dart:convert';
import 'dart:typed_data';

import '../../../service/api/ApiLink.dart';
import '../../../service/model/message/MessageModel.dart';
import '../../../service/model/pinjam/PinjamSendModel.dart';
import 'package:http/http.dart' as http;

import '../model/pinjam/DetailPinjamGetModel.dart';
import '../model/pinjam/PinjamGetModel.dart';

class ApiPinjam {

  final _baseUrl = Api.Url;

  Future<PinjamSendResponse> sendPinjam({
    required String teknisiId,
    required String barangId,
    required int qty,
    required String tglPinjam,
    required String code,
  }) async {
    // Data yang akan dikirim sebagai body dari POST request
    Map<String, dynamic> data = {
      'teknisi_id': teknisiId,
      'barang_id': barangId,
      'qty': qty.toString(),
      'tgl_pinjam' : tglPinjam,
      'code' : code,
      'status' : 'diajukan'
    };

    // Headers untuk request
    Map<String, String> requestHeaders = {
      'Content-Type': 'application/json',
      'Accept': 'application/json',
      // Jika perlu otentikasi token, tambahkan di sini:
      // 'Authorization': 'Bearer $token',
    };

    // Lakukan POST request ke endpoint API untuk mengirim barang
    final response = await http.post(
      Uri.parse("$_baseUrl/peminjaman"), // Ganti dengan endpoint yang sesuai
      headers: requestHeaders,
      body: json.encode(data), // Konversi data ke format JSON
    );

    if (response.statusCode == 201) {
      // Jika berhasil, parse JSON dan return hasilnya
      print(json.decode(response.body));
      return PinjamSendResponse.fromJson(json.decode(response.body));
    } else {
      // Jika gagal, lempar exception dengan pesan error dari response
      final errorData = jsonDecode(response.body) as Map<String, dynamic>;
      throw Exception(errorData['message']);
    }
  }

  Future<PinjamGetResponse> getActivePinjam(String id) async{

    Map<String, String> requestHeadersToken = {
      'Content-type': 'application/json',
      'Accept': 'application/json'
    };

    final response = await http.get(
      Uri.parse("$_baseUrl/peminjaman/teknisi/$id"),
      headers: requestHeadersToken,
    );

    print('response API: ${response.body}');
    print('response STATUS CODE: ${response.statusCode}');

    if(response.statusCode >= 200 && response.statusCode < 300){
      print('respon get');
      return PinjamGetResponse.fromJson(json.decode(response.body));
    }else{
      print('api error${response.body}');
      throw Exception(json.decode(response.body));
    }
  }

  Future<DetailPinjamGetResponse> getDetailPinjam(String code) async{

    Map<String, String> requestHeadersToken = {
      'Content-type': 'application/json',
      'Accept': 'application/json'
    };

    final response = await http.get(
      Uri.parse("$_baseUrl/peminjaman/$code"),
      headers: requestHeadersToken,
    );

    print('response API: ${response.body}');
    print('response STATUS CODE: ${response.statusCode}');

    if(response.statusCode >= 200 && response.statusCode < 300){
      print('respon get');
      return DetailPinjamGetResponse.fromJson(json.decode(response.body));
    }else{
      print('api error${response.body}');
      throw Exception(json.decode(response.body));
    }
  }

  Future<MessageResponse> deletePinjam(String code) async{

    Map<String, String> requestHeadersToken = {
      'Content-type': 'application/json',
      'Accept': 'application/json'
    };

    final response = await http.delete(
      Uri.parse("$_baseUrl/peminjaman/$code"),
      headers: requestHeadersToken,
    );

    print('response API: ${response.body}');
    print('response STATUS CODE: ${response.statusCode}');

    if(response.statusCode >= 200 && response.statusCode < 300){
      print('respon get');
      return MessageResponse.fromJson(json.decode(response.body));
    }else{
      print('api error${response.body}');
      throw Exception(json.decode(response.body));
    }
  }

  Future<MessageResponse> updateStatus(String status,String code) async{

    Map<String, String> requestHeadersToken = {
      //'Content-type': 'application/json',
      'Accept': 'application/json'
    };

    Map<String, String> data = {
      'status' : status
    };

    final response = await http.put(
        Uri.parse("$_baseUrl/peminjaman/status/$code"),
        headers: requestHeadersToken,
        body: data
    );

    print('response API: ${response.body}');
    print('response STATUS CODE: ${response.statusCode}');

    if(response.statusCode >= 200 && response.statusCode < 300){
      print('respon get');
      return MessageResponse.fromJson(json.decode(response.body));
    }else{
      print('api error${response.body}');
      throw Exception(json.decode(response.body));
    }
  }

  Future<MessageResponse> kembalikanBarang(
      List<int> imagesBytes, String keterangan, String id) async {

    String url = '$_baseUrl/peminjaman/kembalikan/$id';

    final uri = Uri.parse(url);
    var request = http.MultipartRequest('POST', uri);

    final multiPartFile = http.MultipartFile.fromBytes(
      "image_bukti",
      imagesBytes,
      filename: 'images_1.jpg',
    );
    request.files.add(multiPartFile);

    final Map<String, String> fields = {
      "keterangan": keterangan,
      "status" : "pengembalian"
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
      final MessageResponse uploadResponse =
      MessageResponse.fromJson(
        json.decode(responseData),
      );
      return uploadResponse;
    } else {
      print('Error: $responseData');
      throw Exception("Upload file error");
    }
  }
}