import 'package:flutter/material.dart';
import 'package:iconsax/iconsax.dart';
import 'package:provider/provider.dart';
import 'package:timezone/data/latest.dart' as tz;

import '../../service/provider/Task/DetailTaskGetProvider.dart';
import '../../service/provider/Pencapaian/PencapaianProvider.dart';
import '../../service/provider/preferences/PreferencesIDProvider.dart';
import '../../service/model/task/TaskAllResponse.dart';
import '../../utils/state.dart';
import '../../widget/CardTask.dart';

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

  @override
  void initState() {
    super.initState();
    tz.initializeTimeZones();

    WidgetsBinding.instance.addPostFrameCallback((_) {
      _loadInitialData();
    });
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
    }
  }

  Future<void> _onRefresh() async {
    setState(() => _isRefreshing = true);
    if (_teknisiId != null && _teknisiId!.isNotEmpty) {
      await Provider.of<DetailTaskGetProvider>(context, listen: false).getTask(_teknisiId!);
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
                      if (!isAssigned) return <DataTask>[]; // Bukan tugasnya, sembunyikan

                      // Teknisi ditugaskan. Cek pelaksanaan:
                      if (task.pelaksanaan.isEmpty) {
                        return [task]; // Belum ada pelaksanaan tapi ditugaskan, tampilkan
                      }

                      // Ada pelaksanaan, filter hanya yang milik teknisi ini dan belum selesai
                      final myActivePelaksanaan = task.pelaksanaan
                          .where((data) => data.teknisiId == teknisiIdParsed &&
                                           data.status != 'selesai' &&
                                           data.status != 'Lanjut Nanti');

                      return myActivePelaksanaan.isNotEmpty ? [task] : <DataTask>[];
                    }).toList();

                    if (filteredData.isEmpty) {
                      return _buildEmptyState('Belum ada tugas untuk kamu');
                    }

                    return _buildTaskList(filteredData);
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

  Widget _buildTaskList(List<dynamic> filteredData) {
    return RefreshIndicator(
      onRefresh: _onRefresh,
      color: Colors.white,
      backgroundColor: _brandBlue,
      displacement: 60,
      child: CustomScrollView(
        physics: const BouncingScrollPhysics(parent: AlwaysScrollableScrollPhysics()),
        slivers: [
          // Premium gradient header
          SliverToBoxAdapter(
            child: _buildPremiumHeader(filteredData.length),
          ),
          // Statistik summary cards
          SliverToBoxAdapter(
            child: RepaintBoundary(child: _buildStatistikSection()),
          ),
          // Task count indicator
          SliverToBoxAdapter(
            child: Padding(
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
          // Task list with RepaintBoundary
          SliverPadding(
            padding: const EdgeInsets.fromLTRB(20, 4, 20, 20),
            sliver: SliverList(
              delegate: SliverChildBuilderDelegate(
                (context, index) {
                  return RepaintBoundary(
                    child: Padding(
                      padding: const EdgeInsets.only(bottom: 12),
                      child: CardTask(
                        data: filteredData[index],
                        history: false,
                      ),
                    ),
                  );
                },
                childCount: filteredData.length,
                addAutomaticKeepAlives: true,
              ),
            ),
          ),
        ],
      ),
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
          padding: const EdgeInsets.fromLTRB(20, 8, 20, 60),
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

  // ─── STATISTIK SECTION ─────────────────────────────
  Widget _buildStatistikSection() {
    return Consumer<PencapaianProvider>(
      builder: (context, provider, child) {
        if (provider.pencapaianState == PencapaianState.loading ||
            provider.pendapatanState == PencapaianState.loading) {
          return Padding(
            padding: const EdgeInsets.fromLTRB(20, 20, 20, 0),
            child: Container(
              padding: const EdgeInsets.all(24),
              decoration: BoxDecoration(
                color: Colors.white,
                borderRadius: BorderRadius.circular(20),
                boxShadow: [
                  BoxShadow(
                    color: Colors.black.withValues(alpha: 0.04),
                    blurRadius: 16,
                    offset: const Offset(0, 4),
                  ),
                ],
              ),
              child: const Center(
                child: SizedBox(
                  width: 22, height: 22,
                  child: CircularProgressIndicator(strokeWidth: 2.5, color: _skyBlue),
                ),
              ),
            ),
          );
        }

        if (provider.pencapaianState != PencapaianState.loaded &&
            provider.pendapatanState != PencapaianState.loaded) {
          return const SizedBox.shrink();
        }

        final totalKegiatan = provider.pencapaianData?.totalSelesai ?? 0;
        final totalPendapatan = provider.pendapatanData?.totalKeseluruhan ?? 0;
        final bonus = provider.pendapatanData?.bonus ?? 0;
        final target = provider.pendapatanData?.target ?? 0;
        final progress = target > 0
            ? ((totalPendapatan / target) * 100).clamp(0, 999).toInt()
            : 0;

        return Transform.translate(
          offset: const Offset(0, -36),
          child: Padding(
            padding: const EdgeInsets.fromLTRB(20, 0, 20, 0),
            child: Column(
              children: [
                // Floating stats card with gradient border
                Container(
                  decoration: BoxDecoration(
                    gradient: const LinearGradient(
                      begin: Alignment.topLeft,
                      end: Alignment.bottomRight,
                      colors: [Color(0xFF1E40AF), Color(0xFF0891B2)],
                    ),
                    borderRadius: BorderRadius.circular(20),
                    boxShadow: [
                      BoxShadow(
                        color: const Color(0xFF1E40AF).withValues(alpha: 0.15),
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
                const SizedBox(height: 16),
                // Progress bar
                GestureDetector(
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
              ],
            ),
          ),
        );
      },
    );
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
