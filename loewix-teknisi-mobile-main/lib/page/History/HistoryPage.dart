import 'package:flutter/material.dart';
import 'package:iconsax/iconsax.dart';
import 'package:provider/provider.dart';

import '../../service/provider/Task/HistoryGetAllProvider.dart';
import '../../service/provider/preferences/PreferencesIDProvider.dart';
import '../../utils/state.dart';
import '../../widget/CardTask.dart';

class HistoryPage extends StatefulWidget {
  const HistoryPage({super.key});

  @override
  State<HistoryPage> createState() => _HistoryPageState();
}

class _HistoryPageState extends State<HistoryPage> {
  // ─── Premium Color Palette ─────────────────────
  static const Color _navy = Color(0xFF0F172A);
  static const Color _skyBlue = Color(0xFF0EA5E9);
  static const Color _textSecondary = Color(0xFF64748B);
  static const Color _bgColor = Color(0xFFF1F5F9);

  final ScrollController _scrollController = ScrollController();
  String? _teknisiId;

  @override
  void initState() {
    super.initState();
    _scrollController.addListener(_onScroll);
    WidgetsBinding.instance.addPostFrameCallback((_) => _loadInitialData());
  }

  @override
  void dispose() {
    _scrollController.removeListener(_onScroll);
    _scrollController.dispose();
    super.dispose();
  }

  void _loadInitialData() {
    final idProvider = Provider.of<PreferencesIDProvider>(context, listen: false);
    _teknisiId = idProvider.isUserRole;
    if (_teknisiId != null && _teknisiId!.isNotEmpty) {
      Provider.of<HistoryGetAllProvider>(context, listen: false).getTask(_teknisiId!);
    }
  }

  void _onScroll() {
    if (_isBottom) {
      Provider.of<HistoryGetAllProvider>(context, listen: false).loadMore();
    }
  }

  bool get _isBottom {
    if (!_scrollController.hasClients) return false;
    final maxScroll = _scrollController.position.maxScrollExtent;
    final currentScroll = _scrollController.offset;
    return currentScroll >= (maxScroll - 200);
  }

