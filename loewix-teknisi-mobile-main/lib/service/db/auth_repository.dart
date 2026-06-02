import 'package:shared_preferences/shared_preferences.dart';

class AuthRepository {
  final Future<SharedPreferences> sharedPreferences;

  AuthRepository({required this.sharedPreferences});

  static const userToken = 'USER_TOKEN';
  static const userID = 'USER_ID';

  Future<String> get isUserToken async {
    final prefs = await sharedPreferences;
    return prefs.getString(userToken) ?? 'no token';
  }

  Future<void> setUserToken(String token) async {
    final prefs = await sharedPreferences;
    await prefs.setString(userToken, token);
  }

  Future<void> deleteUserToken() async {
    final prefs = await sharedPreferences;
    await prefs.remove(userToken);
  }

  Future<String> get isUserID async {
    final prefs = await sharedPreferences;
    return prefs.getString(userID) ?? '1';
  }

  Future<void> setUserID(String id) async {
    final prefs = await sharedPreferences;
    await prefs.setString(userID, id);
  }

  Future<void> deleteUserID() async {
    final prefs = await sharedPreferences;
    await prefs.remove(userID);
  }
}