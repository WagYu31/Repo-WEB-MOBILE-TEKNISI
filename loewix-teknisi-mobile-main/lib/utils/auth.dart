class AuthApp {
  static late final String _token;
  static late final String _id;

  static void setToken(String token){
    _token = token;
  }

  static String getToken(){
    return _token;
  }

  static void setID(String id){
    _id = id;
  }

  static String getID(){
    return _id;
  }
}

