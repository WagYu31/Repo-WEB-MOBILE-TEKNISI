import 'package:flutter/material.dart';
import 'package:iconsax/iconsax.dart';
import 'package:provider/provider.dart';

import '../../service/provider/Pinjam/PinjamGetProvider.dart';
import '../../service/provider/preferences/PreferencesIDProvider.dart';
import '../../utils/state.dart';
import 'DetailPinjamPage.dart';

class PinjamanRiwayatPage extends StatefulWidget {
  const PinjamanRiwayatPage({super.key});

  @override
  State<PinjamanRiwayatPage> createState() => _PinjamanRiwayatPageState();
}

class _PinjamanRiwayatPageState extends State<PinjamanRiwayatPage> {
  // ─── Parclgo Inspired Palette (Matching Gambar 5) ───
  static const Color _primaryOrange = Color(0xFFFF5B22);
  static const Color _darkCharcoal = Color(0xFF11141A);
  static const Color _bgCanvas = Color(0xFFF4F6F8);
  static const Color _successGreen = Color(0xFF059669);
  static const Color _errorRed = Color(0xFFE11D48);
  static const Color _textPrimary = Color(0xFF0F172A);
  static const Color _textSecondary = Color(0xFF64748B);

  String _id = '';

  static const List<String> _days = [
    'Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'
  ];

