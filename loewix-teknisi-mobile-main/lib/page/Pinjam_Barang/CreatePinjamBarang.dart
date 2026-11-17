import 'dart:math';

import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:iconsax/iconsax.dart';
import 'package:provider/provider.dart';
import 'package:quickalert/quickalert.dart';

import '../../service/provider/Barang/BarangGetProvider.dart';
import '../../service/provider/Pinjam/PinjamGetProvider.dart';
import '../../service/provider/Pinjam/PinjamSendProvider.dart';
import '../../service/provider/preferences/PreferencesIDProvider.dart';
import '../../utils/state.dart';

class PinjamBarangPage extends StatefulWidget {
  static const routeName = '/create_pinjam_page';
  const PinjamBarangPage({super.key});

  @override
  State<PinjamBarangPage> createState() => _PinjamBarangPageState();
}

class _PinjamBarangPageState extends State<PinjamBarangPage> {
  // ─── Parclgo Inspired Palette (Matching Gambar 5) ───
  static const Color _darkHeader = Color(0xFF11141B);
  static const Color _darkSurface = Color(0xFF1E2330);
  static const Color _primaryOrange = Color(0xFFFF5B22);
  static const Color _bgCanvas = Color(0xFFF4F6F8);
  static const Color _successGreen = Color(0xFF059669);
  static const Color _errorRed = Color(0xFFE11D48);
  static const Color _textPrimary = Color(0xFF0F172A);
  static const Color _textSecondary = Color(0xFF64748B);

  static const List<String> _days = [
    'Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'
  ];

  static const List<String> _months = [
    'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
    'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
  ];

  final Map<int, int> _selectedItems = {};
  final Map<int, TextEditingController> _qtyControllers = {};
  String _id = '';
  DateTime? _selectedDate;
  final TextEditingController _tanggalPinjamController = TextEditingController();
  final TextEditingController _searchController = TextEditingController();
  String _searchQuery = '';
  bool _onlyAvailable = false;
  bool _isSubmitting = false;

  String _generateMixedCode() {
    const characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    final random = Random();
    return String.fromCharCodes(Iterable.generate(
      6,
      (_) => characters.codeUnitAt(random.nextInt(characters.length)),
    ));
  }

  String _formatIndonesianDate(DateTime dt) {
    final dayName = _days[dt.weekday % 7];
    final monthName = _months[dt.month - 1];
    return '$dayName, ${dt.day} $monthName ${dt.year}';
  }

  @override
  void initState() {
    super.initState();
    _id = context.read<PreferencesIDProvider>().isUserRole;
    final today = DateTime.now();
    _selectedDate = DateTime(today.year, today.month, today.day);
    _tanggalPinjamController.text =
        '${today.year}-${today.month.toString().padLeft(2, '0')}-${today.day.toString().padLeft(2, '0')}';
  }

  @override
  void dispose() {
    _tanggalPinjamController.dispose();
    _searchController.dispose();
    for (var controller in _qtyControllers.values) {
      controller.dispose();
    }
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return AnnotatedRegion<SystemUiOverlayStyle>(
      value: SystemUiOverlayStyle.light,
      child: Scaffold(
        backgroundColor: _bgCanvas,
        body: Column(
          children: [
            _buildParclgoHeader(),
            Expanded(
              child: Consumer<BarangGetProvider>(
                builder: (context, state, _) {
                  if (state.state == ResultState.loading) {
                    return const Center(
                      child: CircularProgressIndicator(color: _primaryOrange),
                    );
                  } else if (state.state == ResultState.hasData) {
                    final allItems = state.response.data;
                    final items = allItems.where((b) {
                      if (_onlyAvailable && b.stok <= 0 && !_selectedItems.containsKey(b.id)) {
                        return false;
                      }
                      if (_searchQuery.trim().isNotEmpty) {
                        final q = _searchQuery.toLowerCase().trim();
                        final name = b.namaBarang.toLowerCase();
                        final desc = b.deskripsi.toLowerCase();
                        return name.contains(q) || desc.contains(q);
                      }
                      return true;
                    }).toList();

                    return ListView(
                      physics: const BouncingScrollPhysics(),
                      padding: const EdgeInsets.fromLTRB(20, 18, 20, 24),
                      children: [
                        _buildDateCard(),
                        const SizedBox(height: 18),
                        _buildSectionHeader(allItems.length),
                        const SizedBox(height: 12),
                        if (items.isEmpty)
                          _buildEmptyFilterState()
                        else
                          ...items.map((barang) => _buildBarangCard(barang)),
                      ],
                    );
                  } else if (state.state == ResultState.noData) {
                    return _buildEmptyState('Tidak ada data barang');
                  } else if (state.state == ResultState.error) {
                    return _buildErrorState(state.message);
                  } else {
                    return _buildEmptyState('Tidak ada barang tersedia');
                  }
                },
              ),
            ),
            _buildBottomSubmitBar(),
          ],
        ),
      ),
    );
  }

