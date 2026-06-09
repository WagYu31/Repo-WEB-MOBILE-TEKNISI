import 'dart:io';

import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:shared_preferences/shared_preferences.dart';
import 'package:teknisi_loewix/service/provider/Invoice/DetailInvoiceProvider.dart';
import 'package:teknisi_loewix/service/provider/Pelaksanaan/CoProvider.dart';
import 'package:teknisi_loewix/service/provider/reimburse/ReimburseDeleteProvider.dart';
import 'package:teknisi_loewix/service/provider/reimburse/ReimburseProvider.dart';
import 'package:teknisi_loewix/service/provider/reimburse/ReimburseSendProvider.dart';
import 'package:timezone/data/latest.dart' as tz;
import '../page/Auth/LoginPage.dart';
import '../page/Auth/RegisterPage.dart';
import '../page/Pinjam_Barang/CreatePinjamBarang.dart';
import '../page/Pinjam_Barang/DetailPinjamPage.dart';
import '../page/Pinjam_Barang/KembalikanPinjamanPage.dart';
import '../page/Splash/SplashPage.dart';
import '../page/Task/DetailTaskPage.dart';
import '../page/Task/LanjutNantiPage.dart';
import '../page/Task/ReportDonePage.dart';
import '../page/Task/ReschedulePage.dart';
import '../page/Task/TaskPage.dart';
import '../page/Task/sales/StartTaskPage.dart';
import '../page/container/HomePage.dart';
import '../page/tutor/api_tutor/ApiTutor.dart';
import '../page/tutor/page/TutorialPage.dart';
import '../page/tutor/provider/DetailTutorProvider.dart';
import '../page/tutor/provider/TutorialAllProvider.dart';
import '../service/api/ApiAuth.dart';
import '../service/api/ApiBarang.dart';
import '../service/api/ApiPelaksanaan.dart';
import '../service/api/ApiPinjam.dart';
import '../service/api/ApiTask.dart';
import '../service/api/ApiTeknisi.dart';
import '../service/db/auth_repository.dart';
import '../service/model/pinjam/DetailPinjamGetModel.dart';
import '../service/model/task/TaskAllResponse.dart';
import '../service/notification/NotificationService.dart';
import '../service/provider/Auth/AuthProvider.dart';
import '../service/provider/Auth/TeknisiLoginProvider.dart';
import '../service/provider/Auth/TeknisiRegisterProvider.dart';
import '../service/provider/Barang/BarangGetProvider.dart';
import '../service/provider/Maps/MapsProvider.dart';
import '../service/provider/Pelaksanaan/LanjutNantiProvider.dart';
import '../service/provider/Pelaksanaan/PelaksanaanSendProvider.dart';
import '../service/provider/Pelaksanaan/ReportPelaksanaanProvider.dart';
import '../service/provider/Pelaksanaan/RescheduleProvider.dart';
import '../service/provider/Pencapaian/ChartProvider.dart';
import '../service/provider/Pencapaian/PencapaianProvider.dart';
import '../service/provider/Pencapaian/PencapaianTeknisiProvider.dart';
import '../service/provider/Tutorial/TutorialProvider.dart';
import '../service/provider/Pinjam/DetailPinjamGetProvider.dart';
import '../service/provider/Pinjam/KembalikanPinjamProvider.dart';
import '../service/provider/Pinjam/PinjamDeleteProvider.dart';
import '../service/provider/Pinjam/PinjamGetProvider.dart';
import '../service/provider/Pinjam/PinjamSendProvider.dart';
import '../service/provider/Pinjam/StatusPinjamProvider.dart';
import '../service/provider/Profile/ProfileProvider.dart';
import '../service/provider/Task/DetailTaskGetProvider.dart';
import '../service/provider/Task/HistoryGetAllProvider.dart';
import '../service/provider/Task/TaskGetAllProvider.dart';
import '../service/provider/Teknisi/TeknisiGetAllProvider.dart';
import '../service/provider/preferences/PreferencesIDProvider.dart';
import '../service/provider/preferences/PreferencesTokenProvider.dart';

import 'package:intl/intl.dart';
import 'package:intl/date_symbol_data_local.dart';

void main() async {
  WidgetsFlutterBinding.ensureInitialized();
  await initializeDateFormatting('id_ID', null);
  
  // Initialize notification system
  await NotificationService().init();

  tz.initializeTimeZones();
  HttpOverrides.global = MyHttpOverrides();
  runApp(const MyApp());
}

class MyApp extends StatelessWidget {
  const MyApp({super.key});