  static const List<String> _months = [
    'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun',
    'Jul', 'Ags', 'Sep', 'Okt', 'Nov', 'Des'
  ];

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      if (!mounted) return;
      final fetchedId = context.read<PreferencesIDProvider>().isUserRole;
      setState(() {
        _id = fetchedId;
      });
      if (fetchedId.isNotEmpty) {
        Provider.of<PinjamGetProvider>(context, listen: false).getPinjam(fetchedId);
      }
    });
  }

  String _formatDate(DateTime dateTime) {
    final dayName = _days[dateTime.weekday % 7];
    final monthName = _months[dateTime.month - 1];
    return '$dayName, ${dateTime.day} $monthName ${dateTime.year}';
  }

  Color _getStatusColor(String status) {
    switch (status.toLowerCase()) {
      case 'selesai':
        return _successGreen;
      case 'ditolak':
        return _errorRed;
      default:
        return _textSecondary;
    }
  }

  Color _getStatusBg(String status) {
    switch (status.toLowerCase()) {
      case 'selesai':
        return const Color(0xFFECFDF5);
      case 'ditolak':
        return const Color(0xFFFFF1F2);
      default:
        return const Color(0xFFF1F5F9);
    }
  }

  Color _getStatusBorder(String status) {
    switch (status.toLowerCase()) {
      case 'selesai':
        return const Color(0xFFA7F3D0);
      case 'ditolak':
        return const Color(0xFFFECDD3);
      default:
        return const Color(0xFFE2E8F0);
    }
  }

  IconData _getStatusIcon(String status) {
    switch (status.toLowerCase()) {
      case 'selesai':
        return Icons.check_circle_outline_rounded;
      case 'ditolak':
        return Icons.cancel_outlined;
      default:
        return Icons.info_outline_rounded;
    }
  }

  String _getStatusLabel(String status) {
    switch (status.toLowerCase()) {
      case 'selesai':
        return 'Dikembalikan';
      case 'ditolak':
        return 'Ditolak';
      default:
        return status;
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: _bgCanvas,
      body: Consumer<PinjamGetProvider>(
        builder: (context, state, _) {
          if (state.state == ResultState.loading) {
            return const Center(
              child: CircularProgressIndicator(color: _primaryOrange),
            );
          } else if (state.state == ResultState.hasData) {
            final data = state.response.data
                .where((element) =>
                    element.status == 'ditolak' || element.status == 'selesai')
                .toList();

            if (data.isEmpty) {
              return _buildEmptyState();
            }

            return RefreshIndicator(
              color: Colors.white,
              backgroundColor: _darkCharcoal,
              onRefresh: () async {
                if (_id.isNotEmpty) {
                  await Provider.of<PinjamGetProvider>(context, listen: false)
                      .getPinjam(_id);
                }
              },
              child: ListView.builder(
                physics: const BouncingScrollPhysics(
                  parent: AlwaysScrollableScrollPhysics(),
                ),
                padding: const EdgeInsets.fromLTRB(20, 18, 20, 100),
                itemCount: data.length,
                itemBuilder: (context, index) {
                  final pinjamData = data[index];
                  return _buildPinjamCard(pinjamData);
                },
              ),
            );
          } else if (state.state == ResultState.error) {
            return _buildErrorState(state.message);
          } else {
            return _buildEmptyState();
          }
        },
      ),
    );
  }

  Widget _buildPinjamCard(dynamic pinjamData) {
    final statusColor = _getStatusColor(pinjamData.status);
    final statusBg = _getStatusBg(pinjamData.status);
    final statusBorder = _getStatusBorder(pinjamData.status);
    final isSelesai = pinjamData.status.toLowerCase() == 'selesai';

    return Container(
      margin: const EdgeInsets.only(bottom: 14),
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
      child: Material(
        color: Colors.transparent,
        child: InkWell(
          onTap: () {
            Navigator.pushNamed(
              context,
              DetailPinjamPage.routeName,
              arguments: pinjamData.code,
            );
          },
          borderRadius: BorderRadius.circular(22),
          child: Padding(
            padding: const EdgeInsets.all(16),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Row(
                  crossAxisAlignment: CrossAxisAlignment.center,
                  children: [
                    Container(
                      width: 44,
                      height: 44,
                      decoration: BoxDecoration(
                        color: isSelesai
                            ? const Color(0xFFECFDF5)
                            : const Color(0xFFFFF1F2),
                        borderRadius: BorderRadius.circular(13),
                      ),
                      child: Icon(
                        isSelesai ? Iconsax.box_tick : Iconsax.box_remove,
                        color: statusColor,
                        size: 22,
                      ),
                    ),
                    const SizedBox(width: 12),
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Row(
                            children: [
                              Text(
                                'ID: ${pinjamData.code}',
                                style: const TextStyle(
                                  fontFamily: 'Poppins',
                                  fontSize: 15.5,
                                  fontWeight: FontWeight.w700,
                                  color: _textPrimary,
                                  letterSpacing: 0.2,
                                ),
                              ),
                              const SizedBox(width: 8),
                              Container(
                                padding: const EdgeInsets.symmetric(
                                  horizontal: 8,
                                  vertical: 2,
                                ),
                                decoration: BoxDecoration(
                                  color: const Color(0xFFF1F5F9),
                                  borderRadius: BorderRadius.circular(8),
                                ),
                                child: Text(
                                  '${pinjamData.total} Barang',
                                  style: const TextStyle(
                                    fontFamily: 'Poppins',
                                    fontSize: 11,
                                    fontWeight: FontWeight.w600,
                                    color: Color(0xFF475569),
                                  ),
                                ),
                              ),
                            ],
                          ),
                          const SizedBox(height: 5),
                          Container(
                            padding: const EdgeInsets.symmetric(
                              horizontal: 9,
                              vertical: 3.5,
                            ),
                            decoration: BoxDecoration(
                              color: statusBg,
                              borderRadius: BorderRadius.circular(20),
                              border: Border.all(color: statusBorder),
                            ),
                            child: Row(
                              mainAxisSize: MainAxisSize.min,
                              children: [
                                Icon(
                                  _getStatusIcon(pinjamData.status),
                                  size: 13,
                                  color: statusColor,
                                ),
                                const SizedBox(width: 4),
                                Text(
                                  _getStatusLabel(pinjamData.status),
                                  style: TextStyle(
                                    fontFamily: 'Poppins',
                                    fontSize: 11,
                                    fontWeight: FontWeight.w600,
                                    color: statusColor,
                                  ),
                                ),
                              ],
                            ),
                          ),
                        ],
                      ),
                    ),
                  ],
                ),
                const Padding(
                  padding: EdgeInsets.symmetric(vertical: 12),
                  child: Divider(height: 1, color: Color(0xFFF1F5F9)),
                ),
                Row(
                  children: [
                    const Icon(
                      Icons.calendar_today_outlined,
                      size: 14,
                      color: Color(0xFF94A3B8),
                    ),
                    const SizedBox(width: 6),
                    Expanded(
                      child: Text(
                        _formatDate(pinjamData.tglPinjam),
                        style: const TextStyle(
                          fontFamily: 'Poppins',
                          fontSize: 12,
                          fontWeight: FontWeight.w500,
                          color: _textSecondary,
                        ),
                      ),
                    ),
                    Container(
                      width: 30,
                      height: 30,
                      decoration: BoxDecoration(
                        color: const Color(0xFFF8FAFC),
                        borderRadius: BorderRadius.circular(9),
                        border: Border.all(color: const Color(0xFFE2E8F0)),
                      ),
                      child: const Icon(
                        Icons.chevron_right_rounded,
                        size: 18,
                        color: _textSecondary,
                      ),
                    ),
                  ],
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }

  Widget _buildEmptyState() {
    return Center(
      child: Padding(
        padding: const EdgeInsets.symmetric(horizontal: 32),
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Container(
              padding: const EdgeInsets.all(22),
              decoration: const BoxDecoration(
                color: Color(0xFFF1F5F9),
                shape: BoxShape.circle,
              ),
              child: const Icon(
                Iconsax.clock,
                size: 44,
                color: _textSecondary,
              ),
            ),
            const SizedBox(height: 18),
            const Text(
              'Belum Ada Riwayat',
              style: TextStyle(
                fontFamily: 'Poppins',
                fontSize: 16.5,
                fontWeight: FontWeight.w700,
                color: _textPrimary,
              ),
            ),
            const SizedBox(height: 6),
            const Text(
              'Riwayat peminjaman yang sudah dikembalikan akan tampil di sini',
              style: TextStyle(
                fontFamily: 'Poppins',
                fontSize: 13,
                color: _textSecondary,
              ),
              textAlign: TextAlign.center,
            ),
          ],
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
                if (_id.isNotEmpty) {
                  Provider.of<PinjamGetProvider>(context, listen: false)
                      .getPinjam(_id);
                }
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
