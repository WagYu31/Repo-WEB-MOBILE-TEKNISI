import 'dart:io';

import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:iconsax/iconsax.dart';
import 'package:image_picker/image_picker.dart';
import 'package:provider/provider.dart';
import 'package:quickalert/quickalert.dart';

import '../../service/model/pinjam/DetailPinjamGetModel.dart';
import '../../service/provider/Pinjam/KembalikanPinjamProvider.dart';
import '../../service/provider/Pinjam/PinjamGetProvider.dart';
import '../../service/provider/preferences/PreferencesIDProvider.dart';

class KembalikanPinjamanPage extends StatefulWidget {
  static const routeName = 'kembalikan_pinjaman_page';
  final List<dynamic> data;

  const KembalikanPinjamanPage({super.key, required this.data});

  @override
  State<KembalikanPinjamanPage> createState() => _KembalikanPinjamanPageState();
}

class _KembalikanPinjamanPageState extends State<KembalikanPinjamanPage> {
  // ─── Parclgo Inspired Palette (Matching Gambar 5) ───
  static const Color _darkHeader = Color(0xFF11141B);
  static const Color _darkSurface = Color(0xFF1E2330);
  static const Color _primaryOrange = Color(0xFFFF5B22);
  static const Color _bgCanvas = Color(0xFFF4F6F8);
  static const Color _successGreen = Color(0xFF059669);
  static const Color _textPrimary = Color(0xFF0F172A);
  static const Color _textSecondary = Color(0xFF64748B);

  final _keteranganController = TextEditingController();
  final _formKey = GlobalKey<FormState>();
  final ImagePicker _picker = ImagePicker();

  File? _imagePath;
  XFile? _selectedImage;
  bool _isSubmitting = false;
  late List<Barang> _barangList;

  @override
  void initState() {
    super.initState();
    _barangList = widget.data[1] as List<Barang>;
  }

  @override
  void dispose() {
    _keteranganController.dispose();
    super.dispose();
  }

  Future<void> _pickImage(ImageSource source) async {
    try {
      final XFile? pickedImage = await _picker.pickImage(
        source: source,
        maxWidth: 1200,
        maxHeight: 1200,
        imageQuality: 80,
      );

      if (pickedImage != null && mounted) {
        setState(() {
          _selectedImage = pickedImage;
          _imagePath = File(pickedImage.path);
        });
      }
    } catch (e) {
      if (mounted) {
        _showAlert(
          type: QuickAlertType.error,
          title: 'Gagal',
          text: 'Gagal mengambil gambar. Silakan coba lagi.',
        );
      }
    }
  }

