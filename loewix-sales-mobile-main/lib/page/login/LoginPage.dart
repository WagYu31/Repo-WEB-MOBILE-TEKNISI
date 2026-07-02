import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:flutter_animate/flutter_animate.dart';
import '../../core/app_theme.dart';
import '../../service/provider/SalesProvider.dart';
import '../home/HomePage.dart';

class LoginPage extends StatefulWidget {
  static const routeName = '/login';
  const LoginPage({super.key});
  @override
  State<LoginPage> createState() => _LoginPageState();
}

class _LoginPageState extends State<LoginPage> {
  final _nikCtrl  = TextEditingController();
  final _passCtrl = TextEditingController();
  final _passNode = FocusNode();
  bool _obscure        = true;
  List<SavedAccount> _savedAccounts = [];

  @override
  void initState() {
    super.initState();
    _loadSaved();
  }

  Future<void> _loadSaved() async {
    final list = await context.read<SalesProvider>().getSavedAccounts();
    if (mounted) setState(() => _savedAccounts = list);
  }

  @override
  void dispose() {
    _nikCtrl.dispose();
    _passCtrl.dispose();
    _passNode.dispose();
    super.dispose();
  }

  Future<void> _doLogin() async {
    final nik  = _nikCtrl.text.trim();
    final pass = _passCtrl.text.trim();
    if (nik.isEmpty || pass.isEmpty) {
      _snack('NIK dan password wajib diisi', error: true);
      return;
    }
    final prov = context.read<SalesProvider>();
    final ok   = await prov.login(nik, pass);
    if (!mounted) return;
    if (ok) {
      Navigator.pushReplacementNamed(context, HomePage.routeName);
    } else {
      _snack(prov.error, error: true);
    }
  }

  void _snack(String msg, {bool error = false}) {
    ScaffoldMessenger.of(context).showSnackBar(SnackBar(
      content: Row(children: [
        Icon(error ? Icons.error_outline : Icons.check_circle_outline,
            color: error ? AppColors.error : AppColors.success, size: 18),
        const SizedBox(width: 10),
        Expanded(child: Text(msg, style: S.bodySm(AppColors.textPrimary))),
      ]),
      backgroundColor: AppColors.cardAlt,
    ));
  }

