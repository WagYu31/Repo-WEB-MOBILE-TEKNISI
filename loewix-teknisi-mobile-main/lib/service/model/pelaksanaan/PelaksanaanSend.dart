// To parse this JSON data, do
//
//     final pelaksanaanSendResponse = pelaksanaanSendResponseFromJson(jsonString);

import 'dart:convert';

PelaksanaanSendResponse pelaksanaanSendResponseFromJson(String str) => PelaksanaanSendResponse.fromJson(json.decode(str));

String pelaksanaanSendResponseToJson(PelaksanaanSendResponse data) => json.encode(data.toJson());

class PelaksanaanSendResponse {
    String message;
    Data? data;

    PelaksanaanSendResponse({
        required this.message,
        this.data,
    });

    factory PelaksanaanSendResponse.fromJson(Map<String, dynamic> json) => PelaksanaanSendResponse(
        message: json["message"],
        data: json["data"] != null ? Data.fromJson(json["data"]) : null,
    );

    Map<String, dynamic> toJson() => {
        "message": message,
        "data": data?.toJson(),
    };
}

class Data {
    int id;
    String kegiatanId;
    String teknisiId;
    dynamic waktuMulai;
    dynamic waktuSelesai;
    dynamic status;
    dynamic keterangan;
    dynamic image1;
    dynamic image2;
    dynamic image3;
    dynamic image4;
    dynamic image5;

    Data({
        required this.id,
        required this.kegiatanId,
        required this.teknisiId,
        required this.waktuMulai,
        required this.waktuSelesai,
        required this.status,
        required this.keterangan,
        required this.image1,
        required this.image2,
        required this.image3,
        required this.image4,
        required this.image5,
    });

    factory Data.fromJson(Map<String, dynamic> json) => Data(
        id: json["id"],
        kegiatanId: json["kegiatan_id"].toString(),
        teknisiId: json["teknisi_id"].toString(),
        waktuMulai: json["waktu_mulai"],
        waktuSelesai: json["waktu_selesai"],
        status: json["status"],
        keterangan: json["keterangan"],
        image1: json["image_1"],
        image2: json["image_2"],
        image3: json["image_3"],
        image4: json["image_4"],
        image5: json["image_5"],
    );

    Map<String, dynamic> toJson() => {
        "id": id,
        "kegiatan_id": kegiatanId,
        "teknisi_id": teknisiId,
        "waktu_mulai": waktuMulai,
        "waktu_selesai": waktuSelesai,
        "status": status,
        "keterangan": keterangan,
        "image_1": image1,
        "image_2": image2,
        "image_3": image3,
        "image_4": image4,
        "image_5": image5,
    };
}
