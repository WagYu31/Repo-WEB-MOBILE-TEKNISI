import 'dart:typed_data';

import '../../../service/api/ApiPinjam.dart';
import '../../../service/model/message/MessageModel.dart';
import 'package:flutter/material.dart';
import 'package:image/image.dart' as img;

import '../../../utils/state.dart';

class KembalikanPinjamProvider extends ChangeNotifier {
  final ApiPinjam apiService;

  KembalikanPinjamProvider({required this.apiService});

  bool isUploading = false;
  ResultState _state = ResultState.dll;
  ResultState get state => _state;
  String message = "";
  MessageResponse? uploadResponse;

  Future<String> kembalikanBarang(
      List<int> imagesBytes,
      String keterangan,
      String id
      ) async {
    try {
      message = "";
      uploadResponse = null;
      isUploading = true;
      _state = ResultState.loading;
      notifyListeners();

      uploadResponse = await apiService.kembalikanBarang(imagesBytes, keterangan, id);

      message = uploadResponse?.message ?? "Data berhasil diperbarui";
      isUploading = false;
      _state = ResultState.hasData;
      notifyListeners();

      return uploadResponse!.message;
    } catch (e) {
      isUploading = false;
      message = e.toString();
      _state = ResultState.noData;
      notifyListeners();

      return message;
    }
  }

  Future<List<int>> compressImage(List<int> bytes) async {
    Uint8List bytess = Uint8List.fromList(bytes);
    int imageLength = bytes.length;
    if (imageLength < 1000000) return bytes;
    final img.Image image = img.decodeImage(bytess)!;
    int compressQuality = 100;
    int length = imageLength;
    List<int> newByte = [];

    do {
      ///
      compressQuality -= 10;

      newByte = img.encodeJpg(
        image,
        quality: compressQuality,
      );

      length = newByte.length;
    } while (length > 1000000);

    return newByte;
  }

  Future<List<int>> resizeImage(List<int> bytes) async {
    Uint8List bytess = Uint8List.fromList(bytes);
    int imageLength = bytes.length;
    if (imageLength < 1000000) return bytes;

    final img.Image image = img.decodeImage(bytess)!;
    bool isWidthMoreTaller = image.width > image.height;
    int imageTall = isWidthMoreTaller ? image.width : image.height;
    double compressTall = 1;
    int length = imageLength;
    List<int> newByte = bytes;

    do {
      ///
      compressTall -= 0.1;

      final newImage = img.copyResize(
        image,
        width: isWidthMoreTaller ? (imageTall * compressTall).toInt() : null,
        height: !isWidthMoreTaller ? (imageTall * compressTall).toInt() : null,
      );

      length = newImage.length;
      if (length < 1000000) {
        newByte = img.encodeJpg(newImage);
      }
    } while (length > 1000000);

    return newByte;
  }
}