// To parse this JSON data, do
//
//     final pinjamGetResponse = pinjamGetResponseFromJson(jsonString);

import 'dart:convert';

PinjamGetResponse pinjamGetResponseFromJson(String str) => PinjamGetResponse.fromJson(json.decode(str));

String pinjamGetResponseToJson(PinjamGetResponse data) => json.encode(data.toJson());

class PinjamGetResponse {
  List<DataPinjam> data;

  PinjamGetResponse({
    required this.data,
  });

  factory PinjamGetResponse.fromJson(Map<String, dynamic> json) => PinjamGetResponse(
    data: List<DataPinjam>.from(json["data"].map((x) => DataPinjam.fromJson(x))),
  );

  Map<String, dynamic> toJson() => {
    "data": List<dynamic>.from(data.map((x) => x.toJson())),
  };
}

class DataPinjam {
  String code;
  int teknisiId;
  DateTime tglPinjam;
  String status;
  int total;
  dynamic barang;
  Teknisi teknisi;

  DataPinjam({
    required this.code,
    required this.teknisiId,
    required this.tglPinjam,
    required this.status,
    required this.total,
    required this.barang,
    required this.teknisi,
  });

  factory DataPinjam.fromJson(Map<String, dynamic> json) => DataPinjam(
    code: json["code"],
    teknisiId: json["teknisi_id"],
    tglPinjam: DateTime.parse(json["tgl_pinjam"]),
    status: json["status"],
    total: json["total"],
    barang: json["barang"],
    teknisi: Teknisi.fromJson(json["teknisi"]),
  );

  Map<String, dynamic> toJson() => {
    "code": code,
    "teknisi_id": teknisiId,
    "tgl_pinjam": "${tglPinjam.year.toString().padLeft(4, '0')}-${tglPinjam.month.toString().padLeft(2, '0')}-${tglPinjam.day.toString().padLeft(2, '0')}",
    "status": status,
    "total": total,
    "barang": barang,
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
