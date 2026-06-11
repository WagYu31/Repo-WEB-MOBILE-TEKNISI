import 'package:flutter/material.dart';
import 'package:geolocator/geolocator.dart';
import 'package:location/location.dart' as a;
import 'package:permission_handler/permission_handler.dart';
import 'package:provider/provider.dart';
import 'package:quickalert/quickalert.dart';

import '../page/History/HistoryDetailPage.dart';
import '../page/Task/TaskPage.dart';
import '../service/model/task/TaskAllResponse.dart';
import '../service/provider/preferences/PreferencesIDProvider.dart';

class CardTask extends StatefulWidget {
  final DataTask data;
  final bool history;
  final GlobalKey? dragHandleKey;

  const CardTask({super.key, required this.data, required this.history, this.dragHandleKey});

  @override
  State<CardTask> createState() => _CardTaskState();
}

class _CardTaskState extends State<CardTask> with SingleTickerProviderStateMixin {
  int? _teknisiId;
  late AnimationController _animationController;
  late Animation<double> _scaleAnimation;

  static const List<String> _days = [
    'Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'
  ];

  static const List<String> _months = [
    'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun',
    'Jul', 'Ags', 'Sep', 'Okt', 'Nov', 'Des'
  ];

  // ─── Premium Color Palette ─────────────────────
  static const Color _primaryBlue = Color(0xFF0EA5E9);
  static const Color _deepBlue = Color(0xFF1E40AF);
  static const Color _successGreen = Color(0xFF14B8A6);
  static const Color _warningAmber = Color(0xFFF97316);
  static const Color _errorRed = Color(0xFFF43F5E);
  static const Color _textPrimary = Color(0xFF0F172A);
  static const Color _textSecondary = Color(0xFF64748B);
  static const Color _cardBg = Colors.white;

  String _statusLabel = '';
  Color _statusColor = Colors.grey;
  Color _statusBgColor = Colors.grey;
  IconData _statusIcon = Icons.schedule;
  String _pelaksanaanStatus = 'Dijadwalkan';

  @override
  void initState() {
    super.initState();
    _animationController = AnimationController(
      duration: const Duration(milliseconds: 150),
      vsync: this,
    );
    _scaleAnimation = Tween<double>(begin: 1.0, end: 0.97).animate(
      CurvedAnimation(parent: _animationController, curve: Curves.easeInOut),
    );
    _computeStatus();

    WidgetsBinding.instance.addPostFrameCallback((_) {
      _teknisiId = int.tryParse(
        Provider.of<PreferencesIDProvider>(context, listen: false).isUserRole,
      );
      _computeStatus();
      if (mounted) setState(() {});
    });
  }

  @override
  void dispose() {
    _animationController.dispose();
    super.dispose();
  }

  @override
  void didUpdateWidget(CardTask oldWidget) {
    super.didUpdateWidget(oldWidget);
    // Re-compute status when parent rebuilds with fresh data
    if (oldWidget.data.id != widget.data.id ||
        oldWidget.data.pelaksanaan.length != widget.data.pelaksanaan.length ||
        oldWidget.data.status != widget.data.status) {
      _computeStatus();
      if (mounted) setState(() {});
    }
  }

