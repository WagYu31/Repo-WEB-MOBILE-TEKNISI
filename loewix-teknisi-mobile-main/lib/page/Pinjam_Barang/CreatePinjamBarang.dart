import 'dart:math';

import '../../service/provider/Barang/BarangGetProvider.dart';
import '../../service/provider/Pinjam/PinjamSendProvider.dart';
import '../../utils/state.dart';
import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:quickalert/quickalert.dart';

import '../../service/provider/Pinjam/PinjamGetProvider.dart';
import '../../service/provider/preferences/PreferencesIDProvider.dart';

class PinjamBarangPage extends StatefulWidget {
  static const routeName = '/create_pinjam_page';
  const PinjamBarangPage({super.key});

  @override
  State<PinjamBarangPage> createState() => _PinjamBarangPageState();
}

class _PinjamBarangPageState extends State<PinjamBarangPage> {
  // Modern color scheme
  static const Color _primaryBlue = Color(0xFF2563EB);
  static const Color _successGreen = Color(0xFF10B981);
  static const Color _errorRed = Color(0xFFEF4444);
  static const Color _textPrimary = Color(0xFF1F2937);
  static const Color _textSecondary = Color(0xFF6B7280);

  // Map untuk menyimpan barang yang dipilih dan quantity-nya
  final Map<int, int> _selectedItems = {};
  final Map<int, TextEditingController> _qtyControllers = {};
  String _id = '';
  final TextEditingController _tanggalPinjamController = TextEditingController();
  bool _isSubmitting = false;

  String _generateMixedCode() {
    const characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
    final random = Random();
    return String.fromCharCodes(Iterable.generate(
      6,
      (_) => characters.codeUnitAt(random.nextInt(characters.length)),
    ));
  }

  @override
  void initState() {
    super.initState();
    _id = context.read<PreferencesIDProvider>().isUserRole;
  }

