import 'dart:io';
import 'package:flutter/material.dart';
import 'package:flutter_sound/flutter_sound.dart';
import 'package:image_picker/image_picker.dart';
import 'package:intl/intl.dart';
import 'package:permission_handler/permission_handler.dart';
import 'package:provider/provider.dart';
import 'package:quickalert/quickalert.dart';

import '../../../service/provider/Pelaksanaan/ReportPelaksanaanProvider.dart';
import '../../../service/provider/Task/DetailTaskGetProvider.dart';

class VoiceRecordPage extends StatefulWidget {
  static const routeName = '/recordd';

  final List<dynamic> data;
  const VoiceRecordPage({super.key,
    required this.data
  });

  @override
  State<VoiceRecordPage> createState() => _VoiceRecordPageState();
}

class _VoiceRecordPageState extends State<VoiceRecordPage> {
  final FlutterSoundRecorder _recorder = FlutterSoundRecorder();
  final FlutterSoundPlayer _player = FlutterSoundPlayer();
  bool _isRecording = false;
  String? recordedPath;
  Duration _recordDuration = Duration.zero;
  late final TextEditingController _keterangan;
  late final TextEditingController _permasalahan;
  late final TextEditingController _solusi;
  File? imageFile;

  @override
  void initState() {
    super.initState();
    _keterangan = TextEditingController();
    _permasalahan = TextEditingController();
    _solusi = TextEditingController();
    _player.openPlayer();
    _recorder.openRecorder();
  }

  @override
  void dispose() {
    _player.closePlayer();
    _recorder.closeRecorder();
    _keterangan.dispose();
    _permasalahan.dispose();
    _solusi.dispose();
    super.dispose();
  }

  Future<void> _startRecording() async {
    final status = await Permission.microphone.request();
    if (status != PermissionStatus.granted) return;

    final path = "/sdcard/Download/recording_${DateFormat('yyyyMMdd_HHmmss').format(DateTime.now())}.aac";
    await _recorder.startRecorder(
      toFile: path,
      codec: Codec.aacADTS,
    );

    setState(() {
      _isRecording = true;
      _recordDuration = Duration.zero;
    });

    _updateTimer();
  }

  void _updateTimer() async {
    while (_isRecording) {
      await Future.delayed(const Duration(seconds: 1));
      if (_isRecording) {
        setState(() => _recordDuration += const Duration(seconds: 1));
      }
    }
  }

  Future<void> _stopRecording() async {
    final path = await _recorder.stopRecorder();
    setState(() {
      _isRecording = false;
      recordedPath = path;
    });
  }

  Future<void> _pickImage() async {
    final picker = ImagePicker();
    final picked = await picker.pickImage(source: ImageSource.camera);
    if (picked != null) {
      setState(() => imageFile = File(picked.path));
    }
  }

