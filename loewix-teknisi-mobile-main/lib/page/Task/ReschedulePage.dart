import '../../service/provider/Pelaksanaan/RescheduleProvider.dart';
import 'package:flutter/material.dart';
import 'package:flutter_datetime_picker_plus/flutter_datetime_picker_plus.dart';
import 'package:provider/provider.dart';
import 'package:quickalert/quickalert.dart';

class ReschedulePage extends StatefulWidget {
  static const routeName = '/schedule_page';

  final String id;

  const ReschedulePage({super.key, required this.id});

  @override
  State<ReschedulePage> createState() => _ReschedulePageState();
}

class _ReschedulePageState extends State<ReschedulePage> {
  final _jadwal = TextEditingController();

  @override
  void dispose() {
    _jadwal.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    double width = MediaQuery
        .of(context)
        .size
        .width;
    return Scaffold(
        backgroundColor: Colors.white,
        appBar: AppBar(
          backgroundColor: Colors.white,
          title: Text(
            'Reschedule',
            style:
            TextStyle(fontFamily: 'Poppins',fontWeight: FontWeight.bold, fontSize: 30),
          ),
        ),
        body: SingleChildScrollView(
          child: Padding(
            padding: const EdgeInsets.symmetric(vertical: 20, horizontal: 15),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    SizedBox(
                      height: 50,
                      width: width * 0.7,
                      child: TextField(
                        controller: _jadwal,
                        decoration: const InputDecoration(
                          border: OutlineInputBorder(),
                        ),
                      ),
                    ),
                    SizedBox(
                      width: width * 0.20,
                      height: 50,
                      child: ElevatedButton(
                          onPressed: () {
                            DatePicker.showDateTimePicker(
                              context,
                              showTitleActions: true,
                              onChanged: (date) {
                                _jadwal.text = date.toString();
                                print('change $date');
                                print('hasil tanggal =$date');
                              },
                              onConfirm: (date) {
                                setState(() {});
                                _jadwal.text = date.toString();
                                print('harusnya berubah');
                              },
                              currentTime: DateTime.now(),
                              locale: LocaleType.en,
                            );
                          },
                          style: ElevatedButton.styleFrom(
                            foregroundColor: Colors.white, backgroundColor: Colors.blue, // Warna teks putih
                            padding: const EdgeInsets.symmetric(
                                horizontal: 20,
                                vertical: 10), // Padding button
                            shape: RoundedRectangleBorder(
                              borderRadius: BorderRadius.circular(
                                  8.0), // Sudut melengkung
                            ),
                          ),
                          child: const Center(
                            child: Icon(
                              Icons.calendar_month,
                              size: 32,
                            ),
                          )),
                    )
                  ],
                ),
                const SizedBox(height: 20,),
                SizedBox(
                  height: 50,
                  width: width * 0.25,
                  child: ElevatedButton(
                    onPressed: () async {
                      QuickAlert.show(
                          context: context,
                          type: QuickAlertType.loading,
                          title: 'Mohon Tunggu',
                          text: 'Sedang Mengirim data'
                      );

                      Provider.of<RescheduleProvider>(context, listen: false)
                          .doReschedule(_jadwal.text, widget.id).then((value) {
                        print(value);

                        if (value.toString() == 'Reschedule jadwal berhasil') {
                          Navigator.pop(context);
                          QuickAlert.show(
                              context: context,
                              type: QuickAlertType.success,
                              title: 'Penjadwalan Ulang Berhasil!',
                              text: value
                          );
                        }else{
                          Navigator.pop(context);
                          QuickAlert.show(
                              context: context,
                              type: QuickAlertType.error,
                              title: 'Penjadwalan Ulang Gagal!',
                              text: value
                          );
                        }
                      });
                      print(widget.id);
                    },
                    style: ElevatedButton.styleFrom(
                      foregroundColor: Colors.white, backgroundColor: Colors.blue, // Warna teks putih
                      padding: const EdgeInsets.symmetric(
                          horizontal: 20, vertical: 10), // Padding button
                      shape: RoundedRectangleBorder(
                        borderRadius:
                        BorderRadius.circular(8.0), // Sudut melengkung
                      ),
                    ),
                    child: const Text(
                      'KIRIM',
                      style: TextStyle(
                        fontSize: 16.0, // Ukuran font
                        fontWeight: FontWeight.bold, // Teks tebal
                      ),
                    ),
                  ),
                )
              ],
            ),
          ),
        ));
  }
}
