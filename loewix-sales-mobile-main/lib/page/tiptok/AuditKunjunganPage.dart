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
  late TextEditingController tambahStokCtrl;
  DateTime tglInvoice = DateTime.now();
  DateTime tglRestock = DateTime.now();
  int calculatedTerjual = 0;
  double calculatedInsentif = 0.0;

  _AuditItemRow({required this.item}) {
    sisaCtrl = TextEditingController(text: item.qtySisa.toString());
    noInvCtrl = TextEditingController();
    tambahStokCtrl = TextEditingController(text: '0');
  }

  int get sisaFisik => int.tryParse(sisaCtrl.text.trim()) ?? item.qtySisa;
  int get tambahStok => int.tryParse(tambahStokCtrl.text.trim()) ?? 0;
  int get stokAkhir => sisaFisik + tambahStok;

  void updateTerjual() {
    final sisa = int.tryParse(sisaCtrl.text.trim()) ?? item.qtySisa;
    if (sisa > item.qtySisa) {
      final excess = sisa - item.qtySisa;
      final curTambah = tambahStok;
      tambahStokCtrl.text = (curTambah + excess).toString();
      sisaCtrl.text = item.qtySisa.toString();
      sisaCtrl.selection = TextSelection.fromPosition(TextPosition(offset: sisaCtrl.text.length));
    }
    final actualSisa = int.tryParse(sisaCtrl.text.trim()) ?? item.qtySisa;
    calculatedTerjual = (item.qtySisa - actualSisa).clamp(0, item.qtySisa);
    calculatedInsentif = calculatedTerjual * item.insentifPerUnit;
  }

  void stepSisa(int delta, [BuildContext? context]) {
    int cur = int.tryParse(sisaCtrl.text.trim()) ?? item.qtySisa;
    if (delta > 0 && cur >= item.qtySisa) {
      stepTambah(delta);
      if (context != null) {
        ScaffoldMessenger.of(context).hideCurrentSnackBar();
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(
              'Stok sisa sudah utuh (${item.qtySisa}). Ditambahkan ke Tambah Stok Titip (Restock: +$tambahStok).',
              style: const TextStyle(fontSize: 12),
            ),
            duration: const Duration(seconds: 2),
            behavior: SnackBarBehavior.floating,
            backgroundColor: const Color(0xFF0D9488),
          ),
        );
      }
      return;
    }
    int next = (cur + delta).clamp(0, item.qtySisa);
    sisaCtrl.text = next.toString();
    sisaCtrl.selection = TextSelection.fromPosition(TextPosition(offset: sisaCtrl.text.length));
    updateTerjual();
  }

  void stepTambah(int delta) {
    int cur = int.tryParse(tambahStokCtrl.text.trim()) ?? 0;
    int next = (cur + delta).clamp(0, 999);
    tambahStokCtrl.text = next.toString();
    tambahStokCtrl.selection = TextSelection.fromPosition(TextPosition(offset: tambahStokCtrl.text.length));
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
  DateTime _tglKunjungan = DateTime.now();
  DateTime _masterTglInvoice = DateTime.now();
  final _catatanCtrl = TextEditingController();
  final _masterNoInvCtrl = TextEditingController();
  bool _gabungInvoice = true; // Default: Gabung 1 No. Invoice
  bool _isLoading = false;
  bool _showPanduan = true;

  Future<void> _selectTglKunjungan() async {
    final picked = await showDatePicker(
      context: context,
      initialDate: _tglKunjungan,
      firstDate: DateTime(2023),
      lastDate: DateTime.now().add(const Duration(days: 1)),
      helpText: 'PILIH TANGGAL KUNJUNGAN AUDIT',
      confirmText: 'PILIH',
      cancelText: 'BATAL',
    );
    if (picked != null) {
      setState(() {
        _tglKunjungan = picked;
        _masterTglInvoice = picked;
        for (var r in _itemRows) {
          r.tglInvoice = picked;
          r.tglRestock = picked;
        }
      });
    }
  }

  Future<void> _selectMasterInvoiceDate() async {
    final picked = await showDatePicker(
      context: context,
      initialDate: _masterTglInvoice,
      firstDate: DateTime(2023),
      lastDate: DateTime.now().add(const Duration(days: 1)),
      helpText: 'PILIH TANGGAL TERJUAL / INVOICE',
      confirmText: 'PILIH',
      cancelText: 'BATAL',
    );
    if (picked != null) {
      setState(() {
        _masterTglInvoice = picked;
        for (var r in _itemRows) {
          r.tglInvoice = picked;
        }
      });
    }
  }

  Future<void> _selectRowInvoiceDate(_AuditItemRow row) async {
    final picked = await showDatePicker(
      context: context,
      initialDate: row.tglInvoice,
      firstDate: DateTime(2023),
      lastDate: DateTime.now().add(const Duration(days: 1)),
      helpText: 'PILIH TANGGAL INVOICE BARANG INI',
      confirmText: 'PILIH',
      cancelText: 'BATAL',
    );
    if (picked != null) {
      setState(() {
        row.tglInvoice = picked;
      });
    }
  }

  Future<void> _selectRowRestockDate(_AuditItemRow row) async {
    final picked = await showDatePicker(
      context: context,
      initialDate: row.tglRestock,
      firstDate: DateTime(2023),
      lastDate: DateTime.now().add(const Duration(days: 1)),
      helpText: 'PILIH TANGGAL RESTOCK BARANG',
      confirmText: 'PILIH',
      cancelText: 'BATAL',
    );
    if (picked != null) {
      setState(() {
        row.tglRestock = picked;
      });
    }
  }

  late List<_AuditItemRow> _itemRows;

  @override
  void initState() {
    super.initState();
    _itemRows = widget.penitipan.items.map((it) => _AuditItemRow(item: it)).toList();
  }

  @override
  void dispose() {
    _catatanCtrl.dispose();
    _masterNoInvCtrl.dispose();
    for (var r in _itemRows) {
      r.sisaCtrl.dispose();
      r.noInvCtrl.dispose();
      r.tambahStokCtrl.dispose();
    }
    super.dispose();
  }

  int get _totalTerjualAll => _itemRows.fold(0, (sum, r) => sum + r.calculatedTerjual);
  double get _totalInsentifAll => _itemRows.fold(0.0, (sum, r) => sum + r.calculatedInsentif);
  int get _totalRestockAll => _itemRows.fold(0, (sum, r) => sum + r.tambahStok);

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

  String? _getSuggestedNextInv(String lastInv) {
    if (lastInv.trim().isEmpty) return null;
    final trimmed = lastInv.trim();
    final reg = RegExp(r'^(.*?)(\d+)$');
    final match = reg.firstMatch(trimmed);
    if (match != null) {
      final prefix = match.group(1) ?? '';
      final numStr = match.group(2) ?? '';
      final numVal = int.tryParse(numStr);
      if (numVal != null) {
        final nextNum = (numVal + 1).toString().padLeft(numStr.length, '0');
        return '$prefix$nextNum';
      }
    }
    return null;
  }

  void _toggleModeInvoice(bool gabung) {
    setState(() {
      _gabungInvoice = gabung;
      if (!gabung && _masterNoInvCtrl.text.trim().isNotEmpty) {
        // Pre-fill each sold row with master text if row is empty
        for (var r in _itemRows) {
          if (r.calculatedTerjual > 0) {
            if (r.noInvCtrl.text.trim().isEmpty) {
              r.noInvCtrl.text = _masterNoInvCtrl.text.trim();
            }
            r.tglInvoice = _masterTglInvoice;
          }
        }
      } else if (gabung && _masterNoInvCtrl.text.trim().isEmpty) {
        // Pick first sold row's text if master is empty
        for (var r in _itemRows) {
          if (r.calculatedTerjual > 0 && r.noInvCtrl.text.trim().isNotEmpty) {
            _masterNoInvCtrl.text = r.noInvCtrl.text.trim();
            break;
          }
        }
      }
    });
  }

  Future<void> _submit() async {
    // Validasi No Invoice jika ada penjualan
    if (_totalTerjualAll > 0) {
      if (_gabungInvoice) {
        final masterInv = _masterNoInvCtrl.text.trim();
        if (masterInv.isEmpty) {
          QuickAlert.show(
            context: context,
            type: QuickAlertType.warning,
            title: 'Nomor Invoice Wajib Diisi!',
            text: 'Ada $_totalTerjualAll unit barang yang laku terjual. Silakan masukkan Nomor Invoice Penjualan (berlaku untuk semua barang laku).',
            confirmBtnColor: AppColors.warning,
          );
          return;
        }
        // Sync master invoice to all sold rows
        for (var r in _itemRows) {
          if (r.calculatedTerjual > 0) {
            r.noInvCtrl.text = masterInv;
          } else {
            r.noInvCtrl.text = '';
          }
        }
      } else {
        // Mode Pisah: Validasi masing-masing item terjual
        for (var r in _itemRows) {
          if (r.calculatedTerjual > 0 && r.noInvCtrl.text.trim().isEmpty) {
            QuickAlert.show(
              context: context,
              type: QuickAlertType.warning,
              title: 'Nomor Invoice Belum Lengkap!',
              text: 'Barang "${r.item.namaBarang}" terjual ${r.calculatedTerjual} unit namun belum memiliki Nomor Invoice. Dalam mode pisah, setiap barang laku wajib diisi nomor invoicenya.',
              confirmBtnColor: AppColors.warning,
            );
            return;
          }
        }
      }
    }

    List<Map<String, dynamic>> itemsPayload = [];

    for (int i = 0; i < _itemRows.length; i++) {
      final r = _itemRows[i];
      final sisa = int.tryParse(r.sisaCtrl.text.trim());
      final tambah = int.tryParse(r.tambahStokCtrl.text.trim()) ?? 0;
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
          text: 'Stok sisa ($sisa) tidak boleh lebih banyak dari stok sebelumnya (${r.item.qtySisa})! Silakan gunakan kolom Tambah Stok Titip (Restock).',
          confirmBtnColor: AppColors.warning,
        );
        return;
      }
      if (tambah < 0) {
        QuickAlert.show(
          context: context,
          type: QuickAlertType.warning,
          title: 'Tambah Stok Tidak Valid',
          text: 'Tambah stok untuk "${r.item.namaBarang}" tidak boleh negatif!',
          confirmBtnColor: AppColors.warning,
        );
        return;
      }

      final terjual = r.item.qtySisa - sisa;
      itemsPayload.add({
        'id_item': r.item.id,
        'stok_sisa': sisa,
        'tambah_stok': tambah,
        'no_inv': terjual > 0 ? noInv : '',
        'tgl_invoice': terjual > 0 ? DateFormat('yyyy-MM-dd').format(r.tglInvoice) : null,
        'tgl_restock': tambah > 0 ? DateFormat('yyyy-MM-dd').format(r.tglRestock) : null,
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

  Widget _buildInvoiceSettingsCard() {
    final lastInv = widget.penitipan.lastNoInv;
    final nextSuggestedInv = _getSuggestedNextInv(lastInv);

    return Container(
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(14),
        border: Border.all(
          color: _gabungInvoice ? const Color(0xFF10B981) : const Color(0xFFF59E0B),
          width: 1.5,
        ),
        boxShadow: const [
          BoxShadow(
            color: Color(0x0A000000),
            blurRadius: 8,
            offset: Offset(0, 3),
          ),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          // Header Row
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Row(
                children: [
                  Container(
                    padding: const EdgeInsets.all(6),
                    decoration: BoxDecoration(
                      color: _gabungInvoice ? const Color(0xFFECFDF5) : const Color(0xFFFEF3C7),
                      borderRadius: BorderRadius.circular(8),
                    ),
                    child: Icon(
                      Icons.receipt_long_rounded,
                      size: 18,
                      color: _gabungInvoice ? const Color(0xFF059669) : const Color(0xFFD97706),
                    ),
                  ),
                  const SizedBox(width: 8),
                  Text(
                    'Nomor Invoice Penjualan',
                    style: S.bodySm().copyWith(fontWeight: FontWeight.w800, color: AppColors.textPrimary),
                  ),
                ],
              ),
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                decoration: BoxDecoration(
                  color: const Color(0xFFF1F5F9),
                  borderRadius: BorderRadius.circular(6),
                ),
                child: Text(
                  '$_totalTerjualAll Unit Laku',
                  style: const TextStyle(fontSize: 11, fontWeight: FontWeight.w800, color: Color(0xFF0F172A)),
                ),
              ),
            ],
          ),
          const SizedBox(height: 10),

          // Opsi Segmented Toggle: [Gabung 1 No. Invoice] | [Pisah per Barang]
          Container(
            padding: const EdgeInsets.all(3),
            decoration: BoxDecoration(
              color: const Color(0xFFF1F5F9),
              borderRadius: BorderRadius.circular(10),
            ),
            child: Row(
              children: [
                Expanded(
                  child: InkWell(
                    borderRadius: BorderRadius.circular(8),
                    onTap: () => _toggleModeInvoice(true),
                    child: AnimatedContainer(
                      duration: const Duration(milliseconds: 200),
                      padding: const EdgeInsets.symmetric(vertical: 8),
                      decoration: BoxDecoration(
                        color: _gabungInvoice ? Colors.white : Colors.transparent,
                        borderRadius: BorderRadius.circular(8),
                        boxShadow: _gabungInvoice
                            ? const [BoxShadow(color: Color(0x14000000), blurRadius: 4, offset: Offset(0, 2))]
                            : null,
                      ),
                      alignment: Alignment.center,
                      child: Row(
                        mainAxisAlignment: MainAxisAlignment.center,
                        children: [
                          Icon(
                            _gabungInvoice ? Icons.check_circle_rounded : Icons.radio_button_unchecked_rounded,
                            size: 15,
                            color: _gabungInvoice ? const Color(0xFF059669) : AppColors.textMuted,
                          ),
                          const SizedBox(width: 6),
                          Text(
                            'Gabung 1 Invoice',
                            style: TextStyle(
                              fontSize: 12,
                              fontWeight: _gabungInvoice ? FontWeight.w800 : FontWeight.w600,
                              color: _gabungInvoice ? const Color(0xFF059669) : AppColors.textSecondary,
                            ),
                          ),
                        ],
                      ),
                    ),
                  ),
                ),
                Expanded(
                  child: InkWell(
                    borderRadius: BorderRadius.circular(8),
                    onTap: () => _toggleModeInvoice(false),
                    child: AnimatedContainer(
                      duration: const Duration(milliseconds: 200),
                      padding: const EdgeInsets.symmetric(vertical: 8),
                      decoration: BoxDecoration(
                        color: !_gabungInvoice ? Colors.white : Colors.transparent,
                        borderRadius: BorderRadius.circular(8),
                        boxShadow: !_gabungInvoice
                            ? const [BoxShadow(color: Color(0x14000000), blurRadius: 4, offset: Offset(0, 2))]
                            : null,
                      ),
                      alignment: Alignment.center,
                      child: Row(
                        mainAxisAlignment: MainAxisAlignment.center,
                        children: [
                          Icon(
                            !_gabungInvoice ? Icons.check_circle_rounded : Icons.radio_button_unchecked_rounded,
                            size: 15,
                            color: !_gabungInvoice ? const Color(0xFFD97706) : AppColors.textMuted,
                          ),
                          const SizedBox(width: 6),
                          Text(
                            'Pisah per Barang',
                            style: TextStyle(
                              fontSize: 12,
                              fontWeight: !_gabungInvoice ? FontWeight.w800 : FontWeight.w600,
                              color: !_gabungInvoice ? const Color(0xFFD97706) : AppColors.textSecondary,
                            ),
                          ),
                        ],
                      ),
                    ),
                  ),
                ),
              ],
            ),
          ),
          const SizedBox(height: 12),

          // Mode Content
          if (_gabungInvoice) ...[
            Text(
              'Nomor Invoice Penjualan (Sama untuk semua barang) *',
              style: S.caption(AppColors.textPrimary).copyWith(fontWeight: FontWeight.w700),
            ),
            const SizedBox(height: 6),
            TextField(
              controller: _masterNoInvCtrl,
              textCapitalization: TextCapitalization.characters,
              onChanged: (val) {
                setState(() {});
              },
              decoration: InputDecoration(
                hintText: 'Cth: INV/2026/09/0018',
                filled: true,
                fillColor: const Color(0xFFF8FAFC),
                border: OutlineInputBorder(
                  borderRadius: BorderRadius.circular(10),
                  borderSide: const BorderSide(color: Color(0xFFCBD5E1)),
                ),
                enabledBorder: OutlineInputBorder(
                  borderRadius: BorderRadius.circular(10),
                  borderSide: const BorderSide(color: Color(0xFFCBD5E1)),
                ),
                focusedBorder: OutlineInputBorder(
                  borderRadius: BorderRadius.circular(10),
                  borderSide: const BorderSide(color: Color(0xFF059669), width: 1.5),
                ),
                prefixIcon: const Icon(Icons.tag_rounded, size: 18, color: Color(0xFF059669)),
                suffixIcon: _masterNoInvCtrl.text.isNotEmpty
                    ? IconButton(
                        icon: const Icon(Icons.clear_rounded, size: 18, color: AppColors.textMuted),
                        onPressed: () {
                          setState(() {
                            _masterNoInvCtrl.clear();
                          });
                        },
                      )
                    : null,
                contentPadding: const EdgeInsets.symmetric(horizontal: 12, vertical: 12),
                isDense: true,
              ),
            ),
            const SizedBox(height: 8),

            // Shortcut buttons from last invoice
            if (lastInv.isNotEmpty) ...[
              Wrap(
                spacing: 6,
                runSpacing: 6,
                children: [
                  if (nextSuggestedInv != null)
                    InkWell(
                      borderRadius: BorderRadius.circular(6),
                      onTap: () {
                        setState(() {
                          _masterNoInvCtrl.text = nextSuggestedInv;
                        });
                      },
                      child: Container(
                        padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                        decoration: BoxDecoration(
                          color: const Color(0xFFECFDF5),
                          borderRadius: BorderRadius.circular(6),
                          border: Border.all(color: const Color(0xFFA7F3D0)),
                        ),
                        child: Row(
                          mainAxisSize: MainAxisSize.min,
                          children: [
                            const Icon(Icons.auto_mode_rounded, size: 12, color: Color(0xFF059669)),
                            const SizedBox(width: 4),
                            Text(
                              '+1 Lanjut: $nextSuggestedInv',
                              style: const TextStyle(fontSize: 11, fontWeight: FontWeight.w700, color: Color(0xFF059669)),
                            ),
                          ],
                        ),
                      ),
                    ),
                  InkWell(
                    borderRadius: BorderRadius.circular(6),
                    onTap: () {
                      setState(() {
                        _masterNoInvCtrl.text = lastInv;
                      });
                    },
                    child: Container(
                      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                      decoration: BoxDecoration(
                        color: const Color(0xFFF1F5F9),
                        borderRadius: BorderRadius.circular(6),
                        border: Border.all(color: const Color(0xFFCBD5E1)),
                      ),
                      child: Row(
                        mainAxisSize: MainAxisSize.min,
                        children: [
                          const Icon(Icons.history_rounded, size: 12, color: AppColors.textSecondary),
                          const SizedBox(width: 4),
                          Text(
                            'Sama dgn Terakhir: $lastInv',
                            style: const TextStyle(fontSize: 11, fontWeight: FontWeight.w600, color: AppColors.textSecondary),
                          ),
                        ],
                      ),
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 6),
            ],

            Row(
              children: [
                const Icon(Icons.info_outline_rounded, size: 13, color: Color(0xFF059669)),
                const SizedBox(width: 5),
                Expanded(
                  child: Text(
                    'No. invoice ini otomatis digunakan untuk semua barang yang terjual.',
                    style: S.caption(const Color(0xFF15803D)).copyWith(fontSize: 11),
                  ),
                ),
              ],
            ),
            const SizedBox(height: 10),
            // Tanggal Terjual / Invoice Selector
            InkWell(
              borderRadius: BorderRadius.circular(10),
              onTap: _selectMasterInvoiceDate,
              child: Container(
                padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
                decoration: BoxDecoration(
                  color: const Color(0xFFF0FDF4),
                  borderRadius: BorderRadius.circular(10),
                  border: Border.all(color: const Color(0xFFA7F3D0)),
                ),
                child: Row(
                  children: [
                    Container(
                      padding: const EdgeInsets.all(6),
                      decoration: BoxDecoration(
                        color: const Color(0xFFDCFCE7),
                        borderRadius: BorderRadius.circular(8),
                      ),
                      child: const Icon(Icons.calendar_month_rounded, size: 18, color: Color(0xFF059669)),
                    ),
                    const SizedBox(width: 10),
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(
                            'Tanggal Terjual / Invoice *',
                            style: S.caption(const Color(0xFF166534)).copyWith(fontWeight: FontWeight.w700),
                          ),
                          Text(
                            DateFormat('EEEE, dd MMMM yyyy', 'id').format(_masterTglInvoice),
                            style: const TextStyle(
                              fontSize: 13,
                              fontWeight: FontWeight.w800,
                              color: Color(0xFF15803D),
                            ),
                          ),
                        ],
                      ),
                    ),
                    Container(
                      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                      decoration: BoxDecoration(
                        color: Colors.white,
                        borderRadius: BorderRadius.circular(6),
                        border: Border.all(color: const Color(0xFFA7F3D0)),
                      ),
                      child: const Row(
                        mainAxisSize: MainAxisSize.min,
                        children: [
                          Text('Ubah', style: TextStyle(fontSize: 11, fontWeight: FontWeight.w700, color: Color(0xFF059669))),
                          SizedBox(width: 3),
                          Icon(Icons.edit_calendar_rounded, size: 13, color: Color(0xFF059669)),
                        ],
                      ),
                    ),
                  ],
                ),
              ),
            ),
          ] else ...[
            // Mode Pisah Info Banner
            Container(
              padding: const EdgeInsets.all(10),
              decoration: BoxDecoration(
                color: const Color(0xFFFFFBEB),
                borderRadius: BorderRadius.circular(8),
                border: Border.all(color: const Color(0xFFFDE68A)),
              ),
              child: Row(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  const Icon(Icons.call_split_rounded, size: 16, color: Color(0xFFD97706)),
                  const SizedBox(width: 8),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          'Mode Pisah per Barang Aktif',
                          style: S.bodySm().copyWith(fontWeight: FontWeight.w700, color: const Color(0xFFB45309)),
                        ),
                        const SizedBox(height: 2),
                        Text(
                          'Silakan isi kolom Nomor Invoice pada masing-masing barang yang laku di bawah.',
                          style: S.caption(const Color(0xFF92400E)).copyWith(fontSize: 11),
                        ),
                      ],
                    ),
                  ),
                ],
              ),
            ),
          ],
        ],
      ),
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
                            InkWell(
                              borderRadius: BorderRadius.circular(6),
                              onTap: _selectTglKunjungan,
                              child: Container(
                                padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 3),
                                decoration: BoxDecoration(
                                  color: const Color(0xFFF1F5F9),
                                  borderRadius: BorderRadius.circular(6),
                                ),
                                child: Row(
                                  mainAxisSize: MainAxisSize.min,
                                  children: [
                                    const Icon(Icons.calendar_today_outlined, size: 13, color: AppColors.primary),
                                    const SizedBox(width: 5),
                                    Text('Tgl Audit:', style: S.caption()),
                                    const SizedBox(width: 4),
                                    Text(DateFormat('dd MMM yyyy', 'id').format(_tglKunjungan), style: S.caption().copyWith(fontWeight: FontWeight.w700, color: AppColors.primary)),
                                    const SizedBox(width: 3),
                                    const Icon(Icons.edit_calendar_rounded, size: 12, color: AppColors.primary),
                                  ],
                                ),
                              ),
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
                                  desc: 'Tekan [−] jika ada barang laku terjual. Untuk restock barang baru ke toko, gunakan kolom Tambah Stok Titip atau tekan [+] saat stok penuh.',
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
                                  title: 'No. Invoice Fleksibel (Gabung / Pisah)',
                                  desc: 'Secara standar, no. invoice otomatis digabung (1 faktur untuk semua barang laku). Anda juga bisa memilih mode pisah jika toko memakai no. faktur berbeda.',
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

                  // Card Pengaturan Nomor Invoice Penjualan (Muncul jika ada barang terjual)
                  if (_totalTerjualAll > 0) ...[
                    _buildInvoiceSettingsCard(),
                    const SizedBox(height: 14),
                  ],

                  // List Item Barang
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
                                                onTap: () {
                                                  setState(() {
                                                    row.stepSisa(1, context);
                                                  });
                                                },
                                                child: Container(
                                                  width: 38,
                                                  height: double.infinity,
                                                  decoration: const BoxDecoration(
                                                    color: Color(0xFFF1F5F9),
                                                    borderRadius: BorderRadius.horizontal(right: Radius.circular(9)),
                                                  ),
                                                  alignment: Alignment.center,
                                                  child: Icon(
                                                    Icons.add_rounded,
                                                    size: 20,
                                                    color: sisaInt < row.item.qtySisa
                                                        ? AppColors.textPrimary
                                                        : const Color(0xFF0D9488),
                                                  ),
                                                ),
                                              ),
                                            ),
                                          ],
                                        ),
                                      ),
                                      const SizedBox(height: 4),
                                      Text(
                                        sisaInt >= row.item.qtySisa
                                            ? 'Utuh (Tekan + utk restock)'
                                            : 'Unit tersisa di toko',
                                        style: S.caption(
                                          sisaInt >= row.item.qtySisa
                                              ? const Color(0xFF0D9488)
                                              : AppColors.textMuted,
                                        ),
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

                            // Tambah Stok Titip (Restock Baru) Container
                            const SizedBox(height: 10),
                            Container(
                              padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 9),
                              decoration: BoxDecoration(
                                color: row.tambahStok > 0 ? const Color(0xFFF0FDF4) : const Color(0xFFF8FAFC),
                                borderRadius: BorderRadius.circular(10),
                                border: Border.all(
                                  color: row.tambahStok > 0 ? const Color(0xFF86EFAC) : const Color(0xFFE2E8F0),
                                  width: row.tambahStok > 0 ? 1.4 : 1.0,
                                ),
                              ),
                              child: Column(
                                children: [
                                  Row(
                                    children: [
                                      Container(
                                        padding: const EdgeInsets.all(6),
                                        decoration: BoxDecoration(
                                          color: row.tambahStok > 0 ? const Color(0xFFDCFCE7) : const Color(0xFFF1F5F9),
                                          borderRadius: BorderRadius.circular(8),
                                        ),
                                        child: Icon(
                                          Icons.add_business_rounded,
                                          size: 16,
                                          color: row.tambahStok > 0 ? const Color(0xFF16A34A) : const Color(0xFF64748B),
                                        ),
                                      ),
                                      const SizedBox(width: 10),
                                      Expanded(
                                        child: Column(
                                          crossAxisAlignment: CrossAxisAlignment.start,
                                          children: [
                                            Text(
                                              'Tambah Stok Titip (Restock)',
                                              style: S.caption(AppColors.textPrimary).copyWith(fontWeight: FontWeight.w700),
                                            ),
                                            Text(
                                              row.tambahStok > 0
                                                  ? 'Stok akhir toko: ${row.stokAkhir} unit'
                                                  : 'Titip unit baru jika restock',
                                              style: S.caption(row.tambahStok > 0 ? const Color(0xFF15803D) : AppColors.textMuted),
                                            ),
                                          ],
                                        ),
                                      ),
                                      // Stepper Tambah Stok
                                      Container(
                                        height: 34,
                                        decoration: BoxDecoration(
                                          color: Colors.white,
                                          borderRadius: BorderRadius.circular(8),
                                          border: Border.all(
                                            color: row.tambahStok > 0 ? const Color(0xFF86EFAC) : const Color(0xFFCBD5E1),
                                          ),
                                        ),
                                        child: Row(
                                          mainAxisSize: MainAxisSize.min,
                                          children: [
                                            Material(
                                              color: Colors.transparent,
                                              child: InkWell(
                                                borderRadius: const BorderRadius.horizontal(left: Radius.circular(7)),
                                                onTap: row.tambahStok > 0
                                                    ? () {
                                                        setState(() {
                                                          row.stepTambah(-1);
                                                        });
                                                      }
                                                    : null,
                                                child: Container(
                                                  width: 32,
                                                  height: double.infinity,
                                                  alignment: Alignment.center,
                                                  child: Icon(
                                                    Icons.remove_rounded,
                                                    size: 16,
                                                    color: row.tambahStok > 0 ? AppColors.textPrimary : const Color(0xFFCBD5E1),
                                                  ),
                                                ),
                                              ),
                                            ),
                                            Container(
                                              width: 42,
                                              alignment: Alignment.center,
                                              child: TextField(
                                                controller: row.tambahStokCtrl,
                                                keyboardType: TextInputType.number,
                                                textAlign: TextAlign.center,
                                                style: TextStyle(
                                                  fontWeight: FontWeight.w800,
                                                  fontSize: 14,
                                                  color: row.tambahStok > 0 ? const Color(0xFF15803D) : AppColors.textPrimary,
                                                ),
                                                decoration: const InputDecoration(
                                                  border: InputBorder.none,
                                                  contentPadding: EdgeInsets.zero,
                                                  isDense: true,
                                                ),
                                                onChanged: (_) {
                                                  setState(() {});
                                                },
                                              ),
                                            ),
                                            Material(
                                              color: Colors.transparent,
                                              child: InkWell(
                                                borderRadius: const BorderRadius.horizontal(right: Radius.circular(7)),
                                                onTap: () {
                                                  setState(() {
                                                    row.stepTambah(1);
                                                  });
                                                },
                                                child: Container(
                                                  width: 32,
                                                  height: double.infinity,
                                                  alignment: Alignment.center,
                                                  child: const Icon(
                                                    Icons.add_rounded,
                                                    size: 16,
                                                    color: Color(0xFF059669),
                                                  ),
                                                ),
                                              ),
                                            ),
                                          ],
                                        ),
                                      ),
                                    ],
                                  ),
                                  if (row.tambahStok > 0) ...[
                                    const Divider(height: 14, color: Color(0xFFBBF7D0)),
                                    InkWell(
                                      borderRadius: BorderRadius.circular(8),
                                      onTap: () => _selectRowRestockDate(row),
                                      child: Padding(
                                        padding: const EdgeInsets.symmetric(vertical: 2),
                                        child: Row(
                                          mainAxisAlignment: MainAxisAlignment.spaceBetween,
                                          children: [
                                            Row(
                                              children: [
                                                const Icon(Icons.event_available_rounded, size: 15, color: Color(0xFF16A34A)),
                                                const SizedBox(width: 6),
                                                Text(
                                                  'Tanggal Restock:',
                                                  style: S.caption(const Color(0xFF166534)).copyWith(fontWeight: FontWeight.w700),
                                                ),
                                              ],
                                            ),
                                            Container(
                                              padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                                              decoration: BoxDecoration(
                                                color: Colors.white,
                                                borderRadius: BorderRadius.circular(6),
                                                border: Border.all(color: const Color(0xFF86EFAC)),
                                              ),
                                              child: Row(
                                                mainAxisSize: MainAxisSize.min,
                                                children: [
                                                  Text(
                                                    DateFormat('dd MMM yyyy', 'id').format(row.tglRestock),
                                                    style: const TextStyle(
                                                      fontSize: 12,
                                                      fontWeight: FontWeight.w800,
                                                      color: Color(0xFF15803D),
                                                    ),
                                                  ),
                                                  const SizedBox(width: 4),
                                                  const Icon(Icons.edit_calendar_rounded, size: 13, color: Color(0xFF16A34A)),
                                                ],
                                              ),
                                            ),
                                          ],
                                        ),
                                      ),
                                    ),
                                  ],
                                ],
                              ),
                            ),

                            // Dynamic Invoice Field
                            if (isTerjual) ...[
                              const SizedBox(height: 12),
                              if (_gabungInvoice) ...[
                                // Mode Gabung: Sleek synchronized indicator badge
                                Container(
                                  padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 8),
                                  decoration: BoxDecoration(
                                    color: const Color(0xFFF0FDF4),
                                    borderRadius: BorderRadius.circular(8),
                                    border: Border.all(color: const Color(0xFFBBF7D0)),
                                  ),
                                  child: Row(
                                    children: [
                                      const Icon(Icons.receipt_long_rounded, size: 16, color: Color(0xFF16A34A)),
                                      const SizedBox(width: 8),
                                      Expanded(
                                        child: Column(
                                          crossAxisAlignment: CrossAxisAlignment.start,
                                          children: [
                                            Text(
                                              'Faktur Penjualan (Gabung 1 Invoice)',
                                              style: S.caption(const Color(0xFF166534)).copyWith(fontWeight: FontWeight.w700, fontSize: 10),
                                            ),
                                            Text(
                                              _masterNoInvCtrl.text.trim().isEmpty
                                                  ? 'Menunggu input No. Invoice di atas...'
                                                  : '${_masterNoInvCtrl.text.trim()} • ${DateFormat('dd MMM yyyy', 'id').format(_masterTglInvoice)}',
                                              style: TextStyle(
                                                fontSize: 13,
                                                fontWeight: FontWeight.w800,
                                                color: _masterNoInvCtrl.text.trim().isEmpty
                                                  ? const Color(0xFF94A3B8)
                                                  : const Color(0xFF15803D),
                                              ),
                                            ),
                                          ],
                                        ),
                                      ),
                                      InkWell(
                                        borderRadius: BorderRadius.circular(6),
                                        onTap: () => _toggleModeInvoice(false),
                                        child: Container(
                                          padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                                          decoration: BoxDecoration(
                                            color: Colors.white,
                                            borderRadius: BorderRadius.circular(6),
                                            border: Border.all(color: const Color(0xFFCBD5E1)),
                                          ),
                                          child: const Text(
                                            'Pisah',
                                            style: TextStyle(fontSize: 10, fontWeight: FontWeight.w700, color: Color(0xFF475569)),
                                          ),
                                        ),
                                      ),
                                    ],
                                  ),
                                ),
                              ] else ...[
                                // Mode Pisah: Separate TextField for this item
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
                                        mainAxisAlignment: MainAxisAlignment.spaceBetween,
                                        children: [
                                          Row(
                                            children: [
                                              const Icon(Icons.receipt_long_rounded, size: 15, color: Color(0xFFD97706)),
                                              const SizedBox(width: 6),
                                              Text(
                                                'No. Invoice Khusus Barang Ini *',
                                                style: S.caption(const Color(0xFFB45309)).copyWith(fontWeight: FontWeight.w700),
                                              ),
                                            ],
                                          ),
                                          InkWell(
                                            borderRadius: BorderRadius.circular(4),
                                            onTap: () => _toggleModeInvoice(true),
                                            child: const Text(
                                              'Gabung',
                                              style: TextStyle(fontSize: 11, fontWeight: FontWeight.w700, color: Color(0xFF2563EB)),
                                            ),
                                          ),
                                        ],
                                      ),
                                      const SizedBox(height: 6),
                                      TextField(
                                        controller: row.noInvCtrl,
                                        textCapitalization: TextCapitalization.characters,
                                        decoration: const InputDecoration(
                                          hintText: 'Cth: INV/2026/09/0012',
                                          filled: true,
                                          fillColor: Colors.white,
                                          border: OutlineInputBorder(),
                                          isDense: true,
                                          contentPadding: EdgeInsets.symmetric(horizontal: 10, vertical: 10),
                                        ),
                                      ),
                                      const SizedBox(height: 8),
                                      InkWell(
                                        borderRadius: BorderRadius.circular(8),
                                        onTap: () => _selectRowInvoiceDate(row),
                                        child: Container(
                                          padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 7),
                                          decoration: BoxDecoration(
                                            color: Colors.white,
                                            borderRadius: BorderRadius.circular(8),
                                            border: Border.all(color: const Color(0xFFFDE68A)),
                                          ),
                                          child: Row(
                                            mainAxisAlignment: MainAxisAlignment.spaceBetween,
                                            children: [
                                              Row(
                                                children: [
                                                  const Icon(Icons.calendar_today_rounded, size: 14, color: Color(0xFFD97706)),
                                                  const SizedBox(width: 6),
                                                  Text(
                                                    'Tgl Faktur / Terjual:',
                                                    style: S.caption(const Color(0xFFB45309)).copyWith(fontWeight: FontWeight.w600),
                                                  ),
                                                  const SizedBox(width: 6),
                                                  Text(
                                                    DateFormat('dd MMM yyyy', 'id').format(row.tglInvoice),
                                                    style: const TextStyle(fontSize: 12, fontWeight: FontWeight.w800, color: Color(0xFFB45309)),
                                                  ),
                                                ],
                                              ),
                                              const Text('Ubah', style: TextStyle(fontSize: 11, fontWeight: FontWeight.w700, color: Color(0xFF2563EB))),
                                            ],
                                          ),
                                        ),
                                      ),
                                    ],
                                  ),
                                ),
                              ],
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
                  if (_totalTerjualAll > 0 || _totalRestockAll > 0)
                    Container(
                      margin: const EdgeInsets.only(bottom: 16),
                      padding: const EdgeInsets.all(14),
                      decoration: BoxDecoration(
                        color: const Color(0xFF0F172A),
                        borderRadius: BorderRadius.circular(12),
                      ),
                      child: Column(
                        children: [
                          Row(
                            mainAxisAlignment: MainAxisAlignment.spaceBetween,
                            children: [
                              Column(
                                crossAxisAlignment: CrossAxisAlignment.start,
                                children: [
                                  Text('Total Terjual', style: S.caption(const Color(0xFF94A3B8))),
                                  Text('$_totalTerjualAll Unit', style: const TextStyle(color: Colors.white, fontWeight: FontWeight.w800, fontSize: 16)),
                                ],
                              ),
                              if (_totalRestockAll > 0)
                                Column(
                                  children: [
                                    Text('Restock Baru', style: S.caption(const Color(0xFF94A3B8))),
                                    Text('+$_totalRestockAll Unit', style: const TextStyle(color: Color(0xFF38BDF8), fontWeight: FontWeight.w800, fontSize: 16)),
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
                          if (_totalTerjualAll > 0) ...[
                            const Divider(height: 16, color: Color(0xFF334155)),
                            Row(
                              mainAxisAlignment: MainAxisAlignment.spaceBetween,
                              children: [
                                Row(
                                  children: [
                                    const Icon(Icons.receipt_long_outlined, size: 14, color: Color(0xFF94A3B8)),
                                    const SizedBox(width: 6),
                                    Text('Status Invoice:', style: S.caption(const Color(0xFF94A3B8))),
                                  ],
                                ),
                                Text(
                                  _gabungInvoice
                                      ? (_masterNoInvCtrl.text.trim().isNotEmpty
                                          ? 'Gabung: ${_masterNoInvCtrl.text.trim()}'
                                          : 'Gabung (Belum diisi)')
                                      : 'Pisah per Barang',
                                  style: TextStyle(
                                    fontSize: 12,
                                    fontWeight: FontWeight.w700,
                                    color: _gabungInvoice
                                        ? (_masterNoInvCtrl.text.trim().isNotEmpty ? const Color(0xFF38BDF8) : const Color(0xFFF87171))
                                        : const Color(0xFFFBBF24),
                                  ),
                                ),
                              ],
                            ),
                          ],
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
                      label: Text(
                        _totalRestockAll > 0
                            ? 'Simpan Audit & Restock (+$_totalRestockAll Unit)'
                            : 'Simpan Hasil Audit',
                        style: const TextStyle(fontWeight: FontWeight.w700, fontSize: 15),
                      ),
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
