// To parse this JSON data, do
//
//     final profileTeknisiResponse = profileTeknisiResponseFromJson(jsonString);

import 'dart:convert';

ProfileTeknisiResponse profileTeknisiResponseFromJson(String str) => ProfileTeknisiResponse.fromJson(json.decode(str));

String profileTeknisiResponseToJson(ProfileTeknisiResponse data) => json.encode(data.toJson());

class ProfileTeknisiResponse {
  Data data;

  ProfileTeknisiResponse({
    required this.data,
  });

  factory ProfileTeknisiResponse.fromJson(Map<String, dynamic> json) => ProfileTeknisiResponse(
    data: Data.fromJson(json["data"]),
  );

  Map<String, dynamic> toJson() => {
    "data": data.toJson(),
  };
}

class Data {
  int id;
  dynamic nik;
  dynamic nama;
  dynamic telp;
  dynamic ktp;
  DateTime createdAt;
  DateTime updatedAt;

  Data({
    required this.id,
    required this.nik,
    required this.nama,
    required this.telp,
    required this.ktp,
    required this.createdAt,
    required this.updatedAt,
  });

  factory Data.fromJson(Map<String, dynamic> json) => Data(
    id: json["id"],
    nik: json["nik"],
    nama: json["nama"],
    telp: json["telp"],
    ktp: json["ktp"],
    createdAt: DateTime.parse(json["created_at"]),
    updatedAt: DateTime.parse(json["updated_at"]),
  );

  Map<String, dynamic> toJson() => {
    "id": id,
    "nik": nik,
    "nama": nama,
    "telp": telp,
    "ktp": ktp,
    "created_at": createdAt.toIso8601String(),
    "updated_at": updatedAt.toIso8601String(),
  };
}