  void _computeStatus() {
    final data = widget.data;
    final now = DateTime.now();
    final isToday = data.jadwal.year == now.year &&
        data.jadwal.month == now.month &&
        data.jadwal.day == now.day;

    final pelaksanaanList = _teknisiId != null
        ? data.pelaksanaan.where((e) => e.teknisiId == _teknisiId).toList()
        : <Pelaksanaan>[];

    _pelaksanaanStatus = pelaksanaanList.isNotEmpty
        ? pelaksanaanList.first.status
        : 'Dijadwalkan';

    final isSelesai = pelaksanaanList.any((e) => e.status == 'selesai');

    // Override: jika task di-reschedule (dijadwalkan), abaikan pelaksanaan lama
    final bool isReschedule = data.status.toLowerCase() == 'dijadwalkan' && isSelesai;
    if (isReschedule) {
      _pelaksanaanStatus = 'Dijadwalkan';
    }

    // Cek apakah user ini adalah Ketua
    final myTeknisiData = data.dataTeknisi.where((t) => t.teknisiId == _teknisiId);
    final bool isKetua = data.dataTeknisi.length == 1 || 
        (myTeknisiData.isNotEmpty && myTeknisiData.first.isKetua == 1);

    // Cek apakah selesai tapi belum upload laporan (hanya untuk Ketua, BUKAN reschedule)
    final bool isNeedReport = !isReschedule && isKetua && isSelesai && pelaksanaanList.isNotEmpty &&
        (pelaksanaanList.first.permasalahan == null ||
         pelaksanaanList.first.permasalahan.toString().trim().isEmpty) &&
        pelaksanaanList.first.image1 == null;

    if (isNeedReport) {
      _statusLabel = 'Perlu Laporan';
      _statusColor = _warningAmber;
      _statusBgColor = const Color(0xFFFFF7ED);
      _statusIcon = Icons.edit_document;
    } else if (isToday && data.status != 'dibatalkan') {
      _statusLabel = 'Hari Ini';
      _statusColor = _warningAmber;
      _statusBgColor = const Color(0xFFFFF7ED);
      _statusIcon = Icons.wb_sunny_rounded;
    } else if (data.jadwal.isAfter(now) &&
        data.status != 'dibatalkan' &&
        data.status != 'lanjut nanti' &&
        data.status != 'selesai') {
      _statusLabel = 'Terjadwal';
      _statusColor = _primaryBlue;
      _statusBgColor = const Color(0xFFF0F9FF);
      _statusIcon = Icons.event_rounded;
    } else if (data.status == 'Lanjut Nanti') {
      _statusLabel = 'Ditunda';
      _statusColor = _textSecondary;
      _statusBgColor = const Color(0xFFF8FAFC);
      _statusIcon = Icons.pause_circle_rounded;
    } else if (data.status == 'selesai' && isSelesai) {
      _statusLabel = 'Selesai';
      _statusColor = _successGreen;
      _statusBgColor = const Color(0xFFF0FDFA);
      _statusIcon = Icons.check_circle_rounded;
    } else if (data.status == 'dibatalkan') {
      _statusLabel = 'Dibatalkan';
      _statusColor = _errorRed;
      _statusBgColor = const Color(0xFFFFF1F2);
      _statusIcon = Icons.cancel_rounded;
    } else if (isSelesai) {
      _statusLabel = _pelaksanaanStatus;
      _statusColor = _successGreen;
      _statusBgColor = const Color(0xFFF0FDFA);
      _statusIcon = Icons.check_circle_outline_rounded;
    } else {
      _statusLabel = 'Terlambat';
      _statusColor = _errorRed;
      _statusBgColor = const Color(0xFFFFF1F2);
      _statusIcon = Icons.warning_amber_rounded;
    }
  }

  // ─── Activity Type Color Mapping ───────────────
  Color _getActivityColor(String kegiatan) {
    switch (kegiatan.toLowerCase()) {
      case 'survey':
        return const Color(0xFF6366F1); // indigo
      case 'service':
        return const Color(0xFF0D9488); // teal
      case 'pasang baru':
        return const Color(0xFFD97706); // amber
      default:
        return _deepBlue;
    }
  }

  Color _getActivityBg(String kegiatan) {
    switch (kegiatan.toLowerCase()) {
      case 'survey':
        return const Color(0xFFEEF2FF);
      case 'service':
        return const Color(0xFFF0FDFA);
      case 'pasang baru':
        return const Color(0xFFFFFBEB);
      default:
        return const Color(0xFFF0F9FF);
    }
  }

  IconData _getActivityIcon(String kegiatan) {
    switch (kegiatan.toLowerCase()) {
      case 'survey':
        return Icons.search_rounded;
      case 'service':
        return Icons.build_rounded;
      case 'pasang baru':
        return Icons.add_circle_rounded;
      default:
        return Icons.work_rounded;
    }
  }

  // ─── Accent Gradient Based on Status ───────────
  List<Color> _getAccentGradient() {
    if (_statusLabel == 'Hari Ini') {
      return [const Color(0xFFF97316), const Color(0xFFFBBF24)];
    } else if (_statusLabel == 'Terjadwal') {
      return [const Color(0xFF1E40AF), const Color(0xFF0EA5E9)];
    } else if (_statusLabel == 'Selesai' || _pelaksanaanStatus == 'selesai') {
      return [const Color(0xFF0D9488), const Color(0xFF14B8A6)];
    } else if (_statusLabel == 'Dibatalkan') {
      return [const Color(0xFFE11D48), const Color(0xFFF43F5E)];
    } else if (_statusLabel == 'Terlambat') {
      return [const Color(0xFFDC2626), const Color(0xFFF87171)];
    } else {
      return [const Color(0xFF64748B), const Color(0xFF94A3B8)];
    }
  }

