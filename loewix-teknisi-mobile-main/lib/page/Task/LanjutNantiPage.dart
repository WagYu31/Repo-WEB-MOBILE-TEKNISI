import 'dart:io';

import 'package:flutter/material.dart';
import 'package:flutter_datetime_picker_plus/flutter_datetime_picker_plus.dart';
import 'package:iconsax/iconsax.dart';
import 'package:image_picker/image_picker.dart';
import 'package:intl/intl.dart';
import 'package:provider/provider.dart';
import 'package:quickalert/quickalert.dart';

import '../../page/container/HomePage.dart';
import '../../service/provider/Pelaksanaan/LanjutNantiProvider.dart';
import '../../service/provider/Task/TaskGetAllProvider.dart';
import '../../service/provider/Task/DetailTaskGetProvider.dart';
import '../../service/provider/preferences/PreferencesIDProvider.dart';

class LanjutNantiPage extends StatefulWidget {
  static const routeName = '/lanjut_nanti';
  final List<dynamic> data;

  const LanjutNantiPage({super.key, required this.data});

  @override
  State<LanjutNantiPage> createState() => _LanjutNantiPageState();
}

class _LanjutNantiPageState extends State<LanjutNantiPage> {
  // Modern color scheme
  static const Color _primaryBlue = Color(0xFF2563EB);
  static const Color _successGreen = Color(0xFF10B981);
  static const Color _warningAmber = Color(0xFFF59E0B);
  static const Color _errorRed = Color(0xFFEF4444);
  static const Color _textPrimary = Color(0xFF1F2937);
  static const Color _textSecondary = Color(0xFF6B7280);
  static const Color _bgLight = Color(0xFFF9FAFB);
  static const Color _borderColor = Color(0xFFE5E7EB);

  // Controllers
  final _keteranganController = TextEditingController();
  final _formKey = GlobalKey<FormState>();

  // State
  DateTime? _selectedDateTime;
  bool _isSubmitting = false;
  final ImagePicker _picker = ImagePicker();

  // Image data - simplified using list
  final List<XFile?> _images = List.filled(4, null);
  XFile? _video;

  @override
  void dispose() {
    _keteranganController.dispose();
    super.dispose();
  }

  Future<void> _pickImage(int index) async {
    try {
      final XFile? image = await _picker.pickImage(
        source: ImageSource.gallery,
        imageQuality: 80,
        maxWidth: 1920,
        maxHeight: 1080,
      );

      if (image != null && mounted) {
        setState(() {
          _images[index] = image;
        });
      }
    } catch (e) {
      if (mounted) {
        _showError('Gagal memilih gambar: ${e.toString()}');
      }
    }
  }

  Future<void> _pickVideo() async {
    try {
      final XFile? video = await _picker.pickVideo(
        source: ImageSource.gallery,
        maxDuration: const Duration(minutes: 5),
      );

      if (video != null && mounted) {
        setState(() {
          _video = video;
        });
      }
    } catch (e) {
      if (mounted) {
        _showError('Gagal memilih video: ${e.toString()}');
      }
    }
  }

  void _removeImage(int index) {
    setState(() {
      _images[index] = null;
    });
  }

  void _removeVideo() {
    setState(() {
      _video = null;
    });
  }

  void _showDatePicker() {
    DatePicker.showDateTimePicker(
      context,
      showTitleActions: true,
      minTime: DateTime.now(),
      maxTime: DateTime.now().add(const Duration(days: 365)),
      onConfirm: (date) {
        if (mounted) {
          setState(() { 
            _selectedDateTime = date;
          });
        }
      },
      currentTime: _selectedDateTime ?? DateTime.now().add(const Duration(hours: 1)),
      locale: LocaleType.id,
    );
  }

  void _showError(String message) {
    if (!mounted) return;
    QuickAlert.show(
      context: context,
      type: QuickAlertType.error,
      title: 'Error',
      text: message,
    );
  }

  void _showInfo(String title, String message) {
    if (!mounted) return;
    QuickAlert.show(
      context: context,
      type: QuickAlertType.info,
      title: title,
      text: message,
    );
  }

