import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:teknisi_loewix/service/model/reimburse/ReimburseModel.dart';
import 'package:teknisi_loewix/service/provider/reimburse/ReimburseProvider.dart';
import 'package:teknisi_loewix/service/provider/reimburse/ReimburseDeleteProvider.dart';
import 'package:intl/intl.dart';
import 'package:quickalert/quickalert.dart';

class ReimburseListPage extends StatefulWidget {
  final int teknisiId;
  const ReimburseListPage({super.key, required this.teknisiId});

  @override
  State<ReimburseListPage> createState() => _ReimburseListPageState();
}

class _ReimburseListPageState extends State<ReimburseListPage> {
  // Modern color scheme
  static const Color _primaryBlue = Color(0xFF2563EB);
  static const Color _successGreen = Color(0xFF10B981);
  static const Color _errorRed = Color(0xFFEF4444);
  static const Color _textPrimary = Color(0xFF1F2937);
  static const Color _textSecondary = Color(0xFF6B7280);

  final ScrollController _scrollController = ScrollController();

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      if (!mounted) return;
      Provider.of<ReimburseProvider>(context, listen: false)
          .fetchReimburse(widget.teknisiId);
    });
  }

  @override
  void dispose() {
    _scrollController.dispose();
    super.dispose();
  }

  void _refreshData() {
    Provider.of<ReimburseProvider>(context, listen: false)
        .fetchReimburse(widget.teknisiId);
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xFFF9FAFB),
      appBar: AppBar(
        backgroundColor: Colors.white,
        surfaceTintColor: Colors.transparent,
        elevation: 0,
        leading: IconButton(
          icon: Container(
            padding: const EdgeInsets.all(8),
            decoration: BoxDecoration(
              color: const Color(0xFFF3F4F6),
              borderRadius: BorderRadius.circular(12),
            ),
            child: const Icon(Icons.arrow_back_ios_new, color: _textPrimary, size: 18),
          ),
          onPressed: () => Navigator.pop(context),
        ),
        title: const Text(
          'Daftar Reimburse',
          style: TextStyle(
            fontFamily: 'Poppins',
            fontWeight: FontWeight.w600,
            fontSize: 20,
            color: _textPrimary,
          ),
        ),
        actions: [
          Container(
            margin: const EdgeInsets.only(right: 16),
            decoration: BoxDecoration(
              color: const Color(0xFFF3F4F6),
              borderRadius: BorderRadius.circular(12),
            ),
            child: IconButton(
              onPressed: _refreshData,
              icon: const Icon(Icons.refresh_rounded, color: _textPrimary, size: 22),
              tooltip: 'Refresh',
            ),
          ),
        ],
      ),
      body: Consumer<ReimburseProvider>(
        builder: (context, reimburseProvider, child) {
          if (reimburseProvider.isLoading) {
            return const Center(
              child: CircularProgressIndicator(color: _primaryBlue),
            );
          }

          if (reimburseProvider.error != null) {
            return _buildErrorState(reimburseProvider.error!);
          }

          if (reimburseProvider.reimburseList.isEmpty) {
            return _buildEmptyState();
          }

          return RefreshIndicator(
            color: _primaryBlue,
            onRefresh: () async {
              await reimburseProvider.fetchReimburse(widget.teknisiId);
            },
            child: ListView.builder(
              controller: _scrollController,
              padding: const EdgeInsets.all(16),
              itemCount: reimburseProvider.reimburseList.length,
              itemBuilder: (context, index) {
                final reimburse = reimburseProvider.reimburseList[index];
                return _buildReimburseCard(reimburse);
              },
            ),
          );
        },
      ),
    );
  }

  Widget _buildReimburseCard(DataReimburse reimburse) {
    final dateFormat = DateFormat('dd MMM yyyy');
    final currencyFormat = NumberFormat.currency(locale: 'id_ID', symbol: 'Rp ', decimalDigits: 0);

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
              color: _successGreen,
            ),
            Padding(
              padding: const EdgeInsets.all(16),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  // Header row with activity and nominal
                  Row(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      // Activity icon
                      Container(
                        padding: const EdgeInsets.all(10),
                        decoration: BoxDecoration(
                          color: _primaryBlue.withValues(alpha: 0.1),
                          borderRadius: BorderRadius.circular(12),
                        ),
                        child: const Icon(
                          Icons.receipt_long_rounded,
                          size: 22,
                          color: _primaryBlue,
                        ),
                      ),
                      const SizedBox(width: 12),
                      // Activity name
                      Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(
                              reimburse.kegiatan.kegiatan,
                              style: const TextStyle(
                                fontFamily: 'Poppins',
                                fontSize: 15,
                                fontWeight: FontWeight.w600,
                                color: _textPrimary,
                              ),
                              maxLines: 2,
                              overflow: TextOverflow.ellipsis,
                            ),
                            const SizedBox(height: 4),
                            Text(
                              reimburse.kegiatan.customer.nama,
                              style: const TextStyle(
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
                  const SizedBox(height: 14),
                  // Description
                  if (reimburse.keterangan.isNotEmpty)
                    Container(
                      width: double.infinity,
                      padding: const EdgeInsets.all(12),
                      decoration: BoxDecoration(
                        color: const Color(0xFFF9FAFB),
                        borderRadius: BorderRadius.circular(10),
                      ),
                      child: Text(
                        reimburse.keterangan,
                        style: const TextStyle(
                          fontFamily: 'Poppins',
                          fontSize: 13,
                          color: _textSecondary,
                        ),
                        maxLines: 2,
                        overflow: TextOverflow.ellipsis,
                      ),
                    ),
                  const SizedBox(height: 14),
                  // Nominal badge
                  Container(
                    padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
                    decoration: BoxDecoration(
                      color: _successGreen.withValues(alpha: 0.1),
                      borderRadius: BorderRadius.circular(10),
                    ),
                    child: Row(
                      mainAxisSize: MainAxisSize.min,
                      children: [
                        const Icon(Icons.payments_rounded, size: 18, color: _successGreen),
                        const SizedBox(width: 8),
                        Text(
                          currencyFormat.format(reimburse.nominal),
                          style: const TextStyle(
                            fontFamily: 'Poppins',
                            fontSize: 16,
                            fontWeight: FontWeight.w700,
                            color: _successGreen,
                          ),
                        ),
                      ],
                    ),
                  ),
                  const SizedBox(height: 14),
                  // Footer row with date and delete button
                  Row(
                    children: [
                      Container(
                        padding: const EdgeInsets.all(8),
                        decoration: BoxDecoration(
                          color: const Color(0xFFF3F4F6),
                          borderRadius: BorderRadius.circular(8),
                        ),
                        child: const Icon(
                          Icons.calendar_today_rounded,
                          size: 14,
                          color: _textSecondary,
                        ),
                      ),
                      const SizedBox(width: 8),
                      Text(
                        dateFormat.format(reimburse.tanggal),
                        style: const TextStyle(
                          fontFamily: 'Poppins',
                          fontSize: 13,
                          color: _textSecondary,
                        ),
                      ),
                      const Spacer(),
                      // Delete button
                      Container(
                        decoration: BoxDecoration(
                          color: _errorRed.withValues(alpha: 0.1),
                          borderRadius: BorderRadius.circular(10),
                        ),
                        child: IconButton(
                          onPressed: () => _confirmDelete(reimburse),
                          icon: const Icon(Icons.delete_outline_rounded, color: _errorRed, size: 20),
                          tooltip: 'Hapus',
                          constraints: const BoxConstraints(minWidth: 40, minHeight: 40),
                          padding: EdgeInsets.zero,
                        ),
                      ),
                    ],
                  ),
                ],
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
              Icons.receipt_long_outlined,
              size: 56,
              color: _textSecondary,
            ),
          ),
          const SizedBox(height: 20),
          const Text(
            'Belum Ada Reimburse',
            style: TextStyle(
              fontFamily: 'Poppins',
              fontSize: 18,
              fontWeight: FontWeight.w600,
              color: _textPrimary,
            ),
          ),
          const SizedBox(height: 8),
          const Text(
            'Data reimburse akan\nditampilkan di sini',
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
              onPressed: _refreshData,
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

  void _confirmDelete(DataReimburse reimburse) {
    QuickAlert.show(
      context: context,
      type: QuickAlertType.confirm,
      title: 'Hapus Reimburse?',
      text: 'Apakah Anda yakin ingin menghapus reimburse untuk ${reimburse.kegiatan.kegiatan}?',
      confirmBtnText: 'Ya, Hapus',
      cancelBtnText: 'Batal',
      confirmBtnColor: _errorRed,
      onConfirmBtnTap: () async {
        Navigator.pop(context); // Close confirm dialog
        await _deleteItem(reimburse);
      },
    );
  }

  Future<void> _deleteItem(DataReimburse reimburse) async {
    // Show loading
    QuickAlert.show(
      context: context,
      type: QuickAlertType.loading,
      title: 'Menghapus...',
      text: 'Mohon tunggu sebentar',
    );

    // Get providers before async gap
    final deleteProvider = Provider.of<ReimburseDeleteProvider>(context, listen: false);
    final reimburseProvider = Provider.of<ReimburseProvider>(context, listen: false);

    try {
      await deleteProvider.deleteReimburse(reimburse.id);

      if (!mounted) return;
      Navigator.pop(context); // Close loading dialog

      if (deleteProvider.isDeleted) {
        // Refresh data
        await reimburseProvider.fetchReimburse(widget.teknisiId);

        if (!mounted) return;

        QuickAlert.show(
          context: context,
          type: QuickAlertType.success,
          title: 'Berhasil!',
          text: 'Reimburse berhasil dihapus.',
          confirmBtnColor: _primaryBlue,
        );
      } else if (deleteProvider.error != null) {
        if (!mounted) return;
        QuickAlert.show(
          context: context,
          type: QuickAlertType.error,
          title: 'Gagal',
          text: deleteProvider.error ?? 'Terjadi kesalahan saat menghapus.',
          confirmBtnColor: _primaryBlue,
        );
      }
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
