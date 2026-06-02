// To parse this JSON data, do
//
//     final detailTutorResponses = detailTutorResponsesFromJson(jsonString);

import 'dart:convert';

DetailTutorResponses detailTutorResponsesFromJson(String str) => DetailTutorResponses.fromJson(json.decode(str));

String detailTutorResponsesToJson(DetailTutorResponses data) => json.encode(data.toJson());

class DetailTutorResponses {
  Data data;

  DetailTutorResponses({
    required this.data,
  });

  factory DetailTutorResponses.fromJson(Map<String, dynamic> json) => DetailTutorResponses(
    data: Data.fromJson(json["data"]),
  );

  Map<String, dynamic> toJson() => {
    "data": data.toJson(),
  };
}

class Data {
  int id;
  String title;
  dynamic media1;
  dynamic media2;
  String description;
  dynamic createdAt;
  dynamic updatedAt;
  dynamic deletedAt;

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
    createdAt: json["created_at"],
    updatedAt: json["updated_at"],
    deletedAt: json["deleted_at"],
  );

  Map<String, dynamic> toJson() => {
    "id": id,
    "title": title,
    "media_1": media1,
    "media_2": media2,
    "description": description,
    "created_at": createdAt,
    "updated_at": updatedAt,
    "deleted_at": deletedAt,
  };
}
