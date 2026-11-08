import 'dart:convert';
import 'package:http/http.dart' as http;
import 'package:shared_preferences/shared_preferences.dart';
import '../constants/api_config.dart';
import '../models/ai_role.dart';
import '../models/practice_session.dart';

class ApiService {
  static const String tokenKey = 'voic_auth_token';

  static Future<String?> getToken() async {
    final prefs = await SharedPreferences.getInstance();
    return prefs.getString(tokenKey);
  }

  static Future<void> saveToken(String token) async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.setString(tokenKey, token);
  }

  static Future<void> clearToken() async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.remove(tokenKey);
  }

  static Future<Map<String, String>> _getHeaders() async {
    final token = await getToken();
    return {
      'Content-Type': 'application/json',
      'Accept': 'application/json',
      if (token != null) 'Authorization': 'Bearer $token',
    };
  }

  // Auth: Login
  static Future<Map<String, dynamic>> login(String email, String password) async {
    final response = await http.post(
      Uri.parse(ApiConfig.login),
      headers: {'Content-Type': 'application/json', 'Accept': 'application/json'},
      body: jsonEncode({
        'email': email,
        'password': password,
        'device_name': 'VOIC Mobile Android',
      }),
    );

    final data = jsonDecode(response.body);
    if (response.statusCode == 200 && data['token'] != null) {
      await saveToken(data['token']);
    }
    return data;
  }

  // Reference: Fetch Roles & Scenarios
  static Future<Map<String, dynamic>> fetchRolesAndScenarios() async {
    final response = await http.get(
      Uri.parse(ApiConfig.roles),
      headers: await _getHeaders(),
    );

    if (response.statusCode == 200) {
      final json = jsonDecode(response.body);
      final roles = (json['roles'] as List)
          .map((item) => AiRole.fromJson(item))
          .toList();
      final scenarios = (json['scenarios'] as List)
          .map((item) => PracticeScenario.fromJson(item))
          .toList();

      return {
        'roles': roles,
        'scenarios': scenarios,
      };
    }
    throw Exception('Gagal memuat peran AI');
  }

  /// Map a legacy role type to an exam mode slug.
  static String modeForRole(String roleType) {
    switch (roleType) {
      case 'hrd':
        return 'wawancara';
      case 'investor':
        return 'pitching';
      default:
        return 'sidang';
    }
  }

  /// Start a three-part mock exam. Returns the examiner's opening line and exam state.
  static Future<Map<String, dynamic>> startExam({
    required String mode,
    String? topic,
    String language = 'id',
    String? contextMaterial,
    List<int> parts = const [1, 2, 3],
  }) async {
    final response = await http.post(
      Uri.parse(ApiConfig.examStart(mode)),
      headers: await _getHeaders(),
      body: jsonEncode({
        'topic': topic,
        'language': language,
        'context_material': contextMaterial,
        'parts': parts,
        'consent': true, // shown to the user on the start screen
      }),
    );

    if (response.statusCode == 201) {
      return jsonDecode(response.body);
    }
    throw Exception('Gagal memulai sesi latihan');
  }

  /// Send one answer (text recognised on the device). Returns the examiner's next turn.
  static Future<Map<String, dynamic>> answerExam({
    required int sessionId,
    required String answer,
    required int durationSeconds,
    required int timestampSeconds,
  }) async {
    final response = await http.post(
      Uri.parse(ApiConfig.examAnswer(sessionId)),
      headers: await _getHeaders(),
      body: jsonEncode({
        'answer': answer,
        'duration_seconds': durationSeconds,
        'timestamp_seconds': timestampSeconds,
      }),
    );

    if (response.statusCode == 200) {
      return jsonDecode(response.body);
    }
    throw Exception('Gagal memproses jawaban');
  }

  /// Finish the exam. The band-score report is computed on the server from the real answers.
  static Future<SessionEvaluationReport> finishExam({
    required int sessionId,
    required int durationSeconds,
  }) async {
    final response = await http.post(
      Uri.parse(ApiConfig.examFinish(sessionId)),
      headers: await _getHeaders(),
      body: jsonEncode({'duration_seconds': durationSeconds}),
    );

    if (response.statusCode == 200) {
      final json = jsonDecode(response.body);
      return SessionEvaluationReport.fromJson(json['evaluation']);
    }
    throw Exception('Gagal menyelesaikan evaluasi sesi');
  }
}
