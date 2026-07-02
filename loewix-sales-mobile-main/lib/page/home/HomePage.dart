import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:intl/intl.dart';
import 'package:flutter_animate/flutter_animate.dart';
import '../../core/app_theme.dart';
import '../../service/provider/SalesProvider.dart';
import '../task/TaskListPage.dart';
import '../rekap/RekapPage.dart';
import '../profile/ProfilePage.dart';

class HomePage extends StatefulWidget {
  static const routeName = '/home';
  const HomePage({super.key});
  @override
  State<HomePage> createState() => _HomePageState();
}

class _HomePageState extends State<HomePage> {
  int _navIdx  = 0; // 0=Kunjungan, 1=Rekap, 2=Profil
  int _taskTab = 0; // 0=Hari Ini, 1=Semua

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      context.read<SalesProvider>().fetchTasks(filter: 'today');
    });
  }

  String _greeting() {
    final h = DateTime.now().hour;
    if (h >= 5  && h < 11) return 'Selamat Pagi';
    if (h >= 11 && h < 15) return 'Selamat Siang';
    if (h >= 15 && h < 19) return 'Selamat Sore';
    return 'Selamat Malam';
  }

  @override
  Widget build(BuildContext context) {
    final prov = context.watch<SalesProvider>();

    return Scaffold(
      backgroundColor: AppColors.bg,
      body: IndexedStack(
        index: _navIdx,
        children: [
          _KunjunganTab(
            taskTab: _taskTab,
            greeting: _greeting(),
            onTaskTabChange: (i) {
              setState(() => _taskTab = i);
              prov.fetchTasks(filter: i == 0 ? 'today' : 'all');
            },
          ),
          const RekapPage(),
          const ProfilePage(),
        ],
      ),
      bottomNavigationBar: _BottomNavBar(
        currentIndex: _navIdx,
        onTap: (i) => setState(() => _navIdx = i),
      ),
    );
  }
}

// ── Bottom Navigation Bar ────────────────────────────────
class _BottomNavBar extends StatelessWidget {
  final int currentIndex;
  final ValueChanged<int> onTap;
  const _BottomNavBar({required this.currentIndex, required this.onTap});

  @override
  Widget build(BuildContext context) {
    return Container(
      decoration: BoxDecoration(
        color: AppColors.surface,
        border: const Border(top: BorderSide(color: AppColors.border, width: 1)),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withOpacity(0.4),
            blurRadius: 20, offset: const Offset(0, -4),
          ),
        ],
      ),
      child: SafeArea(
        top: false,
        child: Padding(
          padding: const EdgeInsets.symmetric(vertical: 4),
          child: Row(
            children: [
              _navItem(0, Icons.home_rounded, Icons.home_outlined, 'Kunjungan'),
              _navItem(1, Icons.bar_chart_rounded, Icons.bar_chart_outlined, 'Rekap'),
              _navItem(2, Icons.person_rounded, Icons.person_outline_rounded, 'Profil'),
            ],
          ),
        ),
      ),
    );
  }

  Widget _navItem(int idx, IconData activeIcon, IconData inactiveIcon, String label) {
    final active = currentIndex == idx;
    return Expanded(
      child: GestureDetector(
        onTap: () => onTap(idx),
        behavior: HitTestBehavior.opaque,
        child: AnimatedContainer(
          duration: const Duration(milliseconds: 200),
          padding: const EdgeInsets.symmetric(vertical: 8),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              AnimatedContainer(
                duration: const Duration(milliseconds: 200),
                padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 6),
                decoration: BoxDecoration(
                  color: active ? AppColors.primary.withOpacity(0.15) : Colors.transparent,
                  borderRadius: BorderRadius.circular(20),
                ),
                child: Icon(
                  active ? activeIcon : inactiveIcon,
                  color: active ? AppColors.primaryLight : AppColors.textMuted,
                  size: 24,
                ),
              ),
              const SizedBox(height: 2),
              AnimatedDefaultTextStyle(
                duration: const Duration(milliseconds: 200),
                style: active
                    ? S.label(AppColors.primaryLight)
                    : S.label(AppColors.textMuted),
                child: Text(label),
              ),
            ],
          ),
        ),
      ),
    );
  }
}

// ── Kunjungan Tab ────────────────────────────────────────
class _KunjunganTab extends StatelessWidget {
  final int taskTab;
  final String greeting;
  final ValueChanged<int> onTaskTabChange;
  const _KunjunganTab({
    required this.taskTab,
    required this.greeting,
    required this.onTaskTabChange,
  });

