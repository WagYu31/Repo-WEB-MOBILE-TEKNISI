import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:provider/provider.dart';
import 'package:intl/date_symbol_data_local.dart';
import 'core/app_theme.dart';
import 'service/provider/SalesProvider.dart';
import 'page/login/LoginPage.dart';
import 'page/home/HomePage.dart';

void main() async {
  WidgetsFlutterBinding.ensureInitialized();
  await initializeDateFormatting('id', null);
  SystemChrome.setPreferredOrientations([DeviceOrientation.portraitUp]);
  SystemChrome.setSystemUIOverlayStyle(const SystemUiOverlayStyle(
    statusBarColor: Colors.transparent,
    statusBarIconBrightness: Brightness.dark,
    systemNavigationBarColor: AppColors.surface,
    systemNavigationBarIconBrightness: Brightness.dark,
  ));
  runApp(
    ChangeNotifierProvider(
      create: (_) => SalesProvider(),
      child: const SalesApp(),
    ),
  );
}

class SalesApp extends StatelessWidget {
  const SalesApp({super.key});

  @override
  Widget build(BuildContext context) {
    return MaterialApp(
      title: 'Loewix Sales',
      debugShowCheckedModeBanner: false,
      theme: AppTheme.light,
      initialRoute: LoginPage.routeName,
      routes: {
        LoginPage.routeName: (_) => const _AuthGate(),
        HomePage.routeName:  (_) => const HomePage(),
      },
    );
  }
}

// Cek session tersimpan → langsung ke Home
class _AuthGate extends StatefulWidget {
  const _AuthGate();
  @override
  State<_AuthGate> createState() => _AuthGateState();
}

class _AuthGateState extends State<_AuthGate> {
  @override
  void initState() {
    super.initState();
    _check();
  }

  Future<void> _check() async {
    final prov = context.read<SalesProvider>();
    final ok = await prov.loadFromPrefs();
    if (!mounted) return;
    if (ok) Navigator.pushReplacementNamed(context, HomePage.routeName);
  }

  @override
  Widget build(BuildContext context) => const LoginPage();
}
