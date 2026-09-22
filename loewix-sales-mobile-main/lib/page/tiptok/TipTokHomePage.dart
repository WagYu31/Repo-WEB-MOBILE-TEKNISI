import 'package:flutter/material.dart';
import 'package:intl/intl.dart';
import 'package:provider/provider.dart';
import 'package:quickalert/quickalert.dart';
import '../../core/app_theme.dart';
import '../../service/api/ApiTipTok.dart';
import '../../service/model/TipTokModel.dart';
import '../../service/provider/SalesProvider.dart';
import 'CreatePenitipanPage.dart';
import 'AuditKunjunganPage.dart';
import 'ClaimInsentifPage.dart';

class TipTokHomePage extends StatefulWidget {
  static const routeName = '/tiptok';
  const TipTokHomePage({super.key});

  @override
  State<TipTokHomePage> createState() => _TipTokHomePageState();
}

class _TipTokHomePageState extends State<TipTokHomePage> {
  final _api = ApiTipTok();
  bool _isLoading = true;
  String _errorMessage = '';

  TipTokMetricsModel? _metrics;
  List<TipTokPenitipanModel> _allList = [];
  String _searchQuery = '';
  int _selectedFilterTab = 0; // 0=Semua, 1=Stok Aktif, 2=Ada Penjualan, 3=Selesai

  @override
  void initState() {
    super.initState();
    _loadData();
  }

  Future<void> _loadData() async {
    setState(() {
      _isLoading = true;
      _errorMessage = '';
    });

    final prov = context.read<SalesProvider>();
    final salesId = prov.profile?.id ?? 0;

    try {
      final res = await _api.getDashboard(salesId);
      if (mounted) {
        setState(() {
          _metrics = res.metrics;
          _allList = res.list;
          _isLoading = false;
        });
      }
    } catch (e) {
      if (mounted) {
        setState(() {
          _errorMessage = e.toString().replaceAll('Exception: ', '');
          _isLoading = false;
        });
      }
    }
  }

  List<TipTokPenitipanModel> get _filteredList {
    return _allList.where((p) {
      // Filter tab
      if (_selectedFilterTab == 1 && p.sumSisa <= 0) return false;
      if (_selectedFilterTab == 2 && p.sumTerjual <= 0) return false;
      if (_selectedFilterTab == 3 && p.status != 'selesai') return false;

      // Filter search
      if (_searchQuery.isNotEmpty) {
        final q = _searchQuery.toLowerCase();
        final matchToko = p.namaToko.toLowerCase().contains(q);
        final matchAlamat = p.alamatToko.toLowerCase().contains(q);
        final matchKode = p.kodeTitip.toLowerCase().contains(q);
        final matchBarang = p.items.any((it) => it.namaBarang.toLowerCase().contains(q));
        if (!matchToko && !matchAlamat && !matchKode && !matchBarang) return false;
      }
      return true;
    }).toList();
  }

