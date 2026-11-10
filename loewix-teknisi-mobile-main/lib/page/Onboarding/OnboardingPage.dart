import 'package:flutter/material.dart';
import '../Auth/LoginPage.dart';

class OnboardingPage extends StatefulWidget {
  static const routeName = '/onboarding_page';

  const OnboardingPage({super.key});

  @override
  State<OnboardingPage> createState() => _OnboardingPageState();
}

class _OnboardingPageState extends State<OnboardingPage>
    with SingleTickerProviderStateMixin {
  final PageController _pageController = PageController();
  int _currentPage = 0;
  bool _isButtonPressed = false;
  double _buttonDragOffset = 0.0;

  static const Color _darkShape = Color(0xFF0F172A);
  static const Color _textDark = Color(0xFF0F172A);
  static const Color _textMuted = Color(0xFF64748B);
  static const Color _primaryBlue = Color(0xFF1E40AF);

  final List<Map<String, String>> _slides = [
    {
      'title': 'Instalasi CCTV\nJadi Lebih Mudah',
      'subtitle':
          'Cepat, aman, dan pantau seluruh pengerjaan teknisi dalam satu aplikasi terintegrasi.',
      'tag': 'TEKNOLOGI CCTV LOEWIX',
    },
    {
      'title': 'Navigasi GPS\n& Presisi Lokasi',
      'subtitle':
          'Rute terarah menuju lokasi pelanggan dengan akurasi koordinat titik pemasangan.',
      'tag': 'NAVIGASI & PRESENSI',
    },
    {
      'title': 'Laporan Instan\n& Bonus Terjamin',
      'subtitle':
          'Upload foto hasil instalasi langsung dari smartphone dan lacak pencapaian komisi Anda.',
      'tag': 'INSENTIF & KOMISI',
    },
  ];

  void _onNext() {
    if (_currentPage < _slides.length - 1) {
      _pageController.animateToPage(
        _currentPage + 1,
        duration: const Duration(milliseconds: 380),
        curve: Curves.easeInOutCubic,
      );
    } else {
      _goToLogin();
    }
  }

  void _onPrev() {
    if (_currentPage > 0) {
      _pageController.animateToPage(
        _currentPage - 1,
        duration: const Duration(milliseconds: 380),
        curve: Curves.easeInOutCubic,
      );
    }
  }

  void _goToLogin() {
    Navigator.pushReplacement(
      context,
      MaterialPageRoute(builder: (context) => const LoginPage()),
    );
  }

  @override
  void dispose() {
    _pageController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: Colors.white,
      body: Stack(
        children: [
          SafeArea(
            child: Column(
              children: [
                // ─── Top Bar: Loewix Logo & Skip Button ───
                Padding(
                  padding: const EdgeInsets.fromLTRB(24, 12, 24, 0),
                  child: Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      Row(
                        children: [
                          Image.asset(
                            'assets/icon.png',
                            width: 28,
                            height: 28,
                            errorBuilder: (_, __, ___) => const Icon(
                              Icons.videocam_rounded,
                              color: _primaryBlue,
                              size: 26,
                            ),
                          ),
                          const SizedBox(width: 8),
                          const Text(
                            'LOEWIX',
                            style: TextStyle(
                              fontFamily: 'Poppins',
                              fontSize: 15,
                              fontWeight: FontWeight.w900,
                              letterSpacing: 1.5,
                              color: _primaryBlue,
                            ),
                          ),
                          const SizedBox(width: 6),
                          Container(
                            padding: const EdgeInsets.symmetric(
                              horizontal: 6,
                              vertical: 2,
                            ),
                            decoration: BoxDecoration(
                              color: const Color(0xFFE0E7FF),
                              borderRadius: BorderRadius.circular(4),
                            ),
                            child: const Text(
                              'TEKNISI',
                              style: TextStyle(
                                fontFamily: 'Poppins',
                                fontSize: 9,
                                fontWeight: FontWeight.w800,
                                color: _primaryBlue,
                                letterSpacing: 0.5,
                              ),
                            ),
                          ),
                        ],
                      ),
                      GestureDetector(
                        onTap: _goToLogin,
                        child: const Padding(
                          padding:
                              EdgeInsets.symmetric(horizontal: 10, vertical: 8),
                          child: Text(
                            'Skip',
                            style: TextStyle(
                              fontFamily: 'Poppins',
                              fontSize: 15,
                              fontWeight: FontWeight.w600,
                              color: _textMuted,
                            ),
                          ),
                        ),
                      ),
                    ],
                  ),
                ),

                // ─── Center Interactive PageView (Clean & Fully Swipeable) ───
                Expanded(
                  child: PageView.builder(
                    controller: _pageController,
                    physics: const BouncingScrollPhysics(),
                    onPageChanged: (index) {
                      setState(() {
                        _currentPage = index;
                      });
                    },
                    itemCount: _slides.length,
                    itemBuilder: (context, index) {
                      final item = _slides[index];
                      return Column(
                        children: [
                          // ── Hero Model Image (Clean, Uncluttered, Seamless Bottom Fade) ──
                          Expanded(
                            flex: 12,
                            child: Container(
                              width: double.infinity,
                              alignment: Alignment.topCenter,
                              child: ShaderMask(
                                shaderCallback: (rect) {
                                  return const LinearGradient(
                                    begin: Alignment.topCenter,
                                    end: Alignment.bottomCenter,
                                    colors: [
                                      Colors.white,
                                      Colors.white,
                                      Colors.transparent,
                                    ],
                                    stops: [0.0, 0.78, 1.0],
                                  ).createShader(rect);
                                },
                                blendMode: BlendMode.dstIn,
                                child: Image.asset(
                                  'assets/onboarding_teknisi.png',
                                  fit: BoxFit.contain,
                                  alignment: Alignment.topCenter,
                                  width: double.infinity,
                                ),
                              ),
                            ),
                          ),

                          // ── Typography: Clean Headline & Subtitle ──
                          Expanded(
                            flex: 7,
                            child: Padding(
                              padding:
                                  const EdgeInsets.fromLTRB(32, 10, 32, 0),
                              child: Column(
                                crossAxisAlignment: CrossAxisAlignment.start,
                                children: [
                                  // Subtle Category Pill
                                  Container(
                                    padding: const EdgeInsets.symmetric(
                                      horizontal: 10,
                                      vertical: 4,
                                    ),
                                    decoration: BoxDecoration(
                                      color: const Color(0xFFF1F5F9),
                                      borderRadius: BorderRadius.circular(20),
                                    ),
                                    child: Text(
                                      item['tag'] ?? '',
                                      style: const TextStyle(
                                        fontFamily: 'Poppins',
                                        fontSize: 10,
                                        fontWeight: FontWeight.w700,
                                        letterSpacing: 0.8,
                                        color: _primaryBlue,
                                      ),
                                    ),
                                  ),
                                  const SizedBox(height: 10),
                                  Text(
                                    item['title'] ?? '',
                                    style: const TextStyle(
                                      fontFamily: 'Poppins',
                                      fontSize: 28,
                                      fontWeight: FontWeight.w900,
                                      color: _textDark,
                                      height: 1.22,
                                      letterSpacing: -0.5,
                                    ),
                                  ),
                                  const SizedBox(height: 10),
                                  Text(
                                    item['subtitle'] ?? '',
                                    style: const TextStyle(
                                      fontFamily: 'Poppins',
                                      fontSize: 14,
                                      fontWeight: FontWeight.w400,
                                      color: _textMuted,
                                      height: 1.5,
                                    ),
                                  ),
                                ],
                              ),
                            ),
                          ),
                        ],
                      );
                    },
                  ),
                ),

                // Clearance for bottom navigation
                const SizedBox(height: 80),
              ],
            ),
          ),

          // ─── 3. Bottom-Left: Interactive Pagination Dots [ — • • ] ───
          Positioned(
            bottom: 38,
            left: 32,
            child: Row(
              children: List.generate(
                _slides.length,
                (index) {
                  final bool isActive = _currentPage == index;
                  return GestureDetector(
                    onTap: () {
                      _pageController.animateToPage(
                        index,
                        duration: const Duration(milliseconds: 350),
                        curve: Curves.easeInOutCubic,
                      );
                    },
                    behavior: HitTestBehavior.opaque,
                    child: Padding(
                      padding: const EdgeInsets.symmetric(
                        vertical: 8,
                        horizontal: 2,
                      ),
                      child: AnimatedContainer(
                        duration: const Duration(milliseconds: 280),
                        height: 6,
                        width: isActive ? 24 : 6,
                        decoration: BoxDecoration(
                          color:
                              isActive ? _darkShape : const Color(0xFFCBD5E1),
                          borderRadius: BorderRadius.circular(10),
                        ),
                      ),
                    ),
                  );
                },
              ),
            ),
          ),

          // ─── 4. Bottom-Right: Corner Button (Zero Overflow, Tap & Drag Support) ───
          Positioned(
            bottom: 0,
            right: 0,
            child: _buildOrganicCornerButton(),
          ),
        ],
      ),
    );
  }

  // ════════════════════════════════════════════════════════════════════════════
  // ─── Organic Corner Button (No Overflow, Perfectly Sized) ───
  // ════════════════════════════════════════════════════════════════════════════

  Widget _buildOrganicCornerButton() {
    final bool isLast = _currentPage == _slides.length - 1;
    // Sized generously to guarantee ZERO overflow on any screen
    final double buttonWidth = isLast ? 215 : 130;
    const double buttonHeight = 110;

    return GestureDetector(
      behavior: HitTestBehavior.opaque,
      onTapDown: (_) {
        setState(() {
          _isButtonPressed = true;
        });
      },
      onTapUp: (_) {
        setState(() {
          _isButtonPressed = false;
        });
        _onNext();
      },
      onTapCancel: () {
        setState(() {
          _isButtonPressed = false;
        });
      },
      onHorizontalDragUpdate: (details) {
        setState(() {
          _buttonDragOffset =
              (_buttonDragOffset + details.delta.dx).clamp(-40.0, 40.0);
        });
      },
      onHorizontalDragEnd: (details) {
        if (_buttonDragOffset < -15 ||
            (details.primaryVelocity != null &&
                details.primaryVelocity! < -120)) {
          _onNext();
        } else if (_buttonDragOffset > 15 ||
            (details.primaryVelocity != null &&
                details.primaryVelocity! > 120)) {
          _onPrev();
        }
        setState(() {
          _buttonDragOffset = 0.0;
        });
      },
      child: AnimatedScale(
        scale: _isButtonPressed ? 0.94 : 1.0,
        duration: const Duration(milliseconds: 120),
        curve: Curves.easeOut,
        child: AnimatedContainer(
          duration: const Duration(milliseconds: 320),
          curve: Curves.easeInOutCubic,
          width: buttonWidth,
          height: buttonHeight,
          child: Stack(
            alignment: Alignment.bottomRight,
            children: [
              // Dark Organic Background Scoop
              Positioned.fill(
                child: CustomPaint(
                  painter: OrganicCornerPainter(
                    color: _darkShape,
                    shadowColor: _darkShape.withOpacity(0.32),
                  ),
                ),
              ),

              // Content: Text (on last slide) + Floating White Circle
              Padding(
                padding: const EdgeInsets.fromLTRB(16, 10, 16, 14),
                child: Row(
                  mainAxisSize: MainAxisSize.min,
                  crossAxisAlignment: CrossAxisAlignment.center,
                  children: [
                    if (isLast)
                      const Padding(
                        padding: EdgeInsets.only(right: 12),
                        child: Text(
                          'Mulai Tugas',
                          style: TextStyle(
                            fontFamily: 'Poppins',
                            fontSize: 15,
                            fontWeight: FontWeight.w700,
                            color: Colors.white,
                            letterSpacing: 0.3,
                          ),
                        ),
                      ),

                    // Floating Tactile White Circular Button
                    Transform.translate(
                      offset: Offset(_buttonDragOffset * 0.4, 0),
                      child: Container(
                        width: 56,
                        height: 56,
                        decoration: BoxDecoration(
                          color: Colors.white,
                          shape: BoxShape.circle,
                          boxShadow: [
                            BoxShadow(
                              color: Colors.black.withOpacity(0.22),
                              blurRadius: 12,
                              offset: const Offset(0, 4),
                            ),
                            BoxShadow(
                              color: Colors.black.withOpacity(0.08),
                              blurRadius: 4,
                              offset: const Offset(0, 1),
                            ),
                          ],
                        ),
                        child: Center(
                          child: AnimatedSwitcher(
                            duration: const Duration(milliseconds: 250),
                            transitionBuilder: (child, animation) {
                              return ScaleTransition(
                                scale: animation,
                                child: FadeTransition(
                                  opacity: animation,
                                  child: child,
                                ),
                              );
                            },
                            child: isLast
                                ? const Icon(
                                    Icons.arrow_forward_rounded,
                                    key: ValueKey('icon_next'),
                                    color: _darkShape,
                                    size: 26,
                                  )
                                : Text(
                                    '${_currentPage + 1}',
                                    key: ValueKey('page_num_$_currentPage'),
                                    style: const TextStyle(
                                      fontFamily: 'Poppins',
                                      fontSize: 22,
                                      fontWeight: FontWeight.w900,
                                      color: _darkShape,
                                    ),
                                  ),
                          ),
                        ),
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
}

// ══════════════════════════════════════════════════════════════════════════════
// ─── Custom Painter for Organic Scooped Notch / Liquid Corner ───
// ══════════════════════════════════════════════════════════════════════════════

class OrganicCornerPainter extends CustomPainter {
  final Color color;
  final Color shadowColor;

  OrganicCornerPainter({
    required this.color,
    required this.shadowColor,
  });

  @override
  bool? hitTest(Offset position) => true;

  @override
  void paint(Canvas canvas, Size size) {
    final double w = size.width;
    final double h = size.height;

    final Path path = Path();
    path.moveTo(w, 0);

    // Smooth organic concave scoop
    path.cubicTo(
      w,
      h * 0.25,
      w * 0.65,
      h * 0.05,
      w * 0.45,
      0,
    );

    // Rounding left bulb
    path.cubicTo(
      w * 0.1,
      0,
      0,
      h * 0.35,
      0,
      h * 0.65,
    );

    // Lower curve rounding to bottom
    path.cubicTo(
      0,
      h * 0.88,
      w * 0.15,
      h,
      w * 0.45,
      h,
    );

    path.lineTo(w, h);
    path.close();

    // Soft drop shadow
    final Paint shadowPaint = Paint()
      ..color = shadowColor
      ..maskFilter = const MaskFilter.blur(BlurStyle.normal, 14);
    canvas.drawPath(path.shift(const Offset(-3, -3)), shadowPaint);

    // Smooth dark gradient fill
    final Paint fillPaint = Paint()
      ..shader = const LinearGradient(
        begin: Alignment.topLeft,
        end: Alignment.bottomRight,
        colors: [
          Color(0xFF1E293B), // Slate 800
          Color(0xFF0F172A), // Slate 900
          Color(0xFF020617), // Deep Dark 950
        ],
      ).createShader(Rect.fromLTWH(0, 0, w, h));

    canvas.drawPath(path, fillPaint);
  }

  @override
  bool shouldRepaint(covariant OrganicCornerPainter oldDelegate) {
    return oldDelegate.color != color || oldDelegate.shadowColor != shadowColor;
  }
}
