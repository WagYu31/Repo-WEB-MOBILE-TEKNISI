import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:provider/provider.dart';
import 'package:intl/date_symbol_data_local.dart';
import 'service/provider/SalesProvider.dart';
import 'page/login/LoginPage.dart';
import 'page/home/HomePage.dart';

void main() async {
  WidgetsFlutterBinding.ensureInitialized();
  await initializeDateFormatting('id', null);
  SystemChrome.setPreferredOrientations([DeviceOrientation.portraitUp]);
  SystemChrome.setSystemUIOverlayStyle(const SystemUiOverlayStyle(
    statusBarColor: Colors.transparent,
    statusBarIconBrightness: Brightness.light,
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
      theme: ThemeData(
        colorScheme: ColorScheme.fromSeed(
          seedColor: const Color(0xFF3B82F6),
          brightness: Brightness.dark,
        ),
        useMaterial3: true,
      ),
      initialRoute: LoginPage.routeName,
      routes: {
        LoginPage.routeName: (_) => const _AuthGate(),
        HomePage.routeName: (_) => const HomePage(),
      },
    );
  }
}

// Cek jika sudah login, langsung ke Home
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
    if (ok) {
      Navigator.pushReplacementNamed(context, HomePage.routeName);
    }
  }

  @override
  Widget build(BuildContext context) {
    return const LoginPage();
  }
}
