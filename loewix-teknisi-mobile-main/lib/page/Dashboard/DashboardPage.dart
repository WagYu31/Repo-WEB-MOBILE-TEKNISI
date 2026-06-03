import 'package:flutter/material.dart';
import 'package:iconsax/iconsax.dart';
import 'package:provider/provider.dart';
import 'package:timezone/data/latest.dart' as tz;

import '../../service/provider/Task/DetailTaskGetProvider.dart';
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
  // Modern color scheme
  static const Color _primaryBlue = Color(0xFF2563EB);
  static const Color _successGreen = Color(0xFF10B981);
  static const Color _warningAmber = Color(0xFFF59E0B);
  static const Color _purpleAccent = Color(0xFF8B5CF6);
  static const Color _bgColor = Color(0xFFF8FAFC);
  static const Color _textPrimary = Color(0xFF1F2937);
  static const Color _textSecondary = Color(0xFF6B7280);

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
      body: SafeArea(
        child: Consumer<PreferencesIDProvider>(
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
              color: _primaryBlue,
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
      color: _primaryBlue,
      backgroundColor: Colors.white,
      child: CustomScrollView(
        physics: const AlwaysScrollableScrollPhysics(),
        slivers: [
          // Header section
          SliverToBoxAdapter(
            child: _buildHeader(filteredData.length),
          ),
          // Motivation card
          SliverToBoxAdapter(
            child: _buildMotivationCard(),
          ),
          // Task count indicator
          SliverToBoxAdapter(
            child: Padding(
              padding: const EdgeInsets.fromLTRB(20, 24, 20, 12),
              child: Row(
                children: [
                  Container(
                    padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
                    decoration: BoxDecoration(
                      color: _primaryBlue.withValues(alpha: 0.1),
                      borderRadius: BorderRadius.circular(20),
                    ),
                    child: Row(
                      mainAxisSize: MainAxisSize.min,
                      children: [
                        const Icon(Icons.assignment, size: 16, color: _primaryBlue),
                        const SizedBox(width: 6),
                        Text(
                          '${filteredData.length} Tugas Aktif',
                          style: const TextStyle(
                            fontFamily: 'Poppins',
                            fontSize: 13,
                            fontWeight: FontWeight.w600,
                            color: _primaryBlue,
                          ),
                        ),
                      ],
                    ),
                  ),
                ],
              ),
            ),
          ),
          // Task list
          SliverPadding(
            padding: const EdgeInsets.fromLTRB(20, 0, 20, 20),
            sliver: SliverList(
              delegate: SliverChildBuilderDelegate(
                (context, index) {
                  return Padding(
                    padding: const EdgeInsets.only(bottom: 12),
                    child: CardTask(
                      data: filteredData[index],
                      history: false,
                    ),
                  );
                },
                childCount: filteredData.length,
              ),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildHeader(int taskCount) {
    return Container(
      padding: const EdgeInsets.fromLTRB(20, 16, 20, 0),
      child: Row(
        children: [
          // Menu button
          Material(
            color: Colors.white,
            borderRadius: BorderRadius.circular(12),
            child: InkWell(
              onTap: () => Scaffold.of(context).openDrawer(),
              borderRadius: BorderRadius.circular(12),
              child: Container(
                padding: const EdgeInsets.all(12),
                decoration: BoxDecoration(
                  borderRadius: BorderRadius.circular(12),
                  boxShadow: [
                    BoxShadow(
                      color: Colors.black.withValues(alpha: 0.04),
                      blurRadius: 8,
                      offset: const Offset(0, 2),
                    ),
                  ],
                ),
                child: const Icon(
                  Iconsax.menu_1,
                  color: _primaryBlue,
                  size: 22,
                ),
              ),
            ),
          ),
          const SizedBox(width: 14),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  _getGreeting(),
                  style: const TextStyle(
                    fontFamily: 'Poppins',
                    fontSize: 14,
                    color: _textSecondary,
                  ),
                ),
                const Text(
                  'Daftar Kegiatan',
                  style: TextStyle(
                    fontFamily: 'Poppins',
                    fontSize: 24,
                    fontWeight: FontWeight.w700,
                    color: _textPrimary,
                    height: 1.2,
                  ),
                ),
              ],
            ),
          ),
          // Refresh button
          Material(
            color: Colors.white,
            borderRadius: BorderRadius.circular(12),
            child: InkWell(
              onTap: _isRefreshing ? null : _onRefresh,
              borderRadius: BorderRadius.circular(12),
              child: Container(
                padding: const EdgeInsets.all(12),
                decoration: BoxDecoration(
                  borderRadius: BorderRadius.circular(12),
                  boxShadow: [
                    BoxShadow(
                      color: Colors.black.withValues(alpha: 0.04),
                      blurRadius: 8,
                      offset: const Offset(0, 2),
                    ),
                  ],
                ),
                child: _isRefreshing
                    ? const SizedBox(
                        width: 20,
                        height: 20,
                        child: CircularProgressIndicator(
                          strokeWidth: 2,
                          color: _primaryBlue,
                        ),
                      )
                    : const Icon(
                        Icons.refresh_rounded,
                        color: _primaryBlue,
                        size: 22,
                      ),
              ),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildMotivationCard() {
    return Padding(
      padding: const EdgeInsets.fromLTRB(20, 20, 20, 0),
      child: Container(
        padding: const EdgeInsets.all(16),
        decoration: BoxDecoration(
          gradient: const LinearGradient(
            colors: [Color(0xFF2563EB), Color(0xFF3B82F6)],
            begin: Alignment.topLeft,
            end: Alignment.bottomRight,
          ),
          borderRadius: BorderRadius.circular(16),
          boxShadow: [
            BoxShadow(
              color: _primaryBlue.withValues(alpha: 0.3),
              blurRadius: 12,
              offset: const Offset(0, 4),
            ),
          ],
        ),
        child: Row(
          children: [
            Container(
              padding: const EdgeInsets.all(10),
              decoration: BoxDecoration(
                color: Colors.white.withValues(alpha: 0.2),
                borderRadius: BorderRadius.circular(12),
              ),
              child: const Icon(
                Icons.lightbulb_outline_rounded,
                color: Colors.white,
                size: 24,
              ),
            ),
            const SizedBox(width: 14),
            Expanded(
              child: Text(
                _getMotivation(),
                style: const TextStyle(
                  fontFamily: 'Poppins',
                  fontSize: 14,
                  fontWeight: FontWeight.w500,
                  color: Colors.white,
                  height: 1.4,
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildEmptyState(String message) {
    return RefreshIndicator(
      onRefresh: _onRefresh,
      color: _primaryBlue,
      child: CustomScrollView(
        physics: const AlwaysScrollableScrollPhysics(),
        slivers: [
          SliverToBoxAdapter(child: _buildHeader(0)),
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
          colors: [_successGreen, _successGreen.withValues(alpha: 0.8)],
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
        ),
        borderRadius: BorderRadius.circular(20),
        boxShadow: [
          BoxShadow(
            color: _successGreen.withValues(alpha: 0.3),
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
                color: _primaryBlue,
                onTap: () => _navigateToMenu(1),
              ),
            ),
            const SizedBox(width: 12),
            Expanded(
              child: _buildQuickActionItem(
                icon: Iconsax.box,
                label: 'Peminjaman',
                color: _purpleAccent,
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
                color: _warningAmber,
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
                  color: _primaryBlue.withValues(alpha: 0.1),
                  shape: BoxShape.circle,
                ),
              ),
              Container(
                width: 70,
                height: 70,
                decoration: BoxDecoration(
                  color: _primaryBlue.withValues(alpha: 0.15),
                  shape: BoxShape.circle,
                ),
              ),
              const Icon(
                Iconsax.task_square,
                size: 36,
                color: _primaryBlue,
              ),
              Positioned(
                right: 20,
                top: 10,
                child: Container(
                  padding: const EdgeInsets.all(6),
                  decoration: const BoxDecoration(
                    color: _successGreen,
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
      // Fallback: open drawer for user to navigate manually
      Scaffold.of(context).openDrawer();
    }
  }

  Widget _buildErrorState(String message) {
    return RefreshIndicator(
      onRefresh: _onRefresh,
      color: _primaryBlue,
      child: CustomScrollView(
        physics: const AlwaysScrollableScrollPhysics(),
        slivers: [
          SliverToBoxAdapter(child: _buildHeader(0)),
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
                    backgroundColor: _primaryBlue,
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