  @override
  Widget build(BuildContext context) {
    final prov    = context.watch<SalesProvider>();
    final profile = prov.profile;
    final tasks   = prov.tasks;

    final total    = tasks.length;
    final berjalan = tasks.where((t) => t.sedangBerjalan).length;
    final selesai  = tasks.where((t) => t.selesai).length;
    final belum    = tasks.where((t) => !t.sudahClockIn).length;

    return Column(
      children: [
        // ── Rounded Premium Header ──────────────────────────
        Container(
          decoration: BoxDecoration(
            gradient: AppColors.gradientHeader,
            borderRadius: const BorderRadius.vertical(bottom: Radius.circular(24)),
            boxShadow: [
              BoxShadow(
                color: Colors.black.withOpacity(0.04),
                blurRadius: 12,
                offset: const Offset(0, 4),
              ),
            ],
          ),
          child: SafeArea(
            bottom: false,
            child: Padding(
              padding: const EdgeInsets.fromLTRB(20, 16, 20, 20),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Row(
                    crossAxisAlignment: CrossAxisAlignment.center,
                    children: [
                      // Circular Profile Avatar
                      Container(
                        width: 46, height: 46,
                        decoration: BoxDecoration(
                          shape: BoxShape.circle,
                          border: Border.all(color: AppColors.primary, width: 2),
                          boxShadow: [
                            BoxShadow(
                              color: AppColors.primary.withOpacity(0.15),
                              blurRadius: 10,
                              offset: const Offset(0, 4),
                            ),
                          ],
                        ),
                        child: ClipOval(
                          child: Image.network(
                            'https://ui-avatars.com/api/?name=${Uri.encodeComponent(profile?.nama ?? "User")}&background=FEF9C3&color=CA8A04&bold=true',
                            fit: BoxFit.cover,
                            errorBuilder: (_, __, ___) => const Icon(Icons.person, color: AppColors.primary),
                          ),
                        ),
                      ).animate(delay: 50.ms).fadeIn(duration: 400.ms),
                      const SizedBox(width: 12),

                      Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          mainAxisAlignment: MainAxisAlignment.center,
                          children: [
                            Text('$greeting,',
                                style: S.caption(AppColors.textSecondary))
                                .animate().fadeIn(duration: 500.ms),
                            Text(
                              profile?.nama ?? '—',
                              style: S.h2(AppColors.textPrimary).copyWith(fontWeight: FontWeight.w700),
                              maxLines: 1,
                              overflow: TextOverflow.ellipsis,
                            ).animate(delay: 100.ms).fadeIn(duration: 500.ms),
                            const SizedBox(height: 2),
                            Row(
                              children: [
                                const Icon(Icons.calendar_today_outlined,
                                    size: 11, color: AppColors.textMuted),
                                const SizedBox(width: 4),
                                Text(
                                  DateFormat('EEEE, dd MMMM yyyy', 'id')
                                      .format(DateTime.now()),
                                  style: S.caption(AppColors.textMuted),
                                ),
                              ],
                            ).animate(delay: 150.ms).fadeIn(duration: 500.ms),
                          ],
                        ),
                      ),

                      // Notification icon
                      Container(
                        width: 42, height: 42,
                        decoration: BoxDecoration(
                          color: AppColors.surface,
                          shape: BoxShape.circle,
                          border: Border.all(color: AppColors.border),
                          boxShadow: [
                            BoxShadow(
                              color: Colors.black.withOpacity(0.03),
                              blurRadius: 6,
                              offset: const Offset(0, 2),
                            ),
                          ],
                        ),
                        child: const Icon(Icons.notifications_none_rounded,
                            color: AppColors.textSecondary, size: 20),
                      ).animate(delay: 200.ms).fadeIn(duration: 400.ms),
                    ],
                  ),
                  const SizedBox(height: 22),

                  // ── Stat Cards ─────────────────────
                  Row(
                    children: [
                      _StatCard('Total', total, Icons.list_alt_rounded, AppColors.primary),
                      const SizedBox(width: 10),
                      _StatCard('Berjalan', berjalan, Icons.directions_walk, AppColors.warning),
                      const SizedBox(width: 10),
                      _StatCard('Selesai', selesai, Icons.check_circle_rounded, AppColors.success),
                      const SizedBox(width: 10),
                      _StatCard('Belum', belum, Icons.schedule_rounded, AppColors.pending),
                    ],
                  ).animate(delay: 250.ms).fadeIn(duration: 500.ms).slideY(begin: 0.15, end: 0),
                ],
              ),
            ),
          ),
        ),

        // ── Tab Bar ──────────────────────────────────
        Padding(
          padding: const EdgeInsets.fromLTRB(20, 16, 20, 8),
          child: Container(
            height: 44,
            decoration: BoxDecoration(
              color: AppColors.surface,
              borderRadius: BorderRadius.circular(12),
              border: Border.all(color: AppColors.border),
            ),
            child: Row(
              children: [
                _TabBtn('Hari Ini', 0, taskTab, onTaskTabChange),
                _TabBtn('Semua', 1, taskTab, onTaskTabChange),
              ],
            ),
          ),
        ).animate(delay: 350.ms).fadeIn(duration: 400.ms),

        // ── Task List ─────────────────────────────────
        Expanded(
          child: prov.isLoading
              ? const Center(
                  child: CircularProgressIndicator(color: AppColors.primary))
              : tasks.isEmpty
                  ? _EmptyKunjungan(
                      onRefresh: () => prov.fetchTasks(
                          filter: taskTab == 0 ? 'today' : 'all'),
                    )
                  : RefreshIndicator(
                      onRefresh: () => prov.fetchTasks(
                          filter: taskTab == 0 ? 'today' : 'all'),
                      color: AppColors.primary,
                      backgroundColor: AppColors.card,
                      child: TaskListPage(tasks: tasks),
                    ),
        ),
      ],
    );
  }
}

