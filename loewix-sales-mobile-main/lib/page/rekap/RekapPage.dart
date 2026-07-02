import 'dart:collection';
import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:intl/intl.dart';
import 'package:flutter_animate/flutter_animate.dart';
import '../../core/app_theme.dart';
import '../../service/provider/SalesProvider.dart';
import '../../service/api/ApiSales.dart';
import '../../service/model/SalesModel.dart';

class RekapPage extends StatefulWidget {
  const RekapPage({super.key});
  @override
  State<RekapPage> createState() => _RekapPageState();
}

class _RekapPageState extends State<RekapPage> {
  List<VisitTask>? _all;
  bool _loading = true;
  String? _err;

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    final profile = context.read<SalesProvider>().profile;
    if (profile == null) return;
    setState(() => _loading = true);
    try {
      final tasks = await ApiSales().getTasks(profile.id, filter: 'all');
      if (mounted) setState(() { _all = tasks; _loading = false; });
    } catch (e) {
      if (mounted) setState(() { _err = e.toString().replaceAll('Exception: ', ''); _loading = false; });
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: AppColors.bg,
      body: SafeArea(
        child: RefreshIndicator(
          onRefresh: _load,
          color: AppColors.primary,
          backgroundColor: AppColors.card,
          child: CustomScrollView(
            slivers: [
              // ── Header ──────────────────────────────
              SliverToBoxAdapter(
                child: Padding(
                  padding: const EdgeInsets.fromLTRB(20, 24, 20, 0),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text('Rekap Kunjungan', style: S.h1())
                          .animate().fadeIn(duration: 400.ms),
                      const SizedBox(height: 4),
                      Text('Riwayat & statistik kunjungan Anda',
                          style: S.caption())
                          .animate(delay: 100.ms).fadeIn(duration: 400.ms),
                      const SizedBox(height: 20),
                    ],
                  ),
                ),
              ),

              // ── Content ──────────────────────────────
              if (_loading)
                const SliverFillRemaining(
                  child: Center(
                    child: CircularProgressIndicator(color: AppColors.primary),
                  ),
                )
              else if (_err != null)
                SliverFillRemaining(child: _ErrorState(err: _err!, onRetry: _load))
              else if (_all == null || _all!.isEmpty)
                const SliverFillRemaining(child: _EmptyState())
              else
                _RekapContent(tasks: _all!),
            ],
          ),
        ),
      ),
    );
  }
}

// ── Rekap Content ────────────────────────────────────────
class _RekapContent extends StatelessWidget {
  final List<VisitTask> tasks;
  const _RekapContent({required this.tasks});

