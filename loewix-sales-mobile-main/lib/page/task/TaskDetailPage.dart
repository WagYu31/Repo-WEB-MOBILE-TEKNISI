import 'dart:io';
import 'package:flutter/material.dart';
import 'package:geolocator/geolocator.dart';
import 'package:provider/provider.dart';
import 'package:slide_to_act/slide_to_act.dart';
import 'package:quickalert/quickalert.dart';
import 'package:url_launcher/url_launcher.dart';
import 'package:intl/intl.dart';
import 'package:image_picker/image_picker.dart';
import 'package:flutter_animate/flutter_animate.dart';
import '../../core/app_theme.dart';
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
  XFile? _capturedPhoto;
  final _picker = ImagePicker();

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

  // ── GPS ─────────────────────────────────────────────────
  Future<Position?> _getPosition() async {
    if (!await Geolocator.isLocationServiceEnabled()) {
      _showError('GPS tidak aktif. Aktifkan lokasi perangkat.');
      return null;
    }
    LocationPermission perm = await Geolocator.checkPermission();
    if (perm == LocationPermission.denied) {
      perm = await Geolocator.requestPermission();
      if (perm == LocationPermission.denied) {
        _showError('Izin lokasi ditolak.');
        return null;
      }
    }
    if (perm == LocationPermission.deniedForever) {
      _showError('Izin lokasi diblokir. Aktifkan di Pengaturan.');
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

  // ── Clock In ────────────────────────────────────────────
  Future<void> _doClockIn() async {
    final pos = await _getPosition();
    if (pos == null) { _slideKeyCI.currentState?.reset(); return; }
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
        confirmBtnColor: AppColors.primary,
        onConfirmBtnTap: () {
          Navigator.pop(context);
          Navigator.pop(context);
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
        confirmBtnColor: AppColors.error,
      );
    }
  }

  // ── Clock Out ───────────────────────────────────────────
  Future<void> _doClockOut() async {
    final result = await _showCatatanSheet();
    if (result == null) { _slideKeyCO.currentState?.reset(); return; }

    final pos = await _getPosition();
    if (pos == null) { _slideKeyCO.currentState?.reset(); return; }

    try {
      final msg = await context.read<SalesProvider>().doClockOut(
        kegiatanId: _task.kegiatanId,
        lat: pos.latitude.toString(),
        lon: pos.longitude.toString(),
        catatan: result,
        isMock: pos.isMocked,
      );
      if (!mounted) return;
      QuickAlert.show(
        context: context,
        type: QuickAlertType.success,
        title: 'Kunjungan Selesai!',
        text: msg,
        confirmBtnColor: AppColors.success,
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
        confirmBtnColor: AppColors.error,
      );
    }
  }

  // ── Catatan + Foto Bottom Sheet ──────────────────────────
  Future<String?> _showCatatanSheet() {
    _catatanCtrl.clear();
    _capturedPhoto = null;
    return showModalBottomSheet<String>(
      context: context,
      isScrollControlled: true,
      backgroundColor: AppColors.cardAlt,
      shape: const RoundedRectangleBorder(
          borderRadius: BorderRadius.vertical(top: Radius.circular(24))),
      builder: (_) => _CatatanSheet(
        ctrl: _catatanCtrl,
        picker: _picker,
        onPhotoCapture: (f) => setState(() => _capturedPhoto = f),
        onSubmit: (catatan) => Navigator.pop(context, catatan),
      ),
    );
  }

  void _showError(String msg) => ScaffoldMessenger.of(context)
      .showSnackBar(SnackBar(
        content: Text(msg, style: S.body(AppColors.textPrimary)),
        backgroundColor: AppColors.error,
      ));

  String _fmtTime(String? dt) {
    if (dt == null || dt.isEmpty) return '--:--';
    try { return DateFormat('HH:mm').format(DateTime.parse(dt)); }
    catch (_) { return dt; }
  }

  String _fmtDate(String dt) {
    try {
      return DateFormat('EEEE, dd MMMM yyyy • HH:mm', 'id')
          .format(DateTime.parse(dt));
    } catch (_) { return dt; }
  }

  void _openMaps() {
    final q = Uri.encodeComponent(
        '${_task.alamatCustomer}, ${_task.kotaCustomer}');
    launchUrl(Uri.parse('https://www.google.com/maps/search/?api=1&query=$q'),
        mode: LaunchMode.externalApplication);
  }

  void _openWhatsApp() {
    String wa = _task.telpCustomer.replaceAll(RegExp(r'\D'), '');
    if (wa.startsWith('0')) wa = '62${wa.substring(1)}';
    launchUrl(Uri.parse('https://wa.me/$wa'),
        mode: LaunchMode.externalApplication);
  }

  @override
  Widget build(BuildContext context) {
    final t = _task;
    return Scaffold(
      backgroundColor: AppColors.bg,
      body: CustomScrollView(
        slivers: [
          // ── Sliver App Bar ───────────────────────────
          SliverAppBar(
            backgroundColor: AppColors.surface,
            pinned: true,
            elevation: 0,
            leading: IconButton(
              onPressed: () => Navigator.pop(context),
              icon: Container(
                padding: const EdgeInsets.all(6),
                decoration: BoxDecoration(
                  color: AppColors.card,
                  borderRadius: BorderRadius.circular(10),
                  border: Border.all(color: AppColors.border),
                ),
                child: const Icon(Icons.arrow_back_ios_new_rounded,
                    color: AppColors.textPrimary, size: 16),
              ),
            ),
            title: Text('Detail Kunjungan', style: S.h3()),
            centerTitle: true,
          ),

          // ── Content ──────────────────────────────────
          SliverPadding(
            padding: const EdgeInsets.fromLTRB(20, 16, 20, 40),
            sliver: SliverList(
              delegate: SliverChildListDelegate([

                // ── Customer Card ───────────────────────
                Container(
                  padding: const EdgeInsets.all(18),
                  decoration: AppTheme.cardDeco(accentColor: AppColors.primary),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Row(children: [
                        Container(
                          width: 52, height: 52,
                          decoration: BoxDecoration(
                            gradient: AppColors.gradientPrimary,
                            borderRadius: BorderRadius.circular(14),
                          ),
                          child: const Icon(Icons.storefront_rounded,
                              color: Colors.white, size: 26),
                        ),
                        const SizedBox(width: 14),
                        Expanded(
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Text(t.namaCustomer,
                                  style: S.h2(), maxLines: 2,
                                  overflow: TextOverflow.ellipsis),
                              if (t.kode != null)
                                Text('Kode: ${t.kode}',
                                    style: S.caption()),
                            ],
                          ),
                        ),
                      ]),
                      const SizedBox(height: 16),
                      Container(height: 1, color: AppColors.divider),
                      const SizedBox(height: 14),

                      // Info rows
                      _InfoRow(
                        icon: Icons.location_on_outlined,
                        text: '${t.alamatCustomer}, ${t.kotaCustomer}',
                        action: IconButton(
                          onPressed: _openMaps,
                          icon: const Icon(Icons.map_outlined,
                              size: 20, color: AppColors.info),
                          padding: EdgeInsets.zero,
                          constraints: const BoxConstraints(),
                        ),
                      ),
                      const SizedBox(height: 10),
                      _InfoRow(
                        icon: Icons.calendar_today_outlined,
                        text: _fmtDate(t.jadwal),
                      ),
                      if (t.telpCustomer.isNotEmpty) ...[
                        const SizedBox(height: 10),
                        _InfoRow(
                          icon: Icons.phone_outlined,
                          text: t.telpCustomer,
                          action: IconButton(
                            onPressed: _openWhatsApp,
                            icon: const Icon(Icons.chat_rounded,
                                size: 20, color: AppColors.success),
                            padding: EdgeInsets.zero,
                            constraints: const BoxConstraints(),
                          ),
                        ),
                      ],
                      if (t.keterangan.isNotEmpty) ...[
                        const SizedBox(height: 10),
                        _InfoRow(
                          icon: Icons.notes_rounded,
                          text: t.keterangan,
                        ),
                      ],
                    ],
                  ),
                ).animate().fadeIn(duration: 400.ms).slideY(begin: 0.1, end: 0),

                const SizedBox(height: 16),

                // ── Status Timeline ─────────────────────
                Container(
                  padding: const EdgeInsets.all(18),
                  decoration: AppTheme.cardDeco(),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Row(
                        children: [
                          const Icon(Icons.access_time_filled_rounded,
                              size: 16, color: AppColors.primary),
                          const SizedBox(width: 8),
                          Text('Status Kunjungan', style: S.h3()),
                        ],
                      ),
                      const SizedBox(height: 18),

                      // Timeline
                      Row(
                        children: [
                          _ClockNode(
                            label: 'Clock In',
                            time: t.sudahClockIn ? _fmtTime(t.ciAt) : '--:--',
                            icon: Icons.login_rounded,
                            active: t.sudahClockIn,
                            color: AppColors.primary,
                          ),
                          // Connector line
                          Expanded(
                            child: Container(
                              height: 3,
                              margin: const EdgeInsets.only(bottom: 22),
                              decoration: BoxDecoration(
                                gradient: LinearGradient(
                                  colors: t.sudahClockOut
                                      ? [AppColors.primary, AppColors.success]
                                      : [
                                          AppColors.primary.withOpacity(0.3),
                                          AppColors.border,
                                        ],
                                ),
                                borderRadius: BorderRadius.circular(2),
                              ),
                            ),
                          ),
                          _ClockNode(
                            label: 'Clock Out',
                            time: t.sudahClockOut ? _fmtTime(t.coAt) : '--:--',
                            icon: Icons.logout_rounded,
                            active: t.sudahClockOut,
                            color: AppColors.success,
                          ),
                        ],
                      ),

                      // Catatan visit
                      if (t.catatanVisit != null && t.catatanVisit!.isNotEmpty) ...[
                        const SizedBox(height: 14),
                        Container(
                          padding: const EdgeInsets.all(12),
                          decoration: BoxDecoration(
                            color: AppColors.bg,
                            borderRadius: BorderRadius.circular(12),
                            border: Border.all(color: AppColors.border),
                          ),
                          child: Row(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              const Icon(Icons.notes_rounded,
                                  size: 15, color: AppColors.textMuted),
                              const SizedBox(width: 8),
                              Expanded(
                                child: Text(t.catatanVisit!,
                                    style: S.bodySm()),
                              ),
                            ],
                          ),
                        ),
                      ],
                    ],
                  ),
                ).animate(delay: 150.ms).fadeIn(duration: 400.ms).slideY(begin: 0.1, end: 0),

                const SizedBox(height: 28),

                // ── Action Buttons ──────────────────────
                if (_gpsLoading)
                  Center(
                    child: Column(
                      children: [
                        const CircularProgressIndicator(color: AppColors.primary),
                        const SizedBox(height: 10),
                        Text('Mendapatkan lokasi GPS...',
                            style: S.caption()),
                      ],
                    ),
                  ).animate().fadeIn(duration: 300.ms)
                else if (!t.sudahClockIn)
                  SlideAction(
                    key: _slideKeyCI,
                    text: 'Geser untuk Clock In',
                    textStyle: S.body(AppColors.textSecondary),
                    innerColor: AppColors.primary,
                    outerColor: AppColors.primaryDark.withOpacity(0.35),
                    sliderButtonIcon: const Icon(Icons.login_rounded,
                        color: Colors.white, size: 26),
                    elevation: 0,
                    borderRadius: 18,
                    height: 62,
                    onSubmit: () { _doClockIn(); return null; },
                  ).animate(delay: 200.ms).fadeIn(duration: 500.ms).slideY(begin: 0.2, end: 0)
                else if (t.sedangBerjalan)
                  SlideAction(
                    key: _slideKeyCO,
                    text: 'Geser untuk Clock Out',
                    textStyle: S.body(AppColors.textSecondary),
                    innerColor: AppColors.success,
                    outerColor: AppColors.successBg,
                    sliderButtonIcon: const Icon(Icons.logout_rounded,
                        color: Colors.white, size: 26),
                    elevation: 0,
                    borderRadius: 18,
                    height: 62,
                    onSubmit: () { _doClockOut(); return null; },
                  ).animate(delay: 200.ms).fadeIn(duration: 500.ms).slideY(begin: 0.2, end: 0)
                else
                  Container(
                    padding: const EdgeInsets.all(18),
                    decoration: BoxDecoration(
                      color: AppColors.success.withOpacity(0.08),
                      borderRadius: BorderRadius.circular(18),
                      border: Border.all(
                          color: AppColors.success.withOpacity(0.3)),
                    ),
                    child: Row(
                      mainAxisAlignment: MainAxisAlignment.center,
                      children: [
                        const Icon(Icons.check_circle_rounded,
                            color: AppColors.success, size: 24),
                        const SizedBox(width: 10),
                        Text('Kunjungan Selesai',
                            style: S.h3(AppColors.success)),
                      ],
                    ),
                  ).animate(delay: 200.ms).fadeIn(duration: 500.ms),

                const SizedBox(height: 16),
              ]),
            ),
          ),
        ],
      ),
    );
  }
}

