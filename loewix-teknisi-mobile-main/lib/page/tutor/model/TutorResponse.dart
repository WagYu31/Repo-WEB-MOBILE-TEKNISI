// To parse this JSON data, do
//
//     final tutorResponses = tutorResponsesFromJson(jsonString);

import 'dart:convert';

TutorResponses tutorResponsesFromJson(String str) => TutorResponses.fromJson(json.decode(str));

String tutorResponsesToJson(TutorResponses data) => json.encode(data.toJson());

class TutorResponses {
  List<Data> data;

  TutorResponses({
    required this.data,
  });

  factory TutorResponses.fromJson(Map<String, dynamic> json) => TutorResponses(
    data: List<Data>.from(json["data"].map((x) => Data.fromJson(x))),
  );

  Map<String, dynamic> toJson() => {
    "data": List<dynamic>.from(data.map((x) => x.toJson())),
  };
}

class Data {
  int id;
  String title;
  String? media1;
  String? media2;
  String description;
  DateTime? createdAt;
  DateTime? updatedAt;
  DateTime? deletedAt;

  Data({
    required this.id,
    required this.title,
    required this.media1,
    required this.media2,
    required this.description,
    required this.createdAt,
    required this.updatedAt,
    required this.deletedAt,
  });

  factory Data.fromJson(Map<String, dynamic> json) => Data(
    id: json["id"],
    title: json["title"],
    media1: json["media_1"],
    media2: json["media_2"],
    description: json["description"],
    createdAt: json["created_at"] == null ? null : DateTime.parse(json["created_at"]),
    updatedAt: json["updated_at"] == null ? null : DateTime.parse(json["updated_at"]),
    deletedAt: json["deleted_at"],
  );

  Map<String, dynamic> toJson() => {
    "id": id,
    "title": title,
    "media_1": media1,
    "media_2": media2,
    "description": description,
    "created_at": createdAt?.toIso8601String(),
    "updated_at": updatedAt?.toIso8601String(),
    "deleted_at": deletedAt,
  };
}
