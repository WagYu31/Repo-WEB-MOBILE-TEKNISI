import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:geolocator/geolocator.dart';
import 'package:provider/provider.dart';
import 'package:slide_to_act/slide_to_act.dart';
import 'package:quickalert/quickalert.dart';
import 'package:url_launcher/url_launcher.dart';
import 'package:intl/intl.dart';
import '../../service/model/SalesModel.dart';
import '../../service/provider/SalesProvider.dart';

class TaskDetailPage extends StatefulWidget {
  final VisitTask task;
  const TaskDetailPage({super.key, required this.task});
  @override
  State<TaskDetailPage> createState() => _TaskDetailPageState();
}

class _TaskDetailPageState extends State<TaskDetailPage> {
  late VisitTask _task;
  bool _gpsLoading = false;
  final _catatanCtrl = TextEditingController();
  final GlobalKey<SlideActionState> _slideKeyCI = GlobalKey();
  final GlobalKey<SlideActionState> _slideKeyCO = GlobalKey();

  @override
  void initState() {
    super.initState();
    _task = widget.task;
  }

  @override
  void dispose() {
    _catatanCtrl.dispose();
    super.dispose();
  }

  // ── GPS Permission & Position ──────────────────────────────
  Future<Position?> _getPosition() async {
    final serviceEnabled = await Geolocator.isLocationServiceEnabled();
    if (!serviceEnabled) {
      _showError('GPS tidak aktif. Aktifkan lokasi perangkat Anda.');
      return null;
    }
    LocationPermission permission = await Geolocator.checkPermission();
    if (permission == LocationPermission.denied) {
      permission = await Geolocator.requestPermission();
      if (permission == LocationPermission.denied) {
        _showError('Izin lokasi ditolak.');
        return null;
      }
    }
    if (permission == LocationPermission.deniedForever) {
      _showError('Izin lokasi diblokir permanen. Aktifkan di Pengaturan.');
      return null;
    }
    setState(() => _gpsLoading = true);
    try {
      final pos = await Geolocator.getCurrentPosition(
          desiredAccuracy: LocationAccuracy.high);
      setState(() => _gpsLoading = false);
      return pos;
    } catch (e) {
      setState(() => _gpsLoading = false);
      _showError('Gagal mendapatkan lokasi: $e');
      return null;
    }
  }

  // ── Clock In ────────────────────────────────────────────────
  Future<void> _doClockIn() async {
    final pos = await _getPosition();
    if (pos == null) {
      _slideKeyCI.currentState?.reset();
      return;
    }
    try {
      final msg = await context.read<SalesProvider>().doClockIn(
            kegiatanId: _task.kegiatanId,
            lat: pos.latitude.toString(),
            lon: pos.longitude.toString(),
            isMock: pos.isMocked,
          );
      if (!mounted) return;
      QuickAlert.show(
        context: context,
        type: QuickAlertType.success,
        title: 'Clock In Berhasil!',
        text: msg,
        confirmBtnColor: const Color(0xFF3B82F6),
        onConfirmBtnTap: () {
          Navigator.pop(context); // close alert
          Navigator.pop(context); // kembali ke list dan reload
        },
      );
    } catch (e) {
      if (!mounted) return;
      _slideKeyCI.currentState?.reset();
      QuickAlert.show(
        context: context,
        type: QuickAlertType.error,
        title: 'Gagal Clock In',
        text: e.toString().replaceAll('Exception: ', ''),
        confirmBtnColor: Colors.red,
      );
    }
  }

  // ── Clock Out ───────────────────────────────────────────────
  Future<void> _doClockOut() async {
    // Minta catatan dulu
    final catatan = await _showCatatanDialog();
    if (catatan == null) {
      _slideKeyCO.currentState?.reset();
      return;
    }

    final pos = await _getPosition();
    if (pos == null) {
      _slideKeyCO.currentState?.reset();
      return;
    }

    try {
      final msg = await context.read<SalesProvider>().doClockOut(
            kegiatanId: _task.kegiatanId,
            lat: pos.latitude.toString(),
            lon: pos.longitude.toString(),
            catatan: catatan,
            isMock: pos.isMocked,
          );
      if (!mounted) return;
      QuickAlert.show(
        context: context,
        type: QuickAlertType.success,
        title: 'Kunjungan Selesai!',
        text: msg,
        confirmBtnColor: const Color(0xFF10B981),
        onConfirmBtnTap: () {
          Navigator.pop(context);
          Navigator.pop(context);
        },
      );
    } catch (e) {
      if (!mounted) return;
      _slideKeyCO.currentState?.reset();
      QuickAlert.show(
        context: context,
        type: QuickAlertType.error,
        title: 'Gagal Clock Out',
        text: e.toString().replaceAll('Exception: ', ''),
        confirmBtnColor: Colors.red,
      );
    }
  }

