import 'package:flutter/material.dart';
import 'package:intl/intl.dart';
import 'package:quickalert/quickalert.dart';
import '../../core/app_theme.dart';
import '../../service/api/ApiTipTok.dart';
import '../../service/model/TipTokModel.dart';

class ClaimInsentifPage extends StatefulWidget {
  final int salesId;
  final String namaSales;
  final TipTokMetricsModel metrics;
  final List<TipTokPenitipanModel> penitipanList;

  const ClaimInsentifPage({
    super.key,
    required this.salesId,
    required this.namaSales,
    required this.metrics,
    this.penitipanList = const [],
  });

  @override
  State<ClaimInsentifPage> createState() => _ClaimInsentifPageState();
}

class _ClaimInsentifPageState extends State<ClaimInsentifPage> {
  final _api = ApiTipTok();
  bool _isLoading = false;
  List<TipTokClaimModel> _claimHistory = [];
  bool _isLoadingHistory = true;

  @override
  void initState() {
    super.initState();
    _loadHistory();
  }

  Future<void> _loadHistory() async {
    setState(() => _isLoadingHistory = true);
    try {
      final list = await _api.getClaims(widget.salesId);
      if (mounted) {
        setState(() {
          _claimHistory = list;
          _isLoadingHistory = false;
        });
      }
    } catch (_) {
      if (mounted) setState(() => _isLoadingHistory = false);
    }
  }

