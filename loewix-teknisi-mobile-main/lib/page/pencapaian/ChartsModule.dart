import 'package:fl_chart/fl_chart.dart';
import 'package:flutter/material.dart';

class LineChartExample extends StatelessWidget {
  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(16),
      height: 300,
      width: double.infinity,
      child: LineChart(
        LineChartData(
          titlesData: FlTitlesData(
            leftTitles: AxisTitles(
              sideTitles: SideTitles(
                showTitles: true,
                getTitlesWidget: (value, meta) {
                  // Format angka pada axis kiri
                  final formattedValue = value.toInt().toString();
                  return Text(
                    formattedValue,
                    style: TextStyle(fontFamily: 'Poppins',
                      fontSize: 14,
                      color: Colors.black,
                    ),
                  );
                },
                reservedSize: 42,
              ),
            ),
            bottomTitles: AxisTitles(
              sideTitles: SideTitles(
                showTitles: true,
                interval: 1,
                getTitlesWidget: (value, meta) {
                  // Nama bulan September hingga Desember
                  switch (value.toInt()) {
                    case 0:
                      return Text(
                        'Sep',
                        style: TextStyle(fontFamily: 'Poppins',
                          fontSize: 14,
                          color: Colors.black,
                        ),
                      );
                    case 1:
                      return Text(
                        'Oct',
                        style: TextStyle(fontFamily: 'Poppins',
                          fontSize: 14,
                          color: Colors.black,
                        ),
                      );
                    case 2:
                      return Text(
                        'Nov',
                        style: TextStyle(fontFamily: 'Poppins',
                          fontSize: 14,
                          color: Colors.black,
                        ),
                      );
                    case 3:
                      return Text(
                        'Dec',
                        style: TextStyle(fontFamily: 'Poppins',
                          fontSize: 14,
                          color: Colors.black,
                        ),
                      );
                    default:
                      return Text('');
                  }
                },
              ),
            ),
            topTitles: AxisTitles( // Menghilangkan tulisan di atas
              sideTitles: SideTitles(showTitles: false),
            ),
            rightTitles: AxisTitles( // Menghilangkan tulisan di kanan
              sideTitles: SideTitles(showTitles: false),
            ),
          ),
          gridData: FlGridData(show: false),
          borderData: FlBorderData(
            show: true,
            border: const Border(
              bottom: BorderSide(color: Colors.black, width: 1),
              left: BorderSide(color: Colors.black, width: 1),
              right: BorderSide(color: Colors.transparent), // Menghilangkan garis kanan
              top: BorderSide(color: Colors.transparent), // Menghilangkan garis atas
            ),
          ),
          lineBarsData: [
            LineChartBarData(
              spots: [
                // Data untuk September hingga Desember
                FlSpot(0, 300000), // September
                FlSpot(1, 1500000), // Oktober
                FlSpot(2, 2500000), // November
                FlSpot(3, 5000000), // Desember
              ],
              isCurved: true,
              //colors: [Colors.blueAccent],
              dotData: FlDotData(show: false),
              belowBarData: BarAreaData(show: false),
            ),
          ],
          minY: 0, // Nilai minimum untuk axis Y
          maxY: 5000000, // Nilai maksimum untuk axis Y
        ),
      ),
    );
  }
}
