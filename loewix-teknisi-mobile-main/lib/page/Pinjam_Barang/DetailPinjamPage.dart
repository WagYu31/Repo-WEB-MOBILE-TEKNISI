import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:iconsax/iconsax.dart';
import 'package:intl/intl.dart';
import 'package:provider/provider.dart';

import '../../../service/model/pinjam/DetailPinjamGetModel.dart';
import '../../page/Pinjam_Barang/KembalikanPinjamanPage.dart';
import '../../service/provider/Pinjam/DetailPinjamGetProvider.dart';
import '../../utils/state.dart';

class DetailPinjamPage extends StatefulWidget {
  static const routeName = '/detail_pinjam_page';
  final String code;

  const DetailPinjamPage({super.key, required this.code});

  @override
  State<DetailPinjamPage> createState() => _DetailPinjamPageState();
}

class _DetailPinjamPageState extends State<DetailPinjamPage> {
  // ─── Parclgo Inspired Palette (Matching Gambar 5) ───
  static const Color _darkHeader = Color(0xFF11141B);
  static const Color _darkSurface = Color(0xFF1E2330);
  static const Color _primaryOrange = Color(0xFFFF5B22);
  static const Color _bgCanvas = Color(0xFFF4F6F8);
  static const Color _successGreen = Color(0xFF059669);
  static const Color _warningAmber = Color(0xFFD97706);
  static const Color _errorRed = Color(0xFFE11D48);
  static const Color _textPrimary = Color(0xFF0F172A);
  static const Color _textSecondary = Color(0xFF64748B);

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      if (!mounted) return;
      Provider.of<DetailPinjamGetProvider>(context, listen: false)
          .getDetailPinjam(widget.code);
    });
  }

  Color _getStatusColor(String status) {
    switch (status.toLowerCase()) {
      case 'diajukan':
        return _warningAmber;
      case 'dipinjam':
        return _successGreen;
      case 'pengembalian':
        return _primaryOrange;
      case 'selesai':
        return _successGreen;
      case 'ditolak':
        return _errorRed;
      default:
        return _textSecondary;
    }
  }

  Color _getStatusSoftBg(String status) {
    switch (status.toLowerCase()) {
      case 'diajukan':
        return const Color(0xFFFFFBEB);
      case 'dipinjam':
        return const Color(0xFFECFDF5);
      case 'pengembalian':
        return const Color(0xFFFFF2EC);
      case 'selesai':
        return const Color(0xFFECFDF5);
      case 'ditolak':
        return const Color(0xFFFFF1F2);
      default:
        return const Color(0xFFF1F5F9);
    }
  }

  IconData _getStatusIcon(String status) {
    switch (status.toLowerCase()) {
      case 'diajukan':
        return Iconsax.timer_1;
      case 'dipinjam':
        return Iconsax.box_tick;
      case 'pengembalian':
        return Iconsax.refresh;
      case 'selesai':
        return Iconsax.tick_circle;
      case 'ditolak':
        return Iconsax.close_circle;
      default:
        return Iconsax.info_circle;
    }
  }

  String _getStatusDescription(String status) {
    switch (status.toLowerCase()) {
      case 'diajukan':
        return 'Pengajuan sedang menunggu persetujuan admin';
      case 'dipinjam':
        return 'Barang sudah dipinjam dan sedang digunakan';
      case 'pengembalian':
        return 'Menunggu konfirmasi pengembalian dari admin';
      case 'selesai':
        return 'Barang telah dikembalikan dengan sukses';
      case 'ditolak':
        return 'Pengajuan peminjaman ditolak oleh admin';
      default:
        return '-';
    }
  }

  String _getStatusLabel(String status) {
    switch (status.toLowerCase()) {
      case 'diajukan':
        return 'Menunggu Persetujuan';
      case 'dipinjam':
        return 'Sedang Dipinjam';
      case 'pengembalian':
        return 'Proses Pengembalian';
      case 'selesai':
        return 'Dikembalikan';
      case 'ditolak':
        return 'Ditolak';
      default:
        return status;
    }
  }

  String _formatDateSafe(DateTime dt) {
    try {
      return DateFormat('dd MMMM yyyy', 'id_ID').format(dt);
    } catch (_) {
      const months = [
        'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
      ];
      return '${dt.day} ${months[dt.month - 1]} ${dt.year}';
    }
  }

  @override
  Widget build(BuildContext context) {
    return AnnotatedRegion<SystemUiOverlayStyle>(
      value: SystemUiOverlayStyle.light,
      child: Scaffold(
        backgroundColor: _bgCanvas,
        body: Consumer<DetailPinjamGetProvider>(
          builder: (context, state, _) {
            if (state.state == ResultState.loading) {
              return Column(
                children: [
                  _buildTopDarkBar(null),
                  const Expanded(
                    child: Center(
                      child: CircularProgressIndicator(color: _primaryOrange),
                    ),
                  ),
                ],
              );
            } else if (state.state == ResultState.hasData) {
              final data = state.response.data;
              return _buildContent(data);
            } else {
              return Column(
                children: [
                  _buildTopDarkBar(null),
                  Expanded(child: _buildErrorState(state.message)),
                ],
              );
            }
          },
        ),
      ),
    );
  }

  Widget _buildTopDarkBar(dynamic data) {
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
          padding: EdgeInsets.fromLTRB(20, 12, 20, data != null ? 24 : 20),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Row(
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
                          'ID Peminjaman • ${widget.code}',
                          style: TextStyle(
                            fontFamily: 'Poppins',
                            fontSize: 11.5,
                            fontWeight: FontWeight.w500,
                            color: Colors.white.withValues(alpha: 0.65),
                          ),
                        ),
                        const Text(
                          'Detail Peminjaman',
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
              if (data != null) ...[
                const SizedBox(height: 18),
                _buildHeroStatusBanner(data),
              ],
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildHeroStatusBanner(dynamic data) {
    final statusColor = _getStatusColor(data.status);
    final statusSoftBg = _getStatusSoftBg(data.status);

    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(20),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.14),
            blurRadius: 16,
            offset: const Offset(0, 6),
          ),
        ],
      ),
      child: Column(
        children: [
          Row(
            children: [
              Container(
                width: 50,
                height: 50,
                decoration: BoxDecoration(
                  color: statusSoftBg,
                  borderRadius: BorderRadius.circular(14),
                ),
                child: Icon(
                  _getStatusIcon(data.status),
                  color: statusColor,
                  size: 26,
                ),
              ),
              const SizedBox(width: 14),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      _getStatusLabel(data.status),
                      style: const TextStyle(
                        fontFamily: 'Poppins',
                        fontSize: 15.5,
                        fontWeight: FontWeight.w700,
                        color: _textPrimary,
                      ),
                    ),
                    const SizedBox(height: 2),
                    Text(
                      _getStatusDescription(data.status),
                      style: const TextStyle(
                        fontFamily: 'Poppins',
                        fontSize: 12,
                        color: _textSecondary,
                      ),
                      maxLines: 2,
                      overflow: TextOverflow.ellipsis,
                    ),
                  ],
                ),
              ),
            ],
          ),
          const SizedBox(height: 14),
          Container(
            padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 10),
            decoration: BoxDecoration(
              color: const Color(0xFFF8FAFC),
              borderRadius: BorderRadius.circular(12),
              border: Border.all(color: const Color(0xFFE2E8F0)),
            ),
            child: Row(
              children: [
                const Icon(
                  Icons.qr_code_2_rounded,
                  size: 20,
                  color: _primaryOrange,
                ),
                const SizedBox(width: 10),
                const Text(
                  'Kode Pinjam',
                  style: TextStyle(
                    fontFamily: 'Poppins',
                    fontSize: 12,
                    fontWeight: FontWeight.w500,
                    color: _textSecondary,
                  ),
                ),
                const Spacer(),
                Container(
                  padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 3),
                  decoration: BoxDecoration(
                    color: _darkHeader,
                    borderRadius: BorderRadius.circular(8),
                  ),
                  child: Text(
                    data.code,
                    style: const TextStyle(
                      fontFamily: 'Poppins',
                      fontSize: 13,
                      fontWeight: FontWeight.w700,
                      color: Colors.white,
                      letterSpacing: 1.2,
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

  Widget _buildContent(dynamic data) {
    final statusLower = data.status.toLowerCase();
    final canReturn = statusLower == 'dipinjam';

    return Column(
      children: [
        _buildTopDarkBar(data),
        Expanded(
          child: SingleChildScrollView(
            physics: const BouncingScrollPhysics(),
            padding: const EdgeInsets.fromLTRB(20, 18, 20, 24),
            child: Column(
              children: [
                _buildInfoCard(data),
                const SizedBox(height: 16),
                _buildItemsCard(data.barang),
              ],
            ),
          ),
        ),
        if (canReturn) _buildBottomAction(data),
      ],
    );
  }

  Widget _buildInfoCard(dynamic data) {
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
            'Informasi Peminjaman',
            style: TextStyle(
              fontFamily: 'Poppins',
              fontSize: 15.5,
              fontWeight: FontWeight.w700,
              color: _textPrimary,
            ),
          ),
          const SizedBox(height: 16),
          _buildInfoRow(
            icon: Iconsax.user,
            iconBg: const Color(0xFFFFF2EC),
            iconColor: _primaryOrange,
            label: 'Nama Teknisi',
            value: data.teknisi.nama,
          ),
          const Padding(
            padding: EdgeInsets.symmetric(vertical: 14),
            child: Divider(height: 1, color: Color(0xFFF1F5F9)),
          ),
          _buildInfoRow(
            icon: Iconsax.calendar_1,
            iconBg: const Color(0xFFF1F5F9),
            iconColor: const Color(0xFF334155),
            label: 'Tanggal Pinjam',
            value: _formatDateSafe(data.tglPinjam),
          ),
        ],
      ),
    );
  }

  Widget _buildInfoRow({
    required IconData icon,
    required Color iconBg,
    required Color iconColor,
    required String label,
    required String value,
  }) {
    return Row(
      children: [
        Container(
          width: 42,
          height: 42,
          decoration: BoxDecoration(
            color: iconBg,
            borderRadius: BorderRadius.circular(12),
          ),
          child: Icon(icon, size: 20, color: iconColor),
        ),
        const SizedBox(width: 14),
        Expanded(
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(
                label,
                style: const TextStyle(
                  fontFamily: 'Poppins',
                  fontSize: 11.5,
                  color: _textSecondary,
                ),
              ),
              const SizedBox(height: 2),
              Text(
                value,
                style: const TextStyle(
                  fontFamily: 'Poppins',
                  fontSize: 14.5,
                  fontWeight: FontWeight.w600,
                  color: _textPrimary,
                ),
              ),
            ],
          ),
        ),
      ],
    );
  }

  Widget _buildItemsCard(List<Barang> barangList) {
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
                'Barang Dipinjam',
                style: TextStyle(
                  fontFamily: 'Poppins',
                  fontSize: 15.5,
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
                  '${barangList.length} Item',
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
          ...barangList.map((barang) => _buildItemTile(barang)),
        ],
      ),
    );
  }

  Widget _buildItemTile(Barang barang) {
    final hasDesc = barang.deskripsi.trim().isNotEmpty && barang.deskripsi.trim() != '-';

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
              color: const Color(0xFFFFF2EC),
              borderRadius: BorderRadius.circular(12),
            ),
            child: const Icon(
              Iconsax.box,
              color: _primaryOrange,
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
              color: const Color(0xFFECFDF5),
              borderRadius: BorderRadius.circular(9),
              border: Border.all(color: const Color(0xFFA7F3D0)),
            ),
            child: Text(
              'x${barang.qty}',
              style: const TextStyle(
                fontFamily: 'Poppins',
                fontSize: 12.5,
                fontWeight: FontWeight.w700,
                color: _successGreen,
              ),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildBottomAction(dynamic data) {
    final bottomPadding = MediaQuery.of(context).padding.bottom;

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
          gradient: const LinearGradient(
            colors: [Color(0xFFFF5B22), Color(0xFFFF7A00)],
            begin: Alignment.centerLeft,
            end: Alignment.centerRight,
          ),
          borderRadius: BorderRadius.circular(16),
          boxShadow: [
            BoxShadow(
              color: _primaryOrange.withValues(alpha: 0.32),
              blurRadius: 14,
              offset: const Offset(0, 5),
            ),
          ],
        ),
        child: Material(
          color: Colors.transparent,
          child: InkWell(
            borderRadius: BorderRadius.circular(16),
            onTap: () {
              Navigator.pushNamed(
                context,
                KembalikanPinjamanPage.routeName,
                arguments: [widget.code, data.barang],
              );
            },
            child: const Row(
              mainAxisAlignment: MainAxisAlignment.center,
              children: [
                Icon(Iconsax.box_tick, color: Colors.white, size: 20),
                SizedBox(width: 10),
                Text(
                  'Kembalikan Barang',
                  style: TextStyle(
                    fontFamily: 'Poppins',
                    fontSize: 15,
                    fontWeight: FontWeight.w700,
                    color: Colors.white,
                  ),
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }

  Widget _buildErrorState(String message) {
    return Center(
      child: Padding(
        padding: const EdgeInsets.all(32),
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Container(
              padding: const EdgeInsets.all(22),
              decoration: const BoxDecoration(
                color: Color(0xFFFFF1F2),
                shape: BoxShape.circle,
              ),
              child: const Icon(
                Icons.error_outline_rounded,
                size: 48,
                color: _errorRed,
              ),
            ),
            const SizedBox(height: 18),
            const Text(
              'Terjadi Kesalahan',
              style: TextStyle(
                fontFamily: 'Poppins',
                fontSize: 17,
                fontWeight: FontWeight.w700,
                color: _textPrimary,
              ),
            ),
            const SizedBox(height: 6),
            Text(
              message,
              style: const TextStyle(
                fontFamily: 'Poppins',
                fontSize: 13,
                color: _textSecondary,
              ),
              textAlign: TextAlign.center,
            ),
            const SizedBox(height: 22),
            ElevatedButton.icon(
              onPressed: () {
                Provider.of<DetailPinjamGetProvider>(context, listen: false)
                    .getDetailPinjam(widget.code);
              },
              icon: const Icon(Icons.refresh_rounded, size: 18),
              label: const Text(
                'Coba Lagi',
                style: TextStyle(
                  fontFamily: 'Poppins',
                  fontWeight: FontWeight.w600,
                ),
              ),
              style: ElevatedButton.styleFrom(
                backgroundColor: _primaryOrange,
                foregroundColor: Colors.white,
                elevation: 0,
                padding: const EdgeInsets.symmetric(horizontal: 24, vertical: 12),
                shape: RoundedRectangleBorder(
                  borderRadius: BorderRadius.circular(14),
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }
}
