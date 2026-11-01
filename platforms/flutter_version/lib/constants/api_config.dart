class ApiConfig {
  // Gunakan 10.0.2.2 untuk Android Emulator yang mengakses localhost mesin host.
  // Ganti dengan IP LAN (misal: 192.168.1.X:8000) jika menggunakan HP fisik melalui WiFi.
  static const String baseUrl = 'http://10.0.2.2:8000/api/v1';

  // Auth
  static const String login = '$baseUrl/auth/login';
  static const String register = '$baseUrl/auth/register';
  static const String user = '$baseUrl/auth/user';
  static const String logout = '$baseUrl/auth/logout';

  // Roleplay & Practice
  static const String roles = '$baseUrl/roles';
  static const String practiceStart = '$baseUrl/practice/start';
  static String practiceMessage(int sessionId) => '$baseUrl/practice/$sessionId/message';
  static String practiceFinish(int sessionId) => '$baseUrl/practice/$sessionId/finish';
  static const String practiceHistory = '$baseUrl/practice';
  static String practiceDetail(int sessionId) => '$baseUrl/practice/$sessionId';
}
