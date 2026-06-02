// import 'package:absen_teknisi/page/tutor/page/PdfViewerPage.dart';
// import 'package:absen_teknisi/page/tutor/provider/TutorialAllProvider.dart';
// import 'package:flutter/material.dart';
// import 'package:provider/provider.dart';
// import '../../../utils/state.dart';
// import 'DetailTutorPage.dart';
//
// class TutorialPage extends StatelessWidget {
//   static const routeName = '/TutorialPage';
//   const TutorialPage({super.key});
//
//   @override
//   Widget build(BuildContext context) {
//     return Scaffold(
//       appBar: AppBar(
//         title: const Text('Tutorial'),
//         actions: [
//           IconButton(
//             icon: const Icon(Icons.refresh),
//             onPressed: () {
//               WidgetsBinding.instance.addPostFrameCallback((_) {
//                 Provider.of<TutorialAllProvider>(context, listen: false).getAllTutor();
//               });
//             },
//           )
//         ],
//       ),
//       body: SingleChildScrollView(
//         child: Consumer<TutorialAllProvider>(
//             builder: (context, provider, child) {
//               if (provider.state == ResultState.loading) {
//                 return const Center(child: CircularProgressIndicator());
//               } else if (provider.state == ResultState.hasData) {
//                 return SizedBox(
//                   width: double.infinity,
//                   height: MediaQuery.of(context).size.height,
//                   child: ListView.separated(
//                       itemCount: provider.tutorResponse.data.length,
//                       separatorBuilder: (context, index) => Divider(
//                         color: Colors.grey,
//                         height: 1,
//                       ),
//                       itemBuilder: (context, index) {
//                         var tutor = provider.tutorResponse.data[index];
//                         return ListTile(
//                           contentPadding: const EdgeInsets.all(10),
//                           leading: Icon(Icons.school, color: Colors.blueAccent),
//                           title: Text(
//                             tutor.title,
//                             style: TextStyle(
//                               color: Colors.black,
//                               fontWeight: FontWeight.bold,
//                             ),
//                           ),
//                           subtitle: Text(
//                             tutor.description,
//                             style: TextStyle(color: Colors.grey[600]),
//                           ),
//                           trailing: Icon(Icons.arrow_forward_ios, color: Colors.grey),
//                           onTap: () {
//                             Navigator.push(
//                               context,
//                               MaterialPageRoute(
//                                 builder: (context) => DetailTutorPage(data: tutor,),
//                                 // builder: (context) => PdfOnlineViewerPage(pdfUrl: 'https://loewix.com/jadwal-3/api/storage/app/pdf/test.pdf'),
//                               ),
//                             );
//                           },
//                         );
//                       }),
//                 );
//               } else if (provider.state == ResultState.noData) {
//                 return const Center(child: Text('Data tidak ditemukan'));
//               } else if (provider.state == ResultState.error) {
//                 return const Center(child: Text('Terjadi kesalahan'));
//               } else {
//                 return const Center(child: Text(''));
//               }
//             }
//         ),
//       ),
//     );
//   }
// }
