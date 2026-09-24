import 'package:flutter/material.dart';
import 'package:intl/intl.dart';
import 'package:quickalert/quickalert.dart';
import '../../core/app_theme.dart';
import '../../service/api/ApiTipTok.dart';

// =============================================================================
// 6 PRODUK RESMI PROGRAM TIP TOK (KONSINYASI LOEWIX)
// =============================================================================
class OfficialTipTokProduct {
  final int id;
  final String category;
  final String type;
  final String model;
  final String description;
  final double msrp;
  final double insentif;

  const OfficialTipTokProduct({
    required this.id,
    required this.category,
    required this.type,
    required this.model,
    required this.description,
    required this.msrp,
    required this.insentif,
  });
}

const List<OfficialTipTokProduct> kOfficialTipTokProducts = [
  OfficialTipTokProduct(
    id: 1,
    category: '2MP AHD INDOOR',
    type: '2MP AHD INDOOR LX-4F320-CE',
    model: 'LX-4F320-CE',
    description: 'Kamera CCTV Loewix 2MP AHD Indoor CatEyes (LX-4F320-CE)',
    msrp: 145000,
    insentif: 15000,
  ),
  OfficialTipTokProduct(
    id: 2,
    category: '2MP AHD OUTDOOR',
    type: '2MP AHD OUTDOOR LX-50F320-CM',
    model: 'LX-50F320-CM',
    description: 'Kamera CCTV Loewix 2MP AHD Outdoor ColorMax (LX-50F320-CM)',
    msrp: 170000,
    insentif: 15000,
  ),
  OfficialTipTokProduct(
    id: 3,
    category: '2MP AHD INDOOR',
    type: '2MP AHD INDOOR LX-4F320-CM',
    model: 'LX-4F320-CM',
    description: 'Kamera CCTV Loewix 2MP AHD Indoor ColorMax (LX-4F320-CM)',
    msrp: 145000,
    insentif: 15000,
  ),
  OfficialTipTokProduct(
    id: 4,
    category: '2MP AHD OUTDOOR',
    type: '2MP AHD OUTDOOR LX-50F320-CE',
    model: 'LX-50F320-CE',
    description: 'Kamera CCTV Loewix 2MP AHD Outdoor CatEyes (LX-50F320-CE)',
    msrp: 170000,
    insentif: 15000,
  ),
  OfficialTipTokProduct(
    id: 5,
    category: '4MP IPCAM INDOOR',
    type: '4MP IPCAM INDOOR LX-IPF40CMT02',
    model: 'LX-IPF40CMT02',
    description: 'Kamera CCTV Loewix 4MP IP Camera Indoor (LX-IPF40CMT02)',
    msrp: 350000,
    insentif: 15000,
  ),
  OfficialTipTokProduct(
    id: 6,
    category: '4MP IPCAM INDOOR',
    type: '4MP IPCAM INDOOR LX+IPF40CMT17',
    model: 'LX+IPF40CMT17',
    description: 'Kamera CCTV Loewix 4MP IP Camera Indoor (LX+IPF40CMT17)',
    msrp: 360000,
    insentif: 15000,
  ),
];

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
  OfficialTipTokProduct? selectedProduct;
  final qtyCtrl = TextEditingController(text: '1');

  _ItemRow({this.selectedProduct});

  int get qty => int.tryParse(qtyCtrl.text.trim()) ?? 0;
  double get insentifPerUnit => selectedProduct?.insentif ?? 0.0;
  double get subtotalReward => qty * insentifPerUnit;
}

class _CreatePenitipanPageState extends State<CreatePenitipanPage> {
  final _api = ApiTipTok();
  int? _selectedCustomerId;
  String? _selectedCustomerName;
  DateTime _tglTitip = DateTime.now();
  final _catatanCtrl = TextEditingController();
  bool _isLoading = false;
  bool _isCheckingSchedule = true;
  List<Map<String, dynamic>> _dealersToday = [];

  final List<_ItemRow> _items = [_ItemRow()];

  @override
  void initState() {
    super.initState();
    if (widget.preselectedCustomerId != null && widget.preselectedCustomerId! > 0) {
      _selectedCustomerId = widget.preselectedCustomerId;
      _selectedCustomerName = widget.preselectedCustomerName;
      _isCheckingSchedule = false;
    } else {
      _loadScheduleToday();
    }
  }

  Future<void> _loadScheduleToday() async {
    setState(() => _isCheckingSchedule = true);
    try {
      final list = await _api.getDealers(salesId: widget.salesId);
      if (mounted) {
        setState(() {
          _dealersToday = list;
          _isCheckingSchedule = false;
          if (list.length == 1) {
            _selectedCustomerId = int.tryParse(list[0]['id'].toString());
            _selectedCustomerName = list[0]['nama']?.toString();
          }
        });
      }
    } catch (_) {
      if (mounted) {
        setState(() => _isCheckingSchedule = false);
      }
    }
  }