  void _showImageSourceDialog() {
    showModalBottomSheet(
      context: context,
      backgroundColor: Colors.transparent,
      builder: (context) => Container(
        padding: const EdgeInsets.all(20),
        decoration: const BoxDecoration(
          color: Colors.white,
          borderRadius: BorderRadius.vertical(top: Radius.circular(28)),
        ),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Container(
              width: 44,
              height: 4.5,
              margin: const EdgeInsets.only(bottom: 18),
              decoration: BoxDecoration(
                color: const Color(0xFFCBD5E1),
                borderRadius: BorderRadius.circular(3),
              ),
            ),
            const Text(
              'Pilih Sumber Foto',
              style: TextStyle(
                fontFamily: 'Poppins',
                fontSize: 17,
                fontWeight: FontWeight.w700,
                color: _textPrimary,
              ),
            ),
            const SizedBox(height: 18),
            Row(
              mainAxisAlignment: MainAxisAlignment.spaceEvenly,
              children: [
                _buildImageSourceOption(
                  icon: Iconsax.camera,
                  label: 'Kamera',
                  onTap: () {
                    Navigator.pop(context);
                    _pickImage(ImageSource.camera);
                  },
                ),
                _buildImageSourceOption(
                  icon: Iconsax.gallery,
                  label: 'Galeri',
                  onTap: () {
                    Navigator.pop(context);
                    _pickImage(ImageSource.gallery);
                  },
                ),
              ],
            ),
            SizedBox(height: MediaQuery.of(context).padding.bottom + 10),
          ],
        ),
      ),
    );
  }

  Widget _buildImageSourceOption({
    required IconData icon,
    required String label,
    required VoidCallback onTap,
  }) {
    return InkWell(
      onTap: onTap,
      borderRadius: BorderRadius.circular(18),
      child: Container(
        width: 120,
        padding: const EdgeInsets.symmetric(vertical: 18),
        decoration: BoxDecoration(
          color: const Color(0xFFFFF2EC),
          borderRadius: BorderRadius.circular(18),
          border: Border.all(color: const Color(0xFFFED7AA)),
        ),
        child: Column(
          children: [
            Icon(icon, size: 30, color: _primaryOrange),
            const SizedBox(height: 8),
            Text(
              label,
              style: const TextStyle(
                fontFamily: 'Poppins',
                fontSize: 13.5,
                fontWeight: FontWeight.w600,
                color: _primaryOrange,
              ),
            ),
          ],
        ),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return AnnotatedRegion<SystemUiOverlayStyle>(
      value: SystemUiOverlayStyle.light,
      child: Scaffold(
        backgroundColor: _bgCanvas,
        body: Column(
          children: [
            _buildParclgoHeader(),
            Expanded(
              child: SingleChildScrollView(
                physics: const BouncingScrollPhysics(),
                padding: const EdgeInsets.fromLTRB(20, 18, 20, 24),
                child: Form(
                  key: _formKey,
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      _buildPhotoSection(),
                      const SizedBox(height: 16),
                      _buildDescriptionSection(),
                      const SizedBox(height: 16),
                      _buildItemsSection(),
                    ],
                  ),
                ),
              ),
            ),
            _buildBottomAction(),
          ],
        ),
      ),
    );
  }

  Widget _buildParclgoHeader() {
    final code = widget.data[0] as String;

    return Container(
      decoration: const BoxDecoration(
        color: _darkHeader,
        borderRadius: BorderRadius.only(
          bottomLeft: Radius.circular(32),
          bottomRight: Radius.circular(32),
        ),
      ),
      child: SafeArea(
        bottom: false,
        child: Padding(
          padding: const EdgeInsets.fromLTRB(20, 12, 20, 20),
          child: Row(
            children: [
              GestureDetector(
                onTap: () => Navigator.pop(context),
                child: Container(
                  width: 44,
                  height: 44,
                  decoration: BoxDecoration(
                    color: _darkSurface,
                    shape: BoxShape.circle,
                    border: Border.all(
                      color: Colors.white.withValues(alpha: 0.1),
                    ),
                  ),
                  child: const Icon(
                    Icons.arrow_back_ios_new_rounded,
                    color: Colors.white,
                    size: 18,
                  ),
                ),
              ),
              const SizedBox(width: 14),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      'Kode Pinjam • $code',
                      style: TextStyle(
                        fontFamily: 'Poppins',
                        fontSize: 11.5,
                        fontWeight: FontWeight.w500,
                        color: Colors.white.withValues(alpha: 0.65),
                      ),
                    ),
                    const Text(
                      'Kembalikan Barang',
                      style: TextStyle(
                        fontFamily: 'Poppins',
                        fontSize: 19,
                        fontWeight: FontWeight.w700,
                        color: Colors.white,
                        letterSpacing: -0.2,
                      ),
                    ),
                  ],
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildPhotoSection() {
    return Container(
      padding: const EdgeInsets.all(18),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(22),
        border: Border.all(color: const Color(0xFFEDF1F5)),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.04),
            blurRadius: 16,
            offset: const Offset(0, 6),
          ),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Container(
                width: 42,
                height: 42,
                decoration: BoxDecoration(
                  color: const Color(0xFFFFF2EC),
                  borderRadius: BorderRadius.circular(12),
                ),
                child: const Icon(
                  Iconsax.camera,
                  color: _primaryOrange,
                  size: 20,
                ),
              ),
              const SizedBox(width: 12),
              const Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      'Bukti Foto Pengembalian',
                      style: TextStyle(
                        fontFamily: 'Poppins',
                        fontSize: 15,
                        fontWeight: FontWeight.w700,
                        color: _textPrimary,
                      ),
                    ),
                    Text(
                      'Ambil foto kondisi alat yang dikembalikan',
                      style: TextStyle(
                        fontFamily: 'Poppins',
                        fontSize: 12,
                        color: _textSecondary,
                      ),
                    ),
                  ],
                ),
              ),
            ],
          ),
          const SizedBox(height: 16),
          if (_imagePath != null) ...[
            Stack(
              children: [
                ClipRRect(
                  borderRadius: BorderRadius.circular(14),
                  child: Image.file(
                    _imagePath!,
                    width: double.infinity,
                    height: 200,
                    fit: BoxFit.cover,
                  ),
                ),
                Positioned(
                  top: 8,
                  right: 8,
                  child: GestureDetector(
                    onTap: () {
                      setState(() {
                        _imagePath = null;
                        _selectedImage = null;
                      });
                    },
                    child: Container(
                      padding: const EdgeInsets.all(6),
                      decoration: BoxDecoration(
                        color: Colors.black.withValues(alpha: 0.6),
                        borderRadius: BorderRadius.circular(8),
                      ),
                      child: const Icon(
                        Icons.close_rounded,
                        color: Colors.white,
                        size: 18,
                      ),
                    ),
                  ),
                ),
              ],
            ),
            const SizedBox(height: 12),
            SizedBox(
              width: double.infinity,
              child: OutlinedButton.icon(
                onPressed: _showImageSourceDialog,
                icon: const Icon(Icons.refresh_rounded, size: 18),
                label: const Text(
                  'Ganti Foto',
                  style: TextStyle(
                    fontFamily: 'Poppins',
                    fontWeight: FontWeight.w600,
                  ),
                ),
                style: OutlinedButton.styleFrom(
                  foregroundColor: _primaryOrange,
                  side: const BorderSide(color: _primaryOrange),
                  padding: const EdgeInsets.symmetric(vertical: 12),
                  shape: RoundedRectangleBorder(
                    borderRadius: BorderRadius.circular(12),
                  ),
                ),
              ),
            ),
          ] else
            GestureDetector(
              onTap: _showImageSourceDialog,
              child: Container(
                width: double.infinity,
                height: 150,
                decoration: BoxDecoration(
                  color: const Color(0xFFF8FAFC),
                  borderRadius: BorderRadius.circular(16),
                  border: Border.all(
                    color: const Color(0xFFE2E8F0),
                    width: 1.5,
                  ),
                ),
                child: Column(
                  mainAxisAlignment: MainAxisAlignment.center,
                  children: [
                    Container(
                      padding: const EdgeInsets.all(13),
                      decoration: BoxDecoration(
                        color: const Color(0xFFFFF2EC),
                        borderRadius: BorderRadius.circular(14),
                      ),
                      child: const Icon(
                        Iconsax.camera,
                        color: _primaryOrange,
                        size: 26,
                      ),
                    ),
                    const SizedBox(height: 10),
                    const Text(
                      'Tap untuk mengambil foto bukti',
                      style: TextStyle(
                        fontFamily: 'Poppins',
                        fontSize: 13,
                        fontWeight: FontWeight.w600,
                        color: _textSecondary,
                      ),
                    ),
                  ],
                ),
              ),
            ),
        ],
      ),
    );
  }

  Widget _buildDescriptionSection() {
    return Container(
      padding: const EdgeInsets.all(18),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(22),
        border: Border.all(color: const Color(0xFFEDF1F5)),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.04),
            blurRadius: 16,
            offset: const Offset(0, 6),
          ),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          const Text(
            'Keterangan',
            style: TextStyle(
              fontFamily: 'Poppins',
              fontSize: 15,
              fontWeight: FontWeight.w700,
              color: _textPrimary,
            ),
          ),
          const SizedBox(height: 12),
          TextFormField(
            controller: _keteranganController,
            maxLines: 3,
            style: const TextStyle(
              fontFamily: 'Poppins',
              fontSize: 13.5,
              color: _textPrimary,
            ),
            decoration: InputDecoration(
              hintText: 'Tambahkan catatan kondisi barang (opsional)',
              hintStyle: const TextStyle(
                fontFamily: 'Poppins',
                fontSize: 13,
                color: Color(0xFF94A3B8),
              ),
              filled: true,
              fillColor: const Color(0xFFF8FAFC),
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
                borderSide: const BorderSide(color: _primaryOrange, width: 1.8),
              ),
              contentPadding: const EdgeInsets.all(14),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildItemsSection() {
    return Container(
      padding: const EdgeInsets.all(18),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(22),
        border: Border.all(color: const Color(0xFFEDF1F5)),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.04),
            blurRadius: 16,
            offset: const Offset(0, 6),
          ),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              const Text(
                'Barang Dikembalikan',
                style: TextStyle(
                  fontFamily: 'Poppins',
                  fontSize: 15,
                  fontWeight: FontWeight.w700,
                  color: _textPrimary,
                ),
              ),
              const SizedBox(width: 8),
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 9, vertical: 3),
                decoration: BoxDecoration(
                  color: _darkHeader,
                  borderRadius: BorderRadius.circular(10),
                ),
                child: Text(
                  '${_barangList.length} Item',
                  style: const TextStyle(
                    fontFamily: 'Poppins',
                    fontSize: 11,
                    fontWeight: FontWeight.w700,
                    color: Colors.white,
                  ),
                ),
              ),
            ],
          ),
          const SizedBox(height: 14),
          ..._barangList.map((barang) => _buildItemTile(barang)),
        ],
      ),
    );
  }

  Widget _buildItemTile(Barang barang) {
    final hasDesc =
        barang.deskripsi.trim().isNotEmpty && barang.deskripsi.trim() != '-';

    return Container(
      margin: const EdgeInsets.only(bottom: 10),
      padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
      decoration: BoxDecoration(
        color: const Color(0xFFF8FAFC),
        borderRadius: BorderRadius.circular(14),
        border: Border.all(color: const Color(0xFFE2E8F0)),
      ),
      child: Row(
        children: [
          Container(
            width: 42,
            height: 42,
            decoration: BoxDecoration(
              color: const Color(0xFFECFDF5),
              borderRadius: BorderRadius.circular(12),
            ),
            child: const Icon(
              Iconsax.box_tick,
              color: _successGreen,
              size: 20,
            ),
          ),
          const SizedBox(width: 12),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  barang.namaBarang,
                  style: const TextStyle(
                    fontFamily: 'Poppins',
                    fontSize: 14,
                    fontWeight: FontWeight.w600,
                    color: _textPrimary,
                  ),
                ),
                if (hasDesc) ...[
                  const SizedBox(height: 2),
                  Text(
                    barang.deskripsi,
                    style: const TextStyle(
                      fontFamily: 'Poppins',
                      fontSize: 11.5,
                      color: _textSecondary,
                    ),
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                  ),
                ],
              ],
            ),
          ),
          Container(
            padding: const EdgeInsets.symmetric(horizontal: 11, vertical: 5),
            decoration: BoxDecoration(
              color: const Color(0xFFFFF2EC),
              borderRadius: BorderRadius.circular(9),
              border: Border.all(color: const Color(0xFFFED7AA)),
            ),
            child: Text(
              'x${barang.qty}',
              style: const TextStyle(
                fontFamily: 'Poppins',
                fontSize: 12.5,
                fontWeight: FontWeight.w700,
                color: _primaryOrange,
              ),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildBottomAction() {
    final bottomPadding = MediaQuery.of(context).padding.bottom;
    final isValid = _imagePath != null;

    return Container(
      padding: EdgeInsets.fromLTRB(20, 14, 20, 14 + bottomPadding),
      decoration: BoxDecoration(
        color: Colors.white,
        border: const Border(top: BorderSide(color: Color(0xFFEDF1F5))),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.05),
            blurRadius: 16,
            offset: const Offset(0, -4),
          ),
        ],
      ),
      child: Container(
        width: double.infinity,
        height: 52,
        decoration: BoxDecoration(
          gradient: isValid
              ? const LinearGradient(
                  colors: [Color(0xFFFF5B22), Color(0xFFFF7A00)],
                  begin: Alignment.centerLeft,
                  end: Alignment.centerRight,
                )
              : null,
          color: isValid ? null : const Color(0xFFE2E8F0),
          borderRadius: BorderRadius.circular(16),
          boxShadow: isValid
              ? [
                  BoxShadow(
                    color: _primaryOrange.withValues(alpha: 0.32),
                    blurRadius: 14,
                    offset: const Offset(0, 5),
                  ),
                ]
              : null,
        ),
        child: Material(
          color: Colors.transparent,
          child: InkWell(
            borderRadius: BorderRadius.circular(16),
            onTap: isValid && !_isSubmitting ? _sendReport : null,
            child: Row(
              mainAxisAlignment: MainAxisAlignment.center,
              children: [
                if (_isSubmitting)
                  const SizedBox(
                    width: 20,
                    height: 20,
                    child: CircularProgressIndicator(
                      strokeWidth: 2.2,
                      valueColor: AlwaysStoppedAnimation<Color>(Colors.white),
                    ),
                  )
                else
                  Icon(
                    Iconsax.send_1,
                    size: 19,
                    color: isValid ? Colors.white : const Color(0xFF94A3B8),
                  ),
                const SizedBox(width: 8),
                Text(
                  _isSubmitting ? 'Mengirim...' : 'Kirim Laporan',
                  style: TextStyle(
                    fontFamily: 'Poppins',
                    fontSize: 15,
                    fontWeight: FontWeight.w700,
                    color: isValid ? Colors.white : const Color(0xFF94A3B8),
                  ),
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }

  Future<void> _sendReport() async {
    if (_selectedImage == null) {
      _showAlert(
        type: QuickAlertType.warning,
        title: 'Foto Diperlukan',
        text: 'Silakan ambil foto bukti pengembalian barang.',
      );
      return;
    }

    QuickAlert.show(
      context: context,
      type: QuickAlertType.confirm,
      title: 'Konfirmasi Pengembalian',
      text: 'Apakah Anda yakin ingin mengembalikan barang-barang ini?',
      confirmBtnText: 'Ya, Kembalikan',
      cancelBtnText: 'Batal',
      confirmBtnColor: _primaryOrange,
      onConfirmBtnTap: () async {
        Navigator.pop(context);
        await _processReturn();
      },
    );
  }

  Future<void> _processReturn() async {
    setState(() {
      _isSubmitting = true;
    });

    QuickAlert.show(
      context: context,
      type: QuickAlertType.loading,
      title: 'Mengirim Laporan',
      text: 'Mohon tunggu sebentar...',
    );

    final kembalikanProvider =
        Provider.of<KembalikanPinjamProvider>(context, listen: false);
    final pinjamGetProvider =
        Provider.of<PinjamGetProvider>(context, listen: false);
    final userId = context.read<PreferencesIDProvider>().isUserRole;

    try {
      final Uint8List bytes = await _selectedImage!.readAsBytes();
      final compressedBytes = await kembalikanProvider.compressImage(bytes);

      await kembalikanProvider.kembalikanBarang(
        Uint8List.fromList(compressedBytes),
        _keteranganController.text,
        widget.data[0] as String,
      );

      if (!mounted) return;
      Navigator.pop(context);

      if (userId.isNotEmpty) {
        await pinjamGetProvider.getPinjam(userId);
      }

      if (!mounted) return;

      QuickAlert.show(
        context: context,
        type: QuickAlertType.success,
        title: 'Berhasil!',
        text: 'Laporan pengembalian berhasil dikirim.',
        confirmBtnColor: _primaryOrange,
        onConfirmBtnTap: () {
          Navigator.pop(context);
          Navigator.pop(context);
          Navigator.pop(context);
        },
      );
    } catch (e) {
      if (!mounted) return;
      Navigator.pop(context);

      _showAlert(
        type: QuickAlertType.error,
        title: 'Gagal',
        text: 'Terjadi kesalahan saat mengirim laporan. Silakan coba lagi.',
      );
    } finally {
      if (mounted) {
        setState(() {
          _isSubmitting = false;
        });
      }
    }
  }

  void _showAlert({
    required QuickAlertType type,
    required String title,
    required String text,
  }) {
    QuickAlert.show(
      context: context,
      type: type,
      title: title,
      text: text,
      confirmBtnColor: _primaryOrange,
    );
  }
}
