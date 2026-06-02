// To parse this JSON data, do
//
//     final reimburseResponse = reimburseResponseFromJson(jsonString);

import 'dart:convert';

ReimburseResponse reimburseResponseFromJson(String str) => ReimburseResponse.fromJson(json.decode(str));

String reimburseResponseToJson(ReimburseResponse data) => json.encode(data.toJson());

class ReimburseResponse {
    String status;
    List<DataReimburse> data;                                  

    ReimburseResponse({
        required this.status,
        required this.data,
    });

    factory ReimburseResponse.fromJson(Map<String, dynamic> json) => ReimburseResponse(
        status: json["status"],
        data: List<DataReimburse>.from(json["data"].map((x) => DataReimburse.fromJson(x))),
    );

    Map<String, dynamic> toJson() => {
        "status": status,
        "data": List<dynamic>.from(data.map((x) => x.toJson())),
    };
}

class DataReimburse {
    int id;
    String kegiatanId;
    String teknisiId;
    int nominal;
    DateTime tanggal;
    String keterangan;
    String bukti1;
    dynamic bukti2;
    dynamic bukti3;
    dynamic bukti4;
    dynamic bukti5;
    DateTime createdAt;
    DateTime updatedAt;
    dynamic deletedAt;
    Kegiatan kegiatan;

    DataReimburse({
        required this.id,
        required this.kegiatanId,
        required this.teknisiId,
        required this.nominal,
        required this.tanggal,
        required this.keterangan,
        required this.bukti1,
        required this.bukti2,
        required this.bukti3,
        required this.bukti4,
        required this.bukti5,
        required this.createdAt,
        required this.updatedAt,
        required this.deletedAt,
        required this.kegiatan,
    });

    factory DataReimburse.fromJson(Map<String, dynamic> json) => DataReimburse(
        id: json["id"],
        kegiatanId: json["kegiatan_id"],
        teknisiId: json["teknisi_id"],
        nominal: json["nominal"],
        tanggal: DateTime.parse(json["tanggal"]),
        keterangan: json["keterangan"],
        bukti1: json["bukti_1"],
        bukti2: json["bukti_2"],
        bukti3: json["bukti_3"],
        bukti4: json["bukti_4"],
        bukti5: json["bukti_5"],
        createdAt: DateTime.parse(json["created_at"]),
        updatedAt: DateTime.parse(json["updated_at"]),
        deletedAt: json["deleted_at"],
        kegiatan: Kegiatan.fromJson(json["kegiatan"]),
    );

    Map<String, dynamic> toJson() => {
        "id": id,
        "kegiatan_id": kegiatanId,
        "teknisi_id": teknisiId,
        "nominal": nominal,
        "tanggal": "${tanggal.year.toString().padLeft(4, '0')}-${tanggal.month.toString().padLeft(2, '0')}-${tanggal.day.toString().padLeft(2, '0')}",
        "keterangan": keterangan,
        "bukti_1": bukti1,
        "bukti_2": bukti2,
        "bukti_3": bukti3,
        "bukti_4": bukti4,
        "bukti_5": bukti5,
        "created_at": createdAt.toIso8601String(),
        "updated_at": updatedAt.toIso8601String(),
        "deleted_at": deletedAt,
        "kegiatan": kegiatan.toJson(),
    };
}

class Kegiatan {
    int id;
    String kegiatan;
    DateTime jadwal;
    Customer customer;

    Kegiatan({
        required this.id,
        required this.kegiatan,
        required this.jadwal,
        required this.customer,
    });

    factory Kegiatan.fromJson(Map<String, dynamic> json) => Kegiatan(
        id: json["id"],
        kegiatan: json["kegiatan"],
        jadwal: DateTime.parse(json["jadwal"]),
        customer: Customer.fromJson(json["customer"]),
    );

    Map<String, dynamic> toJson() => {
        "id": id,
        "kegiatan": kegiatan,
        "jadwal": jadwal.toIso8601String(),
        "customer": customer.toJson(),
    };
}

class Customer {
    int id;
    String nama;
    String telp;

    Customer({
        required this.id,
        required this.nama,
        required this.telp,
    });

    factory Customer.fromJson(Map<String, dynamic> json) => Customer(
        id: json["id"],
        nama: json["nama"],
        telp: json["telp"],
    );

    Map<String, dynamic> toJson() => {
        "id": id,
        "nama": nama,
        "telp": telp,
    };
}
