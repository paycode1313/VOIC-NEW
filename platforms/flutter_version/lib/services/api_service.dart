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

  // Practice: Start Session
  static Future<Map<String, dynamic>> startPractice({
    required int aiRoleId,
    String? scenarioType,
    String language = 'id',
    String? contextMaterial,
  }) async {
    final response = await http.post(
      Uri.parse(ApiConfig.practiceStart),
      headers: await _getHeaders(),
      body: jsonEncode({
        'ai_role_id': aiRoleId,
        'scenario_type': scenarioType,
        'language': language,
        'context_material': contextMaterial,
      }),
    );

    if (response.statusCode == 201) {
      return jsonDecode(response.body);
    }
    throw Exception('Gagal memulai sesi latihan');
  }

  // Practice: Send conversational turn (speech text + face telemetry)
  static Future<Map<String, dynamic>> sendMessageTurn({
    required int sessionId,
    required String message,
    required FacialTelemetry telemetry,
    required int timestampSeconds,
    String language = 'id',
  }) async {
    final response = await http.post(
      Uri.parse(ApiConfig.practiceMessage(sessionId)),
      headers: await _getHeaders(),
      body: jsonEncode({
        'message': message,
        'facial_status': telemetry.toJson(),
        'timestamp_seconds': timestampSeconds,
        'language': language,
      }),
    );

    if (response.statusCode == 200) {
      return jsonDecode(response.body);
    }
    throw Exception('Gagal memproses giliran bicara');
  }

  // Practice: Finalize and calculate score
  static Future<SessionEvaluationReport> finishPractice({
    required int sessionId,
    required int durationSeconds,
    required double faceScore,
    required double voiceScore,
    required double overallScore,
    Map<String, dynamic>? feedbackNotes,
  }) async {
    final response = await http.post(
      Uri.parse(ApiConfig.practiceFinish(sessionId)),
      headers: await _getHeaders(),
      body: jsonEncode({
        'duration_seconds': durationSeconds,
        'face_score': faceScore,
        'voice_score': voiceScore,
        'overall_score': overallScore,
        'feedback_notes': feedbackNotes ?? {},
      }),
    );

    if (response.statusCode == 200) {
      final json = jsonDecode(response.body);
      return SessionEvaluationReport.fromJson(json['session']);
    }
    throw Exception('Gagal menyelesaikan evaluasi sesi');
  }
}
