import 'package:flutter/material.dart';
import 'package:iconsax/iconsax.dart';
import 'package:provider/provider.dart';
import 'package:tutorial_coach_mark/tutorial_coach_mark.dart';

import '../../page/Pinjam_Barang/CreatePinjamBarang.dart';
import '../../page/Pinjam_Barang/PinjamanAktifPage.dart';
import '../../page/Pinjam_Barang/PinjamanRiwayatPage.dart';
import '../../service/provider/Pinjam/PinjamGetProvider.dart';
import '../../service/provider/preferences/PreferencesIDProvider.dart';
import '../../widget/CoachMarkHelper.dart';

class ContainerPinjamBarang extends StatefulWidget {
  static const routeName = '/container_pinjam_barang';

  const ContainerPinjamBarang({super.key});

  @override
  State<ContainerPinjamBarang> createState() => _ContainerPinjamBarangState();
}

class _ContainerPinjamBarangState extends State<ContainerPinjamBarang> {
  // Modern color scheme
  static const Color _primaryBlue = Color(0xFF2563EB);
  static const Color _textPrimary = Color(0xFF1F2937);

  String _id = '';

  // Coach Mark Keys
  final GlobalKey _keyFab = GlobalKey();
  final GlobalKey _keyTabs = GlobalKey();
  bool _coachMarkShown = false;

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      if (!mounted) return;
      _id = context.read<PreferencesIDProvider>().isUserRole;
      if (_id.isNotEmpty) {
        Provider.of<PinjamGetProvider>(context, listen: false).getPinjam(_id);
      }
      _showPinjamCoachMark();
    });
  }

  void _refreshData() {
    if (_id.isNotEmpty) {
      Provider.of<PinjamGetProvider>(context, listen: false).getPinjam(_id);
    }
  }

  @override
  Widget build(BuildContext context) {
    return DefaultTabController(
      length: 2,
      child: Scaffold(
        backgroundColor: const Color(0xFFF9FAFB),
        floatingActionButton: FloatingActionButton(
          key: _keyFab,
          heroTag: 'fab_add_pinjam',
          onPressed: () async {
            final result = await Navigator.pushNamed(
              context,
              PinjamBarangPage.routeName,
            );
            if (result == true) {
              _refreshData();
            }
          },
          backgroundColor: _primaryBlue,
          elevation: 4,
          shape: RoundedRectangleBorder(
            borderRadius: BorderRadius.circular(16),
          ),
          child: const Icon(Icons.add, color: Colors.white, size: 28),
        ),
        appBar: AppBar(
          backgroundColor: Colors.white,
          surfaceTintColor: Colors.transparent,
          elevation: 0,
          leading: Container(
            margin: const EdgeInsets.only(left: 8),
            child: IconButton(
              onPressed: () => Scaffold.of(context).openDrawer(),
              icon: const Icon(Iconsax.menu_1, color: _textPrimary, size: 22),
              tooltip: 'Menu',
            ),
          ),
          title: const Text(
            'Peminjaman Barang',
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
          bottom: TabBar(
            key: _keyTabs,
            isScrollable: false,
            indicatorColor: _primaryBlue,
            indicatorWeight: 3,
            indicatorSize: TabBarIndicatorSize.label,
            labelStyle: const TextStyle(
              fontFamily: 'Poppins',
              fontSize: 14,
              fontWeight: FontWeight.w600,
            ),
            unselectedLabelStyle: const TextStyle(
              fontFamily: 'Poppins',
              fontSize: 14,
              fontWeight: FontWeight.w500,
            ),
            labelColor: _primaryBlue,
            unselectedLabelColor: const Color(0xFF9CA3AF),
            tabs: const [
              Tab(
                icon: Icon(Icons.assignment_turned_in_rounded, size: 22),
                iconMargin: EdgeInsets.only(bottom: 4),
                text: 'Pinjaman Aktif',
              ),
              Tab(
                icon: Icon(Icons.history_rounded, size: 22),
                iconMargin: EdgeInsets.only(bottom: 4),
                text: 'Riwayat',
              ),
            ],
          ),
        ),
        body: const TabBarView(
          children: <Widget>[
            PinjamanAktifPage(),
            PinjamanRiwayatPage(),
          ],
        ),
      ),
    );
  }

  // ─── Coach Mark for PinjamBarang ──────────────────
  Future<void> _showPinjamCoachMark() async {
    await Future.delayed(const Duration(milliseconds: 2000));
    if (!mounted || _coachMarkShown) return;
    _coachMarkShown = true;

    final targets = <TargetFocus>[
      TargetFocus(
        identify: 'tabs',
        keyTarget: _keyTabs,
        alignSkip: Alignment.bottomRight,
        enableOverlayTab: true,
        shape: ShapeLightFocus.RRect,
        radius: 0,
        contents: [
          TargetContent(
            align: ContentAlign.bottom,
            builder: (context, controller) => CoachMarkHelper.buildTooltip(
              icon: '📦', title: 'Tab Peminjaman',
              descriptions: [
                '✅ Pinjaman Aktif — Barang yang sedang dipinjam',
                '📜 Riwayat — Barang yang sudah dikembalikan',
              ],
              step: '1 / 2',
            ),
          ),
        ],
      ),
      TargetFocus(
        identify: 'fab_add',
        keyTarget: _keyFab,
        alignSkip: Alignment.topLeft,
        enableOverlayTab: true,
        shape: ShapeLightFocus.RRect,
        radius: 16,
        contents: [
          TargetContent(
            align: ContentAlign.top,
            builder: (context, controller) => CoachMarkHelper.buildTooltip(
              icon: '➕', title: 'Tambah Peminjaman',
              descriptions: [
                'Tap tombol ini untuk mengajukan peminjaman baru',
                'Pilih barang, isi jumlah, lalu submit',
              ],
              note: 'Pastikan kembalikan barang tepat waktu!',
              step: '2 / 2',
            ),
          ),
        ],
      ),
    ];
    CoachMarkHelper.show(context: context, targets: targets);
  }
}
