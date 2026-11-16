class ApiConfig {
  // IP LAN mesin host saat ini: 10.161.13.82:8000
  static const String baseUrl = 'http://10.161.13.82:8000/api/v1';

  // Auth
  static const String login = '$baseUrl/auth/login';
  static const String register = '$baseUrl/auth/register';
  static const String user = '$baseUrl/auth/user';
  static const String logout = '$baseUrl/auth/logout';

  // Roleplay & Practice
  static const String roles = '$baseUrl/roles';
  // Three-part mock exam (same engine as the web app)
  static const String examModes = '$baseUrl/exam/modes';
  static String examStart(String mode) => '$baseUrl/exam/$mode/start';
  static String examAnswer(int sessionId) => '$baseUrl/exam/$sessionId/answer';
  static String examFinish(int sessionId) => '$baseUrl/exam/$sessionId/finish';
  static const String practiceHistory = '$baseUrl/practice';
  static String practiceDetail(int sessionId) => '$baseUrl/practice/$sessionId';
}
