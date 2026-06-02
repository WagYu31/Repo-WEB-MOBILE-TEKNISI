import '../../service/provider/Pinjam/PinjamDeleteProvider.dart';
import '../../service/provider/Pinjam/PinjamGetProvider.dart';
import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:quickalert/quickalert.dart';
import '../../service/provider/preferences/PreferencesIDProvider.dart';
import '../../utils/state.dart';
import 'DetailPinjamPage.dart';

class PinjamanAktifPage extends StatefulWidget {
  const PinjamanAktifPage({super.key});

  @override
  State<PinjamanAktifPage> createState() => _PinjamanAktifPageState();
}

class _PinjamanAktifPageState extends State<PinjamanAktifPage> {
  // Modern color scheme
  static const Color _primaryBlue = Color(0xFF2563EB);
  static const Color _successGreen = Color(0xFF10B981);
  static const Color _warningAmber = Color(0xFFF59E0B);
  static const Color _errorRed = Color(0xFFEF4444);
  static const Color _textPrimary = Color(0xFF1F2937);
  static const Color _textSecondary = Color(0xFF6B7280);

  String _id = '';

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
    final monthName = _months[dateTime.month - 1];
    return '${dateTime.day} $monthName ${dateTime.year}';
  }

  Color _getStatusColor(String status) {
    switch (status.toLowerCase()) {
      case 'diajukan':
        return _warningAmber;
      case 'dipinjam':
        return _successGreen;
      case 'pengembalian':
        return _primaryBlue;
      default:
        return _textSecondary;
    }
  }

  IconData _getStatusIcon(String status) {
    switch (status.toLowerCase()) {
      case 'diajukan':
        return Icons.pending_outlined;
      case 'dipinjam':
        return Icons.check_circle_outline;
      case 'pengembalian':
        return Icons.assignment_return_outlined;
      default:
        return Icons.info_outline;
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
      default:
        return status;
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xFFF9FAFB),
      body: Consumer<PinjamGetProvider>(
        builder: (context, state, _) {
          if (state.state == ResultState.loading) {
            return const Center(
              child: CircularProgressIndicator(color: _primaryBlue),
            );
          } else if (state.state == ResultState.hasData) {
            final data = state.response.data
                .where((element) =>
                    element.status == 'diajukan' ||
                    element.status == 'dipinjam' ||
                    element.status == 'pengembalian')
                .toList();

            if (data.isEmpty) {
              return _buildEmptyState();
            }

            return RefreshIndicator(
              color: _primaryBlue,
              onRefresh: () async {
                if (_id.isNotEmpty) {
                  await Provider.of<PinjamGetProvider>(context, listen: false).getPinjam(_id);
                }
              },
              child: ListView.builder(
                padding: const EdgeInsets.all(16),
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

    return Container(
      margin: const EdgeInsets.only(bottom: 12),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(16),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.04),
            blurRadius: 8,
            offset: const Offset(0, 2),
          ),
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.02),
            blurRadius: 24,
            offset: const Offset(0, 8),
          ),
        ],
      ),
      child: ClipRRect(
        borderRadius: BorderRadius.circular(16),
        child: Column(
          children: [
            // Status indicator bar
            Container(
              height: 4,
              color: statusColor,
            ),
            InkWell(
              onTap: () {
                Navigator.pushNamed(
                  context,
                  DetailPinjamPage.routeName,
                  arguments: pinjamData.code,
                );
              },
              borderRadius: const BorderRadius.only(
                bottomLeft: Radius.circular(16),
                bottomRight: Radius.circular(16),
              ),
              child: Padding(
                padding: const EdgeInsets.all(16),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    // Header row with status and total
                    Row(
                      children: [
                        // Status badge
                        Container(
                          padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
                          decoration: BoxDecoration(
                            color: statusColor.withValues(alpha: 0.1),
                            borderRadius: BorderRadius.circular(20),
                          ),
                          child: Row(
                            mainAxisSize: MainAxisSize.min,
                            children: [
                              Icon(_getStatusIcon(pinjamData.status), size: 14, color: statusColor),
                              const SizedBox(width: 4),
                              Text(
                                _getStatusLabel(pinjamData.status),
                                style: TextStyle(
                                  fontFamily: 'Poppins',
                                  fontSize: 12,
                                  fontWeight: FontWeight.w600,
                                  color: statusColor,
                                ),
                              ),
                            ],
                          ),
                        ),
                        const Spacer(),
                        // Total badge
                        Container(
                          padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
                          decoration: BoxDecoration(
                            color: _primaryBlue.withValues(alpha: 0.1),
                            borderRadius: BorderRadius.circular(8),
                          ),
                          child: Text(
                            '${pinjamData.total} Barang',
                            style: const TextStyle(
                              fontFamily: 'Poppins',
                              fontSize: 12,
                              fontWeight: FontWeight.w600,
                              color: _primaryBlue,
                            ),
                          ),
                        ),
                      ],
                    ),
                    const SizedBox(height: 14),
                    // Loan code
                    Text(
                      pinjamData.code,
                      style: const TextStyle(
                        fontFamily: 'Poppins',
                        fontSize: 18,
                        fontWeight: FontWeight.w700,
                        color: _textPrimary,
                        height: 1.2,
                      ),
                    ),
                    const SizedBox(height: 12),
                    // Date info row
                    Row(
                      children: [
                        Expanded(
                          child: Row(
                            children: [
                              Container(
                                padding: const EdgeInsets.all(8),
                                decoration: BoxDecoration(
                                  color: const Color(0xFFF3F4F6),
                                  borderRadius: BorderRadius.circular(8),
                                ),
                                child: const Icon(
                                  Icons.calendar_today_rounded,
                                  size: 16,
                                  color: _textSecondary,
                                ),
                              ),
                              const SizedBox(width: 10),
                              Column(
                                crossAxisAlignment: CrossAxisAlignment.start,
                                children: [
                                  const Text(
                                    'Tanggal Pinjam',
                                    style: TextStyle(
                                      fontFamily: 'Poppins',
                                      fontSize: 11,
                                      color: _textSecondary,
                                    ),
                                  ),
                                  Text(
                                    _formatDate(pinjamData.tglPinjam),
                                    style: const TextStyle(
                                      fontFamily: 'Poppins',
                                      fontSize: 13,
                                      fontWeight: FontWeight.w500,
                                      color: _textPrimary,
                                    ),
                                  ),
                                ],
                              ),
                            ],
                          ),
                        ),
                        // Action buttons for 'diajukan' status
                        if (pinjamData.status.toLowerCase() == 'diajukan')
                          Container(
                            decoration: BoxDecoration(
                              color: _errorRed.withValues(alpha: 0.1),
                              borderRadius: BorderRadius.circular(10),
                            ),
                            child: IconButton(
                              onPressed: () => _confirmDelete(pinjamData.code),
                              icon: const Icon(Icons.delete_outline_rounded, color: _errorRed, size: 20),
                              tooltip: 'Hapus',
                              constraints: const BoxConstraints(minWidth: 40, minHeight: 40),
                              padding: EdgeInsets.zero,
                            ),
                          )
                        else
                          Container(
                            padding: const EdgeInsets.all(10),
                            decoration: BoxDecoration(
                              color: _primaryBlue.withValues(alpha: 0.1),
                              borderRadius: BorderRadius.circular(10),
                            ),
                            child: const Icon(
                              Icons.arrow_forward_ios_rounded,
                              size: 14,
                              color: _primaryBlue,
                            ),
                          ),
                      ],
                    ),
                  ],
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildEmptyState() {
    return Center(
      child: Column(
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          Container(
            padding: const EdgeInsets.all(24),
            decoration: BoxDecoration(
              color: const Color(0xFFF3F4F6),
              borderRadius: BorderRadius.circular(24),
            ),
            child: const Icon(
              Icons.inventory_2_outlined,
              size: 56,
              color: _textSecondary,
            ),
          ),
          const SizedBox(height: 20),
          const Text(
            'Belum Ada Pinjaman Aktif',
            style: TextStyle(
              fontFamily: 'Poppins',
              fontSize: 18,
              fontWeight: FontWeight.w600,
              color: _textPrimary,
            ),
          ),
          const SizedBox(height: 8),
          const Text(
            'Pinjaman yang sedang aktif akan\nditampilkan di sini',
            style: TextStyle(
              fontFamily: 'Poppins',
              fontSize: 14,
              color: _textSecondary,
            ),
            textAlign: TextAlign.center,
          ),
        ],
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
              padding: const EdgeInsets.all(24),
              decoration: BoxDecoration(
                color: _errorRed.withValues(alpha: 0.1),
                borderRadius: BorderRadius.circular(24),
              ),
              child: const Icon(
                Icons.error_outline_rounded,
                size: 56,
                color: _errorRed,
              ),
            ),
            const SizedBox(height: 20),
            const Text(
              'Terjadi Kesalahan',
              style: TextStyle(
                fontFamily: 'Poppins',
                fontSize: 18,
                fontWeight: FontWeight.w600,
                color: _textPrimary,
              ),
            ),
            const SizedBox(height: 8),
            Text(
              message,
              style: const TextStyle(
                fontFamily: 'Poppins',
                fontSize: 14,
                color: _textSecondary,
              ),
              textAlign: TextAlign.center,
            ),
            const SizedBox(height: 24),
            ElevatedButton.icon(
              onPressed: () {
                if (_id.isNotEmpty) {
                  Provider.of<PinjamGetProvider>(context, listen: false).getPinjam(_id);
                }
              },
              icon: const Icon(Icons.refresh_rounded, size: 18),
              label: const Text(
                'Coba Lagi',
                style: TextStyle(fontFamily: 'Poppins', fontWeight: FontWeight.w600),
              ),
              style: ElevatedButton.styleFrom(
                backgroundColor: _primaryBlue,
                foregroundColor: Colors.white,
                padding: const EdgeInsets.symmetric(horizontal: 24, vertical: 12),
                shape: RoundedRectangleBorder(
                  borderRadius: BorderRadius.circular(12),
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }

  Future<void> _confirmDelete(String code) async {
    QuickAlert.show(
      context: context,
      type: QuickAlertType.confirm,
      title: 'Hapus Pengajuan?',
      text: 'Apakah Anda yakin ingin menghapus pengajuan pinjaman ini?',
      confirmBtnText: 'Ya, Hapus',
      cancelBtnText: 'Batal',
      confirmBtnColor: _errorRed,
      onConfirmBtnTap: () async {
        Navigator.pop(context); // Close confirm dialog
        await _deleteItem(code);
      },
    );
  }

  Future<void> _deleteItem(String code) async {
    // Show loading
    QuickAlert.show(
      context: context,
      type: QuickAlertType.loading,
      title: 'Menghapus...',
      text: 'Mohon tunggu sebentar',
    );

    // Get provider before async gap
    final deleteProvider = Provider.of<PinjamDeleteProvider>(context, listen: false);
    final getProvider = Provider.of<PinjamGetProvider>(context, listen: false);

    try {
      await deleteProvider.deletePinjam(code);

      if (!mounted) return;
      Navigator.pop(context); // Close loading dialog

      await getProvider.getPinjam(_id);

      if (!mounted) return;

      QuickAlert.show(
        context: context,
        type: QuickAlertType.success,
        title: 'Berhasil!',
        text: 'Pengajuan pinjaman berhasil dihapus.',
        confirmBtnColor: _primaryBlue,
      );
    } catch (e) {
      if (!mounted) return;
      Navigator.pop(context); // Close loading dialog

      QuickAlert.show(
        context: context,
        type: QuickAlertType.error,
        title: 'Gagal',
        text: 'Terjadi kesalahan saat menghapus data.',
        confirmBtnColor: _primaryBlue,
      );
    }
  }
}
