import 'dart:convert';
import 'TaskAllResponse.dart';

HistoryPaginatedResponse historyPaginatedResponseFromJson(String str) =>
    HistoryPaginatedResponse.fromJson(json.decode(str));

String historyPaginatedResponseToJson(HistoryPaginatedResponse data) =>
    json.encode(data.toJson());

class HistoryPaginatedResponse {
  int currentPage;
  List<DataTask> data;
  String? firstPageUrl;
  int? from;
  int lastPage;
  String? lastPageUrl;
  String? nextPageUrl;
  String path;
  int perPage;
  String? prevPageUrl;
  int? to;
  int total;

  HistoryPaginatedResponse({
    required this.currentPage,
    required this.data,
    this.firstPageUrl,
    this.from,
    required this.lastPage,
    this.lastPageUrl,
    this.nextPageUrl,
    required this.path,
    required this.perPage,
    this.prevPageUrl,
    this.to,
    required this.total,
  });

  factory HistoryPaginatedResponse.fromJson(Map<String, dynamic> json) =>
      HistoryPaginatedResponse(
        currentPage: json["current_page"] ?? 1,
        data: json["data"] != null
            ? List<DataTask>.from(json["data"].map((x) => DataTask.fromJson(x)))
            : [],
        firstPageUrl: json["first_page_url"],
        from: json["from"],
        lastPage: json["last_page"] ?? 1,
        lastPageUrl: json["last_page_url"],
        nextPageUrl: json["next_page_url"],
        path: json["path"] ?? "",
        perPage: json["per_page"] ?? 10,
        prevPageUrl: json["prev_page_url"],
        to: json["to"],
        total: json["total"] ?? 0,
      );

  Map<String, dynamic> toJson() => {
        "current_page": currentPage,
        "data": List<dynamic>.from(data.map((x) => x.toJson())),
        "first_page_url": firstPageUrl,
        "from": from,
        "last_page": lastPage,
        "last_page_url": lastPageUrl,
        "next_page_url": nextPageUrl,
        "path": path,
        "per_page": perPage,
        "prev_page_url": prevPageUrl,
        "to": to,
        "total": total,
      };

  bool get hasNextPage => nextPageUrl != null;
  bool get hasPrevPage => prevPageUrl != null;
  bool get isLastPage => currentPage >= lastPage;
}
