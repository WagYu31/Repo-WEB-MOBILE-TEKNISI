import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:slide_to_act/slide_to_act.dart';
import 'package:quickalert/quickalert.dart';
import 'package:teknisi_loewix/page/reimburse/reimburse_add_screen.dart';
import 'package:teknisi_loewix/service/model/pelaksanaan/PelaksanaanSend.dart';
import 'package:teknisi_loewix/service/provider/Pelaksanaan/CoProvider.dart';
import 'package:url_launcher/url_launcher.dart';
import 'dart:math';

import '../../page/Maps/MapsPage.dart';
import '../../service/model/task/TaskAllResponse.dart';
import '../../service/provider/Maps/MapsProvider.dart';
import '../../service/provider/Pelaksanaan/PelaksanaanSendProvider.dart';
import '../../service/provider/Task/TaskGetAllProvider.dart';
import '../../service/provider/Task/DetailTaskGetProvider.dart';
import '../../service/provider/Pelaksanaan/RescheduleProvider.dart';
import '../../service/provider/preferences/PreferencesIDProvider.dart';
import '../../utils/state.dart';
import 'ReportDonePage.dart';
import '../Invoice/DetailInvoicePage.dart';
import 'LanjutNantiPage.dart';

class TaskPage extends StatefulWidget {
  static const routeName = '/task_page';
  final List<dynamic> dataa;

  const TaskPage({super.key, required this.dataa});

  @override
  State<TaskPage> createState() => _TaskPageState();
}

class _TaskPageState extends State<TaskPage> {
  late DataTask data;
  late bool history;
  late String id;
  late int _idTeknisi;
  bool start = false;

  // Cached computed values
  String? _cachedStatus;
  Color? _cachedStatusColor;
  IconData? _cachedStatusIcon;

  // Modern color scheme - consistent with app theme
  static const Color primaryColor = Color(0xFF2563EB);
  static const Color secondaryColor = Color(0xFF0EA5E9);
  static const Color errorColor = Color(0xFFEF4444);
  static const Color warningColor = Color(0xFFF59E0B);
  static const Color successColor = Color(0xFF10B981);
  static const Color surfaceColor = Color(0xFFF8FAFC);
  static const Color cardColor = Colors.white;
  static const Color textPrimary = Color(0xFF1F2937);
  static const Color textSecondary = Color(0xFF6B7280);

  @override
  void initState() {
    super.initState();
    data = widget.dataa[0];
    history = widget.dataa[1];
    id = context.read<PreferencesIDProvider>().isUserRole;
    _idTeknisi = int.parse(id);
    start = data.pelaksanaan.any(
      (e) => e.teknisiId == _idTeknisi && e.status == 'berjalan',
    );

    // Pre-compute status values
    _computeStatusValues();
  }

  void _computeStatusValues() {
    final filtered = data.pelaksanaan.where(
      (a) => a.kegiatanId == data.id && a.teknisiId == _idTeknisi,
    );
    final status = filtered.isNotEmpty ? filtered.first.status : 'tidak';
    final now = DateTime.now();
    final selisih = now.difference(data.jadwal).inDays;

    // Compute status text
    if (status == 'selesai') {
      _cachedStatus = 'Selesai';
      _cachedStatusColor = successColor;
      _cachedStatusIcon = Icons.check_circle;
    } else if (status == 'menunggu laporan') {
      _cachedStatus = 'Menunggu Laporan';
      _cachedStatusColor = secondaryColor;
      _cachedStatusIcon = Icons.pending;
    } else if (status == 'Lanjut Nanti') {
      _cachedStatus = 'Lanjut Nanti';
      _cachedStatusColor = warningColor;
      _cachedStatusIcon = Icons.pause_circle;
    } else if (status == 'dibatalkan') {
      _cachedStatus = 'Dibatalkan';
      _cachedStatusColor = errorColor;
      _cachedStatusIcon = Icons.cancel;
    } else if (start && selisih == 0) {
      _cachedStatus = 'Sedang Berjalan';
      _cachedStatusColor = successColor;
      _cachedStatusIcon = Icons.play_circle_filled;
    } else if (start && selisih > 0) {
      _cachedStatus = 'Tidak absen';
      _cachedStatusColor = errorColor;
      _cachedStatusIcon = Icons.play_circle_filled;
    } else if (_isSchedulePassed()) {
      _cachedStatus = 'Terlambat';
      _cachedStatusColor = errorColor;
      _cachedStatusIcon = Icons.warning;
    } else {
      _cachedStatus = 'Belum Dimulai';
      _cachedStatusColor = warningColor;
      _cachedStatusIcon = Icons.schedule;
    }
  }

