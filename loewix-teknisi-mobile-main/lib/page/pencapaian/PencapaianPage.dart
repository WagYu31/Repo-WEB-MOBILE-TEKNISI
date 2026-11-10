import 'package:fl_chart/fl_chart.dart';
import 'package:flutter/material.dart';
import 'package:iconsax/iconsax.dart';
import 'package:provider/provider.dart';
import 'package:tutorial_coach_mark/tutorial_coach_mark.dart';

import '../../service/api/ApiTeknisi.dart';
import '../../service/model/pencapaian/PencapaianChartGet.dart';
import '../../service/provider/Pencapaian/PencapaianProvider.dart';
import '../../service/provider/preferences/PreferencesIDProvider.dart';
import '../../service/model/pencapaian/PencapaianResponse.dart';
import '../../service/model/pencapaian/PendapatanResponse.dart';
import '../../utils/demo_teknisi_data.dart';
import '../../widget/CoachMarkHelper.dart';

class PencapaianPage extends StatefulWidget {
  const PencapaianPage({super.key});

  @override
  State<PencapaianPage> createState() => _PencapaianPageState();
}

class _PencapaianPageState extends State<PencapaianPage>
    with SingleTickerProviderStateMixin {
  // ─── Premium Color Palette ─────────────────────
  static const Color _primaryBlue = Color(0xFF0EA5E9);
  static const Color _successGreen = Color(0xFF14B8A6);
  static const Color _warningAmber = Color(0xFFF97316);
  static const Color _errorRed = Color(0xFFF43F5E);
  static const Color _purpleAccent = Color(0xFF6366F1);
  static const Color _cyanAccent = Color(0xFF06B6D4);
  static const Color _pinkAccent = Color(0xFFEC4899);
  static const Color _textPrimary = Color(0xFF0F172A);
  static const Color _textSecondary = Color(0xFF64748B);

  int _selectedMonth = DateTime.now().month;
  int _selectedYear = DateTime.now().year;
  int? _teknisiId;

  // Yearly trend data
  List<DataPencapaian> _yearlyData = [];
  bool _isLoadingYearly = false;

  late AnimationController _animController;
  late Animation<double> _fadeAnim;

  // Coach Mark Keys
  final GlobalKey _keyMonthSelector = GlobalKey();
  final GlobalKey _keySummaryCards = GlobalKey();
  final GlobalKey _keyBarChart = GlobalKey();
  final GlobalKey _keyTargetProgress = GlobalKey();
  bool _coachMarkShown = false;

  static const List<String> _monthNames = [
    'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
    'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
  ];

  static const List<String> _monthShort = [
    'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun',
    'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'
  ];

  static const List<Color> _barColors = [
    _primaryBlue, _successGreen, _purpleAccent, _warningAmber,
    _cyanAccent, _pinkAccent, _errorRed, Color(0xFF6366F1),
  ];

  @override
  void initState() {
    super.initState();
    _animController = AnimationController(
      vsync: this,
      duration: const Duration(milliseconds: 600),
    );
    _fadeAnim = CurvedAnimation(parent: _animController, curve: Curves.easeOut);

    WidgetsBinding.instance.addPostFrameCallback((_) {
      if (!mounted) return;
      final idString = context.read<PreferencesIDProvider>().isUserRole;
      _teknisiId = int.tryParse(idString);
      if (_teknisiId != null) {
        _loadData();
        _loadYearlyData();
      }
      _animController.forward();
      _showStatistikCoachMark();
    });
  }

  @override
  void dispose() {
    _animController.dispose();
    super.dispose();
  }

  void _loadData() {
    if (_teknisiId == null) return;
    Provider.of<PencapaianProvider>(context, listen: false).loadAll(
      teknisiId: _teknisiId!,
      bulan: _selectedMonth,
      tahun: _selectedYear,
    );
  }

  Future<void> _loadYearlyData() async {
    if (_teknisiId == null) return;
    setState(() => _isLoadingYearly = true);
    try {
      final api = ApiTeknisi();
      final response = await api.getPencapaian(_teknisiId.toString());
      if (mounted) {
        setState(() {
          _yearlyData = response.data.isNotEmpty ? response.data : DemoTeknisiData.chart.data;
          _isLoadingYearly = false;
        });
      }
    } catch (e) {
      if (mounted) {
        setState(() {
          _yearlyData = DemoTeknisiData.chart.data;
          _isLoadingYearly = false;
        });
      }
    }
  }

  void _showMonthYearPicker() {
    showModalBottomSheet(
      context: context,
      backgroundColor: Colors.transparent,
      isScrollControlled: true,
      builder: (context) => _buildMonthYearPickerModal(),
    );
  }

  String _formatRupiah(int value) {
    return 'Rp ${value.toString().replaceAllMapped(
      RegExp(r'(\d{1,3})(?=(\d{3})+(?!\d))'),
      (Match m) => '${m[1]}.',
    )}';
  }

  String _formatRupiahShort(int value) {
    if (value >= 1000000) return 'Rp ${(value / 1000000).toStringAsFixed(1)}jt';
    if (value >= 1000) return 'Rp ${(value / 1000).toStringAsFixed(0)}rb';
    return _formatRupiah(value);
  }

  String _capitalizeFirst(String text) {
    if (text.isEmpty) return text;
    return text.split(' ').map((word) {
      if (word.isEmpty) return word;
      return word[0].toUpperCase() + word.substring(1).toLowerCase();
    }).join(' ');
  }

  /// Calculate nice round intervals for chart Y-axis
  double _calcNiceInterval(double maxY) {
    if (maxY <= 0) return 1;
    final raw = maxY / 4;
    // Round to nearest nice number
    if (raw >= 1000000) return (raw / 1000000).ceil() * 1000000;
    if (raw >= 100000) return (raw / 100000).ceil() * 100000;
    if (raw >= 10000) return (raw / 10000).ceil() * 10000;
    if (raw >= 1000) return (raw / 1000).ceil() * 1000;
    if (raw >= 100) return (raw / 100).ceil() * 100;
    if (raw >= 10) return (raw / 10).ceil() * 10;
    return raw.ceilToDouble().clamp(1, double.infinity);
  }

  /// Format axis label (short form)
  String _formatAxisLabel(double value) {
    if (value >= 1000000) return '${(value / 1000000).toStringAsFixed(1)}jt';
    if (value >= 1000) return '${(value / 1000).toStringAsFixed(0)}rb';
    return value.toInt().toString();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xFFF8FAFC),
      body: FadeTransition(
        opacity: _fadeAnim,
        child: Consumer<PencapaianProvider>(
          builder: (context, provider, child) {
            final isLoading =
                provider.pencapaianState == PencapaianState.loading ||
                provider.pendapatanState == PencapaianState.loading;

            return RefreshIndicator(
              color: _primaryBlue,
              onRefresh: () async {
                _loadData();
                await _loadYearlyData();
              },
              child: CustomScrollView(
                physics: const AlwaysScrollableScrollPhysics(),
                slivers: [
                  // ─── HERO HEADER & MONTH SELECTOR ───
                  SliverToBoxAdapter(child: _buildHeroHeader()),
                  if (isLoading)
                    SliverFillRemaining(
                      hasScrollBody: false,
                      child: _buildLoadingState(),
                    )
                  else ...[
                    // ─── SUMMARY CARDS ─────────────────
                    if (provider.pencapaianState == PencapaianState.loaded ||
                        provider.pendapatanState == PencapaianState.loaded)
                      SliverToBoxAdapter(
                        child: Padding(
                          padding: const EdgeInsets.fromLTRB(20, 20, 20, 0),
                          child: _buildSummaryCards(
                            provider.pencapaianData,
                            provider.pendapatanData,
                          ),
                        ),
                      ),

                    // ─── BAR CHART — Rincian Kegiatan ──
                    SliverToBoxAdapter(
                      child: Padding(
                        key: _keyBarChart,
                        padding: const EdgeInsets.fromLTRB(20, 20, 20, 0),
                        child: _buildBarChartSection(provider),
                      ),
                    ),

                    // ─── LINE CHART — Tren Pendapatan ──
                    SliverToBoxAdapter(
                      child: Padding(
                        padding: const EdgeInsets.fromLTRB(20, 20, 20, 0),
                        child: _buildLineChartSection(),
                      ),
                    ),

                    // ─── PENDAPATAN TARGET PROGRESS ────
                    if (provider.pendapatanState == PencapaianState.loaded &&
                        provider.pendapatanData != null)
                      SliverToBoxAdapter(
                        child: Padding(
                          key: _keyTargetProgress,
                          padding: const EdgeInsets.fromLTRB(20, 20, 20, 0),
                          child: _buildTargetProgress(provider.pendapatanData!),
                        ),
                      ),

                    // ─── DETAIL PENDAPATAN ─────────────
                    if (provider.pendapatanState == PencapaianState.loaded &&
                        provider.pendapatanData != null)
                      SliverToBoxAdapter(
                        child: Padding(
                          padding: const EdgeInsets.fromLTRB(20, 20, 20, 36),
                          child: _buildPendapatanDetail(provider.pendapatanData!),
                        ),
                      ),
                  ],
                ],
              ),
            );
          },
        ),
      ),
    );
  }

  // ════════════════════════════════════════════════════
  // HERO HEADER & MONTH SELECTOR
  // ════════════════════════════════════════════════════
  Widget _buildHeroHeader() {
    return Container(
      width: double.infinity,
      decoration: const BoxDecoration(
        gradient: LinearGradient(
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
          colors: [Color(0xFF0F172A), Color(0xFF1E293B), Color(0xFF0B132B)],
        ),
        borderRadius: BorderRadius.only(
          bottomLeft: Radius.circular(32),
          bottomRight: Radius.circular(32),
        ),
      ),
      child: SafeArea(
        bottom: false,
        child: Padding(
          padding: const EdgeInsets.fromLTRB(20, 10, 20, 24),
          child: Column(
            children: [
              // Top Bar
              Row(
                children: [
                  Material(
                    color: Colors.white.withValues(alpha: 0.1),
                    borderRadius: BorderRadius.circular(14),
                    child: InkWell(
                      onTap: () => Scaffold.of(context).openDrawer(),
                      borderRadius: BorderRadius.circular(14),
                      child: const Padding(
                        padding: EdgeInsets.all(11),
                        child: Icon(Iconsax.menu_1, color: Colors.white, size: 20),
                      ),
                    ),
                  ),
                  const SizedBox(width: 14),
                  const Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          'Analisis Kinerja',
                          style: TextStyle(
                            fontFamily: 'Poppins',
                            fontSize: 11.5,
                            color: Color(0xFF94A3B8),
                            fontWeight: FontWeight.w500,
                          ),
                        ),
                        Text(
                          'Statistik & Tracking',
                          style: TextStyle(
                            fontFamily: 'Poppins',
                            fontSize: 20,
                            fontWeight: FontWeight.w700,
                            color: Colors.white,
                            letterSpacing: -0.4,
                          ),
                        ),
                      ],
                    ),
                  ),
                  Material(
                    color: Colors.white.withValues(alpha: 0.1),
                    borderRadius: BorderRadius.circular(14),
                    child: InkWell(
                      onTap: () {
                        _loadData();
                        _loadYearlyData();
                      },
                      borderRadius: BorderRadius.circular(14),
                      child: const Padding(
                        padding: EdgeInsets.all(11),
                        child: Icon(Icons.refresh_rounded, color: Colors.white, size: 20),
                      ),
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 18),
              // Frosted Month/Year Selector
              _buildMonthSelectorCard(),
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildMonthSelectorCard() {
    return InkWell(
      onTap: _showMonthYearPicker,
      borderRadius: BorderRadius.circular(18),
      child: Container(
        key: _keyMonthSelector,
        padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
        decoration: BoxDecoration(
          color: Colors.white.withValues(alpha: 0.08),
          borderRadius: BorderRadius.circular(18),
          border: Border.all(color: Colors.white.withValues(alpha: 0.14), width: 1.2),
          boxShadow: [
            BoxShadow(
              color: Colors.black.withValues(alpha: 0.15),
              blurRadius: 10,
              offset: const Offset(0, 3),
            ),
          ],
        ),
        child: Row(
          children: [
            Container(
              padding: const EdgeInsets.all(9),
              decoration: BoxDecoration(
                gradient: const LinearGradient(
                  colors: [Color(0xFFFF6B2B), Color(0xFFFF4800)],
                  begin: Alignment.topLeft,
                  end: Alignment.bottomRight,
                ),
                borderRadius: BorderRadius.circular(12),
                boxShadow: [
                  BoxShadow(
                    color: const Color(0xFFFF5B22).withValues(alpha: 0.35),
                    blurRadius: 8,
                    offset: const Offset(0, 2),
                  ),
                ],
              ),
              child: const Icon(
                Icons.calendar_month_rounded,
                color: Colors.white,
                size: 20,
              ),
            ),
            const SizedBox(width: 14),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  const Text(
                    'Periode Analisis',
                    style: TextStyle(
                      fontFamily: 'Poppins',
                      fontSize: 11,
                      color: Color(0xFF94A3B8),
                    ),
                  ),
                  const SizedBox(height: 1),
                  Text(
                    '${_monthNames[_selectedMonth - 1]} $_selectedYear',
                    style: const TextStyle(
                      fontFamily: 'Poppins',
                      fontSize: 16,
                      fontWeight: FontWeight.w700,
                      color: Colors.white,
                    ),
                  ),
                ],
              ),
            ),
            Container(
              padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
              decoration: BoxDecoration(
                color: Colors.white.withValues(alpha: 0.12),
                borderRadius: BorderRadius.circular(10),
              ),
              child: const Row(
                mainAxisSize: MainAxisSize.min,
                children: [
                  Text(
                    'Ubah',
                    style: TextStyle(
                      fontFamily: 'Poppins',
                      fontSize: 11,
                      fontWeight: FontWeight.w600,
                      color: Colors.white,
                    ),
                  ),
                  SizedBox(width: 4),
                  Icon(Icons.keyboard_arrow_down_rounded, size: 16, color: Colors.white),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }

  // ════════════════════════════════════════════════════
  // SUMMARY CARDS (4 cards in 2x2 grid)
  // ════════════════════════════════════════════════════
  Widget _buildSummaryCards(
    PencapaianResponse? pencapaian,
    PendapatanResponse? pendapatan,
  ) {
    final totalKegiatan = pencapaian?.totalSelesai ?? 0;
    final totalPendapatan = pendapatan?.totalKeseluruhan ?? 0;
    final bonus = pendapatan?.bonus ?? 0;
    final target = pendapatan?.target ?? 0;
    final progress = target > 0
        ? ((totalPendapatan / target) * 100).clamp(0, 999).toInt()
        : 0;

    return Column(
      key: _keySummaryCards,
      children: [
        Row(
          children: [
            Expanded(
              child: _buildStatCard(
                icon: Iconsax.task_square,
                title: 'Kegiatan',
                value: totalKegiatan.toString(),
                subtitle: 'Selesai bulan ini',
                gradient: const [Color(0xFF0284C7), Color(0xFF0369A1)],
                badgeText: 'Tugas',
              ),
            ),
            const SizedBox(width: 12),
            Expanded(
              child: _buildStatCard(
                icon: Iconsax.wallet_3,
                title: 'Pendapatan',
                value: _formatRupiahShort(totalPendapatan),
                subtitle: 'Total bulan ini',
                gradient: const [Color(0xFF16A34A), Color(0xFF15803D)],
                badgeText: 'Realisasi',
              ),
            ),
          ],
        ),
        const SizedBox(height: 12),
        Row(
          children: [
            Expanded(
              child: _buildStatCard(
                icon: Iconsax.medal_star,
                title: 'Bonus',
                value: bonus > 0 ? _formatRupiahShort(bonus) : '-',
                subtitle: bonus > 0 ? 'Tercapai!' : 'Belum tercapai',
                gradient: const [Color(0xFFF59E0B), Color(0xFFD97706)],
                badgeText: bonus > 0 ? 'Cair' : 'Target',
              ),
            ),
            const SizedBox(width: 12),
            Expanded(
              child: _buildStatCard(
                icon: Iconsax.diagram,
                title: 'Target',
                value: '$progress%',
                subtitle: 'Progress tercapai',
                gradient: const [Color(0xFF6366F1), Color(0xFF4F46E5)],
                badgeText: progress >= 100 ? '100%' : 'Bulan ini',
              ),
            ),
          ],
        ),
      ],
    );
  }

  Widget _buildStatCard({
    required IconData icon,
    required String title,
    required String value,
    required String subtitle,
    required List<Color> gradient,
    required String badgeText,
  }) {
    return Container(
      padding: const EdgeInsets.all(15),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(20),
        border: Border.all(color: const Color(0xFFE2E8F0), width: 1.2),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.04),
            blurRadius: 14,
            offset: const Offset(0, 4),
          ),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Container(
                width: 38,
                height: 38,
                decoration: BoxDecoration(
                  gradient: LinearGradient(
                    colors: gradient,
                    begin: Alignment.topLeft,
                    end: Alignment.bottomRight,
                  ),
                  borderRadius: BorderRadius.circular(12),
                  boxShadow: [
                    BoxShadow(
                      color: gradient.first.withValues(alpha: 0.35),
                      blurRadius: 8,
                      offset: const Offset(0, 2),
                    ),
                  ],
                ),
                child: Center(
                  child: Icon(icon, size: 19, color: Colors.white),
                ),
              ),
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 7, vertical: 3),
                decoration: BoxDecoration(
                  color: gradient.first.withValues(alpha: 0.1),
                  borderRadius: BorderRadius.circular(8),
                ),
                child: Text(
                  badgeText,
                  style: TextStyle(
                    fontFamily: 'Poppins',
                    fontSize: 9.5,
                    fontWeight: FontWeight.w600,
                    color: gradient.first,
                  ),
                ),
              ),
            ],
          ),
          const SizedBox(height: 12),
          Text(
            value,
            style: const TextStyle(
              fontFamily: 'Poppins',
              fontSize: 21,
              fontWeight: FontWeight.w800,
              color: Color(0xFF0F172A),
              letterSpacing: -0.4,
            ),
            maxLines: 1,
            overflow: TextOverflow.ellipsis,
          ),
          const SizedBox(height: 2),
          Text(
            subtitle,
            style: const TextStyle(
              fontFamily: 'Poppins',
              fontSize: 10.5,
              fontWeight: FontWeight.w500,
              color: Color(0xFF64748B),
            ),
            maxLines: 1,
            overflow: TextOverflow.ellipsis,
          ),
        ],
      ),
    );
  }

  // ════════════════════════════════════════════════════
  // BAR CHART — Rincian Kegiatan
  // ════════════════════════════════════════════════════
  Widget _buildBarChartSection(PencapaianProvider provider) {
    return _buildChartCard(
      icon: Iconsax.chart_215,
      iconColor: _primaryBlue,
      title: 'Rincian Kegiatan',
      subtitle: 'Breakdown kegiatan selesai',
      child: provider.pencapaianState == PencapaianState.error
          ? _buildChartMessage('Gagal memuat data', Icons.error_outline_rounded, _errorRed)
          : provider.pencapaianState == PencapaianState.loaded &&
                provider.pencapaianData != null &&
                provider.pencapaianData!.rincian.isNotEmpty
              ? _buildBarChart(provider.pencapaianData!)
              : _buildChartMessage('Belum ada kegiatan', Iconsax.chart, _textSecondary),
    );
  }

  Widget _buildBarChart(PencapaianResponse data) {
    final entries = data.rincian.entries.toList();
    final maxVal = entries.isEmpty
        ? 5
        : entries.map((e) => e.value).reduce((a, b) => a > b ? a : b);
    final maxY = (maxVal < 4 ? 5 : maxVal + 2).toDouble();

    return Column(
      children: [
        SizedBox(
          height: 210,
          child: BarChart(
            BarChartData(
              alignment: BarChartAlignment.spaceAround,
              maxY: maxY,
              barTouchData: BarTouchData(
                enabled: true,
                touchTooltipData: BarTouchTooltipData(
                  tooltipRoundedRadius: 12,
                  getTooltipItem: (group, groupIndex, rod, rodIndex) {
                    final entry = entries[group.x.toInt()];
                    return BarTooltipItem(
                      '${_capitalizeFirst(entry.key)}\n',
                      const TextStyle(
                        fontFamily: 'Poppins',
                        color: Colors.white,
                        fontWeight: FontWeight.w500,
                        fontSize: 12,
                      ),
                      children: [
                        TextSpan(
                          text: '${entry.value} kegiatan',
                          style: const TextStyle(
                            fontFamily: 'Poppins',
                            fontSize: 14,
                            fontWeight: FontWeight.w700,
                            color: Colors.white,
                          ),
                        ),
                      ],
                    );
                  },
                ),
              ),
              titlesData: FlTitlesData(
                show: true,
                topTitles: const AxisTitles(sideTitles: SideTitles(showTitles: false)),
                rightTitles: const AxisTitles(sideTitles: SideTitles(showTitles: false)),
                leftTitles: AxisTitles(
                  sideTitles: SideTitles(
                    showTitles: true,
                    reservedSize: 28,
                    interval: maxY > 10 ? (maxY / 5).ceilToDouble() : 1,
                    getTitlesWidget: (value, meta) => Text(
                      value.toInt().toString(),
                      style: const TextStyle(
                        fontFamily: 'Poppins',
                        fontSize: 11,
                        fontWeight: FontWeight.w500,
                        color: Color(0xFF94A3B8),
                      ),
                    ),
                  ),
                ),
                bottomTitles: AxisTitles(
                  sideTitles: SideTitles(
                    showTitles: true,
                    reservedSize: 42,
                    getTitlesWidget: (value, meta) {
                      final idx = value.toInt();
                      if (idx < 0 || idx >= entries.length) return const SizedBox();
                      final rawLabel = entries[idx].key.trim();
                      String displayLabel;
                      if (rawLabel.toLowerCase().contains('pasang')) {
                        displayLabel = 'Pasang\nBaru';
                      } else if (rawLabel.toLowerCase().contains('service')) {
                        displayLabel = 'Service';
                      } else if (rawLabel.length > 9) {
                        final words = rawLabel.split(' ');
                        displayLabel = words.length > 1
                            ? '${words[0]}\n${words.sublist(1).join(" ")}'
                            : rawLabel;
                      } else {
                        displayLabel = _capitalizeFirst(rawLabel);
                      }
                      return Padding(
                        padding: const EdgeInsets.only(top: 6),
                        child: Text(
                          displayLabel,
                          style: const TextStyle(
                            fontFamily: 'Poppins',
                            fontSize: 10,
                            fontWeight: FontWeight.w600,
                            color: Color(0xFF475569),
                            height: 1.15,
                          ),
                          textAlign: TextAlign.center,
                        ),
                      );
                    },
                  ),
                ),
              ),
              gridData: FlGridData(
                show: true,
                drawVerticalLine: false,
                horizontalInterval: maxY > 10 ? (maxY / 5).ceilToDouble() : 1,
                getDrawingHorizontalLine: (value) => FlLine(
                  color: const Color(0xFFF1F5F9),
                  strokeWidth: 1.2,
                  dashArray: [5, 5],
                ),
              ),
              borderData: FlBorderData(show: false),
              barGroups: entries.asMap().entries.map((e) {
                final color = _barColors[e.key % _barColors.length];
                return BarChartGroupData(
                  x: e.key,
                  barRods: [
                    BarChartRodData(
                      toY: e.value.value.toDouble(),
                      gradient: LinearGradient(
                        colors: [color, color.withValues(alpha: 0.75)],
                        begin: Alignment.topCenter,
                        end: Alignment.bottomCenter,
                      ),
                      width: entries.length > 5 ? 18 : 32,
                      borderRadius: const BorderRadius.vertical(top: Radius.circular(10)),
                      backDrawRodData: BackgroundBarChartRodData(
                        show: true,
                        toY: maxY,
                        color: color.withValues(alpha: 0.08),
                      ),
                    ),
                  ],
                );
              }).toList(),
            ),
          ),
        ),
        const SizedBox(height: 16),
        Wrap(
          spacing: 14,
          runSpacing: 8,
          children: entries.asMap().entries.map((e) {
            final color = _barColors[e.key % _barColors.length];
            return Container(
              padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
              decoration: BoxDecoration(
                color: color.withValues(alpha: 0.08),
                borderRadius: BorderRadius.circular(10),
              ),
              child: Row(
                mainAxisSize: MainAxisSize.min,
                children: [
                  Container(
                    width: 8,
                    height: 8,
                    decoration: BoxDecoration(
                      color: color,
                      shape: BoxShape.circle,
                    ),
                  ),
                  const SizedBox(width: 6),
                  Text(
                    '${_capitalizeFirst(e.value.key)}: ${e.value.value}',
                    style: TextStyle(
                      fontFamily: 'Poppins',
                      fontSize: 11,
                      fontWeight: FontWeight.w600,
                      color: color,
                    ),
                  ),
                ],
              ),
            );
          }).toList(),
        ),
      ],
    );
  }

  // ════════════════════════════════════════════════════
  // LINE CHART — Tren Pendapatan
  // ════════════════════════════════════════════════════
  Widget _buildLineChartSection() {
    Widget content;
    if (_isLoadingYearly) {
      content = const SizedBox(
        height: 180,
        child: Center(child: CircularProgressIndicator(color: _primaryBlue, strokeWidth: 2)),
      );
    } else if (_yearlyData.isEmpty) {
      content = _buildChartMessage('Belum ada data tren', Iconsax.trend_up, _textSecondary);
    } else {
      content = _buildLineChart();
    }

    return _buildChartCard(
      icon: Iconsax.trend_up,
      iconColor: const Color(0xFF0284C7),
      title: 'Tren Pendapatan Bulanan',
      subtitle: 'Grafik realisasi vs batas target',
      child: Column(
        children: [
          content,
          const SizedBox(height: 16),
          Row(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              _buildLegendDot('Realisasi Pendapatan', const Color(0xFF0284C7)),
              const SizedBox(width: 20),
              _buildLegendDot('Garis Target', const Color(0xFFEF4444)),
            ],
          ),
        ],
      ),
    );
  }

  Widget _buildLegendDot(String label, Color color) {
    return Row(
      mainAxisSize: MainAxisSize.min,
      children: [
        Container(
          width: 14,
          height: 4,
          decoration: BoxDecoration(color: color, borderRadius: BorderRadius.circular(2)),
        ),
        const SizedBox(width: 6),
        Text(
          label,
          style: const TextStyle(
            fontFamily: 'Poppins',
            fontSize: 11,
            fontWeight: FontWeight.w600,
            color: Color(0xFF64748B),
          ),
        ),
      ],
    );
  }

  Widget _buildLineChart() {
    final sorted = List<DataPencapaian>.from(_yearlyData)
      ..sort((a, b) => a.tanggal.compareTo(b.tanggal));
    final data = sorted.length > 12 ? sorted.sublist(sorted.length - 12) : sorted;

    final pendapatanSpots = <FlSpot>[];
    final targetSpots = <FlSpot>[];
    double maxY = 0;

    for (int i = 0; i < data.length; i++) {
      final p = double.tryParse(data[i].pendapatan) ?? 0;
      final t = double.tryParse(data[i].target) ?? 0;
      pendapatanSpots.add(FlSpot(i.toDouble(), p));
      targetSpots.add(FlSpot(i.toDouble(), t));
      if (p > maxY) maxY = p;
      if (t > maxY) maxY = t;
    }
    maxY = maxY * 1.25;
    if (maxY < 100) maxY = 100;

    final interval = _calcNiceInterval(maxY);

    return SizedBox(
      height: 200,
      child: LineChart(
        LineChartData(
          minY: 0,
          maxY: maxY,
          lineTouchData: LineTouchData(
            enabled: true,
            touchTooltipData: LineTouchTooltipData(
              tooltipRoundedRadius: 12,
              fitInsideHorizontally: true,
              getTooltipItems: (spots) => spots.map((spot) {
                final label = spot.barIndex == 0 ? 'Pendapatan' : 'Target';
                return LineTooltipItem(
                  '$label\n${_formatRupiah(spot.y.toInt())}',
                  const TextStyle(
                    fontFamily: 'Poppins',
                    color: Colors.white,
                    fontSize: 11,
                    fontWeight: FontWeight.w600,
                  ),
                );
              }).toList(),
            ),
          ),
          gridData: FlGridData(
            show: true,
            drawVerticalLine: false,
            horizontalInterval: interval,
            getDrawingHorizontalLine: (value) => FlLine(
              color: const Color(0xFFF1F5F9),
              strokeWidth: 1.2,
              dashArray: [5, 5],
            ),
          ),
          titlesData: FlTitlesData(
            topTitles: const AxisTitles(sideTitles: SideTitles(showTitles: false)),
            rightTitles: const AxisTitles(sideTitles: SideTitles(showTitles: false)),
            leftTitles: AxisTitles(
              sideTitles: SideTitles(
                showTitles: true,
                reservedSize: 46,
                interval: interval,
                getTitlesWidget: (value, meta) {
                  if (value == 0) return const SizedBox();
                  return Text(
                    _formatAxisLabel(value),
                    style: const TextStyle(
                      fontFamily: 'Poppins',
                      fontSize: 10,
                      fontWeight: FontWeight.w500,
                      color: Color(0xFF94A3B8),
                    ),
                  );
                },
              ),
            ),
            bottomTitles: AxisTitles(
              sideTitles: SideTitles(
                showTitles: true,
                reservedSize: 28,
                interval: 1,
                getTitlesWidget: (value, meta) {
                  final idx = value.toInt();
                  if (idx < 0 || idx >= data.length) return const SizedBox();
                  final dt = data[idx].tanggal;
                  final label = _monthShort[dt.month - 1];
                  return Padding(
                    padding: const EdgeInsets.only(top: 8),
                    child: Text(
                      label,
                      style: const TextStyle(
                        fontFamily: 'Poppins',
                        fontSize: 10,
                        fontWeight: FontWeight.w600,
                        color: Color(0xFF64748B),
                      ),
                    ),
                  );
                },
              ),
            ),
          ),
          borderData: FlBorderData(show: false),
          lineBarsData: [
            LineChartBarData(
              spots: pendapatanSpots,
              isCurved: true,
              curveSmoothness: 0.35,
              color: const Color(0xFF0284C7),
              barWidth: 3.5,
              isStrokeCapRound: true,
              dotData: FlDotData(
                show: true,
                getDotPainter: (spot, percent, barData, index) => FlDotCirclePainter(
                  radius: 4.5,
                  color: Colors.white,
                  strokeWidth: 2.5,
                  strokeColor: const Color(0xFF0284C7),
                ),
              ),
              belowBarData: BarAreaData(
                show: true,
                gradient: LinearGradient(
                  begin: Alignment.topCenter,
                  end: Alignment.bottomCenter,
                  colors: [
                    const Color(0xFF0284C7).withValues(alpha: 0.22),
                    const Color(0xFF0284C7).withValues(alpha: 0.0),
                  ],
                ),
              ),
            ),
            LineChartBarData(
              spots: targetSpots,
              isCurved: false,
              color: const Color(0xFFEF4444).withValues(alpha: 0.7),
              barWidth: 2,
              isStrokeCapRound: true,
              dashArray: [6, 4],
              dotData: const FlDotData(show: false),
              belowBarData: BarAreaData(show: false),
            ),
          ],
        ),
      ),
    );
  }

  // ════════════════════════════════════════════════════
  // TARGET PROGRESS
  // ════════════════════════════════════════════════════
  Widget _buildTargetProgress(PendapatanResponse data) {
    final progress = data.target > 0
        ? (data.totalKeseluruhan / data.target).clamp(0.0, 1.5)
        : 0.0;
    final isReached = data.totalKeseluruhan >= data.target && data.target > 0;
    final remaining = (data.target - data.totalKeseluruhan).clamp(0, data.target);

    return Container(
      padding: const EdgeInsets.all(20),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(22),
        border: Border.all(color: const Color(0xFFE2E8F0), width: 1.2),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.04),
            blurRadius: 16,
            offset: const Offset(0, 4),
          ),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Row(
                children: [
                  Container(
                    width: 38,
                    height: 38,
                    decoration: BoxDecoration(
                      gradient: LinearGradient(
                        colors: isReached
                            ? const [Color(0xFF16A34A), Color(0xFF15803D)]
                            : const [Color(0xFFF59E0B), Color(0xFFD97706)],
                        begin: Alignment.topLeft,
                        end: Alignment.bottomRight,
                      ),
                      borderRadius: BorderRadius.circular(12),
                      boxShadow: [
                        BoxShadow(
                          color: (isReached ? const Color(0xFF16A34A) : const Color(0xFFF59E0B))
                              .withValues(alpha: 0.35),
                          blurRadius: 8,
                          offset: const Offset(0, 2),
                        ),
                      ],
                    ),
                    child: Center(
                      child: Icon(
                        isReached ? Iconsax.medal_star5 : Iconsax.flag,
                        size: 20,
                        color: Colors.white,
                      ),
                    ),
                  ),
                  const SizedBox(width: 12),
                  const Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        'Pencapaian Target',
                        style: TextStyle(
                          fontFamily: 'Poppins',
                          fontSize: 15,
                          fontWeight: FontWeight.w700,
                          color: _textPrimary,
                        ),
                      ),
                      Text(
                        'Target bulanan teknisi',
                        style: TextStyle(
                          fontFamily: 'Poppins',
                          fontSize: 11,
                          color: _textSecondary,
                        ),
                      ),
                    ],
                  ),
                ],
              ),
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 5),
                decoration: BoxDecoration(
                  color: (isReached ? const Color(0xFF16A34A) : const Color(0xFFF59E0B))
                      .withValues(alpha: 0.12),
                  borderRadius: BorderRadius.circular(14),
                ),
                child: Text(
                  '${(progress * 100).toStringAsFixed(0)}%',
                  style: TextStyle(
                    fontFamily: 'Poppins',
                    fontSize: 13,
                    fontWeight: FontWeight.w700,
                    color: isReached ? const Color(0xFF16A34A) : const Color(0xFFD97706),
                  ),
                ),
              ),
            ],
          ),
          const SizedBox(height: 18),
          ClipRRect(
            borderRadius: BorderRadius.circular(10),
            child: LinearProgressIndicator(
              value: progress.clamp(0.0, 1.0),
              minHeight: 12,
              backgroundColor: const Color(0xFFF1F5F9),
              valueColor: AlwaysStoppedAnimation<Color>(
                isReached ? const Color(0xFF16A34A) : const Color(0xFF0284C7),
              ),
            ),
          ),
          const SizedBox(height: 14),
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  const Text(
                    'Realisasi',
                    style: TextStyle(
                      fontFamily: 'Poppins',
                      fontSize: 10.5,
                      color: _textSecondary,
                    ),
                  ),
                  Text(
                    _formatRupiah(data.totalKeseluruhan),
                    style: const TextStyle(
                      fontFamily: 'Poppins',
                      fontSize: 15,
                      fontWeight: FontWeight.w700,
                      color: Color(0xFF0284C7),
                    ),
                  ),
                ],
              ),
              Column(
                crossAxisAlignment: CrossAxisAlignment.end,
                children: [
                  const Text(
                    'Target Bulanan',
                    style: TextStyle(
                      fontFamily: 'Poppins',
                      fontSize: 10.5,
                      color: _textSecondary,
                    ),
                  ),
                  Text(
                    _formatRupiah(data.target),
                    style: const TextStyle(
                      fontFamily: 'Poppins',
                      fontSize: 15,
                      fontWeight: FontWeight.w700,
                      color: _textPrimary,
                    ),
                  ),
                ],
              ),
            ],
          ),
          if (remaining > 0) ...[
            const SizedBox(height: 12),
            Container(
              width: double.infinity,
              padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
              decoration: BoxDecoration(
                color: const Color(0xFFFFFBEB),
                borderRadius: BorderRadius.circular(10),
                border: Border.all(color: const Color(0xFFFEF3C7)),
              ),
              child: Row(
                children: [
                  const Icon(Icons.info_outline_rounded, size: 16, color: Color(0xFFD97706)),
                  const SizedBox(width: 8),
                  Expanded(
                    child: Text(
                      'Sisa target: ${_formatRupiah(remaining)} untuk capai bonus',
                      style: const TextStyle(
                        fontFamily: 'Poppins',
                        fontSize: 11,
                        fontWeight: FontWeight.w500,
                        color: Color(0xFFB45309),
                      ),
                    ),
                  ),
                ],
              ),
            ),
          ],
        ],
      ),
    );
  }

  // ════════════════════════════════════════════════════
  // DETAIL PENDAPATAN
  // ════════════════════════════════════════════════════
  Widget _buildPendapatanDetail(PendapatanResponse data) {
    return Container(
      padding: const EdgeInsets.all(20),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(22),
        border: Border.all(color: const Color(0xFFE2E8F0), width: 1.2),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.04),
            blurRadius: 16,
            offset: const Offset(0, 4),
          ),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Container(
                width: 38,
                height: 38,
                decoration: BoxDecoration(
                  gradient: const LinearGradient(
                    colors: [Color(0xFF6366F1), Color(0xFF4F46E5)],
                    begin: Alignment.topLeft,
                    end: Alignment.bottomRight,
                  ),
                  borderRadius: BorderRadius.circular(12),
                  boxShadow: [
                    BoxShadow(
                      color: const Color(0xFF6366F1).withValues(alpha: 0.35),
                      blurRadius: 8,
                      offset: const Offset(0, 2),
                    ),
                  ],
                ),
                child: const Center(
                  child: Icon(Iconsax.receipt_text, size: 19, color: Colors.white),
                ),
              ),
              const SizedBox(width: 12),
              const Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    'Rincian Pendapatan',
                    style: TextStyle(
                      fontFamily: 'Poppins',
                      fontSize: 16,
                      fontWeight: FontWeight.w700,
                      color: _textPrimary,
                    ),
                  ),
                  Text(
                    'Kalkulasi operasional teknisi',
                    style: TextStyle(
                      fontFamily: 'Poppins',
                      fontSize: 11,
                      color: _textSecondary,
                    ),
                  ),
                ],
              ),
            ],
          ),
          const SizedBox(height: 20),
          _buildDetailRow('Jumlah Kegiatan', '${data.jumlahKegiatan} Tugas', Iconsax.task_square, const Color(0xFF0284C7)),
          _buildDetailRow('Tugas Selesai', '${data.selesai} Tugas', Iconsax.tick_circle, const Color(0xFF16A34A)),
          _buildDetailRow('Total Nilai Invoice', _formatRupiah(data.invoice), Iconsax.document_text, const Color(0xFF0D9488)),
          _buildDetailRow('Fee Teknisi', _formatRupiah(data.fee), Iconsax.money_recive, const Color(0xFFF59E0B)),
          if (data.bonus > 0)
            _buildDetailRow('Bonus Tambahan', _formatRupiah(data.bonus), Iconsax.medal_star, const Color(0xFF16A34A)),
          Divider(height: 28, color: Colors.grey.withValues(alpha: 0.15)),
          Container(
            padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
            decoration: BoxDecoration(
              gradient: const LinearGradient(
                colors: [Color(0xFF0F172A), Color(0xFF1E293B)],
                begin: Alignment.topLeft,
                end: Alignment.bottomRight,
              ),
              borderRadius: BorderRadius.circular(16),
            ),
            child: Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                const Text(
                  'Total Pendapatan',
                  style: TextStyle(
                    fontFamily: 'Poppins',
                    fontSize: 13,
                    fontWeight: FontWeight.w600,
                    color: Colors.white70,
                  ),
                ),
                Text(
                  _formatRupiah(data.totalKeseluruhan),
                  style: const TextStyle(
                    fontFamily: 'Poppins',
                    fontSize: 17,
                    fontWeight: FontWeight.w800,
                    color: Color(0xFF38BDF8),
                  ),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildDetailRow(String label, String value, IconData icon, Color color) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 12),
      child: Row(
        children: [
          Container(
            width: 32,
            height: 32,
            decoration: BoxDecoration(
              color: color.withValues(alpha: 0.1),
              borderRadius: BorderRadius.circular(9),
            ),
            child: Icon(icon, size: 16, color: color),
          ),
          const SizedBox(width: 12),
          Expanded(
            child: Text(
              label,
              style: const TextStyle(
                fontFamily: 'Poppins',
                fontSize: 13,
                fontWeight: FontWeight.w500,
                color: _textSecondary,
              ),
            ),
          ),
          Text(
            value,
            style: const TextStyle(
              fontFamily: 'Poppins',
              fontSize: 14,
              fontWeight: FontWeight.w700,
              color: _textPrimary,
            ),
          ),
        ],
      ),
    );
  }

  // ════════════════════════════════════════════════════
  // SHARED WIDGETS
  // ════════════════════════════════════════════════════
  Widget _buildChartCard({
    required IconData icon,
    required Color iconColor,
    required String title,
    required String subtitle,
    required Widget child,
  }) {
    return Container(
      padding: const EdgeInsets.all(20),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(22),
        border: Border.all(color: const Color(0xFFE2E8F0), width: 1.2),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.04),
            blurRadius: 16,
            offset: const Offset(0, 4),
          ),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Container(
                width: 38,
                height: 38,
                decoration: BoxDecoration(
                  color: iconColor.withValues(alpha: 0.1),
                  borderRadius: BorderRadius.circular(12),
                ),
                child: Center(
                  child: Icon(icon, size: 20, color: iconColor),
                ),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      title,
                      style: const TextStyle(
                        fontFamily: 'Poppins',
                        fontSize: 16,
                        fontWeight: FontWeight.w700,
                        color: _textPrimary,
                        letterSpacing: -0.2,
                      ),
                    ),
                    Text(
                      subtitle,
                      style: const TextStyle(
                        fontFamily: 'Poppins',
                        fontSize: 11,
                        fontWeight: FontWeight.w500,
                        color: _textSecondary,
                      ),
                    ),
                  ],
                ),
              ),
            ],
          ),
          const SizedBox(height: 22),
          child,
        ],
      ),
    );
  }

  Widget _buildChartMessage(String msg, IconData icon, Color color) {
    return SizedBox(
      height: 140,
      child: Center(
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Icon(icon, size: 36, color: color.withValues(alpha: 0.4)),
            const SizedBox(height: 8),
            Text(msg,
              style: const TextStyle(
                fontFamily: 'Poppins', fontSize: 13, color: _textSecondary,
              ),
            ),
          ],
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
            padding: const EdgeInsets.all(20),
            decoration: BoxDecoration(
              color: Colors.white,
              borderRadius: BorderRadius.circular(20),
              boxShadow: [
                BoxShadow(
                  color: Colors.black.withValues(alpha: 0.05),
                  blurRadius: 20,
                ),
              ],
            ),
            child: const CircularProgressIndicator(color: _primaryBlue, strokeWidth: 3),
          ),
          const SizedBox(height: 20),
          const Text(
            'Memuat data...',
            style: TextStyle(fontFamily: 'Poppins', fontSize: 14, color: _textSecondary),
          ),
        ],
      ),
    );
  }

  // ════════════════════════════════════════════════════
  // MONTH PICKER MODAL
  // ════════════════════════════════════════════════════
  Widget _buildMonthYearPickerModal() {
    int tempMonth = _selectedMonth;
    int tempYear = _selectedYear;

    return StatefulBuilder(
      builder: (context, setModalState) {
        return Container(
          decoration: const BoxDecoration(
            color: Colors.white,
            borderRadius: BorderRadius.vertical(top: Radius.circular(24)),
          ),
          padding: const EdgeInsets.all(20),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              Container(
                width: 40, height: 4,
                decoration: BoxDecoration(
                  color: Colors.grey[300],
                  borderRadius: BorderRadius.circular(2),
                ),
              ),
              const SizedBox(height: 20),
              const Text(
                'Pilih Bulan & Tahun',
                style: TextStyle(
                  fontFamily: 'Poppins', fontSize: 18,
                  fontWeight: FontWeight.w600, color: _textPrimary,
                ),
              ),
              const SizedBox(height: 24),
              Row(
                mainAxisAlignment: MainAxisAlignment.center,
                children: [
                  IconButton(
                    onPressed: () => setModalState(() => tempYear--),
                    icon: Container(
                      padding: const EdgeInsets.all(8),
                      decoration: BoxDecoration(
                        color: _primaryBlue.withValues(alpha: 0.1),
                        borderRadius: BorderRadius.circular(8),
                      ),
                      child: const Icon(Icons.chevron_left, color: _primaryBlue),
                    ),
                  ),
                  Container(
                    padding: const EdgeInsets.symmetric(horizontal: 24, vertical: 12),
                    decoration: BoxDecoration(
                      color: _primaryBlue,
                      borderRadius: BorderRadius.circular(12),
                    ),
                    child: Text(tempYear.toString(),
                      style: const TextStyle(
                        fontFamily: 'Poppins', fontSize: 18,
                        fontWeight: FontWeight.w600, color: Colors.white,
                      ),
                    ),
                  ),
                  IconButton(
                    onPressed: () => setModalState(() => tempYear++),
                    icon: Container(
                      padding: const EdgeInsets.all(8),
                      decoration: BoxDecoration(
                        color: _primaryBlue.withValues(alpha: 0.1),
                        borderRadius: BorderRadius.circular(8),
                      ),
                      child: const Icon(Icons.chevron_right, color: _primaryBlue),
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 20),
              GridView.builder(
                shrinkWrap: true,
                physics: const NeverScrollableScrollPhysics(),
                gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
                  crossAxisCount: 4, childAspectRatio: 2,
                  crossAxisSpacing: 10, mainAxisSpacing: 10,
                ),
                itemCount: 12,
                itemBuilder: (context, index) {
                  final month = index + 1;
                  final isSelected = month == tempMonth;
                  return InkWell(
                    onTap: () => setModalState(() => tempMonth = month),
                    borderRadius: BorderRadius.circular(10),
                    child: Container(
                      decoration: BoxDecoration(
                        color: isSelected ? _primaryBlue : _primaryBlue.withValues(alpha: 0.1),
                        borderRadius: BorderRadius.circular(10),
                      ),
                      child: Center(
                        child: Text(_monthShort[index],
                          style: TextStyle(
                            fontFamily: 'Poppins', fontSize: 13,
                            fontWeight: FontWeight.w500,
                            color: isSelected ? Colors.white : _primaryBlue,
                          ),
                        ),
                      ),
                    ),
                  );
                },
              ),
              const SizedBox(height: 24),
              SizedBox(
                width: double.infinity,
                child: ElevatedButton(
                  onPressed: () {
                    setState(() {
                      _selectedMonth = tempMonth;
                      _selectedYear = tempYear;
                    });
                    Navigator.pop(context);
                    _loadData();
                  },
                  style: ElevatedButton.styleFrom(
                    backgroundColor: _primaryBlue,
                    foregroundColor: Colors.white,
                    padding: const EdgeInsets.symmetric(vertical: 16),
                    shape: RoundedRectangleBorder(
                      borderRadius: BorderRadius.circular(14),
                    ),
                    elevation: 0,
                  ),
                  child: const Text('Terapkan',
                    style: TextStyle(
                      fontFamily: 'Poppins', fontSize: 16, fontWeight: FontWeight.w600,
                    ),
                  ),
                ),
              ),
              SizedBox(height: MediaQuery.of(context).padding.bottom + 10),
            ],
          ),
        );
      },
    );
  }

  // ─── Coach Mark for PencapaianPage ────────────────
  Future<void> _showStatistikCoachMark() async {
    await Future.delayed(const Duration(milliseconds: 2000));
    if (!mounted || _coachMarkShown || CoachMarkHelper.isActive) return;
    _coachMarkShown = true;

    final targets = <TargetFocus>[
      // Step 1: Period selector
      TargetFocus(
        identify: 'month_selector',
        keyTarget: _keyMonthSelector,
        alignSkip: Alignment.bottomRight,
        enableOverlayTab: true,
        shape: ShapeLightFocus.RRect,
        radius: 16,
        contents: [
          TargetContent(
            align: ContentAlign.bottom,
            builder: (context, controller) => CoachMarkHelper.buildTooltip(
              onClose: () => controller.skip(),
              title: 'Pilih Periode',
              descriptions: ['Tap untuk ganti bulan & tahun statistik'],
              arrowUp: true,
              step: '1/4',
            ),
          ),
        ],
      ),
      // Step 2: Summary cards
      TargetFocus(
        identify: 'summary_cards',
        keyTarget: _keySummaryCards,
        alignSkip: Alignment.bottomRight,
        enableOverlayTab: true,
        shape: ShapeLightFocus.RRect,
        radius: 18,
        contents: [
          TargetContent(
            align: ContentAlign.bottom,
            builder: (context, controller) => CoachMarkHelper.buildTooltip(
              onClose: () => controller.skip(),
              title: 'Ringkasan Kinerja',
              descriptions: ['Kegiatan, pendapatan, bonus & progress target bulan ini'],
              arrowUp: true,
              step: '2/4',
            ),
          ),
        ],
      ),
      // Step 3: Bar chart breakdown
      TargetFocus(
        identify: 'bar_chart',
        keyTarget: _keyBarChart,
        alignSkip: Alignment.bottomRight,
        enableOverlayTab: true,
        shape: ShapeLightFocus.RRect,
        radius: 18,
        contents: [
          TargetContent(
            align: ContentAlign.top,
            builder: (context, controller) => CoachMarkHelper.buildTooltip(
              onClose: () => controller.skip(),
              title: 'Rincian Kegiatan',
              descriptions: ['Grafik breakdown kegiatan selesai per kategori'],
              step: '3/4',
            ),
          ),
        ],
      ),
      // Step 4: Target progress
      TargetFocus(
        identify: 'target_progress',
        keyTarget: _keyTargetProgress,
        alignSkip: Alignment.topRight,
        enableOverlayTab: true,
        shape: ShapeLightFocus.RRect,
        radius: 18,
        contents: [
          TargetContent(
            align: ContentAlign.top,
            builder: (context, controller) => CoachMarkHelper.buildTooltip(
              onClose: () => controller.skip(),
              title: 'Progress Target',
              descriptions: ['Perbandingan pendapatan vs target bulanan kamu'],
              step: '4/4',
            ),
          ),
        ],
      ),
    ];
    CoachMarkHelper.show(context: context, targets: targets, key: 'pencapaian');
  }
}
