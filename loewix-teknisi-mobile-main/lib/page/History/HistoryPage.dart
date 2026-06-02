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
  final ScrollController _scrollController = ScrollController();
  String? _teknisiId;

  @override
  void initState() {
    super.initState();
    _scrollController.addListener(_onScroll);

    WidgetsBinding.instance.addPostFrameCallback((_) {
      _loadInitialData();
    });
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
    // Trigger load more when 200px from bottom
    return currentScroll >= (maxScroll - 200);
  }

  Future<void> _onRefresh() async {
    await Provider.of<HistoryGetAllProvider>(context, listen: false).refresh();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xFFF8FAFC),
      appBar: AppBar(
        backgroundColor: const Color(0xFFF8FAFC),
        elevation: 0,
        leading: IconButton(
          onPressed: () => Scaffold.of(context).openDrawer(),
          icon: const Icon(Iconsax.menu_1, color: Color(0xFF1E293B)),
        ),
        title: const Text(
          'Riwayat Tugas',
          style: TextStyle(
            fontFamily: 'Poppins',
            fontWeight: FontWeight.bold,
            fontSize: 24,
            color: Color(0xFF1E293B),
          ),
        ),
        actions: [
          IconButton(
            onPressed: _onRefresh,
            icon: const Icon(Icons.refresh, color: Color(0xFF1E293B)),
          ),
        ],
      ),
      body: Consumer<HistoryGetAllProvider>(
        builder: (context, provider, child) {
          // Loading state awal
          if (provider.state == ResultState.loading) {
            return const Center(
              child: CircularProgressIndicator(),
            );
          }

          // No data
          if (provider.state == ResultState.noData) {
            return _buildEmptyState();
          }

          // Error state
          if (provider.state == ResultState.error) {
            return _buildErrorState(provider.message);
          }

          // Has data
          if (provider.state == ResultState.hasData) {
            return _buildHistoryList(provider);
          }

          // Default / initial state
          return const Center(
            child: CircularProgressIndicator(),
          );
        },
      ),
    );
  }

  Widget _buildEmptyState() {
    return Center(
      child: Column(
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          Icon(
            Icons.history,
            size: 80,
            color: Colors.grey[400],
          ),
          const SizedBox(height: 16),
          Text(
            'Belum ada riwayat tugas',
            style: TextStyle(
              fontFamily: 'Poppins',
              fontSize: 16,
              color: Colors.grey[600],
            ),
          ),
          const SizedBox(height: 24),
          ElevatedButton.icon(
            onPressed: _onRefresh,
            icon: const Icon(Icons.refresh),
            label: const Text('Muat Ulang'),
            style: ElevatedButton.styleFrom(
              backgroundColor: const Color(0xFF3B82F6),
              foregroundColor: Colors.white,
              shape: RoundedRectangleBorder(
                borderRadius: BorderRadius.circular(12),
              ),
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
          Icon(
            Icons.error_outline,
            size: 80,
            color: Colors.red[400],
          ),
          const SizedBox(height: 16),
          Padding(
            padding: const EdgeInsets.symmetric(horizontal: 32),
            child: Text(
              message,
              textAlign: TextAlign.center,
              style: TextStyle(
                fontFamily: 'Poppins',
                fontSize: 14,
                color: Colors.grey[600],
              ),
            ),
          ),
          const SizedBox(height: 24),
          ElevatedButton.icon(
            onPressed: _onRefresh,
            icon: const Icon(Icons.refresh),
            label: const Text('Coba Lagi'),
            style: ElevatedButton.styleFrom(
              backgroundColor: const Color(0xFF3B82F6),
              foregroundColor: Colors.white,
              shape: RoundedRectangleBorder(
                borderRadius: BorderRadius.circular(12),
              ),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildHistoryList(HistoryGetAllProvider provider) {
    final historyList = provider.historyList;

    return RefreshIndicator(
      onRefresh: _onRefresh,
      child: Column(
        children: [
          // Info pagination
          _buildPaginationInfo(provider),
          // List
          Expanded(
            child: ListView.builder(
              controller: _scrollController,
              padding: const EdgeInsets.symmetric(horizontal: 15, vertical: 10),
              itemCount: historyList.length + (provider.hasNextPage ? 1 : 0),
              itemBuilder: (context, index) {
                // Loading indicator di akhir list
                if (index == historyList.length) {
                  return _buildLoadMoreIndicator(provider);
                }

                return Padding(
                  padding: const EdgeInsets.only(bottom: 15.0),
                  child: CardTask(
                    data: historyList[index],
                    history: true,
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
      width: double.infinity,
      padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 10),
      decoration: BoxDecoration(
        color: Colors.white,
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.05),
            blurRadius: 4,
            offset: const Offset(0, 2),
          ),
        ],
      ),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceBetween,
        children: [
          Text(
            'Total: ${provider.total} tugas',
            style: const TextStyle(
              fontFamily: 'Poppins',
              fontSize: 13,
              fontWeight: FontWeight.w500,
              color: Color(0xFF64748B),
            ),
          ),
          Text(
            'Halaman ${provider.currentPage} dari ${provider.lastPage}',
            style: const TextStyle(
              fontFamily: 'Poppins',
              fontSize: 13,
              color: Color(0xFF64748B),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildLoadMoreIndicator(HistoryGetAllProvider provider) {
    return Container(
      padding: const EdgeInsets.symmetric(vertical: 20),
      child: Center(
        child: provider.isLoadingMore
            ? const Row(
                mainAxisAlignment: MainAxisAlignment.center,
                children: [
                  SizedBox(
                    width: 20,
                    height: 20,
                    child: CircularProgressIndicator(strokeWidth: 2),
                  ),
                  SizedBox(width: 12),
                  Text(
                    'Memuat lebih banyak...',
                    style: TextStyle(
                      fontFamily: 'Poppins',
                      fontSize: 13,
                      color: Color(0xFF64748B),
                    ),
                  ),
                ],
              )
            : TextButton(
                onPressed: () => provider.loadMore(),
                child: const Text(
                  'Muat lebih banyak',
                  style: TextStyle(
                    fontFamily: 'Poppins',
                    fontSize: 13,
                    color: Color(0xFF3B82F6),
                  ),
                ),
              ),
      ),
    );
  }
}
