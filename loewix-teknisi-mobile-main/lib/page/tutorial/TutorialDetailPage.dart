import 'package:flutter/material.dart';
import 'package:iconsax/iconsax.dart';
import 'package:provider/provider.dart';
import 'package:url_launcher/url_launcher.dart';
import 'package:video_player/video_player.dart';
import 'package:chewie/chewie.dart';

import '../../constants/app_constants.dart';
import '../../service/model/tutorial/TutorialResponse.dart';
import '../../service/provider/Tutorial/TutorialProvider.dart';

class TutorialDetailPage extends StatefulWidget {
  final int tutorialId;

  const TutorialDetailPage({super.key, required this.tutorialId});

  @override
  State<TutorialDetailPage> createState() => _TutorialDetailPageState();
}

class _TutorialDetailPageState extends State<TutorialDetailPage> {
  // Modern color scheme
  static const Color _primaryBlue = Color(0xFF2563EB);
  static const Color _successGreen = Color(0xFF10B981);
  static const Color _warningAmber = Color(0xFFF59E0B);
  static const Color _errorRed = Color(0xFFEF4444);
  static const Color _purpleAccent = Color(0xFF8B5CF6);
  static const Color _textPrimary = Color(0xFF1F2937);
  static const Color _textSecondary = Color(0xFF6B7280);

