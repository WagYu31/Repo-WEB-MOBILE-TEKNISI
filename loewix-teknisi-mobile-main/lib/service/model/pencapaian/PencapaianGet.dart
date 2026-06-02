// To parse this JSON data, do
//
//     final pencapaianResponse = pencapaianResponseFromJson(jsonString);

import 'dart:convert';

PencapaianResponse pencapaianResponseFromJson(String str) => PencapaianResponse.fromJson(json.decode(str));

String pencapaianResponseToJson(PencapaianResponse data) => json.encode(data.toJson());

class PencapaianResponse {
  List<DataPencapaian> data;

  PencapaianResponse({
    required this.data,
  });

  factory PencapaianResponse.fromJson(Map<String, dynamic> json) => PencapaianResponse(
    data: List<DataPencapaian>.from(json["data"].map((x) => DataPencapaian.fromJson(x))),
  );

  Map<String, dynamic> toJson() => {
    "data": List<dynamic>.from(data.map((x) => x.toJson())),
  };
}

class DataPencapaian {
  int teknisiId;
  String totalPendapatan;
  Teknisi teknisi;

  DataPencapaian({
    required this.teknisiId,
    required this.totalPendapatan,
    required this.teknisi,
  });

  factory DataPencapaian.fromJson(Map<String, dynamic> json) => DataPencapaian(
    teknisiId: json["teknisi_id"],
    totalPendapatan: json["total_pendapatan"],
    teknisi: Teknisi.fromJson(json["teknisi"]),
  );

  Map<String, dynamic> toJson() => {
    "teknisi_id": teknisiId,
    "total_pendapatan": totalPendapatan,
    "teknisi": teknisi.toJson(),
  };
}

class Teknisi {
  int id;
  String nik;
  String nama;
  String telp;
  String ktp;
  String target;
  DateTime createdAt;
  DateTime updatedAt;

  Teknisi({
    required this.id,
    required this.nik,
    required this.nama,
    required this.telp,
    required this.ktp,
    required this.target,
    required this.createdAt,
    required this.updatedAt,
  });

  factory Teknisi.fromJson(Map<String, dynamic> json) => Teknisi(
    id: json["id"],
    nik: json["nik"],
    nama: json["nama"],
    telp: json["telp"],
    ktp: json["ktp"],
    target: json["target"],
    createdAt: DateTime.parse(json["created_at"]),
    updatedAt: DateTime.parse(json["updated_at"]),
  );

  Map<String, dynamic> toJson() => {
    "id": id,
    "nik": nik,
    "nama": nama,
    "telp": telp,
    "ktp": ktp,
    "target": target,
    "created_at": createdAt.toIso8601String(),
    "updated_at": updatedAt.toIso8601String(),
  };
}
