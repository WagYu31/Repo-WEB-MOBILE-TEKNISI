import 'dart:io';

import 'package:flutter/material.dart';
import 'package:iconsax/iconsax.dart';
import 'package:image_picker/image_picker.dart';
import 'package:provider/provider.dart';
import 'package:quickalert/quickalert.dart';

import '../../service/provider/Pelaksanaan/ReportPelaksanaanProvider.dart';
import '../../service/provider/Task/TaskGetAllProvider.dart';
import '../../service/provider/Task/DetailTaskGetProvider.dart';
import '../../service/provider/preferences/PreferencesIDProvider.dart';
import '../container/HomePage.dart';

class ReportDonePage extends StatefulWidget {
  static const routeName = '/report_done';
  final List<dynamic> data;

  const ReportDonePage({super.key, required this.data});

  @override
  State<ReportDonePage> createState() => _ReportDonePageState();
}

class _ReportDonePageState extends State<ReportDonePage> {
  // Modern color scheme
  static const Color _primaryBlue = Color(0xFF2563EB);
  static const Color _successGreen = Color(0xFF10B981);
  static const Color _errorRed = Color(0xFFEF4444);
  static const Color _textPrimary = Color(0xFF1F2937);
  static const Color _textSecondary = Color(0xFF6B7280);
  static const Color _bgLight = Color(0xFFF9FAFB);
  static const Color _borderColor = Color(0xFFE5E7EB);

  // Controllers
  final _permasalahanController = TextEditingController();
  final _solusiController = TextEditingController();
  final _keteranganController = TextEditingController();
  final _garansiController = TextEditingController();
  final _formKey = GlobalKey<FormState>();
  final _scrollController = ScrollController();

  // State
  bool _isGaransi = false;
  bool _isSubmitting = false;
  final ImagePicker _picker = ImagePicker();

  // Image data - simplified using list
  final List<XFile?> _images = List.filled(4, null);
  XFile? _video;

  @override
  void dispose() {
    _permasalahanController.dispose();
    _solusiController.dispose();
    _keteranganController.dispose();
    _garansiController.dispose();
    _scrollController.dispose();
    super.dispose();
  }