  String _formatDate(DateTime date) {
    final dayName = _days[date.weekday % 7];
    final monthName = _months[date.month - 1];
    return '$dayName, ${date.day} $monthName ${date.year}';
  }

  String _formatTime(DateTime date) {
    return '${date.hour.toString().padLeft(2, '0')}:${date.minute.toString().padLeft(2, '0')}';
  }

  Future<bool> _checkLocationPermission() async {
    PermissionStatus status = await Permission.location.status;
    if (status.isGranted) return true;
    if (status.isDenied) {
      status = await Permission.location.request();
      return status.isGranted;
    }
    if (status.isPermanentlyDenied) {
      openAppSettings();
      return false;
    }
    return false;
  }

  Future<bool> _checkLocationService() async {
    final location = a.Location();
    bool serviceEnabled = await location.serviceEnabled();
    if (!serviceEnabled) {
      serviceEnabled = await location.requestService();
      if (!serviceEnabled && mounted) {
        QuickAlert.show(
          context: context,
          type: QuickAlertType.warning,
          title: 'Lokasi Diperlukan',
          text: 'Harap aktifkan lokasi/GPS anda',
        );
        return false;
      }
    }
    return true;
  }

  Future<void> _onCardTap() async {
    // Jika history, langsung ke halaman detail history tanpa cek lokasi
    if (widget.history) {
      Navigator.push(
        context,
        MaterialPageRoute(
          builder: (context) => HistoryDetailPage(data: widget.data),
          settings: RouteSettings(name: '${HistoryDetailPage.routeName}_${widget.data.id}'),
        ),
      );
      return;
    }

    // Untuk task aktif, cek lokasi terlebih dahulu
    showDialog(
      context: context,
      barrierDismissible: false,
      builder: (_) => Center(
        child: Container(
          padding: const EdgeInsets.all(24),
          decoration: BoxDecoration(
            color: Colors.white,
            borderRadius: BorderRadius.circular(16),
          ),
          child: const Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              CircularProgressIndicator(color: _primaryBlue),
              SizedBox(height: 16),
              Text('Memuat lokasi...', style: TextStyle(fontFamily: 'Poppins')),
            ],
          ),
        ),
      ),
    );

    try {
      final hasPermission = await _checkLocationPermission();
      if (!hasPermission) {
        if (!mounted) return;
        Navigator.pop(context);
        QuickAlert.show(
          context: context,
          type: QuickAlertType.warning,
          title: 'Izin Dibutuhkan!',
          text: 'Harap berikan izin lokasi sebelum melanjutkan',
        );
        return;
      }

      final hasService = await _checkLocationService();
      if (!hasService) {
        if (!mounted) return;
        Navigator.pop(context);
        return;
      }

      Position? position = await Geolocator.getLastKnownPosition();
      if (position == null) {
        try {
          position = await Geolocator.getCurrentPosition(
            desiredAccuracy: LocationAccuracy.high,
          ).timeout(const Duration(seconds: 10));
        } catch (_) {
          position = await Geolocator.getCurrentPosition(
            desiredAccuracy: LocationAccuracy.medium,
          ).timeout(const Duration(seconds: 8));
        }
      }

      if (position != null && position.isMocked) {
        if (!mounted) return;
        Navigator.pop(context);
        QuickAlert.show(
          context: context,
          type: QuickAlertType.error,
          title: 'Lokasi Palsu Terdeteksi!',
          text: 'Harap nonaktifkan aplikasi fake GPS',
        );
        return;
      }

      if (!mounted) return;
      Navigator.pop(context);

      Navigator.push(
        context,
        MaterialPageRoute(
          builder: (context) => TaskPage(dataa: [widget.data, widget.history]),
          settings: RouteSettings(name: '${TaskPage.routeName}_${widget.data.id}'),
        ),
      );
    } catch (e) {
      if (!mounted) return;
      Navigator.pop(context);
      QuickAlert.show(
        context: context,
        type: QuickAlertType.error,
        title: 'Error',
        text: 'Gagal mendapatkan lokasi. Coba lagi.',
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    final data = widget.data;
    final accentColors = _getAccentGradient();
    final activityColor = _getActivityColor(data.kegiatan);
    final activityBg = _getActivityBg(data.kegiatan);
    final activityIcon = _getActivityIcon(data.kegiatan);

    return GestureDetector(
      onTapDown: (_) => _animationController.forward(),
      onTapUp: (_) => _animationController.reverse(),
      onTapCancel: () => _animationController.reverse(),
      onTap: _onCardTap,
      child: AnimatedBuilder(
        animation: _scaleAnimation,
        builder: (context, child) => Transform.scale(
          scale: _scaleAnimation.value,
          child: child,
        ),
        child: Container(
          decoration: BoxDecoration(
            color: _cardBg,
            borderRadius: BorderRadius.circular(18),
            boxShadow: [
              BoxShadow(
                color: accentColors[0].withValues(alpha: 0.1),
                blurRadius: 20,
                offset: const Offset(0, 6),
              ),
              BoxShadow(
                color: Colors.black.withValues(alpha: 0.04),
                blurRadius: 8,
                offset: const Offset(0, 2),
              ),
            ],
          ),
          child: Column(
            children: [
              // ─── Top accent gradient bar ─────────────
              Container(
                height: 4,
                decoration: BoxDecoration(
                  gradient: LinearGradient(colors: accentColors),
                  borderRadius: const BorderRadius.vertical(
                    top: Radius.circular(18),
                  ),
                ),
              ),

              // ─── Card body ───────────────────────────
              Padding(
                padding: const EdgeInsets.fromLTRB(16, 14, 10, 14),
                child: Row(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    // ── Left content ──
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          // ── Row 1: Status + Activity ──
                          Row(
                            children: [
                              // Status badge
                              Container(
                                padding: const EdgeInsets.symmetric(
                                  horizontal: 10,
                                  vertical: 5,
                                ),
                                decoration: BoxDecoration(
                                  gradient: LinearGradient(
                                    colors: [
                                      _statusColor.withValues(alpha: 0.12),
                                      _statusColor.withValues(alpha: 0.06),
                                    ],
                                  ),
                                  borderRadius: BorderRadius.circular(20),
                                  border: Border.all(
                                    color: _statusColor.withValues(alpha: 0.2),
                                    width: 1,
                                  ),
                                ),
                                child: Row(
                                  mainAxisSize: MainAxisSize.min,
                                  children: [
                                    Icon(
                                      _statusIcon,
                                      size: 13,
                                      color: _statusColor,
                                    ),
                                    const SizedBox(width: 5),
                                    Text(
                                      _statusLabel,
                                      style: TextStyle(
                                        fontFamily: 'Poppins',
                                        fontSize: 11,
                                        fontWeight: FontWeight.w700,
                                        color: _statusColor,
                                      ),
                                    ),
                                  ],
                                ),
                              ),
                              const Spacer(),
                              // Activity type pill
                              Container(
                                padding: const EdgeInsets.symmetric(
                                  horizontal: 10,
                                  vertical: 5,
                                ),
                                decoration: BoxDecoration(
                                  color: activityBg,
                                  borderRadius: BorderRadius.circular(20),
                                ),
                                child: Row(
                                  mainAxisSize: MainAxisSize.min,
                                  children: [
                                    Icon(
                                      activityIcon,
                                      size: 12,
                                      color: activityColor,
                                    ),
                                    const SizedBox(width: 4),
                                    Text(
                                      data.kegiatan,
                                      style: TextStyle(
                                        fontFamily: 'Poppins',
                                        fontSize: 10,
                                        fontWeight: FontWeight.w600,
                                        color: activityColor,
                                      ),
                                    ),
                                  ],
                                ),
                              ),
                            ],
                          ),

                          const SizedBox(height: 12),

                          // ── Customer name ──
                          Text(
                            data.dataCustomer.nama,
                            style: const TextStyle(
                              fontFamily: 'Poppins',
                              fontSize: 15,
                              fontWeight: FontWeight.w700,
                              color: _textPrimary,
                              height: 1.3,
                              letterSpacing: -0.2,
                            ),
                            maxLines: 2,
                            overflow: TextOverflow.ellipsis,
                          ),

                          // ── Execution status (if active) ──
                          if (_pelaksanaanStatus != 'Dijadwalkan') ...[
                            const SizedBox(height: 6),
                            Container(
                              padding: const EdgeInsets.symmetric(
                                horizontal: 8,
                                vertical: 3,
                              ),
                              decoration: BoxDecoration(
                                color: _successGreen.withValues(alpha: 0.08),
                                borderRadius: BorderRadius.circular(6),
                              ),
                              child: Row(
                                mainAxisSize: MainAxisSize.min,
                                children: [
                                  Icon(
                                    _getStatusIconForPelaksanaan(
                                        _pelaksanaanStatus),
                                    size: 12,
                                    color: _successGreen,
                                  ),
                                  const SizedBox(width: 4),
                                  Text(
                                    _pelaksanaanStatus,
                                    style: TextStyle(
                                      fontFamily: 'Poppins',
                                      fontSize: 10,
                                      fontWeight: FontWeight.w500,
                                      color: _successGreen
                                          .withValues(alpha: 0.9),
                                    ),
                                  ),
                                ],
                              ),
                            ),
                          ],

                          const SizedBox(height: 12),

                          // ── Date & Time row ──
                          Container(
                            padding: const EdgeInsets.symmetric(
                              horizontal: 10,
                              vertical: 7,
                            ),
                            decoration: BoxDecoration(
                              color: const Color(0xFFF8FAFC),
                              borderRadius: BorderRadius.circular(10),
                            ),
                            child: Row(
                              mainAxisSize: MainAxisSize.min,
                              children: [
                                Icon(
                                  Icons.calendar_today_rounded,
                                  size: 13,
                                  color:
                                      _textSecondary.withValues(alpha: 0.6),
                                ),
                                const SizedBox(width: 6),
                                Text(
                                  _formatDate(data.jadwal),
                                  style: TextStyle(
                                    fontFamily: 'Poppins',
                                    fontSize: 11,
                                    fontWeight: FontWeight.w500,
                                    color: _textSecondary
                                        .withValues(alpha: 0.8),
                                  ),
                                ),
                                Container(
                                  margin: const EdgeInsets.symmetric(
                                      horizontal: 8),
                                  width: 1,
                                  height: 12,
                                  color: const Color(0xFFE2E8F0),
                                ),
                                Icon(
                                  Icons.access_time_rounded,
                                  size: 13,
                                  color:
                                      _textSecondary.withValues(alpha: 0.6),
                                ),
                                const SizedBox(width: 4),
                                Text(
                                  _formatTime(data.jadwal),
                                  style: TextStyle(
                                    fontFamily: 'Poppins',
                                    fontSize: 11,
                                    fontWeight: FontWeight.w600,
                                    color: _textSecondary
                                        .withValues(alpha: 0.8),
                                  ),
                                ),
                                if (data.paid != null) ...[
                                  const SizedBox(width: 8),
                                  Container(
                                    padding: const EdgeInsets.all(3),
                                    decoration: BoxDecoration(
                                      color: _successGreen
                                          .withValues(alpha: 0.1),
                                      borderRadius:
                                          BorderRadius.circular(5),
                                    ),
                                    child: Icon(
                                      Icons.receipt_long_rounded,
                                      size: 12,
                                      color: _successGreen
                                          .withValues(alpha: 0.7),
                                    ),
                                  ),
                                ],
                              ],
                            ),
                          ),
                        ],
                      ),
                    ),

                    // ── Right: drag handle + chevron ──
                    Column(
                      mainAxisAlignment: MainAxisAlignment.center,
                      children: [
                        if (!widget.history) ...[
                          Padding(
                            key: widget.dragHandleKey,
                            padding: const EdgeInsets.only(
                                left: 4, top: 4, bottom: 8),
                            child: Column(
                              children: [
                                for (int i = 0; i < 3; i++) ...[
                                  Row(
                                    mainAxisSize: MainAxisSize.min,
                                    children: [
                                      Container(
                                        width: 3,
                                        height: 3,
                                        decoration: BoxDecoration(
                                          color: _textSecondary
                                              .withValues(alpha: 0.2),
                                          shape: BoxShape.circle,
                                        ),
                                      ),
                                      const SizedBox(width: 3),
                                      Container(
                                        width: 3,
                                        height: 3,
                                        decoration: BoxDecoration(
                                          color: _textSecondary
                                              .withValues(alpha: 0.2),
                                          shape: BoxShape.circle,
                                        ),
                                      ),
                                    ],
                                  ),
                                  if (i < 2) const SizedBox(height: 3),
                                ],
                              ],
                            ),
                          ),
                        ],
                        Container(
                          padding: const EdgeInsets.all(6),
                          decoration: BoxDecoration(
                            color: accentColors[0].withValues(alpha: 0.08),
                            borderRadius: BorderRadius.circular(8),
                          ),
                          child: Icon(
                            Icons.chevron_right_rounded,
                            size: 18,
                            color: accentColors[0].withValues(alpha: 0.6),
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
      ),
    );
  }

  IconData _getStatusIconForPelaksanaan(String status) {
    switch (status.toLowerCase()) {
      case 'berjalan':
        return Icons.play_circle_outline;
      case 'selesai':
        return Icons.check_circle_outline;
      case 'menunggu laporan':
        return Icons.pending_outlined;
      default:
        return Icons.info_outline;
    }
  }
}