  @override
  Widget build(BuildContext context) {
    final total    = tasks.length;
    final selesai  = tasks.where((t) => t.selesai).length;
    final berjalan = tasks.where((t) => t.sedangBerjalan).length;
    final belum    = tasks.where((t) => !t.sudahClockIn).length;
    final rate     = total == 0 ? 0.0 : selesai / total;

    // Group by date
    final grouped = LinkedHashMap<String, List<VisitTask>>();
    for (final t in tasks) {
      String key;
      try {
        final dt = DateTime.parse(t.jadwal);
        key = DateFormat('EEEE, dd MMMM yyyy', 'id').format(dt);
      } catch (_) {
        key = t.jadwal;
      }
      grouped.putIfAbsent(key, () => []).add(t);
    }

    return SliverPadding(
      padding: const EdgeInsets.fromLTRB(20, 0, 20, 40),
      sliver: SliverList(
        delegate: SliverChildListDelegate([
          // ── Completion Rate Card ─────────────────────
          Container(
            padding: const EdgeInsets.all(20),
            decoration: AppTheme.cardDeco(accentColor: AppColors.primary),
            child: Row(
              children: [
                // Circle progress
                SizedBox(
                  width: 80, height: 80,
                  child: Stack(
                    alignment: Alignment.center,
                    children: [
                      SizedBox(
                        width: 80, height: 80,
                        child: CircularProgressIndicator(
                          value: rate,
                          strokeWidth: 8,
                          backgroundColor: AppColors.border,
                          color: rate >= 0.8
                              ? AppColors.success
                              : rate >= 0.5
                                  ? AppColors.warning
                                  : AppColors.primary,
                        ),
                      ),
                      Text('${(rate * 100).toStringAsFixed(0)}%',
                          style: S.h3(AppColors.textPrimary)),
                    ],
                  ),
                ),
                const SizedBox(width: 20),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text('Completion Rate', style: S.h3()),
                      const SizedBox(height: 4),
                      Text('$selesai dari $total kunjungan selesai',
                          style: S.caption()),
                      const SizedBox(height: 12),
                      Row(
                        children: [
                          _dot(AppColors.success),
                          const SizedBox(width: 4),
                          Text('Selesai: $selesai', style: S.micro()),
                          const SizedBox(width: 12),
                          _dot(AppColors.warning),
                          const SizedBox(width: 4),
                          Text('Aktif: $berjalan', style: S.micro()),
                        ],
                      ),
                    ],
                  ),
                ),
              ],
            ),
          ).animate(delay: 200.ms).fadeIn(duration: 500.ms).slideY(begin: 0.1, end: 0),

          const SizedBox(height: 14),

          // ── Stat Cards Row ───────────────────────────
          Row(
            children: [
              _statCard('Total', total, Icons.list_alt_rounded, AppColors.primary),
              const SizedBox(width: 10),
              _statCard('Selesai', selesai, Icons.check_circle_rounded, AppColors.success),
              const SizedBox(width: 10),
              _statCard('Berjalan', berjalan, Icons.directions_walk, AppColors.warning),
              const SizedBox(width: 10),
              _statCard('Belum', belum, Icons.schedule_rounded, AppColors.pending),
            ],
          ).animate(delay: 300.ms).fadeIn(duration: 500.ms),

          const SizedBox(height: 24),

          Text('Riwayat Kunjungan', style: S.h3())
              .animate(delay: 400.ms).fadeIn(duration: 400.ms),
          const SizedBox(height: 12),

          // ── Grouped list ─────────────────────────────
          ...grouped.entries.toList().asMap().entries.map((entry) {
            final idx       = entry.key;
            final dateLabel = entry.value.key;
            final list      = entry.value.value;
            return Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                // Date header
                Padding(
                  padding: const EdgeInsets.only(bottom: 8),
                  child: Row(
                    children: [
                      Container(
                        width: 4, height: 16,
                        decoration: BoxDecoration(
                          color: AppColors.primary,
                          borderRadius: BorderRadius.circular(2),
                        ),
                      ),
                      const SizedBox(width: 8),
                      Text(dateLabel, style: S.micro(AppColors.textSecondary)),
                      const SizedBox(width: 8),
                      Expanded(child: Container(height: 1, color: AppColors.divider)),
                      const SizedBox(width: 8),
                      Container(
                        padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 2),
                        decoration: BoxDecoration(
                          color: AppColors.primary.withOpacity(0.12),
                          borderRadius: BorderRadius.circular(10),
                        ),
                        child: Text('${list.length}', style: S.label(AppColors.primaryLight)),
                      ),
                    ],
                  ),
                ),
                ...list.map((t) => Padding(
                  padding: const EdgeInsets.only(bottom: 8),
                  child: _RekapTaskItem(task: t),
                )),
                const SizedBox(height: 12),
              ],
            ).animate(delay: Duration(milliseconds: 450 + idx * 80))
                .fadeIn(duration: 400.ms)
                .slideY(begin: 0.08, end: 0);
          }),
        ]),
      ),
    );
  }

  Widget _dot(Color color) => Container(
    width: 8, height: 8,
    decoration: BoxDecoration(color: color, shape: BoxShape.circle),
  );

  Widget _statCard(String label, int val, IconData icon, Color color) {
    return Expanded(
      child: Container(
        padding: const EdgeInsets.symmetric(vertical: 12),
        decoration: BoxDecoration(
          color: color.withOpacity(0.08),
          borderRadius: BorderRadius.circular(12),
          border: Border.all(color: color.withOpacity(0.25)),
        ),
        child: Column(
          children: [
            Icon(icon, color: color, size: 20),
            const SizedBox(height: 4),
            Text('$val',
                style: S.h2(color),
                textAlign: TextAlign.center),
            Text(label, style: S.label(color),
                textAlign: TextAlign.center),
          ],
        ),
      ),
    );
  }
}