  // ── Dialog catatan ──────────────────────────────────────────
  Future<String?> _showCatatanDialog() {
    _catatanCtrl.clear();
    return showModalBottomSheet<String>(
      context: context,
      isScrollControlled: true,
      backgroundColor: const Color(0xFF1E293B),
      shape: const RoundedRectangleBorder(
          borderRadius: BorderRadius.vertical(top: Radius.circular(20))),
      builder: (_) => Padding(
        padding: EdgeInsets.only(
          left: 20,
          right: 20,
          top: 20,
          bottom: MediaQuery.of(context).viewInsets.bottom + 20,
        ),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Center(
              child: Container(
                width: 40,
                height: 4,
                decoration: BoxDecoration(
                    color: Colors.white24, borderRadius: BorderRadius.circular(2)),
              ),
            ),
            const SizedBox(height: 16),
            Text('Catatan Kunjungan',
                style: GoogleFonts.poppins(
                    fontSize: 16, fontWeight: FontWeight.w600, color: Colors.white)),
            const SizedBox(height: 12),
            TextField(
              controller: _catatanCtrl,
              maxLines: 4,
              style: GoogleFonts.poppins(color: Colors.white, fontSize: 13),
              decoration: InputDecoration(
                hintText: 'Tuliskan hasil/catatan kunjungan...',
                hintStyle: GoogleFonts.poppins(color: Colors.white38, fontSize: 13),
                filled: true,
                fillColor: const Color(0xFF0F172A),
                border: OutlineInputBorder(
                    borderRadius: BorderRadius.circular(12),
                    borderSide: const BorderSide(color: Color(0xFF334155))),
                enabledBorder: OutlineInputBorder(
                    borderRadius: BorderRadius.circular(12),
                    borderSide: const BorderSide(color: Color(0xFF334155))),
                focusedBorder: OutlineInputBorder(
                    borderRadius: BorderRadius.circular(12),
                    borderSide: const BorderSide(color: Color(0xFF10B981), width: 1.5)),
              ),
            ),
            const SizedBox(height: 16),
            SizedBox(
              width: double.infinity,
              height: 48,
              child: ElevatedButton(
                style: ElevatedButton.styleFrom(
                  backgroundColor: const Color(0xFF10B981),
                  shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                ),
                onPressed: () => Navigator.pop(context, _catatanCtrl.text.trim()),
                child: Text('Konfirmasi Clock Out',
                    style: GoogleFonts.poppins(
                        color: Colors.white, fontWeight: FontWeight.w600)),
              ),
            ),
          ],
        ),
      ),
    );
  }

  void _showError(String msg) {
    ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(msg), backgroundColor: Colors.red));
  }

  String _fmtTime(String? dt) {
    if (dt == null || dt.isEmpty) return '-';
    try {
      return DateFormat('HH:mm').format(DateTime.parse(dt));
    } catch (_) {
      return dt;
    }
  }

  String _fmtDate(String dt) {
    try {
      return DateFormat('EEEE, dd MMMM yyyy • HH:mm', 'id').format(DateTime.parse(dt));
    } catch (_) {
      return dt;
    }
  }

  @override
  Widget build(BuildContext context) {
    final t = _task;
    return Scaffold(
      backgroundColor: const Color(0xFF0F172A),
      appBar: AppBar(
        backgroundColor: const Color(0xFF0F172A),
        elevation: 0,
        leading: IconButton(
          icon: const Icon(Icons.arrow_back_ios, color: Colors.white, size: 18),
          onPressed: () => Navigator.pop(context),
        ),
        title: Text('Detail Kunjungan',
            style: GoogleFonts.poppins(
                fontSize: 16, fontWeight: FontWeight.w600, color: Colors.white)),
      ),
      body: SingleChildScrollView(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            // ── Customer Card ──────────────────────────────────
            _sectionCard(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Row(children: [
                    Container(
                      padding: const EdgeInsets.all(10),
                      decoration: BoxDecoration(
                        color: const Color(0xFF3B82F6).withOpacity(0.15),
                        borderRadius: BorderRadius.circular(12),
                      ),
                      child: const Icon(Icons.storefront, color: Color(0xFF3B82F6), size: 24),
                    ),
                    const SizedBox(width: 12),
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(t.namaCustomer,
                              style: GoogleFonts.poppins(
                                  fontSize: 16, fontWeight: FontWeight.w700, color: Colors.white)),
                          if (t.kode != null)
                            Text('Kode: ${t.kode}',
                                style: GoogleFonts.poppins(fontSize: 11, color: Colors.white38)),
                        ],
                      ),
                    ),
                  ]),
                  const SizedBox(height: 14),
                  _infoRow(Icons.location_on_outlined,
                      '${t.alamatCustomer}, ${t.kotaCustomer}'),
                  const SizedBox(height: 6),
                  _infoRow(Icons.calendar_today, _fmtDate(t.jadwal)),
                  if (t.telpCustomer.isNotEmpty) ...[
                    const SizedBox(height: 6),
                    _infoRow(Icons.phone, t.telpCustomer,
                        onTap: () {
                          String wa = t.telpCustomer.replaceAll(RegExp(r'\D'), '');
                          if (wa.startsWith('0')) wa = '62${wa.substring(1)}';
                          launchUrl(Uri.parse('https://wa.me/$wa'));
                        },
                        actionIcon: Icons.chat, actionColor: const Color(0xFF25D366)),
                  ],
                  if (t.keterangan.isNotEmpty) ...[
                    const SizedBox(height: 6),
                    _infoRow(Icons.notes, t.keterangan),
                  ],
                ],
              ),
            ),
            const SizedBox(height: 14),

            // ── Clock In/Out Status ────────────────────────────
            _sectionCard(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text('Status Kunjungan',
                      style: GoogleFonts.poppins(
                          fontSize: 13, fontWeight: FontWeight.w600, color: Colors.white70)),
                  const SizedBox(height: 12),
                  Row(
                    children: [
                      _clockStatus(
                          label: 'Clock In',
                          time: t.sudahClockIn ? _fmtTime(t.ciAt) : '--:--',
                          icon: Icons.login,
                          active: t.sudahClockIn,
                          color: const Color(0xFF3B82F6)),
                      const SizedBox(width: 12),
                      Expanded(
                        child: Container(
                          height: 2,
                          decoration: BoxDecoration(
                            gradient: LinearGradient(
                              colors: t.sudahClockOut
                                  ? [const Color(0xFF3B82F6), const Color(0xFF10B981)]
                                  : [const Color(0xFF3B82F6).withOpacity(0.3),
                                     const Color(0xFF10B981).withOpacity(0.3)],
                            ),
                          ),
                        ),
                      ),
                      const SizedBox(width: 12),
                      _clockStatus(
                          label: 'Clock Out',
                          time: t.sudahClockOut ? _fmtTime(t.coAt) : '--:--',
                          icon: Icons.logout,
                          active: t.sudahClockOut,
                          color: const Color(0xFF10B981)),
                    ],
                  ),
                  if (t.catatanVisit != null && t.catatanVisit!.isNotEmpty) ...[
                    const SizedBox(height: 12),
                    Container(
                      padding: const EdgeInsets.all(10),
                      decoration: BoxDecoration(
                        color: const Color(0xFF0F172A),
                        borderRadius: BorderRadius.circular(10),
                      ),
                      child: Row(
                        children: [
                          const Icon(Icons.notes, size: 14, color: Colors.white38),
                          const SizedBox(width: 6),
                          Expanded(
                            child: Text(t.catatanVisit!,
                                style: GoogleFonts.poppins(
                                    fontSize: 12, color: Colors.white60)),
                          ),
                        ],
                      ),
                    ),
                  ],
                ],
              ),
            ),
            const SizedBox(height: 24),

            // ── Slide Buttons ──────────────────────────────────
            if (_gpsLoading)
              const Center(
                child: Column(
                  children: [
                    CircularProgressIndicator(color: Color(0xFF3B82F6)),
                    SizedBox(height: 8),
                    Text('Mendapatkan lokasi GPS...',
                        style: TextStyle(color: Colors.white54, fontSize: 12)),
                  ],
                ),
              )
            else if (!t.sudahClockIn) ...[
              // CLOCK IN SLIDER
              SlideAction(
                key: _slideKeyCI,
                text: 'Geser → Clock In',
                textStyle: GoogleFonts.poppins(
                    color: Colors.white70,
                    fontSize: 14,
                    fontWeight: FontWeight.w500),
                innerColor: const Color(0xFF3B82F6),
                outerColor: const Color(0xFF1E3A5F),
                sliderButtonIcon: const Icon(Icons.login, color: Colors.white, size: 24),
                elevation: 0,
                borderRadius: 16,
                height: 60,
                onSubmit: () {
                  _doClockIn();
                  return null;
                },
              ),
            ] else if (t.sedangBerjalan) ...[
              // CLOCK OUT SLIDER
              SlideAction(
                key: _slideKeyCO,
                text: 'Geser → Clock Out',
                textStyle: GoogleFonts.poppins(
                    color: Colors.white70,
                    fontSize: 14,
                    fontWeight: FontWeight.w500),
                innerColor: const Color(0xFF10B981),
                outerColor: const Color(0xFF064E3B),
                sliderButtonIcon: const Icon(Icons.logout, color: Colors.white, size: 24),
                elevation: 0,
                borderRadius: 16,
                height: 60,
                onSubmit: () {
                  _doClockOut();
                  return null;
                },
              ),
            ] else ...[
              Container(
                width: double.infinity,
                padding: const EdgeInsets.all(16),
                decoration: BoxDecoration(
                  color: const Color(0xFF10B981).withOpacity(0.1),
                  borderRadius: BorderRadius.circular(16),
                  border: Border.all(color: const Color(0xFF10B981).withOpacity(0.3)),
                ),
                child: Row(
                  mainAxisAlignment: MainAxisAlignment.center,
                  children: [
                    const Icon(Icons.check_circle, color: Color(0xFF10B981), size: 22),
                    const SizedBox(width: 8),
                    Text('Kunjungan Selesai',
                        style: GoogleFonts.poppins(
                            fontSize: 15,
                            fontWeight: FontWeight.w600,
                            color: const Color(0xFF10B981))),
                  ],
                ),
              ),
            ],
            const SizedBox(height: 32),
          ],
        ),
      ),
    );
  }

  Widget _sectionCard({required Widget child}) {
    return Container(
      width: double.infinity,
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: const Color(0xFF1E293B),
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: const Color(0xFF334155)),
      ),
      child: child,
    );
  }

  Widget _infoRow(IconData icon, String text,
      {VoidCallback? onTap, IconData? actionIcon, Color? actionColor}) {
    return Row(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Icon(icon, size: 14, color: Colors.white38),
        const SizedBox(width: 8),
        Expanded(
          child: Text(text,
              style: GoogleFonts.poppins(fontSize: 12, color: Colors.white60)),
        ),
        if (onTap != null && actionIcon != null)
          GestureDetector(
            onTap: onTap,
            child: Icon(actionIcon, size: 18, color: actionColor ?? Colors.white38),
          ),
      ],
    );
  }

  Widget _clockStatus({
    required String label,
    required String time,
    required IconData icon,
    required bool active,
    required Color color,
  }) {
    return Column(
      children: [
        Container(
          width: 48,
          height: 48,
          decoration: BoxDecoration(
            shape: BoxShape.circle,
            color: active ? color.withOpacity(0.15) : const Color(0xFF0F172A),
            border: Border.all(
              color: active ? color : Colors.white12,
              width: 2,
            ),
          ),
          child: Icon(icon, color: active ? color : Colors.white24, size: 22),
        ),
        const SizedBox(height: 6),
        Text(label,
            style: GoogleFonts.poppins(fontSize: 10, color: Colors.white38)),
        Text(time,
            style: GoogleFonts.poppins(
                fontSize: 13,
                fontWeight: FontWeight.w700,
                color: active ? color : Colors.white24)),
      ],
    );
  }
}
