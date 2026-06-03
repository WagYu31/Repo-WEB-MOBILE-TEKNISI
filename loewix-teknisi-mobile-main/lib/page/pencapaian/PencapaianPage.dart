import 'package:fl_chart/fl_chart.dart';
import 'package:flutter/material.dart';
import 'package:iconsax/iconsax.dart';
import 'package:provider/provider.dart';

import '../../service/api/ApiTeknisi.dart';
import '../../service/model/pencapaian/PencapaianChartGet.dart';
import '../../service/provider/Pencapaian/PencapaianProvider.dart';
import '../../service/provider/preferences/PreferencesIDProvider.dart';
import '../../service/model/pencapaian/PencapaianResponse.dart';
import '../../service/model/pencapaian/PendapatanResponse.dart';

class PencapaianPage extends StatefulWidget {
  const PencapaianPage({super.key});

  @override
  State<PencapaianPage> createState() => _PencapaianPageState();
}

class _PencapaianPageState extends State<PencapaianPage>
    with SingleTickerProviderStateMixin {
  // ─── Premium Color Palette ─────────────────────
  static const Color _primaryBlue = Color(0xFF0EA5E9);
  static const Color _lightBlue = Color(0xFF38BDF8);
  static const Color _successGreen = Color(0xFF14B8A6);
  static const Color _warningAmber = Color(0xFFF97316);
  static const Color _errorRed = Color(0xFFF43F5E);
  static const Color _purpleAccent = Color(0xFF6366F1);
  static const Color _cyanAccent = Color(0xFF06B6D4);
  static const Color _pinkAccent = Color(0xFFEC4899);
  static const Color _textPrimary = Color(0xFF0F172A);
  static const Color _textSecondary = Color(0xFF64748B);
  static const Color _bgColor = Color(0xFFF1F5F9);

  int _selectedMonth = DateTime.now().month;
  int _selectedYear = DateTime.now().year;
  int? _teknisiId;

  // Yearly trend data
  List<DataPencapaian> _yearlyData = [];
  bool _isLoadingYearly = false;

  late AnimationController _animController;
  late Animation<double> _fadeAnim;

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
          _yearlyData = response.data;
          _isLoadingYearly = false;
        });
      }
    } catch (e) {
      if (mounted) setState(() => _isLoadingYearly = false);
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

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: _bgColor,
      body: SafeArea(
        child: FadeTransition(
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
                    // ─── HEADER ──────────────────────────
                    SliverToBoxAdapter(child: _buildHeader()),
                    // ─── MONTH SELECTOR ──────────────────
                    SliverToBoxAdapter(
                      child: Padding(
                        padding: const EdgeInsets.fromLTRB(20, 20, 20, 0),
                        child: _buildMonthSelector(),
                      ),
                    ),
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
                            padding: const EdgeInsets.fromLTRB(20, 24, 20, 0),
                            child: _buildSummaryCards(
                              provider.pencapaianData,
                              provider.pendapatanData,
                            ),
                          ),
                        ),

                      // ─── BAR CHART — Rincian Kegiatan ──
                      SliverToBoxAdapter(
                        child: Padding(
                          padding: const EdgeInsets.fromLTRB(20, 24, 20, 0),
                          child: _buildBarChartSection(provider),
                        ),
                      ),

                      // ─── LINE CHART — Tren Pendapatan ──
                      SliverToBoxAdapter(
                        child: Padding(
                          padding: const EdgeInsets.fromLTRB(20, 24, 20, 0),
                          child: _buildLineChartSection(),
                        ),
                      ),

                      // ─── PENDAPATAN TARGET PROGRESS ────
                      if (provider.pendapatanState == PencapaianState.loaded &&
                          provider.pendapatanData != null)
                        SliverToBoxAdapter(
                          child: Padding(
                            padding: const EdgeInsets.fromLTRB(20, 24, 20, 0),
                            child: _buildTargetProgress(provider.pendapatanData!),
                          ),
                        ),

                      // ─── DETAIL PENDAPATAN ─────────────
                      if (provider.pendapatanState == PencapaianState.loaded &&
                          provider.pendapatanData != null)
                        SliverToBoxAdapter(
                          child: Padding(
                            padding: const EdgeInsets.fromLTRB(20, 24, 20, 32),
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
      ),
    );
  }

  // ════════════════════════════════════════════════════
  // HEADER
  // ════════════════════════════════════════════════════
  Widget _buildHeader() {
    return Padding(
      padding: const EdgeInsets.fromLTRB(20, 16, 20, 0),
      child: Row(
        children: [
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
                child: const Icon(Iconsax.menu_1, color: _primaryBlue, size: 22),
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
                    fontSize: 12,
                    color: _textSecondary,
                  ),
                ),
                Text(
                  'Statistik',
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
          Material(
            color: Colors.white,
            borderRadius: BorderRadius.circular(12),
            child: InkWell(
              onTap: () {
                _loadData();
                _loadYearlyData();
              },
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
                child: const Icon(Icons.refresh_rounded, color: _primaryBlue, size: 22),
              ),
            ),
          ),
        ],
      ),
    );
  }

  // ════════════════════════════════════════════════════
  // MONTH SELECTOR
  // ════════════════════════════════════════════════════
  Widget _buildMonthSelector() {
    return InkWell(
      onTap: _showMonthYearPicker,
      borderRadius: BorderRadius.circular(16),
      child: Container(
        padding: const EdgeInsets.all(16),
        decoration: BoxDecoration(
          gradient: const LinearGradient(
            colors: [Color(0xFF0F172A), Color(0xFF1D4ED8)],
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
                Icons.calendar_month_rounded,
                color: Colors.white,
                size: 22,
              ),
            ),
            const SizedBox(width: 14),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    'Periode',
                    style: TextStyle(
                      fontFamily: 'Poppins',
                      fontSize: 11,
                      color: Colors.white.withValues(alpha: 0.7),
                    ),
                  ),
                  const SizedBox(height: 2),
                  Text(
                    '${_monthNames[_selectedMonth - 1]} $_selectedYear',
                    style: const TextStyle(
                      fontFamily: 'Poppins',
                      fontSize: 16,
                      fontWeight: FontWeight.w600,
                      color: Colors.white,
                    ),
                  ),
                ],
              ),
            ),
            Container(
              padding: const EdgeInsets.all(8),
              decoration: BoxDecoration(
                color: Colors.white.withValues(alpha: 0.2),
                borderRadius: BorderRadius.circular(8),
              ),
              child: const Icon(
                Icons.arrow_forward_ios_rounded,
                size: 14,
                color: Colors.white,
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
      children: [
        Row(
          children: [
            Expanded(
              child: _buildStatCard(
                icon: Iconsax.task_square,
                title: 'Kegiatan',
                value: totalKegiatan.toString(),
                subtitle: 'Selesai bulan ini',
                gradient: [_primaryBlue, _lightBlue],
              ),
            ),
            const SizedBox(width: 12),
            Expanded(
              child: _buildStatCard(
                icon: Iconsax.wallet_3,
                title: 'Pendapatan',
                value: _formatRupiahShort(totalPendapatan),
                subtitle: 'Total bulan ini',
                gradient: [_successGreen, const Color(0xFF34D399)],
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
                gradient: [_warningAmber, const Color(0xFFFBBF24)],
              ),
            ),
            const SizedBox(width: 12),
            Expanded(
              child: _buildStatCard(
                icon: Iconsax.diagram,
                title: 'Target',
                value: '$progress%',
                subtitle: 'Progress tercapai',
                gradient: [_purpleAccent, const Color(0xFFA78BFA)],
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
  }) {
    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(18),
        boxShadow: [
          BoxShadow(
            color: gradient[0].withValues(alpha: 0.08),
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
                padding: const EdgeInsets.all(8),
                decoration: BoxDecoration(
                  gradient: LinearGradient(colors: gradient),
                  borderRadius: BorderRadius.circular(10),
                ),
                child: Icon(icon, size: 18, color: Colors.white),
              ),
              const SizedBox(width: 8),
              Expanded(
                child: Text(
                  title,
                  style: const TextStyle(
                    fontFamily: 'Poppins',
                    fontSize: 11,
                    fontWeight: FontWeight.w500,
                    color: _textSecondary,
                  ),
                ),
              ),
            ],
          ),
          const SizedBox(height: 12),
          Text(
            value,
            style: TextStyle(
              fontFamily: 'Poppins',
              fontSize: 22,
              fontWeight: FontWeight.w700,
              color: gradient[0],
            ),
            maxLines: 1,
            overflow: TextOverflow.ellipsis,
          ),
          const SizedBox(height: 2),
          Text(
            subtitle,
            style: const TextStyle(
              fontFamily: 'Poppins',
              fontSize: 10,
              color: _textSecondary,
            ),
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
    final maxVal = entries.map((e) => e.value).reduce((a, b) => a > b ? a : b);
    final maxY = (maxVal + 2).toDouble();

    return Column(
      children: [
        SizedBox(
          height: 200,
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
                    reservedSize: 32,
                    interval: maxY > 10 ? (maxY / 5).ceilToDouble() : 1,
                    getTitlesWidget: (value, meta) => Text(
                      value.toInt().toString(),
                      style: const TextStyle(
                        fontFamily: 'Poppins', fontSize: 11, color: _textSecondary,
                      ),
                    ),
                  ),
                ),
                bottomTitles: AxisTitles(
                  sideTitles: SideTitles(
                    showTitles: true,
                    reservedSize: 36,
                    getTitlesWidget: (value, meta) {
                      final idx = value.toInt();
                      if (idx < 0 || idx >= entries.length) return const SizedBox();
                      final label = entries[idx].key;
                      final short = label.length > 6
                          ? '${label.substring(0, 5)}..'
                          : _capitalizeFirst(label);
                      return Padding(
                        padding: const EdgeInsets.only(top: 8),
                        child: Text(short,
                          style: const TextStyle(
                            fontFamily: 'Poppins', fontSize: 9,
                            fontWeight: FontWeight.w500, color: _textSecondary,
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
                  color: const Color(0xFFE5E7EB), strokeWidth: 1, dashArray: [5, 5],
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
                      color: color,
                      width: entries.length > 5 ? 16 : 28,
                      borderRadius: const BorderRadius.vertical(top: Radius.circular(8)),
                      backDrawRodData: BackgroundBarChartRodData(
                        show: true, toY: maxY, color: color.withValues(alpha: 0.06),
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
          spacing: 12,
          runSpacing: 8,
          children: entries.asMap().entries.map((e) {
            final color = _barColors[e.key % _barColors.length];
            return Row(
              mainAxisSize: MainAxisSize.min,
              children: [
                Container(
                  width: 10, height: 10,
                  decoration: BoxDecoration(
                    color: color, borderRadius: BorderRadius.circular(3),
                  ),
                ),
                const SizedBox(width: 4),
                Text(
                  '${_capitalizeFirst(e.value.key)} (${e.value.value})',
                  style: const TextStyle(
                    fontFamily: 'Poppins', fontSize: 10, color: _textSecondary,
                  ),
                ),
              ],
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
      iconColor: _successGreen,
      title: 'Tren Pendapatan',
      subtitle: 'Pendapatan vs Target per bulan',
      child: Column(
        children: [
          content,
          const SizedBox(height: 16),
          Row(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              _buildLegendDot('Pendapatan', _primaryBlue),
              const SizedBox(width: 24),
              _buildLegendDot('Target', _errorRed),
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
          width: 14, height: 4,
          decoration: BoxDecoration(color: color, borderRadius: BorderRadius.circular(2)),
        ),
        const SizedBox(width: 6),
        Text(label,
          style: const TextStyle(
            fontFamily: 'Poppins', fontSize: 11, fontWeight: FontWeight.w500, color: _textSecondary,
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
    maxY = maxY * 1.2;
    if (maxY == 0) maxY = 1000000;

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
                    fontFamily: 'Poppins', color: Colors.white, fontSize: 11,
                    fontWeight: FontWeight.w500,
                  ),
                );
              }).toList(),
            ),
          ),
          gridData: FlGridData(
            show: true, drawVerticalLine: false,
            horizontalInterval: maxY / 4,
            getDrawingHorizontalLine: (value) => FlLine(
              color: const Color(0xFFE5E7EB), strokeWidth: 1, dashArray: [5, 5],
            ),
          ),
          titlesData: FlTitlesData(
            topTitles: const AxisTitles(sideTitles: SideTitles(showTitles: false)),
            rightTitles: const AxisTitles(sideTitles: SideTitles(showTitles: false)),
            leftTitles: AxisTitles(
              sideTitles: SideTitles(
                showTitles: true,
                reservedSize: 46,
                interval: maxY / 4,
                getTitlesWidget: (value, meta) {
                  if (value == 0) return const SizedBox();
                  String label;
                  if (value >= 1000000) {
                    label = '${(value / 1000000).toStringAsFixed(1)}jt';
                  } else if (value >= 1000) {
                    label = '${(value / 1000).toStringAsFixed(0)}rb';
                  } else {
                    label = value.toInt().toString();
                  }
                  return Text(label,
                    style: const TextStyle(
                      fontFamily: 'Poppins', fontSize: 9, color: _textSecondary,
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
                  return Padding(
                    padding: const EdgeInsets.only(top: 8),
                    child: Text(_monthShort[data[idx].tanggal.month - 1],
                      style: const TextStyle(
                        fontFamily: 'Poppins', fontSize: 9,
                        fontWeight: FontWeight.w500, color: _textSecondary,
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
              curveSmoothness: 0.3,
              color: _primaryBlue,
              barWidth: 3,
              isStrokeCapRound: true,
              dotData: FlDotData(
                show: true,
                getDotPainter: (spot, percent, barData, index) =>
                    FlDotCirclePainter(
                  radius: 4, color: Colors.white,
                  strokeWidth: 2.5, strokeColor: _primaryBlue,
                ),
              ),
              belowBarData: BarAreaData(
                show: true,
                gradient: LinearGradient(
                  begin: Alignment.topCenter,
                  end: Alignment.bottomCenter,
                  colors: [
                    _primaryBlue.withValues(alpha: 0.15),
                    _primaryBlue.withValues(alpha: 0.0),
                  ],
                ),
              ),
            ),
            LineChartBarData(
              spots: targetSpots,
              isCurved: false,
              color: _errorRed.withValues(alpha: 0.6),
              barWidth: 2,
              isStrokeCapRound: true,
              dashArray: [8, 4],
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
    final isReached = data.totalKeseluruhan >= data.target;

    return Container(
      padding: const EdgeInsets.all(20),
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
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Row(
                children: [
                  Container(
                    padding: const EdgeInsets.all(8),
                    decoration: BoxDecoration(
                      color: (isReached ? _successGreen : _warningAmber).withValues(alpha: 0.1),
                      borderRadius: BorderRadius.circular(10),
                    ),
                    child: Icon(
                      isReached ? Iconsax.medal_star5 : Iconsax.flag,
                      size: 20,
                      color: isReached ? _successGreen : _warningAmber,
                    ),
                  ),
                  const SizedBox(width: 10),
                  const Text(
                    'Progress Target',
                    style: TextStyle(
                      fontFamily: 'Poppins',
                      fontSize: 14,
                      fontWeight: FontWeight.w600,
                      color: _textPrimary,
                    ),
                  ),
                ],
              ),
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                decoration: BoxDecoration(
                  color: (isReached ? _successGreen : _warningAmber).withValues(alpha: 0.1),
                  borderRadius: BorderRadius.circular(20),
                ),
                child: Text(
                  '${(progress * 100).toStringAsFixed(0)}%',
                  style: TextStyle(
                    fontFamily: 'Poppins',
                    fontSize: 12,
                    fontWeight: FontWeight.w600,
                    color: isReached ? _successGreen : _warningAmber,
                  ),
                ),
              ),
            ],
          ),
          const SizedBox(height: 16),
          ClipRRect(
            borderRadius: BorderRadius.circular(10),
            child: LinearProgressIndicator(
              value: progress.clamp(0.0, 1.0),
              minHeight: 10,
              backgroundColor: const Color(0xFFE5E7EB),
              valueColor: AlwaysStoppedAnimation<Color>(
                isReached ? _successGreen : _primaryBlue,
              ),
            ),
          ),
          const SizedBox(height: 12),
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Text(
                _formatRupiah(data.totalKeseluruhan),
                style: const TextStyle(
                  fontFamily: 'Poppins',
                  fontSize: 16,
                  fontWeight: FontWeight.w600,
                  color: _textPrimary,
                ),
              ),
              Text(
                'Target: ${_formatRupiah(data.target)}',
                style: const TextStyle(
                  fontFamily: 'Poppins',
                  fontSize: 12,
                  color: _textSecondary,
                ),
              ),
            ],
          ),
          if (data.bonus > 0) ...[
            const SizedBox(height: 12),
            Container(
              width: double.infinity,
              padding: const EdgeInsets.all(12),
              decoration: BoxDecoration(
                gradient: LinearGradient(
                  colors: [_successGreen, _successGreen.withValues(alpha: 0.8)],
                ),
                borderRadius: BorderRadius.circular(12),
              ),
              child: Row(
                children: [
                  const Icon(Iconsax.gift, color: Colors.white, size: 20),
                  const SizedBox(width: 10),
                  Expanded(
                    child: Text(
                      'Bonus: ${_formatRupiah(data.bonus)}',
                      style: const TextStyle(
                        fontFamily: 'Poppins',
                        fontSize: 14,
                        fontWeight: FontWeight.w600,
                        color: Colors.white,
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
        borderRadius: BorderRadius.circular(20),
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
                padding: const EdgeInsets.all(8),
                decoration: BoxDecoration(
                  color: _purpleAccent.withValues(alpha: 0.1),
                  borderRadius: BorderRadius.circular(10),
                ),
                child: const Icon(Iconsax.receipt_text, size: 20, color: _purpleAccent),
              ),
              const SizedBox(width: 10),
              const Text(
                'Rincian Pendapatan',
                style: TextStyle(
                  fontFamily: 'Poppins',
                  fontSize: 16,
                  fontWeight: FontWeight.w600,
                  color: _textPrimary,
                ),
              ),
            ],
          ),
          const SizedBox(height: 20),
          _buildDetailRow('Jumlah Kegiatan', data.jumlahKegiatan.toString(), Iconsax.task_square, _primaryBlue),
          _buildDetailRow('Selesai', data.selesai.toString(), Iconsax.tick_circle, _successGreen),
          _buildDetailRow('Invoice', _formatRupiah(data.invoice), Iconsax.document_text, _cyanAccent),
          _buildDetailRow('Fee Teknisi', _formatRupiah(data.fee), Iconsax.money_recive, _warningAmber),
          _buildDetailRow('Total Pendapatan', _formatRupiah(data.totalPendapatan), Iconsax.wallet_money, _purpleAccent),
          if (data.bonus > 0)
            _buildDetailRow('Bonus', _formatRupiah(data.bonus), Iconsax.medal_star, _successGreen),
          const Divider(height: 24),
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              const Text(
                'Total Keseluruhan',
                style: TextStyle(
                  fontFamily: 'Poppins',
                  fontSize: 14,
                  fontWeight: FontWeight.w700,
                  color: _textPrimary,
                ),
              ),
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 6),
                decoration: BoxDecoration(
                  gradient: const LinearGradient(colors: [_primaryBlue, _lightBlue]),
                  borderRadius: BorderRadius.circular(20),
                ),
                child: Text(
                  _formatRupiah(data.totalKeseluruhan),
                  style: const TextStyle(
                    fontFamily: 'Poppins',
                    fontSize: 14,
                    fontWeight: FontWeight.w700,
                    color: Colors.white,
                  ),
                ),
              ),
            ],
          ),
        ],
      ),
    );
  }

  Widget _buildDetailRow(String label, String value, IconData icon, Color color) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 14),
      child: Row(
        children: [
          Container(
            padding: const EdgeInsets.all(6),
            decoration: BoxDecoration(
              color: color.withValues(alpha: 0.1),
              borderRadius: BorderRadius.circular(8),
            ),
            child: Icon(icon, size: 16, color: color),
          ),
          const SizedBox(width: 12),
          Expanded(
            child: Text(label,
              style: const TextStyle(
                fontFamily: 'Poppins', fontSize: 13, color: _textSecondary,
              ),
            ),
          ),
          Text(value,
            style: const TextStyle(
              fontFamily: 'Poppins', fontSize: 14, fontWeight: FontWeight.w600, color: _textPrimary,
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
        borderRadius: BorderRadius.circular(20),
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
                padding: const EdgeInsets.all(8),
                decoration: BoxDecoration(
                  color: iconColor.withValues(alpha: 0.1),
                  borderRadius: BorderRadius.circular(10),
                ),
                child: Icon(icon, size: 20, color: iconColor),
              ),
              const SizedBox(width: 10),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(title,
                      style: const TextStyle(
                        fontFamily: 'Poppins', fontSize: 16,
                        fontWeight: FontWeight.w600, color: _textPrimary,
                      ),
                    ),
                    Text(subtitle,
                      style: const TextStyle(
                        fontFamily: 'Poppins', fontSize: 11, color: _textSecondary,
                      ),
                    ),
                  ],
                ),
              ),
            ],
          ),
          const SizedBox(height: 24),
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
}
