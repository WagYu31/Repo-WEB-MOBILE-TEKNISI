// To parse this JSON data, do
//
//     final detailInvoiceResponse = detailInvoiceResponseFromJson(jsonString);

import 'dart:convert';

DetailInvoiceResponse detailInvoiceResponseFromJson(String str) => DetailInvoiceResponse.fromJson(json.decode(str));

String detailInvoiceResponseToJson(DetailInvoiceResponse data) => json.encode(data.toJson());

class DetailInvoiceResponse {
  List<DataInvoice> data;

  DetailInvoiceResponse({
    required this.data,
  });

  factory DetailInvoiceResponse.fromJson(Map<String, dynamic> json) => DetailInvoiceResponse(
    data: List<DataInvoice>.from(json["data"].map((x) => DataInvoice.fromJson(x))),
  );

  Map<String, dynamic> toJson() => {
    "data": List<dynamic>.from(data.map((x) => x.toJson())),
  };
}

class DataInvoice {
  int id;
  String kegiatanKode;
  String name;
  int qty;
  int price;
  DateTime createdAt;
  DateTime updatedAt;

  DataInvoice({
    required this.id,
    required this.kegiatanKode,
    required this.name,
    required this.qty,
    required this.price,
    required this.createdAt,
    required this.updatedAt,
  });

  factory DataInvoice.fromJson(Map<String, dynamic> json) => DataInvoice(
    id: json["id"],
    kegiatanKode: json["kegiatan_kode"],
    name: json["name"],
    qty: json["qty"],
    price: json["price"],
    createdAt: DateTime.parse(json["created_at"]),
    updatedAt: DateTime.parse(json["updated_at"]),
  );

  Map<String, dynamic> toJson() => {
    "id": id,
    "kegiatan_kode": kegiatanKode,
    "name": name,
    "qty": qty,
    "price": price,
    "created_at": createdAt.toIso8601String(),
    "updated_at": updatedAt.toIso8601String(),
  };
}
