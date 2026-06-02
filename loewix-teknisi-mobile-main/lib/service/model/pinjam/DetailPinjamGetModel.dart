// To parse this JSON data, do
//
//     final detailPinjamGetResponse = detailPinjamGetResponseFromJson(jsonString);

import 'dart:convert';

DetailPinjamGetResponse detailPinjamGetResponseFromJson(String str) => DetailPinjamGetResponse.fromJson(json.decode(str));

String detailPinjamGetResponseToJson(DetailPinjamGetResponse data) => json.encode(data.toJson());

class DetailPinjamGetResponse {
  Data data;

  DetailPinjamGetResponse({
    required this.data,
  });

  factory DetailPinjamGetResponse.fromJson(Map<String, dynamic> json) => DetailPinjamGetResponse(
    data: Data.fromJson(json["data"]),
  );

  Map<String, dynamic> toJson() => {
    "data": data.toJson(),
  };
}

class Data {
  int id;
  String status;
  String code;
  DateTime tglPinjam;
  dynamic tglKembali;
  dynamic keterangan;
  dynamic gambar;
  List<Barang> barang;
  Teknisi teknisi;

  Data({
    required this.id,
    required this.status,
    required this.code,
    required this.tglPinjam,
    required this.tglKembali,
    required this.keterangan,
    required this.gambar,
    required this.barang,
    required this.teknisi,
  });

  factory Data.fromJson(Map<String, dynamic> json) => Data(
    id: json["id"],
    status: json["status"],
    code: json["code"],
    tglPinjam: DateTime.parse(json["tgl_pinjam"]),
    tglKembali: json["tgl_kembali"],
    keterangan: json["keterangan"],
    gambar: json["gambar"],
    barang: List<Barang>.from(json["barang"].map((x) => Barang.fromJson(x))),
    teknisi: Teknisi.fromJson(json["teknisi"]),
  );

  Map<String, dynamic> toJson() => {
    "id": id,
    "status": status,
    "code": code,
    "tgl_pinjam": "${tglPinjam.year.toString().padLeft(4, '0')}-${tglPinjam.month.toString().padLeft(2, '0')}-${tglPinjam.day.toString().padLeft(2, '0')}",
    "tgl_kembali": tglKembali,
    "keterangan": keterangan,
    "gambar": gambar,
    "barang": List<dynamic>.from(barang.map((x) => x.toJson())),
    "teknisi": teknisi.toJson(),
  };
}

class Barang {
  int id;
  String namaBarang;
  String deskripsi;
  int qty;
  DateTime createdAt;
  DateTime updatedAt;
  dynamic deletedAt;

  Barang({
    required this.id,
    required this.namaBarang,
    required this.deskripsi,
    required this.qty,
    required this.createdAt,
    required this.updatedAt,
    required this.deletedAt,
  });

  factory Barang.fromJson(Map<String, dynamic> json) => Barang(
    id: json["id"],
    namaBarang: json["nama_barang"],
    deskripsi: json["deskripsi"],
    qty: json["qty"],
    createdAt: DateTime.parse(json["created_at"]),
    updatedAt: DateTime.parse(json["updated_at"]),
    deletedAt: json["deleted_at"],
  );

  Map<String, dynamic> toJson() => {
    "id": id,
    "nama_barang": namaBarang,
    "deskripsi": deskripsi,
    "qty": qty,
    "created_at": createdAt.toIso8601String(),
    "updated_at": updatedAt.toIso8601String(),
    "deleted_at": deletedAt,
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
