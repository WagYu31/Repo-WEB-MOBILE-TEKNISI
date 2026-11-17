import 'dart:io';

import 'package:flutter/material.dart';
import 'package:iconsax/iconsax.dart';
import 'package:image_picker/image_picker.dart';
import 'package:quickalert/quickalert.dart';

import '../../service/api/ApiBast.dart';

class _CameraPointField {
  XFile? newFile;
  String? existingFilename;
  String? existingUrl;
  final TextEditingController controller;

  _CameraPointField({
    this.newFile,
    this.existingFilename,
    this.existingUrl,
    String initialKeterangan = '',
  }) : controller = TextEditingController(text: initialKeterangan);

  void dispose() => controller.dispose();
}

class InputBastPage extends StatefulWidget {
  final String kode;
  final int kegiatanId;
  final int customerId;
  final String namaCustomer;
  final int teknisiId;

  const InputBastPage({
    super.key,
    required this.kode,
    required this.kegiatanId,
    required this.customerId,
    required this.namaCustomer,
    required this.teknisiId,
  });

  @override
  State<InputBastPage> createState() => _InputBastPageState();
}

class _InputBastPageState extends State<InputBastPage> {
  static const Color _darkBg = Color(0xFF11141B);
  static const Color _darkPill = Color(0xFF1E2330);
  static const Color _orange = Color(0xFFFF5B22);
  static const Color _canvas = Color(0xFFF4F6F8);
  static const Color _border = Color(0xFFEDF1F5);
  static const Color _textDark = Color(0xFF11141A);
  static const Color _textMuted = Color(0xFF64748B);

  final ApiBast _api = ApiBast();
  final ImagePicker _picker = ImagePicker();
  final TextEditingController _ketDisplayCtrl = TextEditingController();

  bool _isLoading = true;
  bool _isSubmitting = false;
  bool _hasExistingBast = false;

  XFile? _newDisplayPhoto;
  String? _existingDisplayFilename;
  String? _existingDisplayUrl;

  final List<_CameraPointField> _points = [];

  @override
  void initState() {
    super.initState();
    _loadExisting();
  }

  @override
  void dispose() {
    _ketDisplayCtrl.dispose();
    for (final p in _points) {
      p.dispose();
    }
    super.dispose();
  }

  Future<void> _loadExisting() async {
    try {
      final res = await _api.getDetail(widget.kode, customerId: widget.customerId);
      if (!mounted) return;
      final bast = res['bast'] as Map<String, dynamic>?;
      final titik = res['titik_kamera'] as List? ?? const [];
      setState(() {
        if (bast != null) {
          _hasExistingBast = (bast['foto_display_utama']?.toString().isNotEmpty ?? false);
          _existingDisplayFilename = bast['foto_display_utama']?.toString();
          _existingDisplayUrl = bast['foto_display_url']?.toString();
          _ketDisplayCtrl.text = bast['ket_display_utama']?.toString() ?? '';
        }
        for (final p in _points) {
          p.dispose();
        }
        _points.clear();
        for (final item in titik) {
          final m = Map<String, dynamic>.from(item as Map);
          _points.add(
            _CameraPointField(
              existingFilename: m['foto_titik']?.toString(),
              existingUrl: m['foto_url']?.toString(),
              initialKeterangan: m['keterangan']?.toString() ?? '',
            ),
          );
        }
        _isLoading = false;
      });
    } catch (_) {
      if (mounted) setState(() => _isLoading = false);
    }
  }

