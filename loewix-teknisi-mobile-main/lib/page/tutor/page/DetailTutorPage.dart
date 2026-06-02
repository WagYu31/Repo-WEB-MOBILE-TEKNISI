// import 'package:flutter/material.dart';
// import 'package:absen_teknisi/page/tutor/model/TutorResponse.dart';
//
// import 'PdfViewerPage.dart';
// import 'VideoViewerPage.dart';
//
// class DetailTutorPage extends StatefulWidget {
//   final Data data;
//
//   const DetailTutorPage({super.key, required this.data});
//
//   @override
//   State<DetailTutorPage> createState() => _DetailTutorPageState();
// }
//
// class _DetailTutorPageState extends State<DetailTutorPage> {
//   String getFileType(String fileName) {
//     if (fileName.endsWith('.pdf')) {
//       return 'PDF';
//     } else if (fileName.endsWith('.mp4')) {
//       return 'Video';
//     } else {
//       return 'Unknown';
//     }
//   }
//
//   @override
//   Widget build(BuildContext context) {
//     return Scaffold(
//       appBar: AppBar(
//         title: const Text('Detail Tutorial'),
//       ),
//       body: Column(
//         crossAxisAlignment: CrossAxisAlignment.start,
//         children: [
//           Padding(
//             padding: const EdgeInsets.all(16.0),
//             child: Column(
//               crossAxisAlignment: CrossAxisAlignment.start,
//               children: [
//                 Text(
//                   widget.data.title,
//                   style: const TextStyle(
//                     fontWeight: FontWeight.bold,
//                     fontSize: 20,
//                   ),
//                 ),
//                 const SizedBox(height: 8),
//                 Text(
//                   widget.data.description,
//                   style: const TextStyle(
//                     color: Colors.grey,
//                   ),
//                 ),
//                 const SizedBox(height: 16),
//                 const Text(
//                   'Media:',
//                   style: TextStyle(fontWeight: FontWeight.bold, fontSize: 16),
//                 ),
//                 const SizedBox(height: 8),
//                 ListView(
//                   shrinkWrap: true,
//                   children: [
//                     widget.data.media1 != null
//                         ? InkWell(
//                             onTap: () {
//                               if (getFileType(widget.data.media1!) == 'PDF') {
//                                 Navigator.push(
//                                   context,
//                                   MaterialPageRoute(
//                                     builder: (context) => PdfOnlineViewerPage(
//                                         pdfUrl: widget.data.media1!),
//                                   ),
//                                 );
//                               } else if (getFileType(widget.data.media1!) ==
//                                   'Video') {
//                                 Navigator.push(
//                                   context,
//                                   MaterialPageRoute(
//                                     builder: (context) => VideoPlayerScreen(
//                                         videoUrl: widget.data.media1!),
//                                   ),
//                                 );
//                               }
//                             },
//                             child: ListTile(
//                               title: Text(getFileType(widget.data.media1!)),
//                               trailing: const Icon(Icons.arrow_forward_ios),
//                             ),
//                           )
//                         : Container(),
//                     widget.data.media2 != null
//                         ? InkWell(
//                             onTap: () {
//                               if (getFileType(widget.data.media2!) == 'PDF') {
//                                 Navigator.push(
//                                   context,
//                                   MaterialPageRoute(
//                                     builder: (context) => PdfOnlineViewerPage(
//                                         pdfUrl: widget.data.media2!),
//                                   ),
//                                 );
//                               } else if (getFileType(widget.data.media2!) ==
//                                   'Video') {
//                                 Navigator.push(
//                                   context,
//                                   MaterialPageRoute(
//                                     builder: (context) => VideoPlayerScreen(
//                                         videoUrl: widget.data.media2!),
//                                   ),
//                                 );
//                               }
//                             },
//                             child: ListTile(
//                               title: Text(getFileType(widget.data.media2!)),
//                               trailing: const Icon(Icons.arrow_forward_ios),
//                             ),
//                           )
//                         : Container(),
//                   ],
//                 ),
//                 // Expanded(child: PdfView(controller: pdfController)),
//               ],
//             ),
//           ),
//         ],
//       ),
//     );
//   }
// }