  @override
  void dispose() {
    _tanggalPinjamController.dispose();
    for (var controller in _qtyControllers.values) {
      controller.dispose();
    }
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xFFF9FAFB),
      appBar: _buildAppBar(),
      body: Column(
        children: [
          _buildDatePicker(),
          Expanded(
            child: Consumer<BarangGetProvider>(
              builder: (context, state, _) {
                if (state.state == ResultState.loading) {
                  return const Center(
                    child: CircularProgressIndicator(color: _primaryBlue),
                  );
                } else if (state.state == ResultState.hasData) {
                  final items = state.response.data;
                  if (items.isEmpty) {
                    return _buildEmptyState('Tidak ada barang tersedia');
                  }
                  return ListView.builder(
                    padding: const EdgeInsets.fromLTRB(16, 0, 16, 100),
                    itemCount: items.length,
                    itemBuilder: (context, index) {
                      final barang = items[index];
                      return _buildBarangCard(barang);
                    },
                  );
                } else if (state.state == ResultState.noData) {
                  return _buildEmptyState('Tidak ada data barang');
                } else if (state.state == ResultState.error) {
                  return _buildErrorState(state.message);
                } else {
                  return _buildEmptyState('Tidak ada status yang sesuai');
                }
              },
            ),
          ),
        ],
      ),
      floatingActionButton: _buildSubmitButton(),
    );
  }

  PreferredSizeWidget _buildAppBar() {
    return AppBar(
      backgroundColor: Colors.white,
      surfaceTintColor: Colors.transparent,
      elevation: 0,
      leading: IconButton(
        icon: Container(
          padding: const EdgeInsets.all(8),
          decoration: BoxDecoration(
            color: const Color(0xFFF3F4F6),
            borderRadius: BorderRadius.circular(12),
          ),
          child: const Icon(Icons.arrow_back_ios_new, color: _textPrimary, size: 18),
        ),
        onPressed: () => Navigator.pop(context),
      ),
      title: const Text(
        'Pinjam Barang',
        style: TextStyle(
          fontFamily: 'Poppins',
          fontWeight: FontWeight.w600,
          fontSize: 20,
          color: _textPrimary,
        ),
      ),
    );
  }

  Widget _buildDatePicker() {
    return Container(
      margin: const EdgeInsets.all(16),
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(16),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.04),
            blurRadius: 8,
            offset: const Offset(0, 2),
          ),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          const Text(
            'Tanggal Pinjam',
            style: TextStyle(
              fontFamily: 'Poppins',
              fontSize: 14,
              fontWeight: FontWeight.w600,
              color: _textPrimary,
            ),
          ),
          const SizedBox(height: 8),
          GestureDetector(
            onTap: _selectDate,
            child: Container(
              padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 14),
              decoration: BoxDecoration(
                color: const Color(0xFFF9FAFB),
                borderRadius: BorderRadius.circular(12),
                border: Border.all(color: const Color(0xFFE5E7EB)),
              ),
              child: Row(
                children: [
                  Container(
                    padding: const EdgeInsets.all(8),
                    decoration: BoxDecoration(
                      color: _primaryBlue.withValues(alpha: 0.1),
                      borderRadius: BorderRadius.circular(8),
                    ),
                    child: const Icon(Icons.calendar_today_rounded, color: _primaryBlue, size: 18),
                  ),
                  const SizedBox(width: 12),
                  Expanded(
                    child: Text(
                      _tanggalPinjamController.text.isEmpty
                          ? 'Pilih tanggal pinjam'
                          : _tanggalPinjamController.text,
                      style: TextStyle(
                        fontFamily: 'Poppins',
                        fontSize: 14,
                        color: _tanggalPinjamController.text.isEmpty
                            ? _textSecondary
                            : _textPrimary,
                      ),
                    ),
                  ),
                  const Icon(Icons.arrow_drop_down, color: _textSecondary),
                ],
              ),
            ),
          ),
        ],
      ),
    );
  }

  Future<void> _selectDate() async {
    final DateTime? pickedDate = await showDatePicker(
      context: context,
      initialDate: DateTime.now(),
      firstDate: DateTime.now(),
      lastDate: DateTime(2100),
      builder: (context, child) {
        return Theme(
          data: Theme.of(context).copyWith(
            colorScheme: const ColorScheme.light(
              primary: _primaryBlue,
              onPrimary: Colors.white,
              surface: Colors.white,
              onSurface: _textPrimary,
            ),
          ),
          child: child!,
        );
      },
    );

    if (pickedDate != null && mounted) {
      setState(() {
        _tanggalPinjamController.text = pickedDate.toString().split(' ')[0];
      });
    }
  }

  Widget _buildBarangCard(dynamic barang) {
    final isSelected = _selectedItems.containsKey(barang.id);

    // Initialize controller if needed
    if (!_qtyControllers.containsKey(barang.id)) {
      _qtyControllers[barang.id] = TextEditingController(text: '1');
    }

    return Container(
      margin: const EdgeInsets.only(bottom: 12),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(
          color: isSelected ? _primaryBlue : const Color(0xFFE5E7EB),
          width: isSelected ? 2 : 1,
        ),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.04),
            blurRadius: 8,
            offset: const Offset(0, 2),
          ),
        ],
      ),
      child: Column(
        children: [
          InkWell(
            onTap: () {
              setState(() {
                if (isSelected) {
                  _selectedItems.remove(barang.id);
                } else {
                  _selectedItems[barang.id] = 1;
                  _qtyControllers[barang.id]?.text = '1';
                }
              });
            },
            borderRadius: BorderRadius.circular(16),
            child: Padding(
              padding: const EdgeInsets.all(16),
              child: Row(
                children: [
                  Container(
                    width: 56,
                    height: 56,
                    decoration: BoxDecoration(
                      color: isSelected
                          ? _primaryBlue.withValues(alpha: 0.1)
                          : const Color(0xFFF3F4F6),
                      borderRadius: BorderRadius.circular(12),
                    ),
                    clipBehavior: Clip.antiAlias,
                    child: barang.imageUrl != null
                        ? Image.network(
                            barang.imageUrl!,
                            fit: BoxFit.cover,
                            width: 56,
                            height: 56,
                            errorBuilder: (_, __, ___) => Icon(
                              Icons.inventory_2_rounded,
                              color: isSelected ? _primaryBlue : _textSecondary,
                              size: 24,
                            ),
                          )
                        : Icon(
                            Icons.inventory_2_rounded,
                            color: isSelected ? _primaryBlue : _textSecondary,
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
                            fontSize: 15,
                            fontWeight: FontWeight.w600,
                            color: _textPrimary,
                          ),
                        ),
                        const SizedBox(height: 4),
                        Text(
                          barang.deskripsi ?? '-',
                          style: const TextStyle(
                            fontFamily: 'Poppins',
                            fontSize: 13,
                            color: _textSecondary,
                          ),
                          maxLines: 1,
                          overflow: TextOverflow.ellipsis,
                        ),
                        const SizedBox(height: 6),
                        Container(
                          padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                          decoration: BoxDecoration(
                            color: barang.stok > 0
                                ? _successGreen.withValues(alpha: 0.1)
                                : _errorRed.withValues(alpha: 0.1),
                            borderRadius: BorderRadius.circular(6),
                          ),
                          child: Text(
                            'Stok: ${barang.stok}',
                            style: TextStyle(
                              fontFamily: 'Poppins',
                              fontSize: 12,
                              fontWeight: FontWeight.w500,
                              color: barang.stok > 0 ? _successGreen : _errorRed,
                            ),
                          ),
                        ),
                      ],
                    ),
                  ),
                  Container(
                    width: 24,
                    height: 24,
                    decoration: BoxDecoration(
                      color: isSelected ? _primaryBlue : Colors.transparent,
                      borderRadius: BorderRadius.circular(6),
                      border: Border.all(
                        color: isSelected ? _primaryBlue : const Color(0xFFD1D5DB),
                        width: 2,
                      ),
                    ),
                    child: isSelected
                        ? const Icon(Icons.check, color: Colors.white, size: 16)
                        : null,
                  ),
                ],
              ),
            ),
          ),
          if (isSelected) ...[
            Container(
              height: 1,
              color: const Color(0xFFE5E7EB),
            ),
            Padding(
              padding: const EdgeInsets.all(16),
              child: Row(
                children: [
                  const Text(
                    'Jumlah:',
                    style: TextStyle(
                      fontFamily: 'Poppins',
                      fontSize: 14,
                      fontWeight: FontWeight.w500,
                      color: _textPrimary,
                    ),
                  ),
                  const SizedBox(width: 16),
                  Container(
                    decoration: BoxDecoration(
                      color: const Color(0xFFF9FAFB),
                      borderRadius: BorderRadius.circular(10),
                      border: Border.all(color: const Color(0xFFE5E7EB)),
                    ),
                    child: Row(
                      children: [
                        _buildQtyButton(
                          icon: Icons.remove,
                          onTap: () {
                            final currentQty = _selectedItems[barang.id] ?? 1;
                            if (currentQty > 1) {
                              setState(() {
                                _selectedItems[barang.id] = currentQty - 1;
                                _qtyControllers[barang.id]?.text = (currentQty - 1).toString();
                              });
                            }
                          },
                        ),
                        SizedBox(
                          width: 50,
                          child: TextField(
                            controller: _qtyControllers[barang.id],
                            keyboardType: TextInputType.number,
                            textAlign: TextAlign.center,
                            style: const TextStyle(
                              fontFamily: 'Poppins',
                              fontSize: 14,
                              fontWeight: FontWeight.w600,
                              color: _textPrimary,
                            ),
                            decoration: const InputDecoration(
                              border: InputBorder.none,
                              contentPadding: EdgeInsets.zero,
                            ),
                            onChanged: (value) {
                              int qty = int.tryParse(value) ?? 1;
                              if (qty < 1) qty = 1;
                              if (qty > barang.stok) qty = barang.stok;
                              setState(() {
                                _selectedItems[barang.id] = qty;
                              });
                            },
                          ),
                        ),
                        _buildQtyButton(
                          icon: Icons.add,
                          onTap: () {
                            final currentQty = _selectedItems[barang.id] ?? 1;
                            if (currentQty < barang.stok) {
                              setState(() {
                                _selectedItems[barang.id] = currentQty + 1;
                                _qtyControllers[barang.id]?.text = (currentQty + 1).toString();
                              });
                            }
                          },
                        ),
                      ],
                    ),
                  ),
                  const SizedBox(width: 12),
                  Text(
                    'Maks: ${barang.stok}',
                    style: const TextStyle(
                      fontFamily: 'Poppins',
                      fontSize: 12,
                      color: _textSecondary,
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

  Widget _buildQtyButton({required IconData icon, required VoidCallback onTap}) {
    return InkWell(
      onTap: onTap,
      borderRadius: BorderRadius.circular(8),
      child: Container(
        width: 36,
        height: 36,
        decoration: BoxDecoration(
          color: Colors.white,
          borderRadius: BorderRadius.circular(8),
        ),
        child: Icon(icon, size: 18, color: _primaryBlue),
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
            decoration: BoxDecoration(
              color: const Color(0xFFF3F4F6),
              borderRadius: BorderRadius.circular(20),
            ),
            child: const Icon(Icons.inventory_2_outlined, size: 48, color: _textSecondary),
          ),
          const SizedBox(height: 16),
          Text(
            message,
            style: const TextStyle(
              fontFamily: 'Poppins',
              fontSize: 16,
              color: _textSecondary,
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildErrorState(String message) {
    return Center(
      child: Column(
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          Container(
            padding: const EdgeInsets.all(20),
            decoration: BoxDecoration(
              color: _errorRed.withValues(alpha: 0.1),
              borderRadius: BorderRadius.circular(20),
            ),
            child: const Icon(Icons.error_outline_rounded, size: 48, color: _errorRed),
          ),
          const SizedBox(height: 16),
          Text(
            'Terjadi kesalahan',
            style: const TextStyle(
              fontFamily: 'Poppins',
              fontSize: 16,
              fontWeight: FontWeight.w600,
              color: _textPrimary,
            ),
          ),
          const SizedBox(height: 4),
          Padding(
            padding: const EdgeInsets.symmetric(horizontal: 32),
            child: Text(
              message,
              style: const TextStyle(
                fontFamily: 'Poppins',
                fontSize: 14,
                color: _textSecondary,
              ),
              textAlign: TextAlign.center,
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildSubmitButton() {
    final hasSelection = _selectedItems.isNotEmpty && _tanggalPinjamController.text.isNotEmpty;

    return FloatingActionButton.extended(
      heroTag: 'fab_submit_pinjam',
      onPressed: hasSelection && !_isSubmitting ? () => _submitPeminjaman(context) : null,
      backgroundColor: hasSelection ? _primaryBlue : const Color(0xFFD1D5DB),
      elevation: hasSelection ? 4 : 0,
      icon: _isSubmitting
          ? const SizedBox(
              width: 20,
              height: 20,
              child: CircularProgressIndicator(
                strokeWidth: 2,
                valueColor: AlwaysStoppedAnimation<Color>(Colors.white),
              ),
            )
          : const Icon(Icons.check_rounded, color: Colors.white),
      label: Text(
        _isSubmitting ? 'Memproses...' : 'Ajukan Pinjaman',
        style: const TextStyle(
          fontFamily: 'Poppins',
          fontWeight: FontWeight.w600,
          color: Colors.white,
        ),
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

    // Get providers before async gap
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

      // Refresh data di container
      await pinjamGetProvider.getPinjam(_id);

      if (!mounted) return;

      _showAlert(
        type: QuickAlertType.success,
        title: 'Berhasil!',
        text: 'Peminjaman berhasil diajukan.',
        onConfirm: () {
          Navigator.pop(context); // Close alert
          Navigator.pop(context, true); // Return to container with success flag
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
      confirmBtnColor: _primaryBlue,
      onConfirmBtnTap: onConfirm ?? () => Navigator.pop(context),
    );
  }
}
