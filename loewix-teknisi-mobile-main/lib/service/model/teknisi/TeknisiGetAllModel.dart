// To parse this JSON data, do
//
//     final teknisiGetAllResponse = teknisiGetAllResponseFromJson(jsonString);

import 'dart:convert';

TeknisiGetAllResponse teknisiGetAllResponseFromJson(String str) => TeknisiGetAllResponse.fromJson(json.decode(str));

String teknisiGetAllResponseToJson(TeknisiGetAllResponse data) => json.encode(data.toJson());

class TeknisiGetAllResponse {
    List<DataTeknisi> data;

    TeknisiGetAllResponse({
        required this.data,
    });

    factory TeknisiGetAllResponse.fromJson(Map<String, dynamic> json) => TeknisiGetAllResponse(
        data: List<DataTeknisi>.from(json["data"].map((x) => DataTeknisi.fromJson(x))),
    );

    Map<String, dynamic> toJson() => {
        "data": List<dynamic>.from(data.map((x) => x.toJson())),
    };
}

class DataTeknisi {
    int id;
    String nik;
    String nama;
    String telp;
    String ktp;
    DateTime createdAt;
    DateTime updatedAt;

    DataTeknisi({
        required this.id,
        required this.nik,
        required this.nama,
        required this.telp,
        required this.ktp,
        required this.createdAt,
        required this.updatedAt,
    });

    factory DataTeknisi.fromJson(Map<String, dynamic> json) => DataTeknisi(
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