  @override
  void dispose() {
    _catatanCtrl.dispose();
    for (var it in _items) {
      it.qtyCtrl.dispose();
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
      removed.qtyCtrl.dispose();
    });
  }

  int get _totalUnit => _items.fold(0, (sum, it) => sum + it.qty);
  int get _totalModel => _items.where((it) => it.selectedProduct != null).map((it) => it.selectedProduct!.id).toSet().length;
  double get _totalEstimatedReward => _items.fold(0.0, (sum, it) => sum + it.subtotalReward);

  String _formatRp(num number) {
    return NumberFormat.currency(locale: 'id', symbol: 'Rp ', decimalDigits: 0).format(number);
  }

  Future<void> _pickDealer() async {
    if (widget.preselectedCustomerId != null || _dealersToday.length == 1) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('Toko telah terkunci sesuai jadwal kunjungan resmi Admin hari ini.'),
          duration: Duration(seconds: 2),
        ),
      );
      return;
    }

    final searchCtrl = TextEditingController();
    List<Map<String, dynamic>> dealerList = _dealersToday.isNotEmpty
        ? List.from(_dealersToday)
        : await _api.getDealers(salesId: widget.salesId);

    if (!mounted) return;

    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: AppColors.surface,
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
                      Text('Pilih Toko Sesuai Jadwal Kunjungan', style: S.h3()),
                      const SizedBox(height: 4),
                      Text(
                        'Hanya toko yang dijadwalkan oleh Admin yang dapat dititipkan barang.',
                        style: S.caption(AppColors.textMuted),
                        textAlign: TextAlign.center,
                      ),
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
                          final res = await _api.getDealers(salesId: widget.salesId, search: val);
                          setModalState(() {
                            dealerList = res;
                          });
                        },
                      ),
                      const SizedBox(height: 12),
                      Expanded(
                        child: dealerList.isEmpty
                            ? Center(
                                child: Padding(
                                  padding: const EdgeInsets.all(24),
                                  child: Column(
                                    mainAxisAlignment: MainAxisAlignment.center,
                                    children: [
                                      const Icon(Icons.event_busy_rounded, color: Color(0xFFD97706), size: 42),
                                      const SizedBox(height: 10),
                                      Text('Tidak ada toko dalam jadwal kunjungan', style: S.bodySm().copyWith(fontWeight: FontWeight.w700), textAlign: TextAlign.center),
                                      const SizedBox(height: 4),
                                      Text('Penitipan barang baru hanya dapat dilakukan jika Admin telah membuatkan jadwal kunjungan ke toko tersebut.', style: S.caption(AppColors.textMuted), textAlign: TextAlign.center),
                                    ],
                                  ),
                                ),
                              )
                            : ListView.separated(
                                controller: scrollCtrl,
                                itemCount: dealerList.length,
                                separatorBuilder: (_, __) => const Divider(height: 1, color: AppColors.border),
                                itemBuilder: (ctx, i) {
                                  final d = dealerList[i];
                                  final isDealer = (d['kategori']?.toString().toLowerCase() == 'dealer');
                                  final jadwalStr = d['jadwal']?.toString() ?? '';
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
                                      "${d['kategori'] ?? 'Customer'} • ${d['alamat'] ?? ''}, ${d['kota'] ?? ''}${jadwalStr.isNotEmpty ? ' • Jadwal: $jadwalStr' : ''}",
                                      style: S.caption(),
                                      maxLines: 2,
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

  void _pickProductForItem(int itemIndex) {
    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: AppColors.surface,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(24)),
      ),
      builder: (ctx) {
        return Container(
          padding: const EdgeInsets.fromLTRB(16, 12, 16, 24),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              Container(
                width: 44,
                height: 5,
                decoration: BoxDecoration(
                  color: AppColors.borderLight,
                  borderRadius: BorderRadius.circular(3),
                ),
              ),
              const SizedBox(height: 14),
              Row(
                children: [
                  Container(
                    width: 36,
                    height: 36,
                    decoration: BoxDecoration(
                      color: const Color(0xFFEFF6FF),
                      borderRadius: BorderRadius.circular(10),
                    ),
                    child: const Icon(Icons.videocam_rounded, color: Color(0xFF2563EB), size: 20),
                  ),
                  const SizedBox(width: 12),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        const Text(
                          'Pilih 1 dari 6 Kamera Resmi TIP TOK',
                          style: TextStyle(fontSize: 15, fontWeight: FontWeight.w800, color: Color(0xFF0F172A)),
                        ),
                        Text(
                          'Program Konsinyasi Loewix (Insentif Rp 15.000 / unit)',
                          style: TextStyle(fontSize: 11, color: Colors.grey.shade600, fontWeight: FontWeight.w600),
                        ),
                      ],
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 16),
              ...kOfficialTipTokProducts.map((p) {
                final isSelected = (_items[itemIndex].selectedProduct?.id == p.id);
                final isOutdoor = p.category.contains('OUTDOOR');
                final isIp = p.category.contains('IPCAM');

                Color catBg = const Color(0xFFEFF6FF);
                Color catColor = const Color(0xFF1D4ED8);
                if (isOutdoor) {
                  catBg = const Color(0xFFFEF3C7);
                  catColor = const Color(0xFFB45309);
                } else if (isIp) {
                  catBg = const Color(0xFFF3E8FF);
                  catColor = const Color(0xFF7E22CE);
                }

                return Container(
                  margin: const EdgeInsets.only(bottom: 10),
                  decoration: BoxDecoration(
                    color: isSelected ? const Color(0xFFF0FDF4) : Colors.white,
                    borderRadius: BorderRadius.circular(14),
                    border: Border.all(
                      color: isSelected ? const Color(0xFF10B981) : const Color(0xFFE2E8F0),
                      width: isSelected ? 2 : 1.2,
                    ),
                    boxShadow: [
                      BoxShadow(
                        color: Colors.black.withOpacity(0.02),
                        blurRadius: 4,
                        offset: const Offset(0, 2),
                      )
                    ],
                  ),
                  child: Material(
                    color: Colors.transparent,
                    borderRadius: BorderRadius.circular(14),
                    child: InkWell(
                      borderRadius: BorderRadius.circular(14),
                      onTap: () {
                        setState(() {
                          _items[itemIndex].selectedProduct = p;
                        });
                        Navigator.pop(ctx);
                      },
                      child: Padding(
                        padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
                        child: Row(
                          children: [
                            Container(
                              padding: const EdgeInsets.all(10),
                              decoration: BoxDecoration(
                                color: catBg,
                                borderRadius: BorderRadius.circular(10),
                              ),
                              child: Icon(
                                isIp
                                    ? Icons.wifi_tethering_rounded
                                    : (isOutdoor ? Icons.wb_sunny_outlined : Icons.home_rounded),
                                color: catColor,
                                size: 20,
                              ),
                            ),
                            const SizedBox(width: 12),
                            Expanded(
                              child: Column(
                                crossAxisAlignment: CrossAxisAlignment.start,
                                children: [
                                  Row(
                                    children: [
                                      Container(
                                        padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
                                        decoration: BoxDecoration(
                                          color: catBg,
                                          borderRadius: BorderRadius.circular(4),
                                        ),
                                        child: Text(
                                          p.category,
                                          style: TextStyle(fontSize: 9.5, fontWeight: FontWeight.w800, color: catColor),
                                        ),
                                      ),
                                      const SizedBox(width: 6),
                                      Text(
                                        p.model,
                                        style: const TextStyle(fontSize: 12, fontWeight: FontWeight.w800, color: Color(0xFF0F172A)),
                                      ),
                                    ],
                                  ),
                                  const SizedBox(height: 4),
                                  Text(
                                    p.description,
                                    style: TextStyle(fontSize: 11.5, fontWeight: FontWeight.w600, color: Colors.grey.shade700),
                                    maxLines: 2,
                                    overflow: TextOverflow.ellipsis,
                                  ),
                                  const SizedBox(height: 4),
                                  Row(
                                    children: [
                                      Text(
                                        'MSRP: ${_formatRp(p.msrp)}',
                                        style: TextStyle(fontSize: 11, color: Colors.grey.shade500, fontWeight: FontWeight.w600),
                                      ),
                                      const Spacer(),
                                      Container(
                                        padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
                                        decoration: BoxDecoration(
                                          color: const Color(0xFFDCFCE7),
                                          borderRadius: BorderRadius.circular(6),
                                        ),
                                        child: const Text(
                                          '+Rp 15.000 / unit',
                                          style: TextStyle(fontSize: 10.5, fontWeight: FontWeight.w800, color: Color(0xFF15803D)),
                                        ),
                                      ),
                                    ],
                                  ),
                                ],
                              ),
                            ),
                            const SizedBox(width: 8),
                            Icon(
                              isSelected ? Icons.check_circle_rounded : Icons.radio_button_unchecked,
                              color: isSelected ? const Color(0xFF10B981) : Colors.grey.shade300,
                              size: 22,
                            ),
                          ],
                        ),
                      ),
                    ),
                  ),
                );
              }),
            ],
          ),
        );
      },
    );
  }

  void _showKatalogDialog() {
    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: AppColors.surface,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(24)),
      ),
      builder: (ctx) {
        return DraggableScrollableSheet(
          initialChildSize: 0.85,
          minChildSize: 0.5,
          maxChildSize: 0.95,
          expand: false,
          builder: (_, scrollCtrl) {
            return Padding(
              padding: const EdgeInsets.fromLTRB(16, 12, 16, 20),
              child: Column(
                children: [
                  Container(
                    width: 44,
                    height: 5,
                    decoration: BoxDecoration(
                      color: AppColors.borderLight,
                      borderRadius: BorderRadius.circular(3),
                    ),
                  ),
                  const SizedBox(height: 14),
                  Row(
                    children: [
                      Container(
                        width: 38,
                        height: 38,
                        decoration: BoxDecoration(
                          gradient: const LinearGradient(colors: [Color(0xFF2563EB), Color(0xFF1D4ED8)]),
                          borderRadius: BorderRadius.circular(10),
                        ),
                        child: const Icon(Icons.menu_book_rounded, color: Colors.white, size: 20),
                      ),
                      const SizedBox(width: 12),
                      Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            const Text(
                              'Katalog 6 Kamera Resmi TIP TOK',
                              style: TextStyle(fontSize: 16, fontWeight: FontWeight.w800, color: Color(0xFF0F172A)),
                            ),
                            Text(
                              'Pilihan resmi konsinyasi Loewix dengan insentif penjualan',
                              style: TextStyle(fontSize: 11.5, color: Colors.grey.shade600),
                            ),
                          ],
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(height: 16),
                  Expanded(
                    child: ListView.separated(
                      controller: scrollCtrl,
                      itemCount: kOfficialTipTokProducts.length,
                      separatorBuilder: (_, __) => const SizedBox(height: 12),
                      itemBuilder: (context, i) {
                        final p = kOfficialTipTokProducts[i];
                        final isOutdoor = p.category.contains('OUTDOOR');
                        final isIp = p.category.contains('IPCAM');

                        Color catBg = const Color(0xFFEFF6FF);
                        Color catColor = const Color(0xFF1D4ED8);
                        if (isOutdoor) {
                          catBg = const Color(0xFFFEF3C7);
                          catColor = const Color(0xFFB45309);
                        } else if (isIp) {
                          catBg = const Color(0xFFF3E8FF);
                          catColor = const Color(0xFF7E22CE);
                        }

                        return Container(
                          padding: const EdgeInsets.all(14),
                          decoration: BoxDecoration(
                            color: Colors.white,
                            borderRadius: BorderRadius.circular(14),
                            border: Border.all(color: const Color(0xFFE2E8F0)),
                            boxShadow: [
                              BoxShadow(color: Colors.black.withOpacity(0.02), blurRadius: 4, offset: const Offset(0, 2))
                            ],
                          ),
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Row(
                                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                                children: [
                                  Container(
                                    padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                                    decoration: BoxDecoration(color: catBg, borderRadius: BorderRadius.circular(6)),
                                    child: Text(p.category, style: TextStyle(fontSize: 10, fontWeight: FontWeight.w800, color: catColor)),
                                  ),
                                  Container(
                                    padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                                    decoration: BoxDecoration(color: const Color(0xFFDCFCE7), borderRadius: BorderRadius.circular(6)),
                                    child: Text(
                                      'Reward: ${_formatRp(p.insentif)}',
                                      style: const TextStyle(fontSize: 11, fontWeight: FontWeight.w800, color: Color(0xFF15803D)),
                                    ),
                                  ),
                                ],
                              ),
                              const SizedBox(height: 8),
                              Text(p.type, style: const TextStyle(fontSize: 14, fontWeight: FontWeight.w800, color: Color(0xFF0F172A))),
                              const SizedBox(height: 4),
                              Text(p.description, style: TextStyle(fontSize: 12, color: Colors.grey.shade600, height: 1.3)),
                              const SizedBox(height: 8),
                              Container(
                                padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
                                decoration: BoxDecoration(color: const Color(0xFFF8FAFC), borderRadius: BorderRadius.circular(8)),
                                child: Row(
                                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                                  children: [
                                    Text('Harga MSRP:', style: TextStyle(fontSize: 12, color: Colors.grey.shade600)),
                                    Text(_formatRp(p.msrp), style: const TextStyle(fontSize: 13, fontWeight: FontWeight.w800, color: Color(0xFF0F172A))),
                                  ],
                                ),
                              ),
                            ],
                          ),
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
  }

  Future<void> _submit() async {
    if (_selectedCustomerId == null || _selectedCustomerId! <= 0) {
      QuickAlert.show(
        context: context,
        type: QuickAlertType.warning,
        title: 'Toko Belum Dipilih',
        text: 'Silakan pilih toko / dealer mitra terlebih dahulu.',
        confirmBtnColor: AppColors.warning,
      );
      return;
    }

    List<Map<String, dynamic>> itemsPayload = [];
    for (int i = 0; i < _items.length; i++) {
      final row = _items[i];
      if (row.selectedProduct == null) {
        QuickAlert.show(
          context: context,
          type: QuickAlertType.warning,
          title: 'Kamera Belum Dipilih',
          text: 'Pilih 1 dari 6 model kamera resmi pada Baris #${i + 1}!',
          confirmBtnColor: AppColors.warning,
        );
        return;
      }

      final qty = row.qty;
      if (qty <= 0) {
        QuickAlert.show(
          context: context,
          type: QuickAlertType.warning,
          title: 'Qty Tidak Valid',
          text: 'Qty titip untuk "${row.selectedProduct!.type}" minimal 1 unit!',
          confirmBtnColor: AppColors.warning,
        );
        return;
      }

      itemsPayload.add({
        'nama_barang': row.selectedProduct!.type,
        'tipe_barang': row.selectedProduct!.category,
        'qty_titip': qty,
        'insentif_per_unit': row.selectedProduct!.insentif,
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
        title: 'Penitipan Berhasil!',
        text: res['message'] ?? 'Data penitipan barang telah tersimpan ke sistem.',
        confirmBtnText: 'Selesai',
        confirmBtnColor: AppColors.success,
        onConfirmBtnTap: () {
          Navigator.pop(context); // Close alert
          Navigator.pop(context, true); // Return to home with refresh
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
    final isStoreLocked = (widget.preselectedCustomerId != null || _dealersToday.length == 1);
    final hasNoSchedule = (!_isCheckingSchedule && widget.preselectedCustomerId == null && _dealersToday.isEmpty);

    return Scaffold(
      backgroundColor: AppColors.bg,
      appBar: AppBar(
        title: const Text('Titip Barang Baru (TIP TOK)', style: TextStyle(fontWeight: FontWeight.w800, fontSize: 17)),
        elevation: 0,
        backgroundColor: Colors.white,
        foregroundColor: const Color(0xFF0F172A),
        centerTitle: false,
        actions: [
          IconButton(
            tooltip: 'Katalog 6 Produk',
            icon: const Icon(Icons.menu_book_rounded, color: Color(0xFF2563EB)),
            onPressed: _showKatalogDialog,
          ),
        ],
      ),
      body: _isCheckingSchedule
          ? const Center(child: CircularProgressIndicator(color: AppColors.primary))
          : hasNoSchedule
              ? Center(
                  child: Padding(
                    padding: const EdgeInsets.all(24),
                    child: Column(
                      mainAxisAlignment: MainAxisAlignment.center,
                      children: [
                        Container(
                          width: 80,
                          height: 80,
                          decoration: BoxDecoration(
                            color: const Color(0xFFFEF3C7),
                            shape: BoxShape.circle,
                            border: Border.all(color: const Color(0xFFFDE68A), width: 2),
                          ),
                          child: const Icon(Icons.event_busy_rounded, size: 40, color: Color(0xFFD97706)),
                        ),
                        const SizedBox(height: 20),
                        const Text(
                          'Tidak Ada Jadwal Kunjungan Hari Ini',
                          style: TextStyle(fontSize: 18, fontWeight: FontWeight.w800, color: Color(0xFF0F172A)),
                          textAlign: TextAlign.center,
                        ),
                        const SizedBox(height: 10),
                        const Text(
                          'Sales TIDAK BISA menitipkan barang baru jika belum ada jadwal kunjungan resmi dari Admin hari ini.\n\nSilakan minta Admin untuk membuatkan jadwal kunjungan toko terlebih dahulu.',
                          style: TextStyle(fontSize: 13, color: Color(0xFF64748B), height: 1.5),
                          textAlign: TextAlign.center,
                        ),
                        const SizedBox(height: 24),
                        SizedBox(
                          width: double.infinity,
                          height: 46,
                          child: ElevatedButton.icon(
                            onPressed: () => Navigator.pop(context),
                            icon: const Icon(Icons.arrow_back_rounded, size: 18),
                            label: const Text('Kembali ke Beranda', style: TextStyle(fontWeight: FontWeight.w700)),
                            style: ElevatedButton.styleFrom(
                              backgroundColor: const Color(0xFF0F172A),
                              foregroundColor: Colors.white,
                              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                            ),
                          ),
                        ),
                      ],
                    ),
                  ),
                )
              : _isLoading
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
                                color: isStoreLocked ? const Color(0xFFF8FAFC) : AppColors.surface,
                                borderRadius: BorderRadius.circular(12),
                                border: Border.all(
                                  color: isStoreLocked ? const Color(0xFF10B981).withOpacity(0.5) : AppColors.border,
                                ),
                              ),
                              child: Row(
                                children: [
                                  Icon(
                                    isStoreLocked ? Icons.verified_rounded : Icons.storefront_rounded,
                                    color: isStoreLocked ? const Color(0xFF10B981) : AppColors.primary,
                                  ),
                                  const SizedBox(width: 12),
                                  Expanded(
                                    child: Column(
                                      crossAxisAlignment: CrossAxisAlignment.start,
                                      children: [
                                        Text(
                                          _selectedCustomerName ?? 'Pilih Toko Pelanggan / Dealer...',
                                          style: _selectedCustomerName != null
                                              ? S.bodySm().copyWith(fontWeight: FontWeight.w700)
                                              : S.bodySm().copyWith(color: AppColors.textMuted),
                                        ),
                                        if (_selectedCustomerName != null && isStoreLocked) ...[
                                          const SizedBox(height: 2),
                                          const Text(
                                            '✓ Sesuai Jadwal Kunjungan Resmi Admin Hari Ini',
                                            style: TextStyle(
                                              fontSize: 10,
                                              color: Color(0xFF059669),
                                              fontWeight: FontWeight.w600,
                                            ),
                                          ),
                                        ],
                                      ],
                                    ),
                                  ),
                                  Icon(
                                    isStoreLocked ? Icons.lock_outline_rounded : Icons.arrow_drop_down,
                                    color: AppColors.textMuted,
                                    size: 20,
                                  ),
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

                          const SizedBox(height: 22),

                          // 3. Section Header Daftar Barang Titipan
                          Row(
                            mainAxisAlignment: MainAxisAlignment.spaceBetween,
                            children: [
                              Column(
                                crossAxisAlignment: CrossAxisAlignment.start,
                                children: [
                                  Text('Daftar Barang Dititipkan', style: S.h3()),
                                  Text(
                                    'Pilih dari 6 model kamera resmi berinsentif',
                                    style: TextStyle(fontSize: 11, color: Colors.grey.shade600),
                                  ),
                                ],
                              ),
                              OutlinedButton.icon(
                                onPressed: _addItem,
                                icon: const Icon(Icons.add_circle_outline_rounded, size: 16),
                                label: const Text('Tambah Baris', style: TextStyle(fontSize: 12, fontWeight: FontWeight.w700)),
                                style: OutlinedButton.styleFrom(
                                  foregroundColor: const Color(0xFF2563EB),
                                  side: const BorderSide(color: Color(0xFF93C5FD)),
                                  shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
                                  padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
                                ),
                              ),
                            ],
                          ),

                          const SizedBox(height: 12),

                          ListView.builder(
                            shrinkWrap: true,
                            physics: const NeverScrollableScrollPhysics(),
                            itemCount: _items.length,
                            itemBuilder: (context, i) {
                              final item = _items[i];
                              final hasProduct = (item.selectedProduct != null);
                              final p = item.selectedProduct;

                              return Container(
                                margin: const EdgeInsets.only(bottom: 14),
                                padding: const EdgeInsets.all(14),
                                decoration: BoxDecoration(
                                  color: Colors.white,
                                  borderRadius: BorderRadius.circular(16),
                                  border: Border.all(
                                    color: hasProduct ? const Color(0xFF93C5FD) : const Color(0xFFE2E8F0),
                                    width: 1.5,
                                  ),
                                  boxShadow: [
                                    BoxShadow(
                                      color: Colors.black.withOpacity(0.03),
                                      blurRadius: 8,
                                      offset: const Offset(0, 3),
                                    )
                                  ],
                                ),
                                child: Column(
                                  crossAxisAlignment: CrossAxisAlignment.start,
                                  children: [
                                    // Header Baris Barang
                                    Row(
                                      mainAxisAlignment: MainAxisAlignment.spaceBetween,
                                      children: [
                                        Container(
                                          padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                                          decoration: BoxDecoration(
                                            color: const Color(0xFF0F172A),
                                            borderRadius: BorderRadius.circular(6),
                                          ),
                                          child: Text(
                                            'Item #${i + 1}',
                                            style: const TextStyle(fontSize: 11, fontWeight: FontWeight.w800, color: Colors.white),
                                          ),
                                        ),
                                        if (_items.length > 1)
                                          GestureDetector(
                                            onTap: () => _removeItem(i),
                                            child: Container(
                                              padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                                              decoration: BoxDecoration(
                                                color: const Color(0xFFFEE2E2),
                                                borderRadius: BorderRadius.circular(6),
                                              ),
                                              child: const Row(
                                                children: [
                                                  Icon(Icons.delete_outline_rounded, color: Color(0xFFDC2626), size: 14),
                                                  SizedBox(width: 4),
                                                  Text('Hapus', style: TextStyle(fontSize: 11, fontWeight: FontWeight.w700, color: Color(0xFFDC2626))),
                                                ],
                                              ),
                                            ),
                                          ),
                                      ],
                                    ),
                                    const SizedBox(height: 12),

                                    // PILIH KAMERA RESMI
                                    Text('PILIH 1 DARI 6 KAMERA RESMI TIP TOK *',
                                        style: TextStyle(fontSize: 11, fontWeight: FontWeight.w800, color: Colors.grey.shade700)),
                                    const SizedBox(height: 6),
                                    GestureDetector(
                                      onTap: () => _pickProductForItem(i),
                                      child: Container(
                                        padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
                                        decoration: BoxDecoration(
                                          color: hasProduct ? const Color(0xFFF8FAFC) : const Color(0xFFFFFBEB),
                                          borderRadius: BorderRadius.circular(10),
                                          border: Border.all(
                                            color: hasProduct ? const Color(0xFFCBD5E1) : const Color(0xFFFCD34D),
                                            width: 1.2,
                                          ),
                                        ),
                                        child: Row(
                                          children: [
                                            Icon(
                                              Icons.videocam_rounded,
                                              color: hasProduct ? const Color(0xFF2563EB) : const Color(0xFFD97706),
                                              size: 20,
                                            ),
                                            const SizedBox(width: 10),
                                            Expanded(
                                              child: hasProduct
                                                  ? Text(
                                                      p!.type,
                                                      style: const TextStyle(
                                                        fontSize: 13,
                                                        fontWeight: FontWeight.w800,
                                                        color: Color(0xFF0F172A),
                                                      ),
                                                    )
                                                  : const Text(
                                                      '-- Pilih Model Kamera Resmi TIP TOK --',
                                                      style: TextStyle(
                                                        fontSize: 13,
                                                        fontWeight: FontWeight.w700,
                                                        color: Color(0xFF92400E),
                                                      ),
                                                    ),
                                            ),
                                            const Icon(Icons.arrow_drop_down, color: Color(0xFF64748B)),
                                          ],
                                        ),
                                      ),
                                    ),

                                    if (hasProduct) ...[
                                      const SizedBox(height: 10),

                                      // Auto-filled Description & Category
                                      Container(
                                        padding: const EdgeInsets.all(10),
                                        decoration: BoxDecoration(
                                          color: const Color(0xFFF1F5F9),
                                          borderRadius: BorderRadius.circular(8),
                                        ),
                                        child: Column(
                                          crossAxisAlignment: CrossAxisAlignment.start,
                                          children: [
                                            Text(
                                              p!.description,
                                              style: const TextStyle(fontSize: 12, fontWeight: FontWeight.w600, color: Color(0xFF334155)),
                                            ),
                                            const SizedBox(height: 4),
                                            Row(
                                              children: [
                                                Container(
                                                  padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
                                                  decoration: BoxDecoration(
                                                    color: const Color(0xFFE2E8F0),
                                                    borderRadius: BorderRadius.circular(4),
                                                  ),
                                                  child: Text(
                                                    'Kategori: ${p.category}',
                                                    style: const TextStyle(fontSize: 10, fontWeight: FontWeight.w700, color: Color(0xFF475569)),
                                                  ),
                                                ),
                                                const Spacer(),
                                                Text(
                                                  'MSRP: ${_formatRp(p.msrp)}',
                                                  style: TextStyle(fontSize: 11, fontWeight: FontWeight.w600, color: Colors.grey.shade600),
                                                ),
                                              ],
                                            ),
                                          ],
                                        ),
                                      ),

                                      const SizedBox(height: 12),

                                      // QTY & INSENTIF ROW
                                      Row(
                                        crossAxisAlignment: CrossAxisAlignment.start,
                                        children: [
                                          // QTY STEPPER
                                          Expanded(
                                            flex: 4,
                                            child: Column(
                                              crossAxisAlignment: CrossAxisAlignment.start,
                                              children: [
                                                Text('JUMLAH TITIP *',
                                                    style: TextStyle(fontSize: 10.5, fontWeight: FontWeight.w800, color: Colors.grey.shade700)),
                                                const SizedBox(height: 4),
                                                Container(
                                                  height: 42,
                                                  decoration: BoxDecoration(
                                                    color: const Color(0xFFF8FAFC),
                                                    borderRadius: BorderRadius.circular(8),
                                                    border: Border.all(color: const Color(0xFFCBD5E1)),
                                                  ),
                                                  child: Row(
                                                    children: [
                                                      IconButton(
                                                        padding: EdgeInsets.zero,
                                                        iconSize: 18,
                                                        icon: const Icon(Icons.remove_rounded, color: Color(0xFF475569)),
                                                        onPressed: () {
                                                          int current = item.qty;
                                                          if (current > 1) {
                                                            setState(() {
                                                              item.qtyCtrl.text = (current - 1).toString();
                                                            });
                                                          }
                                                        },
                                                      ),
                                                      Expanded(
                                                        child: TextField(
                                                          controller: item.qtyCtrl,
                                                          keyboardType: TextInputType.number,
                                                          textAlign: TextAlign.center,
                                                          style: const TextStyle(fontSize: 14, fontWeight: FontWeight.w900, color: Color(0xFF0F172A)),
                                                          decoration: const InputDecoration(
                                                            border: InputBorder.none,
                                                            isDense: true,
                                                            contentPadding: EdgeInsets.zero,
                                                          ),
                                                          onChanged: (_) => setState(() {}),
                                                        ),
                                                      ),
                                                      IconButton(
                                                        padding: EdgeInsets.zero,
                                                        iconSize: 18,
                                                        icon: const Icon(Icons.add_rounded, color: Color(0xFF475569)),
                                                        onPressed: () {
                                                          int current = item.qty;
                                                          setState(() {
                                                            item.qtyCtrl.text = (current + 1).toString();
                                                          });
                                                        },
                                                      ),
                                                    ],
                                                  ),
                                                ),
                                              ],
                                            ),
                                          ),
                                          const SizedBox(width: 8),

                                          // TARIF INSENTIF (LOCKED)
                                          Expanded(
                                            flex: 4,
                                            child: Column(
                                              crossAxisAlignment: CrossAxisAlignment.start,
                                              children: [
                                                Text('TARIF INSENTIF',
                                                    style: TextStyle(fontSize: 10.5, fontWeight: FontWeight.w800, color: Colors.grey.shade700)),
                                                const SizedBox(height: 4),
                                                Container(
                                                  height: 42,
                                                  padding: const EdgeInsets.symmetric(horizontal: 8),
                                                  alignment: Alignment.centerLeft,
                                                  decoration: BoxDecoration(
                                                    color: const Color(0xFFF1F5F9),
                                                    borderRadius: BorderRadius.circular(8),
                                                    border: Border.all(color: const Color(0xFFE2E8F0)),
                                                  ),
                                                  child: Text(
                                                    _formatRp(p.insentif),
                                                    style: const TextStyle(fontSize: 13, fontWeight: FontWeight.w800, color: Color(0xFF1E293B)),
                                                  ),
                                                ),
                                              ],
                                            ),
                                          ),
                                        ],
                                      ),

                                      const SizedBox(height: 10),

                                      // Subtotal Komisi Box
                                      Container(
                                        padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 8),
                                        decoration: BoxDecoration(
                                          color: const Color(0xFFECFDF5),
                                          borderRadius: BorderRadius.circular(8),
                                          border: Border.all(color: const Color(0xFFA7F3D0)),
                                        ),
                                        child: Row(
                                          mainAxisAlignment: MainAxisAlignment.spaceBetween,
                                          children: [
                                            Text(
                                              'Subtotal Reward (${item.qty} unit):',
                                              style: const TextStyle(fontSize: 11.5, fontWeight: FontWeight.w700, color: Color(0xFF047857)),
                                            ),
                                            Text(
                                              _formatRp(item.subtotalReward),
                                              style: const TextStyle(fontSize: 13, fontWeight: FontWeight.w900, color: Color(0xFF059669)),
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

                          // 4. SUMMARY ESTIMASI CARD
                          Container(
                            padding: const EdgeInsets.all(16),
                            margin: const EdgeInsets.only(top: 4, bottom: 16),
                            decoration: BoxDecoration(
                              gradient: const LinearGradient(
                                colors: [Color(0xFF0F172A), Color(0xFF1E293B)],
                                begin: Alignment.topLeft,
                                end: Alignment.bottomRight,
                              ),
                              borderRadius: BorderRadius.circular(16),
                              boxShadow: [
                                BoxShadow(
                                  color: Colors.black.withOpacity(0.15),
                                  blurRadius: 10,
                                  offset: const Offset(0, 4),
                                )
                              ],
                            ),
                            child: Row(
                              children: [
                                Container(
                                  width: 42,
                                  height: 42,
                                  decoration: BoxDecoration(
                                    color: Colors.white.withOpacity(0.1),
                                    borderRadius: BorderRadius.circular(10),
                                  ),
                                  child: const Icon(Icons.calculate_outlined, color: Colors.white, size: 22),
                                ),
                                const SizedBox(width: 12),
                                Expanded(
                                  child: Column(
                                    crossAxisAlignment: CrossAxisAlignment.start,
                                    children: [
                                      const Text(
                                        'TOTAL TITIP FISIK',
                                        style: TextStyle(fontSize: 10, fontWeight: FontWeight.w800, color: Color(0xFF94A3B8), letterSpacing: 0.5),
                                      ),
                                      Text(
                                        '$_totalUnit Unit ($_totalModel Model)',
                                        style: const TextStyle(fontSize: 15, fontWeight: FontWeight.w900, color: Colors.white),
                                      ),
                                    ],
                                  ),
                                ),
                                Column(
                                  crossAxisAlignment: CrossAxisAlignment.end,
                                  children: [
                                    const Text(
                                      'ESTIMASI REWARD',
                                      style: TextStyle(fontSize: 10, fontWeight: FontWeight.w800, color: Color(0xFF34D399), letterSpacing: 0.5),
                                    ),
                                    Text(
                                      _formatRp(_totalEstimatedReward),
                                      style: const TextStyle(fontSize: 16, fontWeight: FontWeight.w900, color: Color(0xFF34D399)),
                                    ),
                                  ],
                                ),
                              ],
                            ),
                          ),

                          // 5. Catatan Tambahan
                          TextField(
                            controller: _catatanCtrl,
                            maxLines: 2,
                            decoration: InputDecoration(
                              labelText: 'Catatan Penitipan (Opsional)',
                              hintText: 'Tuliskan perjanjian stok atau keterangan khusus...',
                              filled: true,
                              fillColor: Colors.white,
                              border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
                            ),
                          ),

                          const SizedBox(height: 24),

                          // 6. Tombol Simpan
                          SizedBox(
                            width: double.infinity,
                            height: 52,
                            child: ElevatedButton.icon(
                              onPressed: _submit,
                              icon: const Icon(Icons.check_circle_outline_rounded),
                              label: const Text('Simpan Penitipan Barang', style: TextStyle(fontWeight: FontWeight.w800, fontSize: 15)),
                              style: ElevatedButton.styleFrom(
                                backgroundColor: const Color(0xFF0F172A),
                                foregroundColor: Colors.white,
                                elevation: 3,
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
