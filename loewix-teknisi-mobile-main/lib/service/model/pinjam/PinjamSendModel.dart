// To parse this JSON data, do
//
//     final pinjamSendResponse = pinjamSendResponseFromJson(jsonString);

import 'dart:convert';

PinjamSendResponse pinjamSendResponseFromJson(String str) => PinjamSendResponse.fromJson(json.decode(str));

String pinjamSendResponseToJson(PinjamSendResponse data) => json.encode(data.toJson());

class PinjamSendResponse {
  String message;
  Peminjaman peminjaman;

  PinjamSendResponse({
    required this.message,
    required this.peminjaman,
  });

  factory PinjamSendResponse.fromJson(Map<String, dynamic> json) => PinjamSendResponse(
    message: json["message"],
    peminjaman: Peminjaman.fromJson(json["peminjaman"]),
  );

  Map<String, dynamic> toJson() => {
    "message": message,
    "peminjaman": peminjaman.toJson(),
  };
}

class Peminjaman {
  String teknisiId;
  String barangId;
  int qty;
  DateTime tglPinjam;
  DateTime updatedAt;
  DateTime createdAt;
  int id;

  Peminjaman({
    required this.teknisiId,
    required this.barangId,
    required this.qty,
    required this.tglPinjam,
    required this.updatedAt,
    required this.createdAt,
    required this.id,
  });

  factory Peminjaman.fromJson(Map<String, dynamic> json) => Peminjaman(
    teknisiId: json["teknisi_id"],
    barangId: json["barang_id"],
    qty: json["qty"],
    tglPinjam: DateTime.parse(json["tgl_pinjam"]),
    updatedAt: DateTime.parse(json["updated_at"]),
    createdAt: DateTime.parse(json["created_at"]),
    id: json["id"],
  );

  Map<String, dynamic> toJson() => {
    "teknisi_id": teknisiId,
    "barang_id": barangId,
    "qty": qty,
    "tgl_pinjam": "${tglPinjam.year.toString().padLeft(4, '0')}-${tglPinjam.month.toString().padLeft(2, '0')}-${tglPinjam.day.toString().padLeft(2, '0')}",
    "updated_at": updatedAt.toIso8601String(),
    "created_at": createdAt.toIso8601String(),
    "id": id,
  };
}
