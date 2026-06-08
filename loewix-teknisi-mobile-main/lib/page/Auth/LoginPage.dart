import 'dart:convert';
import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:provider/provider.dart';
import 'package:shared_preferences/shared_preferences.dart';

import '../../service/model/auth/LoginResponse.dart';
import '../../service/provider/Auth/AuthProvider.dart';
import '../../service/provider/Auth/TeknisiLoginProvider.dart';
import '../../service/provider/preferences/PreferencesIDProvider.dart';
import '../../service/provider/preferences/PreferencesTokenProvider.dart';
import '../container/HomePage.dart';

class LoginPage extends StatefulWidget {
  static const routeName = 'login_page';

  const LoginPage({super.key});

  @override
  State<LoginPage> createState() => _LoginPageState();
}

class _LoginPageState extends State<LoginPage> with SingleTickerProviderStateMixin {
  // ─── Premium Color Palette ─────────────────────
  static const Color _navy = Color(0xFF0F172A);
  static const Color _navyLight = Color(0xFF1E293B);
  static const Color _skyBlue = Color(0xFF0EA5E9);
  static const Color _skyDark = Color(0xFF0284C7);
  static const Color _rose = Color(0xFFF43F5E);
  static const Color _textPrimary = Color(0xFF0F172A);
  static const Color _textSecondary = Color(0xFF64748B);
  static const Color _inputBg = Color(0xFFF8FAFC);
  static const Color _inputBorder = Color(0xFFE2E8F0);

  final _formKey = GlobalKey<FormState>();
  final _usernameController = TextEditingController();
  final _passwordController = TextEditingController();

