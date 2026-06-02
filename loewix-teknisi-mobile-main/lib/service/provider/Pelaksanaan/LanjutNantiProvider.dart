import 'dart:typed_data';
import '../../../service/api/ApiPelaksanaan.dart';
import '../../../service/model/pelaksanaan/PelaksanaanSend.dart';
import '../../../utils/state.dart';
import 'package:flutter/material.dart';
import 'package:image/image.dart' as img;

class LanjutNantiProvider extends ChangeNotifier {
  final ApiPelaksanaan apiService;

  LanjutNantiProvider({required this.apiService});

  bool isUploading = false;
  ResultState _state = ResultState.dll;
  ResultState get state => _state;
  String message = "";
  PelaksanaanSendResponse? uploadResponse;

  Future<String> upload(
      List<List<int>> imagesBytes,
      String kegiatanId,
      String teknisiId,
      String jadwal,
      String keterangan,
      String latS,
      String lonS,
      ) async {
    try {
      message = "";
      uploadResponse = null;
      isUploading = true;
      _state = ResultState.loading;
      notifyListeners();

      uploadResponse = await apiService.doLanjutNanti(imagesBytes, kegiatanId, teknisiId, jadwal, keterangan, latS, lonS);
      print('hasil lanjut nanti dari api :' +uploadResponse.toString());
      message = uploadResponse?.message ?? "success";
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
