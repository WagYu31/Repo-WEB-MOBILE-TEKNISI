import 'package:flutter/material.dart';
import 'package:intl/intl.dart';
import 'package:quickalert/quickalert.dart';
import '../../core/app_theme.dart';
import '../../service/api/ApiTipTok.dart';
import '../../service/model/TipTokModel.dart';

class AuditKunjunganPage extends StatefulWidget {
  final TipTokPenitipanModel penitipan;
  final int salesId;
  final String namaSales;

  const AuditKunjunganPage({
    super.key,
    required this.penitipan,
    required this.salesId,
    required this.namaSales,
  });

  @override
  State<AuditKunjunganPage> createState() => _AuditKunjunganPageState();
}

class _AuditItemRow {
  final TipTokItemModel item;
  late TextEditingController sisaCtrl;
  late TextEditingController noInvCtrl;
  int calculatedTerjual = 0;
  double calculatedInsentif = 0.0;

  _AuditItemRow({required this.item}) {
    sisaCtrl = TextEditingController(text: item.qtySisa.toString());
    noInvCtrl = TextEditingController();
  }

  void updateTerjual() {
    final sisa = int.tryParse(sisaCtrl.text.trim()) ?? item.qtySisa;
    calculatedTerjual = (item.qtySisa - sisa).clamp(0, item.qtySisa);
    calculatedInsentif = calculatedTerjual * item.insentifPerUnit;
  }
}

class _AuditKunjunganPageState extends State<AuditKunjunganPage> {
  final _api = ApiTipTok();
  DateTime _tglKunjungan = DateTime.now();
  final _catatanCtrl = TextEditingController();
  bool _isLoading = false;

  late List<_AuditItemRow> _itemRows;

  @override
  void initState() {
    super.initState();
    _itemRows = widget.penitipan.items.map((it) => _AuditItemRow(item: it)).toList();
  }

  @override
  void dispose() {
    _catatanCtrl.dispose();
    for (var r in _itemRows) {
      r.sisaCtrl.dispose();
      r.noInvCtrl.dispose();
    }
    super.dispose();
  }

  int get _totalTerjualAll => _itemRows.fold(0, (sum, r) => sum + r.calculatedTerjual);
  double get _totalInsentifAll => _itemRows.fold(0.0, (sum, r) => sum + r.calculatedInsentif);

