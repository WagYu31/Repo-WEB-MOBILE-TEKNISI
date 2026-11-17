import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:iconsax/iconsax.dart';
import 'package:provider/provider.dart';
import 'package:tutorial_coach_mark/tutorial_coach_mark.dart';

import '../../page/Pinjam_Barang/CreatePinjamBarang.dart';
import '../../page/Pinjam_Barang/PinjamanAktifPage.dart';
import '../../page/Pinjam_Barang/PinjamanRiwayatPage.dart';
import '../../service/provider/Pinjam/PinjamGetProvider.dart';
import '../../service/provider/preferences/PreferencesIDProvider.dart';
import '../../utils/state.dart';
import '../../widget/CoachMarkHelper.dart';

class ContainerPinjamBarang extends StatefulWidget {
  static const routeName = '/container_pinjam_barang';

  const ContainerPinjamBarang({super.key});

  @override
  State<ContainerPinjamBarang> createState() => _ContainerPinjamBarangState();
}

class _ContainerPinjamBarangState extends State<ContainerPinjamBarang> {
  // ─── Parclgo Inspired Palette (Matching Gambar 5) ───
  static const Color _darkHeader = Color(0xFF11141B);
  static const Color _darkSurface = Color(0xFF1E2330);
  static const Color _primaryOrange = Color(0xFFFF5B22);
  static const Color _bgCanvas = Color(0xFFF4F6F8);
  static const Color _textPrimary = Color(0xFF0F172A);

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
    final parentScaffold = Scaffold.maybeOf(context);