  Widget _buildParclgoHeader() {
    final selectedCount = _selectedItems.length;

    return Container(
      decoration: const BoxDecoration(
        color: _darkHeader,
        borderRadius: BorderRadius.only(
          bottomLeft: Radius.circular(32),
          bottomRight: Radius.circular(32),
        ),
      ),
      child: SafeArea(
        bottom: false,
        child: Padding(
          padding: const EdgeInsets.fromLTRB(20, 12, 20, 22),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Row(
                children: [
                  GestureDetector(
                    onTap: () => Navigator.pop(context),
                    child: Container(
                      width: 44,
                      height: 44,
                      decoration: BoxDecoration(
                        color: _darkSurface,
                        shape: BoxShape.circle,
                        border: Border.all(
                          color: Colors.white.withValues(alpha: 0.1),
                        ),
                      ),
                      child: const Icon(
                        Icons.arrow_back_ios_new_rounded,
                        color: Colors.white,
                        size: 18,
                      ),
                    ),
                  ),
                  const SizedBox(width: 14),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          'Pengajuan Baru',
                          style: TextStyle(
                            fontFamily: 'Poppins',
                            fontSize: 11.5,
                            fontWeight: FontWeight.w500,
                            color: Colors.white.withValues(alpha: 0.65),
                          ),
                        ),
                        const Text(
                          'Pinjam Barang',
                          style: TextStyle(
                            fontFamily: 'Poppins',
                            fontSize: 19,
                            fontWeight: FontWeight.w700,
                            color: Colors.white,
                            letterSpacing: -0.2,
                          ),
                        ),
                      ],
                    ),
                  ),
                  Container(
                    padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
                    decoration: BoxDecoration(
                      color: selectedCount > 0 ? _primaryOrange : _darkSurface,
                      borderRadius: BorderRadius.circular(20),
                      border: Border.all(
                        color: selectedCount > 0
                            ? _primaryOrange
                            : Colors.white.withValues(alpha: 0.1),
                      ),
                    ),
                    child: Text(
                      '$selectedCount Dipilih',
                      style: const TextStyle(
                        fontFamily: 'Poppins',
                        fontSize: 11.5,
                        fontWeight: FontWeight.w700,
                        color: Colors.white,
                      ),
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 18),

              // ── White Search Pill Bar (Matching Gambar 5) ──
              Container(
                height: 48,
                decoration: BoxDecoration(
                  color: Colors.white,
                  borderRadius: BorderRadius.circular(24),
                  boxShadow: [
                    BoxShadow(
                      color: Colors.black.withValues(alpha: 0.12),
                      blurRadius: 10,
                      offset: const Offset(0, 4),
                    ),
                  ],
                ),
                padding: const EdgeInsets.symmetric(horizontal: 14),
                child: Row(
                  children: [
                    const Icon(
                      Icons.search_rounded,
                      color: _darkHeader,
                      size: 21,
                    ),
                    const SizedBox(width: 10),
                    Expanded(
                      child: TextField(
                        controller: _searchController,
                        onChanged: (val) => setState(() => _searchQuery = val),
                        style: const TextStyle(
                          fontFamily: 'Poppins',
                          fontSize: 13,
                          color: _textPrimary,
                        ),
                        decoration: const InputDecoration(
                          hintText: 'Cari obeng, tang, alat kerja...',
                          hintStyle: TextStyle(
                            fontFamily: 'Poppins',
                            fontSize: 13,
                            color: Color(0xFF94A3B8),
                          ),
                          border: InputBorder.none,
                          isDense: true,
                        ),
                      ),
                    ),
                    if (_searchQuery.isNotEmpty)
                      GestureDetector(
                        onTap: () {
                          _searchController.clear();
                          setState(() => _searchQuery = '');
                        },
                        child: const Icon(
                          Icons.close_rounded,
                          size: 18,
                          color: Color(0xFF94A3B8),
                        ),
                      ),
                  ],
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildDateCard() {
    final hasDate = _selectedDate != null;

    return GestureDetector(
      onTap: _selectDate,
      child: Container(
        padding: const EdgeInsets.all(16),
        decoration: BoxDecoration(
          color: Colors.white,
          borderRadius: BorderRadius.circular(20),
          border: Border.all(color: const Color(0xFFEDF1F5)),
          boxShadow: [
            BoxShadow(
              color: Colors.black.withValues(alpha: 0.04),
              blurRadius: 16,
              offset: const Offset(0, 6),
            ),
          ],
        ),
        child: Row(
          children: [
            Container(
              width: 46,
              height: 46,
              decoration: BoxDecoration(
                color: const Color(0xFFFFF2EC),
                borderRadius: BorderRadius.circular(14),
              ),
              child: const Icon(
                Iconsax.calendar_1,
                color: _primaryOrange,
                size: 22,
              ),
            ),
            const SizedBox(width: 14),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  const Text(
                    'Tanggal Pinjam',
                    style: TextStyle(
                      fontFamily: 'Poppins',
                      fontSize: 11.5,
                      fontWeight: FontWeight.w500,
                      color: _textSecondary,
                    ),
                  ),
                  const SizedBox(height: 2),
                  Text(
                    hasDate
                        ? _formatIndonesianDate(_selectedDate!)
                        : 'Pilih tanggal pinjam',
                    style: TextStyle(
                      fontFamily: 'Poppins',
                      fontSize: 14.5,
                      fontWeight: FontWeight.w700,
                      color: hasDate ? _textPrimary : _textSecondary,
                    ),
                  ),
                ],
              ),
            ),
            Container(
              padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
              decoration: BoxDecoration(
                color: const Color(0xFFF8FAFC),
                borderRadius: BorderRadius.circular(10),
                border: Border.all(color: const Color(0xFFE2E8F0)),
              ),
              child: const Row(
                mainAxisSize: MainAxisSize.min,
                children: [
                  Text(
                    'Ubah',
                    style: TextStyle(
                      fontFamily: 'Poppins',
                      fontSize: 11.5,
                      fontWeight: FontWeight.w600,
                      color: _primaryOrange,
                    ),
                  ),
                  SizedBox(width: 2),
                  Icon(
                    Icons.keyboard_arrow_down_rounded,
                    size: 16,
                    color: _primaryOrange,
                  ),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildSectionHeader(int totalCount) {
    return Row(
      children: [
        const Text(
          'Daftar Alat',
          style: TextStyle(
            fontFamily: 'Poppins',
            fontSize: 16,
            fontWeight: FontWeight.w700,
            color: _textPrimary,
          ),
        ),
        const SizedBox(width: 8),
        Container(
          padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 2),
          decoration: BoxDecoration(
            color: _darkHeader,
            borderRadius: BorderRadius.circular(10),
          ),
          child: Text(
            '$totalCount',
            style: const TextStyle(
              fontFamily: 'Poppins',
              fontSize: 11,
              fontWeight: FontWeight.w700,
              color: Colors.white,
            ),
          ),
        ),
        const Spacer(),
        GestureDetector(
          onTap: () => setState(() => _onlyAvailable = !_onlyAvailable),
          child: Container(
            padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 5),
            decoration: BoxDecoration(
              color: _onlyAvailable ? const Color(0xFFFFF2EC) : Colors.white,
              borderRadius: BorderRadius.circular(20),
              border: Border.all(
                color: _onlyAvailable ? _primaryOrange : const Color(0xFFE2E8F0),
              ),
            ),
            child: Row(
              mainAxisSize: MainAxisSize.min,
              children: [
                Icon(
                  _onlyAvailable
                      ? Icons.check_circle_rounded
                      : Icons.filter_list_rounded,
                  size: 14,
                  color: _onlyAvailable ? _primaryOrange : _textSecondary,
                ),
                const SizedBox(width: 5),
                Text(
                  'Tersedia Saja',
                  style: TextStyle(
                    fontFamily: 'Poppins',
                    fontSize: 11,
                    fontWeight: FontWeight.w600,
                    color: _onlyAvailable ? _primaryOrange : _textSecondary,
                  ),
                ),
              ],
            ),
          ),
        ),
      ],
    );
  }

  Future<void> _selectDate() async {
    final now = DateTime.now();
    final today = DateTime(now.year, now.month, now.day);

    final DateTime? pickedDate = await showDatePicker(
      context: context,
      initialDate: _selectedDate ?? today,
      firstDate: today,
      lastDate: DateTime(2100),
      helpText: 'PILIH TANGGAL PINJAM',
      cancelText: 'Batal',
      confirmText: 'Pilih',
      builder: (context, child) {
        return Theme(
          data: Theme.of(context).copyWith(
            colorScheme: const ColorScheme.light(
              primary: _primaryOrange,
              onPrimary: Colors.white,
              surface: Colors.white,
              onSurface: _textPrimary,
            ),
            datePickerTheme: DatePickerThemeData(
              backgroundColor: Colors.white,
              surfaceTintColor: Colors.transparent,
              headerBackgroundColor: _darkHeader,
              headerForegroundColor: Colors.white,
              shape: RoundedRectangleBorder(
                borderRadius: BorderRadius.circular(24),
              ),
              dayStyle: const TextStyle(
                fontFamily: 'Poppins',
                fontWeight: FontWeight.w500,
              ),
              headerHeadlineStyle: const TextStyle(
                fontFamily: 'Poppins',
                fontSize: 22,
                fontWeight: FontWeight.w700,
              ),
              headerHelpStyle: TextStyle(
                fontFamily: 'Poppins',
                fontSize: 11,
                fontWeight: FontWeight.w600,
                letterSpacing: 0.8,
                color: Colors.white.withValues(alpha: 0.7),
              ),
              cancelButtonStyle: TextButton.styleFrom(
                foregroundColor: _textSecondary,
                textStyle: const TextStyle(
                  fontFamily: 'Poppins',
                  fontWeight: FontWeight.w600,
                ),
              ),
              confirmButtonStyle: TextButton.styleFrom(
                foregroundColor: _primaryOrange,
                textStyle: const TextStyle(
                  fontFamily: 'Poppins',
                  fontWeight: FontWeight.w700,
                ),
              ),
            ),
          ),
          child: child!,
        );
      },
    );

    if (pickedDate != null && mounted) {
      setState(() {
        _selectedDate = pickedDate;
        _tanggalPinjamController.text =
            '${pickedDate.year}-${pickedDate.month.toString().padLeft(2, '0')}-${pickedDate.day.toString().padLeft(2, '0')}';
      });
    }
  }

  Widget _buildBarangCard(dynamic barang) {
    final isSelected = _selectedItems.containsKey(barang.id);
    final isAvailable = barang.stok > 0;
    final rawDesc = (barang.deskripsi ?? '').toString().trim();
    final hasDesc = rawDesc.isNotEmpty && rawDesc != '-';

    if (!_qtyControllers.containsKey(barang.id)) {
      _qtyControllers[barang.id] = TextEditingController(text: '1');
    }

    return Opacity(
      opacity: (isAvailable || isSelected) ? 1.0 : 0.72,
      child: Container(
        margin: const EdgeInsets.only(bottom: 12),
        decoration: BoxDecoration(
          color: isSelected ? const Color(0xFFFFFAF7) : Colors.white,
          borderRadius: BorderRadius.circular(20),
          border: Border.all(
            color: isSelected ? _primaryOrange : const Color(0xFFEDF1F5),
            width: isSelected ? 1.8 : 1,
          ),
          boxShadow: [
            BoxShadow(
              color: isSelected
                  ? _primaryOrange.withValues(alpha: 0.08)
                  : Colors.black.withValues(alpha: 0.03),
              blurRadius: 14,
              offset: const Offset(0, 5),
            ),
          ],
        ),
        child: Column(
          children: [
            InkWell(
              onTap: () {
                if (!isAvailable && !isSelected) {
                  ScaffoldMessenger.of(context).clearSnackBars();
                  ScaffoldMessenger.of(context).showSnackBar(
                    SnackBar(
                      content: Text(
                        'Stok "${barang.namaBarang}" sedang habis (0)',
                        style: const TextStyle(
                          fontFamily: 'Poppins',
                          fontSize: 12.5,
                          fontWeight: FontWeight.w500,
                        ),
                      ),
                      backgroundColor: _darkHeader,
                      behavior: SnackBarBehavior.floating,
                      shape: RoundedRectangleBorder(
                        borderRadius: BorderRadius.circular(12),
                      ),
                      duration: const Duration(seconds: 2),
                    ),
                  );
                  return;
                }
                setState(() {
                  if (isSelected) {
                    _selectedItems.remove(barang.id);
                  } else {
                    _selectedItems[barang.id] = 1;
                    _qtyControllers[barang.id]?.text = '1';
                  }
                });
              },
              borderRadius: BorderRadius.circular(20),
              child: Padding(
                padding: const EdgeInsets.all(14),
                child: Row(
                  children: [
                    Container(
                      width: 58,
                      height: 58,
                      decoration: BoxDecoration(
                        color: isSelected
                            ? const Color(0xFFFFF2EC)
                            : const Color(0xFFF1F5F9),
                        borderRadius: BorderRadius.circular(14),
                      ),
                      clipBehavior: Clip.antiAlias,
                      child: barang.imageUrl != null
                          ? Image.network(
                              barang.imageUrl!,
                              fit: BoxFit.cover,
                              width: 58,
                              height: 58,
                              errorBuilder: (_, __, ___) => Icon(
                                Iconsax.box,
                                color: isSelected ? _primaryOrange : _textSecondary,
                                size: 24,
                              ),
                            )
                          : Icon(
                              Iconsax.box,
                              color: isSelected ? _primaryOrange : _textSecondary,
                              size: 24,
                            ),
                    ),
                    const SizedBox(width: 14),
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(
                            barang.namaBarang,
                            style: const TextStyle(
                              fontFamily: 'Poppins',
                              fontSize: 14.5,
                              fontWeight: FontWeight.w700,
                              color: _textPrimary,
                            ),
                          ),
                          if (hasDesc) ...[
                            const SizedBox(height: 2),
                            Text(
                              rawDesc,
                              style: const TextStyle(
                                fontFamily: 'Poppins',
                                fontSize: 12,
                                color: _textSecondary,
                              ),
                              maxLines: 1,
                              overflow: TextOverflow.ellipsis,
                            ),
                          ],
                          const SizedBox(height: 6),
                          Container(
                            padding: const EdgeInsets.symmetric(
                              horizontal: 9,
                              vertical: 3.5,
                            ),
                            decoration: BoxDecoration(
                              color: isAvailable
                                  ? const Color(0xFFECFDF5)
                                  : const Color(0xFFFFF1F2),
                              borderRadius: BorderRadius.circular(8),
                              border: Border.all(
                                color: isAvailable
                                    ? const Color(0xFFA7F3D0)
                                    : const Color(0xFFFECDD3),
                              ),
                            ),
                            child: Text(
                              isAvailable
                                  ? 'Tersedia: ${barang.stok}'
                                  : 'Stok Habis (0)',
                              style: TextStyle(
                                fontFamily: 'Poppins',
                                fontSize: 11,
                                fontWeight: FontWeight.w600,
                                color: isAvailable ? _successGreen : _errorRed,
                              ),
                            ),
                          ),
                        ],
                      ),
                    ),
                    const SizedBox(width: 8),
                    AnimatedContainer(
                      duration: const Duration(milliseconds: 160),
                      width: 26,
                      height: 26,
                      decoration: BoxDecoration(
                        color: isSelected
                            ? _primaryOrange
                            : (isAvailable
                                ? Colors.white
                                : const Color(0xFFF1F5F9)),
                        borderRadius: BorderRadius.circular(8),
                        border: Border.all(
                          color: isSelected
                              ? _primaryOrange
                              : const Color(0xFFCBD5E1),
                          width: 1.8,
                        ),
                      ),
                      child: isSelected
                          ? const Icon(
                              Icons.check_rounded,
                              color: Colors.white,
                              size: 17,
                            )
                          : null,
                    ),
                  ],
                ),
              ),
            ),
            if (isSelected) ...[
              const Divider(height: 1, color: Color(0xFFFED7AA)),
              Padding(
                padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
                child: Row(
                  children: [
                    const Text(
                      'Jumlah Pinjam',
                      style: TextStyle(
                        fontFamily: 'Poppins',
                        fontSize: 12.5,
                        fontWeight: FontWeight.w600,
                        color: _textPrimary,
                      ),
                    ),
                    const Spacer(),
                    Text(
                      'Maks ${barang.stok}',
                      style: const TextStyle(
                        fontFamily: 'Poppins',
                        fontSize: 11.5,
                        color: _textSecondary,
                      ),
                    ),
                    const SizedBox(width: 10),
                    Container(
                      padding: const EdgeInsets.all(3),
                      decoration: BoxDecoration(
                        color: Colors.white,
                        borderRadius: BorderRadius.circular(11),
                        border: Border.all(color: const Color(0xFFE2E8F0)),
                      ),
                      child: Row(
                        children: [
                          _buildQtyButton(
                            icon: Icons.remove_rounded,
                            onTap: () {
                              final currentQty = _selectedItems[barang.id] ?? 1;
                              if (currentQty > 1) {
                                setState(() {
                                  _selectedItems[barang.id] = currentQty - 1;
                                  _qtyControllers[barang.id]?.text =
                                      (currentQty - 1).toString();
                                });
                              }
                            },
                          ),
                          SizedBox(
                            width: 42,
                            child: TextField(
                              controller: _qtyControllers[barang.id],
                              keyboardType: TextInputType.number,
                              textAlign: TextAlign.center,
                              style: const TextStyle(
                                fontFamily: 'Poppins',
                                fontSize: 13.5,
                                fontWeight: FontWeight.w700,
                                color: _textPrimary,
                              ),
                              decoration: const InputDecoration(
                                border: InputBorder.none,
                                isDense: true,
                                contentPadding: EdgeInsets.zero,
                              ),
                              onChanged: (value) {
                                int qty = int.tryParse(value) ?? 1;
                                if (qty < 1) qty = 1;
                                if (barang.stok > 0 && qty > barang.stok) {
                                  qty = barang.stok;
                                }
                                setState(() {
                                  _selectedItems[barang.id] = qty;
                                });
                              },
                            ),
                          ),
                          _buildQtyButton(
                            icon: Icons.add_rounded,
                            filled: true,
                            onTap: () {
                              final currentQty = _selectedItems[barang.id] ?? 1;
                              if (currentQty < barang.stok) {
                                setState(() {
                                  _selectedItems[barang.id] = currentQty + 1;
                                  _qtyControllers[barang.id]?.text =
                                      (currentQty + 1).toString();
                                });
                              }
                            },
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
      ),
    );
  }

  Widget _buildQtyButton({
    required IconData icon,
    required VoidCallback onTap,
    bool filled = false,
  }) {
    return InkWell(
      onTap: onTap,
      borderRadius: BorderRadius.circular(8),
      child: Container(
        width: 30,
        height: 30,
        decoration: BoxDecoration(
          color: filled ? _primaryOrange : const Color(0xFFF1F5F9),
          borderRadius: BorderRadius.circular(8),
        ),
        child: Icon(
          icon,
          size: 17,
          color: filled ? Colors.white : _textPrimary,
        ),
      ),
    );
  }

  Widget _buildEmptyFilterState() {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 36),
      child: Column(
        children: [
          Container(
            padding: const EdgeInsets.all(18),
            decoration: const BoxDecoration(
              color: Color(0xFFF1F5F9),
              shape: BoxShape.circle,
            ),
            child: const Icon(
              Icons.search_off_rounded,
              size: 34,
              color: Color(0xFF94A3B8),
            ),
          ),
          const SizedBox(height: 12),
          const Text(
            'Alat tidak ditemukan',
            style: TextStyle(
              fontFamily: 'Poppins',
              fontSize: 14.5,
              fontWeight: FontWeight.w600,
              color: _textPrimary,
            ),
          ),
          const SizedBox(height: 4),
          const Text(
            'Coba ubah kata kunci pencarian atau matikan filter stok',
            style: TextStyle(
              fontFamily: 'Poppins',
              fontSize: 12,
              color: _textSecondary,
            ),
            textAlign: TextAlign.center,
          ),
        ],
      ),
    );
  }

  Widget _buildEmptyState(String message) {
    return Center(
      child: Column(
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          Container(
            padding: const EdgeInsets.all(20),
            decoration: const BoxDecoration(
              color: Color(0xFFFFF2EC),
              shape: BoxShape.circle,
            ),
            child: const Icon(Iconsax.box, size: 44, color: _primaryOrange),
          ),
          const SizedBox(height: 16),
          Text(
            message,
            style: const TextStyle(
              fontFamily: 'Poppins',
              fontSize: 15,
              fontWeight: FontWeight.w600,
              color: _textSecondary,
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildErrorState(String message) {
    return Center(
      child: Padding(
        padding: const EdgeInsets.symmetric(horizontal: 32),
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Container(
              padding: const EdgeInsets.all(20),
              decoration: const BoxDecoration(
                color: Color(0xFFFFF1F2),
                shape: BoxShape.circle,
              ),
              child: const Icon(
                Icons.error_outline_rounded,
                size: 46,
                color: _errorRed,
              ),
            ),
            const SizedBox(height: 16),
            const Text(
              'Terjadi Kesalahan',
              style: TextStyle(
                fontFamily: 'Poppins',
                fontSize: 16,
                fontWeight: FontWeight.w700,
                color: _textPrimary,
              ),
            ),
            const SizedBox(height: 4),
            Text(
              message,
              style: const TextStyle(
                fontFamily: 'Poppins',
                fontSize: 13,
                color: _textSecondary,
              ),
              textAlign: TextAlign.center,
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildBottomSubmitBar() {
    final bottomPadding = MediaQuery.of(context).padding.bottom;
    final hasSelection =
        _selectedItems.isNotEmpty && _tanggalPinjamController.text.isNotEmpty;
    final totalUnits =
        _selectedItems.values.fold<int>(0, (prev, qty) => prev + qty);

    return Container(
      padding: EdgeInsets.fromLTRB(20, 14, 20, 14 + bottomPadding),
      decoration: BoxDecoration(
        color: Colors.white,
        border: const Border(top: BorderSide(color: Color(0xFFEDF1F5))),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.05),
            blurRadius: 16,
            offset: const Offset(0, -4),
          ),
        ],
      ),
      child: Row(
        children: [
          if (_selectedItems.isNotEmpty) ...[
            Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              mainAxisSize: MainAxisSize.min,
              children: [
                const Text(
                  'Total Dipilih',
                  style: TextStyle(
                    fontFamily: 'Poppins',
                    fontSize: 11,
                    color: _textSecondary,
                  ),
                ),
                Text(
                  '${_selectedItems.length} Alat ($totalUnits pcs)',
                  style: const TextStyle(
                    fontFamily: 'Poppins',
                    fontSize: 13.5,
                    fontWeight: FontWeight.w700,
                    color: _textPrimary,
                  ),
                ),
              ],
            ),
            const SizedBox(width: 16),
          ],
          Expanded(
            child: Container(
              height: 52,
              decoration: BoxDecoration(
                gradient: hasSelection
                    ? const LinearGradient(
                        colors: [Color(0xFFFF5B22), Color(0xFFFF7A00)],
                        begin: Alignment.centerLeft,
                        end: Alignment.centerRight,
                      )
                    : null,
                color: hasSelection ? null : const Color(0xFFE2E8F0),
                borderRadius: BorderRadius.circular(16),
                boxShadow: hasSelection
                    ? [
                        BoxShadow(
                          color: _primaryOrange.withValues(alpha: 0.32),
                          blurRadius: 14,
                          offset: const Offset(0, 5),
                        ),
                      ]
                    : null,
              ),
              child: Material(
                color: Colors.transparent,
                child: InkWell(
                  borderRadius: BorderRadius.circular(16),
                  onTap: hasSelection && !_isSubmitting
                      ? () => _submitPeminjaman(context)
                      : null,
                  child: Row(
                    mainAxisAlignment: MainAxisAlignment.center,
                    children: [
                      if (_isSubmitting)
                        const SizedBox(
                          width: 20,
                          height: 20,
                          child: CircularProgressIndicator(
                            strokeWidth: 2.2,
                            valueColor:
                                AlwaysStoppedAnimation<Color>(Colors.white),
                          ),
                        )
                      else
                        Icon(
                          Icons.check_circle_outline_rounded,
                          size: 20,
                          color: hasSelection
                              ? Colors.white
                              : const Color(0xFF94A3B8),
                        ),
                      const SizedBox(width: 8),
                      Text(
                        _isSubmitting ? 'Memproses...' : 'Ajukan Pinjaman',
                        style: TextStyle(
                          fontFamily: 'Poppins',
                          fontSize: 14.5,
                          fontWeight: FontWeight.w700,
                          color: hasSelection
                              ? Colors.white
                              : const Color(0xFF94A3B8),
                        ),
                      ),
                    ],
                  ),
                ),
              ),
            ),
          ),
        ],
      ),
    );
  }

  Future<void> _submitPeminjaman(BuildContext context) async {
    if (_tanggalPinjamController.text.isEmpty) {
      _showAlert(
        type: QuickAlertType.warning,
        title: 'Tanggal Kosong',
        text: 'Silakan pilih tanggal pinjam terlebih dahulu.',
      );
      return;
    }

    if (_selectedItems.isEmpty) {
      _showAlert(
        type: QuickAlertType.warning,
        title: 'Belum Ada Barang',
        text: 'Silakan pilih minimal satu barang untuk dipinjam.',
      );
      return;
    }

    if (_id.isEmpty) {
      _showAlert(
        type: QuickAlertType.error,
        title: 'Gagal',
        text: 'Gagal mendapatkan ID pengguna.',
      );
      return;
    }

    setState(() {
      _isSubmitting = true;
    });

    final pinjamProvider = Provider.of<PinjamSendProvider>(context, listen: false);
    final pinjamGetProvider = Provider.of<PinjamGetProvider>(context, listen: false);

    try {
      final String code = _generateMixedCode();

      for (var entry in _selectedItems.entries) {
        await pinjamProvider.postBarang(
          teknisiId: _id,
          barangId: entry.key.toString(),
          qty: entry.value,
          tglPinjam: _tanggalPinjamController.text,
          code: code,
        );
      }

      if (!mounted) return;

      await pinjamGetProvider.getPinjam(_id);

      if (!mounted) return;

      _showAlert(
        type: QuickAlertType.success,
        title: 'Berhasil!',
        text: 'Peminjaman berhasil diajukan.',
        onConfirm: () {
          Navigator.pop(context);
          Navigator.pop(context, true);
        },
      );
    } catch (e) {
      if (!mounted) return;

      _showAlert(
        type: QuickAlertType.error,
        title: 'Gagal',
        text: 'Terjadi kesalahan saat mengajukan peminjaman. Silakan coba lagi.',
      );
    } finally {
      if (mounted) {
        setState(() {
          _isSubmitting = false;
        });
      }
    }
  }

  void _showAlert({
    required QuickAlertType type,
    required String title,
    required String text,
    VoidCallback? onConfirm,
  }) {
    QuickAlert.show(
      context: context,
      type: type,
      title: title,
      text: text,
      confirmBtnColor: _primaryOrange,
      onConfirmBtnTap: onConfirm ?? () => Navigator.pop(context),
    );
  }
}