  Future<ImageSource?> _chooseImageSource() {
    return showModalBottomSheet<ImageSource>(
      context: context,
      backgroundColor: Colors.transparent,
      builder: (ctx) => Container(
        margin: const EdgeInsets.all(16),
        decoration: BoxDecoration(
          color: Colors.white,
          borderRadius: BorderRadius.circular(22),
        ),
        child: SafeArea(
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              const SizedBox(height: 12),
              Container(
                width: 40,
                height: 4,
                decoration: BoxDecoration(
                  color: const Color(0xFFE2E8F0),
                  borderRadius: BorderRadius.circular(2),
                ),
              ),
              const SizedBox(height: 14),
              const Text(
                'Pilih Sumber Foto',
                style: TextStyle(
                  fontFamily: 'Poppins',
                  fontSize: 15,
                  fontWeight: FontWeight.w700,
                  color: _textDark,
                ),
              ),
              const SizedBox(height: 10),
              ListTile(
                leading: Container(
                  padding: const EdgeInsets.all(10),
                  decoration: BoxDecoration(
                    color: _orange.withValues(alpha: 0.12),
                    borderRadius: BorderRadius.circular(12),
                  ),
                  child: const Icon(Iconsax.camera, color: _orange, size: 22),
                ),
                title: const Text(
                  'Kamera',
                  style: TextStyle(fontFamily: 'Poppins', fontWeight: FontWeight.w600, fontSize: 14),
                ),
                subtitle: const Text(
                  'Ambil foto langsung dari kamera',
                  style: TextStyle(fontFamily: 'Poppins', fontSize: 11.5, color: _textMuted),
                ),
                onTap: () => Navigator.pop(ctx, ImageSource.camera),
              ),
              ListTile(
                leading: Container(
                  padding: const EdgeInsets.all(10),
                  decoration: BoxDecoration(
                    color: const Color(0xFF10B981).withValues(alpha: 0.12),
                    borderRadius: BorderRadius.circular(12),
                  ),
                  child: const Icon(Iconsax.gallery, color: Color(0xFF10B981), size: 22),
                ),
                title: const Text(
                  'Galeri HP (Bisa dari Rumah)',
                  style: TextStyle(fontFamily: 'Poppins', fontWeight: FontWeight.w600, fontSize: 14),
                ),
                subtitle: const Text(
                  'Pilih foto yang sudah tersimpan di HP',
                  style: TextStyle(fontFamily: 'Poppins', fontSize: 11.5, color: _textMuted),
                ),
                onTap: () => Navigator.pop(ctx, ImageSource.gallery),
              ),
              const SizedBox(height: 12),
            ],
          ),
        ),
      ),
    );
  }

  Future<void> _pickDisplayPhoto() async {
    final source = await _chooseImageSource();
    if (source == null) return;
    final img = await _picker.pickImage(
      source: source,
      imageQuality: 80,
      maxWidth: 1600,
      maxHeight: 1600,
    );
    if (img != null && mounted) {
      setState(() => _newDisplayPhoto = img);
    }
  }

  Future<void> _addSinglePointPhoto() async {
    final source = await _chooseImageSource();
    if (source == null) return;
    final img = await _picker.pickImage(
      source: source,
      imageQuality: 80,
      maxWidth: 1600,
      maxHeight: 1600,
    );
    if (img != null && mounted) {
      setState(() {
        _points.add(_CameraPointField(newFile: img));
      });
    }
  }

  Future<void> _addMultipleGalleryPhotos() async {
    final imgs = await _picker.pickMultiImage(
      imageQuality: 80,
      maxWidth: 1600,
      maxHeight: 1600,
    );
    if (imgs.isNotEmpty && mounted) {
      setState(() {
        for (final x in imgs) {
          _points.add(_CameraPointField(newFile: x));
        }
      });
    }
  }

  Future<void> _replacePointPhoto(int index) async {
    final source = await _chooseImageSource();
    if (source == null) return;
    final img = await _picker.pickImage(
      source: source,
      imageQuality: 80,
      maxWidth: 1600,
      maxHeight: 1600,
    );
    if (img != null && mounted) {
      setState(() => _points[index].newFile = img);
    }
  }

  void _removePoint(int index) {
    setState(() {
      final removed = _points.removeAt(index);
      removed.dispose();
    });
  }

  Future<void> _submit() async {
    final hasDisplay = _newDisplayPhoto != null ||
        (_existingDisplayFilename != null && _existingDisplayFilename!.isNotEmpty);
    if (!hasDisplay) {
      QuickAlert.show(
        context: context,
        type: QuickAlertType.warning,
        title: 'Foto Display Belum Ada',
        text: 'Mohon upload Foto Display CCTV Keseluruhan terlebih dahulu.',
      );
      return;
    }

    setState(() => _isSubmitting = true);
    try {
      final drafts = _points
          .map(
            (p) => BastPointDraft(
              newFile: p.newFile,
              existingFilename: p.existingFilename,
              existingUrl: p.existingUrl,
              keterangan: p.controller.text,
            ),
          )
          .toList();

      await _api.submitBast(
        kode: widget.kode,
        kegiatanId: widget.kegiatanId,
        customerId: widget.customerId,
        teknisiId: widget.teknisiId,
        newDisplayPhoto: _newDisplayPhoto,
        existingDisplayFilename: _existingDisplayFilename,
        ketDisplayUtama: _ketDisplayCtrl.text,
        titikList: drafts,
      );

      if (!mounted) return;
      setState(() => _isSubmitting = false);
      await QuickAlert.show(
        context: context,
        type: QuickAlertType.success,
        title: 'BAST Tersimpan',
        text: 'Foto display & ${drafts.length} titik kamera berhasil dikirim ke Web Jadwal Admin.',
        confirmBtnColor: _orange,
      );
      if (mounted) Navigator.pop(context, true);
    } catch (e) {
      if (!mounted) return;
      setState(() => _isSubmitting = false);
      QuickAlert.show(
        context: context,
        type: QuickAlertType.error,
        title: 'Gagal Menyimpan',
        text: e.toString().replaceFirst('Exception: ', ''),
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    final bottomInset = MediaQuery.of(context).padding.bottom;
    return Scaffold(
      backgroundColor: _canvas,
      body: Column(
        children: [
          _buildHeader(),
          Expanded(
            child: _isLoading
                ? const Center(child: CircularProgressIndicator(color: _orange))
                : ListView(
                    padding: const EdgeInsets.fromLTRB(18, 16, 18, 28),
                    children: [
                      _buildInfoBanner(),
                      const SizedBox(height: 14),
                      _buildDisplayUtamaCard(),
                      const SizedBox(height: 16),
                      _buildCameraPointsSection(),
                    ],
                  ),
          ),
          _buildBottomBar(bottomInset),
        ],
      ),
    );
  }

  Widget _buildHeader() {
    return Container(
      decoration: const BoxDecoration(
        color: _darkBg,
        borderRadius: BorderRadius.only(
          bottomLeft: Radius.circular(32),
          bottomRight: Radius.circular(32),
        ),
      ),
      child: SafeArea(
        bottom: false,
        child: Padding(
          padding: const EdgeInsets.fromLTRB(18, 10, 18, 20),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Row(
                children: [
                  GestureDetector(
                    onTap: () => Navigator.pop(context),
                    child: Container(
                      width: 42,
                      height: 42,
                      decoration: const BoxDecoration(
                        color: _darkPill,
                        shape: BoxShape.circle,
                      ),
                      child: const Icon(Icons.arrow_back_ios_new_rounded, color: Colors.white, size: 17),
                    ),
                  ),
                  const SizedBox(width: 12),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          _hasExistingBast ? 'Edit Dokumen BAST' : 'Input Dokumen BAST',
                          style: const TextStyle(
                            fontFamily: 'Poppins',
                            fontSize: 16.5,
                            fontWeight: FontWeight.w700,
                            color: Colors.white,
                          ),
                        ),
                        Text(
                          '#${widget.kode} • ${widget.namaCustomer}',
                          maxLines: 1,
                          overflow: TextOverflow.ellipsis,
                          style: TextStyle(
                            fontFamily: 'Poppins',
                            fontSize: 12,
                            color: Colors.white.withValues(alpha: 0.7),
                          ),
                        ),
                      ],
                    ),
                  ),
                  Container(
                    padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 5),
                    decoration: BoxDecoration(
                      color: _orange.withValues(alpha: 0.18),
                      borderRadius: BorderRadius.circular(20),
                      border: Border.all(color: _orange.withValues(alpha: 0.45)),
                    ),
                    child: const Text(
                      'BAST',
                      style: TextStyle(
                        fontFamily: 'Poppins',
                        fontSize: 11,
                        fontWeight: FontWeight.w700,
                        color: Color(0xFFFF8A5B),
                      ),
                    ),
                  ),
                ],
              ),
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildInfoBanner() {
    return Container(
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: const Color(0xFFFFF7ED),
        borderRadius: BorderRadius.circular(18),
        border: Border.all(color: const Color(0xFFFED7AA)),
      ),
      child: const Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Icon(Iconsax.home_wifi, color: _orange, size: 20),
          SizedBox(width: 10),
          Expanded(
            child: Text(
              'Laporan BAST terpisah dari Laporan Harian dan bisa dikerjakan di rumah. Anda dapat mencicil atau mengedit foto kapan saja.',
              style: TextStyle(
                fontFamily: 'Poppins',
                fontSize: 11.5,
                height: 1.45,
                color: Color(0xFF9A3412),
                fontWeight: FontWeight.w500,
              ),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildDisplayUtamaCard() {
    final hasLocal = _newDisplayPhoto != null;
    final hasRemote = !hasLocal && (_existingDisplayUrl != null && _existingDisplayUrl!.isNotEmpty);

    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(20),
        border: Border.all(color: _border),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.03),
            blurRadius: 12,
            offset: const Offset(0, 4),
          ),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Container(
                padding: const EdgeInsets.all(9),
                decoration: BoxDecoration(
                  color: _orange.withValues(alpha: 0.12),
                  borderRadius: BorderRadius.circular(12),
                ),
                child: const Icon(Iconsax.monitor, color: _orange, size: 19),
              ),
              const SizedBox(width: 10),
              const Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      '1. Foto Display CCTV Keseluruhan',
                      style: TextStyle(
                        fontFamily: 'Poppins',
                        fontSize: 13.5,
                        fontWeight: FontWeight.w700,
                        color: _textDark,
                      ),
                    ),
                    Text(
                      'Tampilan layar monitor semua channel kamera',
                      style: TextStyle(
                        fontFamily: 'Poppins',
                        fontSize: 11,
                        color: _textMuted,
                      ),
                    ),
                  ],
                ),
              ),
            ],
          ),
          const SizedBox(height: 14),
          GestureDetector(
            onTap: _pickDisplayPhoto,
            child: ClipRRect(
              borderRadius: BorderRadius.circular(15),
              child: Container(
                height: 185,
                width: double.infinity,
                decoration: BoxDecoration(
                  color: const Color(0xFFF8FAFC),
                  borderRadius: BorderRadius.circular(15),
                  border: Border.all(color: const Color(0xFFE2E8F0)),
                ),
                child: hasLocal
                    ? Stack(
                        fit: StackFit.expand,
                        children: [
                          Image.file(File(_newDisplayPhoto!.path), fit: BoxFit.cover),
                          _buildChangePhotoBadge(),
                        ],
                      )
                    : hasRemote
                        ? Stack(
                            fit: StackFit.expand,
                            children: [
                              Image.network(
                                _existingDisplayUrl!,
                                fit: BoxFit.cover,
                                errorBuilder: (_, __, ___) => const Center(
                                  child: Icon(Icons.broken_image_outlined, color: _textMuted),
                                ),
                              ),
                              _buildChangePhotoBadge(),
                            ],
                          )
                        : Column(
                            mainAxisAlignment: MainAxisAlignment.center,
                            children: [
                              Container(
                                padding: const EdgeInsets.all(12),
                                decoration: BoxDecoration(
                                  color: _orange.withValues(alpha: 0.1),
                                  shape: BoxShape.circle,
                                ),
                                child: const Icon(Iconsax.camera, color: _orange, size: 26),
                              ),
                              const SizedBox(height: 8),
                              const Text(
                                'Tap untuk pilih Foto Display Keseluruhan',
                                style: TextStyle(
                                  fontFamily: 'Poppins',
                                  fontSize: 12.5,
                                  fontWeight: FontWeight.w600,
                                  color: _textDark,
                                ),
                              ),
                              const Text(
                                'Bisa ambil dari Galeri atau Kamera',
                                style: TextStyle(
                                  fontFamily: 'Poppins',
                                  fontSize: 11,
                                  color: _textMuted,
                                ),
                              ),
                            ],
                          ),
              ),
            ),
          ),
          const SizedBox(height: 12),
          TextField(
            controller: _ketDisplayCtrl,
            maxLines: 2,
            minLines: 1,
            style: const TextStyle(fontFamily: 'Poppins', fontSize: 13, color: _textDark),
            decoration: InputDecoration(
              labelText: 'Keterangan Display Keseluruhan',
              hintText: 'Contoh: Display Monitor NVR 16 Channel Ruang Server',
              labelStyle: const TextStyle(fontFamily: 'Poppins', fontSize: 12, color: _textMuted),
              hintStyle: const TextStyle(fontFamily: 'Poppins', fontSize: 12, color: Color(0xFF94A3B8)),
              filled: true,
              fillColor: const Color(0xFFF8FAFC),
              contentPadding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
              border: OutlineInputBorder(
                borderRadius: BorderRadius.circular(14),
                borderSide: const BorderSide(color: Color(0xFFE2E8F0)),
              ),
              enabledBorder: OutlineInputBorder(
                borderRadius: BorderRadius.circular(14),
                borderSide: const BorderSide(color: Color(0xFFE2E8F0)),
              ),
              focusedBorder: OutlineInputBorder(
                borderRadius: BorderRadius.circular(14),
                borderSide: const BorderSide(color: _orange, width: 1.4),
              ),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildChangePhotoBadge() {
    return Positioned(
      right: 10,
      bottom: 10,
      child: Container(
        padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
        decoration: BoxDecoration(
          color: Colors.black.withValues(alpha: 0.68),
          borderRadius: BorderRadius.circular(20),
        ),
        child: const Row(
          mainAxisSize: MainAxisSize.min,
          children: [
            Icon(Iconsax.edit_2, color: Colors.white, size: 13),
            SizedBox(width: 5),
            Text(
              'Ganti Foto',
              style: TextStyle(
                fontFamily: 'Poppins',
                fontSize: 11,
                fontWeight: FontWeight.w600,
                color: Colors.white,
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildCameraPointsSection() {
    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(20),
        border: Border.all(color: _border),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.03),
            blurRadius: 12,
            offset: const Offset(0, 4),
          ),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Container(
                padding: const EdgeInsets.all(9),
                decoration: BoxDecoration(
                  color: const Color(0xFF2563EB).withValues(alpha: 0.1),
                  borderRadius: BorderRadius.circular(12),
                ),
                child: const Icon(Iconsax.video, color: Color(0xFF2563EB), size: 19),
              ),
              const SizedBox(width: 10),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    const Text(
                      '2. Foto Masing-Masing Titik Kamera',
                      style: TextStyle(
                        fontFamily: 'Poppins',
                        fontSize: 13.5,
                        fontWeight: FontWeight.w700,
                        color: _textDark,
                      ),
                    ),
                    Text(
                      '${_points.length} titik kamera ditambahkan',
                      style: const TextStyle(
                        fontFamily: 'Poppins',
                        fontSize: 11,
                        color: _textMuted,
                      ),
                    ),
                  ],
                ),
              ),
            ],
          ),
          const SizedBox(height: 14),
          if (_points.isEmpty)
            Container(
              width: double.infinity,
              padding: const EdgeInsets.symmetric(vertical: 20, horizontal: 14),
              decoration: BoxDecoration(
                color: const Color(0xFFF8FAFC),
                borderRadius: BorderRadius.circular(14),
                border: Border.all(color: const Color(0xFFE2E8F0)),
              ),
              child: const Column(
                children: [
                  Icon(Iconsax.gallery_add, color: Color(0xFF94A3B8), size: 28),
                  SizedBox(height: 6),
                  Text(
                    'Belum ada titik kamera',
                    style: TextStyle(
                      fontFamily: 'Poppins',
                      fontSize: 12.5,
                      fontWeight: FontWeight.w600,
                      color: _textDark,
                    ),
                  ),
                  Text(
                    'Tambahkan satu per satu atau pilih banyak foto sekaligus dari galeri',
                    textAlign: TextAlign.center,
                    style: TextStyle(
                      fontFamily: 'Poppins',
                      fontSize: 11,
                      color: _textMuted,
                    ),
                  ),
                ],
              ),
            )
          else
            ...List.generate(_points.length, (i) => _buildPointItem(i)),
          const SizedBox(height: 12),
          Row(
            children: [
              Expanded(
                child: OutlinedButton.icon(
                  onPressed: _addSinglePointPhoto,
                  icon: const Icon(Iconsax.camera, size: 16),
                  label: const Text('Tambah Titik'),
                  style: OutlinedButton.styleFrom(
                    foregroundColor: _textDark,
                    side: const BorderSide(color: Color(0xFFCBD5E1)),
                    padding: const EdgeInsets.symmetric(vertical: 12),
                    shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(13)),
                    textStyle: const TextStyle(
                      fontFamily: 'Poppins',
                      fontSize: 12,
                      fontWeight: FontWeight.w600,
                    ),
                  ),
                ),
              ),
              const SizedBox(width: 10),
              Expanded(
                child: ElevatedButton.icon(
                  onPressed: _addMultipleGalleryPhotos,
                  icon: const Icon(Iconsax.gallery_add, size: 16),
                  label: const Text('Pilih Banyak'),
                  style: ElevatedButton.styleFrom(
                    backgroundColor: _darkBg,
                    foregroundColor: Colors.white,
                    elevation: 0,
                    padding: const EdgeInsets.symmetric(vertical: 12),
                    shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(13)),
                    textStyle: const TextStyle(
                      fontFamily: 'Poppins',
                      fontSize: 12,
                      fontWeight: FontWeight.w600,
                    ),
                  ),
                ),
              ),
            ],
          ),
        ],
      ),
    );
  }

  Widget _buildPointItem(int index) {
    final pt = _points[index];
    final hasLocal = pt.newFile != null;
    final hasRemote = !hasLocal && (pt.existingUrl != null && pt.existingUrl!.isNotEmpty);

    return Container(
      margin: const EdgeInsets.only(bottom: 12),
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        color: const Color(0xFFF8FAFC),
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: const Color(0xFFE2E8F0)),
      ),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          GestureDetector(
            onTap: () => _replacePointPhoto(index),
            child: ClipRRect(
              borderRadius: BorderRadius.circular(12),
              child: SizedBox(
                width: 84,
                height: 84,
                child: hasLocal
                    ? Image.file(File(pt.newFile!.path), fit: BoxFit.cover)
                    : hasRemote
                        ? Image.network(
                            pt.existingUrl!,
                            fit: BoxFit.cover,
                            errorBuilder: (_, __, ___) => Container(
                              color: const Color(0xFFE2E8F0),
                              child: const Icon(Icons.broken_image_outlined, color: _textMuted),
                            ),
                          )
                        : Container(
                            color: const Color(0xFFE2E8F0),
                            child: const Icon(Iconsax.camera, color: _textMuted),
                          ),
              ),
            ),
          ),
          const SizedBox(width: 12),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Row(
                  children: [
                    Container(
                      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 2),
                      decoration: BoxDecoration(
                        color: _darkBg,
                        borderRadius: BorderRadius.circular(8),
                      ),
                      child: Text(
                        'Titik #${index + 1}',
                        style: const TextStyle(
                          fontFamily: 'Poppins',
                          fontSize: 10.5,
                          fontWeight: FontWeight.w700,
                          color: Colors.white,
                        ),
                      ),
                    ),
                    const Spacer(),
                    InkWell(
                      onTap: () => _removePoint(index),
                      borderRadius: BorderRadius.circular(8),
                      child: const Padding(
                        padding: EdgeInsets.all(4),
                        child: Icon(Iconsax.trash, size: 17, color: Color(0xFFEF4444)),
                      ),
                    ),
                  ],
                ),
                const SizedBox(height: 8),
                TextField(
                  controller: pt.controller,
                  style: const TextStyle(fontFamily: 'Poppins', fontSize: 12.5, color: _textDark),
                  decoration: InputDecoration(
                    hintText: 'Ket. kamera (misal: Gate 1 / Gudang)',
                    hintStyle: const TextStyle(fontFamily: 'Poppins', fontSize: 11.5, color: Color(0xFF94A3B8)),
                    filled: true,
                    fillColor: Colors.white,
                    isDense: true,
                    contentPadding: const EdgeInsets.symmetric(horizontal: 11, vertical: 10),
                    border: OutlineInputBorder(
                      borderRadius: BorderRadius.circular(10),
                      borderSide: const BorderSide(color: Color(0xFFE2E8F0)),
                    ),
                    enabledBorder: OutlineInputBorder(
                      borderRadius: BorderRadius.circular(10),
                      borderSide: const BorderSide(color: Color(0xFFE2E8F0)),
                    ),
                    focusedBorder: OutlineInputBorder(
                      borderRadius: BorderRadius.circular(10),
                      borderSide: const BorderSide(color: _orange),
                    ),
                  ),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildBottomBar(double bottomInset) {
    return Container(
      padding: EdgeInsets.fromLTRB(18, 12, 18, bottomInset > 0 ? bottomInset + 8 : 16),
      decoration: BoxDecoration(
        color: Colors.white,
        border: const Border(top: BorderSide(color: _border)),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.05),
            blurRadius: 16,
            offset: const Offset(0, -4),
          ),
        ],
      ),
      child: SizedBox(
        width: double.infinity,
        height: 52,
        child: ElevatedButton.icon(
          onPressed: _isSubmitting ? null : _submit,
          icon: _isSubmitting
              ? const SizedBox(
                  width: 18,
                  height: 18,
                  child: CircularProgressIndicator(strokeWidth: 2.2, color: Colors.white),
                )
              : const Icon(Iconsax.document_upload, size: 19),
          label: Text(_isSubmitting ? 'Menyimpan BAST...' : 'Simpan Dokumen BAST'),
          style: ElevatedButton.styleFrom(
            backgroundColor: _orange,
            foregroundColor: Colors.white,
            disabledBackgroundColor: _orange.withValues(alpha: 0.6),
            disabledForegroundColor: Colors.white,
            elevation: 0,
            shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
            textStyle: const TextStyle(
              fontFamily: 'Poppins',
              fontSize: 14,
              fontWeight: FontWeight.w700,
            ),
          ),
        ),
      ),
    );
  }
}

