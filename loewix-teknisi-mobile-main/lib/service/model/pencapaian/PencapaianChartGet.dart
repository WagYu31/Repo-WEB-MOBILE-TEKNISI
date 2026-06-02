// To parse this JSON data, do
//
//     final chartResponse = chartResponseFromJson(jsonString);

import 'dart:convert';

ChartResponse chartResponseFromJson(String str) => ChartResponse.fromJson(json.decode(str));

String chartResponseToJson(ChartResponse data) => json.encode(data.toJson());

class ChartResponse {
  List<DataPencapaian> data;

  ChartResponse({
    required this.data,
  });

  factory ChartResponse.fromJson(Map<String, dynamic> json) => ChartResponse(
    data: List<DataPencapaian>.from(json["data"].map((x) => DataPencapaian.fromJson(x))),
  );

  Map<String, dynamic> toJson() => {
    "data": List<dynamic>.from(data.map((x) => x.toJson())),
  };
}

class DataPencapaian {
  int id;
  int teknisiId;
  DateTime tanggal;
  String target;
  String pendapatan;
  dynamic bonus;
  DateTime createdAt;
  DateTime updatedAt;
  dynamic deletedAt;

  DataPencapaian({
    required this.id,
    required this.teknisiId,
    required this.tanggal,
    required this.target,
    required this.pendapatan,
    required this.bonus,
    required this.createdAt,
    required this.updatedAt,
    required this.deletedAt,
  });

  factory DataPencapaian.fromJson(Map<String, dynamic> json) => DataPencapaian(
    id: json["id"],
    teknisiId: json["teknisi_id"],
    tanggal: DateTime.parse(json["tanggal"]),
    target: json["target"],
    pendapatan: json["pendapatan"],
    bonus: json["bonus"],
    createdAt: DateTime.parse(json["created_at"]),
    updatedAt: DateTime.parse(json["updated_at"]),
    deletedAt: json["deleted_at"],
  );

  Map<String, dynamic> toJson() => {
    "id": id,
    "teknisi_id": teknisiId,
    "tanggal": "${tanggal.year.toString().padLeft(4, '0')}-${tanggal.month.toString().padLeft(2, '0')}-${tanggal.day.toString().padLeft(2, '0')}",
    "target": target,
    "pendapatan": pendapatan,
    "bonus": bonus,
    "created_at": createdAt.toIso8601String(),
    "updated_at": updatedAt.toIso8601String(),
    "deleted_at": deletedAt,
  };
}
