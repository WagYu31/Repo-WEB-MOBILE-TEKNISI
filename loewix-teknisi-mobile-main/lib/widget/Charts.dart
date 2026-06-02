import 'package:fl_chart/fl_chart.dart';
import 'package:flutter/material.dart';

import '../service/model/pencapaian/PencapaianChartGet.dart';

class AppColors {
  static const contentColorYellow = Color(0xFFFFD700);
  static const contentColorRed = Color(0xFFFF0000);
  static const contentColorOrange = Color(0xFFFFA500);
}

class BarChartSample2 extends StatefulWidget {
  final List<DataPencapaian> a;

  const BarChartSample2({super.key, required this.a});

  @override
  State<StatefulWidget> createState() => BarChartSample2State();
}

class BarChartSample2State extends State<BarChartSample2> {
  static const double _barWidth = 7;
  static const Color _barColor = AppColors.contentColorRed;

  late List<BarChartGroupData> _barGroups;

  @override
  void initState() {
    super.initState();
    _barGroups = _buildBarGroups();
  }

  List<BarChartGroupData> _buildBarGroups() {
    // Month indices: Aug=8, Sep=9, Oct=10, Nov=11, Dec=12
    const months = [8, 9, 10, 11, 12];

    return List.generate(months.length, (index) {
      final monthData = widget.a.where((e) => e.tanggal.month == months[index]).toList();

      if (monthData.isEmpty) {
        return _makeGroupData(index, 0.0, 0.0);
      }

      final pendapatan = _formatNumber(double.parse(monthData[0].pendapatan));
      final target = double.parse(monthData[0].target);
      return _makeGroupData(index, target, pendapatan);
    });
  }

  double _formatNumber(double number) {
    return double.parse((number / 1000000).toStringAsFixed(2));
  }

  BarChartGroupData _makeGroupData(int x, double target, double pendapatan) {
    return BarChartGroupData(
      barsSpace: 4,
      x: x,
      barRods: [
        BarChartRodData(
          toY: pendapatan,
          color: _barColor,
          width: _barWidth,
        ),
      ],
    );
  }

  @override
  Widget build(BuildContext context) {
    return AspectRatio(
      aspectRatio: 1,
      child: SafeArea(
        child: Padding(
          padding: const EdgeInsets.all(16),
          child: Container(
            decoration: const BoxDecoration(
              borderRadius: BorderRadius.all(Radius.circular(20)),
              color: Color(0XFF203858),
            ),
            padding: const EdgeInsets.all(15),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.stretch,
              children: [
                Row(
                  children: [
                    _buildTransactionsIcon(),
                    const SizedBox(width: 10),
                    const Text(
                      'Grafik Pencapaian',
                      style: TextStyle(color: Colors.white, fontSize: 22),
                    ),
                  ],
                ),
                const SizedBox(height: 38),
                Expanded(
                  child: BarChart(
                    BarChartData(
                      maxY: 7,
                      barTouchData: BarTouchData(
                        touchTooltipData: BarTouchTooltipData(
                          getTooltipItem: (a, b, c, d) => null,
                        ),
                        touchCallback: _handleBarTouch,
                      ),
                      titlesData: FlTitlesData(
                        show: true,
                        rightTitles: const AxisTitles(
                          sideTitles: SideTitles(showTitles: false),
                        ),
                        topTitles: const AxisTitles(
                          sideTitles: SideTitles(showTitles: false),
                        ),
                        bottomTitles: AxisTitles(
                          sideTitles: SideTitles(
                            showTitles: true,
                            getTitlesWidget: _bottomTitles,
                            reservedSize: 42,
                          ),
                        ),
                        leftTitles: AxisTitles(
                          sideTitles: SideTitles(
                            showTitles: true,
                            reservedSize: 28,
                            interval: 1,
                            getTitlesWidget: _leftTitles,
                          ),
                        ),
                      ),
                      borderData: FlBorderData(show: false),
                      barGroups: _barGroups,
                      gridData: const FlGridData(show: false),
                    ),
                  ),
                ),
                const SizedBox(height: 12),
              ],
            ),
          ),
        ),
      ),
    );
  }

  void _handleBarTouch(FlTouchEvent event, BarTouchResponse? response) {
    // Touch handling - can be extended for interactive features
  }

  Widget _leftTitles(double value, TitleMeta meta) {
    const style = TextStyle(
      color: Color(0xff7589a2),
      fontWeight: FontWeight.bold,
      fontSize: 14,
    );

    String? text;
    switch (value.toInt()) {
      case 1:
        text = '1jt';
        break;
      case 5:
        text = '5jt';
        break;
      case 7:
        text = '7jt';
        break;
    }

    if (text == null) return const SizedBox.shrink();

    return SideTitleWidget(
      axisSide: meta.axisSide,
      space: 0,
      child: Text(text, style: style),
    );
  }

  Widget _bottomTitles(double value, TitleMeta meta) {
    const titles = ['Ags', 'Sep', 'Okt', 'Nov', 'Des'];
    const style = TextStyle(
      color: Color(0xff7589a2),
      fontWeight: FontWeight.bold,
      fontSize: 14,
    );

    return SideTitleWidget(
      axisSide: meta.axisSide,
      space: 16,
      child: Text(titles[value.toInt()], style: style),
    );
  }

  Widget _buildTransactionsIcon() {
    const width = 4.5;
    const space = 3.5;

    return Row(
      mainAxisSize: MainAxisSize.min,
      children: [
        Container(width: width, height: 10, color: Colors.white.withValues(alpha: 0.4)),
        const SizedBox(width: space),
        Container(width: width, height: 28, color: Colors.white.withValues(alpha: 0.8)),
        const SizedBox(width: space),
        Container(width: width, height: 42, color: Colors.white),
        const SizedBox(width: space),
        Container(width: width, height: 28, color: Colors.white.withValues(alpha: 0.8)),
        const SizedBox(width: space),
        Container(width: width, height: 10, color: Colors.white.withValues(alpha: 0.4)),
      ],
    );
  }
}