  @override
  Widget build(BuildContext context) {
    return MultiProvider(
      providers: [
        ChangeNotifierProvider(
          create: (_) => TeknisiGetAllProvider(api: ApiTeknisi()),
        ),
        ChangeNotifierProvider(
          create: (_) => TeknisiRegisterProvider(api: ApiAuth()),
        ),
        ChangeNotifierProvider(
          create: (_) => TeknisiLoginProvider(api: ApiAuth()),
        ),
        ChangeNotifierProvider(create: (_) => ProfileProvider(api: ApiAuth())),
        ChangeNotifierProvider(
          create: (_) => TaskGetAllProvider(api: ApiTask()),
        ),
        ChangeNotifierProvider(
          create: (_) => HistoryGetAllProvider(api: ApiTask()),
        ),
        ChangeNotifierProvider(
          create: (_) => PencapaianTeknisiProvider(api: ApiTask()),
        ),
        ChangeNotifierProvider(
          create: (_) => PelaksanaanSendProvider(api: ApiPelaksanaan()),
        ),
        ChangeNotifierProvider(
          create: (_) =>
              ReportPelaksanaanProvider(apiService: ApiPelaksanaan()),
        ),
        ChangeNotifierProvider(
          create: (_) => LanjutNantiProvider(apiService: ApiPelaksanaan()),
        ),
        ChangeNotifierProvider(
          create: (_) => RescheduleProvider(api: ApiPelaksanaan()),
        ),
        ChangeNotifierProvider(create: (_) => AuthProvider()),
        ChangeNotifierProvider(create: (_) => MapsProvider()),
        ChangeNotifierProvider(
          create: (_) => BarangGetProvider(api: ApiBarang()),
        ),
        ChangeNotifierProvider(
          create: (_) => DetailTaskGetProvider(api: ApiTask()),
        ),
        ChangeNotifierProvider(
          create: (_) => PinjamSendProvider(api: ApiPinjam()),
        ),
        ChangeNotifierProvider(
          create: (_) => PinjamGetProvider(api: ApiPinjam()),
        ),
        ChangeNotifierProvider(
          create: (_) => PinjamDeleteProvider(api: ApiPinjam()),
        ),
        ChangeNotifierProvider(
          create: (_) => DetailPinjamGetProvider(api: ApiPinjam()),
        ),
        ChangeNotifierProvider(
          create: (_) => StatusPinjamProvider(api: ApiPinjam()),
        ),
        ChangeNotifierProvider(
          create: (_) => ChartsProvider(api: ApiTeknisi()),
        ),
        ChangeNotifierProvider(
          create: (_) => KembalikanPinjamProvider(apiService: ApiPinjam()),
        ),
        ChangeNotifierProvider(
          create: (_) => TutorialAllProvider(api: ApiTutor()),
        ),
        ChangeNotifierProvider(
          create: (_) => DetailTutorProvider(api: ApiTutor()),
        ),
        ChangeNotifierProvider(
          create: (_) => CoProvider(api: ApiPelaksanaan()),
        ),
        ChangeNotifierProvider(create: (_) => ReimburseProvider()),
        ChangeNotifierProvider(create: (_) => ReimburseSendProvider()),
        ChangeNotifierProvider(create: (_) => ReimburseDeleteProvider()),
        ChangeNotifierProvider(create: (_) => PencapaianProvider()),
        ChangeNotifierProvider(create: (_) => TutorialProvider()),
        ChangeNotifierProvider(
          create: (_) => PreferencesTokenProvider(
            repository: AuthRepository(
              sharedPreferences: SharedPreferences.getInstance(),
            ),
          ),
        ),
        ChangeNotifierProvider(
          create: (_) => PreferencesIDProvider(
            repository: AuthRepository(
              sharedPreferences: SharedPreferences.getInstance(),
            ),
          ),
        ),
      ],
      child: MaterialApp(
        debugShowCheckedModeBanner: false,
        initialRoute: SplashScreen.routeName,
        routes: {
          SplashScreen.routeName: (context) => const SplashScreen(),
          LoginPage.routeName: (context) => const LoginPage(),
          HomePageAdmin.routeName: (context) => const HomePageAdmin(),
          TaskPage.routeName: (context) => TaskPage(
            dataa: ModalRoute.of(context)?.settings.arguments as List<dynamic>,
          ),
          ReportDonePage.routeName: (context) => ReportDonePage(
            data: ModalRoute.of(context)?.settings.arguments as List<dynamic>,
          ),
          LanjutNantiPage.routeName: (context) => LanjutNantiPage(
            data: ModalRoute.of(context)?.settings.arguments as List<dynamic>,
          ),
          ReschedulePage.routeName: (context) => ReschedulePage(
            id: ModalRoute.of(context)?.settings.arguments as String,
          ),
          DetailTaskPage.routeName: (context) => DetailTaskPage(
            data: ModalRoute.of(context)?.settings.arguments as DataTask,
          ),
          RegisterPage.routeName: (context) => const RegisterPage(),
          PinjamBarangPage.routeName: (context) => const PinjamBarangPage(),
          DetailPinjamPage.routeName: (context) => DetailPinjamPage(
            code: ModalRoute.of(context)?.settings.arguments as String,
          ),
          KembalikanPinjamanPage.routeName: (context) => KembalikanPinjamanPage(
            data: ModalRoute.of(context)?.settings.arguments as List<dynamic>,
          ),
          // TutorialPage.routeName : (context) => const TutorialPage(),
          VoiceRecordPage.routeName: (context) => VoiceRecordPage(
            data: ModalRoute.of(context)?.settings.arguments as List<dynamic>,
          ),
        },
      ),
    );
  }
}

class MyHttpOverrides extends HttpOverrides {
  @override
  HttpClient createHttpClient(SecurityContext? context) {
    return super.createHttpClient(context)
      ..badCertificateCallback =
          (X509Certificate cert, String host, int port) => true;
  }
}
