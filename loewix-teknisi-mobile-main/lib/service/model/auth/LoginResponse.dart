// To parse this JSON data, do
//
//     final loginResponse = loginResponseFromJson(jsonString);

import 'dart:convert';

LoginResponse loginResponseFromJson(String str) => LoginResponse.fromJson(json.decode(str));

String loginResponseToJson(LoginResponse data) => json.encode(data.toJson());

class LoginResponse {
    String message;
    String token;
    User user;

    LoginResponse({
        required this.message,
        required this.token,
        required this.user,
    });

    factory LoginResponse.fromJson(Map<String, dynamic> json) => LoginResponse(
        message: json["message"],
        token: json["token"],
        user: User.fromJson(json["user"]),
    );

    Map<String, dynamic> toJson() => {
        "message": message,
        "token": token,
        "user": user.toJson(),
    };
}

class User {
    int id;
    int teknisiId;
    String nama;
    String username;
    DateTime createdAt;
    DateTime updatedAt;
    dynamic deletedAt;

    User({
        required this.id,
        required this.teknisiId,
        required this.nama,
        required this.username,
        required this.createdAt,
        required this.updatedAt,
        required this.deletedAt,
    });

    factory User.fromJson(Map<String, dynamic> json) => User(
        id: json["id"],
        teknisiId: json["teknisi_id"],
        nama: json["nama"],
        username: json["username"],
        createdAt: DateTime.parse(json["created_at"]),
        updatedAt: DateTime.parse(json["updated_at"]),
        deletedAt: json["deleted_at"],
    );

    Map<String, dynamic> toJson() => {
        "id": id,
        "teknisi_id": teknisiId,
        "nama": nama,
        "username": username,
        "created_at": createdAt.toIso8601String(),
        "updated_at": updatedAt.toIso8601String(),
        "deleted_at": deletedAt,
    };
}
