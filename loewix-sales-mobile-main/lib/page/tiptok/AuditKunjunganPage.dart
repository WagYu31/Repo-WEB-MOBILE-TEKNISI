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

  void stepSisa(int delta) {
    int cur = int.tryParse(sisaCtrl.text.trim()) ?? item.qtySisa;
    int next = (cur + delta).clamp(0, item.qtySisa);
    sisaCtrl.text = next.toString();
    sisaCtrl.selection = TextSelection.fromPosition(TextPosition(offset: sisaCtrl.text.length));
    updateTerjual();
  }

  void setSisa(int val) {
    int next = val.clamp(0, item.qtySisa);
    sisaCtrl.text = next.toString();
    sisaCtrl.selection = TextSelection.fromPosition(TextPosition(offset: sisaCtrl.text.length));
    updateTerjual();
  }
}

class _AuditKunjunganPageState extends State<AuditKunjunganPage> {
  final _api = ApiTipTok();
  final DateTime _tglKunjungan = DateTime.now();
  final _catatanCtrl = TextEditingController();
  bool _isLoading = false;
  bool _showPanduan = true;

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

  void _setSemuaUtuh() {
    setState(() {
      for (var r in _itemRows) {
        r.setSisa(r.item.qtySisa);
      }
    });
  }

  void _setSemuaHabis() {
    setState(() {
      for (var r in _itemRows) {
        r.setSisa(0);
      }
    });
  }

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

