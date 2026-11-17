import { Platform } from 'react-native';
import { endpoints } from '../constants/api';

const TOKEN_KEY = 'voic_auth_token';
let unauthorizedHandler = null;

const readStoredToken = () => {
  if (globalThis.__VOIC_AUTH_TOKEN__) {
    return globalThis.__VOIC_AUTH_TOKEN__;
  }
  try {
    if (typeof window !== 'undefined' && window.localStorage) {
      const saved = window.localStorage.getItem(TOKEN_KEY);
      if (saved) {
        globalThis.__VOIC_AUTH_TOKEN__ = saved;
        return saved;
      }
    }
  } catch (e) {}
  return null;
};

export const setAuthToken = (token) => {
  globalThis.__VOIC_AUTH_TOKEN__ = token || null;
  try {
    if (typeof window !== 'undefined' && window.localStorage) {
      if (token) {
        window.localStorage.setItem(TOKEN_KEY, token);
      } else {
        window.localStorage.removeItem(TOKEN_KEY);
      }
    }
  } catch (e) {}
};

export const getAuthToken = () => readStoredToken();

export const clearAuthToken = () => {
  setAuthToken(null);
};

export const setUnauthorizedHandler = (handler) => {
  unauthorizedHandler = handler;
};

const getHeaders = () => {
  const token = getAuthToken();
  return {
    'Content-Type': 'application/json',
    Accept: 'application/json',
    ...(token ? { Authorization: `Bearer ${token}` } : {}),
  };
};

const parseResponse = async (res, notifyUnauthorized = true) => {
  const rawText = await res.text();
  let data = null;
  try {
    data = rawText ? JSON.parse(rawText) : {};
  } catch (e) {
    throw new Error(`Server membalas HTTP ${res.status} (format bukan JSON).`);
  }
  if (res.status === 401 && notifyUnauthorized) {
    clearAuthToken();
    if (typeof unauthorizedHandler === 'function') {
      unauthorizedHandler();
    }
  }
  return data;
};

/**
 * React Native / Expo SDK 57 global fetch (expo/fetch) does not support `{ uri, name, type }`
 * parts inside FormData (`Unsupported FormDataPart implementation`). On native platforms,
 * XMLHttpRequest routes FormData through RCTNetworking, which supports file URIs natively.
 */
