import 'package:flutter/material.dart';
import 'package:iconsax/iconsax.dart';
import 'package:provider/provider.dart';
import 'package:timezone/data/latest.dart' as tz;
import 'package:tutorial_coach_mark/tutorial_coach_mark.dart';

import '../../service/provider/Task/DetailTaskGetProvider.dart';
import '../../service/provider/Pencapaian/PencapaianProvider.dart';
import '../../service/provider/preferences/PreferencesIDProvider.dart';
import '../../service/model/task/TaskAllResponse.dart';
import '../../utils/state.dart';
import '../../widget/CardTask.dart';
import '../../widget/CoachMarkHelper.dart';
import '../../service/notification/NotificationService.dart';

class DashboardPage extends StatefulWidget {
  final Function(int)? onNavigate;

  const DashboardPage({super.key, this.onNavigate});

  @override
  State<DashboardPage> createState() => _DashboardPageState();
}

class _DashboardPageState extends State<DashboardPage> {
  // ─── Energetic Color Palette ─────────────────────
  static const Color _brandBlue = Color(0xFF1E40AF);
  static const Color _brandCyan = Color(0xFF0891B2);
  static const Color _skyBlue = Color(0xFF0EA5E9);
  static const Color _teal = Color(0xFF14B8A6);
  static const Color _warmOrange = Color(0xFFF97316);
  static const Color _indigo = Color(0xFF6366F1);
  static const Color _bgColor = Color(0xFFF4F6F8);
  static const Color _textPrimary = Color(0xFF1E293B);
  static const Color _textSecondary = Color(0xFF64748B);

  static const List<String> _motivationMessages = [
    "Selesaikan tugas, bonus datang dengan cepat!",
    "Teknisi hebat, bonus sudah di depan!",
    "Kerja cepat, bonus berlipat, semangat!",
    "Tugas selesai, dompet tebal, ayo maju!",
    "Lembur dikit, bonus naik, ayo semangat!",
    "Bonus menanti, tinggal satu tugas lagi!",
    "Tugas kelar, bonus besar, tinggal jalan!",
    "Semangat tugas, bonus datang tanpa permisi!",
    "Kerja santai, bonus datang tanpa disadari!",
    "Cepat kelar, bonus bisa buat liburan!",
  ];

  String? _teknisiId;
  int? _cachedTeknisiIdParsed;
  bool _isRefreshing = false;
  List<DataTask> _orderedTasks = [];
  List<int> _lastDataIds = [];

  // ─── Coach Mark Keys ──────────────────────────
  final GlobalKey _keyStatCard = GlobalKey();
  final GlobalKey _keyTugasAktif = GlobalKey();
  final GlobalKey _keyFirstTask = GlobalKey();
  final GlobalKey _keyDragHandle = GlobalKey();
  bool _coachMarkShown = false;

  @override
  void initState() {
    super.initState();
    tz.initializeTimeZones();

    WidgetsBinding.instance.addPostFrameCallback((_) {
      _loadInitialData();
    });
    _checkFirstLaunch();
  }

  Future<void> _loadInitialData() async {
    final idProvider = Provider.of<PreferencesIDProvider>(context, listen: false);
    await idProvider.getUserRolePreferences();
    _teknisiId = idProvider.isUserRole;
    if (_teknisiId != null && _teknisiId!.isNotEmpty) {
      Provider.of<DetailTaskGetProvider>(context, listen: false).getTask(_teknisiId!);
      // Load statistik data
      final teknisiIdInt = int.tryParse(_teknisiId!);
      if (teknisiIdInt != null) {
        final now = DateTime.now();
        Provider.of<PencapaianProvider>(context, listen: false).loadAll(
          teknisiId: teknisiIdInt,
          bulan: now.month,
          tahun: now.year,
        );
      }

      // ─── Register push notification worker ───
      NotificationService().registerPeriodicCheck(_teknisiId!);
      // Check pending reports immediately
      NotificationService().checkNow(_teknisiId!);
    }
  }

  // ─── Coach Mark (Always Show on Open) ───────────
  Future<void> _checkFirstLaunch() async {
    await Future.delayed(const Duration(milliseconds: 2500));
    if (mounted && !_coachMarkShown && !CoachMarkHelper.isActive) {
      _coachMarkShown = true;
      _showCoachMark();
    }
  }

