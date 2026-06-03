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

  const CardTask({super.key, required this.data, required this.history});

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

  static const List<String> _lateMessages = [
    "Terlambat", "Overdue", "Lewat jadwal",
  ];

  static const List<String> _bonusMessages = [
    "Selesai!", "Completed", "Done!",
  ];

  // ─── Premium Color Palette ─────────────────────
  static const Color _primaryBlue = Color(0xFF0EA5E9);
  static const Color _successGreen = Color(0xFF14B8A6);
  static const Color _warningAmber = Color(0xFFF97316);
  static const Color _errorRed = Color(0xFFF43F5E);
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

    if (isToday && data.status != 'dibatalkan') {
      _statusLabel = 'Hari Ini';
      _statusColor = _warningAmber;
      _statusIcon = Icons.today;
    } else if (data.jadwal.isAfter(now) &&
        data.status != 'dibatalkan' &&
        data.status != 'lanjut nanti' &&
        data.status != 'selesai') {
      _statusLabel = 'Terjadwal';
      _statusColor = _primaryBlue;
      _statusIcon = Icons.event;
    } else if (data.status == 'Lanjut Nanti') {
      _statusLabel = 'Ditunda';
      _statusColor = _textSecondary;
      _statusIcon = Icons.pause_circle_outline;
    } else if (data.status == 'selesai' && isSelesai) {
      _statusLabel = _bonusMessages[now.day % _bonusMessages.length];
      _statusColor = _successGreen;
      _statusIcon = Icons.check_circle;
    } else if (data.status == 'dibatalkan') {
      _statusLabel = 'Dibatalkan';
      _statusColor = _errorRed;
      _statusIcon = Icons.cancel;
    } else if (isSelesai) {
      _statusLabel = _pelaksanaanStatus;
      _statusColor = _successGreen;
      _statusIcon = Icons.check_circle_outline;
    } else {
      _statusLabel = _lateMessages[now.day % _lateMessages.length];
      _statusColor = _errorRed;
      _statusIcon = Icons.warning_amber_rounded;
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
      position ??= await Geolocator.getCurrentPosition(
        desiredAccuracy: LocationAccuracy.medium,
      );

      if (position.isMocked) {
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
            borderRadius: BorderRadius.circular(16),
            boxShadow: [
              BoxShadow(
                color: Colors.black.withValues(alpha: 0.06),
                blurRadius: 12,
                offset: const Offset(0, 3),
              ),
            ],
          ),
            child: Padding(
              padding: const EdgeInsets.fromLTRB(16, 14, 16, 14),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  // Top row: status + kegiatan
                  Row(
                    children: [
                      // Status — clean text only
                      Text(
                        _statusLabel,
                        style: TextStyle(
                          fontFamily: 'Poppins',
                          fontSize: 12,
                          fontWeight: FontWeight.w600,
                          color: _statusColor,
                        ),
                      ),
                      const Spacer(),
                      // Activity type
                      Container(
                        padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                        decoration: BoxDecoration(
                          color: const Color(0xFFF1F5F9),
                          borderRadius: BorderRadius.circular(6),
                        ),
                        child: Text(
                          data.kegiatan,
                          style: const TextStyle(
                            fontFamily: 'Poppins',
                            fontSize: 10,
                            fontWeight: FontWeight.w500,
                            color: Color(0xFF64748B),
                          ),
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(height: 8),
                  // Customer name
                  Text(
                    data.dataCustomer.nama,
                    style: const TextStyle(
                      fontFamily: 'Poppins',
                      fontSize: 15,
                      fontWeight: FontWeight.w600,
                      color: _textPrimary,
                      height: 1.3,
                    ),
                    maxLines: 2,
                    overflow: TextOverflow.ellipsis,
                  ),
                  const SizedBox(height: 10),
                  // Bottom row: date + arrow
                  Row(
                    children: [
                      Icon(
                        Icons.event_rounded,
                        size: 14,
                        color: _textSecondary.withValues(alpha: 0.5),
                      ),
                      const SizedBox(width: 6),
                      Text(
                        _formatDate(data.jadwal),
                        style: TextStyle(
                          fontFamily: 'Poppins',
                          fontSize: 12,
                          color: _textSecondary.withValues(alpha: 0.7),
                        ),
                      ),
                      const SizedBox(width: 5),
                      Container(
                        width: 3,
                        height: 3,
                        decoration: BoxDecoration(
                          color: _textSecondary.withValues(alpha: 0.3),
                          shape: BoxShape.circle,
                        ),
                      ),
                      const SizedBox(width: 5),
                      Text(
                        _formatTime(data.jadwal),
                        style: TextStyle(
                          fontFamily: 'Poppins',
                          fontSize: 12,
                          color: _textSecondary.withValues(alpha: 0.5),
                        ),
                      ),
                      const Spacer(),
                      if (data.paid != null)
                        Padding(
                          padding: const EdgeInsets.only(right: 6),
                          child: Icon(
                            Icons.receipt_long_rounded,
                            size: 14,
                            color: _successGreen.withValues(alpha: 0.6),
                          ),
                        ),
                      Icon(
                        Icons.arrow_forward_ios_rounded,
                        size: 14,
                        color: _textSecondary.withValues(alpha: 0.3),
                      ),
                    ],
                  ),
                  // Execution status
                  if (_pelaksanaanStatus != 'Dijadwalkan') ...[
                    const SizedBox(height: 8),
                    Row(
                      children: [
                        Icon(
                          _getStatusIconForPelaksanaan(_pelaksanaanStatus),
                          size: 12,
                          color: _textSecondary.withValues(alpha: 0.5),
                        ),
                        const SizedBox(width: 5),
                        Text(
                          _pelaksanaanStatus,
                          style: TextStyle(
                            fontFamily: 'Poppins',
                            fontSize: 11,
                            fontWeight: FontWeight.w400,
                            color: _textSecondary.withValues(alpha: 0.6),
                          ),
                        ),
                      ],
                    ),
                  ],
                ],
              ),
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