  // ── Tampilkan daftar akun tersimpan di bottom sheet ──
  void _showSavedAccounts() {
    if (_savedAccounts.isEmpty) return;
    showModalBottomSheet(
      context: context,
      backgroundColor: AppColors.cardAlt,
      shape: const RoundedRectangleBorder(
          borderRadius: BorderRadius.vertical(top: Radius.circular(20))),
      builder: (_) {
        return SafeArea(
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              const SizedBox(height: 8),
              Container(
                width: 40, height: 4,
                decoration: BoxDecoration(
                    color: AppColors.border,
                    borderRadius: BorderRadius.circular(2)),
              ),
              const SizedBox(height: 16),
              Padding(
                padding: const EdgeInsets.symmetric(horizontal: 20),
                child: Row(
                  children: [
                    const Icon(Icons.people_outline_rounded,
                        size: 18, color: AppColors.primary),
                    const SizedBox(width: 8),
                    Text('Pilih Akun', style: S.h3()),
                  ],
                ),
              ),
              const SizedBox(height: 12),
              ..._savedAccounts.map((acc) {
                final initial = acc.nama.isNotEmpty
                    ? acc.nama[0].toUpperCase()
                    : acc.nik[0];
                final namaDisplay = acc.nama.isNotEmpty
                    ? acc.nama
                    : acc.nik;
                return ListTile(
                  onTap: () {
                    Navigator.pop(context);
                    setState(() => _nikCtrl.text = acc.nik);
                    FocusScope.of(context).requestFocus(_passNode);
                  },
                  contentPadding: const EdgeInsets.symmetric(
                      horizontal: 20, vertical: 4),
                  leading: Container(
                    width: 42, height: 42,
                    decoration: BoxDecoration(
                      color: AppColors.primary.withOpacity(0.15),
                      shape: BoxShape.circle,
                      border: Border.all(
                          color: AppColors.primary.withOpacity(0.35)),
                    ),
                    child: Center(
                      child: Text(initial,
                          style: S.h3(AppColors.primary)),
                    ),
                  ),
                  title: Text(namaDisplay, style: S.bodyLg()),
                  subtitle: Text('Username: ${acc.nik}', style: S.caption()),
                  trailing: const Icon(Icons.chevron_right_rounded,
                      color: AppColors.textMuted, size: 20),
                );
              }),
              const SizedBox(height: 12),
            ],
          ),
        );
      },
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: AppColors.bg,
      body: Stack(
        children: [
          // Dekorasi orb
          Positioned(top: -80, left: -60,
              child: _Orb(size: 260, color: AppColors.primary.withOpacity(0.18))),
          Positioned(bottom: 40, right: -80,
              child: _Orb(size: 300, color: AppColors.primary.withOpacity(0.10))),

          SafeArea(
            child: Center(
              child: SingleChildScrollView(
                padding: const EdgeInsets.symmetric(horizontal: 24),
                child: Column(
                  mainAxisAlignment: MainAxisAlignment.center,
                  children: [
                    const SizedBox(height: 24),

                    // Logo Loewix
                    _LogoBadge()
                        .animate()
                        .fadeIn(duration: 600.ms)
                        .scale(begin: const Offset(0.7, 0.7),
                            curve: Curves.easeOutBack),

                    const SizedBox(height: 18),

                    Text('Loewix Sales', style: S.display())
                        .animate(delay: 200.ms)
                        .fadeIn(duration: 500.ms)
                        .slideY(begin: 0.2, end: 0),

                    const SizedBox(height: 6),
                    Text('Portal Kunjungan Sales PT. Loewix Indonesia',
                        style: S.caption(AppColors.textMuted),
                        textAlign: TextAlign.center)
                        .animate(delay: 300.ms).fadeIn(duration: 500.ms),

                    const SizedBox(height: 36),

                    // Login Card
                    _LoginCard(
                      nikCtrl: _nikCtrl,
                      passCtrl: _passCtrl,
                      passNode: _passNode,
                      obscure: _obscure,
                      hasSavedAccounts: _savedAccounts.isNotEmpty,
                      onToggleObscure: () =>
                          setState(() => _obscure = !_obscure),
                      onLogin: _doLogin,
                      onTapSaved: _showSavedAccounts,
                    )
                        .animate(delay: 400.ms)
                        .fadeIn(duration: 600.ms)
                        .slideY(begin: 0.15, end: 0,
                            curve: Curves.easeOut),

                    const SizedBox(height: 32),

                    Text('© 2026 PT. Loewix Indonesia',
                        style: S.label(AppColors.textMuted))
                        .animate(delay: 600.ms).fadeIn(duration: 500.ms),

                    const SizedBox(height: 20),
                  ],
                ),
              ),
            ),
          ),
        ],
      ),
    );
  }
}

// ── Logo Badge ───────────────────────────────────────────
class _LogoBadge extends StatelessWidget {
  @override
  Widget build(BuildContext context) {
    return Container(
      width: 100, height: 100,
      decoration: BoxDecoration(
        color: Colors.white,
        shape: BoxShape.circle,
        boxShadow: [
          BoxShadow(color: AppColors.primary.withOpacity(0.45),
              blurRadius: 36, spreadRadius: 6),
          BoxShadow(color: AppColors.primary.withOpacity(0.15),
              blurRadius: 60, spreadRadius: 10),
        ],
      ),
      child: ClipOval(
        child: Padding(
          padding: const EdgeInsets.all(14),
          child: Image.asset('assets/images/logo_loewix.png',
              fit: BoxFit.contain),
        ),
      ),
    );
  }
}

