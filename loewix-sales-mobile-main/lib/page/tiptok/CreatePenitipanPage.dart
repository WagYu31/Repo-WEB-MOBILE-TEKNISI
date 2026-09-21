import 'package:flutter/material.dart';
import 'package:intl/intl.dart';
import 'package:quickalert/quickalert.dart';
import '../../core/app_theme.dart';
import '../../service/api/ApiTipTok.dart';

class CreatePenitipanPage extends StatefulWidget {
  final int salesId;
  final String namaSales;
  final int? preselectedCustomerId;
  final String? preselectedCustomerName;

  const CreatePenitipanPage({
    super.key,
    required this.salesId,
    required this.namaSales,
    this.preselectedCustomerId,
    this.preselectedCustomerName,
  });

  @override
  State<CreatePenitipanPage> createState() => _CreatePenitipanPageState();
}

class _ItemRow {
  final namaCtrl = TextEditingController();
  final tipeCtrl = TextEditingController(text: 'IP Camera');
  final qtyCtrl = TextEditingController(text: '1');
  final insentifCtrl = TextEditingController(text: '20000');
}

class _CreatePenitipanPageState extends State<CreatePenitipanPage> {
  final _api = ApiTipTok();
  int? _selectedCustomerId;
  String? _selectedCustomerName;
  DateTime _tglTitip = DateTime.now();
  final _catatanCtrl = TextEditingController();
  bool _isLoading = false;

  final List<_ItemRow> _items = [_ItemRow()];

  @override
  void initState() {
    super.initState();
    if (widget.preselectedCustomerId != null && widget.preselectedCustomerId! > 0) {
      _selectedCustomerId = widget.preselectedCustomerId;
      _selectedCustomerName = widget.preselectedCustomerName;
    }
  }

  @override
  void dispose() {
    _catatanCtrl.dispose();
    for (var it in _items) {
      it.namaCtrl.dispose();
      it.tipeCtrl.dispose();
      it.qtyCtrl.dispose();
      it.insentifCtrl.dispose();
    }
    super.dispose();
  }

  void _addItem() {
    setState(() {
      _items.add(_ItemRow());
    });
  }

  void _removeItem(int index) {
    if (_items.length <= 1) return;
    setState(() {
      final removed = _items.removeAt(index);
      removed.namaCtrl.dispose();
      removed.tipeCtrl.dispose();
      removed.qtyCtrl.dispose();
      removed.insentifCtrl.dispose();
    });
  }