  @override
  Widget build(BuildContext context) {
    final prov = context.watch<SalesProvider>();
    final salesId = prov.profile?.id ?? 0;
    final namaSales = prov.profile?.nama ?? 'Sales';
    final curFormat = NumberFormat.currency(locale: 'id_ID', symbol: 'Rp ', decimalDigits: 0);

    return Scaffold(
      backgroundColor: AppColors.bg,
      appBar: AppBar(
        title: const Text('TIP TOK (Titip Toko)', style: TextStyle(fontWeight: FontWeight.w800, fontSize: 16)),
        backgroundColor: AppColors.surface,
        elevation: 0.5,
        foregroundColor: AppColors.textPrimary,
        actions: [
          IconButton(
            icon: const Icon(Icons.refresh_rounded),
            onPressed: _loadData,
          ),
        ],
      ),
      body: _isLoading
          ? const Center(child: CircularProgressIndicator(color: AppColors.primary))
          : _errorMessage.isNotEmpty
              ? Center(
                  child: Padding(
                    padding: const EdgeInsets.all(24),
                    child: Column(
                      mainAxisAlignment: MainAxisAlignment.center,
                      children: [
                        const Icon(Icons.error_outline_rounded, color: Colors.redAccent, size: 48),
                        const SizedBox(height: 12),
                        Text(_errorMessage, textAlign: TextAlign.center, style: S.bodySm()),
                        const SizedBox(height: 16),
                        ElevatedButton(
                          onPressed: _loadData,
                          style: ElevatedButton.styleFrom(backgroundColor: AppColors.primary),
                          child: const Text('Coba Lagi', style: TextStyle(color: Colors.white)),
                        ),
                      ],
                    ),
                  ),
                )
              : RefreshIndicator(
                  onRefresh: _loadData,
                  color: AppColors.primary,
                  child: SingleChildScrollView(
                    physics: const AlwaysScrollableScrollPhysics(),
                    padding: const EdgeInsets.all(16),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        // ── Bento Metrics Header ───────────────────
                        if (_metrics != null) ...[
                          Row(
                            children: [
                              Expanded(
                                child: _MetricCard(
                                  label: 'TOKO AKTIF',
                                  value: '${_metrics!.totalTokoAktif}',
                                  sub: 'Mitra Penitipan',
                                  icon: Icons.storefront_rounded,
                                  color: const Color(0xFF0F172A),
                                ),
                              ),
                              const SizedBox(width: 10),
                              Expanded(
                                child: _MetricCard(
                                  label: 'SISA STOK',
                                  value: '${_metrics!.totalSisa}',
                                  sub: 'Terjual: ${_metrics!.totalTerjual} Unit',
                                  icon: Icons.inventory_2_outlined,
                                  color: const Color(0xFF2563EB),
                                ),
                              ),
                            ],
                          ),
                          const SizedBox(height: 10),
                          // Incentive & Target Meter Card
                          Container(
                            width: double.infinity,
                            padding: const EdgeInsets.all(16),
                            decoration: BoxDecoration(
                              color: const Color(0xFF0F172A),
                              borderRadius: BorderRadius.circular(14),
                            ),
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                Row(
                                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                                  children: [
                                    Text('TABUNGAN INSENTIF TERJUAL', style: S.caption(const Color(0xFF94A3B8)).copyWith(fontWeight: FontWeight.w700)),
                                    GestureDetector(
                                      onTap: () {
                                        Navigator.push(
                                          context,
                                          MaterialPageRoute(
                                            builder: (_) => ClaimInsentifPage(
                                              salesId: salesId,
                                              namaSales: namaSales,
                                              metrics: _metrics!,
                                            ),
                                          ),
                                        ).then((_) => _loadData());
                                      },
                                      child: Container(
                                        padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                                        decoration: BoxDecoration(
                                          color: _metrics!.isClaimEligible ? const Color(0xFF10B981) : const Color(0xFF334155),
                                          borderRadius: BorderRadius.circular(12),
                                        ),
                                        child: Text(
                                          _metrics!.isClaimEligible ? 'Klaim Sekarang' : 'Klaim (Min. 50)',
                                          style: const TextStyle(color: Colors.white, fontSize: 11, fontWeight: FontWeight.w700),
                                        ),
                                      ),
                                    ),
                                  ],
                                ),
                                const SizedBox(height: 6),
                                Text(
                                  curFormat.format(_metrics!.unclaimedNominal),
                                  style: const TextStyle(color: Color(0xFF34D399), fontWeight: FontWeight.w800, fontSize: 22),
                                ),
                                const SizedBox(height: 8),
                                Row(
                                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                                  children: [
                                    Text('Progres Klaim: ${_metrics!.unclaimedUnits} / 50 Unit', style: const TextStyle(color: Colors.white70, fontSize: 11)),
                                    Text('${_metrics!.claimProgress}%', style: const TextStyle(color: Colors.white70, fontSize: 11, fontWeight: FontWeight.w700)),
                                  ],
                                ),
                                const SizedBox(height: 4),
                                ClipRRect(
                                  borderRadius: BorderRadius.circular(4),
                                  child: LinearProgressIndicator(
                                    value: (_metrics!.unclaimedUnits / 50).clamp(0.0, 1.0),
                                    backgroundColor: const Color(0xFF334155),
                                    valueColor: AlwaysStoppedAnimation<Color>(_metrics!.isClaimEligible ? const Color(0xFF10B981) : const Color(0xFFF59E0B)),
                                    minHeight: 5,
                                  ),
                                ),
                              ],
                            ),
                          ),
                        ],

                        const SizedBox(height: 18),

                        // ── CTA Bar: + Titip Barang Baru ─────────────
                        if (prov.tasks.isEmpty) ...[
                          Container(
                            padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 10),
                            decoration: BoxDecoration(
                              color: const Color(0xFFFEF3C7),
                              borderRadius: BorderRadius.circular(10),
                              border: Border.all(color: const Color(0xFFFDE68A)),
                            ),
                            child: Row(
                              children: [
                                const Icon(Icons.info_outline_rounded, color: Color(0xFFD97706), size: 18),
                                const SizedBox(width: 10),
                                const Expanded(
                                  child: Text(
                                    'Belum ada jadwal kunjungan toko hari ini dari Admin. Penitipan barang baru terkunci.',
                                    style: TextStyle(color: Color(0xFF92400E), fontSize: 11, fontWeight: FontWeight.w600),
                                  ),
                                ),
                              ],
                            ),
                          ),
                          const SizedBox(height: 10),
                        ],

                        SizedBox(
                          width: double.infinity,
                          height: 46,
                          child: ElevatedButton.icon(
                            onPressed: () async {
                              if (prov.tasks.isEmpty) {
                                QuickAlert.show(
                                  context: context,
                                  type: QuickAlertType.warning,
                                  title: 'Tidak Ada Jadwal Kunjungan',
                                  text: 'Sales TIDAK BISA menitipkan barang jika belum ada jadwal kunjungan resmi dari Admin hari ini.\n\nSilakan minta Admin untuk membuatkan jadwal kunjungan terlebih dahulu.',
                                  confirmBtnText: 'Mengerti',
                                  confirmBtnColor: AppColors.warning,
                                );
                                return;
                              }

                              final res = await Navigator.push(
                                context,
                                MaterialPageRoute(
                                  builder: (_) => CreatePenitipanPage(
                                    salesId: salesId,
                                    namaSales: namaSales,
                                    preselectedCustomerId: prov.tasks.length == 1 ? prov.tasks.first.customerId : null,
                                    preselectedCustomerName: prov.tasks.length == 1 ? prov.tasks.first.namaCustomer : null,
                                  ),
                                ),
                              );
                              if (res == true) _loadData();
                            },
                            icon: Icon(
                              prov.tasks.isEmpty ? Icons.lock_outline_rounded : Icons.add_circle_outline_rounded,
                              size: 20,
                            ),
                            label: Text(
                              prov.tasks.isEmpty ? 'Titip Barang Baru (Perlu Jadwal Admin)' : 'Titip Barang Baru di Toko',
                              style: const TextStyle(fontWeight: FontWeight.w700),
                            ),
                            style: ElevatedButton.styleFrom(
                              backgroundColor: prov.tasks.isEmpty ? const Color(0xFF475569) : const Color(0xFF0F172A),
                              foregroundColor: Colors.white,
                              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                            ),
                          ),
                        ),

                        const SizedBox(height: 16),

                        // ── Search & Filter Tabs ──────────────────
                        TextField(
                          onChanged: (v) => setState(() => _searchQuery = v),
                          decoration: InputDecoration(
                            hintText: 'Cari nama toko, alamat, barang...',
                            prefixIcon: const Icon(Icons.search, color: AppColors.textMuted),
                            filled: true,
                            fillColor: AppColors.surface,
                            contentPadding: const EdgeInsets.symmetric(horizontal: 14, vertical: 10),
                            border: OutlineInputBorder(
                              borderRadius: BorderRadius.circular(10),
                              borderSide: const BorderSide(color: AppColors.border),
                            ),
                          ),
                        ),

                        const SizedBox(height: 12),

                        SingleChildScrollView(
                          scrollDirection: Axis.horizontal,
                          child: Row(
                            children: [
                              _FilterTab('Semua (${_allList.length})', 0),
                              const SizedBox(width: 8),
                              _FilterTab('Stok Aktif (${_allList.where((p) => p.sumSisa > 0).length})', 1),
                              const SizedBox(width: 8),
                              _FilterTab('Ada Penjualan (${_allList.where((p) => p.sumTerjual > 0).length})', 2),
                              const SizedBox(width: 8),
                              _FilterTab('Selesai (${_allList.where((p) => p.status == 'selesai').length})', 3),
                            ],
                          ),
                        ),

                        const SizedBox(height: 16),

                        // ── Consignment List ──────────────────────
                        if (_filteredList.isEmpty)
                          Container(
                            width: double.infinity,
                            padding: const EdgeInsets.all(32),
                            decoration: BoxDecoration(
                              color: AppColors.surface,
                              borderRadius: BorderRadius.circular(12),
                              border: Border.all(color: AppColors.border),
                            ),
                            child: const Column(
                              children: [
                                Icon(Icons.inventory_2_outlined, color: AppColors.textMuted, size: 40),
                                SizedBox(height: 8),
                                Text('Belum ada data titipan barang', style: TextStyle(color: AppColors.textMuted, fontWeight: FontWeight.w600)),
                              ],
                            ),
                          )
                        else
                          ListView.separated(
                            shrinkWrap: true,
                            physics: const NeverScrollableScrollPhysics(),
                            itemCount: _filteredList.length,
                            separatorBuilder: (_, __) => const SizedBox(height: 12),
                            itemBuilder: (context, i) {
                              final item = _filteredList[i];
                              return _PenitipanCard(
                                penitipan: item,
                                salesId: salesId,
                                namaSales: namaSales,
                                onAuditSuccess: _loadData,
                              );
                            },
                          ),
                        const SizedBox(height: 32),
                      ],
                    ),
                  ),
                ),
    );
  }

  Widget _FilterTab(String label, int index) {
    final isSelected = _selectedFilterTab == index;
    return GestureDetector(
      onTap: () => setState(() => _selectedFilterTab = index),
      child: Container(
        padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 8),
        decoration: BoxDecoration(
          color: isSelected ? const Color(0xFF0F172A) : AppColors.surface,
          borderRadius: BorderRadius.circular(20),
          border: Border.all(color: isSelected ? const Color(0xFF0F172A) : AppColors.border),
        ),
        child: Text(
          label,
          style: TextStyle(
            color: isSelected ? Colors.white : AppColors.textSecondary,
            fontSize: 12,
            fontWeight: isSelected ? FontWeight.w700 : FontWeight.w500,
          ),
        ),
      ),
    );
  }
}

