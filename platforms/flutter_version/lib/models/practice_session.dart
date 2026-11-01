class PracticeMessage {
  final int? id;
  final String sender; // 'user' or 'ai'
  final String message;
  final String? audioUrl;
  final int timestampSeconds;
  final String? facialCritique;

  PracticeMessage({
    this.id,
    required this.sender,
    required this.message,
    this.audioUrl,
    required this.timestampSeconds,
    this.facialCritique,
  });

  factory PracticeMessage.fromJson(Map<String, dynamic> json) {
    return PracticeMessage(
      id: json['id'] as int?,
      sender: json['sender'] as String? ?? 'ai',
      message: json['message'] as String? ?? json['response_text'] as String? ?? '',
      audioUrl: json['audio_url'] as String?,
      timestampSeconds: json['timestamp_seconds'] as int? ?? 0,
      facialCritique: json['facial_critique'] as String?,
    );
  }
}

class FacialTelemetry {
  final double eyeContactScore;
  final bool isSmiling;
  final bool faceDetected;
  final String status;

  FacialTelemetry({
    required this.eyeContactScore,
    required this.isSmiling,
    required this.faceDetected,
    required this.status,
  });

  Map<String, dynamic> toJson() {
    return {
      'eye_contact_score': eyeContactScore,
      'is_smiling': isSmiling,
      'face_detected': faceDetected,
      'status': status,
    };
  }
}

class SessionEvaluationReport {
  final int sessionId;
  final String scenarioType;
  final int durationSeconds;
  final double faceScore;
  final double voiceScore;
  final double overallScore;
  final String aiConclusion;
  final Map<String, dynamic> feedbackNotes;
  final String? verificationUrl;
  final String? certificateQrData;

  SessionEvaluationReport({
    required this.sessionId,
    required this.scenarioType,
    required this.durationSeconds,
    required this.faceScore,
    required this.voiceScore,
    required this.overallScore,
    required this.aiConclusion,
    required this.feedbackNotes,
    this.verificationUrl,
    this.certificateQrData,
  });

  factory SessionEvaluationReport.fromJson(Map<String, dynamic> json) {
    return SessionEvaluationReport(
      sessionId: json['id'] as int? ?? 0,
      scenarioType: json['scenario_type'] as String? ?? '',
      durationSeconds: json['duration_seconds'] as int? ?? 0,
      faceScore: (json['face_score'] as num?)?.toDouble() ?? 0.0,
      voiceScore: (json['voice_score'] as num?)?.toDouble() ?? 0.0,
      overallScore: (json['overall_score'] as num?)?.toDouble() ?? 0.0,
      aiConclusion: json['ai_conclusion'] as String? ?? '',
      feedbackNotes: json['feedback_notes'] as Map<String, dynamic>? ?? {},
      verificationUrl: json['verification_url'] as String?,
      certificateQrData: json['certificate_qr_data'] as String?,
    );
  }
}