// ── Info Row ─────────────────────────────────────────────
class _InfoRow extends StatelessWidget {
  final IconData icon;
  final String text;
  final Widget? action;
  const _InfoRow({required this.icon, required this.text, this.action});

  @override
  Widget build(BuildContext context) {
    return Row(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Icon(icon, size: 16, color: AppColors.primary),
        const SizedBox(width: 10),
        Expanded(
          child: Text(text, style: S.bodySm()),
        ),
        if (action != null) action!,
      ],
    );
  }
}

// ── Clock Node ───────────────────────────────────────────
class _ClockNode extends StatelessWidget {
  final String label;
  final String time;
  final IconData icon;
  final bool active;
  final Color color;
  const _ClockNode({
    required this.label, required this.time, required this.icon,
    required this.active, required this.color,
  });

  @override
  Widget build(BuildContext context) {
    return Column(
      children: [
        Container(
          width: 52, height: 52,
          decoration: BoxDecoration(
            shape: BoxShape.circle,
            color: active ? color.withOpacity(0.15) : AppColors.bg,
            border: Border.all(
              color: active ? color : AppColors.border,
              width: active ? 2.5 : 1.5,
            ),
            boxShadow: active
                ? [BoxShadow(color: color.withOpacity(0.2),
                    blurRadius: 12, spreadRadius: 1)]
                : [],
          ),
          child: Icon(icon, color: active ? color : AppColors.textMuted, size: 24),
        ),
        const SizedBox(height: 6),
        Text(label, style: S.label()),
        Text(time,
            style: active ? S.h3(color) : S.h3(AppColors.textMuted)),
      ],
    );
  }
}

