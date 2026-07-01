import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:intl/intl.dart';
import '../../service/model/SalesModel.dart';
import 'TaskDetailPage.dart';

class TaskListPage extends StatelessWidget {
  final List<VisitTask> tasks;
  const TaskListPage({super.key, required this.tasks});

  @override
  Widget build(BuildContext context) {
    return ListView.separated(
      padding: const EdgeInsets.fromLTRB(16, 4, 16, 80),
      itemCount: tasks.length,
      separatorBuilder: (_, __) => const SizedBox(height: 10),
      itemBuilder: (_, i) => _TaskCard(task: tasks[i]),
    );
  }
}

class _TaskCard extends StatelessWidget {
  final VisitTask task;
  const _TaskCard({required this.task});

  @override
  Widget build(BuildContext context) {
    Color statusColor;
    String statusLabel;
    IconData statusIcon;

    if (task.selesai) {
      statusColor = const Color(0xFF10B981);
      statusLabel = 'Selesai';
      statusIcon = Icons.check_circle;
    } else if (task.sedangBerjalan) {
      statusColor = const Color(0xFFF59E0B);
      statusLabel = 'Berjalan';
      statusIcon = Icons.directions_walk;
    } else {
      statusColor = const Color(0xFF6B7280);
      statusLabel = 'Dijadwalkan';
      statusIcon = Icons.schedule;
    }

    String jadwalFormatted = '';
    try {
      final dt = DateTime.parse(task.jadwal);
      jadwalFormatted = DateFormat('dd MMM yyyy • HH:mm', 'id').format(dt);
    } catch (_) {
      jadwalFormatted = task.jadwal;
    }

    return GestureDetector(
      onTap: () => Navigator.push(
        context,
        MaterialPageRoute(builder: (_) => TaskDetailPage(task: task)),
      ),
      child: Container(
        padding: const EdgeInsets.all(16),
        decoration: BoxDecoration(
          color: const Color(0xFF1E293B),
          borderRadius: BorderRadius.circular(16),
          border: Border.all(color: statusColor.withOpacity(0.3)),
          boxShadow: [
            BoxShadow(
              color: Colors.black.withOpacity(0.2),
              blurRadius: 8,
              offset: const Offset(0, 3),
            ),
          ],
        ),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            // Top row
            Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                Expanded(
                  child: Text(
                    task.namaCustomer,
                    style: GoogleFonts.poppins(
                        fontSize: 15,
                        fontWeight: FontWeight.w600,
                        color: Colors.white),
                    overflow: TextOverflow.ellipsis,
                  ),
                ),
                Container(
                  padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                  decoration: BoxDecoration(
                    color: statusColor.withOpacity(0.15),
                    borderRadius: BorderRadius.circular(20),
                    border: Border.all(color: statusColor.withOpacity(0.4)),
                  ),
                  child: Row(
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      Icon(statusIcon, size: 12, color: statusColor),
                      const SizedBox(width: 4),
                      Text(statusLabel,
                          style: GoogleFonts.poppins(
                              fontSize: 11,
                              fontWeight: FontWeight.w600,
                              color: statusColor)),
                    ],
                  ),
                ),
              ],
            ),
            const SizedBox(height: 8),
            // Address
            Row(
              children: [
                const Icon(Icons.location_on_outlined,
                    size: 14, color: Colors.white38),
                const SizedBox(width: 4),
                Expanded(
                  child: Text(
                    '${task.alamatCustomer}, ${task.kotaCustomer}',
                    style: GoogleFonts.poppins(fontSize: 12, color: Colors.white54),
                    overflow: TextOverflow.ellipsis,
                  ),
                ),
              ],
            ),
            const SizedBox(height: 4),
            // Schedule
            Row(
              children: [
                const Icon(Icons.access_time, size: 14, color: Colors.white38),
                const SizedBox(width: 4),
                Text(jadwalFormatted,
                    style: GoogleFonts.poppins(fontSize: 12, color: Colors.white54)),
              ],
            ),
            // Clock time if started
            if (task.sudahClockIn) ...[
              const SizedBox(height: 8),
              Row(
                children: [
                  _timeChip(Icons.login, 'Masuk',
                      _formatTime(task.ciAt), const Color(0xFF3B82F6)),
                  if (task.sudahClockOut) ...[
                    const SizedBox(width: 8),
                    _timeChip(Icons.logout, 'Keluar',
                        _formatTime(task.coAt), const Color(0xFF10B981)),
                  ],
                ],
              ),
            ],
            // Keterangan
            if (task.keterangan.isNotEmpty) ...[
              const SizedBox(height: 8),
              Text(task.keterangan,
                  style: GoogleFonts.poppins(fontSize: 11, color: Colors.white38),
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis),
            ],
          ],
        ),
      ),
    );
  }

  Widget _timeChip(IconData icon, String label, String time, Color color) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
      decoration: BoxDecoration(
        color: color.withOpacity(0.1),
        borderRadius: BorderRadius.circular(8),
      ),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          Icon(icon, size: 11, color: color),
          const SizedBox(width: 3),
          Text('$label $time',
              style: GoogleFonts.poppins(
                  fontSize: 10, color: color, fontWeight: FontWeight.w500)),
        ],
      ),
    );
  }

  String _formatTime(String? dt) {
    if (dt == null || dt.isEmpty) return '-';
    try {
      return DateFormat('HH:mm').format(DateTime.parse(dt));
    } catch (_) {
      return dt;
    }
  }
}
