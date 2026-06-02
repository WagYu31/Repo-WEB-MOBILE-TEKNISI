import 'dart:convert';

import '../../../service/api/ApiLink.dart';
import 'package:http/http.dart' as http;

import '../model/barang/BarangResponse.dart';

class ApiBarang {

  final _baseUrl = Api.Url;

  Future<BarangGetResponse> getBarang() async {
    Map<String, String> requestHeadersToken = {
      'Content-type': 'application/json',
      'Accept': 'application/json',
      //'Authorization': 'Bearer $token',
    };

    final response = await http.get(
      Uri.parse("$_baseUrl/barang"),
      headers: requestHeadersToken,
    );

    print('response API: ${response.body}');
    print('response STATUS CODE: ${response.statusCode}');

    if (response.statusCode >= 200 && response.statusCode < 300) {
      print('respon get');
      return BarangGetResponse.fromJson(json.decode(response.body));
    } else {
      print('api error${response.body}');
      throw Exception(json.decode(response.body));
    }
  }
}