import 'dart:convert';

import '../../../service/model/pencapaian/PencapaianChartGet.dart';
import '../../../service/model/teknisi/TeknisiGetAllModel.dart';
import 'package:http/http.dart' as http;

import 'ApiLink.dart';

class ApiTeknisi {
  //static const _baseUrl = 'http://10.0.2.2/loewix/absen/public/api';
  //static const _baseUrl = 'https://loewix.com/jadwal-3/api/public/api';
  final _baseUrl = Api.Url;

  Future<TeknisiGetAllResponse> getAllTeknisi() async {
    Map<String, String> requestHeadersToken = {
      'Content-type': 'application/json',
      'Accept': 'application/json',
      //'Authorization': 'Bearer $token',
    };

    final response = await http.get(
      Uri.parse("$_baseUrl/teknisi"),
      headers: requestHeadersToken,
    );

    if (response.statusCode >= 200 && response.statusCode < 300) {
      return TeknisiGetAllResponse.fromJson(json.decode(response.body));
    } else {
      print(response.body.toString());

      String error = response.body.toString();

      Map<String, dynamic> jsonResponse = jsonDecode(error);

      String message = jsonResponse['message'];
      throw Exception(message);
    }
  }

  Future<ChartResponse> getPencapaian(String id) async {
    Map<String, String> requestHeadersToken = {
      'Content-type': 'application/json',
      'Accept': 'application/json',
      //'Authorization': 'Bearer $token',
    };

    final response = await http.get(
      Uri.parse("$_baseUrl/pencapaian/$id"),
      headers: requestHeadersToken,
    );

    if (response.statusCode >= 200 && response.statusCode < 300) {
      return ChartResponse.fromJson(json.decode(response.body));
    } else {
      print(response.body.toString());

      String error = response.body.toString();

      Map<String, dynamic> jsonResponse = jsonDecode(error);

      String message = jsonResponse['message'];
      throw Exception(message);
    }
  }
}