// ── Catatan + Foto Bottom Sheet ──────────────────────────
class _CatatanSheet extends StatefulWidget {
  final TextEditingController ctrl;
  final ImagePicker picker;
  final ValueChanged<XFile?> onPhotoCapture;
  final ValueChanged<String> onSubmit;
  const _CatatanSheet({
    required this.ctrl,
    required this.picker,
    required this.onPhotoCapture,
    required this.onSubmit,
  });

  @override
  State<_CatatanSheet> createState() => _CatatanSheetState();
}

class _CatatanSheetState extends State<_CatatanSheet> {
  XFile? _photo;

  Future<void> _pickPhoto(ImageSource source) async {
    Navigator.pop(context); // close source picker
    final f = await widget.picker.pickImage(
        source: source, imageQuality: 75, maxWidth: 1280);
    if (f != null && mounted) {
      setState(() => _photo = f);
      widget.onPhotoCapture(f);
    }
  }

  void _showSourcePicker() {
    showModalBottomSheet(
      context: context,
      backgroundColor: AppColors.cardAlt,
      shape: const RoundedRectangleBorder(
          borderRadius: BorderRadius.vertical(top: Radius.circular(20))),
      builder: (_) => SafeArea(
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            const SizedBox(height: 8),
            Container(width: 40, height: 4,
                decoration: BoxDecoration(color: AppColors.border,
                    borderRadius: BorderRadius.circular(2))),
            const SizedBox(height: 16),
            ListTile(
              leading: Container(
                padding: const EdgeInsets.all(8),
                decoration: BoxDecoration(
                  color: AppColors.primary.withOpacity(0.12),
                  borderRadius: BorderRadius.circular(10),
                ),
                child: const Icon(Icons.camera_alt_rounded, color: AppColors.primary),
              ),
              title: Text('Ambil Foto', style: S.bodyLg()),
              subtitle: Text('Gunakan kamera', style: S.caption()),
              onTap: () => _pickPhoto(ImageSource.camera),
            ),
            ListTile(
              leading: Container(
                padding: const EdgeInsets.all(8),
                decoration: BoxDecoration(
                  color: AppColors.info.withOpacity(0.12),
                  borderRadius: BorderRadius.circular(10),
                ),
                child: const Icon(Icons.photo_library_rounded, color: AppColors.info),
              ),
              title: Text('Pilih dari Galeri', style: S.bodyLg()),
              subtitle: Text('Dari penyimpanan', style: S.caption()),
              onTap: () => _pickPhoto(ImageSource.gallery),
            ),
            const SizedBox(height: 16),
          ],
        ),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: EdgeInsets.only(
        left: 20, right: 20, top: 16,
        bottom: MediaQuery.of(context).viewInsets.bottom + 24,
      ),
      child: Column(
        mainAxisSize: MainAxisSize.min,
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          // Handle
          Center(
            child: Container(
              width: 40, height: 4,
              decoration: BoxDecoration(
                  color: AppColors.border,
                  borderRadius: BorderRadius.circular(2)),
            ),
          ),
          const SizedBox(height: 20),

          Text('Selesaikan Kunjungan', style: S.h2()),
          const SizedBox(height: 4),
          Text('Isi catatan dan opsional foto dokumentasi',
              style: S.caption()),
          const SizedBox(height: 20),

          // Catatan field
          Text('Catatan Kunjungan *', style: S.micro(AppColors.textSecondary)),
          const SizedBox(height: 6),
          TextField(
            controller: widget.ctrl,
            maxLines: 4,
            style: S.body(AppColors.textPrimary),
            decoration: const InputDecoration(
              hintText: 'Tuliskan hasil atau catatan kunjungan...',
            ),
          ),
          const SizedBox(height: 16),

          // Photo section
          Text('Foto Dokumentasi (Opsional)',
              style: S.micro(AppColors.textSecondary)),
          const SizedBox(height: 8),

          if (_photo != null) ...[
            // Photo preview
            Stack(
              children: [
                ClipRRect(
                  borderRadius: BorderRadius.circular(12),
                  child: Image.file(
                    File(_photo!.path),
                    height: 140,
                    width: double.infinity,
                    fit: BoxFit.cover,
                  ),
                ),
                Positioned(
                  top: 8, right: 8,
                  child: GestureDetector(
                    onTap: () => setState(() => _photo = null),
                    child: Container(
                      padding: const EdgeInsets.all(4),
                      decoration: BoxDecoration(
                        color: AppColors.error,
                        borderRadius: BorderRadius.circular(20),
                      ),
                      child: const Icon(Icons.close, color: Colors.white, size: 16),
                    ),
                  ),
                ),
              ],
            ),
            const SizedBox(height: 8),
            GestureDetector(
              onTap: _showSourcePicker,
              child: Text('Ganti Foto',
                  style: S.caption(AppColors.primaryLight)),
            ),
          ] else
            GestureDetector(
              onTap: _showSourcePicker,
              child: Container(
                height: 80,
                decoration: BoxDecoration(
                  color: AppColors.bg,
                  borderRadius: BorderRadius.circular(12),
                  border: Border.all(
                      color: AppColors.border, style: BorderStyle.solid),
                ),
                child: Column(
                  mainAxisAlignment: MainAxisAlignment.center,
                  children: [
                    const Icon(Icons.add_a_photo_outlined,
                        color: AppColors.textMuted, size: 24),
                    const SizedBox(height: 6),
                    Text('Tambah Foto', style: S.caption()),
                  ],
                ),
              ),
            ),

          const SizedBox(height: 20),

          // Submit
          GestureDetector(
            onTap: () {
              final catatan = widget.ctrl.text.trim();
              if (catatan.isEmpty) {
                ScaffoldMessenger.of(context).showSnackBar(
                  SnackBar(
                    content: Text('Catatan wajib diisi',
                        style: S.body(AppColors.textPrimary)),
                    backgroundColor: AppColors.error,
                  ),
                );
                return;
              }
              widget.onSubmit(catatan);
            },
            child: Container(
              width: double.infinity,
              height: 52,
              decoration: AppTheme.btnDeco(radius: 14),
              child: Row(
                mainAxisAlignment: MainAxisAlignment.center,
                children: [
                  const Icon(Icons.check_rounded, color: Colors.white, size: 20),
                  const SizedBox(width: 8),
                  Text('Konfirmasi Clock Out', style: S.btn()),
                ],
              ),
            ),
          ),
        ],
      ),
    );
  }
}