  Future<void> _onRefresh() async {
    await Provider.of<HistoryGetAllProvider>(context, listen: false).refresh();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: _bgColor,
      body: Column(
        children: [
          _buildPremiumHeader(),
          Expanded(
            child: Consumer<HistoryGetAllProvider>(
              builder: (context, provider, child) {
                if (provider.state == ResultState.loading) {
                  return const Center(
                    child: CircularProgressIndicator(color: _skyBlue, strokeWidth: 2.5),
                  );
                }
                if (provider.state == ResultState.noData) {
                  return _buildEmptyState();
                }
                if (provider.state == ResultState.error) {
                  return _buildErrorState(provider.message);
                }
                if (provider.state == ResultState.hasData) {
                  return _buildHistoryList(provider);
                }
                return const Center(
                  child: CircularProgressIndicator(color: _skyBlue, strokeWidth: 2.5),
                );
              },
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildPremiumHeader() {
    return Container(
      decoration: const BoxDecoration(
        gradient: LinearGradient(
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
          colors: [Color(0xFF0F172A), Color(0xFF1E293B), Color(0xFF0F172A)],
          stops: [0.0, 0.5, 1.0],
        ),
        borderRadius: BorderRadius.only(
          bottomLeft: Radius.circular(28),
          bottomRight: Radius.circular(28),
        ),
      ),
      child: SafeArea(
        bottom: false,
        child: Padding(
          padding: const EdgeInsets.fromLTRB(20, 8, 20, 24),
          child: Row(
            children: [
              Material(
                color: Colors.white.withValues(alpha: 0.1),
                borderRadius: BorderRadius.circular(14),
                child: InkWell(
                  onTap: () => Scaffold.of(context).openDrawer(),
                  borderRadius: BorderRadius.circular(14),
                  child: const Padding(
                    padding: EdgeInsets.all(12),
                    child: Icon(Iconsax.menu_1, color: Colors.white, size: 22),
                  ),
                ),
              ),
              const SizedBox(width: 14),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      'Riwayat',
                      style: TextStyle(
                        fontFamily: 'Poppins',
                        fontSize: 13,
                        color: Colors.white.withValues(alpha: 0.6),
                      ),
                    ),
                    const Text(
                      'Semua Tugas',
                      style: TextStyle(
                        fontFamily: 'Poppins',
                        fontSize: 22,
                        fontWeight: FontWeight.w700,
                        color: Colors.white,
                        height: 1.2,
                      ),
                    ),
                  ],
                ),
              ),
              Material(
                color: Colors.white.withValues(alpha: 0.1),
                borderRadius: BorderRadius.circular(14),
                child: InkWell(
                  onTap: _onRefresh,
                  borderRadius: BorderRadius.circular(14),
                  child: const Padding(
                    padding: EdgeInsets.all(12),
                    child: Icon(Icons.refresh_rounded, color: Colors.white, size: 22),
                  ),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildHistoryList(HistoryGetAllProvider provider) {
    final historyList = provider.historyList;
    return RefreshIndicator(
      onRefresh: _onRefresh,
      color: Colors.white,
      backgroundColor: _navy,
      child: Column(
        children: [
          _buildPaginationInfo(provider),
          Expanded(
            child: ListView.builder(
              controller: _scrollController,
              physics: const BouncingScrollPhysics(parent: AlwaysScrollableScrollPhysics()),
              padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 12),
              itemCount: historyList.length + (provider.hasNextPage ? 1 : 0),
              itemBuilder: (context, index) {
                if (index == historyList.length) {
                  return _buildLoadMoreIndicator(provider);
                }
                return RepaintBoundary(
                  child: Padding(
                    padding: const EdgeInsets.only(bottom: 12),
                    child: CardTask(data: historyList[index], history: true),
                  ),
                );
              },
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildPaginationInfo(HistoryGetAllProvider provider) {
    return Container(
      margin: const EdgeInsets.fromLTRB(20, 16, 20, 4),
      padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 10),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(14),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.04),
            blurRadius: 8,
            offset: const Offset(0, 2),
          ),
        ],
      ),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceBetween,
        children: [
          Row(
            children: [
              Container(
                padding: const EdgeInsets.all(6),
                decoration: BoxDecoration(
                  color: _skyBlue.withValues(alpha: 0.1),
                  borderRadius: BorderRadius.circular(8),
                ),
                child: const Icon(Iconsax.document_text, size: 14, color: _skyBlue),
              ),
              const SizedBox(width: 8),
              Text(
                '${provider.total} tugas',
                style: const TextStyle(
                  fontFamily: 'Poppins',
                  fontSize: 13,
                  fontWeight: FontWeight.w600,
                  color: Color(0xFF0F172A),
                ),
              ),
            ],
          ),
          Container(
            padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
            decoration: BoxDecoration(
              color: const Color(0xFFF1F5F9),
              borderRadius: BorderRadius.circular(8),
            ),
            child: Text(
              '${provider.currentPage}/${provider.lastPage}',
              style: const TextStyle(
                fontFamily: 'Poppins',
                fontSize: 12,
                fontWeight: FontWeight.w500,
                color: _textSecondary,
              ),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildLoadMoreIndicator(HistoryGetAllProvider provider) {
    return Container(
      padding: const EdgeInsets.symmetric(vertical: 16),
      child: Center(
        child: provider.isLoadingMore
            ? Row(
                mainAxisAlignment: MainAxisAlignment.center,
                children: [
                  const SizedBox(
                    width: 18, height: 18,
                    child: CircularProgressIndicator(strokeWidth: 2, color: _skyBlue),
                  ),
                  const SizedBox(width: 12),
                  Text(
                    'Memuat...',
                    style: TextStyle(
                      fontFamily: 'Poppins',
                      fontSize: 13,
                      color: _textSecondary,
                    ),
                  ),
                ],
              )
            : GestureDetector(
                onTap: () => provider.loadMore(),
                child: Container(
                  padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 10),
                  decoration: BoxDecoration(
                    color: _skyBlue.withValues(alpha: 0.1),
                    borderRadius: BorderRadius.circular(20),
                  ),
                  child: const Text(
                    'Muat lebih banyak',
                    style: TextStyle(
                      fontFamily: 'Poppins',
                      fontSize: 13,
                      fontWeight: FontWeight.w600,
                      color: _skyBlue,
                    ),
                  ),
                ),
              ),
      ),
    );
  }

  Widget _buildEmptyState() {
    return Center(
      child: Column(
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          Container(
            padding: const EdgeInsets.all(24),
            decoration: BoxDecoration(
              color: _navy.withValues(alpha: 0.05),
              borderRadius: BorderRadius.circular(24),
            ),
            child: Icon(Iconsax.document, size: 56, color: _navy.withValues(alpha: 0.3)),
          ),
          const SizedBox(height: 20),
          const Text(
            'Belum ada riwayat tugas',
            style: TextStyle(
              fontFamily: 'Poppins',
              fontSize: 16,
              fontWeight: FontWeight.w600,
              color: Color(0xFF0F172A),
            ),
          ),
          const SizedBox(height: 24),
          _buildRetryButton(),
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
            padding: const EdgeInsets.all(24),
            decoration: BoxDecoration(
              color: const Color(0xFFF43F5E).withValues(alpha: 0.08),
              borderRadius: BorderRadius.circular(24),
            ),
            child: const Icon(Icons.error_outline_rounded, size: 56, color: Color(0xFFF43F5E)),
          ),
          const SizedBox(height: 20),
          Padding(
            padding: const EdgeInsets.symmetric(horizontal: 32),
            child: Text(message, textAlign: TextAlign.center,
              style: const TextStyle(fontFamily: 'Poppins', fontSize: 14, color: _textSecondary),
            ),
          ),
          const SizedBox(height: 24),
          _buildRetryButton(),
        ],
      ),
    );
  }

  Widget _buildRetryButton() {
    return ElevatedButton.icon(
      onPressed: _onRefresh,
      icon: const Icon(Icons.refresh_rounded, size: 18),
      label: const Text('Muat Ulang', style: TextStyle(fontFamily: 'Poppins', fontWeight: FontWeight.w600)),
      style: ElevatedButton.styleFrom(
        backgroundColor: _skyBlue,
        foregroundColor: Colors.white,
        elevation: 0,
        padding: const EdgeInsets.symmetric(horizontal: 24, vertical: 12),
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
      ),
    );
  }
}
