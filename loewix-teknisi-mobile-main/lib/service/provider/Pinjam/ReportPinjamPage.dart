// import 'dart:convert';
// import 'dart:io';
//
// import 'package:flutter/material.dart';
// import 'package:image_picker/image_picker.dart';
// import 'package:provider/provider.dart';
// import 'package:quickalert/quickalert.dart';
//
// class ReportTaskPage extends StatefulWidget {
//   static const routeName = '/report_done';
//   final List<int> data;
//
//   const ReportTaskPage({super.key, required this.data});
//
//   @override
//   State<ReportTaskPage> createState() => _ReportTaskPageState();
// }
//
// class _ReportTaskPageState extends State<ReportTaskPage> {
//   final _keterangan = TextEditingController();
//
//   File? imagePath1;
//   String? imageData1;
//   XFile? gambar1;
//
//   File? imagePath2;
//   String? imageData2;
//   XFile? gambar2;
//
//   ImagePicker picker = ImagePicker();
//
//   Future<void> getImage(int imageNumber) async {
//     var dataImage = await picker.pickImage(source: ImageSource.gallery);
//
//     setState(() {
//       if (dataImage != null) {
//         if (imageNumber == 1) {
//           imagePath1 = File(dataImage.path);
//           imageData1 = base64Encode(imagePath1!.readAsBytesSync());
//           gambar1 = dataImage;
//         } else if (imageNumber == 2) {
//           imagePath2 = File(dataImage.path);
//           imageData2 = base64Encode(imagePath2!.readAsBytesSync());
//           gambar2 = dataImage;
//         }
//       }
//     });
//   }
//
//   @override
//   Widget build(BuildContext context) {
//     return Scaffold(
//       appBar: AppBar(
//         title: Text(
//           'Laporan Penyelesaian',
//           style: TextStyle(fontFamily: 'Poppins',
//             fontWeight: FontWeight.w600,
//             color: Color(0XFF4c59d6),
//           ),
//         ),
//         backgroundColor: Colors.white,
//       ),
//       body: Padding(
//         padding: const EdgeInsets.symmetric(vertical: 20, horizontal: 15),
//         child: SingleChildScrollView(
//           child: Column(
//             children: [
//               _buildTextFormField('Keterangan', _keterangan),
//               const SizedBox(height: 20),
//               _buildImagePickerRow('Gambar 1', () => getImage(1), imagePath1),
//               const SizedBox(height: 20),
//               _buildImagePickerRow('Gambar 2', () => getImage(2), imagePath2),
//               const SizedBox(height: 30),
//               SizedBox(
//                 width: 200,
//                 height: 50,
//                 child: ElevatedButton(
//                   onPressed: _submitReport,
//                   style: ElevatedButton.styleFrom(
//                     foregroundColor: Colors.white,
//                     backgroundColor: Color(0XFF4c59d6),
//                     padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 10),
//                     shape: RoundedRectangleBorder(
//                       borderRadius: BorderRadius.circular(8.0),
//                     ),
//                   ),
//                   child: Text(
//                     'Kirim',
//                     style: GoogleFonts.openSans(fontWeight: FontWeight.bold, fontSize: 16),
//                   ),
//                 ),
//               ),
//             ],
//           ),
//         ),
//       ),
//     );
//   }
//
//   Widget _buildTextFormField(String label, TextEditingController controller) {
//     return TextFormField(
//       controller: controller,
//       decoration: InputDecoration(
//         labelText: label,
//         border: OutlineInputBorder(
//           borderRadius: BorderRadius.circular(8),
//         ),
//         prefixIcon: Icon(Icons.description),
//       ),
//       maxLines: 5,
//     );
//   }
//
//   Widget _buildImagePickerRow(String label, VoidCallback onPressed, File? imagePath) {
//     return Column(
//       crossAxisAlignment: CrossAxisAlignment.start,
//       children: [
//         Row(
//           children: [
//             Text(label, style: TextStyle(fontFamily: 'Poppins',fontSize: 16, fontWeight: FontWeight.w500)),
//             const Spacer(),
//             ElevatedButton(
//               onPressed: onPressed,
//               style: ElevatedButton.styleFrom(
//                 primary: Color(0XFF4c59d6),
//                 shape: RoundedRectangleBorder(
//                   borderRadius: BorderRadius.circular(10),
//                 ),
//               ),
//               child: const Icon(Icons.image, color: Colors.white),
//             ),
//           ],
//         ),
//         const SizedBox(height: 10),
//         if (imagePath != null)
//           ClipRRect(
//             borderRadius: BorderRadius.circular(10),
//             child: Image.file(
//               imagePath,
//               width: double.infinity,
//               height: 200,
//               fit: BoxFit.contain,
//             ),
//           ),
//       ],
//     );
//   }
//
//   Future<void> _submitReport() async {
//     if (_keterangan.text.isEmpty || imagePath1 == null) {
//       QuickAlert.show(
//         context: context,
//         type: QuickAlertType.info,
//         title: 'Wajib Isi Field!',
//         text: 'Field Permasalahan dan Gambar 1 wajib diisi',
//       );
//       return;
//     }
//
//     QuickAlert.show(
//       context: context,
//       type: QuickAlertType.loading,
//       title: 'Mohon Tunggu',
//       text: 'Mencoba mengirim data laporan',
//     );
//
//     final upload = Provider.of<ReportTaskProvider>(context, listen: false);
//     List<List<int>> dataGambar = [];
//
//     if (imagePath1 != null) {
//       final bytes1 = await gambar1!.readAsBytes();
//       final newBytes1 = await upload.compressImage(bytes1);
//       dataGambar.add(newBytes1);
//     }
//
//     if (imagePath2 != null) {
//       final bytes2 = await gambar2!.readAsBytes();
//       final newBytes2 = await upload.compressImage(bytes2);
//       dataGambar.add(newBytes2);
//     }
//
//     await upload.uploadReport(dataGambar, _keterangan.text, widget.data[0]).then((value) async {
//       Navigator.pop(context);
//
//       //if (value == 'Task updated successfully') {
//       if (0 == 0) {
//         QuickAlert.show(
//           context: context,
//           type: QuickAlertType.success,
//           title: 'Tugas Selesai!',
//           text: 'Kegiatan selesai!',
//           onConfirmBtnTap: () async {
//             await Provider.of<TaskProvider>(context, listen: false).getTask(widget.data[1]);
//             Navigator.pop(context);
//             Future.delayed(Duration(milliseconds: 100));
//             Navigator.pop(context);
//           },
//         );
//       } else {
//         QuickAlert.show(
//           context: context,
//           type: QuickAlertType.error,
//           title: 'Gagal diselesaikan',
//           text: 'ada masalah pada saat menyelesaikan',
//         );
//       }
//     });
//   }
// }