    return AnnotatedRegion<SystemUiOverlayStyle>(
      value: SystemUiOverlayStyle.light,
      child: DefaultTabController(
        length: 2,
        child: Scaffold(
          backgroundColor: _bgCanvas,
          floatingActionButton: Container(
            key: _keyFab,
            decoration: BoxDecoration(
              gradient: const LinearGradient(
                colors: [Color(0xFFFF5B22), Color(0xFFFF7A00)],
                begin: Alignment.topLeft,
                end: Alignment.bottomRight,
              ),
              borderRadius: BorderRadius.circular(18),
              boxShadow: [
                BoxShadow(
                  color: _primaryOrange.withValues(alpha: 0.38),
                  blurRadius: 16,
                  offset: const Offset(0, 6),
                ),
              ],
            ),
            child: Material(
              color: Colors.transparent,
              child: InkWell(
                borderRadius: BorderRadius.circular(18),
                onTap: () async {
                  final result = await Navigator.pushNamed(
                    context,
                    PinjamBarangPage.routeName,
                  );
                  if (result == true) {
                    _refreshData();
                  }
                },
                child: const Padding(
                  padding: EdgeInsets.symmetric(horizontal: 18, vertical: 14),
                  child: Row(
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      Icon(Icons.add_rounded, color: Colors.white, size: 22),
                      SizedBox(width: 6),
                      Text(
                        'Pinjam Alat',
                        style: TextStyle(
                          fontFamily: 'Poppins',
                          fontSize: 13.5,
                          fontWeight: FontWeight.w700,
                          color: Colors.white,
                        ),
                      ),
                    ],
                  ),
                ),
              ),
            ),
          ),
          body: Column(
            children: [
              _buildParclgoHeader(parentScaffold),
              const Expanded(
                child: TabBarView(
                  children: <Widget>[
                    PinjamanAktifPage(),
                    PinjamanRiwayatPage(),
                  ],
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildParclgoHeader(ScaffoldState? parentScaffold) {
    return Container(
      decoration: const BoxDecoration(
        color: _darkHeader,
        borderRadius: BorderRadius.only(
          bottomLeft: Radius.circular(32),
          bottomRight: Radius.circular(32),
        ),
      ),
      child: SafeArea(
        bottom: false,
        child: Padding(
          padding: const EdgeInsets.fromLTRB(20, 12, 20, 20),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              // ── Top Row: Menu + Title + Refresh ──
              Row(
                children: [
                  GestureDetector(
                    onTap: () => parentScaffold?.openDrawer(),
                    child: Container(
                      width: 44,
                      height: 44,
                      decoration: BoxDecoration(
                        color: _darkSurface,
                        shape: BoxShape.circle,
                        border: Border.all(
                          color: Colors.white.withValues(alpha: 0.1),
                        ),
                      ),
                      child: const Icon(
                        Iconsax.menu_1,
                        color: Colors.white,
                        size: 20,
                      ),
                    ),
                  ),
                  const SizedBox(width: 14),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          'Inventaris Teknisi',
                          style: TextStyle(
                            fontFamily: 'Poppins',
                            fontSize: 11.5,
                            fontWeight: FontWeight.w500,
                            color: Colors.white.withValues(alpha: 0.65),
                          ),
                        ),
                        const Text(
                          'Peminjaman Barang',
                          style: TextStyle(
                            fontFamily: 'Poppins',
                            fontSize: 19,
                            fontWeight: FontWeight.w700,
                            color: Colors.white,
                            letterSpacing: -0.2,
                          ),
                        ),
                      ],
                    ),
                  ),
                  GestureDetector(
                    onTap: _refreshData,
                    child: Container(
                      width: 44,
                      height: 44,
                      decoration: BoxDecoration(
                        color: _darkSurface,
                        shape: BoxShape.circle,
                        border: Border.all(
                          color: Colors.white.withValues(alpha: 0.1),
                        ),
                      ),
                      child: const Icon(
                        Icons.refresh_rounded,
                        color: Colors.white,
                        size: 21,
                      ),
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 18),

              // ── Segmented Pill TabBar with Live Counts ──
              Consumer<PinjamGetProvider>(
                builder: (context, state, _) {
                  int activeCount = 0;
                  int historyCount = 0;
                  if (state.state == ResultState.hasData) {
                    for (final item in state.response.data) {
                      final st = item.status.toLowerCase();
                      if (st == 'diajukan' || st == 'dipinjam' || st == 'pengembalian') {
                        activeCount++;
                      } else if (st == 'ditolak' || st == 'selesai') {
                        historyCount++;
                      }
                    }
                  }

                  return Container(
                    key: _keyTabs,
                    height: 48,
                    padding: const EdgeInsets.all(4),
                    decoration: BoxDecoration(
                      color: _darkSurface,
                      borderRadius: BorderRadius.circular(24),
                      border: Border.all(
                        color: Colors.white.withValues(alpha: 0.08),
                      ),
                    ),
                    child: TabBar(
                      isScrollable: false,
                      dividerColor: Colors.transparent,
                      indicatorSize: TabBarIndicatorSize.tab,
                      indicator: BoxDecoration(
                        color: Colors.white,
                        borderRadius: BorderRadius.circular(20),
                        boxShadow: [
                          BoxShadow(
                            color: Colors.black.withValues(alpha: 0.12),
                            blurRadius: 8,
                            offset: const Offset(0, 2),
                          ),
                        ],
                      ),
                      labelColor: _textPrimary,
                      unselectedLabelColor: Colors.white.withValues(alpha: 0.7),
                      labelStyle: const TextStyle(
                        fontFamily: 'Poppins',
                        fontSize: 12.5,
                        fontWeight: FontWeight.w700,
                      ),
                      unselectedLabelStyle: const TextStyle(
                        fontFamily: 'Poppins',
                        fontSize: 12.5,
                        fontWeight: FontWeight.w500,
                      ),
                      tabs: [
                        Tab(
                          child: Row(
                            mainAxisAlignment: MainAxisAlignment.center,
                            children: [
                              const Icon(Iconsax.box_tick, size: 16),
                              const SizedBox(width: 6),
                              const Flexible(
                                child: Text(
                                  'Pinjaman Aktif',
                                  overflow: TextOverflow.ellipsis,
                                ),
                              ),
                              if (activeCount > 0) ...[
                                const SizedBox(width: 6),
                                Container(
                                  padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 1),
                                  decoration: BoxDecoration(
                                    color: _primaryOrange,
                                    borderRadius: BorderRadius.circular(10),
                                  ),
                                  child: Text(
                                    '$activeCount',
                                    style: const TextStyle(
                                      fontFamily: 'Poppins',
                                      fontSize: 10,
                                      fontWeight: FontWeight.w700,
                                      color: Colors.white,
                                    ),
                                  ),
                                ),
                              ],
                            ],
                          ),
                        ),
                        Tab(
                          child: Row(
                            mainAxisAlignment: MainAxisAlignment.center,
                            children: [
                              const Icon(Iconsax.clock, size: 16),
                              const SizedBox(width: 6),
                              const Flexible(
                                child: Text(
                                  'Riwayat',
                                  overflow: TextOverflow.ellipsis,
                                ),
                              ),
                              if (historyCount > 0) ...[
                                const SizedBox(width: 6),
                                Container(
                                  padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 1),
                                  decoration: BoxDecoration(
                                    color: const Color(0xFF334155),
                                    borderRadius: BorderRadius.circular(10),
                                  ),
                                  child: Text(
                                    '$historyCount',
                                    style: const TextStyle(
                                      fontFamily: 'Poppins',
                                      fontSize: 10,
                                      fontWeight: FontWeight.w700,
                                      color: Colors.white,
                                    ),
                                  ),
                                ),
                              ],
                            ],
                          ),
                        ),
                      ],
                    ),
                  );
                },
              ),
            ],
          ),
        ),
      ),
    );
  }

  // ─── Coach Mark for PinjamBarang ──────────────────
  Future<void> _showPinjamCoachMark() async {
    await Future.delayed(const Duration(milliseconds: 2000));
    if (!mounted || _coachMarkShown || CoachMarkHelper.isActive) return;
    _coachMarkShown = true;

    final targets = <TargetFocus>[
      TargetFocus(
        identify: 'tabs',
        keyTarget: _keyTabs,
        alignSkip: Alignment.bottomRight,
        enableOverlayTab: true,
        shape: ShapeLightFocus.RRect,
        radius: 24,
        contents: [
          TargetContent(
            align: ContentAlign.bottom,
            builder: (context, controller) => CoachMarkHelper.buildTooltip(
              onClose: () => controller.skip(),
              title: 'Tab Peminjaman',
              descriptions: ['Aktif = sedang dipinjam. Riwayat = sudah dikembalikan'],
              arrowUp: true,
              step: '1/2',
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
        radius: 18,
        contents: [
          TargetContent(
            align: ContentAlign.top,
            builder: (context, controller) => CoachMarkHelper.buildTooltip(
              onClose: () => controller.skip(),
              title: 'Ajukan Peminjaman',
              descriptions: ['Tap untuk ajukan peminjaman barang baru'],
              step: '2/2',
            ),
          ),
        ],
      ),
    ];
    CoachMarkHelper.show(context: context, targets: targets, key: 'pinjam_barang');
  }
}