  bool _isLoading = false;
  bool _obscurePassword = true;
  String? _errorMessage;
  bool _rememberMe = true; // Default checked

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
      duration: const Duration(milliseconds: 900),
    );

    _fadeAnimation = Tween<double>(begin: 0.0, end: 1.0).animate(
      CurvedAnimation(parent: _animationController, curve: Curves.easeOutCubic),
    );

    _slideAnimation = Tween<Offset>(
      begin: const Offset(0, 0.2),
      end: Offset.zero,
    ).animate(CurvedAnimation(parent: _animationController, curve: Curves.easeOutCubic));

    _animationController.forward();
    _loadSavedAccounts();
  }

  Future<void> _loadSavedAccounts() async {
    final prefs = await SharedPreferences.getInstance();
    final jsonStr = prefs.getString(_prefSavedAccounts);
    if (jsonStr != null && jsonStr.isNotEmpty) {
      try {
        final List<dynamic> decoded = json.decode(jsonStr);
        _savedAccounts = decoded.map((e) => Map<String, String>.from(e)).toList();
        // Auto-fill with the last used account
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

  Future<void> _saveAccount(String username, String password, String nama) async {
    // Remove existing entry for this username (update)
    _savedAccounts.removeWhere((a) => a['username'] == username);
    // Add at top (most recent first)
    _savedAccounts.insert(0, {
      'username': username,
      'password': password,
      'nama': nama,
    });
    // Keep max 10 accounts
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
      final loginProvider = Provider.of<TeknisiLoginProvider>(context, listen: false);
      final result = await loginProvider.doLogin(
        _usernameController.text.trim(),
        _passwordController.text,
      );

      if (!mounted) return;

      if (result != null) {
        await _handleSuccessLogin(result);
      } else {
        _showError('Username atau password salah.');
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
      final tokenProvider = Provider.of<PreferencesTokenProvider>(context, listen: false);
      final idProvider = Provider.of<PreferencesIDProvider>(context, listen: false);

      await authProvider.login();
      tokenProvider.setUserToken(response.token);
      await idProvider.setUserRole(response.user.teknisiId.toString());
      // Force refresh the in-memory value
      await idProvider.getUserRolePreferences();

      // Auto-save credentials if "Ingat Saya" is checked
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

    return 'Terjadi kesalahan. Silakan coba lagi.';
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
            const Icon(Icons.check_circle, color: Colors.white),
            const SizedBox(width: 12),
            Expanded(child: Text(message, style: const TextStyle(fontFamily: 'Poppins'))),
          ],
        ),
        backgroundColor: const Color(0xFF14B8A6),
        behavior: SnackBarBehavior.floating,
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
        margin: const EdgeInsets.all(16),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: Container(
        decoration: const BoxDecoration(
          gradient: LinearGradient(
            begin: Alignment.topLeft,
            end: Alignment.bottomRight,
            colors: [_navy, _navyLight, Color(0xFF334155)],
            stops: [0.0, 0.6, 1.0],
          ),
        ),
        child: SafeArea(
          child: Center(
            child: SingleChildScrollView(
              padding: const EdgeInsets.symmetric(horizontal: 28.0),
              child: FadeTransition(
                opacity: _fadeAnimation,
                child: SlideTransition(
                  position: _slideAnimation,
                  child: Column(
                    mainAxisAlignment: MainAxisAlignment.center,
                    children: [
                      const SizedBox(height: 20),
                      _buildLogo(),
                      const SizedBox(height: 40),
                      _buildLoginCard(),
                      const SizedBox(height: 24),
                      _buildFooter(),
                      const SizedBox(height: 20),
                    ],
                  ),
                ),
              ),
            ),
          ),
        ),
      ),
    );
  }

  Widget _buildLogo() {
    return Column(
      children: [
        // Glassmorphism logo container
        Container(
          padding: const EdgeInsets.all(22),
          decoration: BoxDecoration(
            color: Colors.white.withValues(alpha: 0.1),
            borderRadius: BorderRadius.circular(24),
            border: Border.all(
              color: Colors.white.withValues(alpha: 0.15),
              width: 1.5,
            ),
            boxShadow: [
              BoxShadow(
                color: _skyBlue.withValues(alpha: 0.15),
                blurRadius: 30,
                offset: const Offset(0, 10),
              ),
            ],
          ),
          child: Image.asset(
            'assets/imgLogo.png',
            height: 56,
            errorBuilder: (context, error, stackTrace) {
              return const Icon(
                Icons.engineering,
                size: 56,
                color: Colors.white,
              );
            },
          ),
        ),
        const SizedBox(height: 28),
        const Text(
          'Selamat Datang',
          style: TextStyle(
            fontFamily: 'Poppins',
            fontSize: 28,
            fontWeight: FontWeight.w700,
            color: Colors.white,
            letterSpacing: -0.5,
          ),
        ),
        const SizedBox(height: 8),
        Text(
          'Masuk ke akun teknisi Anda',
          style: TextStyle(
            fontFamily: 'Poppins',
            fontSize: 14,
            color: Colors.white.withValues(alpha: 0.6),
            letterSpacing: 0.2,
          ),
        ),
      ],
    );
  }

  Widget _buildLoginCard() {
    return Container(
      padding: const EdgeInsets.all(28),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(24),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.15),
            blurRadius: 40,
            offset: const Offset(0, 16),
          ),
          BoxShadow(
            color: _skyBlue.withValues(alpha: 0.08),
            blurRadius: 60,
            offset: const Offset(0, 20),
          ),
        ],
      ),
      child: Form(
        key: _formKey,
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            // Card header
            Row(
              children: [
                Container(
                  padding: const EdgeInsets.all(10),
                  decoration: BoxDecoration(
                    color: _skyBlue.withValues(alpha: 0.1),
                    borderRadius: BorderRadius.circular(12),
                  ),
                  child: const Icon(Icons.login_rounded, size: 20, color: _skyBlue),
                ),
                const SizedBox(width: 12),
                const Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      'Login Teknisi',
                      style: TextStyle(
                        fontFamily: 'Poppins',
                        fontSize: 16,
                        fontWeight: FontWeight.w600,
                        color: _textPrimary,
                      ),
                    ),
                    Text(
                      'Silakan masukkan kredensial Anda',
                      style: TextStyle(
                        fontFamily: 'Poppins',
                        fontSize: 11,
                        color: _textSecondary,
                      ),
                    ),
                  ],
                ),
              ],
            ),
            const SizedBox(height: 24),
            if (_errorMessage != null) _buildErrorBanner(),
            _buildUsernameField(),
            const SizedBox(height: 18),
            _buildPasswordField(),
            const SizedBox(height: 14),
            _buildRememberMeCheckbox(),
            const SizedBox(height: 20),
            _buildLoginButton(),
          ],
        ),
      ),
    );
  }

  Widget _buildErrorBanner() {
    return Container(
      margin: const EdgeInsets.only(bottom: 18),
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: _rose.withValues(alpha: 0.08),
        borderRadius: BorderRadius.circular(14),
        border: Border.all(color: _rose.withValues(alpha: 0.2)),
      ),
      child: Row(
        children: [
          Container(
            padding: const EdgeInsets.all(6),
            decoration: BoxDecoration(
              color: _rose.withValues(alpha: 0.1),
              borderRadius: BorderRadius.circular(8),
            ),
            child: const Icon(Icons.error_outline, color: _rose, size: 18),
          ),
          const SizedBox(width: 12),
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
          InkWell(
            onTap: _clearError,
            borderRadius: BorderRadius.circular(8),
            child: Padding(
              padding: const EdgeInsets.all(4),
              child: Icon(Icons.close, color: _rose.withValues(alpha: 0.6), size: 18),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildUsernameField() {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        const Text(
          'Username',
          style: TextStyle(
            fontFamily: 'Poppins',
            fontSize: 13,
            fontWeight: FontWeight.w600,
            color: _textPrimary,
          ),
        ),
        const SizedBox(height: 8),
        TextFormField(
          controller: _usernameController,
          keyboardType: TextInputType.text,
          textInputAction: TextInputAction.next,
          enabled: !_isLoading,
          onChanged: (_) => _clearError(),
          onTap: () {
            if (_savedAccounts.isNotEmpty && _usernameController.text.isEmpty) {
              _showSavedAccountsPicker();
            }
          },
          decoration: _inputDecoration(
            hint: 'Masukkan username',
            prefixIcon: Icons.person_outline_rounded,
            suffixIcon: _savedAccounts.isNotEmpty
                ? IconButton(
                    icon: const Icon(Icons.arrow_drop_down_rounded, color: _textSecondary, size: 28),
                    onPressed: _isLoading ? null : _showSavedAccountsPicker,
                  )
                : null,
          ),
          style: const TextStyle(fontFamily: 'Poppins', fontSize: 14, color: _textPrimary),
          validator: (value) {
            if (value == null || value.trim().isEmpty) {
              return 'Username tidak boleh kosong';
            }
            if (value.trim().length < 3) {
              return 'Username minimal 3 karakter';
            }
            return null;
          },
        ),
      ],
    );
  }

  void _showSavedAccountsPicker() {
    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (ctx) => Container(
        decoration: const BoxDecoration(
          color: Colors.white,
          borderRadius: BorderRadius.vertical(top: Radius.circular(24)),
        ),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            // Handle bar
            Container(
              margin: const EdgeInsets.only(top: 12),
              width: 40,
              height: 4,
              decoration: BoxDecoration(
                color: const Color(0xFFE2E8F0),
                borderRadius: BorderRadius.circular(2),
              ),
            ),
            // Header
            Padding(
              padding: const EdgeInsets.fromLTRB(24, 20, 24, 8),
              child: Row(
                children: [
                  Container(
                    padding: const EdgeInsets.all(10),
                    decoration: BoxDecoration(
                      gradient: const LinearGradient(colors: [_skyBlue, _skyDark]),
                      borderRadius: BorderRadius.circular(12),
                    ),
                    child: const Icon(Icons.key_rounded, color: Colors.white, size: 20),
                  ),
                  const SizedBox(width: 14),
                  const Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        'Akun Tersimpan',
                        style: TextStyle(
                          fontFamily: 'Poppins',
                          fontSize: 16,
                          fontWeight: FontWeight.w700,
                          color: _textPrimary,
                        ),
                      ),
                      Text(
                        'Pilih akun untuk login otomatis',
                        style: TextStyle(
                          fontFamily: 'Poppins',
                          fontSize: 12,
                          color: _textSecondary,
                        ),
                      ),
                    ],
                  ),
                ],
              ),
            ),
            const Divider(height: 1),
            // Account list
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
                    color: _rose.withValues(alpha: 0.1),
                    child: const Icon(Icons.delete_outline, color: _rose),
                  ),
                  onDismissed: (_) async {
                    await _removeAccount(username);
                    if (mounted) setState(() {});
                  },
                  child: ListTile(
                    contentPadding: const EdgeInsets.symmetric(horizontal: 24, vertical: 4),
                    leading: Container(
                      width: 48,
                      height: 48,
                      decoration: BoxDecoration(
                        color: isSelected ? _skyBlue : const Color(0xFFF1F5F9),
                        borderRadius: BorderRadius.circular(14),
                      ),
                      child: Center(
                        child: Text(
                          nama.isNotEmpty ? nama[0].toUpperCase() : '?',
                          style: TextStyle(
                            fontFamily: 'Poppins',
                            fontSize: 20,
                            fontWeight: FontWeight.w700,
                            color: isSelected ? Colors.white : _skyBlue,
                          ),
                        ),
                      ),
                    ),
                    title: Text(
                      nama,
                      style: TextStyle(
                        fontFamily: 'Poppins',
                        fontSize: 14,
                        fontWeight: isSelected ? FontWeight.w700 : FontWeight.w600,
                        color: _textPrimary,
                      ),
                    ),
                    subtitle: Row(
                      children: [
                        Text(
                          username,
                          style: const TextStyle(
                            fontFamily: 'Poppins',
                            fontSize: 12,
                            color: _textSecondary,
                          ),
                        ),
                        const SizedBox(width: 8),
                        Text(
                          '•  ${'•' * (password.length > 8 ? 8 : password.length)}',
                          style: const TextStyle(
                            fontSize: 12,
                            color: _textSecondary,
                          ),
                        ),
                      ],
                    ),
                    trailing: isSelected
                        ? const Icon(Icons.check_circle, color: _skyBlue, size: 22)
                        : const Icon(Icons.arrow_forward_ios, size: 14, color: _textSecondary),
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
            // Manual login option
            Padding(
              padding: const EdgeInsets.fromLTRB(24, 0, 24, 20),
              child: TextButton.icon(
                onPressed: () {
                  setState(() {
                    _usernameController.clear();
                    _passwordController.clear();
                  });
                  Navigator.pop(ctx);
                },
                icon: const Icon(Icons.add_circle_outline, size: 20),
                label: const Text(
                  'Login dengan akun lain',
                  style: TextStyle(
                    fontFamily: 'Poppins',
                    fontSize: 13,
                    fontWeight: FontWeight.w600,
                  ),
                ),
                style: TextButton.styleFrom(
                  foregroundColor: _skyBlue,
                  padding: const EdgeInsets.symmetric(vertical: 12),
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildPasswordField() {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        const Text(
          'Password',
          style: TextStyle(
            fontFamily: 'Poppins',
            fontSize: 13,
            fontWeight: FontWeight.w600,
            color: _textPrimary,
          ),
        ),
        const SizedBox(height: 8),
        TextFormField(
          controller: _passwordController,
          obscureText: _obscurePassword,
          textInputAction: TextInputAction.done,
          enabled: !_isLoading,
          onChanged: (_) => _clearError(),
          onFieldSubmitted: (_) => _login(),
          decoration: _inputDecoration(
            hint: 'Masukkan password',
            prefixIcon: Icons.lock_outline_rounded,
            suffixIcon: IconButton(
              icon: Icon(
                _obscurePassword ? Icons.visibility_off_rounded : Icons.visibility_rounded,
                color: _textSecondary,
                size: 20,
              ),
              onPressed: _isLoading ? null : () {
                setState(() => _obscurePassword = !_obscurePassword);
              },
            ),
          ),
          style: const TextStyle(fontFamily: 'Poppins', fontSize: 14, color: _textPrimary),
          validator: (value) {
            if (value == null || value.isEmpty) {
              return 'Password tidak boleh kosong';
            }
            if (value.length < 4) {
              return 'Password minimal 4 karakter';
            }
            return null;
          },
        ),
      ],
    );
  }

  InputDecoration _inputDecoration({
    required String hint,
    required IconData prefixIcon,
    Widget? suffixIcon,
  }) {
    return InputDecoration(
      hintText: hint,
      hintStyle: const TextStyle(
        fontFamily: 'Poppins',
        color: Color(0xFF94A3B8),
        fontSize: 14,
      ),
      prefixIcon: Padding(
        padding: const EdgeInsets.only(left: 14, right: 10),
        child: Icon(prefixIcon, color: _textSecondary, size: 20),
      ),
      prefixIconConstraints: const BoxConstraints(minWidth: 44, minHeight: 44),
      suffixIcon: suffixIcon,
      filled: true,
      fillColor: _inputBg,
      border: OutlineInputBorder(
        borderRadius: BorderRadius.circular(14),
        borderSide: const BorderSide(color: _inputBorder),
      ),
      enabledBorder: OutlineInputBorder(
        borderRadius: BorderRadius.circular(14),
        borderSide: const BorderSide(color: _inputBorder),
      ),
      focusedBorder: OutlineInputBorder(
        borderRadius: BorderRadius.circular(14),
        borderSide: const BorderSide(color: _skyBlue, width: 2),
      ),
      errorBorder: OutlineInputBorder(
        borderRadius: BorderRadius.circular(14),
        borderSide: const BorderSide(color: _rose),
      ),
      focusedErrorBorder: OutlineInputBorder(
        borderRadius: BorderRadius.circular(14),
        borderSide: const BorderSide(color: _rose, width: 2),
      ),
      errorStyle: const TextStyle(fontFamily: 'Poppins', fontSize: 12, color: _rose),
      contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 16),
    );
  }

  Widget _buildRememberMeCheckbox() {
    return GestureDetector(
      onTap: _isLoading ? null : () {
        setState(() => _rememberMe = !_rememberMe);
      },
      child: Row(
        children: [
          SizedBox(
            width: 22,
            height: 22,
            child: Checkbox(
              value: _rememberMe,
              onChanged: _isLoading ? null : (value) {
                setState(() => _rememberMe = value ?? false);
              },
              activeColor: _skyBlue,
              shape: RoundedRectangleBorder(
                borderRadius: BorderRadius.circular(5),
              ),
              side: const BorderSide(color: _inputBorder, width: 1.5),
              materialTapTargetSize: MaterialTapTargetSize.shrinkWrap,
            ),
          ),
          const SizedBox(width: 10),
          const Text(
            'Ingat Saya',
            style: TextStyle(
              fontFamily: 'Poppins',
              fontSize: 13,
              fontWeight: FontWeight.w500,
              color: _textSecondary,
            ),
          ),
          const Spacer(),
          if (_rememberMe)
            Container(
              padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
              decoration: BoxDecoration(
                color: _skyBlue.withValues(alpha: 0.1),
                borderRadius: BorderRadius.circular(8),
              ),
              child: Row(
                mainAxisSize: MainAxisSize.min,
                children: [
                  Icon(Icons.check_circle, size: 12, color: _skyBlue),
                  const SizedBox(width: 4),
                  Text(
                    'Tersimpan',
                    style: TextStyle(
                      fontFamily: 'Poppins',
                      fontSize: 10,
                      fontWeight: FontWeight.w600,
                      color: _skyBlue,
                    ),
                  ),
                ],
              ),
            ),
        ],
      ),
    );
  }

  Widget _buildLoginButton() {
    return Container(
      height: 54,
      decoration: BoxDecoration(
        borderRadius: BorderRadius.circular(14),
        gradient: const LinearGradient(
          colors: [_skyBlue, _skyDark],
        ),
        boxShadow: [
          BoxShadow(
            color: _skyBlue.withValues(alpha: 0.35),
            blurRadius: 16,
            offset: const Offset(0, 6),
          ),
        ],
      ),
      child: ElevatedButton(
        onPressed: _isLoading ? null : _login,
        style: ElevatedButton.styleFrom(
          backgroundColor: Colors.transparent,
          shadowColor: Colors.transparent,
          foregroundColor: Colors.white,
          disabledBackgroundColor: Colors.transparent,
          shape: RoundedRectangleBorder(
            borderRadius: BorderRadius.circular(14),
          ),
        ),
        child: _isLoading
            ? const SizedBox(
                width: 24,
                height: 24,
                child: CircularProgressIndicator(
                  color: Colors.white,
                  strokeWidth: 2.5,
                ),
              )
            : const Row(
                mainAxisAlignment: MainAxisAlignment.center,
                children: [
                  Icon(Icons.login_rounded, size: 20),
                  SizedBox(width: 10),
                  Text(
                    'Masuk',
                    style: TextStyle(
                      fontFamily: 'Poppins',
                      fontSize: 16,
                      fontWeight: FontWeight.w600,
                      letterSpacing: 0.5,
                    ),
                  ),
                ],
              ),
      ),
    );
  }

  Widget _buildFooter() {
    return Column(
      children: [
        Text(
          'LOEWIX',
          style: TextStyle(
            fontFamily: 'Poppins',
            fontSize: 12,
            fontWeight: FontWeight.w600,
            color: Colors.white.withValues(alpha: 0.3),
            letterSpacing: 3,
          ),
        ),
        const SizedBox(height: 4),
        Text(
          'v3.7.8 • Teknisi App',
          style: TextStyle(
            fontFamily: 'Poppins',
            fontSize: 11,
            color: Colors.white.withValues(alpha: 0.2),
          ),
        ),
      ],
    );
  }
}
