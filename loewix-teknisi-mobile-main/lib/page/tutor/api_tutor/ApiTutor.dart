import 'dart:convert';

import 'package:http/http.dart' as http;

import '../model/DetailTutorResponse.dart';
import '../model/TutorResponse.dart';


class ApiTutor {
  //static const _baseUrl = 'http://10.0.2.2/loewix/absen/public/api';
  //final _baseUrl = 'http://10.0.2.2/loewix/tutor-teknisi/public/api';
  // static const _baseUrl = 'https://loewix.com/jadwal-3/api/public/api';
  static const _baseUrl = 'https://grav-tech.com/jadwal-3/api/public/api';

  Future<TutorResponses> getAllTutor() async{

    Map<String, String> requestHeadersToken = {
      'Accept': 'application/json',
    };

    final responseTutor = await http.get(
        Uri.parse("$_baseUrl/tutor"),
        headers: requestHeadersToken
    );

    if(responseTutor.statusCode == 200){
      return TutorResponses.fromJson(json.decode(responseTutor.body));
    }else{
      throw Exception('error get${responseTutor.body}');
    }

  }

  Future<DetailTutorResponses> getDetailTutor(int id) async{

    Map<String, String> requestHeadersToken = {
      'Accept': 'application/json',
    };

    final responseDetailTutor = await http.get(
        Uri.parse("$_baseUrl/tutor/$id"),
        headers: requestHeadersToken
    );

    if(responseDetailTutor.statusCode == 200){
      return DetailTutorResponses.fromJson(json.decode(responseDetailTutor.body));
    }else{
      throw Exception('error get${responseDetailTutor.body}');
    }

  }
}