// ── Animated Stat Card ───────────────────────────────────
class _StatCard extends StatelessWidget {
  final String label;
  final int value;
  final IconData icon;
  final Color color;
  const _StatCard(this.label, this.value, this.icon, this.color);

  @override
  Widget build(BuildContext context) {
    return Expanded(
      child: Container(
        padding: const EdgeInsets.symmetric(vertical: 14, horizontal: 8),
        decoration: BoxDecoration(
          color: AppColors.card,
          borderRadius: BorderRadius.circular(16),
          border: Border.all(color: AppColors.border),
          boxShadow: [
            BoxShadow(
              color: Colors.black.withOpacity(0.02),
              blurRadius: 8,
              offset: const Offset(0, 2),
            ),
          ],
        ),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            // Circular Icon Badge
            Container(
              padding: const EdgeInsets.all(6),
              decoration: BoxDecoration(
                color: color.withOpacity(0.12),
                shape: BoxShape.circle,
              ),
              child: Icon(icon, color: color, size: 16),
            ),
            const SizedBox(height: 8),
            // Value
            TweenAnimationBuilder<int>(
              tween: IntTween(begin: 0, end: value),
              duration: const Duration(milliseconds: 600),
              curve: Curves.easeOut,
              builder: (_, v, __) => Text(
                '$v',
                style: S.h2(AppColors.textPrimary).copyWith(fontWeight: FontWeight.w700),
                textAlign: TextAlign.center,
              ),
            ),
            const SizedBox(height: 2),
            // Label
            Text(
              label,
              style: S.label(AppColors.textSecondary),
              textAlign: TextAlign.center,
            ),
          ],
        ),
      ),
    );
  }
}

// ── Tab Button ───────────────────────────────────────────
class _TabBtn extends StatelessWidget {
  final String label;
  final int idx;
  final int current;
  final ValueChanged<int> onTap;
  const _TabBtn(this.label, this.idx, this.current, this.onTap);

  @override
  Widget build(BuildContext context) {
    final active = current == idx;
    return Expanded(
      child: GestureDetector(
        onTap: () => onTap(idx),
        child: AnimatedContainer(
          duration: const Duration(milliseconds: 200),
          margin: const EdgeInsets.all(3),
          decoration: BoxDecoration(
            color: active ? AppColors.primary : Colors.transparent,
            borderRadius: BorderRadius.circular(10),
            boxShadow: active
                ? [BoxShadow(
                    color: AppColors.primary.withOpacity(0.2),
                    blurRadius: 6, offset: const Offset(0, 2))]
                : [],
          ),
          child: Center(
            child: Text(label,
                style: active
                    ? S.btnSm(AppColors.textPrimary).copyWith(fontWeight: FontWeight.w700)
                    : S.btnSm(AppColors.textSecondary)),
          ),
        ),
      ),
    );
  }
}

// ── Empty Kunjungan ──────────────────────────────────────
class _EmptyKunjungan extends StatelessWidget {
  final VoidCallback onRefresh;
  const _EmptyKunjungan({required this.onRefresh});

  @override
  Widget build(BuildContext context) {
    return Center(
      child: Column(
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          Container(
            width: 80, height: 80,
            decoration: BoxDecoration(
              color: AppColors.card,
              shape: BoxShape.circle,
              border: Border.all(color: AppColors.border),
            ),
            child: const Icon(Icons.event_available_outlined,
                size: 36, color: AppColors.textMuted),
          ),
          const SizedBox(height: 16),
          Text('Tidak ada kunjungan', style: S.h3(AppColors.textSecondary)),
          const SizedBox(height: 6),
          Text('Tarik ke bawah untuk refresh', style: S.caption()),
          const SizedBox(height: 24),
          GestureDetector(
            onTap: onRefresh,
            child: Container(
              padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 10),
              decoration: BoxDecoration(
                color: AppColors.primary.withOpacity(0.12),
                borderRadius: BorderRadius.circular(20),
                border: Border.all(color: AppColors.primary.withOpacity(0.3)),
              ),
              child: Row(
                mainAxisSize: MainAxisSize.min,
                children: [
                  const Icon(Icons.refresh_rounded,
                      color: AppColors.primaryLight, size: 16),
                  const SizedBox(width: 6),
                  Text('Refresh', style: S.btnSm(AppColors.primaryLight)),
                ],
              ),
            ),
          ),
        ],
      ),
    ).animate().fadeIn(duration: 500.ms);
  }
}
