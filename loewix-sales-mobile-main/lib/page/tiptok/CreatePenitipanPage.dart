import 'package:flutter/material.dart';
import 'package:intl/intl.dart';
import 'package:quickalert/quickalert.dart';
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

  _ItemRow();

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

  Color _getCategoryBg(String cat) {
    if (cat.contains('OUTDOOR')) return const Color(0xFFFFF7ED); // Warm orange tint
    if (cat.contains('IPCAM')) return const Color(0xFFFAF5FF); // Purple tint
    return const Color(0xFFEFF6FF); // Blue tint
  }

  Color _getCategoryBorder(String cat) {
    if (cat.contains('OUTDOOR')) return const Color(0xFFFED7AA);
    if (cat.contains('IPCAM')) return const Color(0xFFE9D5FF);
    return const Color(0xFFBFDBFE);
  }

  Color _getCategoryText(String cat) {
    if (cat.contains('OUTDOOR')) return const Color(0xFFC2410C);
    if (cat.contains('IPCAM')) return const Color(0xFF7E22CE);
    return const Color(0xFF1D4ED8);
  }

  IconData _getCategoryIcon(String cat) {
    if (cat.contains('OUTDOOR')) return Icons.wb_sunny_rounded;
    if (cat.contains('IPCAM')) return Icons.wifi_tethering_rounded;
    return Icons.home_rounded;
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
      backgroundColor: Colors.white,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(24)),
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
                  padding: const EdgeInsets.fromLTRB(20, 14, 20, 20),
                  child: Column(
                    children: [
                      Container(
                        width: 44,
                        height: 5,
                        decoration: BoxDecoration(
                          color: const Color(0xFFCBD5E1),
                          borderRadius: BorderRadius.circular(3),
                        ),
                      ),
                      const SizedBox(height: 16),
                      const Text(
                        'Pilih Toko Sesuai Jadwal Kunjungan',
                        style: TextStyle(fontSize: 17, fontWeight: FontWeight.w800, color: Color(0xFF0F172A)),
                      ),
                      const SizedBox(height: 4),
                      const Text(
                        'Hanya toko yang dijadwalkan oleh Admin yang dapat dititipkan barang.',
                        style: TextStyle(fontSize: 12, color: Color(0xFF64748B)),
                        textAlign: TextAlign.center,
                      ),
                      const SizedBox(height: 16),
                      TextField(
                        controller: searchCtrl,
                        decoration: InputDecoration(
                          hintText: 'Cari nama toko, alamat...',
                          prefixIcon: const Icon(Icons.search_rounded, color: Color(0xFF64748B)),
                          filled: true,
                          fillColor: const Color(0xFFF1F5F9),
                          contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
                          border: OutlineInputBorder(
                            borderRadius: BorderRadius.circular(14),
                            borderSide: BorderSide.none,
                          ),
                        ),
                        onChanged: (val) {
                          setModalState(() {
                            final q = val.toLowerCase().trim();
                            if (q.isEmpty) {
                              dealerList = List.from(_dealersToday);
                            } else {
                              dealerList = _dealersToday.where((d) {
                                final n = (d['nama'] ?? '').toString().toLowerCase();
                                final a = (d['alamat'] ?? '').toString().toLowerCase();
                                return n.contains(q) || a.contains(q);
                              }).toList();
                            }
                          });
                        },
                      ),
                      const SizedBox(height: 12),
                      Expanded(
                        child: dealerList.isEmpty
                            ? Center(
                                child: Column(
                                  mainAxisAlignment: MainAxisAlignment.center,
                                  children: [
                                    Container(
                                      padding: const EdgeInsets.all(16),
                                      decoration: const BoxDecoration(
                                        color: Color(0xFFF1F5F9),
                                        shape: BoxShape.circle,
                                      ),
                                      child: const Icon(Icons.storefront_outlined, size: 36, color: Color(0xFF94A3B8)),
                                    ),
                                    const SizedBox(height: 12),
                                    const Text(
                                      'Tidak ada jadwal toko yang cocok',
                                      style: TextStyle(fontSize: 14, fontWeight: FontWeight.w700, color: Color(0xFF334155)),
                                    ),
                                  ],
                                ),
                              )
                            : ListView.separated(
                                controller: scrollCtrl,
                                itemCount: dealerList.length,
                                separatorBuilder: (_, __) => const SizedBox(height: 8),
                                itemBuilder: (context, i) {
                                  final d = dealerList[i];
                                  final isSel = (_selectedCustomerId != null &&
                                      _selectedCustomerId.toString() == d['id'].toString());

                                  return Material(
                                    color: Colors.transparent,
                                    child: InkWell(
                                      borderRadius: BorderRadius.circular(14),
                                      onTap: () {
                                        setState(() {
                                          _selectedCustomerId = int.tryParse(d['id'].toString());
                                          _selectedCustomerName = d['nama']?.toString();
                                        });
                                        Navigator.pop(ctx);
                                      },
                                      child: Container(
                                        padding: const EdgeInsets.all(14),
                                        decoration: BoxDecoration(
                                          color: isSel ? const Color(0xFFF0FDF4) : const Color(0xFFF8FAFC),
                                          borderRadius: BorderRadius.circular(14),
                                          border: Border.all(
                                            color: isSel ? const Color(0xFF10B981) : const Color(0xFFE2E8F0),
                                            width: isSel ? 1.5 : 1,
                                          ),
                                        ),
                                        child: Row(
                                          children: [
                                            Container(
                                              padding: const EdgeInsets.all(10),
                                              decoration: BoxDecoration(
                                                color: isSel ? const Color(0xFFDCFCE7) : const Color(0xFFE2E8F0),
                                                borderRadius: BorderRadius.circular(10),
                                              ),
                                              child: Icon(
                                                Icons.store_rounded,
                                                color: isSel ? const Color(0xFF15803D) : const Color(0xFF475569),
                                                size: 20,
                                              ),
                                            ),
                                            const SizedBox(width: 12),
                                            Expanded(
                                              child: Column(
                                                crossAxisAlignment: CrossAxisAlignment.start,
                                                children: [
                                                  Text(
                                                    d['nama'] ?? '-',
                                                    style: TextStyle(
                                                      fontSize: 14,
                                                      fontWeight: FontWeight.w800,
                                                      color: isSel ? const Color(0xFF15803D) : const Color(0xFF0F172A),
                                                    ),
                                                  ),
                                                  if (d['alamat'] != null && d['alamat'].toString().isNotEmpty) ...[
                                                    const SizedBox(height: 2),
                                                    Text(
                                                      d['alamat'].toString(),
                                                      style: const TextStyle(fontSize: 11.5, color: Color(0xFF64748B)),
                                                      maxLines: 1,
                                                      overflow: TextOverflow.ellipsis,
                                                    ),
                                                  ],
                                                ],
                                              ),
                                            ),
                                            if (isSel)
                                              const Icon(Icons.check_circle_rounded, color: Color(0xFF10B981), size: 20),
                                          ],
                                        ),
                                      ),
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
      },
    );
  }

  Future<void> _pickDate() async {
    final picked = await showDatePicker(
      context: context,
      initialDate: _tglTitip,
      firstDate: DateTime(2020),
      lastDate: DateTime(2030),
      builder: (context, child) {
        return Theme(
          data: Theme.of(context).copyWith(
            colorScheme: const ColorScheme.light(
              primary: Color(0xFF0F172A),
              onPrimary: Colors.white,
              onSurface: Color(0xFF0F172A),
            ),
          ),
          child: child!,
        );
      },
    );
    if (picked != null) {
      setState(() => _tglTitip = picked);
    }
  }

  void _showOfficialCatalogDialog() {
    showDialog(
      context: context,
      builder: (ctx) {
        return Dialog(
          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
          child: Padding(
            padding: const EdgeInsets.all(20),
            child: Column(
              mainAxisSize: MainAxisSize.min,
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Row(
                  children: [
                    Container(
                      padding: const EdgeInsets.all(8),
                      decoration: BoxDecoration(
                        color: const Color(0xFFEFF6FF),
                        borderRadius: BorderRadius.circular(10),
                      ),
                      child: const Icon(Icons.verified_rounded, color: Color(0xFF2563EB), size: 22),
                    ),
                    const SizedBox(width: 12),
                    const Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(
                            'Katalog Resmi TIP TOK',
                            style: TextStyle(fontSize: 16, fontWeight: FontWeight.w800, color: Color(0xFF0F172A)),
                          ),
                          Text(
                            '6 Model Kamera CCTV Konsinyasi Loewix',
                            style: TextStyle(fontSize: 11.5, color: Color(0xFF64748B)),
                          ),
                        ],
                      ),
                    ),
                    IconButton(
                      icon: const Icon(Icons.close_rounded, size: 20, color: Color(0xFF64748B)),
                      onPressed: () => Navigator.pop(ctx),
                      visualDensity: VisualDensity.compact,
                    ),
                  ],
                ),
                const SizedBox(height: 16),
                Container(
                  padding: const EdgeInsets.all(12),
                  decoration: BoxDecoration(
                    color: const Color(0xFFECFDF5),
                    borderRadius: BorderRadius.circular(12),
                    border: Border.all(color: const Color(0xFFA7F3D0)),
                  ),
                  child: const Row(
                    children: [
                      Icon(Icons.monetization_on_rounded, color: Color(0xFF059669), size: 20),
                      SizedBox(width: 10),
                      Expanded(
                        child: Text(
                          'Insentif Sales: Tetap Rp 15.000 / unit untuk seluruh 6 model kamera.',
                          style: TextStyle(fontSize: 12, fontWeight: FontWeight.w700, color: Color(0xFF065F46)),
                        ),
                      ),
                    ],
                  ),
                ),
                const SizedBox(height: 14),
                ConstrainedBox(
                  constraints: BoxConstraints(maxHeight: MediaQuery.of(context).size.height * 0.45),
                  child: ListView.separated(
                    shrinkWrap: true,
                    itemCount: kOfficialTipTokProducts.length,
                    separatorBuilder: (_, __) => const SizedBox(height: 8),
                    itemBuilder: (context, i) {
                      final p = kOfficialTipTokProducts[i];
                      return Container(
                        padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
                        decoration: BoxDecoration(
                          color: const Color(0xFFF8FAFC),
                          borderRadius: BorderRadius.circular(12),
                          border: Border.all(color: const Color(0xFFE2E8F0)),
                        ),
                        child: Row(
                          children: [
                            Container(
                              padding: const EdgeInsets.all(8),
                              decoration: BoxDecoration(
                                color: _getCategoryBg(p.category),
                                borderRadius: BorderRadius.circular(8),
                              ),
                              child: Icon(_getCategoryIcon(p.category), size: 18, color: _getCategoryText(p.category)),
                            ),
                            const SizedBox(width: 10),
                            Expanded(
                              child: Column(
                                crossAxisAlignment: CrossAxisAlignment.start,
                                children: [
                                  Text(
                                    p.model,
                                    style: const TextStyle(fontSize: 13, fontWeight: FontWeight.w800, color: Color(0xFF0F172A)),
                                  ),
                                  Text(
                                    p.category,
                                    style: TextStyle(fontSize: 10.5, fontWeight: FontWeight.w700, color: _getCategoryText(p.category)),
                                  ),
                                  Text(
                                    'MSRP: ${_formatRp(p.msrp)}',
                                    style: const TextStyle(fontSize: 11, color: Color(0xFF64748B)),
                                  ),
                                ],
                              ),
                            ),
                            Container(
                              padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                              decoration: BoxDecoration(
                                color: const Color(0xFFDCFCE7),
                                borderRadius: BorderRadius.circular(6),
                              ),
                              child: const Text(
                                '+Rp 15.000',
                                style: TextStyle(fontSize: 11, fontWeight: FontWeight.w800, color: Color(0xFF15803D)),
                              ),
                            ),
                          ],
                        ),
                      );
                    },
                  ),
                ),
                const SizedBox(height: 16),
                SizedBox(
                  width: double.infinity,
                  height: 44,
                  child: ElevatedButton(
                    onPressed: () => Navigator.pop(ctx),
                    style: ElevatedButton.styleFrom(
                      backgroundColor: const Color(0xFF0F172A),
                      foregroundColor: Colors.white,
                      elevation: 0,
                      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                    ),
                    child: const Text('Tutup', style: TextStyle(fontWeight: FontWeight.w700, fontSize: 13.5)),
                  ),
                ),
              ],
            ),
          ),
        );
      },
    );
  }

  void _showModelPickerModal(int itemIndex) {
    String selectedCategoryFilter = 'SEMUA';

    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.white,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(24)),
      ),
      builder: (ctx) {
        return StatefulBuilder(
          builder: (context, setModalState) {
            final filteredProducts = kOfficialTipTokProducts.where((p) {
              if (selectedCategoryFilter == 'SEMUA') return true;
              return p.category.contains(selectedCategoryFilter);
            }).toList();

            return Container(
              padding: const EdgeInsets.fromLTRB(20, 14, 20, 24),
              child: Column(
                mainAxisSize: MainAxisSize.min,
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Center(
                    child: Container(
                      width: 44,
                      height: 5,
                      decoration: BoxDecoration(
                        color: const Color(0xFFCBD5E1),
                        borderRadius: BorderRadius.circular(3),
                      ),
                    ),
                  ),
                  const SizedBox(height: 16),
                  Row(
                    children: [
                      Container(
                        padding: const EdgeInsets.all(10),
                        decoration: BoxDecoration(
                          color: const Color(0xFFEFF6FF),
                          borderRadius: BorderRadius.circular(12),
                        ),
                        child: const Icon(Icons.videocam_rounded, color: Color(0xFF2563EB), size: 22),
                      ),
                      const SizedBox(width: 12),
                      const Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(
                              'Pilih Model Kamera Resmi TIP TOK',
                              style: TextStyle(fontSize: 16, fontWeight: FontWeight.w800, color: Color(0xFF0F172A)),
                            ),
                            Text(
                              'Program Konsinyasi Loewix (Insentif Rp 15.000 / unit)',
                              style: TextStyle(fontSize: 11.5, color: Color(0xFF64748B), fontWeight: FontWeight.w500),
                            ),
                          ],
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(height: 14),

                  // Category Filter Chips
                  SingleChildScrollView(
                    scrollDirection: Axis.horizontal,
                    child: Row(
                      children: [
                        _buildFilterChip('SEMUA', 'Semua (6)', selectedCategoryFilter, (val) {
                          setModalState(() => selectedCategoryFilter = val);
                        }),
                        const SizedBox(width: 8),
                        _buildFilterChip('INDOOR', 'AHD Indoor', selectedCategoryFilter, (val) {
                          setModalState(() => selectedCategoryFilter = val);
                        }),
                        const SizedBox(width: 8),
                        _buildFilterChip('OUTDOOR', 'AHD Outdoor', selectedCategoryFilter, (val) {
                          setModalState(() => selectedCategoryFilter = val);
                        }),
                        const SizedBox(width: 8),
                        _buildFilterChip('IPCAM', 'IP Camera', selectedCategoryFilter, (val) {
                          setModalState(() => selectedCategoryFilter = val);
                        }),
                      ],
                    ),
                  ),
                  const SizedBox(height: 14),

                  // Product Cards List
                  ...filteredProducts.map((p) {
                    final isSelected = (_items[itemIndex].selectedProduct?.id == p.id);
                    final catBg = _getCategoryBg(p.category);
                    final catBorder = _getCategoryBorder(p.category);
                    final catText = _getCategoryText(p.category);
                    final catIcon = _getCategoryIcon(p.category);

                    return Container(
                      margin: const EdgeInsets.only(bottom: 10),
                      decoration: BoxDecoration(
                        color: isSelected ? const Color(0xFFF0FDF4) : Colors.white,
                        borderRadius: BorderRadius.circular(16),
                        border: Border.all(
                          color: isSelected ? const Color(0xFF10B981) : const Color(0xFFE2E8F0),
                          width: isSelected ? 2 : 1,
                        ),
                        boxShadow: const [
                          BoxShadow(
                            color: Color(0x080F172A),
                            blurRadius: 6,
                            offset: Offset(0, 2),
                          )
                        ],
                      ),
                      child: Material(
                        color: Colors.transparent,
                        borderRadius: BorderRadius.circular(16),
                        child: InkWell(
                          borderRadius: BorderRadius.circular(16),
                          onTap: () {
                            setState(() {
                              _items[itemIndex].selectedProduct = p;
                            });
                            Navigator.pop(ctx);
                          },
                          child: Padding(
                            padding: const EdgeInsets.all(14),
                            child: Row(
                              children: [
                                Container(
                                  padding: const EdgeInsets.all(10),
                                  decoration: BoxDecoration(
                                    color: catBg,
                                    borderRadius: BorderRadius.circular(12),
                                    border: Border.all(color: catBorder, width: 0.8),
                                  ),
                                  child: Icon(catIcon, color: catText, size: 20),
                                ),
                                const SizedBox(width: 12),
                                Expanded(
                                  child: Column(
                                    crossAxisAlignment: CrossAxisAlignment.start,
                                    children: [
                                      Row(
                                        children: [
                                          Container(
                                            padding: const EdgeInsets.symmetric(horizontal: 7, vertical: 3),
                                            decoration: BoxDecoration(
                                              color: catBg,
                                              borderRadius: BorderRadius.circular(6),
                                              border: Border.all(color: catBorder, width: 0.6),
                                            ),
                                            child: Text(
                                              p.category,
                                              style: TextStyle(fontSize: 10, fontWeight: FontWeight.w800, color: catText),
                                            ),
                                          ),
                                          const SizedBox(width: 8),
                                          Text(
                                            p.model,
                                            style: const TextStyle(fontSize: 13.5, fontWeight: FontWeight.w800, color: Color(0xFF0F172A)),
                                          ),
                                        ],
                                      ),
                                      const SizedBox(height: 5),
                                      Text(
                                        p.description,
                                        style: const TextStyle(fontSize: 12, fontWeight: FontWeight.w500, color: Color(0xFF475569)),
                                        maxLines: 2,
                                        overflow: TextOverflow.ellipsis,
                                      ),
                                      const SizedBox(height: 6),
                                      Row(
                                        children: [
                                          Text(
                                            'MSRP ${_formatRp(p.msrp)}',
                                            style: const TextStyle(fontSize: 11.5, color: Color(0xFF64748B), fontWeight: FontWeight.w600),
                                          ),
                                          const Spacer(),
                                          Container(
                                            padding: const EdgeInsets.symmetric(horizontal: 7, vertical: 3),
                                            decoration: BoxDecoration(
                                              color: const Color(0xFFDCFCE7),
                                              borderRadius: BorderRadius.circular(6),
                                              border: Border.all(color: const Color(0xFFA7F3D0), width: 0.6),
                                            ),
                                            child: const Row(
                                              mainAxisSize: MainAxisSize.min,
                                              children: [
                                                Icon(Icons.monetization_on_rounded, size: 12, color: Color(0xFF15803D)),
                                                SizedBox(width: 3),
                                                Text(
                                                  '+Rp 15.000 / unit',
                                                  style: TextStyle(fontSize: 11, fontWeight: FontWeight.w800, color: Color(0xFF15803D)),
                                                ),
                                              ],
                                            ),
                                          ),
                                        ],
                                      ),
                                    ],
                                  ),
                                ),
                                const SizedBox(width: 8),
                                Container(
                                  width: 24,
                                  height: 24,
                                  decoration: BoxDecoration(
                                    shape: BoxShape.circle,
                                    color: isSelected ? const Color(0xFF10B981) : Colors.transparent,
                                    border: Border.all(
                                      color: isSelected ? const Color(0xFF10B981) : const Color(0xFFCBD5E1),
                                      width: isSelected ? 0 : 2,
                                    ),
                                  ),
                                  child: isSelected
                                      ? const Icon(Icons.check, size: 16, color: Colors.white)
                                      : null,
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
      },
    );
  }

  Widget _buildFilterChip(String key, String label, String currentVal, Function(String) onSelect) {
    final isSel = (currentVal == key);
    return InkWell(
      onTap: () => onSelect(key),
      borderRadius: BorderRadius.circular(20),
      child: Container(
        padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
        decoration: BoxDecoration(
          color: isSel ? const Color(0xFF0F172A) : const Color(0xFFF1F5F9),
          borderRadius: BorderRadius.circular(20),
        ),
        child: Text(
          label,
          style: TextStyle(
            fontSize: 11.5,
            fontWeight: isSel ? FontWeight.w800 : FontWeight.w600,
            color: isSel ? Colors.white : const Color(0xFF475569),
          ),
        ),
      ),
    );
  }

  Future<void> _submit() async {
    if (_selectedCustomerId == null || _selectedCustomerId == 0) {
      QuickAlert.show(
        context: context,
        type: QuickAlertType.error,
        title: 'Toko Belum Dipilih',
        text: 'Silakan pilih toko yang Anda kunjungi hari ini sesuai jadwal resmi Admin.',
      );
      return;
    }

    final validItems = _items.where((it) => it.selectedProduct != null && it.qty > 0).toList();
    if (validItems.isEmpty) {
      QuickAlert.show(
        context: context,
        type: QuickAlertType.warning,
        title: 'Barang Belum Lengkap',
        text: 'Pilih minimal 1 dari 6 model kamera resmi Loewix TIP TOK dengan jumlah minimal 1 unit.',
      );
      return;
    }

    setState(() => _isLoading = true);

    try {
      final itemsPayload = validItems.map((it) {
        final p = it.selectedProduct!;
        return {
          'kategori': p.category,
          'type_barang': p.type,
          'tipe_barang': p.type,
          'nama_barang': p.description,
          'qty': it.qty,
          'qty_titip': it.qty,
          'insentif': p.insentif,
          'insentif_per_unit': p.insentif,
        };
      }).toList();

      final res = await _api.createPenitipan(
        idCustomer: _selectedCustomerId!,
        idSales: widget.salesId,
        namaSales: widget.namaSales,
        tglTitip: DateFormat('yyyy-MM-dd').format(_tglTitip),
        catatan: _catatanCtrl.text.trim(),
        items: itemsPayload,
      );

      setState(() => _isLoading = false);

      if (!mounted) return;

      final isSuccess = (res['status'] == 'success' || res['success'] == true || res['status'] == true);
      if (isSuccess) {
        await QuickAlert.show(
          context: context,
          type: QuickAlertType.success,
          title: 'Penitipan Berhasil',
          text: 'Data penitipan barang telah tersimpan ke sistem TIP TOK.',
          confirmBtnText: 'OK',
          onConfirmBtnTap: () {
            Navigator.pop(context);
            Navigator.pop(context, true);
          },
        );
      } else {
        QuickAlert.show(
          context: context,
          type: QuickAlertType.error,
          title: 'Gagal Menyimpan',
          text: res['message'] ?? 'Terjadi kesalahan sistem saat menyimpan data.',
        );
      }
    } catch (e) {
      setState(() => _isLoading = false);
      if (!mounted) return;
      QuickAlert.show(
        context: context,
        type: QuickAlertType.error,
        title: 'Koneksi Bermasalah',
        text: 'Gagal menghubungi server. Silakan coba kembali: $e',
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xFFF8FAFC),
      appBar: AppBar(
        backgroundColor: Colors.white,
        elevation: 0,
        scrolledUnderElevation: 1,
        titleSpacing: 0,
        leading: IconButton(
          icon: const Icon(Icons.arrow_back_rounded, color: Color(0xFF0F172A)),
          onPressed: () => Navigator.pop(context),
        ),
        title: const Text(
          'Titip Barang Baru (TIP TOK)',
          style: TextStyle(fontSize: 17, fontWeight: FontWeight.w800, color: Color(0xFF0F172A)),
        ),
        actions: [
          IconButton(
            icon: const Icon(Icons.menu_book_rounded, color: Color(0xFF2563EB), size: 22),
            tooltip: 'Katalog 6 Model Resmi',
            onPressed: _showOfficialCatalogDialog,
          ),
          const SizedBox(width: 4),
        ],
      ),
      body: _isLoading
          ? const Center(
              child: Column(
                mainAxisAlignment: MainAxisAlignment.center,
                children: [
                  CircularProgressIndicator(color: Color(0xFF0F172A)),
                  SizedBox(height: 16),
                  Text('Menyimpan data penitipan...', style: TextStyle(fontWeight: FontWeight.w700, color: Color(0xFF475569))),
                ],
              ),
            )
          : _isCheckingSchedule
              ? const Center(
                  child: CircularProgressIndicator(color: Color(0xFF0F172A)),
                )
              : SingleChildScrollView(
                  padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 16),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      // 1. TOKO / DEALER MITRA CARD
                      const Text(
                        'Toko / Dealer Mitra *',
                        style: TextStyle(fontSize: 13, fontWeight: FontWeight.w800, color: Color(0xFF334155)),
                      ),
                      const SizedBox(height: 8),
                      InkWell(
                        onTap: _pickDealer,
                        borderRadius: BorderRadius.circular(16),
                        child: Container(
                          padding: const EdgeInsets.all(16),
                          decoration: BoxDecoration(
                            color: Colors.white,
                            borderRadius: BorderRadius.circular(16),
                            border: Border.all(
                              color: _selectedCustomerId != null ? const Color(0xFF10B981) : const Color(0xFFCBD5E1),
                              width: 1.2,
                            ),
                            boxShadow: const [
                              BoxShadow(
                                color: Color(0x080F172A),
                                blurRadius: 8,
                                offset: Offset(0, 2),
                              )
                            ],
                          ),
                          child: Row(
                            children: [
                              Container(
                                padding: const EdgeInsets.all(10),
                                decoration: BoxDecoration(
                                  color: _selectedCustomerId != null ? const Color(0xFFECFDF5) : const Color(0xFFF1F5F9),
                                  borderRadius: BorderRadius.circular(12),
                                ),
                                child: Icon(
                                  _selectedCustomerId != null ? Icons.verified_rounded : Icons.storefront_rounded,
                                  color: _selectedCustomerId != null ? const Color(0xFF10B981) : const Color(0xFF64748B),
                                  size: 22,
                                ),
                              ),
                              const SizedBox(width: 14),
                              Expanded(
                                child: Column(
                                  crossAxisAlignment: CrossAxisAlignment.start,
                                  children: [
                                    Text(
                                      _selectedCustomerName ?? 'Pilih Toko Jadwal Kunjungan...',
                                      style: TextStyle(
                                        fontSize: 14.5,
                                        fontWeight: FontWeight.w800,
                                        color: _selectedCustomerId != null ? const Color(0xFF0F172A) : const Color(0xFF94A3B8),
                                      ),
                                    ),
                                    const SizedBox(height: 3),
                                    Row(
                                      children: [
                                        Icon(
                                          _selectedCustomerId != null ? Icons.check_circle_rounded : Icons.schedule_rounded,
                                          size: 13,
                                          color: _selectedCustomerId != null ? const Color(0xFF059669) : const Color(0xFF64748B),
                                        ),
                                        const SizedBox(width: 4),
                                        Text(
                                          _selectedCustomerId != null ? 'Sesuai Jadwal Kunjungan Resmi Hari Ini' : 'Wajib sesuai jadwal kunjungan Admin',
                                          style: TextStyle(
                                            fontSize: 11,
                                            fontWeight: FontWeight.w600,
                                            color: _selectedCustomerId != null ? const Color(0xFF059669) : const Color(0xFF64748B),
                                          ),
                                        ),
                                      ],
                                    ),
                                  ],
                                ),
                              ),
                              Icon(
                                (widget.preselectedCustomerId != null || _dealersToday.length == 1)
                                    ? Icons.lock_outline_rounded
                                    : Icons.chevron_right_rounded,
                                color: const Color(0xFF94A3B8),
                                size: 20,
                              ),
                            ],
                          ),
                        ),
                      ),

                      const SizedBox(height: 16),

                      // 2. TANGGAL PENITIPAN
                      const Text(
                        'Tanggal Penitipan *',
                        style: TextStyle(fontSize: 13, fontWeight: FontWeight.w800, color: Color(0xFF334155)),
                      ),
                      const SizedBox(height: 8),
                      InkWell(
                        onTap: _pickDate,
                        borderRadius: BorderRadius.circular(16),
                        child: Container(
                          padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 14),
                          decoration: BoxDecoration(
                            color: Colors.white,
                            borderRadius: BorderRadius.circular(16),
                            border: Border.all(color: const Color(0xFFCBD5E1), width: 1.2),
                            boxShadow: const [
                              BoxShadow(
                                color: Color(0x080F172A),
                                blurRadius: 8,
                                offset: Offset(0, 2),
                              )
                            ],
                          ),
                          child: Row(
                            children: [
                              Container(
                                padding: const EdgeInsets.all(8),
                                decoration: BoxDecoration(
                                  color: const Color(0xFFF1F5F9),
                                  borderRadius: BorderRadius.circular(10),
                                ),
                                child: const Icon(Icons.calendar_today_rounded, size: 18, color: Color(0xFF334155)),
                              ),
                              const SizedBox(width: 12),
                              Expanded(
                                child: Text(
                                  DateFormat('dd MMMM yyyy', 'id').format(_tglTitip),
                                  style: const TextStyle(fontSize: 14, fontWeight: FontWeight.w700, color: Color(0xFF0F172A)),
                                ),
                              ),
                              const Icon(Icons.edit_calendar_outlined, size: 18, color: Color(0xFF64748B)),
                            ],
                          ),
                        ),
                      ),

                      const SizedBox(height: 24),

                      // 3. DAFTAR BARANG HEADER
                      Row(
                        mainAxisAlignment: MainAxisAlignment.spaceBetween,
                        children: [
                          const Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Text(
                                'Daftar Barang Dititipkan',
                                style: TextStyle(fontSize: 15, fontWeight: FontWeight.w800, color: Color(0xFF0F172A)),
                              ),
                              SizedBox(height: 2),
                              Text(
                                'Pilih dari 6 model kamera resmi berinsentif',
                                style: TextStyle(fontSize: 11.5, color: Color(0xFF64748B)),
                              ),
                            ],
                          ),
                          InkWell(
                            onTap: _addItem,
                            borderRadius: BorderRadius.circular(10),
                            child: Container(
                              padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
                              decoration: BoxDecoration(
                                color: const Color(0xFFEFF6FF),
                                borderRadius: BorderRadius.circular(10),
                                border: Border.all(color: const Color(0xFFBFDBFE)),
                              ),
                              child: const Row(
                                mainAxisSize: MainAxisSize.min,
                                children: [
                                  Icon(Icons.add_circle_outline_rounded, size: 16, color: Color(0xFF2563EB)),
                                  SizedBox(width: 6),
                                  Text(
                                    'Tambah Baris',
                                    style: TextStyle(fontSize: 12, fontWeight: FontWeight.w800, color: Color(0xFF2563EB)),
                                  ),
                                ],
                              ),
                            ),
                          ),
                        ],
                      ),
                      const SizedBox(height: 12),

                      // ITEM CARDS LIST
                      ListView.separated(
                        shrinkWrap: true,
                        physics: const NeverScrollableScrollPhysics(),
                        itemCount: _items.length,
                        separatorBuilder: (_, __) => const SizedBox(height: 12),
                        itemBuilder: (context, i) {
                          final item = _items[i];
                          final p = item.selectedProduct;

                          return Container(
                            decoration: BoxDecoration(
                              color: Colors.white,
                              borderRadius: BorderRadius.circular(16),
                              border: Border.all(
                                color: p != null ? const Color(0xFFCBD5E1) : const Color(0xFFE2E8F0),
                                width: 1.2,
                              ),
                              boxShadow: const [
                                BoxShadow(
                                  color: Color(0x080F172A),
                                  blurRadius: 8,
                                  offset: Offset(0, 2),
                                )
                              ],
                            ),
                            padding: const EdgeInsets.all(16),
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                // Header bar of item card
                                Row(
                                  children: [
                                    Container(
                                      padding: const EdgeInsets.symmetric(horizontal: 9, vertical: 4),
                                      decoration: BoxDecoration(
                                        color: const Color(0xFF0F172A),
                                        borderRadius: BorderRadius.circular(8),
                                      ),
                                      child: Text(
                                        'Item #${i + 1}',
                                        style: const TextStyle(fontSize: 11, fontWeight: FontWeight.w800, color: Colors.white),
                                      ),
                                    ),
                                    const Spacer(),
                                    if (_items.length > 1)
                                      InkWell(
                                        onTap: () => _removeItem(i),
                                        borderRadius: BorderRadius.circular(8),
                                        child: Container(
                                          padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                                          decoration: BoxDecoration(
                                            color: const Color(0xFFFEE2E2),
                                            borderRadius: BorderRadius.circular(8),
                                          ),
                                          child: const Row(
                                            children: [
                                              Icon(Icons.delete_outline_rounded, size: 14, color: Color(0xFFDC2626)),
                                              SizedBox(width: 4),
                                              Text(
                                                'Hapus',
                                                style: TextStyle(fontSize: 11, fontWeight: FontWeight.w700, color: Color(0xFFDC2626)),
                                              ),
                                            ],
                                          ),
                                        ),
                                      ),
                                  ],
                                ),

                                const SizedBox(height: 14),

                                // Product Selector Area
                                InkWell(
                                  onTap: () => _showModelPickerModal(i),
                                  borderRadius: BorderRadius.circular(14),
                                  child: Container(
                                    padding: const EdgeInsets.all(12),
                                    decoration: BoxDecoration(
                                      color: p == null ? const Color(0xFFF8FAFC) : _getCategoryBg(p.category),
                                      borderRadius: BorderRadius.circular(14),
                                      border: Border.all(
                                        color: p == null ? const Color(0xFFCBD5E1) : _getCategoryBorder(p.category),
                                        width: 1.2,
                                      ),
                                    ),
                                    child: Row(
                                      children: [
                                        Container(
                                          padding: const EdgeInsets.all(10),
                                          decoration: BoxDecoration(
                                            color: p == null ? const Color(0xFFE2E8F0) : Colors.white,
                                            borderRadius: BorderRadius.circular(10),
                                          ),
                                          child: Icon(
                                            p == null ? Icons.videocam_outlined : _getCategoryIcon(p.category),
                                            color: p == null ? const Color(0xFF64748B) : _getCategoryText(p.category),
                                            size: 22,
                                          ),
                                        ),
                                        const SizedBox(width: 12),
                                        Expanded(
                                          child: p == null
                                              ? const Column(
                                                  crossAxisAlignment: CrossAxisAlignment.start,
                                                  children: [
                                                    Text(
                                                      'Pilih Model Kamera Resmi TIP TOK',
                                                      style: TextStyle(fontSize: 13.5, fontWeight: FontWeight.w700, color: Color(0xFF334155)),
                                                    ),
                                                    SizedBox(height: 2),
                                                    Text(
                                                      'Ketuk untuk memilih 1 dari 6 model kamera',
                                                      style: TextStyle(fontSize: 11, color: Color(0xFF94A3B8)),
                                                    ),
                                                  ],
                                                )
                                              : Column(
                                                  crossAxisAlignment: CrossAxisAlignment.start,
                                                  children: [
                                                    Row(
                                                      children: [
                                                        Container(
                                                          padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
                                                          decoration: BoxDecoration(
                                                            color: Colors.white,
                                                            borderRadius: BorderRadius.circular(4),
                                                            border: Border.all(color: _getCategoryBorder(p.category), width: 0.5),
                                                          ),
                                                          child: Text(
                                                            p.category,
                                                            style: TextStyle(fontSize: 9.5, fontWeight: FontWeight.w800, color: _getCategoryText(p.category)),
                                                          ),
                                                        ),
                                                        const SizedBox(width: 6),
                                                        Text(
                                                          p.model,
                                                          style: const TextStyle(fontSize: 13.5, fontWeight: FontWeight.w800, color: Color(0xFF0F172A)),
                                                        ),
                                                      ],
                                                    ),
                                                    const SizedBox(height: 4),
                                                    Text(
                                                      p.description,
                                                      style: const TextStyle(fontSize: 11.5, fontWeight: FontWeight.w600, color: Color(0xFF475569)),
                                                      maxLines: 2,
                                                      overflow: TextOverflow.ellipsis,
                                                    ),
                                                  ],
                                                ),
                                        ),
                                        const SizedBox(width: 8),
                                        Container(
                                          padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 5),
                                          decoration: BoxDecoration(
                                            color: Colors.white,
                                            borderRadius: BorderRadius.circular(8),
                                            border: Border.all(color: const Color(0xFFE2E8F0)),
                                          ),
                                          child: Text(
                                            p == null ? 'Pilih' : 'Ganti',
                                            style: const TextStyle(fontSize: 11.5, fontWeight: FontWeight.w800, color: Color(0xFF2563EB)),
                                          ),
                                        ),
                                      ],
                                    ),
                                  ),
                                ),

                                if (p != null) ...[
                                  const SizedBox(height: 14),

                                  // Row: Stepper Qty & Insentif Per Unit
                                  Row(
                                    children: [
                                      // Quantity Stepper
                                      Expanded(
                                        flex: 5,
                                        child: Column(
                                          crossAxisAlignment: CrossAxisAlignment.start,
                                          children: [
                                            const Text(
                                              'Jumlah Titip (Unit)',
                                              style: TextStyle(fontSize: 11.5, fontWeight: FontWeight.w700, color: Color(0xFF475569)),
                                            ),
                                            const SizedBox(height: 6),
                                            Container(
                                              height: 42,
                                              decoration: BoxDecoration(
                                                color: const Color(0xFFF8FAFC),
                                                borderRadius: BorderRadius.circular(10),
                                                border: Border.all(color: const Color(0xFFCBD5E1)),
                                              ),
                                              child: Row(
                                                children: [
                                                  IconButton(
                                                    icon: const Icon(Icons.remove_rounded, size: 18),
                                                    onPressed: () {
                                                      int current = item.qty;
                                                      if (current > 1) {
                                                        setState(() {
                                                          item.qtyCtrl.text = '${current - 1}';
                                                        });
                                                      }
                                                    },
                                                    visualDensity: VisualDensity.compact,
                                                    color: const Color(0xFF334155),
                                                  ),
                                                  Expanded(
                                                    child: TextField(
                                                      controller: item.qtyCtrl,
                                                      keyboardType: TextInputType.number,
                                                      textAlign: TextAlign.center,
                                                      style: const TextStyle(fontSize: 14, fontWeight: FontWeight.w900, color: Color(0xFF0F172A)),
                                                      decoration: const InputDecoration(
                                                        border: InputBorder.none,
                                                        contentPadding: EdgeInsets.zero,
                                                        isDense: true,
                                                      ),
                                                      onChanged: (_) => setState(() {}),
                                                    ),
                                                  ),
                                                  IconButton(
                                                    icon: const Icon(Icons.add_rounded, size: 18),
                                                    onPressed: () {
                                                      int current = item.qty;
                                                      setState(() {
                                                        item.qtyCtrl.text = '${current + 1}';
                                                      });
                                                    },
                                                    visualDensity: VisualDensity.compact,
                                                    color: const Color(0xFF334155),
                                                  ),
                                                ],
                                              ),
                                            ),
                                          ],
                                        ),
                                      ),

                                      const SizedBox(width: 12),

                                      // Insentif per unit badge
                                      Expanded(
                                        flex: 5,
                                        child: Column(
                                          crossAxisAlignment: CrossAxisAlignment.start,
                                          children: [
                                            const Text(
                                              'Insentif / Unit',
                                              style: TextStyle(fontSize: 11.5, fontWeight: FontWeight.w700, color: Color(0xFF475569)),
                                            ),
                                            const SizedBox(height: 6),
                                            Container(
                                              height: 42,
                                              padding: const EdgeInsets.symmetric(horizontal: 10),
                                              alignment: Alignment.centerLeft,
                                              decoration: BoxDecoration(
                                                color: const Color(0xFFF1F5F9),
                                                borderRadius: BorderRadius.circular(10),
                                                border: Border.all(color: const Color(0xFFCBD5E1)),
                                              ),
                                              child: Row(
                                                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                                                children: [
                                                  Text(
                                                    _formatRp(p.insentif),
                                                    style: const TextStyle(fontSize: 13, fontWeight: FontWeight.w800, color: Color(0xFF0F172A)),
                                                  ),
                                                  const Icon(Icons.lock_rounded, size: 14, color: Color(0xFF64748B)),
                                                ],
                                              ),
                                            ),
                                          ],
                                        ),
                                      ),
                                    ],
                                  ),

                                  const SizedBox(height: 12),

                                  // Subtotal Reward Banner for this item
                                  Container(
                                    padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
                                    decoration: BoxDecoration(
                                      color: const Color(0xFFECFDF5),
                                      borderRadius: BorderRadius.circular(10),
                                      border: Border.all(color: const Color(0xFFA7F3D0)),
                                    ),
                                    child: Row(
                                      mainAxisAlignment: MainAxisAlignment.spaceBetween,
                                      children: [
                                        Row(
                                          children: [
                                            const Icon(Icons.monetization_on_rounded, size: 15, color: Color(0xFF059669)),
                                            const SizedBox(width: 6),
                                            Text(
                                              'Subtotal Reward (${item.qty} unit):',
                                              style: const TextStyle(fontSize: 11.5, fontWeight: FontWeight.w700, color: Color(0xFF047857)),
                                            ),
                                          ],
                                        ),
                                        Text(
                                          _formatRp(item.subtotalReward),
                                          style: const TextStyle(fontSize: 13.5, fontWeight: FontWeight.w900, color: Color(0xFF059669)),
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

                      const SizedBox(height: 16),

                      // 4. SUMMARY ESTIMASI CARD
                      Container(
                        padding: const EdgeInsets.all(16),
                        decoration: BoxDecoration(
                          color: const Color(0xFF0F172A),
                          borderRadius: BorderRadius.circular(18),
                          boxShadow: const [
                            BoxShadow(
                              color: Color(0x2E0F172A),
                              blurRadius: 12,
                              offset: Offset(0, 4),
                            )
                          ],
                        ),
                        child: Row(
                          children: [
                            Container(
                              width: 44,
                              height: 44,
                              decoration: BoxDecoration(
                                color: const Color(0x1AFFFFFF),
                                borderRadius: BorderRadius.circular(12),
                              ),
                              child: const Icon(Icons.calculate_rounded, color: Colors.white, size: 22),
                            ),
                            const SizedBox(width: 14),
                            Expanded(
                              child: Column(
                                crossAxisAlignment: CrossAxisAlignment.start,
                                children: [
                                  const Text(
                                    'TOTAL TITIP FISIK',
                                    style: TextStyle(fontSize: 10, fontWeight: FontWeight.w800, color: Color(0xFF94A3B8), letterSpacing: 0.5),
                                  ),
                                  const SizedBox(height: 2),
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
                                const SizedBox(height: 2),
                                Text(
                                  _formatRp(_totalEstimatedReward),
                                  style: const TextStyle(fontSize: 16.5, fontWeight: FontWeight.w900, color: Color(0xFF34D399)),
                                ),
                              ],
                            ),
                          ],
                        ),
                      ),

                      const SizedBox(height: 16),

                      // 5. Catatan Tambahan
                      const Text(
                        'Catatan Penitipan (Opsional)',
                        style: TextStyle(fontSize: 13, fontWeight: FontWeight.w800, color: Color(0xFF334155)),
                      ),
                      const SizedBox(height: 8),
                      TextField(
                        controller: _catatanCtrl,
                        maxLines: 2,
                        decoration: InputDecoration(
                          hintText: 'Tuliskan nomor seri, perjanjian stok, atau keterangan khusus...',
                          hintStyle: const TextStyle(fontSize: 12, color: Color(0xFF94A3B8)),
                          filled: true,
                          fillColor: Colors.white,
                          contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
                          border: OutlineInputBorder(
                            borderRadius: BorderRadius.circular(14),
                            borderSide: const BorderSide(color: Color(0xFFCBD5E1), width: 1.2),
                          ),
                          enabledBorder: OutlineInputBorder(
                            borderRadius: BorderRadius.circular(14),
                            borderSide: const BorderSide(color: Color(0xFFE2E8F0), width: 1.2),
                          ),
                          focusedBorder: OutlineInputBorder(
                            borderRadius: BorderRadius.circular(14),
                            borderSide: const BorderSide(color: Color(0xFF0F172A), width: 1.5),
                          ),
                        ),
                      ),

                      const SizedBox(height: 24),

                      // 6. Tombol Simpan
                      SizedBox(
                        width: double.infinity,
                        height: 52,
                        child: ElevatedButton.icon(
                          onPressed: _submit,
                          icon: const Icon(Icons.check_circle_rounded, size: 20),
                          label: const Text('Simpan Penitipan Barang', style: TextStyle(fontWeight: FontWeight.w800, fontSize: 15)),
                          style: ElevatedButton.styleFrom(
                            backgroundColor: const Color(0xFF0F172A),
                            foregroundColor: Colors.white,
                            elevation: 2,
                            shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
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
