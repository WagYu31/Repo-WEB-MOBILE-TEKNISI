import 'package:flutter/material.dart';
import 'package:intl/intl.dart';
import 'package:flutter_animate/flutter_animate.dart';
import '../../core/app_theme.dart';
import '../../service/model/SalesModel.dart';
import 'TaskDetailPage.dart';

class TaskListPage extends StatelessWidget {
  final List<VisitTask> tasks;
  const TaskListPage({super.key, required this.tasks});

  @override
  Widget build(BuildContext context) {
    return ListView.separated(
      physics: const AlwaysScrollableScrollPhysics(),
      padding: const EdgeInsets.fromLTRB(20, 4, 20, 100),
      itemCount: tasks.length,
      separatorBuilder: (_, __) => const SizedBox(height: 10),
      itemBuilder: (_, i) => _TaskCard(task: tasks[i], index: i),
    );
  }
}

class _TaskCard extends StatelessWidget {
  final VisitTask task;
  final int index;
  const _TaskCard({required this.task, required this.index});

  @override
  Widget build(BuildContext context) {
    // Status
    final Color statusColor;
    final String statusLabel;
    final IconData statusIcon;
    if (task.selesai) {
      statusColor = AppColors.success;
      statusLabel = 'Selesai';
      statusIcon  = Icons.check_circle_rounded;
    } else if (task.sedangBerjalan) {
      statusColor = AppColors.warning;
      statusLabel = 'Berjalan';
      statusIcon  = Icons.directions_walk_rounded;
    } else {
      statusColor = AppColors.pending;
      statusLabel = 'Dijadwalkan';
      statusIcon  = Icons.schedule_rounded;
    }

    // Format jadwal
    String jadwalStr = '';
    try {
      final dt = DateTime.parse(task.jadwal);
      jadwalStr = DateFormat('dd MMM yyyy • HH:mm', 'id').format(dt);
    } catch (_) {
      jadwalStr = task.jadwal;
    }

    // Avatar initials
    final parts   = task.namaCustomer.split(' ');
    final initials = parts.length >= 2
        ? '${parts[0][0]}${parts[1][0]}'.toUpperCase()
        : task.namaCustomer.isNotEmpty
            ? task.namaCustomer[0].toUpperCase()
            : '?';

    return GestureDetector(
      onTap: () => Navigator.push(
        context,
        PageRouteBuilder(
          pageBuilder: (_, a, __) => TaskDetailPage(task: task),
          transitionsBuilder: (_, anim, __, child) => SlideTransition(
            position: Tween(begin: const Offset(1, 0), end: Offset.zero)
                .animate(CurvedAnimation(parent: anim, curve: Curves.easeOut)),
            child: child,
          ),
          transitionDuration: const Duration(milliseconds: 280),
        ),
      ),
      child: Container(
        decoration: BoxDecoration(
          color: AppColors.card,
          borderRadius: BorderRadius.circular(16),
          border: Border(
            left: BorderSide(color: statusColor, width: 4),
            top: BorderSide(color: AppColors.border),
            right: BorderSide(color: AppColors.border),
            bottom: BorderSide(color: AppColors.border),
          ),
          boxShadow: [
            BoxShadow(
              color: Colors.black.withOpacity(0.2),
              blurRadius: 10, offset: const Offset(0, 3),
            ),
          ],
        ),
        child: Padding(
          padding: const EdgeInsets.all(14),
          child: Row(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              // Avatar
              Container(
                width: 44, height: 44,
                decoration: BoxDecoration(
                  color: statusColor.withOpacity(0.12),
                  borderRadius: BorderRadius.circular(12),
                  border: Border.all(color: statusColor.withOpacity(0.25)),
                ),
                child: Center(
                  child: Text(initials, style: S.h3(statusColor)),
                ),
              ),
              const SizedBox(width: 12),

              // Content
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    // Customer name + status badge
                    Row(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Expanded(
                          child: Text(
                            task.namaCustomer,
                            style: S.bodyLg(),
                            maxLines: 1,
                            overflow: TextOverflow.ellipsis,
                          ),
                        ),
                        const SizedBox(width: 8),
                        Container(
                          padding: const EdgeInsets.symmetric(
                              horizontal: 8, vertical: 3),
                          decoration: BoxDecoration(
                            color: statusColor.withOpacity(0.12),
                            borderRadius: BorderRadius.circular(20),
                            border: Border.all(
                                color: statusColor.withOpacity(0.3)),
                          ),
                          child: Row(
                            mainAxisSize: MainAxisSize.min,
                            children: [
                              Icon(statusIcon, size: 11, color: statusColor),
                              const SizedBox(width: 3),
                              Text(statusLabel, style: S.label(statusColor)),
                            ],
                          ),
                        ),
                      ],
                    ),
                    const SizedBox(height: 6),

                    // Address
                    Row(
                      children: [
                        const Icon(Icons.location_on_outlined,
                            size: 13, color: AppColors.textMuted),
                        const SizedBox(width: 4),
                        Expanded(
                          child: Text(
                            '${task.alamatCustomer}, ${task.kotaCustomer}',
                            style: S.caption(),
                            maxLines: 1,
                            overflow: TextOverflow.ellipsis,
                          ),
                        ),
                      ],
                    ),
                    const SizedBox(height: 4),

                    // Jadwal
                    Row(
                      children: [
                        const Icon(Icons.access_time_rounded,
                            size: 13, color: AppColors.textMuted),
                        const SizedBox(width: 4),
                        Text(jadwalStr, style: S.caption()),
                      ],
                    ),

                    // Clock in/out chips
                    if (task.sudahClockIn) ...[
                      const SizedBox(height: 8),
                      Wrap(
                        spacing: 8,
                        children: [
                          _TimeChip(
                            icon: Icons.login_rounded,
                            label: 'Masuk',
                            time: _fmtTime(task.ciAt),
                            color: AppColors.primary,
                          ),
                          if (task.sudahClockOut)
                            _TimeChip(
                              icon: Icons.logout_rounded,
                              label: 'Keluar',
                              time: _fmtTime(task.coAt),
                              color: AppColors.success,
                            ),
                        ],
                      ),
                    ],

                    // Keterangan
                    if (task.keterangan.isNotEmpty) ...[
                      const SizedBox(height: 6),
                      Text(
                        task.keterangan,
                        style: S.caption(AppColors.textMuted),
                        maxLines: 1,
                        overflow: TextOverflow.ellipsis,
                      ),
                    ],
                  ],
                ),
              ),

              // Chevron
              const Padding(
                padding: EdgeInsets.only(top: 10),
                child: Icon(Icons.chevron_right_rounded,
                    color: AppColors.textMuted, size: 18),
              ),
            ],
          ),
        ),
      ),
    )
        .animate(delay: Duration(milliseconds: index * 60))
        .fadeIn(duration: 400.ms)
        .slideX(begin: 0.05, end: 0, curve: Curves.easeOut);
  }

  String _fmtTime(String? dt) {
    if (dt == null || dt.isEmpty) return '—';
    try { return DateFormat('HH:mm').format(DateTime.parse(dt)); }
    catch (_) { return dt; }
  }
}

// ── Time Chip ────────────────────────────────────────────
class _TimeChip extends StatelessWidget {
  final IconData icon;
  final String label;
  final String time;
  final Color color;
  const _TimeChip({required this.icon, required this.label,
      required this.time, required this.color});

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
      decoration: BoxDecoration(
        color: color.withOpacity(0.1),
        borderRadius: BorderRadius.circular(8),
        border: Border.all(color: color.withOpacity(0.2)),
      ),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          Icon(icon, size: 11, color: color),
          const SizedBox(width: 4),
          Text('$label $time',
              style: S.micro(color)),
        ],
      ),
    );
  }
}
