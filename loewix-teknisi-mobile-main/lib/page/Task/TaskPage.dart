import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:provider/provider.dart';
import 'package:slide_to_act/slide_to_act.dart';
import 'package:quickalert/quickalert.dart';
import 'package:teknisi_loewix/page/reimburse/reimburse_add_screen.dart';
import 'package:teknisi_loewix/service/model/pelaksanaan/PelaksanaanSend.dart';
import 'package:teknisi_loewix/service/provider/Pelaksanaan/CoProvider.dart';
import 'package:url_launcher/url_launcher.dart';
import 'package:tutorial_coach_mark/tutorial_coach_mark.dart';
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
import '../../widget/CoachMarkHelper.dart';
import '../../service/api/ApiBast.dart';
import 'ReportDonePage.dart';
import 'InputBastPage.dart';
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
  bool _needsReport = false;
  late bool _isKetua;
  late bool _isSoloTeknisi;

  // Cached computed values
  String? _cachedStatus;
  Color? _cachedStatusColor;
  IconData? _cachedStatusIcon;
  bool _hasBast = false;
  bool _wajibBast = false;
  int _bastTitikCount = 0;

  // Coach Mark Keys
  final GlobalKey _keyMap = GlobalKey();
  final GlobalKey _keyInfoChips = GlobalKey();
  final GlobalKey _keyActionButton = GlobalKey();
  bool _coachMarkShown = false;

  // Bottom sheet coach mark keys
  final GlobalKey _keySheetStatus = GlobalKey();
  final GlobalKey _keySheetSlider = GlobalKey();
  final GlobalKey _keySheetActions = GlobalKey();
  bool _sheetCoachShown = false;
  bool _isBottomSheetOpen = false;
  double _panelDragDy = 0.0;

  // Modern color scheme - consistent with app theme
  static const Color primaryColor = Color(0xFF2563EB);
  static const Color secondaryColor = Color(0xFF0EA5E9);
  static const Color errorColor = Color(0xFFEF4444);
  static const Color warningColor = Color(0xFFF59E0B);
  static const Color successColor = Color(0xFF10B981);
  static const Color surfaceColor = Color(0xFFF8FAFC);
  static const Color textPrimary = Color(0xFF1F2937);

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

    // Determine ketua status (HARUS sebelum _computeNeedsReport!)
    _isSoloTeknisi = data.dataTeknisi.length == 1;
    final myTeknisiData = data.dataTeknisi.where((t) => t.teknisiId == _idTeknisi);
    _isKetua = _isSoloTeknisi || (myTeknisiData.isNotEmpty && myTeknisiData.first.isKetua == 1);

    // Cek apakah tugas selesai tapi belum upload laporan
    _computeNeedsReport();

    // Pre-compute status values
    _computeStatusValues();

    // Auto-refresh from API to get latest pelaksanaan data
    WidgetsBinding.instance.addPostFrameCallback((_) {
      _refreshFromApi();
      _loadBastStatus();
      _showTaskCoachMark();
    });
  }

  Future<void> _loadBastStatus() async {
    try {
      final res = await ApiBast().getDetail(data.kode, customerId: data.customerId);
      if (!mounted) return;
      final bast = res['bast'] as Map<String, dynamic>?;
      final titik = res['titik_kamera'] as List? ?? const [];
      setState(() {
        _wajibBast = res['wajib_bast'] == true;
        _hasBast = bast != null && (bast['foto_display_utama']?.toString().isNotEmpty ?? false);
        _bastTitikCount = titik.length;
      });
    } catch (_) {}
  }

  Future<void> _refreshFromApi() async {
    try {
      final provider = Provider.of<DetailTaskGetProvider>(context, listen: false);
      final result = await provider.getTask(id);
      if (result is TaskAllResponse && mounted) {
        // Find the same task by ID in the fresh response
        final freshTask = result.data.where((t) => t.id == data.id);
        if (freshTask.isNotEmpty) {
          setState(() {
            data = freshTask.first;
            start = data.pelaksanaan.any(
              (e) => e.teknisiId == _idTeknisi && e.status == 'berjalan',
            );
            _computeNeedsReport();
            _isSoloTeknisi = data.dataTeknisi.length == 1;
            final myTeknisiData = data.dataTeknisi.where((t) => t.teknisiId == _idTeknisi);
            _isKetua = _isSoloTeknisi || (myTeknisiData.isNotEmpty && myTeknisiData.first.isKetua == 1);
            _computeStatusValues();
          });
        }
      }
    } catch (_) {
      // Fallback: use the data passed from CardTask
    }
  }

  void _computeStatusValues() {
    // Jika kegiatan di-reschedule (status utama 'dijadwalkan'),
    // abaikan pelaksanaan lama dan tampilkan sebagai tugas baru
    if (data.status.toLowerCase() == 'dijadwalkan') {
      // Cek apakah ada pelaksanaan BARU (bukan dari jadwal lama)
      final activePel = data.pelaksanaan.where(
        (a) => a.kegiatanId == data.id && a.teknisiId == _idTeknisi && a.status == 'berjalan',
      );
      if (activePel.isNotEmpty) {
        _cachedStatus = 'Sedang Berjalan';
        _cachedStatusColor = successColor;
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
      return;
    }

    final filtered = data.pelaksanaan.where(
      (a) => a.kegiatanId == data.id && a.teknisiId == _idTeknisi,
    );
    // Gunakan record TERBARU (.last), bukan .first, untuk status yang benar
    // Jika start == true (ada pelaksanaan berjalan), paksa status 'berjalan'
    String status;
    if (start) {
      status = 'berjalan';
    } else {
      status = filtered.isNotEmpty ? filtered.last.status : 'tidak';
    }
    final now = DateTime.now();
    final selisih = now.difference(data.jadwal).inDays;

    // Compute status text
    if (_needsReport) {
      _cachedStatus = 'Perlu Laporan';
      _cachedStatusColor = warningColor;
      _cachedStatusIcon = Icons.edit_document;
    } else if (status == 'selesai') {
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

  void _computeNeedsReport() {
    // Task yang di-reschedule tidak perlu laporan dari pelaksanaan lama
    if (data.status.toLowerCase() == 'dijadwalkan') {
      _needsReport = false;
      return;
    }

    final myPel = data.pelaksanaan.where(
      (e) => e.teknisiId == _idTeknisi && e.status == 'selesai',
    );
    if (myPel.isNotEmpty && _isKetua) {
      // Hanya Ketua yang perlu upload laporan
      final pel = myPel.first;
      _needsReport = (pel.permasalahan == null ||
          pel.permasalahan.toString().trim().isEmpty) &&
          pel.image1 == null;
    } else {
      _needsReport = false;
    }
  }

  Widget _buildLengkapiLaporanButton() {
    // Kumpulkan ID anggota (selain Ketua sendiri) untuk sync laporan
    final anggotaIds = data.dataTeknisi
        .where((t) => t.teknisiId != _idTeknisi)
        .map((t) => t.teknisiId)
        .toList();

    return Material(
      color: Colors.transparent,
      child: InkWell(
        borderRadius: BorderRadius.circular(16),
        onTap: () {
          Navigator.pushNamed(
            context,
            ReportDonePage.routeName,
            arguments: [_idTeknisi, data.id, anggotaIds],
          );
        },
        child: Container(
          width: double.infinity,
          padding: const EdgeInsets.symmetric(vertical: 15),
          decoration: BoxDecoration(
            gradient: const LinearGradient(
              colors: [Color(0xFFFF5B22), Color(0xFFFF7A00)],
              begin: Alignment.centerLeft,
              end: Alignment.centerRight,
            ),
            borderRadius: BorderRadius.circular(16),
          ),
          child: const Row(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              Icon(Icons.edit_document, color: Colors.white, size: 20),
              SizedBox(width: 10),
              Text(
                'Lengkapi Laporan',
                style: TextStyle(
                  fontFamily: 'Poppins',
                  fontSize: 14.5,
                  fontWeight: FontWeight.w700,
                  color: Colors.white,
                ),
              ),
            ],
          ),
        ),
      ),
    );
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
    ).toList();
    final status = filtered.isNotEmpty ? filtered.last.status : 'tidak';
    // Override: reschedule task → abaikan pelaksanaan lama (hanya jika belum mulai baru)
    if (data.status.toLowerCase() == 'dijadwalkan' && (status == 'selesai' || status == 'menunggu laporan')) {
      // Cek apakah ada pelaksanaan baru (berjalan) — jika ada, gunakan itu
      final activePel = filtered.where((p) => p.status == 'berjalan');
      if (activePel.isNotEmpty) return 'berjalan';
      return 'tidak';
    }
    return status;
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

  void _openSheetFromSwipe() {
    if (_isBottomSheetOpen) return;
    if (_panelDragDy != 0.0) {
      setState(() => _panelDragDy = 0.0);
    }
    HapticFeedback.lightImpact();
    _showBottomSheetActions();
  }

  String _formatJadwalLengkap(DateTime dt) {
    const hari = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'];
    const bulan = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
    final hh = dt.hour.toString().padLeft(2, '0');
    final mm = dt.minute.toString().padLeft(2, '0');
    return '${hari[dt.weekday - 1]}, ${dt.day} ${bulan[dt.month - 1]} ${dt.year} • $hh:$mm WIB';
  }

  void _showBottomSheetActions() {
    if (_isBottomSheetOpen) return;
    _isBottomSheetOpen = true;
    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (sheetCtx) {
        final safeBottom = MediaQuery.viewPaddingOf(sheetCtx).bottom;
        WidgetsBinding.instance.addPostFrameCallback((_) {
          Future.delayed(const Duration(milliseconds: 800), () {
            if (!mounted || !sheetCtx.mounted || _sheetCoachShown || CoachMarkHelper.isActive) return;
            _sheetCoachShown = true;
            _showSheetCoachMark(sheetCtx);
          });
        });
        return Container(
          decoration: BoxDecoration(
            color: Colors.white,
            borderRadius: const BorderRadius.vertical(top: Radius.circular(28)),
            boxShadow: [
              BoxShadow(
                color: Colors.black.withValues(alpha: 0.14),
                blurRadius: 24,
                offset: const Offset(0, -6),
              ),
            ],
          ),
          child: DraggableScrollableSheet(
            initialChildSize: _shouldShowSlider() ? 0.78 : 0.72,
            minChildSize: 0.5,
            maxChildSize: 0.95,
            expand: false,
            builder: (context, scrollController) {
              return SingleChildScrollView(
                controller: scrollController,
                child: Padding(
                  padding: EdgeInsets.fromLTRB(20, 10, 20, 24 + safeBottom),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      _buildBottomSheetHeader(sheetCtx),
                      const SizedBox(height: 16),
                      Container(key: _keySheetStatus, child: _buildTaskStatusCard()),
                      const SizedBox(height: 14),
                      _buildTaskDetails(),
                      if (_shouldShowSlider()) ...[
                        const SizedBox(height: 18),
                        Container(key: _keySheetSlider, child: _buildSlideAction()),
                      ] else ...[
                        const SizedBox(height: 14),
                        _buildStatusMessage(),
                      ],
                      const SizedBox(height: 18),
                      Container(key: _keySheetActions, child: _buildActionButtons()),
                    ],
                  ),
                ),
              );
            },
          ),
        );
      },
    ).whenComplete(() {
      _isBottomSheetOpen = false;
    });
  }

  Widget _buildStatusMessage() {
    final status = _pelaksanaanStatus;
    String message;
    Color messageColor;
    IconData messageIcon;

    if (status == 'selesai') {
      message = 'Tugas telah selesai dikerjakan';
      messageColor = successColor;
      messageIcon = Icons.check_circle_rounded;
    } else if (status == 'menunggu laporan') {
      message = _isKetua
          ? 'Silakan kirim laporan kegiatan untuk menyelesaikan tugas'
          : 'Menunggu Ketua Tim mengirim laporan kegiatan';
      messageColor = secondaryColor;
      messageIcon = Icons.pending_actions_rounded;
    } else if (status == 'Lanjut Nanti') {
      message = 'Tugas dijadwalkan untuk dilanjutkan nanti';
      messageColor = warningColor;
      messageIcon = Icons.pause_circle_rounded;
    } else if (status == 'dibatalkan') {
      message = 'Tugas telah dibatalkan';
      messageColor = errorColor;
      messageIcon = Icons.cancel_rounded;
    } else if (_isSchedulePassed() && !start) {
      message = 'Jadwal kunjungan tugas ini telah terlewat';
      messageColor = errorColor;
      messageIcon = Icons.warning_rounded;
    } else if (status == 'berjalan') {
      message = 'Tugas sedang berjalan di lokasi pelanggan';
      messageColor = successColor;
      messageIcon = Icons.play_circle_fill_rounded;
    } else {
      message = 'Tugas siap dimulai sesuai jadwal';
      messageColor = warningColor;
      messageIcon = Icons.schedule_rounded;
    }

    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
      decoration: BoxDecoration(
        color: messageColor.withValues(alpha: 0.08),
        borderRadius: BorderRadius.circular(14),
        border: Border.all(color: messageColor.withValues(alpha: 0.25)),
      ),
      child: Row(
        children: [
          Container(
            padding: const EdgeInsets.all(6),
            decoration: BoxDecoration(
              color: messageColor.withValues(alpha: 0.14),
              borderRadius: BorderRadius.circular(8),
            ),
            child: Icon(messageIcon, color: messageColor, size: 18),
          ),
          const SizedBox(width: 10),
          Expanded(
            child: Text(
              message,
              style: TextStyle(
                fontFamily: 'Poppins',
                color: const Color(0xFF0F172A),
                fontWeight: FontWeight.w600,
                fontSize: 12.5,
              ),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildBottomSheetHeader(BuildContext sheetCtx) {
    return Column(
      children: [
        Center(
          child: Container(
            width: 38,
            height: 4,
            margin: const EdgeInsets.only(bottom: 14),
            decoration: BoxDecoration(
              color: const Color(0xFFCBD5E1),
              borderRadius: BorderRadius.circular(2),
            ),
          ),
        ),
        Row(
          children: [
            Container(
              width: 44,
              height: 44,
              decoration: BoxDecoration(
                gradient: const LinearGradient(
                  colors: [Color(0xFFFF5B22), Color(0xFFFF8A00)],
                  begin: Alignment.topLeft,
                  end: Alignment.bottomRight,
                ),
                borderRadius: BorderRadius.circular(12),
                boxShadow: [
                  BoxShadow(
                    color: const Color(0xFFFF5B22).withValues(alpha: 0.3),
                    blurRadius: 8,
                    offset: const Offset(0, 3),
                  ),
                ],
              ),
              child: const Center(
                child: Icon(Icons.assignment_rounded, color: Colors.white, size: 22),
              ),
            ),
            const SizedBox(width: 12),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  const Text(
                    'Detail Tugas',
                    style: TextStyle(
                      fontFamily: 'Poppins',
                      fontSize: 16.5,
                      fontWeight: FontWeight.w700,
                      color: Color(0xFF0F172A),
                    ),
                  ),
                  const SizedBox(height: 3),
                  Wrap(
                    spacing: 6,
                    runSpacing: 4,
                    crossAxisAlignment: WrapCrossAlignment.center,
                    children: [
                      Container(
                        padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 2),
                        decoration: BoxDecoration(
                          color: const Color(0xFFF1F5F9),
                          borderRadius: BorderRadius.circular(6),
                        ),
                        child: Text(
                          'ID: ${data.kode}',
                          style: const TextStyle(
                            fontFamily: 'Poppins',
                            fontSize: 11,
                            color: Color(0xFF0F172A),
                            fontWeight: FontWeight.w700,
                          ),
                        ),
                      ),
                      Container(
                        padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 2),
                        decoration: BoxDecoration(
                          color: const Color(0xFFFF5B22).withValues(alpha: 0.12),
                          borderRadius: BorderRadius.circular(6),
                        ),
                        child: Text(
                          data.kegiatan,
                          style: const TextStyle(
                            fontFamily: 'Poppins',
                            fontSize: 10.5,
                            color: Color(0xFFFF5B22),
                            fontWeight: FontWeight.w700,
                          ),
                        ),
                      ),
                    ],
                  ),
                ],
              ),
            ),
            Material(
              color: const Color(0xFFF1F5F9),
              borderRadius: BorderRadius.circular(12),
              child: InkWell(
                onTap: () => Navigator.of(sheetCtx).pop(),
                borderRadius: BorderRadius.circular(12),
                child: const Padding(
                  padding: EdgeInsets.all(9),
                  child: Icon(Icons.close_rounded, size: 18, color: Color(0xFF0F172A)),
                ),
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
    final jamWib =
        '${data.jadwal.hour.toString().padLeft(2, '0')}:${data.jadwal.minute.toString().padLeft(2, '0')} WIB';

    return Container(
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: const Color(0xFFF8FAFC),
        borderRadius: BorderRadius.circular(18),
        border: Border.all(color: const Color(0xFFE2E8F0)),
      ),
      child: Column(
        children: [
          Row(
            children: [
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 5),
                decoration: BoxDecoration(
                  color: statusColor.withValues(alpha: 0.12),
                  borderRadius: BorderRadius.circular(10),
                  border: Border.all(color: statusColor.withValues(alpha: 0.25)),
                ),
                child: Row(
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    Icon(statusIcon, color: statusColor, size: 15),
                    const SizedBox(width: 6),
                    Text(
                      statusText,
                      style: TextStyle(
                        fontFamily: 'Poppins',
                        fontSize: 12.5,
                        fontWeight: FontWeight.w700,
                        color: statusColor,
                      ),
                    ),
                  ],
                ),
              ),
              const Spacer(),
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 5),
                decoration: BoxDecoration(
                  color: Colors.white,
                  borderRadius: BorderRadius.circular(10),
                  border: Border.all(color: const Color(0xFFE2E8F0)),
                ),
                child: Row(
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    const Icon(Icons.access_time_rounded, size: 13, color: Color(0xFF64748B)),
                    const SizedBox(width: 5),
                    Text(
                      jamWib,
                      style: const TextStyle(
                        fontFamily: 'Poppins',
                        fontSize: 11.5,
                        fontWeight: FontWeight.w600,
                        color: Color(0xFF0F172A),
                      ),
                    ),
                  ],
                ),
              ),
            ],
          ),
          const Padding(
            padding: EdgeInsets.symmetric(vertical: 12),
            child: Divider(height: 1, color: Color(0xFFE2E8F0)),
          ),
          _buildProgressSteps(),
        ],
      ),
    );
  }

  Widget _buildInfoRow({
    required IconData icon,
    required Color iconColor,
    required String label,
    required String value,
  }) {
    return Row(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Container(
          width: 34,
          height: 34,
          decoration: BoxDecoration(
            color: iconColor.withValues(alpha: 0.12),
            borderRadius: BorderRadius.circular(10),
          ),
          child: Center(child: Icon(icon, color: iconColor, size: 17)),
        ),
        const SizedBox(width: 12),
        Expanded(
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(
                label,
                style: const TextStyle(
                  fontFamily: 'Poppins',
                  fontSize: 11,
                  fontWeight: FontWeight.w600,
                  color: Color(0xFF64748B),
                ),
              ),
              const SizedBox(height: 2),
              Text(
                value,
                style: const TextStyle(
                  fontFamily: 'Poppins',
                  fontSize: 13,
                  fontWeight: FontWeight.w600,
                  color: Color(0xFF0F172A),
                  height: 1.4,
                ),
              ),
            ],
          ),
        ),
      ],
    );
  }

  Widget _buildTaskDetails() {
    final alamatLengkap = data.dataCustomer.alamat.trim().isNotEmpty
        ? data.dataCustomer.alamat.trim()
        : 'Alamat sesuai jadwal';
    final catatanText = (data.keterangan != null && data.keterangan.toString().trim().isNotEmpty)
        ? data.keterangan.toString().trim()
        : 'Tidak ada catatan khusus';

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        // Kartu Pelanggan & Kontak Cepat (Satu Konsep dengan Gambar 2)
        Container(
          padding: const EdgeInsets.all(14),
          decoration: BoxDecoration(
            color: const Color(0xFFF8FAFC),
            borderRadius: BorderRadius.circular(18),
            border: Border.all(color: const Color(0xFFE2E8F0)),
          ),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Row(
                children: [
                  Container(
                    width: 44,
                    height: 44,
                    decoration: BoxDecoration(
                      gradient: const LinearGradient(
                        colors: [Color(0xFFFF5B22), Color(0xFFFF8A00)],
                        begin: Alignment.topLeft,
                        end: Alignment.bottomRight,
                      ),
                      borderRadius: BorderRadius.circular(12),
                      boxShadow: [
                        BoxShadow(
                          color: const Color(0xFFFF5B22).withValues(alpha: 0.28),
                          blurRadius: 8,
                          offset: const Offset(0, 3),
                        ),
                      ],
                    ),
                    child: const Center(
                      child: Icon(Icons.business_rounded, color: Colors.white, size: 22),
                    ),
                  ),
                  const SizedBox(width: 12),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        const Text(
                          'Pelanggan',
                          style: TextStyle(
                            fontFamily: 'Poppins',
                            fontSize: 10.5,
                            fontWeight: FontWeight.w600,
                            color: Color(0xFF64748B),
                          ),
                        ),
                        Text(
                          data.dataCustomer.nama,
                          style: const TextStyle(
                            fontFamily: 'Poppins',
                            fontSize: 14,
                            fontWeight: FontWeight.w700,
                            color: Color(0xFF0F172A),
                          ),
                        ),
                      ],
                    ),
                  ),
                  const SizedBox(width: 8),
                  Row(
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      _buildQuickCircleButton(
                        icon: Icons.chat_bubble_rounded,
                        color: const Color(0xFF25D366),
                        tooltip: 'Chat WhatsApp',
                        onTap: () => _openWhatsAppChat(data.dataCustomer.telp),
                      ),
                      const SizedBox(width: 8),
                      _buildQuickCircleButton(
                        icon: Icons.phone_rounded,
                        color: const Color(0xFF0284C7),
                        tooltip: 'Telepon',
                        onTap: () => _makePhoneCall(data.dataCustomer.telp),
                      ),
                      const SizedBox(width: 8),
                      _buildQuickCircleButton(
                        icon: Icons.navigation_rounded,
                        color: const Color(0xFFFF5B22),
                        tooltip: 'Navigasi Google Maps',
                        onTap: _openGoogleMapsNavigation,
                      ),
                    ],
                  ),
                ],
              ),
              const Padding(
                padding: EdgeInsets.symmetric(vertical: 12),
                child: Divider(height: 1, color: Color(0xFFE2E8F0)),
              ),
              _buildInfoRow(
                icon: Icons.location_on_rounded,
                iconColor: const Color(0xFFFF5B22),
                label: 'Alamat Kunjungan',
                value: alamatLengkap,
              ),
            ],
          ),
        ),
        const SizedBox(height: 16),
        const Text(
          'Informasi Tugas',
          style: TextStyle(
            fontFamily: 'Poppins',
            fontSize: 14.5,
            fontWeight: FontWeight.w700,
            color: Color(0xFF0F172A),
          ),
        ),
        const SizedBox(height: 10),
        Container(
          padding: const EdgeInsets.all(14),
          decoration: BoxDecoration(
            color: const Color(0xFFF8FAFC),
            borderRadius: BorderRadius.circular(18),
            border: Border.all(color: const Color(0xFFE2E8F0)),
          ),
          child: Column(
            children: [
              _buildInfoRow(
                icon: Icons.calendar_today_rounded,
                iconColor: (_isSchedulePassed() && !start) ? errorColor : const Color(0xFFF59E0B),
                label: 'Jadwal Kunjungan',
                value: _formatJadwalLengkap(data.jadwal),
              ),
              const Padding(
                padding: EdgeInsets.symmetric(vertical: 12),
                child: Divider(height: 1, color: Color(0xFFE2E8F0)),
              ),
              _buildInfoRow(
                icon: Icons.sticky_note_2_rounded,
                iconColor: const Color(0xFF7C3AED),
                label: 'Catatan Pekerjaan',
                value: catatanText,
              ),
            ],
          ),
        ),
        _buildAdminNotesCard(),
        if (data.dataTeknisi.isNotEmpty) ...[
          const SizedBox(height: 12),
          _buildTeamMembersCard(),
        ],
      ],
    );
  }

  Widget _buildAdminNotesCard() {
    final reasons = data.reasons;
    if (reasons == null || reasons.isEmpty) return const SizedBox.shrink();

    return Container(
      margin: const EdgeInsets.only(top: 8),
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: surfaceColor,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: Colors.amber.withValues(alpha: 0.3)),
        boxShadow: [
          BoxShadow(
            color: Colors.amber.withValues(alpha: 0.05),
            blurRadius: 8,
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
                  color: Colors.amber.withValues(alpha: 0.15),
                  borderRadius: BorderRadius.circular(8),
                ),
                child: const Icon(Icons.history_edu, color: Colors.amber, size: 20),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: Text(
                  'Riwayat Catatan Admin',
                  style: TextStyle(
                    fontFamily: 'Poppins',
                    fontSize: 15,
                    fontWeight: FontWeight.w600,
                    color: textPrimary,
                  ),
                ),
              ),
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 2),
                decoration: BoxDecoration(
                  color: Colors.amber.withValues(alpha: 0.2),
                  borderRadius: BorderRadius.circular(12),
                ),
                child: Text(
                  '${reasons.length} Catatan',
                  style: const TextStyle(
                    fontFamily: 'Poppins',
                    fontSize: 11,
                    fontWeight: FontWeight.bold,
                    color: Colors.amber,
                  ),
                ),
              ),
            ],
          ),
          const SizedBox(height: 12),
          ListView.separated(
            shrinkWrap: true,
            physics: const NeverScrollableScrollPhysics(),
            itemCount: reasons.length,
            separatorBuilder: (context, index) => const Divider(height: 16, color: Color(0xFFF1F5F9)),
            itemBuilder: (context, index) {
              final item = reasons[index];
              final hasMedia = item.mediaUrl != null && item.mediaUrl!.isNotEmpty;
              final isPdf = hasMedia && (item.mediaUrl!.toLowerCase().endsWith('.pdf'));

              return Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  if (item.createdAt != null && item.createdAt!.isNotEmpty)
                    Container(
                      padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
                      margin: const EdgeInsets.only(bottom: 4),
                      decoration: BoxDecoration(
                        color: const Color(0xFFF1F5F9),
                        borderRadius: BorderRadius.circular(4),
                      ),
                      child: Text(
                        item.createdAt!,
                        style: const TextStyle(
                          fontFamily: 'Poppins',
                          fontSize: 10,
                          fontWeight: FontWeight.w600,
                          color: Color(0xFF64748B),
                        ),
                      ),
                    ),
                  Text(
                    item.reason,
                    style: TextStyle(
                      fontFamily: 'Poppins',
                      fontSize: 13,
                      color: textPrimary,
                      height: 1.4,
                    ),
                  ),
                  if (hasMedia) ...[
                    const SizedBox(height: 8),
                    if (isPdf)
                      Container(
                        padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
                        decoration: BoxDecoration(
                          color: Colors.red.withValues(alpha: 0.1),
                          borderRadius: BorderRadius.circular(8),
                          border: Border.all(color: Colors.red.withValues(alpha: 0.3)),
                        ),
                        child: const Row(
                          mainAxisSize: MainAxisSize.min,
                          children: [
                            Icon(Icons.picture_as_pdf, size: 16, color: Colors.red),
                            SizedBox(width: 6),
                            Text(
                              'Dokumen PDF Terlampir',
                              style: TextStyle(
                                fontFamily: 'Poppins',
                                fontSize: 11,
                                fontWeight: FontWeight.w600,
                                color: Colors.red,
                              ),
                            ),
                          ],
                        ),
                      )
                    else
                      GestureDetector(
                        onTap: () {
                          showDialog(
                            context: context,
                            builder: (ctx) => Dialog(
                              backgroundColor: Colors.black,
                              insetPadding: const EdgeInsets.all(12),
                              child: Stack(
                                alignment: Alignment.topRight,
                                children: [
                                  InteractiveViewer(
                                    child: Image.network(
                                      item.mediaUrl!,
                                      fit: BoxFit.contain,
                                    ),
                                  ),
                                  IconButton(
                                    icon: const Icon(Icons.close, color: Colors.white, size: 28),
                                    onPressed: () => Navigator.of(ctx).pop(),
                                  ),
                                ],
                              ),
                            ),
                          );
                        },
                        child: ClipRRect(
                          borderRadius: BorderRadius.circular(8),
                          child: Container(
                            constraints: const BoxConstraints(maxHeight: 160),
                            decoration: BoxDecoration(
                              border: Border.all(color: const Color(0xFFE2E8F0)),
                              borderRadius: BorderRadius.circular(8),
                            ),
                            child: Image.network(
                              item.mediaUrl!,
                              fit: BoxFit.cover,
                              errorBuilder: (ctx, err, stack) => const SizedBox.shrink(),
                            ),
                          ),
                        ),
                      ),
                  ],
                ],
              );
            },
          ),
        ],
      ),
    );
  }

  Widget _buildTeamMembersCard() {
    return Container(
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: const Color(0xFFF8FAFC),
        borderRadius: BorderRadius.circular(18),
        border: Border.all(color: const Color(0xFFE2E8F0)),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Container(
                width: 34,
                height: 34,
                decoration: BoxDecoration(
                  color: const Color(0xFFFF5B22).withValues(alpha: 0.12),
                  borderRadius: BorderRadius.circular(10),
                ),
                child: const Center(
                  child: Icon(Icons.groups_rounded, color: Color(0xFFFF5B22), size: 18),
                ),
              ),
              const SizedBox(width: 10),
              const Expanded(
                child: Text(
                  'Tim Teknisi',
                  style: TextStyle(
                    fontFamily: 'Poppins',
                    fontWeight: FontWeight.w700,
                    fontSize: 13.5,
                    color: Color(0xFF0F172A),
                  ),
                ),
              ),
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 9, vertical: 3),
                decoration: BoxDecoration(
                  color: Colors.white,
                  borderRadius: BorderRadius.circular(8),
                  border: Border.all(color: const Color(0xFFE2E8F0)),
                ),
                child: Text(
                  '${data.dataTeknisi.length} Orang',
                  style: const TextStyle(
                    fontFamily: 'Poppins',
                    fontSize: 10.5,
                    fontWeight: FontWeight.w600,
                    color: Color(0xFF64748B),
                  ),
                ),
              ),
            ],
          ),
          const Padding(
            padding: EdgeInsets.symmetric(vertical: 10),
            child: Divider(height: 1, color: Color(0xFFE2E8F0)),
          ),
          ...List.generate(data.dataTeknisi.length, (i) {
            final t = data.dataTeknisi[i];
            final isMe = t.teknisiId == _idTeknisi;
            final isLeader = t.isKetua == 1;
            final initial = t.namaDataTeknisi.trim().isNotEmpty
                ? t.namaDataTeknisi.trim()[0].toUpperCase()
                : 'T';
            return Padding(
              padding: EdgeInsets.only(bottom: i < data.dataTeknisi.length - 1 ? 8 : 0),
              child: Container(
                padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 8),
                decoration: BoxDecoration(
                  color: Colors.white,
                  borderRadius: BorderRadius.circular(12),
                  border: Border.all(
                    color: isMe
                        ? const Color(0xFFFF5B22).withValues(alpha: 0.35)
                        : const Color(0xFFE2E8F0),
                  ),
                ),
                child: Row(
                  children: [
                    Container(
                      width: 32,
                      height: 32,
                      decoration: BoxDecoration(
                        gradient: isLeader
                            ? const LinearGradient(
                                colors: [Color(0xFFFF5B22), Color(0xFFFF8A00)],
                                begin: Alignment.topLeft,
                                end: Alignment.bottomRight,
                              )
                            : null,
                        color: isLeader ? null : const Color(0xFFF1F5F9),
                        shape: BoxShape.circle,
                      ),
                      child: Center(
                        child: Text(
                          initial,
                          style: TextStyle(
                            fontFamily: 'Poppins',
                            fontSize: 12.5,
                            fontWeight: FontWeight.w700,
                            color: isLeader ? Colors.white : const Color(0xFF475569),
                          ),
                        ),
                      ),
                    ),
                    const SizedBox(width: 10),
                    Expanded(
                      child: Row(
                        children: [
                          Flexible(
                            child: Text(
                              t.namaDataTeknisi,
                              style: TextStyle(
                                fontFamily: 'Poppins',
                                fontSize: 12.5,
                                fontWeight: isMe ? FontWeight.w700 : FontWeight.w600,
                                color: const Color(0xFF0F172A),
                              ),
                              maxLines: 1,
                              overflow: TextOverflow.ellipsis,
                            ),
                          ),
                          if (isMe) ...[
                            const SizedBox(width: 6),
                            Container(
                              padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 1),
                              decoration: BoxDecoration(
                                color: const Color(0xFFFF5B22).withValues(alpha: 0.12),
                                borderRadius: BorderRadius.circular(5),
                              ),
                              child: const Text(
                                'Anda',
                                style: TextStyle(
                                  fontFamily: 'Poppins',
                                  fontSize: 9.5,
                                  fontWeight: FontWeight.w700,
                                  color: Color(0xFFFF5B22),
                                ),
                              ),
                            ),
                          ],
                        ],
                      ),
                    ),
                    if (isLeader)
                      Container(
                        padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                        decoration: BoxDecoration(
                          color: const Color(0xFFFFF7ED),
                          borderRadius: BorderRadius.circular(8),
                          border: Border.all(color: const Color(0xFFFED7AA)),
                        ),
                        child: const Row(
                          mainAxisSize: MainAxisSize.min,
                          children: [
                            Icon(Icons.workspace_premium_rounded, size: 12, color: Color(0xFFEA580C)),
                            SizedBox(width: 3),
                            Text(
                              'Ketua',
                              style: TextStyle(
                                fontFamily: 'Poppins',
                                fontSize: 10.5,
                                fontWeight: FontWeight.w700,
                                color: Color(0xFFEA580C),
                              ),
                            ),
                          ],
                        ),
                      ),
                  ],
                ),
              ),
            );
          }),
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

        final bool isLocationError = state.state == ResultState.error;
        final isWithinRadius = isLocationError ? false : _isWithinRadius();
        final sliderColor = start ? const Color(0xFF10B981) : const Color(0xFFFF5B22);

        return Column(
          children: [
            if (isLocationError)
              Container(
                margin: const EdgeInsets.only(bottom: 12),
                padding: const EdgeInsets.all(12),
                decoration: BoxDecoration(
                  color: warningColor.withValues(alpha: 0.1),
                  borderRadius: BorderRadius.circular(12),
                  border: Border.all(color: warningColor.withValues(alpha: 0.3)),
                ),
                child: Row(
                  children: [
                    Icon(Icons.location_off_rounded, color: warningColor, size: 20),
                    const SizedBox(width: 10),
                    Expanded(
                      child: Text(
                        state.message.isNotEmpty ? state.message : 'Lokasi tidak tersedia',
                        style: TextStyle(fontFamily: 'Poppins', color: warningColor, fontWeight: FontWeight.w600, fontSize: 12),
                      ),
                    ),
                  ],
                ),
              ),
            if (!start && !_needsReport)
              Container(
                margin: const EdgeInsets.only(bottom: 12),
                padding: const EdgeInsets.all(12),
                decoration: BoxDecoration(
                  color: const Color(0xFFFFF7ED),
                  borderRadius: BorderRadius.circular(12),
                  border: Border.all(color: const Color(0xFFFED7AA)),
                ),
                child: const Row(
                  children: [
                    Icon(Icons.info_outline_rounded, color: Color(0xFFEA580C), size: 19),
                    SizedBox(width: 8),
                    Expanded(
                      child: Text(
                        'Pastikan pelanggan ada di lokasi. Jika tidak ada, gunakan tombol Batalkan Tugas di bawah.',
                        style: TextStyle(fontFamily: 'Poppins', fontSize: 11.5, color: Color(0xFF9A3412), fontWeight: FontWeight.w500),
                      ),
                    ),
                  ],
                ),
              ),
            Container(
              decoration: BoxDecoration(
                borderRadius: BorderRadius.circular(16),
                boxShadow: [
                  BoxShadow(
                    color: (_needsReport ? const Color(0xFFFF5B22) : sliderColor).withValues(alpha: 0.32),
                    blurRadius: 14,
                    offset: const Offset(0, 4),
                  ),
                ],
              ),
              child: _needsReport
                  ? _buildLengkapiLaporanButton()
                  : SlideAction(
                      outerColor: sliderColor,
                      elevation: 0,
                      innerColor: Colors.white,
                      borderRadius: 16,
                      height: 56,
                      sliderButtonIconSize: 18,
                      sliderButtonIconPadding: 11,
                      text: start ? 'Geser untuk Selesai' : 'Geser untuk Clock In',
                      textStyle: const TextStyle(
                        fontFamily: 'Poppins',
                        color: Colors.white,
                        fontSize: 14.5,
                        fontWeight: FontWeight.w700,
                      ),
                      sliderButtonIcon: Icon(
                        start ? Icons.check_rounded : Icons.arrow_forward_rounded,
                        color: sliderColor,
                        size: 20,
                      ),
                      onSubmit: () => _handleSlideAction(isWithinRadius),
                    ),
            ),
            if (start) ...[
              const SizedBox(height: 12),
              SizedBox(
                width: double.infinity,
                child: OutlinedButton.icon(
                  onPressed: () {
                    final position = GoogleMapSample.currentPosition;
                    if (position == null) return;
                    Navigator.pushNamed(
                      context,
                      LanjutNantiPage.routeName,
                      arguments: [
                        int.parse(id),
                        data.id,
                        position.latitude,
                        position.longitude,
                      ],
                    );
                  },
                  icon: const Icon(Icons.pause_circle_rounded, size: 19),
                  label: const Text(
                    'Lanjut Nanti',
                    style: TextStyle(fontFamily: 'Poppins', fontSize: 13.5, fontWeight: FontWeight.w700),
                  ),
                  style: OutlinedButton.styleFrom(
                    foregroundColor: const Color(0xFF0F172A),
                    backgroundColor: const Color(0xFFF8FAFC),
                    side: const BorderSide(color: Color(0xFFE2E8F0)),
                    padding: const EdgeInsets.symmetric(vertical: 13),
                    shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
                  ),
                ),
              ),
            ],
          ],
        );
      },
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
      // Tampilkan dialog konfirmasi checklist sebelum selesai
      await _showFinishConfirmation();
    }
  }

  Future<void> _showFinishConfirmation() async {
    final List<bool> checks = [false, false, false];
    final noteController = TextEditingController();
    
    final confirmed = await showModalBottomSheet<bool>(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (ctx) {
        return StatefulBuilder(
          builder: (ctx, setModalState) {
            final allChecked = checks.every((c) => c);
            final hasNote = noteController.text.trim().isNotEmpty;
            final canSubmit = allChecked && hasNote;
            
            return Container(
              padding: EdgeInsets.only(
                bottom: MediaQuery.of(ctx).viewInsets.bottom,
              ),
              decoration: const BoxDecoration(
                color: Colors.white,
                borderRadius: BorderRadius.vertical(top: Radius.circular(24)),
              ),
              child: Padding(
                padding: const EdgeInsets.all(24),
                child: Column(
                  mainAxisSize: MainAxisSize.min,
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    // Handle bar
                    Center(
                      child: Container(
                        width: 40, height: 4,
                        decoration: BoxDecoration(
                          color: Colors.grey[300],
                          borderRadius: BorderRadius.circular(2),
                        ),
                      ),
                    ),
                    const SizedBox(height: 20),
                    
                    // Header
                    Row(
                      children: [
                        Container(
                          padding: const EdgeInsets.all(10),
                          decoration: BoxDecoration(
                            color: warningColor.withValues(alpha: 0.1),
                            borderRadius: BorderRadius.circular(12),
                          ),
                          child: Icon(Icons.checklist_rounded, color: warningColor, size: 24),
                        ),
                        const SizedBox(width: 12),
                        const Expanded(
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Text('Konfirmasi Selesai',
                                style: TextStyle(fontFamily: 'Poppins', fontSize: 18, fontWeight: FontWeight.w700)),
                              Text('Pastikan semua sudah selesai',
                                style: TextStyle(fontFamily: 'Poppins', fontSize: 13, color: Color(0xFF64748B))),
                            ],
                          ),
                        ),
                      ],
                    ),
                    const SizedBox(height: 20),
                    
                    // Warning banner
                    Container(
                      padding: const EdgeInsets.all(12),
                      decoration: BoxDecoration(
                        color: const Color(0xFFFFF7ED),
                        borderRadius: BorderRadius.circular(12),
                        border: Border.all(color: warningColor.withValues(alpha: 0.3)),
                      ),
                      child: Row(
                        children: [
                          Icon(Icons.warning_amber_rounded, color: warningColor, size: 20),
                          const SizedBox(width: 8),
                          Expanded(
                            child: Text(
                              'Jika pekerjaan belum selesai, gunakan tombol "Lanjut Nanti"',
                              style: TextStyle(fontFamily: 'Poppins', fontSize: 12, color: warningColor, fontWeight: FontWeight.w500),
                            ),
                          ),
                        ],
                      ),
                    ),
                    const SizedBox(height: 16),
                    
                    // Checklist items
                    _buildCheckItem(
                      'Pekerjaan sudah selesai sepenuhnya',
                      Icons.build_circle_outlined,
                      checks[0],
                      (v) => setModalState(() => checks[0] = v ?? false),
                    ),
                    _buildCheckItem(
                      'Peralatan sudah dicek & beres',
                      Icons.handyman_outlined,
                      checks[1],
                      (v) => setModalState(() => checks[1] = v ?? false),
                    ),
                    _buildCheckItem(
                      'Customer sudah konfirmasi hasil kerja',
                      Icons.person_pin_circle_outlined,
                      checks[2],
                      (v) => setModalState(() => checks[2] = v ?? false),
                    ),
                    const SizedBox(height: 16),
                    
                    // Note field (wajib)
                    Text('Catatan Pekerjaan *',
                      style: TextStyle(fontFamily: 'Poppins', fontSize: 13, fontWeight: FontWeight.w600, color: textPrimary)),
                    const SizedBox(height: 8),
                    TextField(
                      controller: noteController,
                      maxLines: 3,
                      onChanged: (_) => setModalState(() {}),
                      decoration: InputDecoration(
                        hintText: 'Jelaskan pekerjaan yang sudah dilakukan...',
                        hintStyle: TextStyle(fontFamily: 'Poppins', fontSize: 13, color: Colors.grey[400]),
                        filled: true,
                        fillColor: const Color(0xFFF8FAFC),
                        border: OutlineInputBorder(
                          borderRadius: BorderRadius.circular(12),
                          borderSide: BorderSide(color: Colors.grey[300]!),
                        ),
                        enabledBorder: OutlineInputBorder(
                          borderRadius: BorderRadius.circular(12),
                          borderSide: BorderSide(color: Colors.grey[300]!),
                        ),
                        focusedBorder: OutlineInputBorder(
                          borderRadius: BorderRadius.circular(12),
                          borderSide: const BorderSide(color: primaryColor, width: 2),
                        ),
                        contentPadding: const EdgeInsets.all(14),
                      ),
                      style: const TextStyle(fontFamily: 'Poppins', fontSize: 14),
                    ),
                    const SizedBox(height: 20),
                    
                    // Buttons
                    Row(
                      children: [
                        Expanded(
                          child: OutlinedButton(
                            onPressed: () => Navigator.pop(ctx, false),
                            style: OutlinedButton.styleFrom(
                              padding: const EdgeInsets.symmetric(vertical: 14),
                              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                              side: BorderSide(color: Colors.grey[300]!),
                            ),
                            child: const Text('Batal', style: TextStyle(fontFamily: 'Poppins', fontWeight: FontWeight.w600)),
                          ),
                        ),
                        const SizedBox(width: 12),
                        Expanded(
                          flex: 2,
                          child: ElevatedButton.icon(
                            onPressed: canSubmit ? () => Navigator.pop(ctx, true) : null,
                            icon: const Icon(Icons.check_circle, size: 20),
                            label: const Text('Selesaikan Tugas',
                              style: TextStyle(fontFamily: 'Poppins', fontWeight: FontWeight.w600)),
                            style: ElevatedButton.styleFrom(
                              backgroundColor: canSubmit ? successColor : Colors.grey[300],
                              foregroundColor: Colors.white,
                              disabledBackgroundColor: Colors.grey[300],
                              disabledForegroundColor: Colors.grey[500],
                              padding: const EdgeInsets.symmetric(vertical: 14),
                              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                              elevation: canSubmit ? 2 : 0,
                            ),
                          ),
                        ),
                      ],
                    ),
                    const SizedBox(height: 8),
                  ],
                ),
              ),
            );
          },
        );
      },
    );
    
    noteController.dispose();
    
    if (confirmed == true) {
      await _finishTask();
    }
  }

  Widget _buildCheckItem(String label, IconData icon, bool value, Function(bool?) onChanged) {
    return Container(
      margin: const EdgeInsets.only(bottom: 8),
      decoration: BoxDecoration(
        color: value ? successColor.withValues(alpha: 0.05) : const Color(0xFFF8FAFC),
        borderRadius: BorderRadius.circular(12),
        border: Border.all(
          color: value ? successColor.withValues(alpha: 0.3) : Colors.grey[200]!,
        ),
      ),
      child: CheckboxListTile(
        value: value,
        onChanged: onChanged,
        title: Text(label,
          style: TextStyle(
            fontFamily: 'Poppins', fontSize: 13, fontWeight: FontWeight.w500,
            color: value ? successColor : textPrimary,
          )),
        secondary: Icon(icon, color: value ? successColor : Colors.grey[400], size: 22),
        activeColor: successColor,
        checkboxShape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(4)),
        controlAffinity: ListTileControlAffinity.trailing,
        dense: true,
        contentPadding: const EdgeInsets.symmetric(horizontal: 12, vertical: 2),
      ),
    );
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

    String status;
    if (start) {
      status = 'berjalan';
    } else {
      status = filtered.isNotEmpty ? filtered.last.status : 'tidak';
    }
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        const Text(
          'Aksi Lainnya',
          style: TextStyle(
            fontFamily: 'Poppins',
            fontSize: 14.5,
            fontWeight: FontWeight.w700,
            color: Color(0xFF0F172A),
          ),
        ),
        const SizedBox(height: 10),
        Wrap(
          spacing: 10,
          runSpacing: 10,
          children: [
            if (status == 'menunggu laporan' && selisih < 2 && _isKetua)
              _buildPremiumActionCard(
                title: 'Kirim Laporan',
                subtitle: 'Lengkapi dan kirim laporan kegiatan',
                icon: Icons.assignment_turned_in_rounded,
                color: successColor,
                onTap: () {
                  final anggotaIds = data.dataTeknisi
                      .where((t) => t.teknisiId != _idTeknisi)
                      .map((t) => t.teknisiId)
                      .toList();
                  Navigator.pushNamed(
                    context,
                    ReportDonePage.routeName,
                    arguments: [int.parse(id), data.id, anggotaIds],
                  );
                },
              ),
            if (status == 'menunggu laporan' && selisih < 2 && !_isKetua)
              _buildPremiumActionCard(
                title: 'Menunggu Ketua',
                subtitle: 'Ketua Tim akan menyelesaikan laporan',
                icon: Icons.hourglass_top_rounded,
                color: secondaryColor,
                onTap: () {
                  QuickAlert.show(
                    context: context,
                    type: QuickAlertType.info,
                    title: 'Hanya Ketua Tim',
                    text: 'Hanya Ketua Tim yang dapat mengirim laporan. Status Anda akan otomatis terupdate setelah Ketua menyelesaikan laporan.',
                  );
                },
              ),
            if (status == 'menunggu laporan' ||
                status == 'selesai' ||
                history ||
                data.status.toLowerCase() == 'menunggu laporan' ||
                data.status.toLowerCase() == 'selesai')
              _buildPremiumActionCard(
                title: _hasBast
                    ? 'Edit Dokumen BAST'
                    : (_wajibBast ? 'Input BAST (Wajib)' : 'Input BAST'),
                subtitle: _hasBast
                    ? 'Tersimpan ($_bastTitikCount titik kamera) • Tap untuk edit'
                    : 'Foto display CCTV & titik kamera (bisa diisi di rumah)',
                icon: Icons.fact_check_rounded,
                color: _hasBast ? successColor : const Color(0xFFFF5B22),
                onTap: () async {
                  final saved = await Navigator.push<bool>(
                    context,
                    MaterialPageRoute(
                      builder: (_) => InputBastPage(
                        kode: data.kode,
                        kegiatanId: data.id,
                        customerId: data.customerId,
                        namaCustomer: data.dataCustomer.nama,
                        teknisiId: idTeknisi,
                      ),
                    ),
                  );
                  if (saved == true) {
                    _loadBastStatus();
                  }
                },
              ),
            if ((status == 'menunggu laporan' || status == 'selesai') &&
                selisih < 2)
              _buildPremiumActionCard(
                title: 'Claim Reimbursement',
                subtitle: 'Ajukan penggantian biaya operasional',
                icon: Icons.receipt_long_rounded,
                color: const Color(0xFF7C3AED),
                onTap: () {
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
              _buildPremiumActionCard(
                title: 'Batalkan Tugas',
                subtitle: 'Pelanggan tidak ada di lokasi? Batalkan tugas ini',
                icon: Icons.cancel_rounded,
                color: errorColor,
                onTap: () => _rescheduleOrCancel(false),
              ),
            if (history)
              _buildPremiumActionCard(
                title: 'Lihat Invoice',
                subtitle: 'Buka rincian invoice kegiatan',
                icon: Icons.receipt_outlined,
                color: successColor,
                onTap: () => Navigator.push(
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

  Widget _buildPremiumActionCard({
    required String title,
    required String subtitle,
    required IconData icon,
    required Color color,
    required VoidCallback onTap,
  }) {
    final isDestructive = color == errorColor;
    return SizedBox(
      width: double.infinity,
      child: Container(
        decoration: BoxDecoration(
          borderRadius: BorderRadius.circular(16),
          border: Border.all(
            color: isDestructive
                ? errorColor.withValues(alpha: 0.28)
                : const Color(0xFFE2E8F0),
          ),
          color: isDestructive ? const Color(0xFFFEF2F2) : const Color(0xFFF8FAFC),
        ),
        child: Material(
          color: Colors.transparent,
          child: InkWell(
            borderRadius: BorderRadius.circular(16),
            onTap: onTap,
            child: Padding(
              padding: const EdgeInsets.symmetric(vertical: 12, horizontal: 14),
              child: Row(
                children: [
                  Container(
                    width: 38,
                    height: 38,
                    decoration: BoxDecoration(
                      color: color.withValues(alpha: 0.12),
                      borderRadius: BorderRadius.circular(11),
                    ),
                    child: Center(child: Icon(icon, color: color, size: 20)),
                  ),
                  const SizedBox(width: 12),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          title,
                          style: TextStyle(
                            fontFamily: 'Poppins',
                            fontSize: 13.5,
                            fontWeight: FontWeight.w700,
                            color: isDestructive ? errorColor : const Color(0xFF0F172A),
                          ),
                        ),
                        const SizedBox(height: 1),
                        Text(
                          subtitle,
                          style: TextStyle(
                            fontFamily: 'Poppins',
                            fontSize: 11,
                            color: isDestructive
                                ? errorColor.withValues(alpha: 0.78)
                                : const Color(0xFF64748B),
                          ),
                        ),
                      ],
                    ),
                  ),
                  Container(
                    padding: const EdgeInsets.all(6),
                    decoration: BoxDecoration(
                      color: isDestructive
                          ? errorColor.withValues(alpha: 0.1)
                          : Colors.white,
                      shape: BoxShape.circle,
                      border: isDestructive
                          ? null
                          : Border.all(color: const Color(0xFFE2E8F0)),
                    ),
                    child: Icon(
                      Icons.arrow_forward_ios_rounded,
                      color: isDestructive ? errorColor : const Color(0xFF64748B),
                      size: 12,
                    ),
                  ),
                ],
              ),
            ),
          ),
        ),
      ),
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

  Future<void> _makePhoneCall(String? phoneNumber) async {
    if (phoneNumber == null || phoneNumber.trim().isEmpty) {
      if (!mounted) return;
      QuickAlert.show(
        context: context,
        type: QuickAlertType.warning,
        title: 'Nomor Tidak Tersedia',
        text: 'Nomor telepon pelanggan belum terdaftar',
      );
      return;
    }
    final clean = phoneNumber.replaceAll(RegExp(r'[^0-9+]'), '');
    final uri = Uri.parse('tel:$clean');
    if (await canLaunchUrl(uri)) {
      await launchUrl(uri);
    }
  }

  Future<void> _openWhatsAppChat(String? phoneNumber) async {
    if (phoneNumber == null || phoneNumber.trim().isEmpty) {
      if (!mounted) return;
      QuickAlert.show(
        context: context,
        type: QuickAlertType.warning,
        title: 'Nomor Tidak Tersedia',
        text: 'Nomor telepon pelanggan belum terdaftar',
      );
      return;
    }
    var clean = phoneNumber.replaceAll(RegExp(r'[^0-9]'), '');
    if (clean.startsWith('0')) {
      clean = '62${clean.substring(1)}';
    } else if (!clean.startsWith('62')) {
      clean = '62$clean';
    }
    final message = 'Halo ${data.dataCustomer.nama}, saya teknisi PT Loewix yang bertugas untuk kegiatan ${data.kegiatan} (${data.kode}). Saya sedang menuju lokasi Anda.';
    final appUrl = Uri.parse('whatsapp://send?phone=$clean&text=${Uri.encodeComponent(message)}');
    final webUrl = Uri.parse('https://wa.me/$clean?text=${Uri.encodeComponent(message)}');

    if (await canLaunchUrl(appUrl)) {
      await launchUrl(appUrl, mode: LaunchMode.externalApplication);
    } else if (await canLaunchUrl(webUrl)) {
      await launchUrl(webUrl, mode: LaunchMode.externalApplication);
    }
  }

  Widget _buildCustomAppBar() {
    final topPadding = MediaQuery.of(context).padding.top;
    return Container(
      margin: EdgeInsets.fromLTRB(16, topPadding + 10, 16, 0),
      padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 10),
      decoration: BoxDecoration(
        color: Colors.white.withValues(alpha: 0.96),
        borderRadius: BorderRadius.circular(20),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.10),
            blurRadius: 18,
            offset: const Offset(0, 4),
          ),
        ],
        border: Border.all(color: const Color(0xFFE2E8F0)),
      ),
      child: Row(
        children: [
          // Circular Frosted Back Button
          Material(
            color: const Color(0xFFF1F5F9),
            borderRadius: BorderRadius.circular(12),
            child: InkWell(
              onTap: () => Navigator.pop(context),
              borderRadius: BorderRadius.circular(12),
              child: const Padding(
                padding: EdgeInsets.all(9),
                child: Icon(Icons.arrow_back_ios_new_rounded, size: 16, color: Color(0xFF0F172A)),
              ),
            ),
          ),
          const SizedBox(width: 12),
          // Task Title & Code
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              mainAxisSize: MainAxisSize.min,
              children: [
                Row(
                  children: [
                    Text(
                      data.kode,
                      style: const TextStyle(
                        fontFamily: 'Poppins',
                        fontSize: 13,
                        fontWeight: FontWeight.w700,
                        color: Color(0xFF0F172A),
                      ),
                    ),
                    const SizedBox(width: 6),
                    Container(
                      padding: const EdgeInsets.symmetric(horizontal: 7, vertical: 2),
                      decoration: BoxDecoration(
                        color: const Color(0xFFFF5B22).withValues(alpha: 0.12),
                        borderRadius: BorderRadius.circular(6),
                      ),
                      child: Text(
                        data.kegiatan,
                        style: const TextStyle(
                          fontFamily: 'Poppins',
                          fontSize: 9.5,
                          fontWeight: FontWeight.w700,
                          color: Color(0xFFFF5B22),
                        ),
                      ),
                    ),
                  ],
                ),
                const SizedBox(height: 1),
                Text(
                  data.dataCustomer.nama,
                  style: const TextStyle(
                    fontFamily: 'Poppins',
                    fontSize: 11,
                    color: Color(0xFF64748B),
                    fontWeight: FontWeight.w500,
                  ),
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis,
                ),
              ],
            ),
          ),
          // Refresh Data Button
          Material(
            color: const Color(0xFFF1F5F9),
            borderRadius: BorderRadius.circular(12),
            child: InkWell(
              onTap: () async {
                ScaffoldMessenger.of(context).showSnackBar(
                  const SnackBar(
                    content: Text('Memperbarui data kegiatan...'),
                    duration: Duration(milliseconds: 700),
                  ),
                );
                await _refreshFromApi();
              },
              borderRadius: BorderRadius.circular(12),
              child: const Padding(
                padding: EdgeInsets.all(9),
                child: Icon(Icons.refresh_rounded, size: 18, color: Color(0xFF0F172A)),
              ),
            ),
          ),
          const SizedBox(width: 8),
          // Google Maps Nav Shortcut Button
          Material(
            color: const Color(0xFF0284C7).withValues(alpha: 0.12),
            borderRadius: BorderRadius.circular(12),
            child: InkWell(
              onTap: _openGoogleMapsNavigation,
              borderRadius: BorderRadius.circular(12),
              child: const Padding(
                padding: EdgeInsets.all(9),
                child: Icon(Icons.navigation_rounded, size: 18, color: Color(0xFF0284C7)),
              ),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildQuickCircleButton({
    required IconData icon,
    required Color color,
    required String tooltip,
    required VoidCallback onTap,
  }) {
    return Tooltip(
      message: tooltip,
      child: Material(
        color: color.withValues(alpha: 0.12),
        shape: const CircleBorder(),
        child: InkWell(
          onTap: onTap,
          customBorder: const CircleBorder(),
          child: Padding(
            padding: const EdgeInsets.all(9),
            child: Icon(icon, color: color, size: 18),
          ),
        ),
      ),
    );
  }

  Widget _buildProgressSteps() {
    final status = data.status.toLowerCase();
    final pelStatus = _pelaksanaanStatus.toLowerCase();
    int currentStep = 1; // 1: Menuju, 2: Tiba/ClockIn, 3: Pengerjaan, 4: Selesai
    if (status == 'selesai' || pelStatus == 'selesai' || pelStatus == 'menunggu laporan') {
      currentStep = 4;
    } else if (start || pelStatus == 'berjalan') {
      currentStep = 3;
    }

    final steps = [
      {'title': 'Menuju Lokasi', 'icon': Icons.two_wheeler_rounded},
      {'title': 'Tiba (Clock In)', 'icon': Icons.pin_drop_rounded},
      {'title': 'Pengerjaan', 'icon': Icons.build_rounded},
      {'title': 'Selesai', 'icon': Icons.check_circle_rounded},
    ];

    return Row(
      children: List.generate(steps.length, (index) {
        final stepIndex = index + 1;
        final isActive = stepIndex <= currentStep;
        final isCurrent = stepIndex == currentStep;

        return Expanded(
          child: Row(
            children: [
              Expanded(
                child: Column(
                  children: [
                    Container(
                      width: 28,
                      height: 28,
                      decoration: BoxDecoration(
                        color: isCurrent
                            ? const Color(0xFFFF5B22)
                            : (isActive ? const Color(0xFF10B981) : const Color(0xFFE2E8F0)),
                        shape: BoxShape.circle,
                        boxShadow: isCurrent
                            ? [
                                BoxShadow(
                                  color: const Color(0xFFFF5B22).withValues(alpha: 0.35),
                                  blurRadius: 8,
                                  offset: const Offset(0, 2),
                                ),
                              ]
                            : null,
                      ),
                      child: Center(
                        child: Icon(
                          steps[index]['icon'] as IconData,
                          size: 14,
                          color: (isActive || isCurrent) ? Colors.white : const Color(0xFF94A3B8),
                        ),
                      ),
                    ),
                    const SizedBox(height: 4),
                    Text(
                      steps[index]['title'] as String,
                      style: TextStyle(
                        fontFamily: 'Poppins',
                        fontSize: 9,
                        fontWeight: isCurrent ? FontWeight.w700 : FontWeight.w500,
                        color: isCurrent ? const Color(0xFFFF5B22) : const Color(0xFF64748B),
                      ),
                      textAlign: TextAlign.center,
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                    ),
                  ],
                ),
              ),
              if (index < steps.length - 1)
                Container(
                  width: 12,
                  height: 2,
                  margin: const EdgeInsets.only(bottom: 16),
                  color: stepIndex < currentStep ? const Color(0xFF10B981) : const Color(0xFFE2E8F0),
                ),
            ],
          ),
        );
      }),
    );
  }

  Widget _buildBottomPanel() {
    final bottomPadding = MediaQuery.of(context).padding.bottom;
    final pullLift = (-_panelDragDy * 0.3).clamp(0.0, 18.0);
    return GestureDetector(
      behavior: HitTestBehavior.opaque,
      onVerticalDragStart: (_) {
        _panelDragDy = 0.0;
      },
      onVerticalDragUpdate: (details) {
        if (_isBottomSheetOpen) return;
        final nextDy = (_panelDragDy + (details.primaryDelta ?? 0.0)).clamp(-80.0, 0.0);
        if (nextDy <= -36.0) {
          _openSheetFromSwipe();
          return;
        }
        if ((nextDy - _panelDragDy).abs() > 0.5) {
          setState(() => _panelDragDy = nextDy);
        }
      },
      onVerticalDragEnd: (details) {
        final vy = details.primaryVelocity ?? 0.0;
        if (!_isBottomSheetOpen && (vy < -220.0 || _panelDragDy <= -20.0)) {
          _openSheetFromSwipe();
          return;
        }
        if (_panelDragDy != 0.0) {
          setState(() => _panelDragDy = 0.0);
        }
      },
      onVerticalDragCancel: () {
        if (_panelDragDy != 0.0) {
          setState(() => _panelDragDy = 0.0);
        }
      },
      child: Container(
        decoration: BoxDecoration(
          color: Colors.white,
          borderRadius: const BorderRadius.vertical(top: Radius.circular(28)),
          boxShadow: [
            BoxShadow(
              color: Colors.black.withValues(alpha: 0.12),
              blurRadius: 24,
              offset: const Offset(0, -6),
            ),
          ],
        ),
        child: SafeArea(
          top: false,
          child: Padding(
            padding: EdgeInsets.fromLTRB(
              20,
              6,
              20,
              16 + (bottomPadding > 0 ? 0 : 8) + pullLift,
            ),
            child: Column(
              mainAxisSize: MainAxisSize.min,
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                // Grab Handle — tarik ke atas atau ketuk untuk membuka Detail Tugas
                GestureDetector(
                  behavior: HitTestBehavior.opaque,
                  onTap: _openSheetFromSwipe,
                  child: SizedBox(
                    width: double.infinity,
                    child: Padding(
                      padding: const EdgeInsets.only(top: 4, bottom: 12),
                      child: Center(
                        child: Container(
                          width: 38,
                          height: 4,
                          decoration: BoxDecoration(
                            color: const Color(0xFFCBD5E1),
                            borderRadius: BorderRadius.circular(2),
                          ),
                        ),
                      ),
                    ),
                  ),
                ),

              // Live Status Header Row
              Row(
                key: _keyInfoChips,
                children: [
                  Container(
                    width: 9,
                    height: 9,
                    decoration: const BoxDecoration(
                      color: Color(0xFF10B981),
                      shape: BoxShape.circle,
                    ),
                  ),
                  const SizedBox(width: 8),
                  Text(
                    data.status.toLowerCase() == 'berjalan'
                        ? 'Menuju Lokasi Pelanggan'
                        : (data.status.toLowerCase() == 'selesai' ? 'Tugas Selesai' : 'Jadwal Siap'),
                    style: const TextStyle(
                      fontFamily: 'Poppins',
                      fontSize: 13.5,
                      fontWeight: FontWeight.w700,
                      color: Color(0xFF0F172A),
                    ),
                  ),
                  const Spacer(),
                  // Schedule Time Badge
                  Container(
                    padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                    decoration: BoxDecoration(
                      color: const Color(0xFFF1F5F9),
                      borderRadius: BorderRadius.circular(10),
                    ),
                    child: Row(
                      mainAxisSize: MainAxisSize.min,
                      children: [
                        const Icon(Icons.access_time_rounded, size: 13, color: Color(0xFF64748B)),
                        const SizedBox(width: 5),
                        Text(
                          '${data.jadwal.hour.toString().padLeft(2, '0')}:${data.jadwal.minute.toString().padLeft(2, '0')} WIB',
                          style: const TextStyle(
                            fontFamily: 'Poppins',
                            fontSize: 11.5,
                            fontWeight: FontWeight.w600,
                            color: Color(0xFF0F172A),
                          ),
                        ),
                      ],
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 12),

              // Customer Quick Card (Sekelas Gojek / Shopee)
              Container(
                padding: const EdgeInsets.all(12),
                decoration: BoxDecoration(
                  color: const Color(0xFFF8FAFC),
                  borderRadius: BorderRadius.circular(16),
                  border: Border.all(color: const Color(0xFFE2E8F0)),
                ),
                child: Row(
                  children: [
                    // Company / Customer Avatar
                    Container(
                      width: 44,
                      height: 44,
                      decoration: BoxDecoration(
                        gradient: const LinearGradient(
                          colors: [Color(0xFFFF5B22), Color(0xFFFF8A00)],
                          begin: Alignment.topLeft,
                          end: Alignment.bottomRight,
                        ),
                        borderRadius: BorderRadius.circular(12),
                        boxShadow: [
                          BoxShadow(
                            color: const Color(0xFFFF5B22).withValues(alpha: 0.3),
                            blurRadius: 8,
                            offset: const Offset(0, 3),
                          ),
                        ],
                      ),
                      child: const Center(
                        child: Icon(Icons.business_rounded, color: Colors.white, size: 22),
                      ),
                    ),
                    const SizedBox(width: 12),
                    // Customer Details
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(
                            data.dataCustomer.nama,
                            style: const TextStyle(
                              fontFamily: 'Poppins',
                              fontSize: 13.5,
                              fontWeight: FontWeight.w700,
                              color: Color(0xFF0F172A),
                            ),
                            maxLines: 1,
                            overflow: TextOverflow.ellipsis,
                          ),
                          const SizedBox(height: 2),
                          Row(
                            children: [
                              const Icon(Icons.location_on_outlined, size: 12, color: Color(0xFF64748B)),
                              const SizedBox(width: 3),
                              Expanded(
                                child: Text(
                                  data.dataCustomer.alamat.isNotEmpty ? data.dataCustomer.alamat : 'Alamat sesuai jadwal',
                                  style: const TextStyle(
                                    fontFamily: 'Poppins',
                                    fontSize: 11,
                                    color: Color(0xFF64748B),
                                  ),
                                  maxLines: 1,
                                  overflow: TextOverflow.ellipsis,
                                ),
                              ),
                            ],
                          ),
                        ],
                      ),
                    ),
                    const SizedBox(width: 8),
                    // Quick Action Buttons (Call, WhatsApp)
                    Row(
                      mainAxisSize: MainAxisSize.min,
                      children: [
                        // WhatsApp Button
                        _buildQuickCircleButton(
                          icon: Icons.chat_bubble_rounded,
                          color: const Color(0xFF25D366),
                          tooltip: 'Chat WhatsApp',
                          onTap: () => _openWhatsAppChat(data.dataCustomer.telp),
                        ),
                        const SizedBox(width: 8),
                        // Call Button
                        _buildQuickCircleButton(
                          icon: Icons.phone_rounded,
                          color: const Color(0xFF0284C7),
                          tooltip: 'Telepon',
                          onTap: () => _makePhoneCall(data.dataCustomer.telp),
                        ),
                      ],
                    ),
                  ],
                ),
              ),

              const SizedBox(height: 14),

              // Gojek 4-Step Progress Indicator
              _buildProgressSteps(),

              const SizedBox(height: 14),

              // Main CTA Action Button (Sekelas Gojek / Shopee)
              Container(
                key: _keyActionButton,
                width: double.infinity,
                height: 50,
                decoration: BoxDecoration(
                  gradient: const LinearGradient(
                    colors: [Color(0xFFFF5B22), Color(0xFFFF7A00)],
                    begin: Alignment.centerLeft,
                    end: Alignment.centerRight,
                  ),
                  borderRadius: BorderRadius.circular(16),
                  boxShadow: [
                    BoxShadow(
                      color: const Color(0xFFFF5B22).withValues(alpha: 0.35),
                      blurRadius: 14,
                      offset: const Offset(0, 4),
                    ),
                  ],
                ),
                child: Material(
                  color: Colors.transparent,
                  child: InkWell(
                    onTap: _showBottomSheetActions,
                    borderRadius: BorderRadius.circular(16),
                    child: Padding(
                      padding: const EdgeInsets.symmetric(horizontal: 18),
                      child: Row(
                        mainAxisAlignment: MainAxisAlignment.spaceBetween,
                        children: [
                          Row(
                            children: [
                              Container(
                                padding: const EdgeInsets.all(6),
                                decoration: BoxDecoration(
                                  color: Colors.white.withValues(alpha: 0.2),
                                  shape: BoxShape.circle,
                                ),
                                child: const Icon(Icons.touch_app_rounded, color: Colors.white, size: 16),
                              ),
                              const SizedBox(width: 10),
                              const Text(
                                'Buka Detail & Aksi Tugas',
                                style: TextStyle(
                                  fontFamily: 'Poppins',
                                  fontSize: 14,
                                  fontWeight: FontWeight.w700,
                                  color: Colors.white,
                                ),
                              ),
                            ],
                          ),
                          const Icon(Icons.arrow_forward_ios_rounded, color: Colors.white, size: 15),
                        ],
                      ),
                    ),
                  ),
                ),
              ),
            ],
          ),
        ),
      ),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      key: ValueKey('task_page_${data.id}_${data.kode}'),
      backgroundColor: Colors.white,
      body: Stack(
        children: [
          // 1. Full-screen Map as the canvas
          Positioned.fill(
            key: _keyMap,
            child: GoogleMapSample(
              key: ValueKey('map_${data.id}_${data.lat}_${data.lon}_${data.dataCustomer.alamat}'),
              taskData: data,
            ),
          ),
          // 2. Floating Top Header (Gojek / Shopee Style)
          Positioned(
            top: 0,
            left: 0,
            right: 0,
            child: _buildCustomAppBar(),
          ),
          // 3. Persistent Floating Bottom Card (Gojek / Shopee Style)
          Positioned(
            bottom: 0,
            left: 0,
            right: 0,
            child: _buildBottomPanel(),
          ),
        ],
      ),
    );
  }

  // ─── Coach Mark for TaskPage ─────────────────────
  Future<void> _showTaskCoachMark() async {
    await Future.delayed(const Duration(milliseconds: 1500));
    if (!mounted || _coachMarkShown || CoachMarkHelper.isActive) return;
    _coachMarkShown = true;

    final targets = <TargetFocus>[
      TargetFocus(
        identify: 'info_chips',
        keyTarget: _keyInfoChips,
        alignSkip: Alignment.bottomRight,
        enableOverlayTab: true,
        shape: ShapeLightFocus.RRect,
        radius: 16,
        contents: [
          TargetContent(
            align: ContentAlign.top,
            builder: (context, controller) => CoachMarkHelper.buildTooltip(
              onClose: () => controller.skip(),
              title: 'Jadwal Kunjungan',
              descriptions: ['Tanggal & jam kunjungan yang ditentukan admin'],
              step: '1/2',
            ),
          ),
        ],
      ),
      TargetFocus(
        identify: 'action_button',
        keyTarget: _keyActionButton,
        alignSkip: Alignment.bottomRight,
        enableOverlayTab: true,
        shape: ShapeLightFocus.RRect,
        radius: 14,
        contents: [
          TargetContent(
            align: ContentAlign.top,
            builder: (context, controller) => CoachMarkHelper.buildTooltip(
              onClose: () => controller.skip(),
              title: 'Detail & Aksi',
              descriptions: ['Tap untuk Clock In, kirim laporan, atau claim reimbursement'],
              step: '2/2',
            ),
          ),
        ],
      ),
    ];
    CoachMarkHelper.show(context: context, targets: targets, key: 'task_detail');
  }

  // ─── Coach Mark for Bottom Sheet ─────────────────
  void _showSheetCoachMark(BuildContext sheetCtx) {
    final targets = <TargetFocus>[
      TargetFocus(
        identify: 'sheet_status',
        keyTarget: _keySheetStatus,
        alignSkip: Alignment.bottomRight,
        enableOverlayTab: true,
        shape: ShapeLightFocus.RRect,
        radius: 16,
        contents: [
          TargetContent(
            align: ContentAlign.bottom,
            builder: (context, controller) => CoachMarkHelper.buildTooltip(
              onClose: () => controller.skip(),
              title: 'Status Tugas',
              descriptions: ['Menunjukkan status terkini tugas ini'],
              arrowUp: true,
              step: '1/3',
            ),
          ),
        ],
      ),
      if (_shouldShowSlider())
        TargetFocus(
          identify: 'sheet_slider',
          keyTarget: _keySheetSlider,
          alignSkip: Alignment.bottomRight,
          enableOverlayTab: true,
          shape: ShapeLightFocus.RRect,
          radius: 16,
          contents: [
            TargetContent(
              align: ContentAlign.top,
              builder: (context, controller) => CoachMarkHelper.buildTooltip(
              onClose: () => controller.skip(),
                title: 'Geser untuk Mulai',
                descriptions: ['Geser slider ke kanan untuk clock in / clock out'],
                step: '2/3',
              ),
            ),
          ],
        ),
      TargetFocus(
        identify: 'sheet_actions',
        keyTarget: _keySheetActions,
        alignSkip: Alignment.topRight,
        enableOverlayTab: true,
        shape: ShapeLightFocus.RRect,
        radius: 16,
        contents: [
          TargetContent(
            align: ContentAlign.top,
            builder: (context, controller) => CoachMarkHelper.buildTooltip(
              onClose: () => controller.skip(),
              title: 'Aksi Lainnya',
              descriptions: ['Kirim laporan, claim reimbursement, atau batalkan tugas'],
              step: '3/3',
            ),
          ),
        ],
      ),
    ];
    CoachMarkHelper.show(context: sheetCtx, targets: targets, key: 'task_sheet');
  }

}
