

// To parse this JSON data, do
//
//     final taskAllResponse = taskAllResponseFromJson(jsonString);

import 'dart:convert';







TaskAllResponse taskAllResponseFromJson(String str) => TaskAllResponse.fromJson(json.decode(str));

String taskAllResponseToJson(TaskAllResponse data) => json.encode(data.toJson());

class TaskAllResponse {
    List<DataTask> data;

    TaskAllResponse({
        required this.data,
    });

    factory TaskAllResponse.fromJson(Map<String, dynamic> json) => TaskAllResponse(
        data: List<DataTask>.from(json["data"].map((x) => DataTask.fromJson(x))),
    );

    Map<String, dynamic> toJson() => {
        "data": List<dynamic>.from(data.map((x) => x.toJson())),
    };
}

class DataTask {
    int id;
    int customerId;
    String kegiatan;
    DateTime jadwal;
    dynamic keterangan;
    String status;
    String request;
    dynamic lanjutanId;
    String kode;
    dynamic paid;
    dynamic sales;
    DateTime createdAt;
    DateTime updatedAt;
    dynamic deletedAt;
    dynamic lat;
    dynamic lon;
    dynamic rad;
    DataCustomer dataCustomer;
    List<DataTeknisi> dataTeknisi;
    List<Pelaksanaan> pelaksanaan;

    DataTask({
        required this.id,
        required this.customerId,
        required this.kegiatan,
        required this.jadwal,
        required this.keterangan,
        required this.status,
        required this.request,
        required this.lanjutanId,
        required this.kode,
        required this.paid,
        required this.sales,
        required this.lat,
        required this.lon,
        required this.rad,
        required this.createdAt,
        required this.updatedAt,
        required this.deletedAt,
        required this.dataCustomer,
        required this.dataTeknisi,
        required this.pelaksanaan,
    });

    factory DataTask.fromJson(Map<String, dynamic> json) => DataTask(
        id: json["id"],
        customerId: json["customer_id"],
        kegiatan: json["kegiatan"],
        jadwal: json["jadwal"] != null ? DateTime.parse(json["jadwal"]) : DateTime.now(),
        keterangan: json["keterangan"],
        status: json["status"] ?? '',
        request: json["request"],
        lanjutanId: json["lanjutan_id"],
        kode: json["kode"],
        paid: json["paid"],
        sales: json["sales"],
        lat: json["lat"],
        lon: json["lon"],
        rad: json["rad"],
        createdAt: DateTime.parse(json["created_at"]),
        updatedAt: DateTime.parse(json["updated_at"]),
        deletedAt: json["deleted_at"],
        dataCustomer: DataCustomer.fromJson(json["customer"]),
        dataTeknisi: List<DataTeknisi>.from(json["teknisi"].map((x) => DataTeknisi.fromJson(x))),
        pelaksanaan: List<Pelaksanaan>.from(json["pelaksanaan"].map((x) => Pelaksanaan.fromJson(x))),
    );

    Map<String, dynamic> toJson() => {
        "id": id,
        "customer_id": customerId,
        "kegiatan": kegiatan,
        "jadwal": jadwal.toIso8601String(),
        "keterangan": keterangan,
        "status": status,
        "request": request,
        "lanjutan_id": lanjutanId,
        "kode": kode,
        "paid": paid,
        "lat": lat,
        "lon": lon,
        "rad": rad,
        "created_at": createdAt.toIso8601String(),
        "updated_at": updatedAt.toIso8601String(),
        "deleted_at": deletedAt,
        "customer": dataCustomer.toJson(),
        "teknisi": List<dynamic>.from(dataTeknisi.map((x) => x.toJson())),
        "pelaksanaan": List<dynamic>.from(pelaksanaan.map((x) => x.toJson())),
    };
}

class DataCustomer {
    int id;
    String nama;
    dynamic telp;
    dynamic email;
    dynamic alamat;
    dynamic kota;
    dynamic kodepos;
    dynamic provinsi;
    dynamic kategori;
    DateTime createdAt;
    DateTime updatedAt;
    dynamic deletedAt;

    DataCustomer({
        required this.id,
        required this.nama,
        required this.telp,
        required this.email,
        required this.alamat,
        required this.kota,
        required this.kodepos,
        required this.provinsi,
        required this.kategori,
        required this.createdAt,
        required this.updatedAt,
        required this.deletedAt,
    });

    factory DataCustomer.fromJson(Map<String, dynamic> json) => DataCustomer(
        id: json["id"],
        nama: json["nama"],
        telp: json["telp"],
        email: json["email"],
        alamat: json["alamat"],
        kota: json["kota"],
        kodepos: json["kodepos"],
        provinsi: json["provinsi"],
        kategori: json["kategori"],
        createdAt: DateTime.parse(json["created_at"]),
        updatedAt: DateTime.parse(json["updated_at"]),
        deletedAt: json["deleted_at"],
    );