class _MetricCard extends StatelessWidget {
  final String label;
  final String value;
  final String sub;
  final IconData icon;
  final Color color;

  const _MetricCard({
    required this.label,
    required this.value,
    required this.sub,
    required this.icon,
    required this.color,
  });

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: AppColors.surface,
        borderRadius: BorderRadius.circular(12),
        border: Border.all(color: AppColors.border),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Text(label, style: S.caption(AppColors.textMuted).copyWith(fontWeight: FontWeight.w700, letterSpacing: 0.5)),
              Icon(icon, size: 18, color: color),
            ],
          ),
          const SizedBox(height: 6),
          Text(value, style: TextStyle(color: color, fontWeight: FontWeight.w800, fontSize: 20)),
          const SizedBox(height: 2),
          Text(sub, style: S.caption(AppColors.textSecondary), maxLines: 1, overflow: TextOverflow.ellipsis),
        ],
      ),
    );
  }
}

class _PenitipanCard extends StatefulWidget {
  final TipTokPenitipanModel penitipan;
  final int salesId;
  final String namaSales;
  final VoidCallback onAuditSuccess;

  const _PenitipanCard({
    required this.penitipan,
    required this.salesId,
    required this.namaSales,
    required this.onAuditSuccess,
  });

  @override
  State<_PenitipanCard> createState() => _PenitipanCardState();
}

