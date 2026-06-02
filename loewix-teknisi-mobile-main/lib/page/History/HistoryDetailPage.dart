import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import '../../service/api/ApiLink.dart';
import '../../service/model/task/TaskAllResponse.dart';
import '../../service/provider/preferences/PreferencesIDProvider.dart';

class HistoryDetailPage extends StatelessWidget {
  static const routeName = '/history_detail';
  final DataTask data;

  const HistoryDetailPage({super.key, required this.data});

  // Modern color scheme
  static const Color _primaryBlue = Color(0xFF2563EB);
  static const Color _successGreen = Color(0xFF10B981);
  static const Color _warningAmber = Color(0xFFF59E0B);
  static const Color _errorRed = Color(0xFFEF4444);
  static const Color _bgColor = Color(0xFFF8FAFC);
  static const Color _cardColor = Colors.white;
  static const Color _textPrimary = Color(0xFF1F2937);
  static const Color _textSecondary = Color(0xFF6B7280);

  @override
  Widget build(BuildContext context) {
    final teknisiId = int.tryParse(
      Provider.of<PreferencesIDProvider>(context, listen: false).isUserRole,
    );

    // Filter pelaksanaan untuk teknisi yang sedang login
    final myPelaksanaan = data.pelaksanaan
        .where((p) => p.teknisiId == teknisiId)
        .toList();

    return Scaffold(
      backgroundColor: _bgColor,
      body: CustomScrollView(
        slivers: [
          // App Bar
          _buildSliverAppBar(context),
          // Content
          SliverToBoxAdapter(
            child: Padding(
              padding: const EdgeInsets.all(16),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  // Status Card
                  _buildStatusCard(),
                  const SizedBox(height: 16),
                  // Customer Info
                  _buildCustomerCard(),
                  const SizedBox(height: 16),
                  // Task Info
                  _buildTaskInfoCard(),
                  const SizedBox(height: 16),
                  // Teknisi List
                  _buildTeknisiCard(),
                  const SizedBox(height: 16),
                  // My Execution Details
                  if (myPelaksanaan.isNotEmpty) ...[
                    _buildSectionTitle('Detail Penyelesaian'),
                    const SizedBox(height: 12),
                    ...myPelaksanaan.map((p) => _buildPelaksanaanCard(p, context)),
                  ],
                  const SizedBox(height: 32),
                ],
              ),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildSliverAppBar(BuildContext context) {
    return SliverAppBar(
      pinned: true,
      backgroundColor: _primaryBlue,
      leading: IconButton(
        icon: Container(
          padding: const EdgeInsets.all(8),
          decoration: BoxDecoration(
            color: Colors.white.withValues(alpha: 0.15),
            borderRadius: BorderRadius.circular(12),
          ),
          child: const Icon(Icons.arrow_back_ios_new, color: Colors.white, size: 18),
        ),
        onPressed: () => Navigator.pop(context),
      ),
      title: const Text(
        'Riwayat Tugas',
        style: TextStyle(
          fontFamily: 'Poppins',
          fontSize: 18,
          fontWeight: FontWeight.w600,
          color: Colors.white,
        ),
      ),
      flexibleSpace: Container(
        decoration: const BoxDecoration(
          gradient: LinearGradient(
            colors: [Color(0xFF2563EB), Color(0xFF3B82F6)],
            begin: Alignment.topLeft,
            end: Alignment.bottomRight,
          ),
        ),
      ),
    );
  }

  Widget _buildStatusCard() {
    final statusColor = _getStatusColor(data.status);
    final statusIcon = _getStatusIcon(data.status);

    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: statusColor.withValues(alpha: 0.1),
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: statusColor.withValues(alpha: 0.3)),
      ),
      child: Row(
        children: [
          Container(
            padding: const EdgeInsets.all(10),
            decoration: BoxDecoration(
              color: statusColor.withValues(alpha: 0.2),
              borderRadius: BorderRadius.circular(12),
            ),
            child: Icon(statusIcon, color: statusColor, size: 24),
          ),
          const SizedBox(width: 14),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                const Text(
                  'Status Tugas',
                  style: TextStyle(
                    fontFamily: 'Poppins',
                    fontSize: 12,
                    color: _textSecondary,
                  ),
                ),
                Text(
                  _formatStatus(data.status),
                  style: TextStyle(
                    fontFamily: 'Poppins',
                    fontSize: 16,
                    fontWeight: FontWeight.w600,
                    color: statusColor,
                  ),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildCustomerCard() {
    return _buildCard(
      title: 'Informasi Customer',
      icon: Icons.business,
      iconColor: _primaryBlue,
      children: [
        _buildInfoRow('Nama', data.dataCustomer.nama),
        if (data.dataCustomer.alamat != null)
          _buildInfoRow('Alamat', data.dataCustomer.alamat.toString()),
        if (data.dataCustomer.telp != null)
          _buildInfoRow('Telepon', data.dataCustomer.telp.toString()),
      ],
    );
  }

  Widget _buildTaskInfoCard() {
    return _buildCard(
      title: 'Informasi Tugas',
      icon: Icons.assignment,
      iconColor: _warningAmber,
      children: [
        _buildInfoRow('Jadwal', _formatDateTime(data.jadwal)),
        _buildInfoRow('Dibuat', _formatDateTime(data.createdAt)),
        _buildInfoRow('Request', data.request),
        if (data.keterangan != null)
          _buildInfoRow('Keterangan', data.keterangan.toString()),
      ],
    );
  }

  Widget _buildTeknisiCard() {
    return _buildCard(
      title: 'Tim Teknisi',
      icon: Icons.group,
      iconColor: _successGreen,
      children: [
        ...data.dataTeknisi.map((t) => Padding(
              padding: const EdgeInsets.only(bottom: 8),
              child: Row(
                children: [
                  Container(
                    padding: const EdgeInsets.all(8),
                    decoration: BoxDecoration(
                      color: _primaryBlue.withValues(alpha: 0.1),
                      borderRadius: BorderRadius.circular(8),
                    ),
                    child: const Icon(Icons.person, size: 16, color: _primaryBlue),
                  ),
                  const SizedBox(width: 12),
                  Text(
                    t.namaDataTeknisi,
                    style: const TextStyle(
                      fontFamily: 'Poppins',
                      fontSize: 14,
                      fontWeight: FontWeight.w500,
                      color: _textPrimary,
                    ),
                  ),
                ],
              ),
            )),
      ],
    );
  }

  Widget _buildPelaksanaanCard(Pelaksanaan p, BuildContext context, {bool isOther = false}) {
    final statusColor = _getStatusColor(p.status);
    final teknisiName = data.dataTeknisi
        .where((t) => t.teknisiId == p.teknisiId)
        .map((t) => t.namaDataTeknisi)
        .firstOrNull ?? 'Teknisi #${p.teknisiId}';

    return Container(
      margin: const EdgeInsets.only(bottom: 16),
      decoration: BoxDecoration(
        color: _cardColor,
        borderRadius: BorderRadius.circular(16),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.05),
            blurRadius: 10,
            offset: const Offset(0, 2),
          ),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          // Header
          Container(
            padding: const EdgeInsets.all(16),
            decoration: BoxDecoration(
              color: statusColor.withValues(alpha: 0.1),
              borderRadius: const BorderRadius.vertical(top: Radius.circular(16)),
            ),
            child: Row(
              children: [
                Icon(_getStatusIcon(p.status), color: statusColor, size: 20),
                const SizedBox(width: 10),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      if (isOther)
                        Text(
                          teknisiName,
                          style: const TextStyle(
                            fontFamily: 'Poppins',
                            fontSize: 14,
                            fontWeight: FontWeight.w600,
                            color: _textPrimary,
                          ),
                        ),
                      Text(
                        _formatStatus(p.status),
                        style: TextStyle(
                          fontFamily: 'Poppins',
                          fontSize: isOther ? 12 : 14,
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
          // Content
          Padding(
            padding: const EdgeInsets.all(16),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                // Time info
                _buildTimeRow(
                  'Mulai',
                  _formatDateTime(p.waktuMulai),
                  Icons.play_circle_outline,
                  _successGreen,
                ),
                if (p.waktuSelesai != null)
                  _buildTimeRow(
                    'Selesai',
                    _formatDateTime(DateTime.parse(p.waktuSelesai.toString())),
                    Icons.check_circle_outline,
                    _primaryBlue,
                  ),
                const Divider(height: 24),
                // Problem, Solution, Notes
                if (p.permasalahan != null && p.permasalahan.toString().isNotEmpty) ...[
                  _buildDetailSection('Permasalahan', p.permasalahan.toString(), Icons.error_outline, _errorRed),
                  const SizedBox(height: 12),
                ],
                if (p.solusi != null && p.solusi.toString().isNotEmpty) ...[
                  _buildDetailSection('Solusi', p.solusi.toString(), Icons.lightbulb_outline, _successGreen),
                  const SizedBox(height: 12),
                ],
                if (p.keterangan != null && p.keterangan.toString().isNotEmpty) ...[
                  _buildDetailSection('Keterangan', p.keterangan.toString(), Icons.notes, _warningAmber),
                  const SizedBox(height: 12),
                ],
                // Images
                if (_hasImages(p)) ...[
                  const SizedBox(height: 8),
                  const Text(
                    'Dokumentasi',
                    style: TextStyle(
                      fontFamily: 'Poppins',
                      fontSize: 14,
                      fontWeight: FontWeight.w600,
                      color: _textPrimary,
                    ),
                  ),
                  const SizedBox(height: 12),
                  _buildImageGallery(p, context),
                ],
              ],
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildTimeRow(String label, String value, IconData icon, Color color) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 8),
      child: Row(
        children: [
          Icon(icon, size: 18, color: color),
          const SizedBox(width: 10),
          Text(
            '$label: ',
            style: const TextStyle(
              fontFamily: 'Poppins',
              fontSize: 13,
              color: _textSecondary,
            ),
          ),
          Text(
            value,
            style: const TextStyle(
              fontFamily: 'Poppins',
              fontSize: 13,
              fontWeight: FontWeight.w500,
              color: _textPrimary,
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildDetailSection(String title, String content, IconData icon, Color color) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Row(
          children: [
            Icon(icon, size: 16, color: color),
            const SizedBox(width: 8),
            Text(
              title,
              style: TextStyle(
                fontFamily: 'Poppins',
                fontSize: 13,
                fontWeight: FontWeight.w600,
                color: color,
              ),
            ),
          ],
        ),
        const SizedBox(height: 6),
        Container(
          width: double.infinity,
          padding: const EdgeInsets.all(12),
          decoration: BoxDecoration(
            color: color.withValues(alpha: 0.05),
            borderRadius: BorderRadius.circular(10),
            border: Border.all(color: color.withValues(alpha: 0.2)),
          ),
          child: Text(
            content,
            style: const TextStyle(
              fontFamily: 'Poppins',
              fontSize: 13,
              color: _textPrimary,
              height: 1.5,
            ),
          ),
        ),
      ],
    );
  }

  bool _hasImages(Pelaksanaan p) {
    return p.image1 != null ||
        p.image2 != null ||
        p.image3 != null ||
        p.image4 != null ||
        p.image5 != null;
  }

  Widget _buildImageGallery(Pelaksanaan p, BuildContext context) {
    final images = [p.image1, p.image2, p.image3, p.image4, p.image5]
        .where((img) => img != null)
        .toList();

    return SizedBox(
      height: 100,
      child: ListView.builder(
        scrollDirection: Axis.horizontal,
        itemCount: images.length,
        itemBuilder: (context, index) {
          final imageUrl = '${Api.Url}/storage/reports/${images[index]}';
          return GestureDetector(
            onTap: () => _showImageDialog(context, imageUrl),
            child: Container(
              width: 100,
              height: 100,
              margin: const EdgeInsets.only(right: 10),
              decoration: BoxDecoration(
                borderRadius: BorderRadius.circular(12),
                border: Border.all(color: Colors.grey.withValues(alpha: 0.2)),
              ),
              child: ClipRRect(
                borderRadius: BorderRadius.circular(12),
                child: Image.network(
                  imageUrl,
                  fit: BoxFit.cover,
                  loadingBuilder: (context, child, loadingProgress) {
                    if (loadingProgress == null) return child;
                    return Container(
                      color: Colors.grey[200],
                      child: const Center(
                        child: SizedBox(
                          width: 20,
                          height: 20,
                          child: CircularProgressIndicator(strokeWidth: 2),
                        ),
                      ),
                    );
                  },
                  errorBuilder: (context, error, stackTrace) => Container(
                    color: Colors.grey[200],
                    child: const Icon(Icons.broken_image, color: Colors.grey),
                  ),
                ),
              ),
            ),
          );
        },
      ),
    );
  }

  void _showImageDialog(BuildContext context, String imageUrl) {
    showDialog(
      context: context,
      builder: (context) => Dialog(
        backgroundColor: Colors.transparent,
        child: Stack(
          children: [
            InteractiveViewer(
              child: ClipRRect(
                borderRadius: BorderRadius.circular(12),
                child: Image.network(
                  imageUrl,
                  fit: BoxFit.contain,
                  loadingBuilder: (context, child, loadingProgress) {
                    if (loadingProgress == null) return child;
                    return const Center(child: CircularProgressIndicator());
                  },
                  errorBuilder: (context, error, stackTrace) => Container(
                    color: Colors.grey[800],
                    child: const Icon(Icons.broken_image, color: Colors.white, size: 48),
                  ),
                ),
              ),
            ),
            Positioned(
              top: 0,
              right: 0,
              child: IconButton(
                icon: Container(
                  padding: const EdgeInsets.all(4),
                  decoration: const BoxDecoration(
                    color: Colors.black54,
                    shape: BoxShape.circle,
                  ),
                  child: const Icon(Icons.close, color: Colors.white, size: 20),
                ),
                onPressed: () => Navigator.pop(context),
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildCard({
    required String title,
    required IconData icon,
    required Color iconColor,
    required List<Widget> children,
  }) {
    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: _cardColor,
        borderRadius: BorderRadius.circular(16),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.05),
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
              Container(
                padding: const EdgeInsets.all(8),
                decoration: BoxDecoration(
                  color: iconColor.withValues(alpha: 0.1),
                  borderRadius: BorderRadius.circular(10),
                ),
                child: Icon(icon, size: 20, color: iconColor),
              ),
              const SizedBox(width: 12),
              Text(
                title,
                style: const TextStyle(
                  fontFamily: 'Poppins',
                  fontSize: 16,
                  fontWeight: FontWeight.w600,
                  color: _textPrimary,
                ),
              ),
            ],
          ),
          const SizedBox(height: 16),
          ...children,
        ],
      ),
    );
  }

  Widget _buildInfoRow(String label, String value) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 10),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          SizedBox(
            width: 100,
            child: Text(
              label,
              style: const TextStyle(
                fontFamily: 'Poppins',
                fontSize: 13,
                color: _textSecondary,
              ),
            ),
          ),
          Expanded(
            child: Text(
              value,
              style: const TextStyle(
                fontFamily: 'Poppins',
                fontSize: 13,
                fontWeight: FontWeight.w500,
                color: _textPrimary,
              ),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildSectionTitle(String title) {
    return Text(
      title,
      style: const TextStyle(
        fontFamily: 'Poppins',
        fontSize: 18,
        fontWeight: FontWeight.w600,
        color: _textPrimary,
      ),
    );
  }

  Color _getStatusColor(String status) {
    switch (status.toLowerCase()) {
      case 'selesai':
      case 'selesai by admin':
        return _successGreen;
      case 'berjalan':
        return _primaryBlue;
      case 'lanjut nanti':
        return _warningAmber;
      case 'dibatalkan':
        return _errorRed;
      case 'menunggu laporan':
        return _warningAmber;
      default:
        return _textSecondary;
    }
  }

  IconData _getStatusIcon(String status) {
    switch (status.toLowerCase()) {
      case 'selesai':
      case 'selesai by admin':
        return Icons.check_circle;
      case 'berjalan':
        return Icons.play_circle;
      case 'lanjut nanti':
        return Icons.pause_circle;
      case 'dibatalkan':
        return Icons.cancel;
      case 'menunggu laporan':
        return Icons.pending;
      default:
        return Icons.info;
    }
  }

  String _formatStatus(String status) {
    switch (status.toLowerCase()) {
      case 'selesai':
        return 'Selesai';
      case 'selesai by admin':
        return 'Selesai (Admin)';
      case 'berjalan':
        return 'Sedang Berjalan';
      case 'lanjut nanti':
        return 'Lanjut Nanti';
      case 'dibatalkan':
        return 'Dibatalkan';
      case 'menunggu laporan':
        return 'Menunggu Laporan';
      default:
        return status;
    }
  }

  String _formatDateTime(DateTime dateTime) {
    final days = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
    final months = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Ags', 'Sep', 'Okt', 'Nov', 'Des'];

    final dayName = days[dateTime.weekday % 7];
    final monthName = months[dateTime.month - 1];
    final time = '${dateTime.hour.toString().padLeft(2, '0')}:${dateTime.minute.toString().padLeft(2, '0')}';

    return '$dayName, ${dateTime.day} $monthName ${dateTime.year} - $time';
  }
}