  void _showCoachMark() {
    final targets = <TargetFocus>[
      TargetFocus(
        identify: 'stat_card',
        keyTarget: _keyStatCard,
        alignSkip: Alignment.bottomRight,
        enableOverlayTab: true,
        shape: ShapeLightFocus.RRect,
        radius: 20,
        contents: [
          TargetContent(
            align: ContentAlign.bottom,
            builder: (context, controller) => CoachMarkHelper.buildTooltip(
              onClose: () => controller.skip(),
              title: 'Ringkasan Kinerja',
              descriptions: ['Lihat kegiatan, pendapatan, bonus & target kamu di sini'],
              arrowUp: true,
              step: '1/4',
            ),
          ),
        ],
      ),
      TargetFocus(
        identify: 'tugas_aktif',
        keyTarget: _keyTugasAktif,
        alignSkip: Alignment.bottomRight,
        enableOverlayTab: true,
        shape: ShapeLightFocus.RRect,
        radius: 12,
        contents: [
          TargetContent(
            align: ContentAlign.bottom,
            builder: (context, controller) => CoachMarkHelper.buildTooltip(
              onClose: () => controller.skip(),
              title: 'Tugas Aktif',
              descriptions: ['Daftar semua tugas yang ditugaskan. Tarik ke bawah untuk refresh'],
              arrowUp: true,
              step: '2/4',
            ),
          ),
        ],
      ),
      TargetFocus(
        identify: 'first_task',
        keyTarget: _keyFirstTask,
        alignSkip: Alignment.bottomRight,
        enableOverlayTab: true,
        shape: ShapeLightFocus.RRect,
        radius: 16,
        contents: [
          TargetContent(
            align: ContentAlign.bottom,
            builder: (context, controller) => CoachMarkHelper.buildTooltip(
              onClose: () => controller.skip(),
              title: 'Kartu Tugas',
              descriptions: ['Tap kartu untuk buka detail & mulai tugas'],
              arrowUp: true,
              step: '3/4',
            ),
          ),
        ],
      ),
      TargetFocus(
        identify: 'drag_handle',
        keyTarget: _keyDragHandle,
        alignSkip: Alignment.bottomRight,
        enableOverlayTab: true,
        shape: ShapeLightFocus.RRect,
        radius: 12,
        contents: [
          TargetContent(
            align: ContentAlign.bottom,
            builder: (context, controller) => CoachMarkHelper.buildTooltip(
              onClose: () => controller.skip(),
              title: 'Urutkan Tugas',
              descriptions: ['Tahan & geser titik ini untuk ubah urutan prioritas'],
              arrowUp: true,
              step: '4/4',
            ),
          ),
        ],
      ),
    ];
    CoachMarkHelper.show(context: context, targets: targets);
  }


  Future<void> _onRefresh() async {
    setState(() => _isRefreshing = true);
    if (_teknisiId != null && _teknisiId!.isNotEmpty) {
      final futures = <Future>[
        Provider.of<DetailTaskGetProvider>(context, listen: false).getTask(_teknisiId!),
      ];
      // Also refresh stats
      final teknisiIdInt = int.tryParse(_teknisiId!);
      if (teknisiIdInt != null) {
        final now = DateTime.now();
        futures.add(
          Provider.of<PencapaianProvider>(context, listen: false).loadAll(
            teknisiId: teknisiIdInt,
            bulan: now.month,
            tahun: now.year,
          ),
        );
      }
      await Future.wait(futures);
    } else {
      await _loadInitialData();
    }
    setState(() => _isRefreshing = false);
  }

  String _getMotivation() {
    final day = DateTime.now().day;
    final index = (day - 1) % _motivationMessages.length;
    return _motivationMessages[index];
  }

  int? _parseTeknisiId(String id) {
    if (_cachedTeknisiIdParsed != null) return _cachedTeknisiIdParsed;
    _cachedTeknisiIdParsed = int.tryParse(id);
    return _cachedTeknisiIdParsed;
  }