  VideoPlayerController? _videoController;
  ChewieController? _chewieController;
  bool _isVideoInitialized = false;
  bool _videoHasError = false;
  String _videoErrorMessage = '';

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      if (!mounted) return;
      _loadData();
    });
  }

  void _loadData() {
    Provider.of<TutorialProvider>(context, listen: false)
        .getTutorialDetail(widget.tutorialId);
  }

  Future<void> _initializeVideo(String videoUrl) async {
    if (_videoController != null) return;

    try {
      _videoController = VideoPlayerController.networkUrl(Uri.parse(videoUrl));
      await _videoController!.initialize();

      if (!mounted) return;

      _chewieController = ChewieController(
        videoPlayerController: _videoController!,
        autoPlay: false,
        looping: false,
        aspectRatio: _videoController!.value.aspectRatio,
        errorBuilder: (context, errorMessage) {
          return _buildVideoError(errorMessage);
        },
        placeholder: Container(
          color: Colors.black12,
          child: const Center(
            child: CircularProgressIndicator(color: _primaryBlue),
          ),
        ),
      );

      setState(() {
        _isVideoInitialized = true;
      });
    } catch (e) {
      if (!mounted) return;
      setState(() {
        _videoHasError = true;
        _videoErrorMessage = e.toString();
      });
    }
  }

  @override
  void dispose() {
    _videoController?.dispose();
    _chewieController?.dispose();
    super.dispose();
  }

  Future<void> _openUrl(String url) async {
    final uri = Uri.parse(url);
    if (await canLaunchUrl(uri)) {
      await launchUrl(uri, mode: LaunchMode.externalApplication);
    } else {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('Tidak dapat membuka file'),
          backgroundColor: _errorRed,
        ),
      );
    }
  }

  // Extract filename from URL for display
  String _getFilenameFromUrl(String url) {
    try {
      final uri = Uri.parse(url);
      final segments = uri.pathSegments;
      if (segments.isNotEmpty) {
        return segments.last;
      }
    } catch (_) {}
    return url;
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xFFF9FAFB),
      appBar: AppBar(
        backgroundColor: Colors.white,
        surfaceTintColor: Colors.transparent,
        elevation: 0,
        leading: Container(
          margin: const EdgeInsets.only(left: 8),
          child: IconButton(
            onPressed: () => Navigator.pop(context),
            icon: const Icon(Icons.arrow_back_ios_new, color: _textPrimary, size: 20),
          ),
        ),
        title: const Text(
          'Detail Tutorial',
          style: TextStyle(
            fontFamily: 'Poppins',
            fontWeight: FontWeight.w600,
            fontSize: 18,
            color: _textPrimary,
          ),
        ),
      ),
      body: SafeArea(
        child: Consumer<TutorialProvider>(
          builder: (context, provider, child) {
            if (provider.detailState == TutorialState.loading) {
              return _buildLoadingState();
            }

            if (provider.detailState == TutorialState.error) {
              return _buildErrorState(provider.detailError);
            }

            if (provider.detailState == TutorialState.loaded &&
                provider.selectedTutorial != null) {
              return _buildContent(provider.selectedTutorial!);
            }

            return _buildLoadingState();
          },
        ),
      ),
    );
  }

  Widget _buildContent(Tutorial tutorial) {
    // Initialize video if available - fix localhost URL if needed
    if (tutorial.isMedia1Video && tutorial.media1 != null) {
      if (!_isVideoInitialized && !_videoHasError) {
        _initializeVideo(AppConstants.fixMediaUrl(tutorial.media1!));
      }
    }

    return SingleChildScrollView(
      padding: const EdgeInsets.all(20),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          // Title card
          _buildTitleCard(tutorial),
          const SizedBox(height: 20),

          // Media 1 (Video/Image)
          if (tutorial.media1 != null) ...[
            _buildSectionTitle(
              tutorial.isMedia1Video ? 'Video Tutorial' : 'Media',
              tutorial.isMedia1Video ? Iconsax.video_play : Iconsax.image,
              tutorial.isMedia1Video ? _errorRed : _successGreen,
            ),
            const SizedBox(height: 12),
            _buildMedia1Section(tutorial),
            const SizedBox(height: 24),
          ],

          // Media 2 (PDF/Document)
          if (tutorial.media2 != null) ...[
            _buildSectionTitle(
              tutorial.isMedia2Pdf ? 'Dokumen PDF' : 'Lampiran',
              tutorial.isMedia2Pdf ? Iconsax.document : Iconsax.attach_circle,
              _warningAmber,
            ),
            const SizedBox(height: 12),
            _buildMedia2Section(tutorial),
            const SizedBox(height: 24),
          ],

          // Description
          _buildSectionTitle('Deskripsi', Iconsax.document_text, _purpleAccent),
          const SizedBox(height: 12),
          _buildDescriptionCard(tutorial),

          // Extra bottom padding untuk safe area
          SizedBox(height: MediaQuery.of(context).padding.bottom + 24),
        ],
      ),
    );
  }

  Widget _buildTitleCard(Tutorial tutorial) {
    final color = _getMediaColor(tutorial);
    final icon = _getMediaIcon(tutorial);

    return Container(
      padding: const EdgeInsets.all(20),
      decoration: BoxDecoration(
        gradient: LinearGradient(
          colors: [color, color.withValues(alpha: 0.8)],
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
        ),
        borderRadius: BorderRadius.circular(20),
        boxShadow: [
          BoxShadow(
            color: color.withValues(alpha: 0.3),
            blurRadius: 16,
            offset: const Offset(0, 6),
          ),
        ],
      ),
      child: Row(
        children: [
          Container(
            padding: const EdgeInsets.all(14),
            decoration: BoxDecoration(
              color: Colors.white.withValues(alpha: 0.2),
              borderRadius: BorderRadius.circular(14),
            ),
            child: Icon(icon, color: Colors.white, size: 32),
          ),
          const SizedBox(width: 16),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Container(
                  padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                  decoration: BoxDecoration(
                    color: Colors.white.withValues(alpha: 0.2),
                    borderRadius: BorderRadius.circular(8),
                  ),
                  child: Text(
                    tutorial.mediaTypeLabel,
                    style: const TextStyle(
                      fontFamily: 'Poppins',
                      fontSize: 11,
                      fontWeight: FontWeight.w600,
                      color: Colors.white,
                    ),
                  ),
                ),
                const SizedBox(height: 10),
                Text(
                  tutorial.title,
                  style: const TextStyle(
                    fontFamily: 'Poppins',
                    fontSize: 18,
                    fontWeight: FontWeight.w600,
                    color: Colors.white,
                    height: 1.3,
                  ),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildSectionTitle(String title, IconData icon, Color color) {
    return Row(
      children: [
        Container(
          padding: const EdgeInsets.all(8),
          decoration: BoxDecoration(
            color: color.withValues(alpha: 0.1),
            borderRadius: BorderRadius.circular(10),
          ),
          child: Icon(icon, size: 18, color: color),
        ),
        const SizedBox(width: 10),
        Text(
          title,
          style: const TextStyle(
            fontFamily: 'Poppins',
            fontSize: 15,
            fontWeight: FontWeight.w600,
            color: _textPrimary,
          ),
        ),
      ],
    );
  }

  Widget _buildMedia1Section(Tutorial tutorial) {
    // Fix localhost URL if needed
    final mediaUrl = tutorial.media1 != null
        ? AppConstants.fixMediaUrl(tutorial.media1!)
        : null;

    if (tutorial.isMedia1Video) {
      return _buildVideoPlayer();
    }

    if (tutorial.isMedia1Image && mediaUrl != null) {
      return _buildImageViewer(mediaUrl);
    }

    // Generic media - show download button
    if (mediaUrl != null) {
      return _buildDownloadButton(
        label: _getFilenameFromUrl(mediaUrl),
        onTap: () => _openUrl(mediaUrl),
        icon: Iconsax.document_download,
        color: _primaryBlue,
      );
    }

    return const SizedBox.shrink();
  }

  Widget _buildVideoPlayer() {
    if (_videoHasError) {
      return _buildVideoError(_videoErrorMessage);
    }

    if (!_isVideoInitialized) {
      return Container(
        height: 200,
        decoration: BoxDecoration(
          color: Colors.black12,
          borderRadius: BorderRadius.circular(16),
        ),
        child: const Center(
          child: Column(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              CircularProgressIndicator(color: _primaryBlue),
              SizedBox(height: 12),
              Text(
                'Memuat video...',
                style: TextStyle(
                  fontFamily: 'Poppins',
                  fontSize: 13,
                  color: _textSecondary,
                ),
              ),
            ],
          ),
        ),
      );
    }

    return ClipRRect(
      borderRadius: BorderRadius.circular(16),
      child: AspectRatio(
        aspectRatio: _videoController!.value.aspectRatio,
        child: Chewie(controller: _chewieController!),
      ),
    );
  }

  Widget _buildVideoError(String message) {
    return Container(
      padding: const EdgeInsets.all(24),
      decoration: BoxDecoration(
        color: _errorRed.withValues(alpha: 0.1),
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: _errorRed.withValues(alpha: 0.3)),
      ),
      child: Column(
        children: [
          const Icon(Iconsax.video_slash, size: 48, color: _errorRed),
          const SizedBox(height: 12),
          const Text(
            'Gagal memuat video',
            style: TextStyle(
              fontFamily: 'Poppins',
              fontSize: 15,
              fontWeight: FontWeight.w600,
              color: _errorRed,
            ),
          ),
          const SizedBox(height: 4),
          Text(
            message.isNotEmpty ? message : 'Terjadi kesalahan saat memuat video',
            style: const TextStyle(
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

  Widget _buildImageViewer(String imageUrl) {
    return ClipRRect(
      borderRadius: BorderRadius.circular(16),
      child: Container(
        constraints: const BoxConstraints(maxHeight: 300),
        width: double.infinity,
        decoration: BoxDecoration(
          color: Colors.grey[200],
          borderRadius: BorderRadius.circular(16),
        ),
        child: Image.network(
          imageUrl,
          fit: BoxFit.cover,
          loadingBuilder: (context, child, loadingProgress) {
            if (loadingProgress == null) return child;
            return Container(
              height: 200,
              color: Colors.grey[200],
              child: Center(
                child: CircularProgressIndicator(
                  value: loadingProgress.expectedTotalBytes != null
                      ? loadingProgress.cumulativeBytesLoaded /
                          loadingProgress.expectedTotalBytes!
                      : null,
                  color: _primaryBlue,
                ),
              ),
            );
          },
          errorBuilder: (context, error, stackTrace) {
            return Container(
              height: 200,
              color: Colors.grey[200],
              child: Column(
                mainAxisAlignment: MainAxisAlignment.center,
                children: [
                  Icon(Iconsax.gallery_slash, size: 48, color: Colors.grey[400]),
                  const SizedBox(height: 8),
                  Text(
                    'Gagal memuat gambar',
                    style: TextStyle(
                      fontFamily: 'Poppins',
                      fontSize: 13,
                      color: Colors.grey[600],
                    ),
                  ),
                ],
              ),
            );
          },
        ),
      ),
    );
  }

  Widget _buildMedia2Section(Tutorial tutorial) {
    // Fix localhost URL if needed
    final mediaUrl = tutorial.media2 != null
        ? AppConstants.fixMediaUrl(tutorial.media2!)
        : null;

    if (mediaUrl == null) return const SizedBox.shrink();

    if (tutorial.isMedia2Pdf) {
      return _buildDownloadButton(
        label: 'Buka Dokumen PDF',
        subtitle: _getFilenameFromUrl(mediaUrl),
        onTap: () => _openUrl(mediaUrl),
        icon: Iconsax.document,
        color: _warningAmber,
      );
    }

    if (tutorial.isMedia2Image) {
      return _buildImageViewer(mediaUrl);
    }

    return _buildDownloadButton(
      label: 'Unduh Lampiran',
      subtitle: _getFilenameFromUrl(mediaUrl),
      onTap: () => _openUrl(mediaUrl),
      icon: Iconsax.document_download,
      color: _primaryBlue,
    );
  }

  Widget _buildDownloadButton({
    required String label,
    String? subtitle,
    required VoidCallback onTap,
    required IconData icon,
    required Color color,
  }) {
    return Material(
      color: Colors.transparent,
      child: InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(16),
        child: Container(
          padding: const EdgeInsets.all(16),
          decoration: BoxDecoration(
            color: Colors.white,
            borderRadius: BorderRadius.circular(16),
            border: Border.all(color: color.withValues(alpha: 0.3)),
            boxShadow: [
              BoxShadow(
                color: Colors.black.withValues(alpha: 0.04),
                blurRadius: 8,
                offset: const Offset(0, 2),
              ),
            ],
          ),
          child: Row(
            children: [
              Container(
                padding: const EdgeInsets.all(12),
                decoration: BoxDecoration(
                  color: color.withValues(alpha: 0.1),
                  borderRadius: BorderRadius.circular(12),
                ),
                child: Icon(icon, color: color, size: 24),
              ),
              const SizedBox(width: 14),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      label,
                      style: TextStyle(
                        fontFamily: 'Poppins',
                        fontSize: 14,
                        fontWeight: FontWeight.w600,
                        color: color,
                      ),
                    ),
                    if (subtitle != null) ...[
                      const SizedBox(height: 2),
                      Text(
                        subtitle,
                        style: const TextStyle(
                          fontFamily: 'Poppins',
                          fontSize: 12,
                          color: _textSecondary,
                        ),
                        maxLines: 1,
                        overflow: TextOverflow.ellipsis,
                      ),
                    ],
                  ],
                ),
              ),
              Container(
                padding: const EdgeInsets.all(8),
                decoration: BoxDecoration(
                  color: color.withValues(alpha: 0.1),
                  borderRadius: BorderRadius.circular(10),
                ),
                child: Icon(Iconsax.export_1, size: 18, color: color),
              ),
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildDescriptionCard(Tutorial tutorial) {
    return Container(
      width: double.infinity,
      padding: const EdgeInsets.all(20),
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
      child: Text(
        tutorial.description,
        style: const TextStyle(
          fontFamily: 'Poppins',
          fontSize: 14,
          color: _textPrimary,
          height: 1.6,
        ),
      ),
    );
  }

  Color _getMediaColor(Tutorial tutorial) {
    if (tutorial.isMedia1Video) return _errorRed;
    if (tutorial.isMedia1Image) return _successGreen;
    return _purpleAccent;
  }

  IconData _getMediaIcon(Tutorial tutorial) {
    if (tutorial.isMedia1Video) return Iconsax.video_play;
    if (tutorial.isMedia1Image) return Iconsax.image;
    return Iconsax.document_text;
  }

  Widget _buildLoadingState() {
    return Center(
      child: Column(
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          Container(
            padding: const EdgeInsets.all(20),
            decoration: BoxDecoration(
              color: Colors.white,
              borderRadius: BorderRadius.circular(20),
              boxShadow: [
                BoxShadow(
                  color: Colors.black.withValues(alpha: 0.05),
                  blurRadius: 20,
                ),
              ],
            ),
            child: const CircularProgressIndicator(
              color: _primaryBlue,
              strokeWidth: 3,
            ),
          ),
          const SizedBox(height: 20),
          const Text(
            'Memuat detail...',
            style: TextStyle(
              fontFamily: 'Poppins',
              fontSize: 14,
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
        padding: const EdgeInsets.all(32),
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Container(
              padding: const EdgeInsets.all(28),
              decoration: BoxDecoration(
                color: _errorRed.withValues(alpha: 0.1),
                shape: BoxShape.circle,
              ),
              child: const Icon(
                Iconsax.warning_2,
                size: 64,
                color: _errorRed,
              ),
            ),
            const SizedBox(height: 28),
            const Text(
              'Terjadi Kesalahan',
              style: TextStyle(
                fontFamily: 'Poppins',
                fontSize: 20,
                fontWeight: FontWeight.w600,
                color: _textPrimary,
              ),
            ),
            const SizedBox(height: 10),
            Text(
              message,
              textAlign: TextAlign.center,
              style: const TextStyle(
                fontFamily: 'Poppins',
                fontSize: 14,
                color: _textSecondary,
                height: 1.5,
              ),
            ),
            const SizedBox(height: 28),
            ElevatedButton.icon(
              onPressed: _loadData,
              icon: const Icon(Icons.refresh_rounded, size: 18),
              label: const Text('Coba Lagi'),
              style: ElevatedButton.styleFrom(
                backgroundColor: _primaryBlue,
                foregroundColor: Colors.white,
                elevation: 0,
                padding: const EdgeInsets.symmetric(horizontal: 28, vertical: 14),
                shape: RoundedRectangleBorder(
                  borderRadius: BorderRadius.circular(14),
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }
}