// ── Decorative Orb ───────────────────────────────────────
class _Orb extends StatelessWidget {
  final double size;
  final Color color;
  const _Orb({required this.size, required this.color});
  @override
  Widget build(BuildContext context) {
    return Container(
      width: size, height: size,
      decoration: BoxDecoration(
        shape: BoxShape.circle,
        gradient: RadialGradient(colors: [color, Colors.transparent]),
      ),
    );
  }
}

// ── Login Card ───────────────────────────────────────────
class _LoginCard extends StatelessWidget {
  final TextEditingController nikCtrl;
  final TextEditingController passCtrl;
  final FocusNode passNode;
  final bool obscure;
  final bool hasSavedAccounts;
  final VoidCallback onToggleObscure;
  final VoidCallback onLogin;
  final VoidCallback onTapSaved;

  const _LoginCard({
    required this.nikCtrl,
    required this.passCtrl,
    required this.passNode,
    required this.obscure,
    required this.hasSavedAccounts,
    required this.onToggleObscure,
    required this.onLogin,
    required this.onTapSaved,
  });

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(24),
      decoration: AppTheme.glassDeco(radius: 24),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text('Masuk ke Akun Anda', style: S.h2()),
          const SizedBox(height: 6),
          Text('Gunakan Username dan password yang diberikan',
              style: S.caption()),
          const SizedBox(height: 24),

          // NIK field — suffix ikon orang jika ada akun tersimpan
          Text('Username', style: S.micro(AppColors.textSecondary)),
          const SizedBox(height: 6),
          TextField(
            controller: nikCtrl,
            keyboardType: TextInputType.text,
            style: S.body(AppColors.textPrimary),
            textInputAction: TextInputAction.next,
            onSubmitted: (_) =>
                FocusScope.of(context).requestFocus(passNode),
            decoration: InputDecoration(
              hintText: 'Masukkan Username',
              prefixIcon: const Icon(Icons.badge_outlined,
                  color: AppColors.primary, size: 20),
              // Ikon akun tersimpan di kanan (hanya jika ada)
              suffixIcon: hasSavedAccounts
                  ? Tooltip(
                      message: 'Pilih akun tersimpan',
                      child: IconButton(
                        onPressed: onTapSaved,
                        icon: const Icon(Icons.expand_circle_down_outlined,
                            color: AppColors.primary, size: 22),
                      ),
                    )
                  : null,
            ),
          ),
          const SizedBox(height: 16),

          // Password field
          Text('Password', style: S.micro(AppColors.textSecondary)),
          const SizedBox(height: 6),
          TextField(
            controller: passCtrl,
            focusNode: passNode,
            obscureText: obscure,
            style: S.body(AppColors.textPrimary),
            textInputAction: TextInputAction.done,
            onSubmitted: (_) => onLogin(),
            decoration: InputDecoration(
              hintText: 'Masukkan password',
              prefixIcon: const Icon(Icons.lock_outline_rounded,
                  color: AppColors.primary, size: 20),
              suffixIcon: IconButton(
                onPressed: onToggleObscure,
                icon: Icon(
                  obscure
                      ? Icons.visibility_off_outlined
                      : Icons.visibility_outlined,
                  color: AppColors.textMuted, size: 20,
                ),
              ),
            ),
          ),
          const SizedBox(height: 28),

          // Tombol masuk
          Consumer<SalesProvider>(
            builder: (_, prov, __) => GestureDetector(
              onTap: prov.isLoading ? null : onLogin,
              child: AnimatedContainer(
                duration: const Duration(milliseconds: 200),
                height: 52,
                decoration: prov.isLoading
                    ? BoxDecoration(
                        color: AppColors.primary.withOpacity(0.5),
                        borderRadius: BorderRadius.circular(14))
                    : AppTheme.btnDeco(),
                child: Center(
                  child: prov.isLoading
                      ? const SizedBox(
                          width: 22, height: 22,
                          child: CircularProgressIndicator(
                              color: Colors.white, strokeWidth: 2.5))
                      : Text('Masuk', style: S.btn()),
                ),
              ),
            ),
          ),
        ],
      ),
    );
  }
}