  String _getGreeting() {
    final hour = DateTime.now().hour;
    if (hour < 12) return 'Selamat Pagi';
    if (hour < 15) return 'Selamat Siang';
    if (hour < 18) return 'Selamat Sore';
    return 'Selamat Malam';
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: _bgColor,
      body: Consumer<PreferencesIDProvider>(
          builder: (context, preferencesIDProvider, child) {
            if (preferencesIDProvider.state == ResultState.loading) {
              return _buildLoadingState();
            } else if (preferencesIDProvider.state == ResultState.hasData) {
              final id = preferencesIDProvider.isUserRole;
              final teknisiIdParsed = _parseTeknisiId(id);

              return Consumer<DetailTaskGetProvider>(
                builder: (context, state, child) {
                  if (state.state == ResultState.loading) {
                    return _buildLoadingState();
                  } else if (state.state == ResultState.noData) {
                    return _buildEmptyState('Tidak ada tugas');
                  } else if (state.state == ResultState.hasData) {
                    final filteredData = state.response.data.expand((task) {
                      if (teknisiIdParsed == null) return [task];

                      // Cek apakah teknisi ini ditugaskan ke task ini (via dataTeknisi / team_kegiatan)
                      final isAssigned = task.dataTeknisi.any((t) => t.teknisiId == teknisiIdParsed);
                      // Fallback: jika ada pelaksanaan milik teknisi ini, berarti pasti ditugaskan
                      final hasPelaksanaan = task.pelaksanaan.any((p) => p.teknisiId == teknisiIdParsed);
                      if (!isAssigned && !hasPelaksanaan) return <DataTask>[]; // Bukan tugasnya

                      // Teknisi ditugaskan. Cek pelaksanaan:
                      if (task.pelaksanaan.isEmpty) {
                        return [task]; // Belum ada pelaksanaan tapi ditugaskan, tampilkan
                      }

                      // Ada pelaksanaan, cek milik teknisi ini
                      final myPelaksanaan = task.pelaksanaan
                          .where((data) => data.teknisiId == teknisiIdParsed);

                      // Jika teknisi ini belum punya pelaksanaan sama sekali (belum clock in),
                      // tetap tampilkan task karena dia ditugaskan
                      if (myPelaksanaan.isEmpty) return [task];

                      // Jika sudah punya pelaksanaan, filter yang masih aktif
                      // Untuk Ketua: tetap tampilkan 'selesai' yang belum upload laporan
                      // Untuk Anggota: selesai = hilang (anggota tidak perlu upload laporan)
                      final myTeknisiData = task.dataTeknisi.where((t) => t.teknisiId == teknisiIdParsed);
                      final bool isKetua = task.dataTeknisi.length == 1 || 
                          (myTeknisiData.isNotEmpty && myTeknisiData.first.isKetua == 1);

                      final myActivePelaksanaan = myPelaksanaan
                          .where((data) {
                            if (data.status != 'selesai' && data.status != 'Lanjut Nanti') return true;
                            // Hanya Ketua yang perlu upload laporan
                            if (data.status == 'selesai' && isKetua) {
                              final hasReport = (data.permasalahan != null && 
                                  data.permasalahan.toString().trim().isNotEmpty) ||
                                  data.image1 != null;
                              return !hasReport; // Tampilkan jika laporan kosong
                            }
                            return false;
                          });

                      return myActivePelaksanaan.isNotEmpty ? [task] : <DataTask>[];
                    }).toList();

                    if (filteredData.isEmpty) {
                      return _buildEmptyState('Belum ada tugas untuk kamu');
                    }

                    // Sync reorder state with fresh data
                    final newIds = filteredData.map((t) => t.id).toList();
                    if (!_listEquals(newIds, _lastDataIds)) {
                      _lastDataIds = newIds;
                      _orderedTasks = List<DataTask>.from(filteredData);
                    }

                    return _buildTaskList(_orderedTasks);
                  } else {
                    return _buildErrorState(state.message);
                  }
                },
              );
            } else {
              return _buildErrorState('Gagal memuat data user role');
            }
          },
        ),
    );
  }

  Widget _buildLoadingState() {
    return Center(
      child: Column(
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          Container(
            padding: const EdgeInsets.all(16),
            decoration: BoxDecoration(
              color: Colors.white,
              borderRadius: BorderRadius.circular(16),
              boxShadow: [
                BoxShadow(
                  color: Colors.black.withValues(alpha: 0.05),
                  blurRadius: 20,
                ),
              ],
            ),
            child: const CircularProgressIndicator(
              color: _skyBlue,
              strokeWidth: 3,
            ),
          ),
          const SizedBox(height: 20),
          const Text(
            'Memuat data...',
            style: TextStyle(
              fontFamily: 'Poppins',
              fontSize: 14,
              color: _textSecondary,
            ),
          ),
        ],
      ),
    );
  }

  bool _listEquals(List<int> a, List<int> b) {
    if (a.length != b.length) return false;
    for (int i = 0; i < a.length; i++) {
      if (a[i] != b[i]) return false;
    }
    return true;
  }

  void _onReorder(int oldIndex, int newIndex) {
    setState(() {
      if (oldIndex < newIndex) newIndex -= 1;
      final item = _orderedTasks.removeAt(oldIndex);
      _orderedTasks.insert(newIndex, item);
      _lastDataIds = _orderedTasks.map((t) => t.id).toList();
    });
  }

  Widget _buildTaskList(List<DataTask> filteredData) {
    return RefreshIndicator(
      onRefresh: _onRefresh,
      color: Colors.white,
      backgroundColor: _brandBlue,
      displacement: 60,
      child: CustomScrollView(
        physics: const BouncingScrollPhysics(parent: AlwaysScrollableScrollPhysics()),
        slivers: [
          // Header + floating stats combined
          SliverToBoxAdapter(
            child: _buildHeaderWithStats(filteredData.length),
          ),
          // Task count indicator
          SliverToBoxAdapter(
            child: Padding(
              key: _keyTugasAktif,
              padding: const EdgeInsets.fromLTRB(20, 8, 20, 8),
              child: Row(
                children: [
                  const Text(
                    'Tugas Aktif',
                    style: TextStyle(
                      fontFamily: 'Poppins',
                      fontSize: 15,
                      fontWeight: FontWeight.w700,
                      color: _textPrimary,
                    ),
                  ),
                  const SizedBox(width: 8),
                  Container(
                    padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 2),
                    decoration: BoxDecoration(
                      gradient: const LinearGradient(
                        colors: [Color(0xFF1E40AF), Color(0xFF0891B2)],
                      ),
                      borderRadius: BorderRadius.circular(10),
                    ),
                    child: Text(
                      '${filteredData.length}',
                      style: const TextStyle(
                        fontFamily: 'Poppins',
                        fontSize: 11,
                        fontWeight: FontWeight.w700,
                        color: Colors.white,
                      ),
                    ),
                  ),
                  const Spacer(),
                  Text(
                    '${DateTime.now().day}/${DateTime.now().month}/${DateTime.now().year}',
                    style: TextStyle(
                      fontFamily: 'Poppins',
                      fontSize: 12,
                      color: _textSecondary.withValues(alpha: 0.5),
                    ),
                  ),
                ],
              ),
            ),
          ),
          // Reorderable task list
          SliverPadding(
            padding: const EdgeInsets.fromLTRB(20, 4, 20, 20),
            sliver: SliverReorderableList(
              itemBuilder: (context, index) {
                return ReorderableDelayedDragStartListener(
                  key: ValueKey(filteredData[index].id),
                  index: index,
                  child: RepaintBoundary(
                    child: Padding(
                      key: index == 0 ? _keyFirstTask : null,
                      padding: const EdgeInsets.only(bottom: 12),
                      child: CardTask(
                        data: filteredData[index],
                        history: false,
                        dragHandleKey: index == 0 ? _keyDragHandle : null,
                      ),
                    ),
                  ),
                );
              },
              itemCount: filteredData.length,
              onReorder: _onReorder,
            ),
          ),
        ],
      ),
    );
  }

  // ─── COMBINED HEADER + FLOATING STATS ─────────────
  Widget _buildHeaderWithStats(int taskCount) {
    return Consumer<PencapaianProvider>(
      builder: (context, provider, child) {
        final bool isLoading = provider.pendapatanState == PencapaianState.loading;
        final bool isLoaded = provider.pendapatanState == PencapaianState.loaded;

        final totalKegiatan = provider.pendapatanData?.jumlahKegiatan ?? 0;
        final totalPendapatan = provider.pendapatanData?.totalKeseluruhan ?? 0;
        final bonus = provider.pendapatanData?.bonus ?? 0;
        final target = provider.pendapatanData?.target ?? 0;
        final progress = target > 0
            ? ((totalPendapatan / target) * 100).clamp(0, 999).toInt()
            : 0;

        // Stats card height estimate for overlap
        const double overlapAmount = 50;

        return Column(
          children: [
            // Use Stack to properly overlap
            Stack(
              clipBehavior: Clip.none,
              children: [
                // Header background — extra padding at bottom for overlap
                _buildPremiumHeader(taskCount),
                // Floating stats card positioned at bottom of header
                Positioned(
                  left: 20,
                  right: 20,
                  bottom: -overlapAmount,
                  child: isLoading
                    ? Container(
                        padding: const EdgeInsets.all(20),
                        decoration: BoxDecoration(
                          color: Colors.white,
                          borderRadius: BorderRadius.circular(18),
                          boxShadow: [
                            BoxShadow(
                              color: const Color(0xFF1E40AF).withValues(alpha: 0.1),
                              blurRadius: 20,
                              offset: const Offset(0, 8),
                            ),
                          ],
                        ),
                        child: const Center(
                          child: SizedBox(
                            width: 22, height: 22,
                            child: CircularProgressIndicator(strokeWidth: 2.5, color: Color(0xFF0891B2)),
                          ),
                        ),
                      )
                    : !isLoaded
                      ? const SizedBox.shrink()
                      : Container(
                          key: _keyStatCard,
                          decoration: BoxDecoration(
                            gradient: const LinearGradient(
                              begin: Alignment.topLeft,
                              end: Alignment.bottomRight,
                              colors: [Color(0xFF1E40AF), Color(0xFF0891B2)],
                            ),
                            borderRadius: BorderRadius.circular(20),
                            boxShadow: [
                              BoxShadow(
                                color: const Color(0xFF1E40AF).withValues(alpha: 0.18),
                                blurRadius: 24,
                                offset: const Offset(0, 10),
                              ),
                            ],
                          ),
                          child: Container(
                            margin: const EdgeInsets.all(2),
                            padding: const EdgeInsets.symmetric(horizontal: 4, vertical: 20),
                            decoration: BoxDecoration(
                              color: Colors.white,
                              borderRadius: BorderRadius.circular(18),
                            ),
                            child: Row(
                              children: [
                                _buildStatItem(
                                  emoji: '\uD83D\uDCCB',
                                  value: totalKegiatan.toString(),
                                  label: 'Kegiatan',
                                ),
                                _buildStatDivider(),
                                _buildStatItem(
                                  emoji: '\uD83D\uDCB0',
                                  value: _formatRupiah(totalPendapatan),
                                  label: 'Pendapatan',
                                ),
                                _buildStatDivider(),
                                _buildStatItem(
                                  emoji: '\u2B50',
                                  value: bonus > 0 ? _formatRupiah(bonus) : '-',
                                  label: 'Bonus',
                                ),
                                _buildStatDivider(),
                                _buildStatItem(
                                  emoji: '\uD83C\uDFAF',
                                  value: '$progress%',
                                  label: 'Target',
                                ),
                              ],
                            ),
                          ),
                        ),
                ),
              ],
            ),
            // Space for the overlapping card
            SizedBox(height: overlapAmount + 16),
            // Progress bar
            if (isLoaded)
              Padding(
                padding: const EdgeInsets.fromLTRB(20, 0, 20, 0),
                child: GestureDetector(
                  onTap: () => _navigateToMenu(3),
                  child: Container(
                    padding: const EdgeInsets.all(14),
                    decoration: BoxDecoration(
                      color: Colors.white,
                      borderRadius: BorderRadius.circular(14),
                      boxShadow: [
                        BoxShadow(
                          color: Colors.black.withValues(alpha: 0.03),
                          blurRadius: 8,
                          offset: const Offset(0, 2),
                        ),
                      ],
                    ),
                    child: Column(
                      children: [
                        Row(
                          children: [
                            Text(
                              'Target Bulan Ini',
                              style: TextStyle(
                                fontFamily: 'Poppins',
                                fontSize: 12,
                                fontWeight: FontWeight.w500,
                                color: _textSecondary.withValues(alpha: 0.7),
                              ),
                            ),
                            const Spacer(),
                            Text(
                              '$progress%',
                              style: const TextStyle(
                                fontFamily: 'Poppins',
                                fontSize: 13,
                                fontWeight: FontWeight.w700,
                                color: _textPrimary,
                              ),
                            ),
                          ],
                        ),
                        const SizedBox(height: 10),
                        ClipRRect(
                          borderRadius: BorderRadius.circular(6),
                          child: Stack(
                            children: [
                              Container(
                                height: 8,
                                decoration: BoxDecoration(
                                  color: const Color(0xFFE2E8F0),
                                  borderRadius: BorderRadius.circular(6),
                                ),
                              ),
                              FractionallySizedBox(
                                widthFactor: (progress / 100).clamp(0.0, 1.0),
                                child: Container(
                                  height: 8,
                                  decoration: BoxDecoration(
                                    gradient: const LinearGradient(
                                      colors: [Color(0xFF1E40AF), Color(0xFF0891B2)],
                                    ),
                                    borderRadius: BorderRadius.circular(6),
                                  ),
                                ),
                              ),
                            ],
                          ),
                        ),
                      ],
                    ),
                  ),
                ),
              ),
          ],
        );
      },
    );
  }

  // ─── PREMIUM GRADIENT HEADER ─────────────────────
  Widget _buildPremiumHeader(int taskCount) {
    return Container(
      decoration: const BoxDecoration(
        gradient: LinearGradient(
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
          colors: [
            Color(0xFF1E40AF),
            Color(0xFF0369A1),
            Color(0xFF0891B2),
          ],
          stops: [0.0, 0.5, 1.0],
        ),
        borderRadius: BorderRadius.only(
          bottomLeft: Radius.circular(28),
          bottomRight: Radius.circular(28),
        ),
      ),
      child: SafeArea(
        bottom: false,
        child: Padding(
          padding: const EdgeInsets.fromLTRB(20, 8, 20, 100),
          child: Column(
            children: [
              Row(
                children: [
                  // Menu button
                  Material(
                    color: Colors.white.withValues(alpha: 0.12),
                    borderRadius: BorderRadius.circular(12),
                    child: InkWell(
                      onTap: () => Scaffold.of(context).openDrawer(),
                      borderRadius: BorderRadius.circular(12),
                      child: Container(
                        padding: const EdgeInsets.all(10),
                        child: const Icon(
                          Iconsax.menu_1,
                          color: Colors.white,
                          size: 20,
                        ),
                      ),
                    ),
                  ),
                  const SizedBox(width: 14),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Row(
                          children: [
                            Text(
                              '${_getGreeting()} 🔥',
                              style: TextStyle(
                                fontFamily: 'Poppins',
                                fontSize: 12,
                                color: Colors.white.withValues(alpha: 0.65),
                              ),
                            ),
                          ],
                        ),
                        const Text(
                          'Daftar Kegiatan',
                          style: TextStyle(
                            fontFamily: 'Poppins',
                            fontSize: 22,
                            fontWeight: FontWeight.w700,
                            color: Colors.white,
                            height: 1.2,
                          ),
                        ),
                      ],
                    ),
                  ),
                ],
              ),
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildEmptyState(String message) {
    return RefreshIndicator(
      onRefresh: _onRefresh,
      color: _skyBlue,
      child: CustomScrollView(
        physics: const AlwaysScrollableScrollPhysics(),
        slivers: [
          SliverToBoxAdapter(child: _buildPremiumHeader(0)),
          SliverToBoxAdapter(
            child: Padding(
              padding: const EdgeInsets.fromLTRB(20, 24, 20, 0),
              child: Column(
                children: [
                  // Motivational Card
                  _buildEmptyMotivationCard(),
                  const SizedBox(height: 24),
                  // Quick Actions
                  _buildQuickActions(),
                  const SizedBox(height: 32),
                  // Empty illustration
                  _buildEmptyIllustration(message),
                ],
              ),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildEmptyMotivationCard() {
    return Container(
      padding: const EdgeInsets.all(20),
      decoration: BoxDecoration(
        gradient: LinearGradient(
          colors: [_teal, _teal.withValues(alpha: 0.8)],
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
        ),
        borderRadius: BorderRadius.circular(20),
        boxShadow: [
          BoxShadow(
            color: _teal.withValues(alpha: 0.3),
            blurRadius: 16,
            offset: const Offset(0, 6),
          ),
        ],
      ),
      child: Row(
        children: [
          Container(
            padding: const EdgeInsets.all(14),
            decoration: BoxDecoration(
              color: Colors.white.withValues(alpha: 0.2),
              borderRadius: BorderRadius.circular(16),
            ),
            child: const Icon(
              Iconsax.medal_star,
              color: Colors.white,
              size: 32,
            ),
          ),
          const SizedBox(width: 16),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                const Text(
                  'Kerja Bagus!',
                  style: TextStyle(
                    fontFamily: 'Poppins',
                    fontSize: 18,
                    fontWeight: FontWeight.w700,
                    color: Colors.white,
                  ),
                ),
                const SizedBox(height: 4),
                Text(
                  'Semua tugas sudah selesai.\nIstirahat sejenak atau cek menu lainnya.',
                  style: TextStyle(
                    fontFamily: 'Poppins',
                    fontSize: 13,
                    color: Colors.white.withValues(alpha: 0.9),
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

  Widget _buildQuickActions() {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        const Text(
          'Akses Cepat',
          style: TextStyle(
            fontFamily: 'Poppins',
            fontSize: 16,
            fontWeight: FontWeight.w600,
            color: _textPrimary,
          ),
        ),
        const SizedBox(height: 12),
        Row(
          children: [
            Expanded(
              child: _buildQuickActionItem(
                icon: Iconsax.clock,
                label: 'Riwayat',
                color: _skyBlue,
                onTap: () => _navigateToMenu(1),
              ),
            ),
            const SizedBox(width: 12),
            Expanded(
              child: _buildQuickActionItem(
                icon: Iconsax.box,
                label: 'Peminjaman',
                color: _indigo,
                onTap: () => _navigateToMenu(2),
              ),
            ),
          ],
        ),
        const SizedBox(height: 12),
        Row(
          children: [
            Expanded(
              child: _buildQuickActionItem(
                icon: Iconsax.chart_1,
                label: 'Statistik',
                color: _warmOrange,
                onTap: () => _navigateToMenu(3),
              ),
            ),
            const SizedBox(width: 12),
            Expanded(
              child: _buildQuickActionItem(
                icon: Iconsax.video_play,
                label: 'Tutorial',
                color: const Color(0xFFEF4444),
                onTap: () => _navigateToMenu(4),
              ),
            ),
          ],
        ),
      ],
    );
  }

  Widget _buildQuickActionItem({
    required IconData icon,
    required String label,
    required Color color,
    required VoidCallback onTap,
  }) {
    return Container(
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(16),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.06),
            blurRadius: 10,
            offset: const Offset(0, 2),
          ),
        ],
      ),
      child: Material(
        color: Colors.transparent,
        borderRadius: BorderRadius.circular(16),
        child: InkWell(
          onTap: onTap,
          borderRadius: BorderRadius.circular(16),
          child: Padding(
            padding: const EdgeInsets.symmetric(vertical: 16, horizontal: 14),
            child: Row(
              children: [
                Container(
                  padding: const EdgeInsets.all(10),
                  decoration: BoxDecoration(
                    color: color.withValues(alpha: 0.1),
                    borderRadius: BorderRadius.circular(12),
                  ),
                  child: Icon(icon, size: 22, color: color),
                ),
                const SizedBox(width: 12),
                Expanded(
                  child: Text(
                    label,
                    style: const TextStyle(
                      fontFamily: 'Poppins',
                      fontSize: 14,
                      fontWeight: FontWeight.w500,
                      color: _textPrimary,
                    ),
                  ),
                ),
                Icon(
                  Iconsax.arrow_right_3,
                  size: 18,
                  color: color.withValues(alpha: 0.6),
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }

  Widget _buildEmptyIllustration(String message) {
    return Container(
      width: double.infinity,
      padding: const EdgeInsets.symmetric(vertical: 32, horizontal: 24),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(20),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.06),
            blurRadius: 16,
            offset: const Offset(0, 4),
          ),
        ],
      ),
      child: Column(
        children: [
          // Custom illustration with stacked icons
          Stack(
            alignment: Alignment.center,
            children: [
              Container(
                width: 100,
                height: 100,
                decoration: BoxDecoration(
                  color: _skyBlue.withValues(alpha: 0.1),
                  shape: BoxShape.circle,
                ),
              ),
              Container(
                width: 70,
                height: 70,
                decoration: BoxDecoration(
                  color: _skyBlue.withValues(alpha: 0.15),
                  shape: BoxShape.circle,
                ),
              ),
              const Icon(
                Iconsax.task_square,
                size: 36,
                color: _skyBlue,
              ),
              Positioned(
                right: 20,
                top: 10,
                child: Container(
                  padding: const EdgeInsets.all(6),
                  decoration: const BoxDecoration(
                    color: _teal,
                    shape: BoxShape.circle,
                  ),
                  child: const Icon(
                    Icons.check,
                    size: 14,
                    color: Colors.white,
                  ),
                ),
              ),
            ],
          ),
          const SizedBox(height: 20),
          Text(
            message,
            style: const TextStyle(
              fontFamily: 'Poppins',
              fontSize: 16,
              fontWeight: FontWeight.w600,
              color: _textPrimary,
            ),
            textAlign: TextAlign.center,
          ),
          const SizedBox(height: 8),
          const Text(
            'Tarik ke bawah untuk memperbarui',
            style: TextStyle(
              fontFamily: 'Poppins',
              fontSize: 13,
              color: _textSecondary,
            ),
            textAlign: TextAlign.center,
          ),
        ],
      ),
    );
  }

  void _navigateToMenu(int index) {
    if (widget.onNavigate != null) {
      widget.onNavigate!(index);
    } else {
      Scaffold.of(context).openDrawer();
    }
  }

  String _formatRupiah(int value) {
    if (value >= 1000000) {
      return 'Rp ${(value / 1000000).toStringAsFixed(1)}jt';
    }
    if (value >= 1000) {
      return 'Rp ${(value / 1000).toStringAsFixed(0)}rb';
    }
    return 'Rp $value';
  }



  Widget _buildStatItem({
    required String emoji,
    required String value,
    required String label,
  }) {
    return Expanded(
      child: Column(
        children: [
          Text(emoji, style: const TextStyle(fontSize: 28)),
          const SizedBox(height: 6),
          Text(
            value,
            style: const TextStyle(
              fontFamily: 'Poppins',
              fontSize: 16,
              fontWeight: FontWeight.w800,
              color: _textPrimary,
              letterSpacing: -0.3,
            ),
            maxLines: 1,
            overflow: TextOverflow.ellipsis,
          ),
          const SizedBox(height: 2),
          Text(
            label,
            style: TextStyle(
              fontFamily: 'Poppins',
              fontSize: 10,
              fontWeight: FontWeight.w500,
              color: _textSecondary.withValues(alpha: 0.6),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildStatDivider() {
    return Container(
      width: 1,
      height: 40,
      color: const Color(0xFFE2E8F0),
    );
  }

  Widget _buildErrorState(String message) {
    return RefreshIndicator(
      onRefresh: _onRefresh,
      color: _skyBlue,
      child: CustomScrollView(
        physics: const AlwaysScrollableScrollPhysics(),
        slivers: [
          SliverToBoxAdapter(child: _buildPremiumHeader(0)),
          SliverFillRemaining(
            hasScrollBody: false,
            child: Column(
              mainAxisAlignment: MainAxisAlignment.center,
              children: [
                Container(
                  padding: const EdgeInsets.all(24),
                  decoration: BoxDecoration(
                    color: const Color(0xFFFEE2E2),
                    shape: BoxShape.circle,
                  ),
                  child: const Icon(
                    Icons.error_outline_rounded,
                    size: 48,
                    color: Color(0xFFEF4444),
                  ),
                ),
                const SizedBox(height: 24),
                const Text(
                  'Terjadi Kesalahan',
                  style: TextStyle(
                    fontFamily: 'Poppins',
                    fontSize: 18,
                    fontWeight: FontWeight.w600,
                    color: _textPrimary,
                  ),
                ),
                const SizedBox(height: 8),
                Padding(
                  padding: const EdgeInsets.symmetric(horizontal: 40),
                  child: Text(
                    message,
                    textAlign: TextAlign.center,
                    style: const TextStyle(
                      fontFamily: 'Poppins',
                      fontSize: 14,
                      color: _textSecondary,
                    ),
                  ),
                ),
                const SizedBox(height: 24),
                ElevatedButton.icon(
                  onPressed: _onRefresh,
                  icon: const Icon(Icons.refresh_rounded, size: 18),
                  label: const Text('Coba Lagi'),
                  style: ElevatedButton.styleFrom(
                    backgroundColor: _skyBlue,
                    foregroundColor: Colors.white,
                    elevation: 0,
                    padding: const EdgeInsets.symmetric(horizontal: 24, vertical: 12),
                    shape: RoundedRectangleBorder(
                      borderRadius: BorderRadius.circular(12),
                    ),
                  ),
                ),
                const SizedBox(height: 60),
              ],
            ),
          ),
        ],
      ),
    );
  }
}