class _PenitipanCardState extends State<_PenitipanCard> {
  bool _isExpanded = false;

  @override
  Widget build(BuildContext context) {
    final p = widget.penitipan;

    return Container(
      decoration: BoxDecoration(
        color: AppColors.surface,
        borderRadius: BorderRadius.circular(14),
        border: Border.all(color: AppColors.border),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          // Header Card
          Padding(
            padding: const EdgeInsets.all(14),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    Expanded(
                      child: Text(
                        p.namaToko,
                        style: S.bodySm().copyWith(fontWeight: FontWeight.w800, fontSize: 15),
                        maxLines: 1,
                        overflow: TextOverflow.ellipsis,
                      ),
                    ),
                    Container(
                      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                      decoration: BoxDecoration(
                        color: p.status == 'selesai' ? const Color(0xFFF1F5F9) : const Color(0xFFECFDF5),
                        borderRadius: BorderRadius.circular(6),
                      ),
                      child: Text(
                        p.status == 'selesai' ? 'Selesai' : 'Stok Aktif',
                        style: TextStyle(
                          color: p.status == 'selesai' ? const Color(0xFF64748B) : const Color(0xFF059669),
                          fontSize: 10,
                          fontWeight: FontWeight.w700,
                        ),
                      ),
                    ),
                  ],
                ),
                const SizedBox(height: 4),
                Text(
                  "${p.kategoriCustomer} • ${p.alamatToko}, ${p.kotaToko}",
                  style: S.caption(AppColors.textSecondary),
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis,
                ),
                const SizedBox(height: 10),

                // Stats Pills
                Row(
                  children: [
                    Container(
                      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                      decoration: BoxDecoration(
                        color: const Color(0xFFF1F5F9),
                        borderRadius: BorderRadius.circular(6),
                      ),
                      child: Text('Titip: ${p.sumTitip}', style: S.caption().copyWith(fontWeight: FontWeight.w600)),
                    ),
                    const SizedBox(width: 6),
                    Container(
                      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                      decoration: BoxDecoration(
                        color: const Color(0xFFFEF3C7),
                        borderRadius: BorderRadius.circular(6),
                      ),
                      child: Text('Sisa: ${p.sumSisa}', style: S.caption(const Color(0xFFB45309)).copyWith(fontWeight: FontWeight.w700)),
                    ),
                    const SizedBox(width: 6),
                    Container(
                      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                      decoration: BoxDecoration(
                        color: const Color(0xFFECFDF5),
                        borderRadius: BorderRadius.circular(6),
                      ),
                      child: Text('Laku: ${p.sumTerjual}', style: S.caption(const Color(0xFF059669)).copyWith(fontWeight: FontWeight.w700)),
                    ),
                  ],
                ),

                if (p.lastNoInv.isNotEmpty) ...[
                  const SizedBox(height: 8),
                  Row(
                    children: [
                      const Icon(Icons.receipt_rounded, size: 13, color: AppColors.textMuted),
                      const SizedBox(width: 4),
                      Text('Inv Terakhir: ${p.lastNoInv}', style: S.caption(AppColors.textMuted)),
                    ],
                  ),
                ],
              ],
            ),
          ),

          const Divider(height: 1, color: AppColors.border),

          // Actions Bar
          Padding(
            padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 8),
            child: Row(
              children: [
                Expanded(
                  child: OutlinedButton.icon(
                    onPressed: () {
                      setState(() => _isExpanded = !_isExpanded);
                    },
                    icon: Icon(_isExpanded ? Icons.expand_less : Icons.expand_more, size: 18),
                    label: Text('${p.items.length} Barang', style: const TextStyle(fontSize: 12)),
                    style: OutlinedButton.styleFrom(
                      foregroundColor: AppColors.textSecondary,
                      side: const BorderSide(color: AppColors.border),
                    ),
                  ),
                ),
                const SizedBox(width: 8),
                Expanded(
                  child: ElevatedButton.icon(
                    onPressed: p.sumSisa <= 0
                        ? null
                        : () async {
                            final res = await Navigator.push(
                              context,
                              MaterialPageRoute(
                                builder: (_) => AuditKunjunganPage(
                                  penitipan: p,
                                  salesId: widget.salesId,
                                  namaSales: widget.namaSales,
                                ),
                              ),
                            );
                            if (res == true) widget.onAuditSuccess();
                          },
                    icon: const Icon(Icons.fact_check_outlined, size: 16),
                    label: const Text('Cek Sisa Stok', style: TextStyle(fontSize: 12, fontWeight: FontWeight.w700)),
                    style: ElevatedButton.styleFrom(
                      backgroundColor: const Color(0xFF059669),
                      foregroundColor: Colors.white,
                    ),
                  ),
                ),
              ],
            ),
          ),

          // Expanded Item Details
          if (_isExpanded) ...[
            Container(
              padding: const EdgeInsets.all(12),
              color: const Color(0xFFF8FAFC),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text('Rincian Barang di Toko:', style: S.caption(AppColors.textMuted).copyWith(fontWeight: FontWeight.w700)),
                  const SizedBox(height: 6),
                  ...p.items.map((it) {
                    return Padding(
                      padding: const EdgeInsets.symmetric(vertical: 4),
                      child: Row(
                        mainAxisAlignment: MainAxisAlignment.spaceBetween,
                        children: [
                          Expanded(
                            child: Text('• ${it.namaBarang}', style: S.caption(AppColors.textPrimary).copyWith(fontWeight: FontWeight.w600)),
                          ),
                          Text('Sisa: ${it.qtySisa}/${it.qtyTitip} | Laku: ${it.qtyTerjual}', style: S.caption(AppColors.textSecondary)),
                        ],
                      ),
                    );
                  }),
                ],
              ),
            ),
          ],
        ],
      ),
    );
  }
}