  bool _validateForm() {
    if (_selectedDateTime == null) {
      _showInfo('Jadwal Wajib Diisi', 'Silakan pilih jadwal untuk melanjutkan tugas.');
      return false;
    }

    if (_selectedDateTime!.isBefore(DateTime.now())) {
      _showInfo('Jadwal Tidak Valid', 'Jadwal harus lebih dari waktu sekarang.');
      return false;
    }

    if (_images[0] == null) {
      _showInfo('Foto Wajib Diisi', 'Minimal satu foto dokumentasi harus diupload.');
      return false;
    }

    return true;
  }

  Future<void> _submitForm() async {
    if (!_validateForm()) return;

    setState(() {
      _isSubmitting = true;
    });

    try {
      final upload = context.read<LanjutNantiProvider>();
      final taskProvider = context.read<TaskGetAllProvider>();
      final idProvider = context.read<PreferencesIDProvider>();
      final detailProvider = context.read<DetailTaskGetProvider>();

      // Compress and prepare images
      List<List<int>> dataGambar = [];

      for (int i = 0; i < _images.length; i++) {
        if (_images[i] != null) {
          final bytes = await _images[i]!.readAsBytes();
          final compressed = await upload.compressImage(bytes);
          dataGambar.add(compressed);
        }
      }

      // Add video if exists
      if (_video != null) {
        final videoBytes = await _video!.readAsBytes();
        final compressedVideo = await upload.compressImage(videoBytes);
        dataGambar.add(compressedVideo);
      }

      final result = await upload.upload(
        dataGambar,
        widget.data[1].toString(),
        widget.data[0].toString(),
        _selectedDateTime.toString(),
        _keteranganController.text,
        widget.data[2].toString(),
        widget.data[3].toString(),
      );

      if (!mounted) return;

      if (result == 'Pelaksanaan kegiatan berhasil diselesaikan') {
        await taskProvider.getTask();
        final teknisiId = idProvider.isUserRole;
        detailProvider.getTask(teknisiId);

        if (!mounted) return;

        QuickAlert.show(
          context: context,
          type: QuickAlertType.success,
          title: 'Berhasil!',
          text: 'Tugas dijadwalkan ulang.',
          onConfirmBtnTap: () {
            Navigator.pushAndRemoveUntil(
              context,
              MaterialPageRoute(builder: (context) => const HomePageAdmin()),
              (route) => false,
            );
          },
        );
      } else {
        _showError(result);
      }
    } catch (e) {
      if (mounted) {
        _showError('Terjadi kesalahan: ${e.toString()}');
      }
    } finally {
      if (mounted) {
        setState(() {
          _isSubmitting = false;
        });
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: _bgLight,
      appBar: _buildAppBar(),
      body: SafeArea(
        child: Stack(
          children: [
            Form(
              key: _formKey,
              child: SingleChildScrollView(
                padding: const EdgeInsets.all(20),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    _buildInfoCard(),
                    const SizedBox(height: 20),
                    _buildDateTimeSection(),
                    const SizedBox(height: 20),
                    _buildKeteranganSection(),
                    const SizedBox(height: 20),
                    _buildMediaSection(),
                    const SizedBox(height: 100),
                  ],
                ),
              ),
            ),
            _buildSubmitButton(),
            if (_isSubmitting) _buildLoadingOverlay(),
          ],
        ),
      ),
    );
  }

  PreferredSizeWidget _buildAppBar() {
    return AppBar(
      backgroundColor: Colors.white,
      elevation: 0,
      leading: IconButton(
        icon: Container(
          padding: const EdgeInsets.all(8),
          decoration: BoxDecoration(
            color: _bgLight,
            borderRadius: BorderRadius.circular(10),
          ),
          child: const Icon(Iconsax.arrow_left, size: 20, color: _textPrimary),
        ),
        onPressed: () => Navigator.pop(context),
      ),
      title: const Text(
        'Lanjut Nanti',
        style: TextStyle(
          fontFamily: 'Poppins',
          fontWeight: FontWeight.w600,
          fontSize: 18,
          color: _textPrimary,
        ),
      ),
      centerTitle: true,
    );
  }

  Widget _buildInfoCard() {
    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        gradient: LinearGradient(
          colors: [_warningAmber, _warningAmber.withValues(alpha: 0.8)],
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
        ),
        borderRadius: BorderRadius.circular(16),
      ),
      child: Row(
        children: [
          Container(
            padding: const EdgeInsets.all(12),
            decoration: BoxDecoration(
              color: Colors.white.withValues(alpha: 0.2),
              borderRadius: BorderRadius.circular(12),
            ),
            child: const Icon(Iconsax.calendar_edit, color: Colors.white, size: 24),
          ),
          const SizedBox(width: 14),
          const Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  'Jadwalkan Ulang Tugas',
                  style: TextStyle(
                    fontFamily: 'Poppins',
                    fontWeight: FontWeight.w600,
                    fontSize: 15,
                    color: Colors.white,
                  ),
                ),
                SizedBox(height: 4),
                Text(
                  'Pilih waktu baru untuk melanjutkan tugas ini',
                  style: TextStyle(
                    fontFamily: 'Poppins',
                    fontSize: 12,
                    color: Colors.white70,
                  ),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildDateTimeSection() {
    return _buildSectionCard(
      title: 'Jadwal Baru',
      icon: Iconsax.calendar,
      isRequired: true,
      child: InkWell(
        onTap: _showDatePicker,
        borderRadius: BorderRadius.circular(12),
        child: Container(
          padding: const EdgeInsets.all(16),
          decoration: BoxDecoration(
            color: _bgLight,
            borderRadius: BorderRadius.circular(12),
            border: Border.all(
              color: _selectedDateTime != null ? _primaryBlue : _borderColor,
              width: _selectedDateTime != null ? 2 : 1,
            ),
          ),
          child: Row(
            children: [
              Container(
                padding: const EdgeInsets.all(10),
                decoration: BoxDecoration(
                  color: _selectedDateTime != null
                      ? _primaryBlue.withValues(alpha: 0.1)
                      : _borderColor.withValues(alpha: 0.5),
                  borderRadius: BorderRadius.circular(10),
                ),
                child: Icon(
                  Iconsax.calendar_1,
                  size: 22,
                  color: _selectedDateTime != null ? _primaryBlue : _textSecondary,
                ),
              ),
              const SizedBox(width: 14),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      _selectedDateTime != null
                          ? DateFormat('EEEE, dd MMMM yyyy', 'id_ID')
                              .format(_selectedDateTime!)
                          : 'Pilih Tanggal & Waktu',
                      style: TextStyle(
                        fontFamily: 'Poppins',
                        fontWeight: FontWeight.w500,
                        fontSize: 14,
                        color: _selectedDateTime != null ? _textPrimary : _textSecondary,
                      ),
                    ),
                    if (_selectedDateTime != null) ...[
                      const SizedBox(height: 2),
                      Text(
                        DateFormat('HH:mm', 'id_ID').format(_selectedDateTime!),
                        style: const TextStyle(
                          fontFamily: 'Poppins',
                          fontSize: 13,
                          color: _textSecondary,
                        ),
                      ),
                    ],
                  ],
                ),
              ),
              Icon(
                Iconsax.arrow_right_3,
                size: 20,
                color: _selectedDateTime != null ? _primaryBlue : _textSecondary,
              ),
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildKeteranganSection() {
    return _buildSectionCard(
      title: 'Keterangan',
      icon: Iconsax.note_text,
      child: TextFormField(
        controller: _keteranganController,
        minLines: 3,
        maxLines: 5,
        style: const TextStyle(
          fontFamily: 'Poppins',
          fontSize: 14,
          color: _textPrimary,
        ),
        decoration: InputDecoration(
          hintText: 'Tuliskan alasan atau keterangan...',
          hintStyle: const TextStyle(
            fontFamily: 'Poppins',
            fontSize: 14,
            color: _textSecondary,
          ),
          filled: true,
          fillColor: _bgLight,
          border: OutlineInputBorder(
            borderRadius: BorderRadius.circular(12),
            borderSide: const BorderSide(color: _borderColor),
          ),
          enabledBorder: OutlineInputBorder(
            borderRadius: BorderRadius.circular(12),
            borderSide: const BorderSide(color: _borderColor),
          ),
          focusedBorder: OutlineInputBorder(
            borderRadius: BorderRadius.circular(12),
            borderSide: const BorderSide(color: _primaryBlue, width: 2),
          ),
          contentPadding: const EdgeInsets.all(16),
        ),
      ),
    );
  }

  Widget _buildMediaSection() {
    return _buildSectionCard(
      title: 'Dokumentasi',
      icon: Iconsax.gallery,
      isRequired: true,
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          const Text(
            'Minimal 1 foto dokumentasi',
            style: TextStyle(
              fontFamily: 'Poppins',
              fontSize: 12,
              color: _textSecondary,
            ),
          ),
          const SizedBox(height: 12),
          // Image grid
          GridView.builder(
            shrinkWrap: true,
            physics: const NeverScrollableScrollPhysics(),
            gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
              crossAxisCount: 2,
              crossAxisSpacing: 12,
              mainAxisSpacing: 12,
              childAspectRatio: 1,
            ),
            itemCount: 4,
            itemBuilder: (context, index) => _buildImagePicker(index),
          ),
          const SizedBox(height: 16),
          // Video picker
          _buildVideoPicker(),
        ],
      ),
    );
  }

  Widget _buildImagePicker(int index) {
    final hasImage = _images[index] != null;

    return InkWell(
      onTap: () => hasImage ? null : _pickImage(index),
      borderRadius: BorderRadius.circular(12),
      child: Container(
        decoration: BoxDecoration(
          color: hasImage ? Colors.transparent : _bgLight,
          borderRadius: BorderRadius.circular(12),
          border: Border.all(
            color: hasImage ? _successGreen : _borderColor,
            width: hasImage ? 2 : 1,
            style: hasImage ? BorderStyle.solid : BorderStyle.solid,
          ),
        ),
        child: hasImage
            ? Stack(
                fit: StackFit.expand,
                children: [
                  ClipRRect(
                    borderRadius: BorderRadius.circular(10),
                    child: Image.file(
                      File(_images[index]!.path),
                      fit: BoxFit.cover,
                    ),
                  ),
                  Positioned(
                    top: 6,
                    right: 6,
                    child: GestureDetector(
                      onTap: () => _removeImage(index),
                      child: Container(
                        padding: const EdgeInsets.all(4),
                        decoration: const BoxDecoration(
                          color: _errorRed,
                          shape: BoxShape.circle,
                        ),
                        child: const Icon(
                          Icons.close,
                          size: 16,
                          color: Colors.white,
                        ),
                      ),
                    ),
                  ),
                  Positioned(
                    bottom: 6,
                    left: 6,
                    child: Container(
                      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                      decoration: BoxDecoration(
                        color: Colors.black54,
                        borderRadius: BorderRadius.circular(6),
                      ),
                      child: Text(
                        'Foto ${index + 1}',
                        style: const TextStyle(
                          fontFamily: 'Poppins',
                          fontSize: 10,
                          color: Colors.white,
                          fontWeight: FontWeight.w500,
                        ),
                      ),
                    ),
                  ),
                ],
              )
            : Column(
                mainAxisAlignment: MainAxisAlignment.center,
                children: [
                  Container(
                    padding: const EdgeInsets.all(12),
                    decoration: BoxDecoration(
                      color: _primaryBlue.withValues(alpha: 0.1),
                      shape: BoxShape.circle,
                    ),
                    child: Icon(
                      index == 0 ? Iconsax.camera : Iconsax.add,
                      size: 24,
                      color: _primaryBlue,
                    ),
                  ),
                  const SizedBox(height: 8),
                  Text(
                    index == 0 ? 'Foto Wajib' : 'Foto ${index + 1}',
                    style: TextStyle(
                      fontFamily: 'Poppins',
                      fontSize: 12,
                      fontWeight: index == 0 ? FontWeight.w600 : FontWeight.w500,
                      color: index == 0 ? _primaryBlue : _textSecondary,
                    ),
                  ),
                ],
              ),
      ),
    );
  }

  Widget _buildVideoPicker() {
    final hasVideo = _video != null;

    return InkWell(
      onTap: () => hasVideo ? null : _pickVideo(),
      borderRadius: BorderRadius.circular(12),
      child: Container(
        height: 80,
        decoration: BoxDecoration(
          color: hasVideo ? _successGreen.withValues(alpha: 0.1) : _bgLight,
          borderRadius: BorderRadius.circular(12),
          border: Border.all(
            color: hasVideo ? _successGreen : _borderColor,
            width: hasVideo ? 2 : 1,
          ),
        ),
        child: Row(
          children: [
            const SizedBox(width: 16),
            Container(
              padding: const EdgeInsets.all(12),
              decoration: BoxDecoration(
                color: hasVideo
                    ? _successGreen.withValues(alpha: 0.2)
                    : _primaryBlue.withValues(alpha: 0.1),
                borderRadius: BorderRadius.circular(10),
              ),
              child: Icon(
                hasVideo ? Iconsax.video5 : Iconsax.video,
                size: 24,
                color: hasVideo ? _successGreen : _primaryBlue,
              ),
            ),
            const SizedBox(width: 14),
            Expanded(
              child: Column(
                mainAxisAlignment: MainAxisAlignment.center,
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    hasVideo ? 'Video Terpilih' : 'Tambah Video (Opsional)',
                    style: TextStyle(
                      fontFamily: 'Poppins',
                      fontWeight: FontWeight.w500,
                      fontSize: 14,
                      color: hasVideo ? _successGreen : _textPrimary,
                    ),
                  ),
                  const SizedBox(height: 2),
                  Text(
                    hasVideo
                        ? _video!.name
                        : 'Maksimal 5 menit',
                    style: const TextStyle(
                      fontFamily: 'Poppins',
                      fontSize: 12,
                      color: _textSecondary,
                    ),
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                  ),
                ],
              ),
            ),
            if (hasVideo)
              IconButton(
                onPressed: _removeVideo,
                icon: const Icon(Iconsax.trash, color: _errorRed, size: 20),
              )
            else
              const Padding(
                padding: EdgeInsets.only(right: 16),
                child: Icon(Iconsax.arrow_right_3, size: 20, color: _textSecondary),
              ),
          ],
        ),
      ),
    );
  }

  Widget _buildSectionCard({
    required String title,
    required IconData icon,
    required Widget child,
    bool isRequired = false,
  }) {
    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(16),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.04),
            blurRadius: 10,
            offset: const Offset(0, 2),
          ),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Icon(icon, size: 20, color: _primaryBlue),
              const SizedBox(width: 8),
              Text(
                title,
                style: const TextStyle(
                  fontFamily: 'Poppins',
                  fontWeight: FontWeight.w600,
                  fontSize: 15,
                  color: _textPrimary,
                ),
              ),
              if (isRequired) ...[
                const SizedBox(width: 4),
                const Text(
                  '*',
                  style: TextStyle(
                    color: _errorRed,
                    fontWeight: FontWeight.bold,
                  ),
                ),
              ],
            ],
          ),
          const SizedBox(height: 14),
          child,
        ],
      ),
    );
  }

  Widget _buildSubmitButton() {
    return Positioned(
      bottom: 0,
      left: 0,
      right: 0,
      child: Container(
        padding: const EdgeInsets.all(20),
        decoration: BoxDecoration(
          color: Colors.white,
          boxShadow: [
            BoxShadow(
              color: Colors.black.withValues(alpha: 0.08),
              blurRadius: 10,
              offset: const Offset(0, -2),
            ),
          ],
        ),
        child: ElevatedButton(
          onPressed: _isSubmitting ? null : _submitForm,
          style: ElevatedButton.styleFrom(
            backgroundColor: _primaryBlue,
            disabledBackgroundColor: _primaryBlue.withValues(alpha: 0.5),
            foregroundColor: Colors.white,
            padding: const EdgeInsets.symmetric(vertical: 16),
            shape: RoundedRectangleBorder(
              borderRadius: BorderRadius.circular(14),
            ),
            elevation: 0,
          ),
          child: Row(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              Icon(
                _isSubmitting ? null : Iconsax.send_1,
                size: 20,
              ),
              const SizedBox(width: 10),
              Text(
                _isSubmitting ? 'Mengirim...' : 'Kirim Laporan',
                style: const TextStyle(
                  fontFamily: 'Poppins',
                  fontWeight: FontWeight.w600,
                  fontSize: 16,
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildLoadingOverlay() {
    return Container(
      color: Colors.black.withValues(alpha: 0.5),
      child: Center(
        child: Container(
          padding: const EdgeInsets.all(24),
          decoration: BoxDecoration(
            color: Colors.white,
            borderRadius: BorderRadius.circular(16),
          ),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              const CircularProgressIndicator(
                valueColor: AlwaysStoppedAnimation<Color>(_primaryBlue),
                strokeWidth: 3,
              ),
              const SizedBox(height: 20),
              const Text(
                'Mengirim Laporan',
                style: TextStyle(
                  fontFamily: 'Poppins',
                  fontWeight: FontWeight.w600,
                  fontSize: 16,
                  color: _textPrimary,
                ),
              ),
              const SizedBox(height: 8),
              const Text(
                'Mohon tunggu sebentar...',
                style: TextStyle(
                  fontFamily: 'Poppins',
                  fontSize: 13,
                  color: _textSecondary,
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}
