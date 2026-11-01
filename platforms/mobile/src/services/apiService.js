import { endpoints } from '../constants/api';

let authToken = null;

export const setAuthToken = (token) => {
  authToken = token;
};

export const getAuthToken = () => authToken;

const getHeaders = () => ({
  'Content-Type': 'application/json',
  Accept: 'application/json',
  ...(authToken ? { Authorization: `Bearer ${authToken}` } : {}),
});

export const apiService = {
  // Login
  login: async (email, password) => {
    const res = await fetch(endpoints.login, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
      body: JSON.stringify({
        email,
        password,
        device_name: 'VOIC Expo Mobile',
      }),
    });
    const data = await res.json();
    if (res.ok && data.token) {
      setAuthToken(data.token);
    }
    return data;
  },

  // Register New Account
  register: async (name, email, password) => {
    const res = await fetch(endpoints.register, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
      body: JSON.stringify({
        name,
        email,
        password,
        device_name: 'VOIC Expo Mobile',
      }),
    });
    const data = await res.json();
    if (res.ok && data.token) {
      setAuthToken(data.token);
    }
    return data;
  },

  // Get AI Roles & Scenarios
  getRoles: async () => {
    const res = await fetch(endpoints.roles, {
      method: 'GET',
      headers: getHeaders(),
    });
    return await res.json();
  },

  // Start Practice Session (Generates opening greeting from AI Role)
  startPractice: async (aiRoleId, scenarioType, language = 'id', contextMaterial = null) => {
    const res = await fetch(endpoints.practiceStart, {
      method: 'POST',
      headers: getHeaders(),
      body: JSON.stringify({
        ai_role_id: aiRoleId,
        scenario_type: scenarioType,
        language: language || 'id',
        context_material: contextMaterial || null,
      }),
    });
    return await res.json();
  },

  // Send Conversational Turn to AI (speech transcript + facial telemetry)
  sendMessage: async (sessionId, message, eyeContactRatio = 0.9, isSmiling = true, faceDetected = true, timestampSeconds = 1) => {
    const telemetry = {
      status: isSmiling ? 'smile' : 'neutral',
      eye_contact_score: Math.round(eyeContactRatio * 100),
      is_smiling: Boolean(isSmiling),
      face_detected: Boolean(faceDetected),
    };

    const res = await fetch(endpoints.practiceMessage(sessionId), {
      method: 'POST',
      headers: getHeaders(),
      body: JSON.stringify({
        message,
        facial_status: telemetry,
        timestamp_seconds: timestampSeconds || 1,
        language: 'id',
      }),
    });
    return await res.json();
  },

  // Alias for backward compatibility
  sendMessageTurn: async (sessionId, message, telemetry, timestampSeconds = 1) => {
    const res = await fetch(endpoints.practiceMessage(sessionId), {
      method: 'POST',
      headers: getHeaders(),
      body: JSON.stringify({
        message,
        facial_status: telemetry,
        timestamp_seconds: timestampSeconds || 1,
        language: 'id',
      }),
    });
    return await res.json();
  },

  // Finalize Practice Session and Trigger AI Evaluation Report
  finishPractice: async (sessionId, durationSeconds = 60, faceScore = 88.0, voiceScore = 85.0, overallScore = 86.5, feedbackNotes = {}) => {
    const safeDuration = Math.max(parseInt(durationSeconds, 10) || 60, 1);
    const safeFace = parseFloat(faceScore) || 88.0;
    const safeVoice = parseFloat(voiceScore) || 85.0;
    const safeOverall = parseFloat(overallScore) || Math.round((safeFace * 0.4 + safeVoice * 0.6) * 10) / 10;

    const res = await fetch(endpoints.practiceFinish(sessionId), {
      method: 'POST',
      headers: getHeaders(),
      body: JSON.stringify({
        duration_seconds: safeDuration,
        face_score: safeFace,
        voice_score: safeVoice,
        overall_score: safeOverall,
        feedback_notes: feedbackNotes || {},
      }),
    });
    return await res.json();
  },
};
