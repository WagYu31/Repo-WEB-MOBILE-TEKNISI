import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:provider/provider.dart';
import 'package:intl/intl.dart';
import '../../service/provider/SalesProvider.dart';
import '../task/TaskListPage.dart';
import '../login/LoginPage.dart';

class HomePage extends StatefulWidget {
  static const routeName = '/home';
  const HomePage({super.key});
  @override
  State<HomePage> createState() => _HomePageState();
}

class _HomePageState extends State<HomePage> {
  int _tab = 0;

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      context.read<SalesProvider>().fetchTasks(filter: 'today');
    });
  }

  @override
  Widget build(BuildContext context) {
    final prov = context.watch<SalesProvider>();
    final profile = prov.profile;
    final tasks = prov.tasks;

    // Stats
    final total   = tasks.length;
    final berjalan = tasks.where((t) => t.sedangBerjalan).length;
    final selesai  = tasks.where((t) => t.selesai).length;
    final belum    = tasks.where((t) => !t.sudahClockIn).length;

    return Scaffold(
      backgroundColor: const Color(0xFF0F172A),
      body: SafeArea(
        child: Column(
          children: [
            // ── Header ──────────────────────────────────────────
            Container(
              padding: const EdgeInsets.fromLTRB(20, 20, 20, 16),
              decoration: const BoxDecoration(
                gradient: LinearGradient(
                  colors: [Color(0xFF1E3A5F), Color(0xFF0F172A)],
                  begin: Alignment.topCenter,
                  end: Alignment.bottomCenter,
                ),
              ),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text('Selamat Datang,',
                              style: GoogleFonts.poppins(fontSize: 13, color: Colors.white54)),
                          Text(profile?.nama ?? '—',
                              style: GoogleFonts.poppins(
                                  fontSize: 20, fontWeight: FontWeight.w700, color: Colors.white)),
                        ],
                      ),
                      GestureDetector(
                        onTap: () async {
                          await prov.logout();
                          if (!mounted) return;
                          Navigator.pushReplacementNamed(context, LoginPage.routeName);
                        },
                        child: Container(
                          padding: const EdgeInsets.all(8),
                          decoration: BoxDecoration(
                            color: const Color(0xFF1E293B),
                            borderRadius: BorderRadius.circular(10),
                            border: Border.all(color: const Color(0xFF334155)),
                          ),
                          child: const Icon(Icons.logout, color: Colors.white54, size: 20),
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(height: 6),
                  Text(
                    DateFormat('EEEE, dd MMMM yyyy', 'id').format(DateTime.now()),
                    style: GoogleFonts.poppins(fontSize: 12, color: Colors.white38),
                  ),
                ],
              ),
            ),

            // ── Stats Cards ─────────────────────────────────────
            Padding(
              padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
              child: Row(
                children: [
                  _statCard('Total', total.toString(), Icons.list_alt, const Color(0xFF3B82F6)),
                  const SizedBox(width: 10),
                  _statCard('Berjalan', berjalan.toString(), Icons.directions_walk, const Color(0xFFF59E0B)),
                  const SizedBox(width: 10),
                  _statCard('Selesai', selesai.toString(), Icons.check_circle, const Color(0xFF10B981)),
                  const SizedBox(width: 10),
                  _statCard('Belum', belum.toString(), Icons.schedule, const Color(0xFF6B7280)),
                ],
              ),
            ),

            // ── Tab Bar ─────────────────────────────────────────
            Padding(
              padding: const EdgeInsets.symmetric(horizontal: 16),
              child: Container(
                decoration: BoxDecoration(
                  color: const Color(0xFF1E293B),
                  borderRadius: BorderRadius.circular(12),
                ),
                child: Row(
                  children: [
                    _tabBtn('Hari Ini', 0),
                    _tabBtn('Semua', 1),
                  ],
                ),
              ),
            ),
            const SizedBox(height: 8),

            // ── Task List ────────────────────────────────────────
            Expanded(
              child: prov.isLoading
                  ? const Center(
                      child: CircularProgressIndicator(color: Color(0xFF3B82F6)))
                  : tasks.isEmpty
                      ? _emptyState()
                      : RefreshIndicator(
                          color: const Color(0xFF3B82F6),
                          backgroundColor: const Color(0xFF1E293B),
                          onRefresh: () => prov.fetchTasks(
                              filter: _tab == 0 ? 'today' : 'all'),
                          child: TaskListPage(tasks: tasks),
                        ),
            ),
          ],
        ),
      ),

      // ── Refresh FAB ─────────────────────────────────────────
      floatingActionButton: FloatingActionButton(
        backgroundColor: const Color(0xFF3B82F6),
        child: const Icon(Icons.refresh, color: Colors.white),
        onPressed: () =>
            prov.fetchTasks(filter: _tab == 0 ? 'today' : 'all'),
      ),
    );
  }

  Widget _statCard(String label, String value, IconData icon, Color color) {
    return Expanded(
      child: Container(
        padding: const EdgeInsets.symmetric(vertical: 12, horizontal: 8),
        decoration: BoxDecoration(
          color: const Color(0xFF1E293B),
          borderRadius: BorderRadius.circular(12),
          border: Border.all(color: color.withOpacity(0.3)),
        ),
        child: Column(
          children: [
            Icon(icon, color: color, size: 20),
            const SizedBox(height: 4),
            Text(value,
                style: GoogleFonts.poppins(
                    fontSize: 18, fontWeight: FontWeight.w700, color: color)),
            Text(label,
                style: GoogleFonts.poppins(fontSize: 9, color: Colors.white38),
                textAlign: TextAlign.center),
          ],
        ),
      ),
    );
  }

  Widget _tabBtn(String label, int idx) {
    final active = _tab == idx;
    return Expanded(
      child: GestureDetector(
        onTap: () {
          setState(() => _tab = idx);
          context.read<SalesProvider>().fetchTasks(
              filter: idx == 0 ? 'today' : 'all');
        },
        child: Container(
          padding: const EdgeInsets.symmetric(vertical: 10),
          decoration: BoxDecoration(
            color: active ? const Color(0xFF3B82F6) : Colors.transparent,
            borderRadius: BorderRadius.circular(12),
          ),
          child: Text(label,
              textAlign: TextAlign.center,
              style: GoogleFonts.poppins(
                  fontSize: 13,
                  fontWeight: FontWeight.w600,
                  color: active ? Colors.white : Colors.white38)),
        ),
      ),
    );
  }

  Widget _emptyState() {
    return Center(
      child: Column(
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          Icon(Icons.event_available_outlined, size: 64, color: Colors.white12),
          const SizedBox(height: 12),
          Text('Tidak ada kunjungan',
              style: GoogleFonts.poppins(fontSize: 16, color: Colors.white38)),
          const SizedBox(height: 4),
          Text('Tarik ke bawah untuk refresh',
              style: GoogleFonts.poppins(fontSize: 12, color: Colors.white24)),
        ],
      ),
    );
  }
}