const uploadFormDataWithXhr = (url, formData, token, notifyUnauthorized = true) =>
  new Promise((resolve, reject) => {
    const xhr = new XMLHttpRequest();
    xhr.open('POST', url);
    xhr.timeout = 45000;
    xhr.setRequestHeader('Accept', 'application/json');
    if (token) {
      xhr.setRequestHeader('Authorization', `Bearer ${token}`);
    }

    xhr.onload = () => {
      const rawText = xhr.responseText || '';
      let data = null;
      try {
        data = rawText ? JSON.parse(rawText) : {};
      } catch (e) {
        reject(new Error(`Server membalas HTTP ${xhr.status} (format bukan JSON).`));
        return;
      }

      if (xhr.status === 401 && notifyUnauthorized) {
        clearAuthToken();
        if (typeof unauthorizedHandler === 'function') {
          unauthorizedHandler();
        }
      }
      resolve(data);
    };

    xhr.onerror = () => {
      reject(new Error(`Tidak dapat menghubungi server (${url}). Pastikan HP & laptop di Wi-Fi yang sama dan server Laravel berjalan.`));
    };

    xhr.ontimeout = () => {
      reject(new Error('Waktu tunggu habis (45 detik) saat memproses suara di server.'));
    };

    xhr.send(formData);
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
    const data = await parseResponse(res, false);
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
    const data = await parseResponse(res, false);
    if (res.ok && data.token) {
      setAuthToken(data.token);
    }
    return data;
  },

  getCurrentUser: async () => {
    const res = await fetch(endpoints.user, {
      method: 'GET',
      headers: getHeaders(),
    });
    return await parseResponse(res);
  },

  logout: async () => {
    const res = await fetch(endpoints.logout, {
      method: 'POST',
      headers: getHeaders(),
    });
    return await parseResponse(res, false);
  },

  getPracticeHistory: async () => {
    const res = await fetch(endpoints.practiceHistory, {
      method: 'GET',
      headers: getHeaders(),
    });
    return await parseResponse(res);
  },

  // Get AI Roles & Scenarios
  getRoles: async () => {
    const res = await fetch(endpoints.roles, {
      method: 'GET',
      headers: getHeaders(),
    });
    return await parseResponse(res, false);
  },

  // Get Unified Exam Modes (each mode pairs an AI examiner with matching topics & parts)
  getExamModes: async (language = 'id') => {
    const res = await fetch(`${endpoints.examModes}?lang=${encodeURIComponent(language)}`, {
      method: 'GET',
      headers: getHeaders(),
    });
    return await parseResponse(res, false);
  },

  // Map a legacy role type to an exam mode slug.
  modeForRole: (roleType) => ({ hrd: 'wawancara', investor: 'pitching' }[roleType] || 'sidang'),

  // Start a three-part mock exam or 10-min coach chat. Returns the examiner's opening line and exam state.
  startExam: async (mode, { topic = null, language = 'id', contextMaterial = null, parts = [1, 2, 3], sessionType = 'exam' } = {}) => {
    const res = await fetch(endpoints.examStart(mode), {
      method: 'POST',
      headers: getHeaders(),
      body: JSON.stringify({
        topic,
        language,
        context_material: contextMaterial,
        parts,
        session_type: sessionType,
        consent: true, // shown to the user on the start screen
      }),
    });
    return await parseResponse(res);
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
    return await parseResponse(res);
  },

  // Send recorded voice audio file directly to Laravel (transcribed by Groq/local Whisper).
  answerExamAudio: async (sessionId, audioUri, durationSeconds, timestampSeconds = 0) => {
    const token = getAuthToken();
    const url = endpoints.examAnswer(sessionId);
    const formData = new FormData();
    const cleanUri = String(audioUri || '');
    const extMatch = cleanUri.split('?')[0].match(/\.([a-zA-Z0-9]+)$/);
    const ext = (extMatch?.[1] || (Platform.OS === 'web' ? 'webm' : 'm4a')).toLowerCase();
    const mime =
      ext === 'webm'
        ? 'audio/webm'
        : ext === 'wav'
          ? 'audio/wav'
          : ext === 'ogg'
            ? 'audio/ogg'
            : ext === '3gp'
              ? 'audio/3gpp'
              : 'audio/mp4';

    if (Platform.OS === 'web' || cleanUri.startsWith('blob:')) {
      const audioRes = await fetch(cleanUri);
      const blob = await audioRes.blob();
      formData.append('audio', blob, `answer.${ext}`);
      formData.append('duration_seconds', String(Math.max(1, Math.round(durationSeconds || 1))));
      formData.append('timestamp_seconds', String(Math.max(0, Math.round(timestampSeconds || 0))));

      const res = await fetch(url, {
        method: 'POST',
        headers: {
          Accept: 'application/json',
          ...(token ? { Authorization: `Bearer ${token}` } : {}),
        },
        body: formData,
      });
      return await parseResponse(res);
    }

    formData.append('audio', {
      uri: cleanUri,
      name: `answer.${ext}`,
      type: mime,
    });
    formData.append('duration_seconds', String(Math.max(1, Math.round(durationSeconds || 1))));
    formData.append('timestamp_seconds', String(Math.max(0, Math.round(timestampSeconds || 0))));

    return await uploadFormDataWithXhr(url, formData, token);
  },

  // Finish the exam. Returns { evaluation } with the real band-score report computed by the server.
  finishExam: async (sessionId, durationSeconds = 0) => {
    const res = await fetch(endpoints.examFinish(sessionId), {
      method: 'POST',
      headers: getHeaders(),
      body: JSON.stringify({ duration_seconds: Math.max(0, Math.round(durationSeconds || 0)) }),
    });
    return await parseResponse(res);
  },
};