    Map<String, dynamic> toJson() => {
        "id": id,
        "nama": nama,
        "telp": telp,
        "email": email,
        "alamat": alamat,
        "kota": kota,
        "kodepos": kodepos,
        "provinsi": provinsi,
        "kategori": kategori,
        "created_at": createdAt.toIso8601String(),
        "updated_at": updatedAt.toIso8601String(),
        "deleted_at": deletedAt,
    };
}

class Pelaksanaan {
    int id;
    int kegiatanId;
    int teknisiId;
    String? kode;
    DateTime? waktuMulai;
    dynamic waktuSelesai;
    String status;
    dynamic permasalahan;
    dynamic solusi;
    dynamic keterangan;
    String? latitude;
    String? longitude;
    dynamic latitudeS;
    dynamic longitudeS;
    dynamic image1;
    dynamic image2;
    dynamic image3;
    dynamic image4;
    dynamic image5;
    DateTime createdAt;
    DateTime updatedAt;
    dynamic deletedAt;

    Pelaksanaan({
        required this.id,
        required this.kegiatanId,
        required this.teknisiId,
        this.kode,
        this.waktuMulai,
        required this.waktuSelesai,
        required this.status,
        required this.permasalahan,
        required this.solusi,
        required this.keterangan,
        this.latitude,
        this.longitude,
        required this.latitudeS,
        required this.longitudeS,
        required this.image1,
        required this.image2,
        required this.image3,
        required this.image4,
        required this.image5,
        required this.createdAt,
        required this.updatedAt,
        required this.deletedAt,
    });

    factory Pelaksanaan.fromJson(Map<String, dynamic> json) => Pelaksanaan(
        id: json["id"],
        kegiatanId: json["kegiatan_id"],
        teknisiId: json["teknisi_id"],
        kode: json["kode"],
        waktuMulai: json["waktu_mulai"] != null ? DateTime.parse(json["waktu_mulai"]) : null,
        waktuSelesai: json["waktu_selesai"],
        status: json["status"] ?? '',
        permasalahan: json["permasalahan"],
        solusi: json["solusi"],
        keterangan: json["keterangan"],
        latitude: json["latitude"],
        longitude: json["longitude"],
        latitudeS: json["latitude_s"],
        longitudeS: json["longitude_s"],
        image1: json["image_1"],
        image2: json["image_2"],
        image3: json["image_3"],
        image4: json["image_4"],
        image5: json["image_5"],
        createdAt: DateTime.parse(json["created_at"]),
        updatedAt: DateTime.parse(json["updated_at"]),
        deletedAt: json["deleted_at"],
    );

    Map<String, dynamic> toJson() => {
        "id": id,
        "kegiatan_id": kegiatanId,
        "teknisi_id": teknisiId,
        "kode": kode,
        "waktu_mulai": waktuMulai?.toIso8601String(),
        "waktu_selesai": waktuSelesai,
        "status": status,
        "permasalahan": permasalahan,
        "solusi": solusi,
        "keterangan": keterangan,
        "latitude": latitude,
        "longitude": longitude,
        "latitude_s": latitudeS,
        "longitude_s": longitudeS,
        "image_1": image1,
        "image_2": image2,
        "image_3": image3,
        "image_4": image4,
        "image_5": image5,
        "created_at": createdAt.toIso8601String(),
        "updated_at": updatedAt.toIso8601String(),
        "deleted_at": deletedAt,
    };
}

class DataTeknisi {
    int id;
    int kegiatanId;
    int teknisiId;
    String namaDataTeknisi;
    int isKetua;
    String kode;
    DateTime createdAt;
    DateTime updatedAt;
    dynamic deletedAt;

    DataTeknisi({
        required this.id,
        required this.kegiatanId,
        required this.teknisiId,
        required this.namaDataTeknisi,
        this.isKetua = 0,
        required this.kode,
        required this.createdAt,
        required this.updatedAt,
        required this.deletedAt,
    });

    factory DataTeknisi.fromJson(Map<String, dynamic> json) => DataTeknisi(
        id: json["id"],
        kegiatanId: json["kegiatan_id"],
        teknisiId: json["teknisi_id"],
        namaDataTeknisi: json["nama_teknisi"],
        isKetua: json["is_ketua"] ?? 0,
        kode: json["kode"],
        createdAt: DateTime.parse(json["created_at"]),
        updatedAt: DateTime.parse(json["updated_at"]),
        deletedAt: json["deleted_at"],
    );

    Map<String, dynamic> toJson() => {
        "id": id,
        "kegiatan_id": kegiatanId,
        "teknisi_id": teknisiId,
        "nama_teknisi": namaDataTeknisi,
        "is_ketua": isKetua,
        "kode": kode,
        "created_at": createdAt.toIso8601String(),
        "updated_at": updatedAt.toIso8601String(),
        "deleted_at": deletedAt,
    };
}