  Future<void> _showConfirmDialog() async {
    if (recordedPath == null || _keterangan.text.isEmpty) {
      QuickAlert.show(
        context: context,
        type: QuickAlertType.error,
        title: 'Oops!',
        text: 'Rekaman dan keterangan wajib diisi!',
      );
      return;
    }

    QuickAlert.show(
      context: context,
      type: QuickAlertType.confirm,
      title: 'Konfirmasi',
      text: 'Apakah kamu yakin ingin mengirim laporan ini?',
      confirmBtnText: 'Ya',
      cancelBtnText: 'Batal',
      onConfirmBtnTap: () async {
        // Navigator.pop(context);
        // // await Provider.of<ReportPelaksanaanProvider>(
        // //   context,
        // //   listen: false,
        // // ).upload(
        // //   [image],
        // //   widget.data[1].toString(),
        // //   widget.data[0].toString(),
        // //   _permasalahan.text,
        // //   _solusi.text,
        // //   _keterangan.text,
        // //   widget.data[2].toString(),
        // //   widget.data[3].toString(),
        // //   File(recordedPath!)
        // // );
        // final voiceBytes = await File(recordedPath!).readAsBytes();
        // if (imageFile != null) {
        //   final imageBytes = await imageFile!.readAsBytes();
        //   await Provider.of<ReportPelaksanaanProvider>(
        //     context,
        //     listen: false,
        //   ).upload(
        //       [imageBytes], // <- ini jadi List<List<int>> dengan 1 gambar
        //       widget.data[1].toString(),
        //       widget.data[0].toString(),
        //       _permasalahan.text,
        //       _solusi.text,
        //       _keterangan.text,
        //       widget.data[2].toString(),
        //       widget.data[3].toString(),
        //       voiceBytes
        //   );
        // } else {
        //   await Provider.of<ReportPelaksanaanProvider>(
        //     context,
        //     listen: false,
        //   ).upload(
        //       [], // kirim list kosong jika tidak ada gambar
        //       widget.data[1].toString(),
        //       widget.data[0].toString(),
        //       _permasalahan.text,
        //       _solusi.text,
        //       _keterangan.text,
        //       widget.data[2].toString(),
        //       widget.data[3].toString(),
        //       voiceBytes
        //   );
        // }
        // Provider.of<DetailTaskGetProvider>(context, listen: false).getTask(widget.data[0].toString());
        // Navigator.pop(context);
        // Navigator.pop(context);
      },
    );
  }

  Widget _buildRecordSection() {
    return Column(
      children: [
        Text(
          _isRecording
              ? 'Merekam... ${_recordDuration.inMinutes.remainder(60).toString().padLeft(2, '0')}:${_recordDuration.inSeconds.remainder(60).toString().padLeft(2, '0')}'
              : 'Tekan tombol untuk mulai merekam',
          style: const TextStyle(fontSize: 16),
        ),
        const SizedBox(height: 10),
        IconButton(
          icon: Icon(_isRecording ? Icons.stop : Icons.mic),
          iconSize: 48,
          color: Colors.white,
          onPressed: _isRecording ? _stopRecording : _startRecording,
          style: IconButton.styleFrom(
            backgroundColor: Colors.teal,
            shape: const CircleBorder(),
          ),
        ),
        if (recordedPath != null)
          Column(
            children: [
              const SizedBox(height: 10),
              const Text('Preview Rekaman'),
              IconButton(
                icon: const Icon(Icons.play_arrow),
                onPressed: () => _player.startPlayer(fromURI: recordedPath),
              ),
            ],
          ),
      ],
    );
  }

  Widget _buildTextField(String label, TextEditingController controller) {
    return TextField(
      controller: controller,
      decoration: InputDecoration(
        labelText: label,
        border: const OutlineInputBorder(),
      ),
      maxLines: null,
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Memulai Percakapan'),
        backgroundColor: Colors.teal,
      ),
      body: Padding(
        padding: const EdgeInsets.all(16.0),
        child: ListView(
          children: [
            _buildRecordSection(),
            const SizedBox(height: 20),
            ElevatedButton.icon(
              onPressed: _pickImage,
              icon: const Icon(Icons.camera_alt),
              label: const Text('Ambil Foto'),
            ),
            if (imageFile != null)
              Padding(
                padding: const EdgeInsets.only(top: 10),
                child: Image.file(imageFile!, height: 150),
              ),
            const SizedBox(height: 20),
            _buildTextField("Permasalahan", _permasalahan),
            const SizedBox(height: 10),
            _buildTextField("Solusi", _solusi),
            const SizedBox(height: 10),
            _buildTextField("Keterangan *", _keterangan),
            const SizedBox(height: 20),
            ElevatedButton.icon(
              onPressed: _showConfirmDialog,
              icon: const Icon(Icons.upload_file),
              label: const Text("Kirim"),
              style: ElevatedButton.styleFrom(
                backgroundColor: Colors.teal,
                minimumSize: const Size.fromHeight(50),
              ),
            )
          ],
        ),
      ),
    );
  }
}