// ── Rekap Task Item ──────────────────────────────────────
class _RekapTaskItem extends StatelessWidget {
  final VisitTask task;
  const _RekapTaskItem({required this.task});

  @override
  Widget build(BuildContext context) {
    Color statusColor;
    String statusLabel;
    if (task.selesai) {
      statusColor = AppColors.success;
      statusLabel = 'Selesai';
    } else if (task.sedangBerjalan) {
      statusColor = AppColors.warning;
      statusLabel = 'Berjalan';
    } else {
      statusColor = AppColors.pending;
      statusLabel = 'Dijadwalkan';
    }

    String ciTime = '—', coTime = '—';
    try {
      if (task.ciAt != null) ciTime = DateFormat('HH:mm').format(DateTime.parse(task.ciAt!));
      if (task.coAt != null) coTime = DateFormat('HH:mm').format(DateTime.parse(task.coAt!));
    } catch (_) {}

    return Container(
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: AppColors.card,
        borderRadius: BorderRadius.circular(12),
        border: Border(left: BorderSide(color: statusColor, width: 3)),
        boxShadow: [
          BoxShadow(color: Colors.black.withOpacity(0.18),
              blurRadius: 8, offset: const Offset(0, 2)),
        ],
      ),
      child: Row(
        children: [
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(task.namaCustomer,
                    style: S.bodyLg(), maxLines: 1,
                    overflow: TextOverflow.ellipsis),
                const SizedBox(height: 2),
                Text('${task.alamatCustomer}, ${task.kotaCustomer}',
                    style: S.caption(), maxLines: 1,
                    overflow: TextOverflow.ellipsis),
              ],
            ),
          ),
          const SizedBox(width: 12),
          Column(
            crossAxisAlignment: CrossAxisAlignment.end,
            children: [
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                decoration: BoxDecoration(
                  color: statusColor.withOpacity(0.12),
                  borderRadius: BorderRadius.circular(8),
                ),
                child: Text(statusLabel, style: S.label(statusColor)),
              ),
              if (task.sudahClockIn) ...[
                const SizedBox(height: 4),
                Text('$ciTime → $coTime',
                    style: S.micro(AppColors.textSecondary)),
              ],
            ],
          ),
        ],
      ),
    );
  }
}

class _EmptyState extends StatelessWidget {
  const _EmptyState();
  @override
  Widget build(BuildContext context) => Center(
    child: Column(
      mainAxisAlignment: MainAxisAlignment.center,
      children: [
        const Icon(Icons.bar_chart_rounded, size: 60, color: AppColors.border),
        const SizedBox(height: 16),
        Text('Belum ada riwayat', style: S.h3(AppColors.textSecondary)),
        const SizedBox(height: 6),
        Text('Tarik ke bawah untuk refresh', style: S.caption()),
      ],
    ),
  );
}

class _ErrorState extends StatelessWidget {
  final String err;
  final VoidCallback onRetry;
  const _ErrorState({required this.err, required this.onRetry});
  @override
  Widget build(BuildContext context) => Center(
    child: Padding(
      padding: const EdgeInsets.all(32),
      child: Column(
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          const Icon(Icons.error_outline, size: 48, color: AppColors.error),
          const SizedBox(height: 16),
          Text('Gagal memuat data', style: S.h3(AppColors.error)),
          const SizedBox(height: 8),
          Text(err, style: S.caption(), textAlign: TextAlign.center),
          const SizedBox(height: 20),
          ElevatedButton.icon(
            onPressed: onRetry,
            icon: const Icon(Icons.refresh, size: 18),
            label: const Text('Coba Lagi'),
            style: ElevatedButton.styleFrom(
              backgroundColor: AppColors.primary,
              shape: RoundedRectangleBorder(
                  borderRadius: BorderRadius.circular(12)),
            ),
          ),
        ],
      ),
    ),
  );
}
