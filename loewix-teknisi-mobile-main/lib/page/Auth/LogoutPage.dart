import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import '../../service/provider/Auth/AuthProvider.dart';
import '../../service/provider/preferences/PreferencesIDProvider.dart';
import 'LoginPage.dart';

class LogoutPage extends StatelessWidget {
  const LogoutPage({super.key});

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: Center(
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.center,
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Text('Apakah anda ingin keluar?'),
            SizedBox(height: 25,),
            SizedBox(
              height: 50,
              width: 200,
              child: ElevatedButton(
                onPressed: () async {
                  Provider.of<AuthProvider>(context, listen: false).logout();
                  Provider.of<PreferencesIDProvider>(context, listen: false).deleteRole();
                  Navigator.pushReplacement(
                      context,
                      MaterialPageRoute(
                        builder: (context) => const LoginPage(),
                      )
                  );
                },
                style: ElevatedButton.styleFrom(
                    backgroundColor: Colors.blue),
                child: Text(
                  'Keluar',
                  style: TextStyle(fontFamily: 'Poppins',color: Colors.white),
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }
}
