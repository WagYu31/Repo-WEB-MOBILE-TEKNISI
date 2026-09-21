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

  const ClaimInsentifPage({
    super.key,
    required this.salesId,
    required this.namaSales,
    required this.metrics,
  });

  @override
  State<ClaimInsentifPage> createState() => _ClaimInsentifPageState();
}

class _ClaimInsentifPageState extends State<ClaimInsentifPage> {
  final _api = ApiTipTok();
  final _catatanCtrl = TextEditingController();
  bool _isLoading = false;
  List<TipTokClaimModel> _claimHistory = [];
  bool _isLoadingHistory = true;

  @override
  void initState() {
    super.initState();
    _loadHistory();
  }

  @override
  void dispose() {
    _catatanCtrl.dispose();
    super.dispose();
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

  Future<void> _submitClaim() async {
    if (widget.metrics.unclaimedUnits < 50) {
      QuickAlert.show(
        context: context,
        type: QuickAlertType.warning,
        title: 'Belum Mencapai Target',
        text: 'Minimal 50 unit terjual untuk mengajukan klaim insentif. Saat ini baru ${widget.metrics.unclaimedUnits} unit.',
        confirmBtnColor: AppColors.warning,
      );
      return;
    }

    setState(() => _isLoading = true);
    try {
      final res = await _api.claimInsentif(
        salesId: widget.salesId,
        namaSales: widget.namaSales,
        catatanClaim: _catatanCtrl.text.trim(),
      );

      if (!mounted) return;
      setState(() => _isLoading = false);

      QuickAlert.show(
        context: context,
        type: QuickAlertType.success,
        title: 'Klaim Berhasil Diajukan!',
        text: res['message'] ?? 'Pengajuan klaim insentif berhasil diajukan ke Admin.',
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
    final isEligible = widget.metrics.unclaimedUnits >= 50;

    return Scaffold(
      backgroundColor: AppColors.bg,
      appBar: AppBar(
        title: const Text('Klaim Insentif TIP TOK', style: TextStyle(fontWeight: FontWeight.w700, fontSize: 16)),
        backgroundColor: AppColors.surface,
        elevation: 0.5,
        foregroundColor: AppColors.textPrimary,
      ),
      body: SingleChildScrollView(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            // Hero Claim Status Card
            Container(
              width: double.infinity,
              padding: const EdgeInsets.all(20),
              decoration: BoxDecoration(
                color: const Color(0xFF0F172A),
                borderRadius: BorderRadius.circular(16),
                boxShadow: [
                  BoxShadow(
                    color: Colors.black.withOpacity(0.08),
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
                      Text('SALDO INSENTIF BELUM KLAIM', style: S.caption(const Color(0xFF94A3B8)).copyWith(letterSpacing: 0.8, fontWeight: FontWeight.w700)),
                      Container(
                        padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                        decoration: BoxDecoration(
                          color: isEligible ? const Color(0xFF059669) : const Color(0xFF334155),
                          borderRadius: BorderRadius.circular(20),
                        ),
                        child: Text(
                          isEligible ? 'SIAP KLAIM' : 'BELUM CAPAI TARGET',
                          style: const TextStyle(color: Colors.white, fontSize: 10, fontWeight: FontWeight.w800),
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(height: 10),
                  Text(
                    curFormat.format(widget.metrics.unclaimedNominal),
                    style: const TextStyle(
                      color: Color(0xFF34D399),
                      fontWeight: FontWeight.w800,
                      fontSize: 26,
                      letterSpacing: -0.5,
                    ),
                  ),
                  const SizedBox(height: 14),

                  // Progress Bar
                  Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      Text('Unit Terjual: ${widget.metrics.unclaimedUnits} / 50 Unit', style: const TextStyle(color: Colors.white70, fontSize: 12)),
                      Text('${widget.metrics.claimProgress}%', style: const TextStyle(color: Colors.white70, fontSize: 12, fontWeight: FontWeight.w700)),
                    ],
                  ),
                  const SizedBox(height: 6),
                  ClipRRect(
                    borderRadius: BorderRadius.circular(4),
                    child: LinearProgressIndicator(
                      value: (widget.metrics.unclaimedUnits / 50).clamp(0.0, 1.0),
                      backgroundColor: const Color(0xFF334155),
                      valueColor: AlwaysStoppedAnimation<Color>(isEligible ? const Color(0xFF10B981) : const Color(0xFFF59E0B)),
                      minHeight: 6,
                    ),
                  ),

                  if (!isEligible) ...[
                    const SizedBox(height: 8),
                    Text(
                      'Kurang ${50 - widget.metrics.unclaimedUnits} unit lagi untuk dapat mengajukan pencairan insentif.',
                      style: S.caption(const Color(0xFF94A3B8)),
                    ),
                  ],
                ],
              ),
            ),

            const SizedBox(height: 20),

            // Form Pengajuan
            if (isEligible) ...[
              Text('Form Pengajuan Klaim', style: S.h3()),
              const SizedBox(height: 8),
              Container(
                padding: const EdgeInsets.all(16),
                decoration: BoxDecoration(
                  color: AppColors.surface,
                  borderRadius: BorderRadius.circular(14),
                  border: Border.all(color: AppColors.border),
                ),
                child: Column(
                  children: [
                    TextField(
                      controller: _catatanCtrl,
                      maxLines: 2,
                      decoration: const InputDecoration(
                        labelText: 'Catatan Pengajuan (Opsional)',
                        hintText: 'Cth: Klaim periode September 2026',
                        border: OutlineInputBorder(),
                      ),
                    ),
                    const SizedBox(height: 14),
                    SizedBox(
                      width: double.infinity,
                      height: 48,
                      child: ElevatedButton.icon(
                        onPressed: _isLoading ? null : _submitClaim,
                        icon: const Icon(Icons.send_rounded),
                        label: Text(_isLoading ? 'Mengajukan...' : 'Ajukan Klaim Sekarang', style: const TextStyle(fontWeight: FontWeight.w700)),
                        style: ElevatedButton.styleFrom(
                          backgroundColor: const Color(0xFF059669),
                          foregroundColor: Colors.white,
                          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                        ),
                      ),
                    ),
                  ],
                ),
              ),
              const SizedBox(height: 24),
            ],

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
    );
  }
}
