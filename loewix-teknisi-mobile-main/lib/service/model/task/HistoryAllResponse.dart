// To parse this JSON data, do
//
//     final taskAllResponse = taskAllResponseFromJson(jsonString);

import 'dart:convert';

HistoryAllResponse taskAllResponseFromJson(String str) => HistoryAllResponse.fromJson(json.decode(str));

String taskAllResponseToJson(HistoryAllResponse data) => json.encode(data.toJson());

class HistoryAllResponse {
  List<DataTask> data;

  HistoryAllResponse({
    required this.data,
  });

  factory HistoryAllResponse.fromJson(Map<String, dynamic> json) => HistoryAllResponse(
    data: List<DataTask>.from(json["data"].map((x) => DataTask.fromJson(x))),
  );

  Map<String, dynamic> toJson() => {
    "data": List<dynamic>.from(data.map((x) => x.toJson())),
  };
}

class DataTask {
  int id;
  DataCustomer dataCustomer;
  List<DataTeknisi> dataTeknisi;
  String kegiatan;
  DateTime jadwal;
  dynamic keterangan;
  String request;
  String status;

  DataTask({
    required this.id,
    required this.dataCustomer,
    required this.dataTeknisi,
    required this.kegiatan,
    required this.jadwal,
    required this.keterangan,
    required this.request,
    required this.status,
  });

  factory DataTask.fromJson(Map<String, dynamic> json) => DataTask(
    id: json["id"],
    dataCustomer: DataCustomer.fromJson(json["data_customer"]),
    dataTeknisi: List<DataTeknisi>.from(json["data_teknisi"].map((x) => DataTeknisi.fromJson(x))),
    kegiatan: json["kegiatan"],
    jadwal: DateTime.parse(json["jadwal"]),
    keterangan: json["keterangan"],
    request: json["request"],
    status: json["status"],
  );

  Map<String, dynamic> toJson() => {
    "id": id,
    "data_customer": dataCustomer.toJson(),
    "data_teknisi": List<dynamic>.from(dataTeknisi.map((x) => x.toJson())),
    "kegiatan": kegiatan,
    "jadwal": jadwal.toIso8601String(),
    "keterangan": keterangan,
    "request": request,
    "status": status,
  };
}

class DataCustomer {
  int customerId;
  String namaCustomer;
  String alamatCustomer;
  String hpCustomer;

  DataCustomer({
    required this.customerId,
    required this.namaCustomer,
    required this.alamatCustomer,
    required this.hpCustomer,
  });

  factory DataCustomer.fromJson(Map<String, dynamic> json) => DataCustomer(
    customerId: json["customer_id"],
    namaCustomer: json["nama_customer"],
    alamatCustomer: json["alamat_customer"],
    hpCustomer: json["hp_customer"],
  );

  Map<String, dynamic> toJson() => {
    "customer_id": customerId,
    "nama_customer": namaCustomer,
    "alamat_customer": alamatCustomer,
    "hp_customer": hpCustomer,
  };
}

class DataTeknisi {
  int id;
  int kegiatanId;
  int teknisiId;
  String namaTeknisi;
  DateTime createdAt;
  DateTime updatedAt;
  dynamic deletedAt;

  DataTeknisi({
    required this.id,
    required this.kegiatanId,
    required this.teknisiId,
    required this.namaTeknisi,
    required this.createdAt,
    required this.updatedAt,
    required this.deletedAt,
  });

  factory DataTeknisi.fromJson(Map<String, dynamic> json) => DataTeknisi(
    id: json["id"],
    kegiatanId: json["kegiatan_id"],
    teknisiId: json["teknisi_id"],
    namaTeknisi: json["nama_teknisi"],
    createdAt: DateTime.parse(json["created_at"]),
    updatedAt: DateTime.parse(json["updated_at"]),
    deletedAt: json["deleted_at"],
  );

  Map<String, dynamic> toJson() => {
    "id": id,
    "kegiatan_id": kegiatanId,
    "teknisi_id": teknisiId,
    "nama_teknisi": namaTeknisi,
    "created_at": createdAt.toIso8601String(),
    "updated_at": updatedAt.toIso8601String(),
    "deleted_at": deletedAt,
  };
}