  Future<void> _submit() async {
    List<Map<String, dynamic>> itemsPayload = [];

    for (int i = 0; i < _itemRows.length; i++) {
      final r = _itemRows[i];
      final sisa = int.tryParse(r.sisaCtrl.text.trim());
      final noInv = r.noInvCtrl.text.trim();

      if (sisa == null || sisa < 0) {
        QuickAlert.show(
          context: context,
          type: QuickAlertType.warning,
          title: 'Stok Sisa Tidak Valid',
          text: 'Stok sisa untuk "${r.item.namaBarang}" tidak boleh kosong atau negatif!',
          confirmBtnColor: AppColors.warning,
        );
        return;
      }
      if (sisa > r.item.qtySisa) {
        QuickAlert.show(
          context: context,
          type: QuickAlertType.warning,
          title: 'Stok Melebihi Batas',
          text: 'Stok sisa ($sisa) tidak boleh lebih banyak dari stok sebelumnya (${r.item.qtySisa})!',
          confirmBtnColor: AppColors.warning,
        );
        return;
      }

      final terjual = r.item.qtySisa - sisa;
      if (terjual > 0 && noInv.isEmpty) {
        QuickAlert.show(
          context: context,
          type: QuickAlertType.warning,
          title: 'Nomor Invoice Wajib Diisi!',
          text: 'Ada $terjual unit "${r.item.namaBarang}" terjual. Silakan masukkan Nomor Invoice (No. INV) untuk barang ini.',
          confirmBtnColor: AppColors.warning,
        );
        return;
      }

      itemsPayload.add({
        'id_item': r.item.id,
        'stok_sisa': sisa,
        'no_inv': noInv,
        'tgl_invoice': DateFormat('yyyy-MM-dd').format(_tglKunjungan),
      });
    }

    setState(() => _isLoading = true);
    try {
      final res = await _api.auditKunjungan(
        idPenitipan: widget.penitipan.id,
        idSales: widget.salesId,
        namaSales: widget.namaSales,
        tglKunjungan: DateFormat('yyyy-MM-dd').format(_tglKunjungan),
        catatan: _catatanCtrl.text.trim(),
        items: itemsPayload,
      );

      if (!mounted) return;
      setState(() => _isLoading = false);

      QuickAlert.show(
        context: context,
        type: QuickAlertType.success,
        title: 'Audit Kunjungan Berhasil!',
        text: res['message'] ?? 'Laporan sisa stok dan penjualan berhasil disimpan.',
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
        title: 'Gagal Menyimpan',
        text: e.toString().replaceAll('Exception: ', ''),
        confirmBtnColor: AppColors.error,
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    final curFormat = NumberFormat.currency(locale: 'id_ID', symbol: 'Rp ', decimalDigits: 0);

    return Scaffold(
      backgroundColor: AppColors.bg,
      appBar: AppBar(
        title: const Text('Audit Sisa Stok & Penjualan', style: TextStyle(fontWeight: FontWeight.w700, fontSize: 16)),
        backgroundColor: AppColors.surface,
        elevation: 0.5,
        foregroundColor: AppColors.textPrimary,
      ),
      body: _isLoading
          ? const Center(child: CircularProgressIndicator(color: AppColors.primary))
          : SingleChildScrollView(
              padding: const EdgeInsets.all(16),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  // Store Info Header Card
                  Container(
                    width: double.infinity,
                    padding: const EdgeInsets.all(16),
                    decoration: BoxDecoration(
                      color: AppColors.surface,
                      borderRadius: BorderRadius.circular(14),
                      border: Border.all(color: AppColors.border),
                    ),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Row(
                          children: [
                            Container(
                              padding: const EdgeInsets.all(8),
                              decoration: BoxDecoration(
                                color: const Color(0xFFFEF3C7),
                                borderRadius: BorderRadius.circular(8),
                              ),
                              child: const Icon(Icons.store_rounded, color: Color(0xFFD97706), size: 20),
                            ),
                            const SizedBox(width: 12),
                            Expanded(
                              child: Column(
                                crossAxisAlignment: CrossAxisAlignment.start,
                                children: [
                                  Text(widget.penitipan.namaToko, style: S.body().copyWith(fontWeight: FontWeight.w800)),
                                  Text(
                                    "${widget.penitipan.alamatToko} • ${widget.penitipan.kodeTitip}",
                                    style: S.caption(),
                                    maxLines: 1,
                                    overflow: TextOverflow.ellipsis,
                                  ),
                                ],
                              ),
                            ),
                          ],
                        ),
                        const Divider(height: 20, color: AppColors.border),
                        Row(
                          mainAxisAlignment: MainAxisAlignment.spaceBetween,
                          children: [
                            Row(
                              children: [
                                const Icon(Icons.calendar_today_outlined, size: 14, color: AppColors.textMuted),
                                const SizedBox(width: 6),
                                Text('Tgl Audit:', style: S.caption()),
                                const SizedBox(width: 4),
                                Text(DateFormat('dd MMM yyyy', 'id').format(_tglKunjungan), style: S.caption().copyWith(fontWeight: FontWeight.w700)),
                              ],
                            ),
                            Text('Total ${_itemRows.length} Jenis Barang', style: S.caption(AppColors.primary).copyWith(fontWeight: FontWeight.w700)),
                          ],
                        ),
                      ],
                    ),
                  ),

                  const SizedBox(height: 18),
                  Text('Audit Fisik Sisa Barang di Rak Toko', style: S.h3()),
                  const SizedBox(height: 8),

                  ListView.builder(
                    shrinkWrap: true,
                    physics: const NeverScrollableScrollPhysics(),
                    itemCount: _itemRows.length,
                    itemBuilder: (context, i) {
                      final row = _itemRows[i];
                      final isTerjual = row.calculatedTerjual > 0;

                      return Container(
                        margin: const EdgeInsets.only(bottom: 12),
                        padding: const EdgeInsets.all(14),
                        decoration: BoxDecoration(
                          color: AppColors.surface,
                          borderRadius: BorderRadius.circular(12),
                          border: Border.all(
                            color: isTerjual ? const Color(0xFF10B981) : AppColors.border,
                            width: isTerjual ? 1.5 : 1,
                          ),
                        ),
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Row(
                              mainAxisAlignment: MainAxisAlignment.spaceBetween,
                              children: [
                                Expanded(
                                  child: Text(
                                    row.item.namaBarang,
                                    style: S.bodySm().copyWith(fontWeight: FontWeight.w700),
                                  ),
                                ),
                                Container(
                                  padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                                  decoration: BoxDecoration(
                                    color: const Color(0xFFF1F5F9),
                                    borderRadius: BorderRadius.circular(6),
                                  ),
                                  child: Text('Stok Lalu: ${row.item.qtySisa} unit', style: S.caption().copyWith(fontWeight: FontWeight.w600)),
                                ),
                              ],
                            ),
                            const SizedBox(height: 4),
                            Text(
                              'Insentif: ${curFormat.format(row.item.insentifPerUnit)} / unit',
                              style: S.caption(AppColors.textMuted),
                            ),
                            const SizedBox(height: 12),

                            Row(
                              children: [
                                Expanded(
                                  flex: 2,
                                  child: TextField(
                                    controller: row.sisaCtrl,
                                    keyboardType: TextInputType.number,
                                    decoration: InputDecoration(
                                      labelText: 'Stok Sisa Fisik *',
                                      border: const OutlineInputBorder(),
                                      isDense: true,
                                      helperText: 'Unit tersisa di toko',
                                      helperStyle: S.caption(AppColors.textMuted),
                                    ),
                                    onChanged: (_) {
                                      setState(() {
                                        row.updateTerjual();
                                      });
                                    },
                                  ),
                                ),
                                const SizedBox(width: 12),
                                Expanded(
                                  flex: 2,
                                  child: Container(
                                    padding: const EdgeInsets.symmetric(vertical: 10, horizontal: 12),
                                    decoration: BoxDecoration(
                                      color: isTerjual ? const Color(0xFFECFDF5) : const Color(0xFFF8FAFC),
                                      borderRadius: BorderRadius.circular(8),
                                      border: Border.all(color: isTerjual ? const Color(0xFFA7F3D0) : AppColors.border),
                                    ),
                                    child: Column(
                                      crossAxisAlignment: CrossAxisAlignment.start,
                                      children: [
                                        Text('Terjual:', style: S.caption(isTerjual ? const Color(0xFF059669) : AppColors.textMuted)),
                                        Text(
                                          "${row.calculatedTerjual} Unit",
                                          style: TextStyle(
                                            fontWeight: FontWeight.w800,
                                            fontSize: 14,
                                            color: isTerjual ? const Color(0xFF059669) : AppColors.textPrimary,
                                          ),
                                        ),
                                        if (isTerjual)
                                          Text('+ ${curFormat.format(row.calculatedInsentif)}', style: S.caption(const Color(0xFF059669))),
                                      ],
                                    ),
                                  ),
                                ),
                              ],
                            ),

                            // Dynamic Invoice Field
                            if (isTerjual) ...[
                              const SizedBox(height: 12),
                              Container(
                                padding: const EdgeInsets.all(10),
                                decoration: BoxDecoration(
                                  color: const Color(0xFFFFFBEB),
                                  borderRadius: BorderRadius.circular(8),
                                  border: Border.all(color: const Color(0xFFFDE68A)),
                                ),
                                child: Column(
                                  crossAxisAlignment: CrossAxisAlignment.start,
                                  children: [
                                    Row(
                                      children: [
                                        const Icon(Icons.receipt_long_rounded, size: 16, color: Color(0xFFD97706)),
                                        const SizedBox(width: 6),
                                        Text('Wajib Nomor Invoice Penjualan *', style: S.caption(const Color(0xFFB45309)).copyWith(fontWeight: FontWeight.w700)),
                                      ],
                                    ),
                                    const SizedBox(height: 6),
                                    TextField(
                                      controller: row.noInvCtrl,
                                      decoration: const InputDecoration(
                                        hintText: 'Cth: INV/2026/09/0012',
                                        filled: true,
                                        fillColor: Colors.white,
                                        border: OutlineInputBorder(),
                                        isDense: true,
                                      ),
                                    ),
                                  ],
                                ),
                              ),
                            ],
                          ],
                        ),
                      );
                    },
                  ),

                  const SizedBox(height: 10),

                  // Catatan Kunjungan
                  TextField(
                    controller: _catatanCtrl,
                    maxLines: 2,
                    decoration: const InputDecoration(
                      labelText: 'Catatan Kunjungan / Audit (Opsional)',
                      hintText: 'Keterangan kondisi toko atau pembayaran customer...',
                      border: OutlineInputBorder(),
                    ),
                  ),

                  const SizedBox(height: 20),

                  // Summary Bar
                  if (_totalTerjualAll > 0)
                    Container(
                      margin: const EdgeInsets.only(bottom: 16),
                      padding: const EdgeInsets.all(14),
                      decoration: BoxDecoration(
                        color: const Color(0xFF0F172A),
                        borderRadius: BorderRadius.circular(12),
                      ),
                      child: Row(
                        mainAxisAlignment: MainAxisAlignment.spaceBetween,
                        children: [
                          Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Text('Total Terjual Hari Ini', style: S.caption(const Color(0xFF94A3B8))),
                              Text('$_totalTerjualAll Unit', style: const TextStyle(color: Colors.white, fontWeight: FontWeight.w800, fontSize: 16)),
                            ],
                          ),
                          Column(
                            crossAxisAlignment: CrossAxisAlignment.end,
                            children: [
                              Text('Tambahan Insentif', style: S.caption(const Color(0xFF94A3B8))),
                              Text(curFormat.format(_totalInsentifAll), style: const TextStyle(color: Color(0xFF34D399), fontWeight: FontWeight.w800, fontSize: 16)),
                            ],
                          ),
                        ],
                      ),
                    ),

                  // Submit Button
                  SizedBox(
                    width: double.infinity,
                    height: 50,
                    child: ElevatedButton.icon(
                      onPressed: _submit,
                      icon: const Icon(Icons.check_circle_outline_rounded),
                      label: const Text('Simpan Hasil Audit', style: TextStyle(fontWeight: FontWeight.w700, fontSize: 15)),
                      style: ElevatedButton.styleFrom(
                        backgroundColor: const Color(0xFF059669),
                        foregroundColor: Colors.white,
                        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                      ),
                    ),
                  ),
                  const SizedBox(height: 32),
                ],
              ),
            ),
    );
  }
}
