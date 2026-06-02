import 'package:flutter/material.dart';
import 'package:http/http.dart' as http;
import 'package:path_provider/path_provider.dart';
// import 'package:pdfx/pdfx.dart';
import 'dart:io';

class PdfOnlineViewerPage extends StatefulWidget {
  final String pdfUrl;

  const PdfOnlineViewerPage({Key? key, required this.pdfUrl}) : super(key: key);

  @override
  _PdfOnlineViewerPageState createState() => _PdfOnlineViewerPageState();
}

class _PdfOnlineViewerPageState extends State<PdfOnlineViewerPage> {
  // late PdfController _pdfController;
  bool _isLoading = true;

  @override
  void initState() {
    super.initState();
    _loadPdfFromUrl('https://grav-tech.com/jadwal-3/api/storage/app/pdf/${widget.pdfUrl}');
  }

  Future<void> _loadPdfFromUrl(String url) async {
    try {
      // Download file PDF
      final response = await http.get(Uri.parse(url));
      if (response.statusCode == 200) {
        // Simpan file ke dalam cache sementara
        final file = File('${(await getTemporaryDirectory()).path}/temp.pdf');
        await file.writeAsBytes(response.bodyBytes);

        // Buka file menggunakan PdfController
        setState(() {
          // _pdfController = PdfController(
          //   document: PdfDocument.openFile(file.path),
          // );
          _isLoading = false;
        });
      } else {
        throw Exception('Gagal mengunduh file PDF.');
      }
    } catch (e) {
      print('Error: $e');
      setState(() {
        _isLoading = false;
      });
    }
  }

  @override
  void dispose() {
    // _pdfController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text("Online PDF Viewer"),
        backgroundColor: const Color(0XFF4c59d6),
      ),
      body: _isLoading
          ? const Center(child: CircularProgressIndicator()) : Text('Fitur sedang dihentikan')
          // : PdfView(controller: _pdfController),
    );
  }
}