  Future<void> _pickDealer() async {
    final searchCtrl = TextEditingController();
    List<Map<String, dynamic>> dealerList = await _api.getDealers();

    if (!mounted) return;

    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: AppColors.bg,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(20)),
      ),
      builder: (ctx) {
        return StatefulBuilder(
          builder: (context, setModalState) {
            return DraggableScrollableSheet(
              initialChildSize: 0.75,
              minChildSize: 0.5,
              maxChildSize: 0.95,
              expand: false,
              builder: (_, scrollCtrl) {
                return Padding(
                  padding: const EdgeInsets.fromLTRB(16, 12, 16, 16),
                  child: Column(
                    children: [
                      Container(
                        width: 40,
                        height: 4,
                        decoration: BoxDecoration(
                          color: AppColors.border,
                          borderRadius: BorderRadius.circular(2),
                        ),
                      ),
                      const SizedBox(height: 12),
                      Text('Pilih Toko / Dealer Mitra', style: S.h3()),
                      const SizedBox(height: 12),
                      TextField(
                        controller: searchCtrl,
                        decoration: InputDecoration(
                          hintText: 'Cari nama toko, alamat...',
                          prefixIcon: const Icon(Icons.search, color: AppColors.textMuted),
                          filled: true,
                          fillColor: AppColors.surface,
                          contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
                          border: OutlineInputBorder(
                            borderRadius: BorderRadius.circular(12),
                            borderSide: const BorderSide(color: AppColors.border),
                          ),
                        ),
                        onChanged: (val) async {
                          final res = await _api.getDealers(search: val);
                          setModalState(() {
                            dealerList = res;
                          });
                        },
                      ),
                      const SizedBox(height: 12),
                      Expanded(
                        child: dealerList.isEmpty
                            ? const Center(child: Text('Toko tidak ditemukan', style: TextStyle(color: AppColors.textMuted)))
                            : ListView.separated(
                                controller: scrollCtrl,
                                itemCount: dealerList.length,
                                separatorBuilder: (_, __) => const Divider(height: 1, color: AppColors.border),
                                itemBuilder: (ctx, i) {
                                  final d = dealerList[i];
                                  final isDealer = (d['kategori']?.toString().toLowerCase() == 'dealer');
                                  return ListTile(
                                    contentPadding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                                    leading: CircleAvatar(
                                      backgroundColor: isDealer ? const Color(0xFFFEF3C7) : const Color(0xFFE0F2FE),
                                      child: Icon(
                                        isDealer ? Icons.store_rounded : Icons.storefront_outlined,
                                        color: isDealer ? const Color(0xFFD97706) : const Color(0xFF0284C7),
                                      ),
                                    ),
                                    title: Text(d['nama'] ?? '', style: S.bodySm().copyWith(fontWeight: FontWeight.w700)),
                                    subtitle: Text(
                                      "${d['kategori'] ?? 'Customer'} • ${d['alamat'] ?? ''}, ${d['kota'] ?? ''}",
                                      style: S.caption(),
                                      maxLines: 1,
                                      overflow: TextOverflow.ellipsis,
                                    ),
                                    onTap: () {
                                      setState(() {
                                        _selectedCustomerId = int.parse(d['id'].toString());
                                        _selectedCustomerName = d['nama'].toString();
                                      });
                                      Navigator.pop(context);
                                    },
                                  );
                                },
                              ),
                      ),
                    ],
                  ),
                );
              },
            );
          },
        );
      },
    );
  }

  Future<void> _submit() async {
    if (_selectedCustomerId == null || _selectedCustomerId! <= 0) {
      QuickAlert.show(
        context: context,
        type: QuickAlertType.warning,
        title: 'Toko Belum Dipilih',
        text: 'Silakan pilih toko / dealer terlebih dahulu.',
        confirmBtnColor: AppColors.warning,
      );
      return;
    }

    List<Map<String, dynamic>> itemsPayload = [];
    for (int i = 0; i < _items.length; i++) {
      final row = _items[i];
      final nama = row.namaCtrl.text.trim();
      final tipe = row.tipeCtrl.text.trim();
      final qty = int.tryParse(row.qtyCtrl.text.trim()) ?? 0;
      final ins = double.tryParse(row.insentifCtrl.text.trim()) ?? 0.0;

      if (nama.isEmpty) {
        QuickAlert.show(
          context: context,
          type: QuickAlertType.warning,
          title: 'Nama Barang Kosong',
          text: 'Nama barang pada baris ke-${i + 1} belum diisi!',
          confirmBtnColor: AppColors.warning,
        );
        return;
      }
      if (qty <= 0) {
        QuickAlert.show(
          context: context,
          type: QuickAlertType.warning,
          title: 'Qty Tidak Valid',
          text: 'Qty titip untuk "$nama" minimal 1 unit!',
          confirmBtnColor: AppColors.warning,
        );
        return;
      }

      itemsPayload.add({
        'nama_barang': nama,
        'tipe_barang': tipe,
        'qty_titip': qty,
        'insentif_per_unit': ins,
      });
    }

    setState(() => _isLoading = true);
    try {
      final res = await _api.createPenitipan(
        idCustomer: _selectedCustomerId!,
        idSales: widget.salesId,
        namaSales: widget.namaSales,
        tglTitip: DateFormat('yyyy-MM-dd').format(_tglTitip),
        catatan: _catatanCtrl.text.trim(),
        items: itemsPayload,
      );

      if (!mounted) return;
      setState(() => _isLoading = false);

      QuickAlert.show(
        context: context,
        type: QuickAlertType.success,
        title: 'Berhasil Titip Barang!',
        text: res['message'] ?? 'Data penitipan berhasil disimpan.',
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
    return Scaffold(
      backgroundColor: AppColors.bg,
      appBar: AppBar(
        title: const Text('Titip Barang Baru (TIP TOK)', style: TextStyle(fontWeight: FontWeight.w700, fontSize: 16)),
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
                  // 1. Pilih Toko
                  Text('Toko / Dealer Mitra *', style: S.label().copyWith(color: AppColors.textPrimary)),
                  const SizedBox(height: 6),
                  GestureDetector(
                    onTap: _pickDealer,
                    child: Container(
                      padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 14),
                      decoration: BoxDecoration(
                        color: AppColors.surface,
                        borderRadius: BorderRadius.circular(12),
                        border: Border.all(color: AppColors.border),
                      ),
                      child: Row(
                        children: [
                          const Icon(Icons.storefront_rounded, color: AppColors.primary),
                          const SizedBox(width: 12),
                          Expanded(
                            child: Text(
                              _selectedCustomerName ?? 'Pilih Toko Pelanggan / Dealer...',
                              style: _selectedCustomerName != null
                                  ? S.bodySm().copyWith(fontWeight: FontWeight.w700)
                                  : S.bodySm().copyWith(color: AppColors.textMuted),
                            ),
                          ),
                          const Icon(Icons.arrow_drop_down, color: AppColors.textMuted),
                        ],
                      ),
                    ),
                  ),

                  const SizedBox(height: 16),

                  // 2. Tanggal Titip
                  Text('Tanggal Penitipan *', style: S.label().copyWith(color: AppColors.textPrimary)),
                  const SizedBox(height: 6),
                  GestureDetector(
                    onTap: () async {
                      final picked = await showDatePicker(
                        context: context,
                        initialDate: _tglTitip,
                        firstDate: DateTime(2024),
                        lastDate: DateTime(2030),
                      );
                      if (picked != null) {
                        setState(() => _tglTitip = picked);
                      }
                    },
                    child: Container(
                      padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 14),
                      decoration: BoxDecoration(
                        color: AppColors.surface,
                        borderRadius: BorderRadius.circular(12),
                        border: Border.all(color: AppColors.border),
                      ),
                      child: Row(
                        children: [
                          const Icon(Icons.calendar_month_outlined, color: AppColors.textSecondary),
                          const SizedBox(width: 12),
                          Text(DateFormat('dd MMMM yyyy', 'id').format(_tglTitip), style: S.bodySm()),
                        ],
                      ),
                    ),
                  ),

                  const SizedBox(height: 20),

                  // 3. Section Daftar Barang Titipan
                  Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      Text('Daftar Barang Titip', style: S.h3()),
                      TextButton.icon(
                        onPressed: _addItem,
                        icon: const Icon(Icons.add_circle_outline_rounded, size: 18),
                        label: const Text('Tambah Baris'),
                        style: TextButton.styleFrom(foregroundColor: AppColors.primary),
                      ),
                    ],
                  ),

                  const SizedBox(height: 8),

                  ListView.builder(
                    shrinkWrap: true,
                    physics: const NeverScrollableScrollPhysics(),
                    itemCount: _items.length,
                    itemBuilder: (context, i) {
                      final item = _items[i];
                      return Container(
                        margin: const EdgeInsets.only(bottom: 12),
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
                                Text('Barang #${i + 1}', style: S.caption(AppColors.primary).copyWith(fontWeight: FontWeight.w700)),
                                if (_items.length > 1)
                                  GestureDetector(
                                    onTap: () => _removeItem(i),
                                    child: const Icon(Icons.delete_outline_rounded, color: Colors.redAccent, size: 20),
                                  ),
                              ],
                            ),
                            const SizedBox(height: 8),
                            TextField(
                              controller: item.namaCtrl,
                              decoration: const InputDecoration(
                                labelText: 'Nama Barang *',
                                hintText: 'Cth: IP Camera 4MP Loewix LX-400',
                                border: OutlineInputBorder(),
                                isDense: true,
                              ),
                            ),
                            const SizedBox(height: 10),
                            Row(
                              children: [
                                Expanded(
                                  flex: 3,
                                  child: TextField(
                                    controller: item.tipeCtrl,
                                    decoration: const InputDecoration(
                                      labelText: 'Kategori/Tipe',
                                      hintText: 'Cth: IP Cam / DVR',
                                      border: OutlineInputBorder(),
                                      isDense: true,
                                    ),
                                  ),
                                ),
                                const SizedBox(width: 8),
                                Expanded(
                                  flex: 2,
                                  child: TextField(
                                    controller: item.qtyCtrl,
                                    keyboardType: TextInputType.number,
                                    decoration: const InputDecoration(
                                      labelText: 'Qty Titip *',
                                      border: OutlineInputBorder(),
                                      isDense: true,
                                    ),
                                  ),
                                ),
                              ],
                            ),
                            const SizedBox(height: 10),
                            TextField(
                              controller: item.insentifCtrl,
                              keyboardType: TextInputType.number,
                              decoration: const InputDecoration(
                                labelText: 'Insentif per Unit (Rp)',
                                prefixText: 'Rp ',
                                border: OutlineInputBorder(),
                                isDense: true,
                              ),
                            ),
                          ],
                        ),
                      );
                    },
                  ),

                  const SizedBox(height: 12),

                  // 4. Catatan Tambahan
                  TextField(
                    controller: _catatanCtrl,
                    maxLines: 2,
                    decoration: const InputDecoration(
                      labelText: 'Catatan Penitipan (Opsional)',
                      hintText: 'Tuliskan perjanjian stok atau keterangan khusus...',
                      border: OutlineInputBorder(),
                    ),
                  ),

                  const SizedBox(height: 24),

                  // 5. Tombol Simpan
                  SizedBox(
                    width: double.infinity,
                    height: 50,
                    child: ElevatedButton.icon(
                      onPressed: _submit,
                      icon: const Icon(Icons.save_rounded),
                      label: const Text('Simpan Penitipan Barang', style: TextStyle(fontWeight: FontWeight.w700, fontSize: 15)),
                      style: ElevatedButton.styleFrom(
                        backgroundColor: const Color(0xFF0F172A),
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
