// To parse this JSON data, do
//
//     final barangGetResponse = barangGetResponseFromJson(jsonString);

import 'dart:convert';

BarangGetResponse barangGetResponseFromJson(String str) => BarangGetResponse.fromJson(json.decode(str));

String barangGetResponseToJson(BarangGetResponse data) => json.encode(data.toJson());

class BarangGetResponse {
  List<DataBarang> data;

  BarangGetResponse({
    required this.data,
  });

  factory BarangGetResponse.fromJson(Map<String, dynamic> json) => BarangGetResponse(
    data: List<DataBarang>.from(json["data"].map((x) => DataBarang.fromJson(x))),
  );

  Map<String, dynamic> toJson() => {
    "data": List<dynamic>.from(data.map((x) => x.toJson())),
  };
}

class DataBarang {
  int id;
  String namaBarang;
  String deskripsi;
  int stok;
  DateTime createdAt;
  DateTime updatedAt;
  dynamic deletedAt;

  DataBarang({
    required this.id,
    required this.namaBarang,
    required this.deskripsi,
    required this.stok,
    required this.createdAt,
    required this.updatedAt,
    required this.deletedAt,
  });

  factory DataBarang.fromJson(Map<String, dynamic> json) => DataBarang(
    id: json["id"],
    namaBarang: json["nama_barang"],
    deskripsi: json["deskripsi"],
    stok: json["stok"],
    createdAt: DateTime.parse(json["created_at"]),
    updatedAt: DateTime.parse(json["updated_at"]),
    deletedAt: json["deleted_at"],
  );

  Map<String, dynamic> toJson() => {
    "id": id,
    "nama_barang": namaBarang,
    "deskripsi": deskripsi,
    "stok": stok,
    "created_at": createdAt.toIso8601String(),
    "updated_at": updatedAt.toIso8601String(),
    "deleted_at": deletedAt,
  };
}
