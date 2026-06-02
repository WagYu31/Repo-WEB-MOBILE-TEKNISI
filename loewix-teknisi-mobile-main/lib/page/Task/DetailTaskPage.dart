import 'package:flutter/material.dart';
import 'package:quickalert/models/quickalert_type.dart';
import 'package:quickalert/widgets/quickalert_dialog.dart';
import 'package:url_launcher/url_launcher.dart';
import 'package:geocoding/geocoding.dart';
import '../../service/model/task/TaskAllResponse.dart';

class DetailTaskPage extends StatelessWidget {
  static const routeName = '/detail_kegiatan_o';
  final DataTask data;
  const DetailTaskPage({super.key, required this.data});

  Future<void> _openMap(BuildContext context, String input) async {
    try {
      String url;
      // Regex untuk mendeteksi URL Google Maps
      RegExp mapsUrlRegex = RegExp(
        r'(https?:\/\/(www\.)?google\.com\/maps\b|https?:\/\/goo\.gl\/maps\b|https?:\/\/\S+)',
        caseSensitive: false,
      );

      Match? match = mapsUrlRegex.firstMatch(input);
      if (match != null) {
        // Jika URL Maps ditemukan, gunakan URL tersebut
        url = match.group(0)!;
      } else {
        // Jika tidak ada URL Maps, coba geocode seluruh input sebagai alamat
        List<Location> locations = await locationFromAddress(input);
        if (locations.isEmpty) {
          throw 'Tidak dapat menemukan koordinat untuk alamat yang diberikan';
        }
        double latitude = locations.first.latitude;
        double longitude = locations.first.longitude;
        url = 'https://www.google.com/maps/search/?api=1&query=$latitude,$longitude';
      }

      if (await canLaunch(url)) {
        await launch(url);
      } else {
        throw 'Tidak dapat membuka $url';
      }
    } catch (e) {
      print(e);
      QuickAlert.show(
        context: context,
        type: QuickAlertType.error,
        title: 'Error',
        text: 'Gagal membuka Maps: $e',
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: Text(
          'Detail Kegiatan',
          style: TextStyle(fontFamily: 'Poppins',
            fontSize: 25
          ),
        ),
      ),
      body: Padding(
        padding: const EdgeInsets.all(15.0),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            Text(
              data.kegiatan,
              textAlign: TextAlign.center,
              style: TextStyle(fontFamily: 'Poppins',
                  fontSize: 24, fontWeight: FontWeight.bold),
            ),
            const SizedBox(
              height: 15,
            ),
            Row(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Image.asset(
                  'assets/imgUser.png',
                  scale: 2,
                ),
                const SizedBox(
                  width: 20,
                ),
                Column(
                  mainAxisAlignment: MainAxisAlignment.center,
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      'Nama Customer',
                      textAlign: TextAlign.left,
                      style: TextStyle(fontFamily: 'Poppins',fontSize: 14),
                    ),
                    Text(
                      data.dataCustomer.nama,
                      textAlign: TextAlign.left,
                      style: TextStyle(fontFamily: 'Poppins',
                          fontSize: 22,
                          fontWeight: FontWeight.w600),
                    )
                  ],
                )
              ],
            ),
            const SizedBox(
              height: 10,
            ),
            Row(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Image.asset(
                  'assets/imgTime.png',
                  scale: 2,
                ),
                const SizedBox(
                  width: 20,
                ),
                Expanded(
                  child: Column(
                    mainAxisAlignment: MainAxisAlignment.center,
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        'Tanggal / Jam',
                        textAlign: TextAlign.left,
                        style: TextStyle(fontFamily: 'Poppins',fontSize: 14),
                      ),
                      Text(
                        '${data.jadwal.day} - ${data.jadwal.month} - ${data.jadwal.year}  ${data.jadwal.hour}:${data.jadwal.minute}',
                        overflow: TextOverflow.ellipsis,
                        maxLines: 5,
                        textAlign: TextAlign.left,
                        style: TextStyle(fontFamily: 'Poppins',
                            fontSize: 16,
                            fontWeight: FontWeight.w600),
                      )
                    ],
                  ),
                )
              ],
            ),
            const SizedBox(
              height: 10,
            ),
            Row(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Image.asset(
                  'assets/imgTelp.png',
                  scale: 2,
                ),
                const SizedBox(
                  width: 20,
                ),
                Expanded(
                  child: Column(
                    mainAxisAlignment: MainAxisAlignment.center,
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        'No Telp',
                        textAlign: TextAlign.left,
                        style: TextStyle(fontFamily: 'Poppins',fontSize: 14),
                      ),
                      InkWell(
                        onTap: () async {
                          final Uri whatsappUrl = Uri.parse("whatsapp://send?phone=${data.dataCustomer.telp}&text=${Uri.encodeComponent('Selamat Siang ${data.dataCustomer.nama}')}");
                          if (await canLaunch(whatsappUrl.toString())) {
                            await launch(whatsappUrl.toString());
                          } else {
                            throw 'Could not launch $whatsappUrl';
                          }
                        },
                        child: Text(
                          data.dataCustomer.telp,
                          overflow: TextOverflow.ellipsis,
                          maxLines: 5,
                          textAlign: TextAlign.left,
                          style: TextStyle(fontFamily: 'Poppins',
                              decoration: TextDecoration.underline                         ,
                              fontSize: 16,
                              fontWeight: FontWeight.w600),
                        ),
                      )
                    ],
                  ),
                )
              ],
            ),
            const SizedBox(
              height: 10,
            ),
            Row(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Image.asset(
                  'assets/imgLocation.png',
                  scale: 2,
                ),
                const SizedBox(
                  width: 20,
                ),
                Expanded(
                  child: Column(
                    mainAxisAlignment: MainAxisAlignment.center,
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        'Alamat',
                        textAlign: TextAlign.left,
                        style: TextStyle(fontFamily: 'Poppins',fontSize: 14),
                      ),
                      InkWell(
                        onTap:() async => _openMap(context, data.dataCustomer.alamat,),
                        child: Text(
                          data.dataCustomer.alamat,
                          overflow: TextOverflow.ellipsis,
                          maxLines: 5,
                          textAlign: TextAlign.left,
                          style: TextStyle(fontFamily: 'Poppins',
                              fontSize: 14,
                              fontWeight: FontWeight.w600),
                        ),
                      )
                    ],
                  ),
                )
              ],
            ),
            const SizedBox(
              height: 15,
            ),
            Row(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Image.asset(
                  'assets/imgNote.png',
                  scale: 2,
                ),
                const SizedBox(
                  width: 20,
                ),
                Column(
                  mainAxisAlignment: MainAxisAlignment.center,
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      'Catatan',
                      textAlign: TextAlign.left,
                      style: TextStyle(fontFamily: 'Poppins',fontSize: 14),
                    ),
                    Text(
                      data.keterangan,
                      textAlign: TextAlign.left,
                      style: TextStyle(fontFamily: 'Poppins',
                          fontSize: 18,),
                    )
                  ],
                )
              ],
            ),
            const Spacer(),
          ],
        ),
      ),
    );
  }
}
