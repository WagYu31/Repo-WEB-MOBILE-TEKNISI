import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:quickalert/quickalert.dart';

import '../../service/model/teknisi/TeknisiGetAllModel.dart';
import '../../service/provider/Auth/TeknisiRegisterProvider.dart';
import '../../service/provider/Teknisi/TeknisiGetAllProvider.dart';
import '../../utils/state.dart';
import 'LoginPage.dart';

class RegisterPage extends StatefulWidget {
  static const routeName = 'register_page';

  const RegisterPage({super.key});
  @override
  _RegisterPageState createState() => _RegisterPageState();
}

class _RegisterPageState extends State<RegisterPage> {
  final _formKey = GlobalKey<FormState>();
  final _usernameController = TextEditingController();
  final _emailController = TextEditingController();
  final _nikController = TextEditingController();
  final _passwordController = TextEditingController();

  @override
  void dispose() {
    _usernameController.dispose();
    _emailController.dispose();
    _passwordController.dispose();
    _nikController.dispose();
    super.dispose();
  }

  void _register() async {
    if (_formKey.currentState!.validate()) {
      if (_nikController.text != _selectedTeknisi!.ktp) {
        QuickAlert.show(
            context: context,
            type: QuickAlertType.warning,
            title: 'Kesalahan Verifikasi',
            text: 'Data yang dimasukan salah!');
      } else {
        QuickAlert.show(
            context: context,
            type: QuickAlertType.loading,
            title: 'Harap Tunggu',
            text: 'Sedang validasi Data!');

        await Provider.of<TeknisiRegisterProvider>(context, listen: false)
            .doRegis(_emailController.text, _passwordController.text,
                _usernameController.text, _selectedTeknisi!.id.toString())
            .then((value) {
          if (value.toString() == "Registrasi Berhasil") {
            Navigator.pop(context);

            QuickAlert.show(
                context: context,
                type: QuickAlertType.success,
                title: 'Berhasil',
                text: value,
                onConfirmBtnTap: () {
                  Navigator.pushAndRemoveUntil(
                    context,
                    MaterialPageRoute(builder: (context) => const LoginPage()),
                        (Route<dynamic> route) => false,
                  );
                });
          } else {
            Navigator.pop(context);

            QuickAlert.show(
                context: context,
                type: QuickAlertType.error,
                title: 'Gagal',
                text: value);
          }
        });
      }
    }
  }

  DataTeknisi? _selectedTeknisi;

  @override
  Widget build(BuildContext context) {
    return Scaffold(body: Consumer<TeknisiGetAllProvider>(
      builder: (context, state, child) {
        if (state.state == ResultState.loading) {
          return const Center(
            child: Column(
              children: [CircularProgressIndicator(), Text('Mohon Tunggu')],
            ),
          );
        } else if (state.state == ResultState.hasData) {
          return Center(
            child: SingleChildScrollView(
              padding: const EdgeInsets.all(16.0),
              child: Material(
                elevation: 8,
                shape: RoundedRectangleBorder(
                  borderRadius: BorderRadius.circular(16),
                ),
                child: Padding(
                  padding: const EdgeInsets.all(16.0),
                  child: Form(
                    key: _formKey,
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.stretch,
                      children: <Widget>[
                        const Text(
                          'Create Account',
                          style: TextStyle(
                            fontSize: 24,
                            fontWeight: FontWeight.bold,
                          ),
                          textAlign: TextAlign.center,
                        ),
                        const SizedBox(height: 16),
                        SizedBox(
                          child: DropdownButtonFormField<DataTeknisi>(
                            value: _selectedTeknisi,
                            hint: const Text('Pilih Nama anda'),
                            decoration: const InputDecoration(
                              border: OutlineInputBorder(),
                            ),
                            items:
                                state.response.data.map((DataTeknisi teknisi) {
                              return DropdownMenuItem<DataTeknisi>(
                                value: teknisi,
                                child: Text(teknisi.nama),
                              );
                            }).toList(),
                            onChanged: (DataTeknisi? newValue) {
                              setState(() {
                                _selectedTeknisi = newValue;
                              });
                              print('hasil pilih ${_selectedTeknisi!.nama}');
                              print('hasil pilih id${_selectedTeknisi!.id}');
                            },
                          ),
                        ),
                        const SizedBox(height: 16),
                        TextFormField(
                          controller: _usernameController,
                          decoration: InputDecoration(
                            labelText: 'Nama',
                            border: OutlineInputBorder(
                              borderRadius: BorderRadius.circular(8),
                            ),
                          ),
                          validator: (value) {
                            if (value == null || value.isEmpty) {
                              return 'Please enter your name';
                            }
                            return null;
                          },
                        ),
                        const SizedBox(height: 16),
                        TextFormField(
                          controller: _emailController,
                          decoration: InputDecoration(
                            labelText: 'Username',
                            border: OutlineInputBorder(
                              borderRadius: BorderRadius.circular(8),
                            ),
                          ),
                          validator: (value) {
                            if (value == null || value.isEmpty) {
                              return 'Please enter your username';
                            }
                            return null;
                          },
                        ),
                        const SizedBox(height: 16),
                        TextFormField(
                          controller: _passwordController,
                          decoration: InputDecoration(
                            labelText: 'Password',
                            border: OutlineInputBorder(
                              borderRadius: BorderRadius.circular(8),
                            ),
                          ),
                          obscureText: true,
                          validator: (value) {
                            if (value == null || value.isEmpty) {
                              return 'Please enter your password';
                            }
                            return null;
                          },
                        ),
                        const SizedBox(height: 16),
                        TextFormField(
                          controller: _nikController,
                          decoration: InputDecoration(
                            labelText: 'NIK/No KTP (Validasi)',
                            border: OutlineInputBorder(
                              borderRadius: BorderRadius.circular(8),
                            ),
                          ),
                          validator: (value) {
                            if (value == null || value.isEmpty) {
                              return 'Please enter your nik';
                            }
                            return null;
                          },
                        ),
                        const SizedBox(height: 24),
                        ElevatedButton(
                          onPressed: _register,
                          style: ElevatedButton.styleFrom(
                            padding: const EdgeInsets.symmetric(vertical: 16),
                            shape: RoundedRectangleBorder(
                              borderRadius: BorderRadius.circular(8),
                            ),
                            backgroundColor: Colors.blue, // Warna latar belakang biru
                            foregroundColor: Colors.white, // Warna teks putih
                          ),
                          child: const Text('Register'),
                        ),
                      ],
                    ),
                  ),
                ),
              ),
            ),
          );
        } else {
          return Center(
            child: Column(
              children: [const CircularProgressIndicator(), Text(state.message)],
            ),
          );
        }
      },
    ));
  }
}