  @override
  void dispose() {
    super.dispose();
  }

  bool _isWithinRadius() {
    if (data.lat == null ||
        data.lon == null ||
        data.rad == null ||
        GoogleMapSample.currentPosition == null) {
      return false;
    }

    final taskLat = double.tryParse(data.lat.toString());
    final taskLon = double.tryParse(data.lon.toString());
    final radius = double.tryParse(data.rad.toString());
    final userPosition = GoogleMapSample.currentPosition!;

    if (taskLat == null || taskLon == null || radius == null) {
      return false;
    }

    return _calculateDistance(
          taskLat,
          taskLon,
          userPosition.latitude,
          userPosition.longitude,
        ) <=
        radius;
  }

  bool _isSchedulePassed() {
    try {
      final scheduleDate = DateTime.parse(data.jadwal.toString());
      final now = DateTime.now();

      // Bandingkan hanya bagian tanggal (tanpa jam)
      return now.year > scheduleDate.year ||
          now.month > scheduleDate.month ||
          now.day > scheduleDate.day;
    } catch (e) {
      return false;
    }
  }

  String get _pelaksanaanStatus {
    final filtered = data.pelaksanaan.where(
      (a) => a.kegiatanId == data.id && a.teknisiId == _idTeknisi,
    );
    return filtered.isNotEmpty ? filtered.first.status : 'tidak';
  }

  bool _shouldShowSlider() {
    if (history) return false;
    final status = _pelaksanaanStatus;
    // Hanya sembunyikan slider jika sudah selesai, menunggu laporan, lanjut nanti, atau dibatalkan
    if (status == 'selesai' ||
        status == 'menunggu laporan' ||
        status == 'Lanjut Nanti' ||
        status == 'dibatalkan') {
      return false;
    }
    // Jika jadwal sudah lewat dan belum mulai, sembunyikan slider
    if (_isSchedulePassed() && !start) return false;
    // Tampilkan slider untuk status 'tidak' (belum ada pelaksanaan), 'Dijadwalkan', atau 'berjalan'
    return true;
  }

  String _getTaskStatus() => _cachedStatus ?? 'Belum Dimulai';

  Color _getStatusColor() => _cachedStatusColor ?? warningColor;

  IconData _getStatusIcon() => _cachedStatusIcon ?? Icons.schedule;

  double _calculateDistance(
    double lat1,
    double lon1,
    double lat2,
    double lon2,
  ) {
    const earthRadius = 6371000; // meters
    final dLat = _toRadians(lat2 - lat1);
    final dLon = _toRadians(lon2 - lon1);
    final a =
        sin(dLat / 2) * sin(dLat / 2) +
        cos(_toRadians(lat1)) *
            cos(_toRadians(lat2)) *
            sin(dLon / 2) *
            sin(dLon / 2);
    final c = 2 * atan2(sqrt(a), sqrt(1 - a));
    return earthRadius * c;
  }

  double _toRadians(double degree) {
    return degree * pi / 180;
  }

  Future<void> _openGoogleMapsNavigation() async {
    if (data.lat == null || data.lon == null) {
      if (!mounted) return;
      QuickAlert.show(
        context: context,
        type: QuickAlertType.warning,
        title: 'Lokasi Tidak Tersedia',
        text: 'Koordinat lokasi tujuan tidak ditemukan',
      );
      return;
    }

    final lat = data.lat.toString();
    final lon = data.lon.toString();
    final url = Uri.parse('https://www.google.com/maps/dir/?api=1&destination=$lat,$lon&travelmode=driving');

    try {
      if (await canLaunchUrl(url)) {
        await launchUrl(url, mode: LaunchMode.externalApplication);
      } else {
        if (!mounted) return;
        QuickAlert.show(
          context: context,
          type: QuickAlertType.error,
          title: 'Gagal',
          text: 'Tidak dapat membuka Google Maps',
        );
      }
    } catch (e) {
      if (!mounted) return;
      QuickAlert.show(
        context: context,
        type: QuickAlertType.error,
        title: 'Error',
        text: 'Terjadi kesalahan saat membuka navigasi',
      );
    }
  }