  Future<void> _pickImage(int index) async {
    final source = await showModalBottomSheet<ImageSource>(
      context: context,
      backgroundColor: Colors.transparent,
      builder: (ctx) => Container(
        margin: const EdgeInsets.all(16),
        decoration: BoxDecoration(
          color: Colors.white,
          borderRadius: BorderRadius.circular(20),
        ),
        child: SafeArea(
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              const SizedBox(height: 12),
              Container(
                width: 40, height: 4,
                decoration: BoxDecoration(
                  color: _borderColor,
                  borderRadius: BorderRadius.circular(2),
                ),
              ),
              const SizedBox(height: 16),
              const Text(
                'Pilih Sumber Foto',
                style: TextStyle(
                  fontFamily: 'Poppins',
                  fontWeight: FontWeight.w600,
                  fontSize: 16,
                  color: _textPrimary,
                ),
              ),
              const SizedBox(height: 16),
              ListTile(
                leading: Container(
                  padding: const EdgeInsets.all(10),
                  decoration: BoxDecoration(
                    color: _primaryBlue.withValues(alpha: 0.1),
                    borderRadius: BorderRadius.circular(12),
                  ),
                  child: const Icon(Icons.camera_alt_rounded, color: _primaryBlue, size: 24),
                ),
                title: const Text('Kamera', style: TextStyle(fontFamily: 'Poppins', fontWeight: FontWeight.w600, fontSize: 14)),
                subtitle: const Text('Ambil foto langsung', style: TextStyle(fontFamily: 'Poppins', fontSize: 12, color: _textSecondary)),
                onTap: () => Navigator.pop(ctx, ImageSource.camera),
              ),
              const Divider(height: 1, indent: 72),
              ListTile(
                leading: Container(
                  padding: const EdgeInsets.all(10),
                  decoration: BoxDecoration(
                    color: _successGreen.withValues(alpha: 0.1),
                    borderRadius: BorderRadius.circular(12),
                  ),
                  child: const Icon(Icons.photo_library_rounded, color: _successGreen, size: 24),
                ),
                title: const Text('Galeri', style: TextStyle(fontFamily: 'Poppins', fontWeight: FontWeight.w600, fontSize: 14)),
                subtitle: const Text('Pilih dari galeri foto', style: TextStyle(fontFamily: 'Poppins', fontSize: 12, color: _textSecondary)),
                onTap: () => Navigator.pop(ctx, ImageSource.gallery),
              ),
              const SizedBox(height: 16),
            ],
          ),
        ),
      ),
    );

    if (source == null) return;

    try {
      final XFile? image = await _picker.pickImage(
        source: source,
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
    if (_permasalahanController.text.trim().isEmpty) {
      _showInfo('Field Wajib Diisi', 'Silakan isi permasalahan yang ditemukan.');
      return false;
    }

    if (_images[0] == null) {
      _showInfo('Foto Wajib Diisi', 'Minimal satu foto dokumentasi harus diupload.');
      return false;
    }

    if (_isGaransi && _garansiController.text.trim().isEmpty) {
      _showInfo('Keterangan Garansi', 'Silakan isi keterangan garansi.');
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
      final upload = context.read<ReportPelaksanaanProvider>();
      final taskProvider = context.read<TaskGetAllProvider>();
      final idProvider = context.read<PreferencesIDProvider>();
      final detailProvider = context.read<DetailTaskGetProvider>();

      // Compress and prepare images
      List<List<int>> dataGambar = [];

      for (int i = 0; i < _images.length; i++) {
        if (_images[i] != null) {
          final file = File(_images[i]!.path);
          if (!await file.exists()) {
            if (mounted) {
              _showError('Foto ${i + 1} sudah tidak tersedia. Silakan pilih ulang foto.');
            }
            return;
          }
          final bytes = await _images[i]!.readAsBytes();
          final compressed = await upload.compressImage(bytes);
          dataGambar.add(compressed);
        }
      }

      // Add video if exists (DON'T compress - it's not an image)
      if (_video != null) {
        final videoFile = File(_video!.path);
        if (!await videoFile.exists()) {
          if (mounted) {
            _showError('Video sudah tidak tersedia. Silakan pilih ulang video.');
          }
          return;
        }
        final videoBytes = await _video!.readAsBytes();
        dataGambar.add(videoBytes.toList());
      }

      // Prepare garansi info
      String? garansiKeterangan = _isGaransi ? _garansiController.text : null;

      final result = await upload.upload(
        dataGambar,
        widget.data[1].toString(),
        widget.data[0].toString(),
        _permasalahanController.text,
        _solusiController.text,
        _keteranganController.text,
        garansiKeterangan,
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
          text: 'Laporan penyelesaian berhasil dikirim.',
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
      resizeToAvoidBottomInset: true,
      appBar: _buildAppBar(),
      body: SafeArea(
        child: Stack(
          children: [
            Form(
              key: _formKey,
              child: SingleChildScrollView(
                controller: _scrollController,
                keyboardDismissBehavior: ScrollViewKeyboardDismissBehavior.onDrag,
                padding: const EdgeInsets.all(20),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    _buildInfoCard(),
                    const SizedBox(height: 20),
                    _buildFormSection(),
                    const SizedBox(height: 20),
                    _buildGaransiSection(),
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
        'Laporan Penyelesaian',
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
      padding: const EdgeInsets.all(20),
      decoration: BoxDecoration(
        gradient: LinearGradient(
          colors: [_primaryBlue, _primaryBlue.withValues(alpha: 0.8)],
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
        ),
        borderRadius: BorderRadius.circular(20),
        boxShadow: [
          BoxShadow(
            color: _primaryBlue.withValues(alpha: 0.3),
            blurRadius: 16,
            offset: const Offset(0, 6),
          ),
        ],
      ),
      child: Row(
        children: [
          Container(
            padding: const EdgeInsets.all(14),
            decoration: BoxDecoration(
              color: Colors.white.withValues(alpha: 0.2),
              borderRadius: BorderRadius.circular(16),
            ),
            child: const Icon(Iconsax.document_text, color: Colors.white, size: 32),
          ),
          const SizedBox(width: 16),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                const Text(
                  'Selesaikan Tugas',
                  style: TextStyle(
                    fontFamily: 'Poppins',
                    fontWeight: FontWeight.w700,
                    fontSize: 18,
                    color: Colors.white,
                  ),
                ),
                const SizedBox(height: 4),
                Text(
                  'Isi laporan penyelesaian tugas dengan lengkap',
                  style: TextStyle(
                    fontFamily: 'Poppins',
                    fontSize: 13,
                    color: Colors.white.withValues(alpha: 0.9),
                    height: 1.4,
                  ),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildFormSection() {
    return _buildSectionCard(
      title: 'Detail Laporan',
      icon: Iconsax.edit_2,
      child: Column(
        children: [
          _buildTextField(
            controller: _permasalahanController,
            label: 'Permasalahan',
            hint: 'Jelaskan permasalahan yang ditemukan...',
            icon: Iconsax.warning_2,
            isRequired: true,
            maxLines: 3,
          ),
          const SizedBox(height: 16),
          _buildTextField(
            controller: _solusiController,
            label: 'Solusi',
            hint: 'Jelaskan solusi yang dilakukan...',
            icon: Iconsax.lamp_on,
            maxLines: 3,
          ),
          const SizedBox(height: 16),
          _buildTextField(
            controller: _keteranganController,
            label: 'Keterangan Tambahan',
            hint: 'Keterangan tambahan (opsional)...',
            icon: Iconsax.note_text,
            maxLines: 2,
          ),
        ],
      ),
    );
  }

  Widget _buildTextField({
    required TextEditingController controller,
    required String label,
    required String hint,
    required IconData icon,
    bool isRequired = false,
    int maxLines = 1,
  }) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Row(
          children: [
            Icon(icon, size: 16, color: _primaryBlue),
            const SizedBox(width: 6),
            Text(
              label,
              style: const TextStyle(
                fontFamily: 'Poppins',
                fontWeight: FontWeight.w500,
                fontSize: 13,
                color: _textPrimary,
              ),
            ),
            if (isRequired) ...[
              const SizedBox(width: 4),
              const Text('*', style: TextStyle(color: _errorRed, fontWeight: FontWeight.bold)),
            ],
          ],
        ),
        const SizedBox(height: 8),
        TextFormField(
          controller: controller,
          minLines: maxLines,
          maxLines: maxLines + 2,
          style: const TextStyle(
            fontFamily: 'Poppins',
            fontSize: 14,
            color: _textPrimary,
          ),
          decoration: InputDecoration(
            hintText: hint,
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
            contentPadding: const EdgeInsets.all(14),
          ),
        ),
      ],
    );
  }

  Widget _buildGaransiSection() {
    return _buildSectionCard(
      title: 'Garansi',
      icon: Iconsax.shield_tick,
      child: Column(
        children: [
          InkWell(
            onTap: () {
              setState(() {
                _isGaransi = !_isGaransi;
              });
            },
            borderRadius: BorderRadius.circular(12),
            child: Container(
              padding: const EdgeInsets.all(14),
              decoration: BoxDecoration(
                color: _isGaransi ? _successGreen.withValues(alpha: 0.1) : _bgLight,
                borderRadius: BorderRadius.circular(12),
                border: Border.all(
                  color: _isGaransi ? _successGreen : _borderColor,
                  width: _isGaransi ? 2 : 1,
                ),
              ),
              child: Row(
                children: [
                  Container(
                    width: 24,
                    height: 24,
                    decoration: BoxDecoration(
                      color: _isGaransi ? _successGreen : Colors.white,
                      borderRadius: BorderRadius.circular(6),
                      border: Border.all(
                        color: _isGaransi ? _successGreen : _borderColor,
                        width: 2,
                      ),
                    ),
                    child: _isGaransi
                        ? const Icon(Icons.check, size: 16, color: Colors.white)
                        : null,
                  ),
                  const SizedBox(width: 12),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          'Berikan Garansi',
                          style: TextStyle(
                            fontFamily: 'Poppins',
                            fontWeight: FontWeight.w500,
                            fontSize: 14,
                            color: _isGaransi ? _successGreen : _textPrimary,
                          ),
                        ),
                        const SizedBox(height: 2),
                        const Text(
                          'Centang jika pekerjaan ini bergaransi',
                          style: TextStyle(
                            fontFamily: 'Poppins',
                            fontSize: 12,
                            color: _textSecondary,
                          ),
                        ),
                      ],
                    ),
                  ),
                  Icon(
                    _isGaransi ? Iconsax.shield_tick5 : Iconsax.shield_tick,
                    color: _isGaransi ? _successGreen : _textSecondary,
                    size: 24,
                  ),
                ],
              ),
            ),
          ),
          if (_isGaransi) ...[
            const SizedBox(height: 16),
            _buildTextField(
              controller: _garansiController,
              label: 'Keterangan Garansi',
              hint: 'Jelaskan detail garansi...',
              icon: Iconsax.document,
              isRequired: true,
              maxLines: 2,
            ),
          ],
        ],
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
                      color: index == 0 
                          ? _primaryBlue.withValues(alpha: 0.1) 
                          : const Color(0xFFF1F5F9),
                      shape: BoxShape.circle,
                    ),
                    child: Icon(
                      index == 0 ? Icons.camera_alt_rounded : Iconsax.add,
                      size: 24,
                      color: index == 0 ? _primaryBlue : _textSecondary,
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
                  if (index == 0)
                    const Text(
                      'Kamera / Galeri',
                      style: TextStyle(
                        fontFamily: 'Poppins',
                        fontSize: 10,
                        color: _textSecondary,
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
                    hasVideo ? _video!.name : 'Maksimal 5 menit',
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
            backgroundColor: _successGreen,
            disabledBackgroundColor: _successGreen.withValues(alpha: 0.5),
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
                _isSubmitting ? null : Iconsax.tick_circle,
                size: 20,
              ),
              const SizedBox(width: 10),
              Text(
                _isSubmitting ? 'Mengirim...' : 'Selesaikan Tugas',
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
          child: const Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              CircularProgressIndicator(
                valueColor: AlwaysStoppedAnimation<Color>(_primaryBlue),
                strokeWidth: 3,
              ),
              SizedBox(height: 20),
              Text(
                'Mengirim Laporan',
                style: TextStyle(
                  fontFamily: 'Poppins',
                  fontWeight: FontWeight.w600,
                  fontSize: 16,
                  color: _textPrimary,
                ),
              ),
              SizedBox(height: 8),
              Text(
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
