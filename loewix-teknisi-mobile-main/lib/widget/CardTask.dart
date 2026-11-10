import 'package:flutter/material.dart';
import 'package:flutter/foundation.dart';
import 'package:geolocator/geolocator.dart';
import 'package:location/location.dart' as a;
import 'package:permission_handler/permission_handler.dart' hide ServiceStatus;
import 'package:provider/provider.dart';
import 'package:quickalert/quickalert.dart';

import '../page/History/HistoryDetailPage.dart';
import '../page/Task/TaskPage.dart';
import '../service/model/task/TaskAllResponse.dart';
import '../service/provider/preferences/PreferencesIDProvider.dart';
import '../service/api/ApiTask.dart';

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
  bool _isLoadingRequest = false;
  late AnimationController _animationController;
  late Animation<double> _scaleAnimation;

  static const List<String> _days = [
    'Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'
  ];

  static const List<String> _months = [
    'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun',
    'Jul', 'Ags', 'Sep', 'Okt', 'Nov', 'Des'
  ];

  // ─── Parclgo Inspired Color Palette ─────────────────────
  static const Color _primaryOrange = Color(0xFFFF5B22);
  static const Color _darkCharcoal = Color(0xFF11141A);
  static const Color _textPrimary = Color(0xFF0F172A);
  static const Color _textSecondary = Color(0xFF64748B);
  static const Color _cardBg = Colors.white;

  String _statusLabel = '';
  Color _statusColor = Colors.grey;
  IconData _statusIcon = Icons.schedule;
  String _pelaksanaanStatus = 'Dijadwalkan';

  @override
  void initState() {
    super.initState();
    _animationController = AnimationController(
      duration: const Duration(milliseconds: 150),
      vsync: this,
    );
    _scaleAnimation = Tween<double>(begin: 1.0, end: 0.98).animate(
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
      _statusColor = const Color(0xFFF97316);
      _statusIcon = Icons.edit_document;
    } else if (isToday && data.status != 'dibatalkan') {
      _statusLabel = 'Hari Ini';
      _statusColor = _primaryOrange;
      _statusIcon = Icons.wb_sunny_rounded;
    } else if (data.jadwal.isAfter(now) &&
        data.status != 'dibatalkan' &&
        data.status != 'lanjut nanti' &&
        data.status != 'selesai') {
      _statusLabel = 'Terjadwal';
      _statusColor = const Color(0xFF0284C7);
      _statusIcon = Icons.event_rounded;
    } else if (data.status == 'Lanjut Nanti') {
      _statusLabel = 'Ditunda';
      _statusColor = _textSecondary;
      _statusIcon = Icons.pause_circle_rounded;
    } else if (data.status == 'selesai' && isSelesai) {
      _statusLabel = 'Selesai';
      _statusColor = const Color(0xFF10B981);
      _statusIcon = Icons.check_circle_rounded;
    } else if (data.status == 'dibatalkan') {
      _statusLabel = 'Dibatalkan';
      _statusColor = const Color(0xFFEF4444);
      _statusIcon = Icons.cancel_rounded;
    } else if (isSelesai) {
      _statusLabel = _pelaksanaanStatus;
      _statusColor = const Color(0xFF10B981);
      _statusIcon = Icons.check_circle_outline_rounded;
    } else {
      _statusLabel = 'Terlambat';
      _statusColor = const Color(0xFFEF4444);
      _statusIcon = Icons.warning_amber_rounded;
    }
  }

  Future<void> _handleRequestInvoice() async {
    setState(() {
      _isLoadingRequest = true;
    });

    try {
      await ApiTask().requestInvoice(widget.data.kode);
      setState(() {
        widget.data.reqInvoiceAt = DateTime.now().toIso8601String();
      });
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(
            content: Text('Permintaan invoice berhasil dikirim ke Admin!'),
            backgroundColor: Color(0xFF10B981),
          ),
        );
      }
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text('Gagal mengirim permintaan: ${e.toString().replaceAll('Exception: ', '')}'),
            backgroundColor: const Color(0xFFEF4444),
          ),
        );
      }
    } finally {
      if (mounted) {
        setState(() {
          _isLoadingRequest = false;
        });
      }
    }
  }

  // ─── Activity Type Mapping ───────────────
  Color _getActivityColor(String kegiatan) {
    switch (kegiatan.toLowerCase()) {
      case 'survey':
        return const Color(0xFF6366F1);
      case 'service':
        return const Color(0xFF0D9488);
      case 'pasang baru':
        return const Color(0xFFFF5B22);
      default:
        return const Color(0xFF0284C7);
    }
  }

  Color _getActivityBg(String kegiatan) {
    switch (kegiatan.toLowerCase()) {
      case 'survey':
        return const Color(0xFFEEF2FF);
      case 'service':
        return const Color(0xFFF0FDFA);
      case 'pasang baru':
        return const Color(0xFFFFF7ED);
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
        return Icons.videocam_rounded;
      default:
        return Icons.work_rounded;
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
              CircularProgressIndicator(color: _primaryOrange),
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

      if (position != null && position.isMocked && !kDebugMode) {
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

  Widget _build3DBoxGraphic() {
    return Container(
      width: 44,
      height: 44,
      decoration: BoxDecoration(
        color: const Color(0xFFFFF7ED),
        borderRadius: BorderRadius.circular(12),
        border: Border.all(color: const Color(0xFFFFEDD5), width: 1),
      ),
      child: Stack(
        alignment: Alignment.center,
        children: [
          Positioned(
            bottom: 5,
            left: 5,
            child: Container(
              width: 17,
              height: 17,
              decoration: BoxDecoration(
                gradient: const LinearGradient(
                  colors: [Color(0xFFFF7A3D), Color(0xFFFF5B22)],
                ),
                borderRadius: BorderRadius.circular(4),
                boxShadow: [
                  BoxShadow(
                    color: const Color(0xFFFF5B22).withValues(alpha: 0.3),
                    blurRadius: 4,
                    offset: const Offset(0, 2),
                  ),
                ],
              ),
              child: const Icon(Icons.inventory_2_rounded, size: 10, color: Colors.white),
            ),
          ),
          Positioned(
            bottom: 5,
            right: 5,
            child: Container(
              width: 17,
              height: 17,
              decoration: BoxDecoration(
                gradient: const LinearGradient(
                  colors: [Color(0xFFFF9566), Color(0xFFFF7A3D)],
                ),
                borderRadius: BorderRadius.circular(4),
              ),
              child: const Icon(Icons.inventory_2_rounded, size: 10, color: Colors.white),
            ),
          ),
          Positioned(
            top: 5,
            child: Container(
              width: 19,
              height: 17,
              decoration: BoxDecoration(
                gradient: const LinearGradient(
                  colors: [Color(0xFFFFA880), Color(0xFFFF5B22)],
                ),
                borderRadius: BorderRadius.circular(4),
                boxShadow: [
                  BoxShadow(
                    color: const Color(0xFFFF5B22).withValues(alpha: 0.25),
                    blurRadius: 4,
                    offset: const Offset(0, 2),
                  ),
                ],
              ),
              child: const Icon(Icons.videocam_rounded, size: 11, color: Colors.white),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildParclgoStepper(bool isDone) {
    return Column(
      children: [
        // Nodes and lines
        Row(
          crossAxisAlignment: CrossAxisAlignment.center,
          children: [
            const SizedBox(width: 8),
            // Step 1: Origin/Jadwal
            Container(
              width: 20,
              height: 20,
              decoration: const BoxDecoration(
                color: _primaryOrange,
                shape: BoxShape.circle,
              ),
              child: const Icon(Icons.check, size: 13, color: Colors.white),
            ),
            // Line 1
            Expanded(
              child: Container(
                height: 2,
                color: _primaryOrange,
              ),
            ),
            // Step 2: Middle Status (with Speech Bubble above)
            Column(
              mainAxisSize: MainAxisSize.min,
              children: [
                // Speech Bubble Tag
                Container(
                  padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 3),
                  decoration: BoxDecoration(
                    color: _darkCharcoal,
                    borderRadius: BorderRadius.circular(10),
                  ),
                  child: Text(
                    _pelaksanaanStatus,
                    style: const TextStyle(
                      fontFamily: 'Poppins',
                      fontSize: 10,
                      fontWeight: FontWeight.w600,
                      color: Colors.white,
                    ),
                  ),
                ),
                ClipPath(
                  clipper: _ArrowClipper(),
                  child: Container(
                    width: 7,
                    height: 4,
                    color: _darkCharcoal,
                  ),
                ),
                const SizedBox(height: 2),
                // Glowing orange ring
                Container(
                  width: 18,
                  height: 18,
                  decoration: BoxDecoration(
                    color: Colors.white,
                    shape: BoxShape.circle,
                    border: Border.all(color: _primaryOrange, width: 3),
                  ),
                  child: Center(
                    child: Container(
                      width: 5,
                      height: 5,
                      decoration: const BoxDecoration(
                        color: _primaryOrange,
                        shape: BoxShape.circle,
                      ),
                    ),
                  ),
                ),
              ],
            ),
            // Line 2
            Expanded(
              child: Container(
                height: 2,
                color: isDone ? _primaryOrange : const Color(0xFFE2E8F0),
              ),
            ),
            // Step 3: Destination/Pelanggan
            Container(
              width: 20,
              height: 20,
              decoration: BoxDecoration(
                color: isDone ? _primaryOrange : Colors.white,
                shape: BoxShape.circle,
                border: Border.all(
                  color: isDone ? _primaryOrange : const Color(0xFFCBD5E1),
                  width: 2,
                ),
              ),
              child: isDone
                  ? const Icon(Icons.check, size: 13, color: Colors.white)
                  : Center(
                      child: Container(
                        width: 6,
                        height: 6,
                        decoration: const BoxDecoration(
                          color: Color(0xFF94A3B8),
                          shape: BoxShape.circle,
                        ),
                      ),
                    ),
            ),
            const SizedBox(width: 8),
          ],
        ),
        const SizedBox(height: 6),
        // Labels below nodes
        Row(
          mainAxisAlignment: MainAxisAlignment.spaceBetween,
          children: [
            // Left label: Time
            Row(
              mainAxisSize: MainAxisSize.min,
              children: [
                const Icon(Icons.access_time_rounded, size: 11, color: Color(0xFF94A3B8)),
                const SizedBox(width: 4),
                Text(
                  '${_formatTime(widget.data.jadwal)} WIB',
                  style: const TextStyle(
                    fontFamily: 'Poppins',
                    fontSize: 10.5,
                    fontWeight: FontWeight.w500,
                    color: Color(0xFF64748B),
                  ),
                ),
              ],
            ),
            // Center label: Menuju
            const Text(
              'Menuju Lokasi',
              style: TextStyle(
                fontFamily: 'Poppins',
                fontSize: 10.5,
                fontWeight: FontWeight.w500,
                color: Color(0xFF94A3B8),
              ),
            ),
            // Right label: Location
            Row(
              mainAxisSize: MainAxisSize.min,
              children: [
                const Icon(Icons.location_on_rounded, size: 11, color: Color(0xFF94A3B8)),
                const SizedBox(width: 4),
                Text(
                  widget.data.dataCustomer.kota ?? 'Pelanggan',
                  style: const TextStyle(
                    fontFamily: 'Poppins',
                    fontSize: 10.5,
                    fontWeight: FontWeight.w500,
                    color: Color(0xFF64748B),
                  ),
                ),
              ],
            ),
          ],
        ),
      ],
    );
  }

  @override
  Widget build(BuildContext context) {
    final data = widget.data;
    final activityColor = _getActivityColor(data.kegiatan);
    final activityBg = _getActivityBg(data.kegiatan);
    final activityIcon = _getActivityIcon(data.kegiatan);

    final bool isDone = _pelaksanaanStatus.toLowerCase() == 'selesai' ||
        data.status.toLowerCase() == 'selesai' ||
        data.status.toLowerCase() == 'selesai by admin' ||
        widget.history;

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
            borderRadius: BorderRadius.circular(22),
            border: Border.all(
              color: const Color(0xFFF1F5F9),
              width: 1.2,
            ),
            boxShadow: [
              BoxShadow(
                color: Colors.black.withValues(alpha: 0.035),
                blurRadius: 18,
                offset: const Offset(0, 6),
              ),
            ],
          ),
          child: Padding(
            padding: const EdgeInsets.all(16),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                // ── Top Row: Activity Avatar + ID/Title + 3D Box ──
                Row(
                  crossAxisAlignment: CrossAxisAlignment.center,
                  children: [
                    // Activity Icon Avatar
                    Container(
                      width: 46,
                      height: 46,
                      decoration: BoxDecoration(
                        color: activityBg,
                        borderRadius: BorderRadius.circular(14),
                      ),
                      child: Icon(
                        activityIcon,
                        size: 22,
                        color: activityColor,
                      ),
                    ),
                    const SizedBox(width: 12),
                    // ID & Customer Name
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Row(
                            children: [
                              Text(
                                'ID: ${data.kode}',
                                style: const TextStyle(
                                  fontFamily: 'Poppins',
                                  fontSize: 12.5,
                                  fontWeight: FontWeight.w700,
                                  color: _textPrimary,
                                ),
                              ),
                              const SizedBox(width: 8),
                              Container(
                                padding: const EdgeInsets.symmetric(
                                  horizontal: 8,
                                  vertical: 2.5,
                                ),
                                decoration: BoxDecoration(
                                  color: const Color(0xFFF1F5F9),
                                  borderRadius: BorderRadius.circular(8),
                                ),
                                child: Text(
                                  data.kegiatan,
                                  style: const TextStyle(
                                    fontFamily: 'Poppins',
                                    fontSize: 10,
                                    fontWeight: FontWeight.w600,
                                    color: Color(0xFF475569),
                                  ),
                                ),
                              ),
                            ],
                          ),
                          const SizedBox(height: 3),
                          Text(
                            data.dataCustomer.nama,
                            style: const TextStyle(
                              fontFamily: 'Poppins',
                              fontSize: 14.5,
                              fontWeight: FontWeight.w700,
                              color: _textPrimary,
                              height: 1.25,
                            ),
                            maxLines: 1,
                            overflow: TextOverflow.ellipsis,
                          ),
                        ],
                      ),
                    ),
                    const SizedBox(width: 8),
                    // 3D Box Graphic
                    _build3DBoxGraphic(),
                  ],
                ),

                const SizedBox(height: 14),

                // ── Middle Section: Parclgo Timeline Stepper ──
                _buildParclgoStepper(isDone),

                // ── Invoice Status if completed ──
                if (isDone) ...[
                  const SizedBox(height: 12),
                  Row(
                    children: [
                      Container(
                        padding: const EdgeInsets.symmetric(
                          horizontal: 8,
                          vertical: 4,
                        ),
                        decoration: BoxDecoration(
                          color: data.paid != null
                              ? const Color(0xFFEFF6FF)
                              : const Color(0xFFFEF2F2),
                          borderRadius: BorderRadius.circular(6),
                          border: Border.all(
                            color: data.paid != null
                                ? const Color(0xFFBFDBFE)
                                : const Color(0xFFFECACA),
                            width: 0.8,
                          ),
                        ),
                        child: Row(
                          mainAxisSize: MainAxisSize.min,
                          children: [
                            Icon(
                              data.paid != null
                                  ? Icons.check_circle_outline_rounded
                                  : Icons.warning_amber_rounded,
                              size: 12,
                              color: data.paid != null
                                  ? const Color(0xFF1D4ED8)
                                  : const Color(0xFFB91C1C),
                            ),
                            const SizedBox(width: 5),
                            Text(
                              data.paid != null
                                  ? 'Invoice Sudah Diinput'
                                  : 'Belum Input Invoice',
                              style: TextStyle(
                                fontFamily: 'Poppins',
                                fontSize: 10,
                                fontWeight: FontWeight.w600,
                                color: data.paid != null
                                    ? const Color(0xFF1D4ED8)
                                    : const Color(0xFFB91C1C),
                              ),
                            ),
                          ],
                        ),
                      ),
                      if (data.paid == null) ...[
                        const SizedBox(width: 8),
                        if (data.reqInvoiceAt == null)
                          Material(
                            color: Colors.transparent,
                            child: InkWell(
                              onTap: _isLoadingRequest ? null : _handleRequestInvoice,
                              borderRadius: BorderRadius.circular(6),
                              child: Ink(
                                padding: const EdgeInsets.symmetric(
                                  horizontal: 8,
                                  vertical: 4,
                                ),
                                decoration: BoxDecoration(
                                  color: const Color(0xFF0284C7).withValues(alpha: 0.1),
                                  borderRadius: BorderRadius.circular(6),
                                  border: Border.all(
                                    color: const Color(0xFF0284C7).withValues(alpha: 0.3),
                                    width: 0.8,
                                  ),
                                ),
                                child: Row(
                                  mainAxisSize: MainAxisSize.min,
                                  children: [
                                    if (_isLoadingRequest)
                                      const SizedBox(
                                        width: 10,
                                        height: 10,
                                        child: CircularProgressIndicator(
                                          strokeWidth: 1.5,
                                          valueColor: AlwaysStoppedAnimation<Color>(Color(0xFF0284C7)),
                                        ),
                                      )
                                    else
                                      const Icon(
                                        Icons.notification_add_rounded,
                                        size: 11,
                                        color: Color(0xFF0284C7),
                                      ),
                                    const SizedBox(width: 4),
                                    const Text(
                                      'Minta Invoice',
                                      style: TextStyle(
                                        fontFamily: 'Poppins',
                                        fontSize: 9.5,
                                        fontWeight: FontWeight.w600,
                                        color: Color(0xFF0284C7),
                                      ),
                                    ),
                                  ],
                                ),
                              ),
                            ),
                          )
                        else
                          Container(
                            padding: const EdgeInsets.symmetric(
                              horizontal: 8,
                              vertical: 4,
                            ),
                            decoration: BoxDecoration(
                              color: const Color(0xFF475569).withValues(alpha: 0.1),
                              borderRadius: BorderRadius.circular(6),
                              border: Border.all(
                                color: const Color(0xFF475569).withValues(alpha: 0.2),
                                width: 0.8,
                              ),
                            ),
                            child: const Row(
                              mainAxisSize: MainAxisSize.min,
                              children: [
                                Icon(
                                  Icons.watch_later_rounded,
                                  size: 11,
                                  color: Color(0xFF475569),
                                ),
                                SizedBox(width: 4),
                                Text(
                                  'Invoice Diminta',
                                  style: TextStyle(
                                    fontFamily: 'Poppins',
                                    fontSize: 9.5,
                                    fontWeight: FontWeight.w600,
                                    color: Color(0xFF475569),
                                  ),
                                ),
                              ],
                            ),
                          ),
                      ],
                    ],
                  ),
                ],

                const SizedBox(height: 12),
                Container(height: 1, color: const Color(0xFFF1F5F9)),
                const SizedBox(height: 10),

                // ── Bottom Row: Date + Drag Handle & Chevron ──
                Row(
                  children: [
                    Icon(
                      Icons.calendar_today_rounded,
                      size: 12,
                      color: _textSecondary.withValues(alpha: 0.7),
                    ),
                    const SizedBox(width: 5),
                    Text(
                      _formatDate(data.jadwal),
                      style: TextStyle(
                        fontFamily: 'Poppins',
                        fontSize: 11,
                        fontWeight: FontWeight.w500,
                        color: _textSecondary.withValues(alpha: 0.8),
                      ),
                    ),
                    const Spacer(),
                    // Reorder handle if active task
                    if (!widget.history) ...[
                      Padding(
                        key: widget.dragHandleKey,
                        padding: const EdgeInsets.symmetric(horizontal: 6),
                        child: Row(
                          mainAxisSize: MainAxisSize.min,
                          children: [
                            for (int i = 0; i < 3; i++) ...[
                              Container(
                                width: 3,
                                height: 3,
                                decoration: BoxDecoration(
                                  color: _textSecondary.withValues(alpha: 0.25),
                                  shape: BoxShape.circle,
                                ),
                              ),
                              if (i < 2) const SizedBox(width: 2.5),
                            ],
                          ],
                        ),
                      ),
                    ],
                    Container(
                      padding: const EdgeInsets.all(4),
                      decoration: BoxDecoration(
                        color: const Color(0xFFF8FAFC),
                        borderRadius: BorderRadius.circular(8),
                      ),
                      child: const Icon(
                        Icons.chevron_right_rounded,
                        size: 16,
                        color: Color(0xFF94A3B8),
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
}

class _ArrowClipper extends CustomClipper<Path> {
  @override
  Path getClip(Size size) {
    final path = Path();
    path.moveTo(0, 0);
    path.lineTo(size.width / 2, size.height);
    path.lineTo(size.width, 0);
    path.close();
    return path;
  }

  @override
  bool shouldReclip(CustomClipper<Path> oldClipper) => false;
}