  void _showBottomSheetActions() {
    final bottomPadding = MediaQuery.of(context).padding.bottom;
    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (_) => Container(
        decoration: const BoxDecoration(
          color: cardColor,
          borderRadius: BorderRadius.vertical(top: Radius.circular(28)),
        ),
        child: DraggableScrollableSheet(
          initialChildSize: _shouldShowSlider() ? 0.75 : 0.65,
          minChildSize: 0.5,
          maxChildSize: 0.95,
          expand: false,
          builder: (context, scrollController) {
            return SingleChildScrollView(
              controller: scrollController,
              child: Padding(
                padding: EdgeInsets.fromLTRB(24, 16, 24, 32 + bottomPadding),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    _buildBottomSheetHeader(),
                    const SizedBox(height: 24),
                    _buildTaskStatusCard(),
                    const SizedBox(height: 20),
                    _buildTaskDetails(),
                    if (_shouldShowSlider()) ...[
                      const SizedBox(height: 28),
                      _buildSlideAction(),
                    ] else ...[
                      const SizedBox(height: 20),
                      _buildStatusMessage(),
                    ],
                    const SizedBox(height: 24),
                    _buildActionButtons(),
                  ],
                ),
              ),
            );
          },
        ),
      ),
    );
  }

  Widget _buildStatusMessage() {
    String message;
    Color messageColor;
    IconData messageIcon;

    final idTeknisi = int.parse(
      Provider.of<PreferencesIDProvider>(context, listen: false).isUserRole,
    );

    final filtered = data.pelaksanaan.where(
      (a) => a.kegiatanId == data.id && a.teknisiId == idTeknisi,
    );

    String status = filtered.isNotEmpty ? filtered.first.status : 'tidak';

    if (status == 'selesai') {
      message = 'Tugas telah selesai dikerjakan';
      messageColor = successColor;
      messageIcon = Icons.check_circle_outline;
    } else if (status == 'menunggu laporan') {
      message = 'Menunggu laporan dari teknisi';
      messageColor = secondaryColor;
      messageIcon = Icons.pending_outlined;
    } else if (status == 'Lanjut Nanti') {
      message = 'Tugas dijadwalkan untuk dilanjutkan nanti';
      messageColor = warningColor;
      messageIcon = Icons.pause_circle_outline;
    } else if (status == 'dibatalkan') {
      message = 'Tugas telah dibatalkan';
      messageColor = errorColor;
      messageIcon = Icons.cancel_outlined;
    } else if (_isSchedulePassed() && !start) {
      message = 'Jadwal tugas telah terlewat';
      messageColor = errorColor;
      messageIcon = Icons.warning_outlined;
    } else if (status == 'tidak' || status == 'Dijadwalkan') {
      message = 'Tugas belum dimulai';
      messageColor = warningColor;
      messageIcon = Icons.schedule_outlined;
    } else if (status == 'berjalan') {
      message = 'Tugas sedang berjalan';
      messageColor = successColor;
      messageIcon = Icons.play_circle_outline;
    } else {
      message = 'Status: $status';
      messageColor = textSecondary;
      messageIcon = Icons.info_outline;
    }

    return Container(
      padding: const EdgeInsets.all(20),
      decoration: BoxDecoration(
        color: messageColor.withValues(alpha: 0.1),
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: messageColor.withValues(alpha: 0.3)),
      ),
      child: Row(
        children: [
          Icon(messageIcon, color: messageColor, size: 28),
          const SizedBox(width: 16),
          Expanded(
            child: Text(
              message,
              style: TextStyle(
                fontFamily: 'Poppins',
                color: messageColor,
                fontWeight: FontWeight.w500,
                fontSize: 15,
              ),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildBottomSheetHeader() {
    return Column(
      children: [
        Center(
          child: Container(
            width: 48,
            height: 4,
            decoration: BoxDecoration(
              color: Colors.grey[300],
              borderRadius: BorderRadius.circular(2),
            ),
          ),
        ),
        const SizedBox(height: 20),
        Row(
          children: [
            Container(
              padding: const EdgeInsets.all(12),
              decoration: BoxDecoration(
                gradient: LinearGradient(
                  colors: [
                    primaryColor.withValues(alpha:0.2),
                    primaryColor.withValues(alpha:0.1),
                  ],
                  begin: Alignment.topLeft,
                  end: Alignment.bottomRight,
                ),
                borderRadius: BorderRadius.circular(12),
              ),
              child: const Icon(
                Icons.assignment,
                color: primaryColor,
                size: 24,
              ),
            ),
            const SizedBox(width: 16),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    'Detail Tugas',
                    style: TextStyle(fontFamily: 'Poppins',
                      fontSize: 22,
                      fontWeight: FontWeight.bold,
                      color: textPrimary,
                    ),
                  ),
                  Container(
                    padding: const EdgeInsets.symmetric(
                      horizontal: 8,
                      vertical: 2,
                    ),
                    decoration: BoxDecoration(
                      color: primaryColor.withValues(alpha:0.1),
                      borderRadius: BorderRadius.circular(6),
                    ),
                    child: Text(
                      'ID: ${data.kode}',
                      style: TextStyle(fontFamily: 'Poppins',
                        fontSize: 12,
                        color: primaryColor,
                        fontWeight: FontWeight.w500,
                      ),
                    ),
                  ),
                ],
              ),
            ),
          ],
        ),
      ],
    );
  }

  Widget _buildTaskStatusCard() {
    final statusColor = _getStatusColor();
    final statusText = _getTaskStatus();
    final statusIcon = _getStatusIcon();

    return Container(
      padding: const EdgeInsets.all(20),
      decoration: BoxDecoration(
        gradient: LinearGradient(
          colors: [
            statusColor.withValues(alpha:0.15),
            statusColor.withValues(alpha:0.05),
          ],
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
        ),
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: statusColor.withValues(alpha:0.3)),
        boxShadow: [
          BoxShadow(
            color: statusColor.withValues(alpha:0.1),
            blurRadius: 8,
            offset: const Offset(0, 2),
          ),
        ],
      ),
      child: Row(
        children: [
          Container(
            padding: const EdgeInsets.all(8),
            decoration: BoxDecoration(
              color: statusColor.withValues(alpha:0.2),
              borderRadius: BorderRadius.circular(8),
            ),
            child: Icon(statusIcon, color: statusColor, size: 24),
          ),
          const SizedBox(width: 16),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  statusText,
                  style: TextStyle(fontFamily: 'Poppins',
                    fontSize: 16,
                    fontWeight: FontWeight.w600,
                    color: statusColor,
                  ),
                ),
                if (_isSchedulePassed() && !start)
                  Text(
                    'Jadwal: ${data.jadwal}',
                    style: TextStyle(fontFamily: 'Poppins',
                      fontSize: 12,
                      color: statusColor.withValues(alpha:0.8),
                    ),
                  ),
              ],
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildTaskDetails() {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(
          'Informasi Tugas',
          style: TextStyle(fontFamily: 'Poppins',
            fontSize: 18,
            fontWeight: FontWeight.w600,
            color: textPrimary,
          ),
        ),
        const SizedBox(height: 16),
        _buildDetailCard(
          Icons.person_outline,
          'Customer',
          data.dataCustomer.nama,
          primaryColor,
        ),
        _buildDetailCard(
          Icons.location_on_outlined,
          'Alamat',
          data.dataCustomer.alamat,
          Colors.red[400]!,
        ),
        _buildDetailCard(
          Icons.calendar_today_outlined,
          'Jadwal',
          data.jadwal.toString(),
          _isSchedulePassed() ? errorColor : Colors.orange[400]!,
        ),
        _buildDetailCard(
          Icons.note_outlined,
          'Catatan',
          data.keterangan ?? 'Tidak ada catatan',
          Colors.purple[400]!,
        ),
      ],
    );
  }

  Widget _buildDetailCard(
    IconData icon,
    String label,
    String value,
    Color iconColor,
  ) {
    return Container(
      margin: const EdgeInsets.only(bottom: 12),
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: surfaceColor,
        borderRadius: BorderRadius.circular(16),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha:0.04),
            blurRadius: 8,
            offset: const Offset(0, 2),
          ),
        ],
      ),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Container(
            padding: const EdgeInsets.all(8),
            decoration: BoxDecoration(
              color: iconColor.withValues(alpha:0.1),
              borderRadius: BorderRadius.circular(8),
            ),
            child: Icon(icon, color: iconColor, size: 20),
          ),
          const SizedBox(width: 16),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  label,
                  style: TextStyle(fontFamily: 'Poppins',
                    fontWeight: FontWeight.w600,
                    fontSize: 14,
                    color: textSecondary,
                  ),
                ),
                const SizedBox(height: 4),
                Text(
                  value,
                  style: TextStyle(fontFamily: 'Poppins',
                    fontSize: 15,
                    color: textPrimary,
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

  Widget _buildSlideAction() {
    return Consumer<MapsProvider>(
      builder: (_, state, __) {
        if (state.state == ResultState.loading) {
          return Container(
            padding: const EdgeInsets.all(20),
            child: const Center(child: CircularProgressIndicator()),
          );
        }

        final isWithinRadius = _isWithinRadius();

        return Column(
          children: [
            if (!isWithinRadius) _buildRadiusWarning(),
            Container(
              decoration: BoxDecoration(
                borderRadius: BorderRadius.circular(16),
                boxShadow: [
                  BoxShadow(
                    color: (start ? successColor : primaryColor).withValues(alpha:
                      0.3,
                    ),
                    blurRadius: 12,
                    offset: const Offset(0, 4),
                  ),
                ],
              ),
              child: SlideAction(
                outerColor: start ? successColor : primaryColor,
                elevation: 0,
                innerColor: Colors.white,
                borderRadius: 16,
                text: start ? 'Geser untuk Selesai' : 'Geser untuk Mulai',
                textStyle: TextStyle(fontFamily: 'Poppins',
                  color: Colors.white,
                  fontSize: 16,
                  fontWeight: FontWeight.w600,
                ),
                sliderButtonIcon: Icon(
                  start ? Icons.check : Icons.play_arrow,
                  color: start ? successColor : primaryColor,
                  size: 24,
                ),
                onSubmit: () => _handleSlideAction(isWithinRadius),
              ),
            ),
          ],
        );
      },
    );
  }

  Widget _buildRadiusWarning() {
    return Container(
      margin: const EdgeInsets.only(bottom: 16),
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: errorColor.withValues(alpha:0.1),
        borderRadius: BorderRadius.circular(12),
        border: Border.all(color: errorColor.withValues(alpha:0.3)),
      ),
      child: Row(
        children: [
          Icon(Icons.warning_amber_rounded, color: errorColor, size: 24),
          const SizedBox(width: 12),
          Expanded(
            child: Text(
              'Anda berada di luar radius yang ditentukan',
              style: TextStyle(fontFamily: 'Poppins',
                color: errorColor,
                fontWeight: FontWeight.w500,
                fontSize: 14,
              ),
            ),
          ),
        ],
      ),
    );
  }

  Future<void> _handleSlideAction(bool isWithinRadius) async {
    if (!isWithinRadius) {
      QuickAlert.show(
        context: context,
        type: QuickAlertType.error,
        title: 'Gagal',
        text: 'Anda berada di luar radius yang ditentukan',
      );
      return;
    }

    if (!start) {
      await _startTask();
    } else {
      await _finishTask();
    }
  }

  Future<void> _startTask() async {
    QuickAlert.show(
      context: context,
      type: QuickAlertType.loading,
      title: 'Memulai tugas...',
    );

    final position = GoogleMapSample.currentPosition!;

    final res =
        await Provider.of<PelaksanaanSendProvider>(
          context,
          listen: false,
        ).doPelaksanaan(
          data.id.toString(),
          id,
          position.latitude.toString(),
          position.longitude.toString(),
          accuracy: position.accuracy,
          isMock: position.isMocked,
        );

    if (!mounted) return;
    Navigator.pop(context);

    QuickAlert.show(
      context: context,
      type: res is PelaksanaanSendResponse
          ? QuickAlertType.success
          : QuickAlertType.error,
      title: res is PelaksanaanSendResponse ? 'Berhasil' : 'Gagal',
      text: res is PelaksanaanSendResponse ? res.message : res.toString(),
      onConfirmBtnTap: () {
        Provider.of<TaskGetAllProvider>(context, listen: false).getTask();
        // Refresh dashboard list
        Provider.of<DetailTaskGetProvider>(context, listen: false).getTask(id);
        Navigator.popUntil(context, (r) => r.isFirst);
      },
    );
  }

  Future<void> _finishTask() async {
    QuickAlert.show(
      context: context,
      type: QuickAlertType.loading,
      title: 'Menyelesaikan tugas...',
    );

    final position = GoogleMapSample.currentPosition!;

    final res = await Provider.of<CoProvider>(context, listen: false).doCo(
      data.id.toString(),
      id,
      position.latitude.toString(),
      position.longitude.toString(),
      accuracy: position.accuracy,
      isMock: position.isMocked,
    );

    Navigator.pop(context);

    QuickAlert.show(
      context: context,
      type: res == 'Pelaksanaan kegiatan berhasil diselesaikan'
          ? QuickAlertType.success
          : QuickAlertType.error,
      title: res == 'Pelaksanaan kegiatan berhasil diselesaikan'
          ? 'Berhasil'
          : 'Gagal',
      text: res == 'Pelaksanaan kegiatan berhasil diselesaikan'
          ? res
          : res.toString(),
      onConfirmBtnTap: () {
        Provider.of<TaskGetAllProvider>(context, listen: false).getTask();
        // Refresh dashboard list
        Provider.of<DetailTaskGetProvider>(context, listen: false).getTask(id);
        Navigator.popUntil(context, (r) => r.isFirst);
      },
    );
  }

  Widget _buildActionButtons() {
    final now = DateTime.now();
    final selisih = now.difference(data.jadwal).inDays;

    final idTeknisi = int.parse(
      Provider.of<PreferencesIDProvider>(context, listen: false).isUserRole,
    );

    final filtered = data.pelaksanaan.where(
      (a) => a.kegiatanId == data.id && a.teknisiId == idTeknisi,
    );

    String status = filtered.isNotEmpty ? filtered.first.status : 'tidak';
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(
          'Aksi Lainnya',
          style: TextStyle(fontFamily: 'Poppins',
            fontSize: 16,
            fontWeight: FontWeight.w600,
            color: textPrimary,
          ),
        ),
        const SizedBox(height: 12),
        Wrap(
          spacing: 12,
          runSpacing: 12,
          children: [
            if (status == 'menunggu laporan' && selisih < 2)
              _buildActionButton('Kirim Laporan', Icons.task, successColor, () {
                Navigator.pushNamed(
                  context,
                  ReportDonePage.routeName,
                  arguments: [int.parse(id), data.id],
                );
              }),
            if ((status == 'menunggu laporan' || status == 'selesai') &&
                selisih < 2)
              _buildActionButton(
                'Claim Reimbursement',
                Icons.task,
                successColor,
                () {
                  Navigator.push(
                    context,
                    MaterialPageRoute(
                      builder: (context) =>
                          ReimburseAddPage(teknisiId: idTeknisi, kegiatanId: data.id)
                    ),
                  );
                },
              ),
            if (!history && data.status != 'dibatalkan')
              _buildActionButton(
                'Batalkan',
                Icons.cancel_outlined,
                errorColor,
                () => _rescheduleOrCancel(false),
              ),
            if (start && selisih == 0)
              _buildActionButton(
                'Lanjut Nanti',
                Icons.schedule_outlined,
                warningColor,
                () => Navigator.pushNamed(
                  context,
                  LanjutNantiPage.routeName,
                  arguments: [
                    int.parse(id),
                    data.id,
                    GoogleMapSample.currentPosition!.latitude,
                    GoogleMapSample.currentPosition!.longitude,
                  ],
                ),
              ),
            if (history)
              _buildActionButton(
                'Lihat Invoice',
                Icons.receipt_outlined,
                successColor,
                () => Navigator.push(
                  context,
                  MaterialPageRoute(
                    builder: (_) => DetailInvoicePage(invoiceId: data.kode),
                  ),
                ),
              ),
          ],
        ),
      ],
    );
  }

  Widget _buildActionButton(
    String label,
    IconData icon,
    Color color,
    VoidCallback onPressed,
  ) {
    return ElevatedButton.icon(
      style: ElevatedButton.styleFrom(
        backgroundColor: color.withValues(alpha:0.1),
        foregroundColor: color,
        elevation: 0,
        shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.circular(12),
          side: BorderSide(color: color.withValues(alpha:0.3)),
        ),
        padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 12),
      ),
      icon: Icon(icon, size: 20),
      label: Text(
        label,
        style: TextStyle(fontFamily: 'Poppins',fontWeight: FontWeight.w500, fontSize: 14),
      ),
      onPressed: onPressed,
    );
  }

  Future<void> _rescheduleOrCancel(bool lanjut) async {
    QuickAlert.show(
      context: context,
      type: QuickAlertType.confirm,
      title: lanjut ? 'Lanjut Nanti' : 'Batalkan Tugas',
      text: 'Apakah Anda yakin ingin melakukan tindakan ini?',
      onConfirmBtnTap: () async {
        Navigator.pop(context);
        QuickAlert.show(
          context: context,
          type: QuickAlertType.loading,
          title: 'Memproses...',
        );

        final res = await Provider.of<RescheduleProvider>(
          context,
          listen: false,
        ).doReschedule(id, data.id.toString());

        Navigator.pop(context);

        QuickAlert.show(
          context: context,
          type: res.toString().contains('berhasil')
              ? QuickAlertType.success
              : QuickAlertType.error,
          title: res.toString().contains('berhasil') ? 'Sukses' : 'Gagal',
          text: res,
          onConfirmBtnTap: () {
            Provider.of<TaskGetAllProvider>(context, listen: false).getTask();
            // Refresh dashboard list
            Provider.of<DetailTaskGetProvider>(context, listen: false).getTask(id);
            Navigator.popUntil(context, (r) => r.isFirst);
          },
        );
      },
    );
  }

  Widget _buildCustomAppBar() {
    return Container(
      padding: EdgeInsets.only(
        top: MediaQuery.of(context).padding.top + 8,
        left: 16,
        right: 16,
        bottom: 12,
      ),
      decoration: BoxDecoration(
        gradient: const LinearGradient(
          colors: [Color(0xFF2563EB), Color(0xFF3B82F6)],
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
        ),
        boxShadow: [
          BoxShadow(
            color: primaryColor.withValues(alpha: 0.2),
            blurRadius: 8,
            offset: const Offset(0, 2),
          ),
        ],
      ),
      child: Row(
        children: [
          // Back button
          Material(
            color: Colors.white.withValues(alpha: 0.15),
            borderRadius: BorderRadius.circular(12),
            child: InkWell(
              onTap: () => Navigator.pop(context),
              borderRadius: BorderRadius.circular(12),
              child: const Padding(
                padding: EdgeInsets.all(10),
                child: Icon(Icons.arrow_back_ios_new, color: Colors.white, size: 20),
              ),
            ),
          ),
          const SizedBox(width: 14),
          // Title
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                const Text(
                  'Detail Tugas',
                  style: TextStyle(
                    fontFamily: 'Poppins',
                    fontSize: 18,
                    fontWeight: FontWeight.w600,
                    color: Colors.white,
                  ),
                ),
                Text(
                  data.kegiatan,
                  style: TextStyle(
                    fontFamily: 'Poppins',
                    fontSize: 12,
                    color: Colors.white.withValues(alpha: 0.8),
                  ),
                ),
              ],
            ),
          ),
          // Navigation button
          Material(
            color: Colors.white.withValues(alpha: 0.15),
            borderRadius: BorderRadius.circular(12),
            child: InkWell(
              onTap: _openGoogleMapsNavigation,
              borderRadius: BorderRadius.circular(12),
              child: const Padding(
                padding: EdgeInsets.all(10),
                child: Icon(Icons.navigation_rounded, color: Colors.white, size: 22),
              ),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildStatusBar() {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 10),
      decoration: BoxDecoration(
        color: Colors.white,
        border: Border(
          bottom: BorderSide(
            color: Colors.grey.withValues(alpha: 0.1),
            width: 1,
          ),
        ),
      ),
      child: Row(
        children: [
          // Status badge
          Container(
            padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
            decoration: BoxDecoration(
              color: _getStatusColor().withValues(alpha: 0.1),
              borderRadius: BorderRadius.circular(20),
            ),
            child: Row(
              mainAxisSize: MainAxisSize.min,
              children: [
                Icon(_getStatusIcon(), color: _getStatusColor(), size: 14),
                const SizedBox(width: 6),
                Text(
                  _getTaskStatus(),
                  style: TextStyle(
                    fontFamily: 'Poppins',
                    fontSize: 12,
                    fontWeight: FontWeight.w600,
                    color: _getStatusColor(),
                  ),
                ),
              ],
            ),
          ),
          const Spacer(),
          // Customer name
          Flexible(
            child: Text(
              data.dataCustomer.nama,
              style: const TextStyle(
                fontFamily: 'Poppins',
                fontSize: 13,
                fontWeight: FontWeight.w500,
                color: textPrimary,
              ),
              overflow: TextOverflow.ellipsis,
              maxLines: 1,
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildBottomPanel() {
    final bottomPadding = MediaQuery.of(context).padding.bottom;
    return Container(
      padding: EdgeInsets.fromLTRB(20, 16, 20, 20 + bottomPadding),
      decoration: BoxDecoration(
        color: cardColor,
        borderRadius: const BorderRadius.vertical(top: Radius.circular(24)),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.08),
            blurRadius: 16,
            offset: const Offset(0, -4),
          ),
        ],
      ),
      child: Column(
        mainAxisSize: MainAxisSize.min,
        children: [
          // Quick info row
          Row(
            children: [
              Expanded(
                child: _buildQuickInfoItem(
                  Icons.calendar_today_rounded,
                  'Jadwal',
                  '${data.jadwal.day}/${data.jadwal.month}/${data.jadwal.year}',
                ),
              ),
              Container(width: 1, height: 40, color: Colors.grey.withValues(alpha: 0.2)),
              Expanded(
                child: _buildQuickInfoItem(
                  Icons.access_time_rounded,
                  'Waktu',
                  '${data.jadwal.hour.toString().padLeft(2, '0')}:${data.jadwal.minute.toString().padLeft(2, '0')}',
                ),
              ),
            ],
          ),
          const SizedBox(height: 16),
          // Main action button
          SizedBox(
            width: double.infinity,
            child: ElevatedButton(
              onPressed: _showBottomSheetActions,
              style: ElevatedButton.styleFrom(
                backgroundColor: primaryColor,
                foregroundColor: Colors.white,
                elevation: 0,
                padding: const EdgeInsets.symmetric(vertical: 16),
                shape: RoundedRectangleBorder(
                  borderRadius: BorderRadius.circular(14),
                ),
              ),
              child: const Row(
                mainAxisAlignment: MainAxisAlignment.center,
                children: [
                  Icon(Icons.touch_app_rounded, size: 20),
                  SizedBox(width: 10),
                  Text(
                    'Detail & Aksi',
                    style: TextStyle(
                      fontFamily: 'Poppins',
                      fontSize: 15,
                      fontWeight: FontWeight.w600,
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

  Widget _buildQuickInfoItem(IconData icon, String label, String value) {
    return Padding(
      padding: const EdgeInsets.symmetric(horizontal: 12),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          Icon(icon, size: 18, color: textSecondary),
          const SizedBox(width: 8),
          Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(
                label,
                style: const TextStyle(
                  fontFamily: 'Poppins',
                  fontSize: 11,
                  color: textSecondary,
                ),
              ),
              Text(
                value,
                style: const TextStyle(
                  fontFamily: 'Poppins',
                  fontSize: 13,
                  fontWeight: FontWeight.w600,
                  color: textPrimary,
                ),
              ),
            ],
          ),
        ],
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      key: ValueKey('task_page_${data.id}_${data.kode}'),
      backgroundColor: surfaceColor,
      body: Column(
        children: [
          // Custom App Bar
          _buildCustomAppBar(),
          // Status bar
          _buildStatusBar(),
          // Map
          Expanded(
            child: ClipRRect(child: GoogleMapSample(taskData: data)),
          ),
          // Bottom action panel
          _buildBottomPanel(),
        ],
      ),
    );
  }

}