  void _showClaimModalForStore(TipTokPenitipanModel store) {
    final catatanCtrl = TextEditingController();
    final curFormat = NumberFormat.currency(locale: 'id_ID', symbol: 'Rp ', decimalDigits: 0);

    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (ctx) {
        return StatefulBuilder(
          builder: (context, setModalState) {
            return Container(
              padding: EdgeInsets.only(
                top: 20,
                left: 20,
                right: 20,
                bottom: MediaQuery.of(context).viewInsets.bottom + 20,
              ),
              decoration: const BoxDecoration(
                color: Colors.white,
                borderRadius: BorderRadius.vertical(top: Radius.circular(20)),
              ),
              child: Column(
                mainAxisSize: MainAxisSize.min,
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Center(
                    child: Container(
                      width: 40,
                      height: 4,
                      margin: const EdgeInsets.only(bottom: 16),
                      decoration: BoxDecoration(
                        color: Colors.grey.shade300,
                        borderRadius: BorderRadius.circular(2),
                      ),
                    ),
                  ),
                  Row(
                    children: [
                      Container(
                        padding: const EdgeInsets.all(10),
                        decoration: BoxDecoration(
                          color: const Color(0xFF10B981).withOpacity(0.1),
                          borderRadius: BorderRadius.circular(10),
                        ),
                        child: const Icon(Icons.verified_rounded, color: Color(0xFF10B981), size: 24),
                      ),
                      const SizedBox(width: 12),
                      Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text('Ajukan Klaim Insentif', style: S.h3()),
                            Text(store.namaToko, style: S.caption(AppColors.textSecondary), maxLines: 1, overflow: TextOverflow.ellipsis),
                          ],
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(height: 16),

                  Container(
                    width: double.infinity,
                    padding: const EdgeInsets.all(16),
                    decoration: BoxDecoration(
                      color: const Color(0xFFF0FDF4),
                      borderRadius: BorderRadius.circular(12),
                      border: Border.all(color: const Color(0xFFBBF7D0)),
                    ),
                    child: Row(
                      mainAxisAlignment: MainAxisAlignment.spaceAround,
                      children: [
                        Column(
                          children: [
                            const Text('UNIT TERJUAL', style: TextStyle(fontSize: 11, fontWeight: FontWeight.w700, color: Color(0xFF065F46))),
                            const SizedBox(height: 4),
                            Text('${store.unclaimedUnits} Unit', style: const TextStyle(fontSize: 20, fontWeight: FontWeight.w800, color: Color(0xFF065F46))),
                          ],
                        ),
                        Container(width: 1, height: 35, color: const Color(0xFF86EFAC)),
                        Column(
                          children: [
                            const Text('TOTAL INSENTIF', style: TextStyle(fontSize: 11, fontWeight: FontWeight.w700, color: Color(0xFF065F46))),
                            const SizedBox(height: 4),
                            Text(curFormat.format(store.unclaimedNominal), style: const TextStyle(fontSize: 20, fontWeight: FontWeight.w800, color: Color(0xFF059669))),
                          ],
                        ),
                      ],
                    ),
                  ),

                  const SizedBox(height: 14),
                  TextField(
                    controller: catatanCtrl,
                    maxLines: 2,
                    decoration: const InputDecoration(
                      labelText: 'Catatan Pengajuan Klaim (Opsional)',
                      hintText: 'Cth: Klaim target 50 unit bulan ini',
                      border: OutlineInputBorder(),
                    ),
                  ),

                  const SizedBox(height: 18),
                  SizedBox(
                    width: double.infinity,
                    height: 48,
                    child: ElevatedButton.icon(
                      onPressed: _isLoading ? null : () async {
                        Navigator.pop(ctx);
                        _submitClaimForStore(store.id, store.namaToko, catatanCtrl.text.trim());
                      },
                      icon: const Icon(Icons.send_rounded),
                      label: Text(_isLoading ? 'Memproses...' : 'Kirim Pengajuan Klaim Toko', style: const TextStyle(fontWeight: FontWeight.w700)),
                      style: ElevatedButton.styleFrom(
                        backgroundColor: const Color(0xFF059669),
                        foregroundColor: Colors.white,
                        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                      ),
                    ),
                  ),
                ],
              ),
            );
          },
        );
      },
    );
  }

  Future<void> _submitClaimForStore(int idPenitipan, String namaToko, String catatan) async {
    setState(() => _isLoading = true);
    try {
      final res = await _api.claimInsentif(
        salesId: widget.salesId,
        namaSales: widget.namaSales,
        idPenitipan: idPenitipan,
        catatanClaim: catatan,
      );

      if (!mounted) return;
      setState(() => _isLoading = false);

      QuickAlert.show(
        context: context,
        type: QuickAlertType.success,
        title: 'Klaim Berhasil Diajukan!',
        text: res['message'] ?? 'Pengajuan klaim insentif toko $namaToko berhasil diajukan ke Admin.',
        confirmBtnColor: AppColors.success,
        onConfirmBtnTap: () {
          Navigator.pop(context);
          Navigator.pop(context, true);
        },
      );
    } catch (e) {
      if (!mounted) return;
      setState(() => _isLoading = false);
      QuickAlert.show(
        context: context,
        type: QuickAlertType.error,
        title: 'Gagal Klaim',
        text: e.toString().replaceAll('Exception: ', ''),
        confirmBtnColor: AppColors.error,
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    final curFormat = NumberFormat.currency(locale: 'id_ID', symbol: 'Rp ', decimalDigits: 0);

    // Calculate eligible stores
    final eligibleStores = widget.penitipanList.where((p) => p.isClaimEligible || p.unclaimedUnits >= 50).toList();
    final totalEligibleNominal = eligibleStores.fold<double>(0.0, (sum, p) => sum + p.unclaimedNominal);

    return Scaffold(
      appBar: AppBar(
        title: const Text('Klaim Insentif Penjualan', style: TextStyle(fontWeight: FontWeight.w700)),
        centerTitle: true,
      ),
      body: RefreshIndicator(
        onRefresh: _loadHistory,
        child: SingleChildScrollView(
          physics: const AlwaysScrollableScrollPhysics(),
          padding: const EdgeInsets.all(16),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              // Hero Summary Card
              Container(
                width: double.infinity,
                padding: const EdgeInsets.all(18),
                decoration: BoxDecoration(
                  gradient: const LinearGradient(
                    colors: [Color(0xFF0F172A), Color(0xFF1E293B)],
                    begin: Alignment.topLeft,
                    end: Alignment.bottomRight,
                  ),
                  borderRadius: BorderRadius.circular(16),
                  boxShadow: [
                    BoxShadow(
                      color: Colors.black.withOpacity(0.12),
                      blurRadius: 10,
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
                        const Text(
                          'KLAIM PER TOKO (MIN. 50 UNIT)',
                          style: TextStyle(
                            color: Color(0xFF94A3B8),
                            fontSize: 11,
                            fontWeight: FontWeight.w800,
                            letterSpacing: 0.8,
                          ),
                        ),
                        Container(
                          padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                          decoration: BoxDecoration(
                            color: eligibleStores.isNotEmpty ? const Color(0xFF10B981).withOpacity(0.2) : Colors.white10,
                            borderRadius: BorderRadius.circular(20),
                          ),
                          child: Text(
                            eligibleStores.isNotEmpty ? '${eligibleStores.length} TOKO SIAP' : 'BELUM MEMENUHI',
                            style: TextStyle(
                              color: eligibleStores.isNotEmpty ? const Color(0xFF34D399) : Colors.white60,
                              fontSize: 10,
                              fontWeight: FontWeight.w700,
                            ),
                          ),
                        ),
                      ],
                    ),
                    const SizedBox(height: 12),
                    Row(
                      mainAxisAlignment: MainAxisAlignment.spaceBetween,
                      crossAxisAlignment: CrossAxisAlignment.end,
                      children: [
                        Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            const Text('Insentif Siap Cair', style: TextStyle(color: Colors.white70, fontSize: 12)),
                            const SizedBox(height: 4),
                            Text(
                              curFormat.format(totalEligibleNominal),
                              style: const TextStyle(
                                color: Color(0xFF34D399),
                                fontWeight: FontWeight.w800,
                                fontSize: 24,
                                letterSpacing: -0.5,
                              ),
                            ),
                          ],
                        ),
                        Column(
                          crossAxisAlignment: CrossAxisAlignment.end,
                          children: [
                            const Text('Toko Target', style: TextStyle(color: Colors.white70, fontSize: 12)),
                            const SizedBox(height: 4),
                            Text(
                              '${eligibleStores.length} Toko',
                              style: const TextStyle(
                                color: Colors.white,
                                fontWeight: FontWeight.w800,
                                fontSize: 20,
                              ),
                            ),
                          ],
                        ),
                      ],
                    ),
                    const SizedBox(height: 12),
                    Container(
                      padding: const EdgeInsets.all(10),
                      decoration: BoxDecoration(
                        color: Colors.white.withOpacity(0.06),
                        borderRadius: BorderRadius.circular(8),
                      ),
                      child: const Row(
                        children: [
                          Icon(Icons.info_outline_rounded, color: Color(0xFF94A3B8), size: 16),
                          SizedBox(width: 8),
                          Expanded(
                            child: Text(
                              'Perhitungan klaim insentif berlaku per masing-masing toko mitra (minimal 50 unit terjual per toko).',
                              style: TextStyle(color: Color(0xFFCBD5E1), fontSize: 11.5),
                            ),
                          ),
                        ],
                      ),
                    ),
                  ],
                ),
              ),

              const SizedBox(height: 20),

              // Daftar Toko & Status Klaim
              Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  Text('Status Klaim Per Toko Mitra', style: S.h3()),
                  Text('Target: 50 Unit', style: S.caption(AppColors.textMuted)),
                ],
              ),
              const SizedBox(height: 10),

              if (widget.penitipanList.isEmpty)
                Container(
                  width: double.infinity,
                  padding: const EdgeInsets.all(24),
                  decoration: BoxDecoration(
                    color: AppColors.surface,
                    borderRadius: BorderRadius.circular(12),
                    border: Border.all(color: AppColors.border),
                  ),
                  child: const Center(
                    child: Text('Belum ada data toko penitipan barang.', style: TextStyle(color: AppColors.textMuted)),
                  ),
                )
              else
                ListView.separated(
                  shrinkWrap: true,
                  physics: const NeverScrollableScrollPhysics(),
                  itemCount: widget.penitipanList.length,
                  separatorBuilder: (_, __) => const SizedBox(height: 12),
                  itemBuilder: (context, i) {
                    final store = widget.penitipanList[i];
                    final isReady = store.isClaimEligible || store.unclaimedUnits >= 50;
                    final progressVal = (store.unclaimedUnits / 50.0).clamp(0.0, 1.0);

                    return Container(
                      padding: const EdgeInsets.all(16),
                      decoration: BoxDecoration(
                        color: AppColors.surface,
                        borderRadius: BorderRadius.circular(14),
                        border: Border.all(
                          color: isReady ? const Color(0xFF10B981) : AppColors.border,
                          width: isReady ? 1.5 : 1,
                        ),
                        boxShadow: isReady ? [
                          BoxShadow(
                            color: const Color(0xFF10B981).withOpacity(0.08),
                            blurRadius: 8,
                            offset: const Offset(0, 2),
                          ),
                        ] : null,
                      ),
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Row(
                            mainAxisAlignment: MainAxisAlignment.spaceBetween,
                            children: [
                              Expanded(
                                child: Column(
                                  crossAxisAlignment: CrossAxisAlignment.start,
                                  children: [
                                    Row(
                                      children: [
                                        Container(
                                          padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
                                          decoration: BoxDecoration(
                                            color: AppColors.bg,
                                            borderRadius: BorderRadius.circular(4),
                                            border: Border.all(color: AppColors.border),
                                          ),
                                          child: Text(store.kodeTitip, style: const TextStyle(fontSize: 10, fontWeight: FontWeight.w700, color: AppColors.textSecondary)),
                                        ),
                                      ],
                                    ),
                                    const SizedBox(height: 4),
                                    Text(store.namaToko, style: S.h3().copyWith(fontSize: 16), maxLines: 1, overflow: TextOverflow.ellipsis),
                                  ],
                                ),
                              ),
                              Container(
                                padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                                decoration: BoxDecoration(
                                  color: isReady ? const Color(0xFF10B981).withOpacity(0.12) : AppColors.bg,
                                  borderRadius: BorderRadius.circular(6),
                                  border: Border.all(color: isReady ? const Color(0xFF10B981).withOpacity(0.3) : AppColors.border),
                                ),
                                child: Text(
                                  isReady ? 'SIAP KLAIM' : 'TERKUNCI',
                                  style: TextStyle(
                                    color: isReady ? const Color(0xFF059669) : AppColors.textMuted,
                                    fontSize: 11,
                                    fontWeight: FontWeight.w800,
                                  ),
                                ),
                              ),
                            ],
                          ),
                          const SizedBox(height: 12),

                          // Target Progress Bar
                          Row(
                            mainAxisAlignment: MainAxisAlignment.spaceBetween,
                            children: [
                              Text(
                                'Progres: ${store.unclaimedUnits} / 50 Unit',
                                style: TextStyle(
                                  fontSize: 12,
                                  fontWeight: FontWeight.w700,
                                  color: isReady ? const Color(0xFF059669) : AppColors.textPrimary,
                                ),
                              ),
                              Text(
                                '${(progressVal * 100).toInt()}%',
                                style: const TextStyle(fontSize: 12, fontWeight: FontWeight.w700, color: AppColors.textSecondary),
                              ),
                            ],
                          ),
                          const SizedBox(height: 6),
                          ClipRRect(
                            borderRadius: BorderRadius.circular(4),
                            child: LinearProgressIndicator(
                              value: progressVal,
                              backgroundColor: const Color(0xFFE2E8F0),
                              valueColor: AlwaysStoppedAnimation<Color>(
                                isReady ? const Color(0xFF10B981) : const Color(0xFF3B82F6),
                              ),
                              minHeight: 8,
                            ),
                          ),
                          const SizedBox(height: 8),

                          Row(
                            mainAxisAlignment: MainAxisAlignment.spaceBetween,
                            children: [
                              Text(
                                isReady ? 'Target 50 Unit Tercapai!' : 'Kurang ${50 - store.unclaimedUnits} unit lagi',
                                style: TextStyle(
                                  fontSize: 11.5,
                                  fontWeight: FontWeight.w600,
                                  color: isReady ? const Color(0xFF059669) : AppColors.textMuted,
                                ),
                              ),
                              Text(
                                'Insentif: ${curFormat.format(store.unclaimedNominal)}',
                                style: TextStyle(
                                  fontSize: 12.5,
                                  fontWeight: FontWeight.w800,
                                  color: isReady ? const Color(0xFF059669) : AppColors.textSecondary,
                                ),
                              ),
                            ],
                          ),

                          const SizedBox(height: 12),
                          if (isReady)
                            SizedBox(
                              width: double.infinity,
                              height: 42,
                              child: ElevatedButton.icon(
                                onPressed: _isLoading ? null : () => _showClaimModalForStore(store),
                                icon: const Icon(Icons.send_rounded, size: 18),
                                label: const Text('Ajukan Klaim Toko Ini', style: TextStyle(fontWeight: FontWeight.w700, fontSize: 13)),
                                style: ElevatedButton.styleFrom(
                                  backgroundColor: const Color(0xFF059669),
                                  foregroundColor: Colors.white,
                                  shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
                                ),
                              ),
                            )
                          else
                            Container(
                              width: double.infinity,
                              height: 38,
                              alignment: Alignment.center,
                              decoration: BoxDecoration(
                                color: const Color(0xFFF8FAFC),
                                borderRadius: BorderRadius.circular(8),
                                border: Border.all(color: const Color(0xFFE2E8F0)),
                              ),
                              child: const Row(
                                mainAxisAlignment: MainAxisAlignment.center,
                                children: [
                                  Icon(Icons.lock_outline_rounded, size: 16, color: Color(0xFF94A3B8)),
                                  SizedBox(width: 6),
                                  Text(
                                    'Terkunci (Perlu 50 Unit Terjual)',
                                    style: TextStyle(color: Color(0xFF94A3B8), fontSize: 12, fontWeight: FontWeight.w700),
                                  ),
                                ],
                              ),
                            ),
                        ],
                      ),
                    );
                  },
                ),

              const SizedBox(height: 24),

              // Riwayat Klaim
              Text('Riwayat Pengajuan Klaim', style: S.h3()),
              const SizedBox(height: 10),

              if (_isLoadingHistory)
                const Center(child: Padding(padding: EdgeInsets.all(20), child: CircularProgressIndicator(color: AppColors.primary)))
              else if (_claimHistory.isEmpty)
                Container(
                  width: double.infinity,
                  padding: const EdgeInsets.all(24),
                  decoration: BoxDecoration(
                    color: AppColors.surface,
                    borderRadius: BorderRadius.circular(12),
                    border: Border.all(color: AppColors.border),
                  ),
                  child: const Center(
                    child: Text('Belum ada riwayat pengajuan klaim.', style: TextStyle(color: AppColors.textMuted)),
                  ),
                )
              else
                ListView.separated(
                  shrinkWrap: true,
                  physics: const NeverScrollableScrollPhysics(),
                  itemCount: _claimHistory.length,
                  separatorBuilder: (_, __) => const SizedBox(height: 10),
                  itemBuilder: (context, i) {
                    final c = _claimHistory[i];
                    Color statusColor = const Color(0xFFD97706);
                    String statusLabel = 'Menunggu Approval';

                    if (c.statusClaim == 'disetujui') {
                      statusColor = const Color(0xFF2563EB);
                      statusLabel = 'Disetujui Admin';
                    } else if (c.statusClaim == 'cair') {
                      statusColor = const Color(0xFF059669);
                      statusLabel = 'Sudah Cair';
                    } else if (c.statusClaim == 'ditolak') {
                      statusColor = const Color(0xFFDC2626);
                      statusLabel = 'Ditolak';
                    }

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
                              Text(c.kodeClaim, style: S.bodySm().copyWith(fontWeight: FontWeight.w800)),
                              Container(
                                padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                                decoration: BoxDecoration(
                                  color: statusColor.withOpacity(0.1),
                                  borderRadius: BorderRadius.circular(6),
                                  border: Border.all(color: statusColor.withOpacity(0.3)),
                                ),
                                child: Text(statusLabel, style: TextStyle(color: statusColor, fontSize: 11, fontWeight: FontWeight.w700)),
                              ),
                            ],
                          ),
                          if (c.namaToko != null && c.namaToko!.isNotEmpty) ...[
                            const SizedBox(height: 4),
                            Row(
                              children: [
                                const Icon(Icons.store_rounded, size: 14, color: AppColors.primary),
                                const SizedBox(width: 4),
                                Expanded(
                                  child: Text(
                                    c.namaToko!,
                                    style: const TextStyle(fontWeight: FontWeight.w700, fontSize: 13, color: AppColors.textPrimary),
                                    maxLines: 1,
                                    overflow: TextOverflow.ellipsis,
                                  ),
                                ),
                              ],
                            ),
                          ],
                          const SizedBox(height: 6),
                          Row(
                            mainAxisAlignment: MainAxisAlignment.spaceBetween,
                            children: [
                              Text('Tgl: ${c.tglClaim} • ${c.totalUnitTerjual} Unit', style: S.caption(AppColors.textMuted)),
                              Text(curFormat.format(c.totalNominalInsentif), style: S.bodySm().copyWith(fontWeight: FontWeight.w800, color: const Color(0xFF059669))),
                            ],
                          ),
                          if (c.catatanAdmin != null && c.catatanAdmin!.isNotEmpty) ...[
                            const SizedBox(height: 6),
                            Text('Catatan Admin: ${c.catatanAdmin}', style: S.caption(AppColors.textSecondary).copyWith(fontStyle: FontStyle.italic)),
                          ],
                        ],
                      ),
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
}
