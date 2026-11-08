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

  // Map a legacy role type to an exam mode slug.
  modeForRole: (roleType) => ({ hrd: 'wawancara', investor: 'pitching' }[roleType] || 'sidang'),

  // Start a three-part mock exam. Returns the examiner's opening line and exam state.
  startExam: async (mode, { topic = null, language = 'id', contextMaterial = null, parts = [1, 2, 3] } = {}) => {
    const res = await fetch(endpoints.examStart(mode), {
      method: 'POST',
      headers: getHeaders(),
      body: JSON.stringify({
        topic,
        language,
        context_material: contextMaterial,
        parts,
        consent: true, // shown to the user on the start screen
      }),
    });
    return await res.json();
  },

  // Send one answer (text recognised on the device). Returns the examiner's next turn.
  answerExam: async (sessionId, answer, durationSeconds, timestampSeconds = 0) => {
    const res = await fetch(endpoints.examAnswer(sessionId), {
      method: 'POST',
      headers: getHeaders(),
      body: JSON.stringify({
        answer,
        duration_seconds: Math.max(0, Math.round(durationSeconds || 0)),
        timestamp_seconds: Math.max(0, Math.round(timestampSeconds || 0)),
      }),
    });
    return await res.json();
  },

  // Finish the exam. Returns { evaluation } with the real band-score report computed by the server.
  finishExam: async (sessionId, durationSeconds = 0) => {
    const res = await fetch(endpoints.examFinish(sessionId), {
      method: 'POST',
      headers: getHeaders(),
      body: JSON.stringify({ duration_seconds: Math.max(0, Math.round(durationSeconds || 0)) }),
    });
    return await res.json();
  },
};
