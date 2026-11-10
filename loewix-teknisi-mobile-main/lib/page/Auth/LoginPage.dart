import 'dart:convert';
import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:provider/provider.dart';
import 'package:shared_preferences/shared_preferences.dart';
import 'package:url_launcher/url_launcher.dart';

import '../../service/model/auth/LoginResponse.dart';
import '../../service/provider/Auth/AuthProvider.dart';
import '../../service/provider/Auth/TeknisiLoginProvider.dart';
import '../../service/provider/preferences/PreferencesIDProvider.dart';
import '../../service/provider/preferences/PreferencesTokenProvider.dart';
import '../../constants/app_constants.dart';
import '../container/HomePage.dart';
import '../Onboarding/OnboardingPage.dart';

class LoginPage extends StatefulWidget {
  static const routeName = 'login_page';

  const LoginPage({super.key});

  @override
  State<LoginPage> createState() => _LoginPageState();
}

class _LoginPageState extends State<LoginPage>
    with SingleTickerProviderStateMixin {
  // ─── Ultra-Clean White Palette (Matching Reference Image 2 in Clean White) ───
  static const Color _bgWhite = Colors.white;
  static const Color _inputBg = Color(0xFFF8FAFC);
  static const Color _inputBorder = Color(0xFFE2E8F0);
  static const Color _inputBorderFocused = Color(0xFF0F172A);
  static const Color _textPrimary = Color(0xFF0F172A);
  static const Color _textMuted = Color(0xFF64748B);
  static const Color _buttonBg = Color(0xFF0F172A);
  static const Color _buttonText = Colors.white;
  static const Color _tileBg = Color(0xFFF8FAFC);
  static const Color _tileBorder = Color(0xFFE2E8F0);
  static const Color _primaryBlue = Color(0xFF1E40AF);
  static const Color _rose = Color(0xFFEF4444);

  final _formKey = GlobalKey<FormState>();
  final _usernameController = TextEditingController();
  final _passwordController = TextEditingController();

  bool _isLoading = false;
  bool _obscurePassword = true;
  String? _errorMessage;
  bool _rememberMe = true;

  // Multi-account saved credentials
  static const String _prefSavedAccounts = 'saved_accounts_list';
  List<Map<String, String>> _savedAccounts = [];

  late AnimationController _animationController;
  late Animation<double> _fadeAnimation;
  late Animation<Offset> _slideAnimation;

  @override
  void initState() {
    super.initState();
    _animationController = AnimationController(
      vsync: this,
      duration: const Duration(milliseconds: 550),
    );

    _fadeAnimation = Tween<double>(begin: 0.0, end: 1.0).animate(
      CurvedAnimation(parent: _animationController, curve: Curves.easeOutCubic),
    );

    _slideAnimation = Tween<Offset>(
      begin: const Offset(0, 0.06),
      end: Offset.zero,
    ).animate(
      CurvedAnimation(parent: _animationController, curve: Curves.easeOutCubic),
    );

    _animationController.forward();
    _loadSavedAccounts();
  }

  Future<void> _loadSavedAccounts() async {
    final prefs = await SharedPreferences.getInstance();
    final jsonStr = prefs.getString(_prefSavedAccounts);
    if (jsonStr != null && jsonStr.isNotEmpty) {
      try {
        final List<dynamic> decoded = json.decode(jsonStr);
        _savedAccounts =
            decoded.map((e) => Map<String, String>.from(e)).toList();
        if (_savedAccounts.isNotEmpty) {
          setState(() {
            _usernameController.text = _savedAccounts.first['username'] ?? '';
            _passwordController.text = _savedAccounts.first['password'] ?? '';
            _rememberMe = true;
          });
        }
      } catch (_) {
        _savedAccounts = [];
      }
    }
  }

  Future<void> _saveAccount(
      String username, String password, String nama) async {
    _savedAccounts.removeWhere((a) => a['username'] == username);
    _savedAccounts.insert(0, {
      'username': username,
      'password': password,
      'nama': nama,
    });
    if (_savedAccounts.length > 10) {
      _savedAccounts = _savedAccounts.sublist(0, 10);
    }
    final prefs = await SharedPreferences.getInstance();
    await prefs.setString(_prefSavedAccounts, json.encode(_savedAccounts));
  }

  Future<void> _removeAccount(String username) async {
    _savedAccounts.removeWhere((a) => a['username'] == username);
    final prefs = await SharedPreferences.getInstance();
    await prefs.setString(_prefSavedAccounts, json.encode(_savedAccounts));
  }

  @override
  void dispose() {
    _usernameController.dispose();
    _passwordController.dispose();
    _animationController.dispose();
    super.dispose();
  }

  void _clearError() {
    if (_errorMessage != null) {
      setState(() => _errorMessage = null);
    }
  }

  Future<void> _login() async {
    _clearError();

    if (!_formKey.currentState!.validate()) {
      return;
    }

    setState(() => _isLoading = true);

    try {
      final loginProvider =
          Provider.of<TeknisiLoginProvider>(context, listen: false);
      final result = await loginProvider.doLogin(
        _usernameController.text.trim(),
        _passwordController.text,
      );

      if (!mounted) return;

      if (result != null) {
        await _handleSuccessLogin(result);
      } else {
        _showError('Username atau password tidak cocok.');
      }
    } catch (e) {
      if (!mounted) return;
      _showError(_parseError(e));
    } finally {
      if (mounted) {
        setState(() => _isLoading = false);
      }
    }
  }

  Future<void> _handleSuccessLogin(LoginResponse response) async {
    try {
      final authProvider = Provider.of<AuthProvider>(context, listen: false);
      final tokenProvider =
          Provider.of<PreferencesTokenProvider>(context, listen: false);
      final idProvider =
          Provider.of<PreferencesIDProvider>(context, listen: false);

      await authProvider.login();
      tokenProvider.setUserToken(response.token);
      await idProvider.setUserRole(response.user.teknisiId.toString());
      await idProvider.getUserRolePreferences();

      if (_rememberMe) {
        await _saveAccount(
          _usernameController.text.trim(),
          _passwordController.text,
          response.user.nama,
        );
      }

      if (!mounted) return;

      _showSuccessSnackBar('Selamat datang, ${response.user.nama}!');

      Navigator.pushAndRemoveUntil(
        context,
        MaterialPageRoute(builder: (context) => const HomePageAdmin()),
        (Route<dynamic> route) => false,
      );
    } catch (e) {
      _showError('Terjadi kesalahan saat menyimpan data login.');
    }
  }

  String _parseError(dynamic error) {
    final errorStr = error.toString().toLowerCase();

    if (errorStr.contains('socketexception') ||
        errorStr.contains('connection refused') ||
        errorStr.contains('network')) {
      return 'Tidak dapat terhubung ke server. Periksa koneksi internet Anda.';
    }

    if (errorStr.contains('timeout')) {
      return 'Koneksi timeout. Silakan coba lagi.';
    }

    if (errorStr.contains('unauthorized') || errorStr.contains('401')) {
      return 'Username atau password salah.';
    }

    if (errorStr.contains('exception:')) {
      return error.toString().replaceAll('Exception:', '').trim();
    }

    return 'Terjadi kesalahan sistem. Silakan coba lagi.';
  }

  void _showError(String message) {
    setState(() => _errorMessage = message);
    HapticFeedback.mediumImpact();
  }

  void _showSuccessSnackBar(String message) {
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Row(
          children: [
            const Icon(Icons.check_circle_rounded, color: Colors.white),
            const SizedBox(width: 12),
            Expanded(
              child: Text(
                message,
                style: const TextStyle(
                  fontFamily: 'Poppins',
                  fontWeight: FontWeight.w500,
                  color: Colors.white,
                ),
              ),
            ),
          ],
        ),
        backgroundColor: const Color(0xFF10B981),
        behavior: SnackBarBehavior.floating,
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
        margin: const EdgeInsets.all(16),
      ),
    );
  }

  void _onBack() {
    if (Navigator.canPop(context)) {
      Navigator.pop(context);
    } else {
      Navigator.pushReplacement(
        context,
        MaterialPageRoute(builder: (context) => const OnboardingPage()),
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: _bgWhite,
      body: SafeArea(
        child: FadeTransition(
          opacity: _fadeAnimation,
          child: SlideTransition(
            position: _slideAnimation,
            child: Column(
              children: [
                // ─── Top Bar (Back Arrow & Trouble Logging In? Help) ───
                _buildTopBar(),

                // ─── Main Content (Scrollable, Clean White) ───
                Expanded(
                  child: SingleChildScrollView(
                    padding: const EdgeInsets.symmetric(horizontal: 24.0),
                    child: Form(
                      key: _formKey,
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          const SizedBox(height: 32),

                          // ─── Typography: Bold & Minimalist ───
                          const Text(
                            'Welcome\nback to Loewix',
                            style: TextStyle(
                              fontFamily: 'Poppins',
                              fontSize: 32,
                              fontWeight: FontWeight.w800,
                              color: _textPrimary,
                              height: 1.25,
                              letterSpacing: -0.8,
                            ),
                          ),
                          const SizedBox(height: 8),
                          const Text(
                            'Masuk ke akun teknisi Anda untuk memulai tugas',
                            style: TextStyle(
                              fontFamily: 'Poppins',
                              fontSize: 13,
                              fontWeight: FontWeight.w400,
                              color: _textMuted,
                            ),
                          ),

                          const SizedBox(height: 38),

                          // Error Banner if exists
                          if (_errorMessage != null) _buildErrorBanner(),

                          // ─── Form Inputs: Clean Pill Inputs ───
                          _buildUsernameField(),
                          const SizedBox(height: 16),
                          _buildPasswordField(),

                          const SizedBox(height: 14),

                          // ─── Options: Remember Me & Forgot Password ───
                          _buildOptionsRow(),

                          const SizedBox(height: 32),

                          // ─── Primary Action: Clean Dark Pill Button (High Contrast) ───
                          _buildSignInButton(),

                          const SizedBox(height: 32),

                          // ─── Divider "Or" ───
                          Center(
                            child: Text(
                              'Or',
                              style: TextStyle(
                                fontFamily: 'Poppins',
                                fontSize: 13,
                                fontWeight: FontWeight.w500,
                                color: _textMuted.withOpacity(0.8),
                              ),
                            ),
                          ),

                          const SizedBox(height: 24),

                          // ─── Alternative Quick Actions (Saved Accounts & CS) ───
                          _buildQuickActionsRow(),

                          const SizedBox(height: 48),

                          // ─── Bottom Link & Version ───
                          _buildBottomSection(),
                          const SizedBox(height: 24),
                        ],
                      ),
                    ),
                  ),
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }

  // ════════════════════════════════════════════════════════════════════════════
  // ─── Top Bar (Matching Image 2 Reference in White Theme) ───
  // ════════════════════════════════════════════════════════════════════════════

  Widget _buildTopBar() {
    return Padding(
      padding: const EdgeInsets.fromLTRB(14, 8, 20, 0),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceBetween,
        children: [
          // Left Back Button
          IconButton(
            onPressed: _onBack,
            icon: const Icon(
              Icons.arrow_back_rounded,
              color: _textPrimary,
              size: 24,
            ),
            splashRadius: 22,
          ),

          // Right "Trouble logging in? Help"
          Row(
            mainAxisSize: MainAxisSize.min,
            children: [
              const Text(
                'Trouble logging in? ',
                style: TextStyle(
                  fontFamily: 'Poppins',
                  fontSize: 13,
                  fontWeight: FontWeight.w400,
                  color: _textMuted,
                ),
              ),
              GestureDetector(
                onTap: _showHelpSheet,
                child: const Text(
                  'Help',
                  style: TextStyle(
                    fontFamily: 'Poppins',
                    fontSize: 13,
                    fontWeight: FontWeight.w700,
                    color: _textPrimary,
                  ),
                ),
              ),
            ],
          ),
        ],
      ),
    );
  }

  // ════════════════════════════════════════════════════════════════════════════
  // ─── Input Fields: Minimalist Rounded Pills on White Background ───
  // ════════════════════════════════════════════════════════════════════════════

  Widget _buildUsernameField() {
    return TextFormField(
      controller: _usernameController,
      keyboardType: TextInputType.text,
      textInputAction: TextInputAction.next,
      enabled: !_isLoading,
      onChanged: (_) => _clearError(),
      style: const TextStyle(
        fontFamily: 'Poppins',
        fontSize: 15,
        color: _textPrimary,
      ),
      cursorColor: _textPrimary,
      decoration: InputDecoration(
        hintText: 'Phone, email, username',
        hintStyle: const TextStyle(
          fontFamily: 'Poppins',
          fontSize: 14,
          color: _textMuted,
        ),
        filled: true,
        fillColor: _inputBg,
        contentPadding:
            const EdgeInsets.symmetric(horizontal: 24, vertical: 19),
        border: OutlineInputBorder(
          borderRadius: BorderRadius.circular(32),
          borderSide: const BorderSide(color: _inputBorder, width: 1.0),
        ),
        enabledBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(32),
          borderSide: const BorderSide(color: _inputBorder, width: 1.0),
        ),
        focusedBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(32),
          borderSide: const BorderSide(color: _inputBorderFocused, width: 1.5),
        ),
        errorBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(32),
          borderSide: const BorderSide(color: _rose, width: 1.2),
        ),
        focusedErrorBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(32),
          borderSide: const BorderSide(color: _rose, width: 1.5),
        ),
        errorStyle: const TextStyle(
          fontFamily: 'Poppins',
          fontSize: 12,
          color: _rose,
        ),
        suffixIcon: _savedAccounts.isNotEmpty
            ? IconButton(
                icon: const Icon(
                  Icons.arrow_drop_down_rounded,
                  color: _textMuted,
                  size: 28,
                ),
                onPressed: _showSavedAccountsPicker,
              )
            : null,
      ),
      validator: (value) {
        if (value == null || value.trim().isEmpty) {
          return 'Masukkan username akun Anda';
        }
        return null;
      },
    );
  }

  Widget _buildPasswordField() {
    return TextFormField(
      controller: _passwordController,
      obscureText: _obscurePassword,
      textInputAction: TextInputAction.done,
      enabled: !_isLoading,
      onChanged: (_) => _clearError(),
      onFieldSubmitted: (_) => _login(),
      style: const TextStyle(
        fontFamily: 'Poppins',
        fontSize: 15,
        color: _textPrimary,
        letterSpacing: 1.5,
      ),
      cursorColor: _textPrimary,
      decoration: InputDecoration(
        hintText: 'Password',
        hintStyle: const TextStyle(
          fontFamily: 'Poppins',
          fontSize: 14,
          letterSpacing: 0,
          color: _textMuted,
        ),
        filled: true,
        fillColor: _inputBg,
        contentPadding:
            const EdgeInsets.symmetric(horizontal: 24, vertical: 19),
        border: OutlineInputBorder(
          borderRadius: BorderRadius.circular(32),
          borderSide: const BorderSide(color: _inputBorder, width: 1.0),
        ),
        enabledBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(32),
          borderSide: const BorderSide(color: _inputBorder, width: 1.0),
        ),
        focusedBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(32),
          borderSide: const BorderSide(color: _inputBorderFocused, width: 1.5),
        ),
        errorBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(32),
          borderSide: const BorderSide(color: _rose, width: 1.2),
        ),
        focusedErrorBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(32),
          borderSide: const BorderSide(color: _rose, width: 1.5),
        ),
        errorStyle: const TextStyle(
          fontFamily: 'Poppins',
          fontSize: 12,
          color: _rose,
        ),
        suffixIcon: Padding(
          padding: const EdgeInsets.only(right: 8.0),
          child: IconButton(
            icon: Icon(
              _obscurePassword
                  ? Icons.visibility_off_outlined
                  : Icons.visibility_outlined,
              color: _textMuted,
              size: 21,
            ),
            onPressed: () {
              setState(() {
                _obscurePassword = !_obscurePassword;
              });
            },
          ),
        ),
      ),
      validator: (value) {
        if (value == null || value.isEmpty) {
          return 'Masukkan password akun Anda';
        }
        return null;
      },
    );
  }

  // ════════════════════════════════════════════════════════════════════════════
  // ─── Options Row: Remember Me & Forgot Password ───
  // ════════════════════════════════════════════════════════════════════════════

  Widget _buildOptionsRow() {
    return Row(
      mainAxisAlignment: MainAxisAlignment.spaceBetween,
      children: [
        // Remember Me
        GestureDetector(
          onTap: () {
            setState(() {
              _rememberMe = !_rememberMe;
            });
          },
          behavior: HitTestBehavior.opaque,
          child: Row(
            mainAxisSize: MainAxisSize.min,
            children: [
              Container(
                width: 18,
                height: 18,
                decoration: BoxDecoration(
                  borderRadius: BorderRadius.circular(6),
                  border: Border.all(
                    color: _rememberMe
                        ? _primaryBlue
                        : _textMuted.withOpacity(0.5),
                    width: 1.5,
                  ),
                  color: _rememberMe ? _primaryBlue : Colors.transparent,
                ),
                child: _rememberMe
                    ? const Icon(Icons.check, size: 13, color: Colors.white)
                    : null,
              ),
              const SizedBox(width: 8),
              const Text(
                'Ingat Saya',
                style: TextStyle(
                  fontFamily: 'Poppins',
                  fontSize: 12,
                  fontWeight: FontWeight.w500,
                  color: _textPrimary,
                ),
              ),
            ],
          ),
        ),

        // Forgot Password?
        GestureDetector(
          onTap: _showForgotPasswordSheet,
          behavior: HitTestBehavior.opaque,
          child: const Text(
            'Forgot password?',
            style: TextStyle(
              fontFamily: 'Poppins',
              fontSize: 13,
              fontWeight: FontWeight.w500,
              color: _textMuted,
            ),
          ),
        ),
      ],
    );
  }

  // ════════════════════════════════════════════════════════════════════════════
  // ─── Primary Action: High-Contrast Solid Dark Pill Button ───
  // ════════════════════════════════════════════════════════════════════════════

  Widget _buildSignInButton() {
    return SizedBox(
      width: double.infinity,
      height: 56,
      child: ElevatedButton(
        onPressed: _isLoading ? null : _login,
        style: ElevatedButton.styleFrom(
          backgroundColor: _buttonBg,
          foregroundColor: _buttonText,
          disabledBackgroundColor: _buttonBg.withOpacity(0.6),
          elevation: 0,
          shape: RoundedRectangleBorder(
            borderRadius: BorderRadius.circular(32),
          ),
        ),
        child: _isLoading
            ? const SizedBox(
                width: 22,
                height: 22,
                child: CircularProgressIndicator(
                  color: _buttonText,
                  strokeWidth: 2.2,
                ),
              )
            : const Text(
                'Sign In',
                style: TextStyle(
                  fontFamily: 'Poppins',
                  fontSize: 16,
                  fontWeight: FontWeight.w700,
                  color: _buttonText,
                  letterSpacing: 0.2,
                ),
              ),
      ),
    );
  }

  // ════════════════════════════════════════════════════════════════════════════
  // ─── Quick Actions: Clean Rounded Tiles on White Background ───
  // ════════════════════════════════════════════════════════════════════════════

  Widget _buildQuickActionsRow() {
    return Row(
      mainAxisAlignment: MainAxisAlignment.center,
      children: [
        // Tile 1: Saved Accounts Picker
        _buildActionTile(
          icon: Icons.account_circle_outlined,
          tooltip: 'Akun Tersimpan',
          badgeText: _savedAccounts.isNotEmpty
              ? '${_savedAccounts.length}'
              : null,
          onTap: _showSavedAccountsPicker,
        ),
        const SizedBox(width: 20),

        // Tile 2: Helpdesk / Admin Support
        _buildActionTile(
          icon: Icons.support_agent_rounded,
          tooltip: 'Bantuan Support Admin',
          onTap: _openWhatsAppAdmin,
        ),
      ],
    );
  }

  Widget _buildActionTile({
    required IconData icon,
    required String tooltip,
    String? badgeText,
    required VoidCallback onTap,
  }) {
    return GestureDetector(
      onTap: onTap,
      child: Stack(
        clipBehavior: Clip.none,
        children: [
          Container(
            width: 62,
            height: 62,
            decoration: BoxDecoration(
              color: _tileBg,
              borderRadius: BorderRadius.circular(20),
              border: Border.all(
                color: _tileBorder,
                width: 1.2,
              ),
            ),
            child: Icon(
              icon,
              color: _textPrimary,
              size: 28,
            ),
          ),
          if (badgeText != null)
            Positioned(
              top: -4,
              right: -4,
              child: Container(
                padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
                decoration: BoxDecoration(
                  color: _primaryBlue,
                  borderRadius: BorderRadius.circular(10),
                ),
                child: Text(
                  badgeText,
                  style: const TextStyle(
                    fontFamily: 'Poppins',
                    fontSize: 10,
                    fontWeight: FontWeight.w700,
                    color: Colors.white,
                  ),
                ),
              ),
            ),
        ],
      ),
    );
  }

  // ════════════════════════════════════════════════════════════════════════════
  // ─── Bottom Footer (New to Loewix? Contact Admin) ───
  // ════════════════════════════════════════════════════════════════════════════

  Widget _buildBottomSection() {
    return Column(
      children: [
        Row(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            const Text(
              'New to Loewix? ',
              style: TextStyle(
                fontFamily: 'Poppins',
                fontSize: 13,
                fontWeight: FontWeight.w400,
                color: _textMuted,
              ),
            ),
            GestureDetector(
              onTap: _showHelpSheet,
              child: const Text(
                'Hubungi Admin',
                style: TextStyle(
                  fontFamily: 'Poppins',
                  fontSize: 13,
                  fontWeight: FontWeight.w700,
                  color: _textPrimary,
                ),
              ),
            ),
          ],
        ),
        const SizedBox(height: 12),
        Text(
          'v${AppConstants.appVersion} • Loewix Teknisi',
          style: TextStyle(
            fontFamily: 'Poppins',
            fontSize: 11,
            color: _textMuted.withOpacity(0.6),
          ),
        ),
      ],
    );
  }

  Widget _buildErrorBanner() {
    return Container(
      margin: const EdgeInsets.only(bottom: 20),
      padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
      decoration: BoxDecoration(
        color: _rose.withOpacity(0.08),
        borderRadius: BorderRadius.circular(20),
        border: Border.all(color: _rose.withOpacity(0.25)),
      ),
      child: Row(
        children: [
          const Icon(Icons.error_outline_rounded, color: _rose, size: 20),
          const SizedBox(width: 10),
          Expanded(
            child: Text(
              _errorMessage!,
              style: const TextStyle(
                fontFamily: 'Poppins',
                fontSize: 13,
                color: _rose,
                fontWeight: FontWeight.w500,
              ),
            ),
          ),
          GestureDetector(
            onTap: _clearError,
            child: Icon(Icons.close, color: _rose.withOpacity(0.7), size: 18),
          ),
        ],
      ),
    );
  }

  // ════════════════════════════════════════════════════════════════════════════
  // ─── Modal Sheets: Clean White Theme ───
  // ════════════════════════════════════════════════════════════════════════════

  void _showSavedAccountsPicker() {
    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (ctx) => Container(
        decoration: const BoxDecoration(
          color: Colors.white,
          borderRadius: BorderRadius.vertical(top: Radius.circular(28)),
        ),
        child: SafeArea(
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              // Handle Bar
              Container(
                margin: const EdgeInsets.only(top: 14),
                width: 38,
                height: 4,
                decoration: BoxDecoration(
                  color: const Color(0xFFE2E8F0),
                  borderRadius: BorderRadius.circular(2),
                ),
              ),

              Padding(
                padding: const EdgeInsets.fromLTRB(24, 20, 24, 12),
                child: Row(
                  children: [
                    Container(
                      padding: const EdgeInsets.all(10),
                      decoration: BoxDecoration(
                        color: _primaryBlue.withOpacity(0.1),
                        borderRadius: BorderRadius.circular(14),
                      ),
                      child: const Icon(Icons.people_alt_rounded,
                          color: _primaryBlue, size: 20),
                    ),
                    const SizedBox(width: 14),
                    const Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          'Akun Teknisi Tersimpan',
                          style: TextStyle(
                            fontFamily: 'Poppins',
                            fontSize: 16,
                            fontWeight: FontWeight.w700,
                            color: _textPrimary,
                          ),
                        ),
                        Text(
                          'Pilih akun untuk mengisi otomatis',
                          style: TextStyle(
                            fontFamily: 'Poppins',
                            fontSize: 12,
                            color: _textMuted,
                          ),
                        ),
                      ],
                    ),
                  ],
                ),
              ),
              const Divider(color: Color(0xFFF1F5F9), height: 1),

              if (_savedAccounts.isEmpty)
                const Padding(
                  padding: EdgeInsets.all(32),
                  child: Text(
                    'Belum ada akun yang tersimpan.\nCentang "Ingat Saya" saat login.',
                    textAlign: TextAlign.center,
                    style: TextStyle(
                      fontFamily: 'Poppins',
                      fontSize: 13,
                      color: _textMuted,
                    ),
                  ),
                )
              else
                ListView.builder(
                  shrinkWrap: true,
                  physics: const NeverScrollableScrollPhysics(),
                  padding: const EdgeInsets.symmetric(vertical: 8),
                  itemCount: _savedAccounts.length,
                  itemBuilder: (context, index) {
                    final account = _savedAccounts[index];
                    final username = account['username'] ?? '';
                    final nama = account['nama'] ?? username;
                    final password = account['password'] ?? '';
                    final isSelected = _usernameController.text == username;

                    return Dismissible(
                      key: Key(username),
                      direction: DismissDirection.endToStart,
                      background: Container(
                        alignment: Alignment.centerRight,
                        padding: const EdgeInsets.only(right: 24),
                        color: _rose.withOpacity(0.1),
                        child: const Icon(Icons.delete_outline_rounded,
                            color: _rose),
                      ),
                      onDismissed: (_) async {
                        await _removeAccount(username);
                        if (mounted) setState(() {});
                      },
                      child: ListTile(
                        contentPadding: const EdgeInsets.symmetric(
                            horizontal: 24, vertical: 4),
                        leading: Container(
                          width: 44,
                          height: 44,
                          decoration: BoxDecoration(
                            color: isSelected
                                ? _buttonBg
                                : const Color(0xFFF1F5F9),
                            borderRadius: BorderRadius.circular(14),
                          ),
                          child: Center(
                            child: Text(
                              nama.isNotEmpty ? nama[0].toUpperCase() : '?',
                              style: TextStyle(
                                fontFamily: 'Poppins',
                                fontSize: 18,
                                fontWeight: FontWeight.w700,
                                color: isSelected ? Colors.white : _primaryBlue,
                              ),
                            ),
                          ),
                        ),
                        title: Text(
                          nama,
                          style: TextStyle(
                            fontFamily: 'Poppins',
                            fontSize: 14,
                            fontWeight: isSelected
                                ? FontWeight.w700
                                : FontWeight.w600,
                            color: _textPrimary,
                          ),
                        ),
                        subtitle: Text(
                          username,
                          style: const TextStyle(
                            fontFamily: 'Poppins',
                            fontSize: 12,
                            color: _textMuted,
                          ),
                        ),
                        trailing: isSelected
                            ? const Icon(Icons.check_circle_rounded,
                                color: _primaryBlue, size: 22)
                            : const Icon(Icons.arrow_forward_ios_rounded,
                                size: 14, color: _textMuted),
                        onTap: () {
                          setState(() {
                            _usernameController.text = username;
                            _passwordController.text = password;
                            _rememberMe = true;
                          });
                          Navigator.pop(ctx);
                        },
                      ),
                    );
                  },
                ),

              Padding(
                padding: const EdgeInsets.fromLTRB(24, 8, 24, 18),
                child: TextButton.icon(
                  onPressed: () {
                    setState(() {
                      _usernameController.clear();
                      _passwordController.clear();
                    });
                    Navigator.pop(ctx);
                  },
                  icon: const Icon(Icons.add_circle_outline_rounded,
                      size: 18, color: _primaryBlue),
                  label: const Text(
                    'Gunakan Akun Baru',
                    style: TextStyle(
                      fontFamily: 'Poppins',
                      fontSize: 13,
                      fontWeight: FontWeight.w600,
                      color: _primaryBlue,
                    ),
                  ),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }

  Future<void> _openWhatsAppAdmin() async {
    const phoneNumber = '6285771593522';
    const rawNumber = '085771593522';
    const message = 'Halo Admin Loewix, saya butuh bantuan terkait akun teknisi saya.';
    
    // Direct WhatsApp app scheme
    final nativeUri = Uri.parse(
        'whatsapp://send?phone=$phoneNumber&text=${Uri.encodeComponent(message)}');
    // Web fallback
    final webUri = Uri.parse(
        'https://wa.me/$phoneNumber?text=${Uri.encodeComponent(message)}');

    try {
      if (await canLaunchUrl(nativeUri)) {
        await launchUrl(nativeUri, mode: LaunchMode.externalApplication);
        return;
      }
      if (await canLaunchUrl(webUri)) {
        await launchUrl(webUri, mode: LaunchMode.externalApplication);
        return;
      }
    } catch (_) {
      try {
        final fallbackWeb = Uri.parse(
            'https://api.whatsapp.com/send?phone=$phoneNumber&text=${Uri.encodeComponent(message)}');
        await launchUrl(fallbackWeb, mode: LaunchMode.externalApplication);
        return;
      } catch (_) {}
    }

    // Fallback if device has no WhatsApp & browser failed
    if (!mounted) return;
    Clipboard.setData(const ClipboardData(text: rawNumber));
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: const Row(
          children: [
            Icon(Icons.copy_rounded, color: Colors.white, size: 20),
            SizedBox(width: 12),
            Expanded(
              child: Text(
                'Nomor WA Admin (0857-7159-3522) disalin ke clipboard.',
                style: TextStyle(fontFamily: 'Poppins'),
              ),
            ),
          ],
        ),
        backgroundColor: const Color(0xFF1E293B),
        behavior: SnackBarBehavior.floating,
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
        margin: const EdgeInsets.all(16),
      ),
    );
  }

  void _showHelpSheet() {
    showModalBottomSheet(
      context: context,
      backgroundColor: Colors.transparent,
      builder: (ctx) => Container(
        decoration: const BoxDecoration(
          color: Colors.white,
          borderRadius: BorderRadius.vertical(top: Radius.circular(28)),
        ),
        padding: const EdgeInsets.fromLTRB(24, 16, 24, 28),
        child: SafeArea(
          child: Column(
            mainAxisSize: MainAxisSize.min,
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Center(
                child: Container(
                  width: 38,
                  height: 4,
                  decoration: BoxDecoration(
                    color: const Color(0xFFE2E8F0),
                    borderRadius: BorderRadius.circular(2),
                  ),
                ),
              ),
              const SizedBox(height: 20),
              const Text(
                'Bantuan Akun Teknisi',
                style: TextStyle(
                  fontFamily: 'Poppins',
                  fontSize: 18,
                  fontWeight: FontWeight.w700,
                  color: _textPrimary,
                ),
              ),
              const SizedBox(height: 6),
              const Text(
                'Akun teknisi didaftarkan dan dikelola langsung oleh Admin Operasional PT Loewix.',
                style: TextStyle(
                  fontFamily: 'Poppins',
                  fontSize: 13,
                  color: _textMuted,
                  height: 1.4,
                ),
              ),
              const SizedBox(height: 18),
              // WhatsApp Interactive Card
              InkWell(
                onTap: () {
                  Navigator.pop(ctx);
                  _openWhatsAppAdmin();
                },
                borderRadius: BorderRadius.circular(18),
                child: Container(
                  padding: const EdgeInsets.all(16),
                  decoration: BoxDecoration(
                    color: const Color(0xFFF0FDF4),
                    borderRadius: BorderRadius.circular(18),
                    border: Border.all(color: const Color(0xFFBBF7D0)),
                  ),
                  child: Row(
                    children: [
                      Container(
                        padding: const EdgeInsets.all(10),
                        decoration: const BoxDecoration(
                          color: Color(0xFF22C55E),
                          shape: BoxShape.circle,
                        ),
                        child: const Icon(
                          Icons.chat_bubble_rounded,
                          color: Colors.white,
                          size: 20,
                        ),
                      ),
                      const SizedBox(width: 14),
                      const Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(
                              'WhatsApp Admin PIC',
                              style: TextStyle(
                                fontFamily: 'Poppins',
                                fontSize: 13,
                                fontWeight: FontWeight.w700,
                                color: Color(0xFF14532D),
                              ),
                            ),
                            SizedBox(height: 2),
                            Text(
                              '0857-7159-3522',
                              style: TextStyle(
                                fontFamily: 'Poppins',
                                fontSize: 15,
                                fontWeight: FontWeight.w800,
                                letterSpacing: 0.5,
                                color: Color(0xFF15803D),
                              ),
                            ),
                          ],
                        ),
                      ),
                      const Icon(
                        Icons.arrow_forward_ios_rounded,
                        size: 14,
                        color: Color(0xFF15803D),
                      ),
                    ],
                  ),
                ),
              ),
              const SizedBox(height: 16),
              // Primary WhatsApp Button
              SizedBox(
                width: double.infinity,
                height: 52,
                child: ElevatedButton.icon(
                  onPressed: () {
                    Navigator.pop(ctx);
                    _openWhatsAppAdmin();
                  },
                  icon: const Icon(Icons.chat_rounded,
                      color: Colors.white, size: 20),
                  label: const Text(
                    'Chat WhatsApp Sekarang',
                    style: TextStyle(
                      fontFamily: 'Poppins',
                      fontSize: 15,
                      fontWeight: FontWeight.w700,
                      color: Colors.white,
                    ),
                  ),
                  style: ElevatedButton.styleFrom(
                    backgroundColor: const Color(0xFF22C55E),
                    elevation: 0,
                    shape: RoundedRectangleBorder(
                      borderRadius: BorderRadius.circular(28),
                    ),
                  ),
                ),
              ),
              const SizedBox(height: 8),
              Center(
                child: TextButton(
                  onPressed: () => Navigator.pop(ctx),
                  child: const Text(
                    'Tutup',
                    style: TextStyle(
                      fontFamily: 'Poppins',
                      fontSize: 13,
                      fontWeight: FontWeight.w600,
                      color: _textMuted,
                    ),
                  ),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }

  void _showForgotPasswordSheet() {
    _showHelpSheet();
  }
}