  Widget _buildPanduanItem({
    required String step,
    required String title,
    required String desc,
    required IconData icon,
    required Color color,
  }) {
    return Row(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Container(
          width: 22,
          height: 22,
          alignment: Alignment.center,
          decoration: BoxDecoration(
            color: color.withAlpha(30),
            borderRadius: BorderRadius.circular(6),
          ),
          child: Text(
            step,
            style: TextStyle(
              fontSize: 11,
              fontWeight: FontWeight.w800,
              color: color,
            ),
          ),
        ),
        const SizedBox(width: 10),
        Expanded(
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(
                title,
                style: S.bodySm().copyWith(fontWeight: FontWeight.w700, color: AppColors.textPrimary),
              ),
              const SizedBox(height: 1.5),
              Text(
                desc,
                style: S.caption(AppColors.textSecondary).copyWith(height: 1.35),
              ),
            ],
          ),
        ),
      ],
    );
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

                  const SizedBox(height: 16),

                  // Panduan Cara Audit Sisa Fisik (Penjelasan biar sales tidak bingung)
                  Container(
                    margin: const EdgeInsets.only(bottom: 16),
                    decoration: BoxDecoration(
                      color: const Color(0xFFF0FDF4),
                      borderRadius: BorderRadius.circular(14),
                      border: Border.all(color: const Color(0xFFBBF7D0)),
                    ),
                    child: Column(
                      children: [
                        InkWell(
                          borderRadius: BorderRadius.circular(14),
                          onTap: () {
                            setState(() {
                              _showPanduan = !_showPanduan;
                            });
                          },
                          child: Padding(
                            padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
                            child: Row(
                              children: [
                                Container(
                                  padding: const EdgeInsets.all(6),
                                  decoration: BoxDecoration(
                                    color: const Color(0xFF16A34A),
                                    borderRadius: BorderRadius.circular(8),
                                  ),
                                  child: const Icon(Icons.lightbulb_outline_rounded, color: Colors.white, size: 16),
                                ),
                                const SizedBox(width: 10),
                                Expanded(
                                  child: Column(
                                    crossAxisAlignment: CrossAxisAlignment.start,
                                    children: [
                                      Text(
                                        'Cara & Panduan Audit Sisa Fisik',
                                        style: S.bodySm().copyWith(fontWeight: FontWeight.w800, color: const Color(0xFF15803D)),
                                      ),
                                      Text(
                                        _showPanduan ? 'Ketuk untuk menutup panduan' : 'Ketuk untuk melihat cara praktis audit',
                                        style: S.caption(const Color(0xFF166534)),
                                      ),
                                    ],
                                  ),
                                ),
                                Icon(
                                  _showPanduan ? Icons.keyboard_arrow_up_rounded : Icons.keyboard_arrow_down_rounded,
                                  color: const Color(0xFF15803D),
                                ),
                              ],
                            ),
                          ),
                        ),
                        if (_showPanduan) ...[
                          const Divider(height: 1, color: Color(0xFFBBF7D0)),
                          Padding(
                            padding: const EdgeInsets.all(14),
                            child: Column(
                              children: [
                                _buildPanduanItem(
                                  step: '1',
                                  title: 'Cek Sisa Fisik di Rak Toko',
                                  desc: 'Hitung berapa unit kamera yang masih terpajang/tersedia di toko saat audit.',
                                  icon: Icons.search_rounded,
                                  color: const Color(0xFF2563EB),
                                ),
                                const SizedBox(height: 10),
                                _buildPanduanItem(
                                  step: '2',
                                  title: 'Gunakan Tombol [−] atau [+]',
                                  desc: 'Tekan [−] jika ada barang laku terjual, atau [+] jika keliru. Anda juga bisa ketik angka langsung.',
                                  icon: Icons.add_circle_outline_rounded,
                                  color: const Color(0xFFD97706),
                                ),
                                const SizedBox(height: 10),
                                _buildPanduanItem(
                                  step: '3',
                                  title: 'Terjual & Insentif Otomatis',
                                  desc: 'Rumus: (Stok Lalu − Sisa Fisik = Terjual). Estimasi reward insentif langsung terhitung otomatis.',
                                  icon: Icons.calculate_outlined,
                                  color: const Color(0xFF059669),
                                ),
                                const SizedBox(height: 10),
                                _buildPanduanItem(
                                  step: '4',
                                  title: 'Wajib Isi No. Invoice Jika Terjual',
                                  desc: 'Jika ada barang yang laku, kolom Nomor Invoice akan otomatis muncul dan wajib diisi.',
                                  icon: Icons.receipt_long_rounded,
                                  color: const Color(0xFF7C3AED),
                                ),
                              ],
                            ),
                          ),
                        ],
                      ],
                    ),
                  ),

                  // Section Title & Quick Fill Buttons
                  Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      Text('Audit Fisik Sisa Barang', style: S.h3()),
                      Row(
                        children: [
                          InkWell(
                            borderRadius: BorderRadius.circular(8),
                            onTap: _setSemuaUtuh,
                            child: Container(
                              padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 5),
                              decoration: BoxDecoration(
                                color: const Color(0xFFF1F5F9),
                                borderRadius: BorderRadius.circular(8),
                                border: Border.all(color: const Color(0xFFCBD5E1)),
                              ),
                              child: Row(
                                children: [
                                  const Icon(Icons.shield_outlined, size: 13, color: AppColors.primary),
                                  const SizedBox(width: 4),
                                  Text(
                                    'Semua Utuh',
                                    style: S.caption(AppColors.textPrimary).copyWith(fontWeight: FontWeight.w700, fontSize: 11),
                                  ),
                                ],
                              ),
                            ),
                          ),
                          const SizedBox(width: 6),
                          InkWell(
                            borderRadius: BorderRadius.circular(8),
                            onTap: _setSemuaHabis,
                            child: Container(
                              padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 5),
                              decoration: BoxDecoration(
                                color: const Color(0xFFFEF2F2),
                                borderRadius: BorderRadius.circular(8),
                                border: Border.all(color: const Color(0xFFFECACA)),
                              ),
                              child: Row(
                                children: [
                                  const Icon(Icons.local_fire_department_outlined, size: 13, color: Colors.red),
                                  const SizedBox(width: 4),
                                  Text(
                                    'Semua Habis',
                                    style: S.caption(Colors.red).copyWith(fontWeight: FontWeight.w700, fontSize: 11),
                                  ),
                                ],
                              ),
                            ),
                          ),
                        ],
                      ),
                    ],
                  ),
                  const SizedBox(height: 10),

                  ListView.builder(
                    shrinkWrap: true,
                    physics: const NeverScrollableScrollPhysics(),
                    itemCount: _itemRows.length,
                    itemBuilder: (context, i) {
                      final row = _itemRows[i];
                      final isTerjual = row.calculatedTerjual > 0;
                      final sisaInt = int.tryParse(row.sisaCtrl.text.trim()) ?? row.item.qtySisa;

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
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                // Stepper Stok Sisa Fisik (+ / -)
                                Expanded(
                                  flex: 3,
                                  child: Column(
                                    crossAxisAlignment: CrossAxisAlignment.start,
                                    children: [
                                      Row(
                                        children: [
                                          Text(
                                            'Stok Sisa Fisik',
                                            style: S.caption().copyWith(fontWeight: FontWeight.w700, color: AppColors.textPrimary),
                                          ),
                                          const Text(' *', style: TextStyle(color: Colors.red, fontWeight: FontWeight.bold)),
                                        ],
                                      ),
                                      const SizedBox(height: 6),
                                      Container(
                                        height: 44,
                                        decoration: BoxDecoration(
                                          color: Colors.white,
                                          borderRadius: BorderRadius.circular(10),
                                          border: Border.all(color: const Color(0xFFCBD5E1), width: 1.2),
                                        ),
                                        child: Row(
                                          children: [
                                            // Tombol Minus (-)
                                            Material(
                                              color: Colors.transparent,
                                              child: InkWell(
                                                borderRadius: const BorderRadius.horizontal(left: Radius.circular(9)),
                                                onTap: sisaInt > 0
                                                    ? () {
                                                        setState(() {
                                                          row.stepSisa(-1);
                                                        });
                                                      }
                                                    : null,
                                                child: Container(
                                                  width: 38,
                                                  height: double.infinity,
                                                  decoration: BoxDecoration(
                                                    color: sisaInt > 0
                                                        ? const Color(0xFFF1F5F9)
                                                        : const Color(0xFFF8FAFC),
                                                    borderRadius: const BorderRadius.horizontal(left: Radius.circular(9)),
                                                  ),
                                                  alignment: Alignment.center,
                                                  child: Icon(
                                                    Icons.remove_rounded,
                                                    size: 20,
                                                    color: sisaInt > 0
                                                        ? AppColors.textPrimary
                                                        : const Color(0xFF94A3B8),
                                                  ),
                                                ),
                                              ),
                                            ),
                                            // Angka Input (bisa diketik manual)
                                            Expanded(
                                              child: TextField(
                                                controller: row.sisaCtrl,
                                                keyboardType: TextInputType.number,
                                                textAlign: TextAlign.center,
                                                style: const TextStyle(
                                                  fontWeight: FontWeight.w800,
                                                  fontSize: 16,
                                                  color: AppColors.textPrimary,
                                                ),
                                                decoration: const InputDecoration(
                                                  border: InputBorder.none,
                                                  enabledBorder: InputBorder.none,
                                                  focusedBorder: InputBorder.none,
                                                  contentPadding: EdgeInsets.zero,
                                                  isDense: true,
                                                ),
                                                onChanged: (_) {
                                                  setState(() {
                                                    row.updateTerjual();
                                                  });
                                                },
                                              ),
                                            ),
                                            // Tombol Plus (+)
                                            Material(
                                              color: Colors.transparent,
                                              child: InkWell(
                                                borderRadius: const BorderRadius.horizontal(right: Radius.circular(9)),
                                                onTap: sisaInt < row.item.qtySisa
                                                    ? () {
                                                        setState(() {
                                                          row.stepSisa(1);
                                                        });
                                                      }
                                                    : null,
                                                child: Container(
                                                  width: 38,
                                                  height: double.infinity,
                                                  decoration: BoxDecoration(
                                                    color: sisaInt < row.item.qtySisa
                                                        ? const Color(0xFFF1F5F9)
                                                        : const Color(0xFFF8FAFC),
                                                    borderRadius: const BorderRadius.horizontal(right: Radius.circular(9)),
                                                  ),
                                                  alignment: Alignment.center,
                                                  child: Icon(
                                                    Icons.add_rounded,
                                                    size: 20,
                                                    color: sisaInt < row.item.qtySisa
                                                        ? AppColors.textPrimary
                                                        : const Color(0xFF94A3B8),
                                                  ),
                                                ),
                                              ),
                                            ),
                                          ],
                                        ),
                                      ),
                                      const SizedBox(height: 4),
                                      Text(
                                        'Unit tersisa di toko',
                                        style: S.caption(AppColors.textMuted),
                                      ),
                                    ],
                                  ),
                                ),
                                const SizedBox(width: 12),
                                // Hasil Terjual Card
                                Expanded(
                                  flex: 2,
                                  child: Column(
                                    crossAxisAlignment: CrossAxisAlignment.start,
                                    children: [
                                      Text(
                                        'Hasil Penjualan',
                                        style: S.caption().copyWith(fontWeight: FontWeight.w700, color: AppColors.textPrimary),
                                      ),
                                      const SizedBox(height: 6),
                                      Container(
                                        height: 44,
                                        padding: const EdgeInsets.symmetric(horizontal: 10),
                                        decoration: BoxDecoration(
                                          color: isTerjual ? const Color(0xFFECFDF5) : const Color(0xFFF8FAFC),
                                          borderRadius: BorderRadius.circular(10),
                                          border: Border.all(
                                            color: isTerjual ? const Color(0xFFA7F3D0) : const Color(0xFFCBD5E1),
                                            width: isTerjual ? 1.4 : 1.0,
                                          ),
                                        ),
                                        child: Row(
                                          mainAxisAlignment: MainAxisAlignment.spaceBetween,
                                          children: [
                                            Column(
                                              crossAxisAlignment: CrossAxisAlignment.start,
                                              mainAxisAlignment: MainAxisAlignment.center,
                                              children: [
                                                Text(
                                                  'Terjual:',
                                                  style: S.caption(isTerjual ? const Color(0xFF059669) : AppColors.textMuted).copyWith(fontSize: 10, fontWeight: FontWeight.w600),
                                                ),
                                                Text(
                                                  "${row.calculatedTerjual} Unit",
                                                  style: TextStyle(
                                                    fontWeight: FontWeight.w900,
                                                    fontSize: 14,
                                                    color: isTerjual ? const Color(0xFF059669) : AppColors.textPrimary,
                                                  ),
                                                ),
                                              ],
                                            ),
                                            if (isTerjual)
                                              const Icon(Icons.check_circle_rounded, size: 18, color: Color(0xFF059669)),
                                          ],
                                        ),
                                      ),
                                      const SizedBox(height: 4),
                                      if (isTerjual)
                                        Text(
                                          '+ ${curFormat.format(row.calculatedInsentif)} insentif',
                                          style: S.caption(const Color(0xFF059669)).copyWith(fontWeight: FontWeight.w700),
                                        )
                                      else
                                        Text(
                                          'Belum ada laku',
                                          style: S.caption(AppColors.textMuted),
                                        ),
                                    ],
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
