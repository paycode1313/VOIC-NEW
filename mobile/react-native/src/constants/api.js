import { NativeModules, Platform } from 'react-native';

// Base URL API Laravel
// Otomatis mendeteksi IP host dari browser (web) atau Metro bundler (Expo Go),
// sehingga tetap terhubung meski IP Wi-Fi/hotspot laptop berubah.
const FALLBACK_HOST = '10.184.85.82';

const getHostFromScriptUrl = () => {
  const scriptURL = NativeModules?.SourceCode?.scriptURL;
  if (!scriptURL || typeof scriptURL !== 'string') {
    return null;
  }
  const match = scriptURL.match(/^https?:\/\/([^/:]+)/i);
  return match?.[1] || null;
};

const getBaseUrl = () => {
  if (Platform.OS === 'web' && typeof window !== 'undefined' && window.location?.hostname) {
    return `http://${window.location.hostname}:8000/api/v1`;
  }
  const metroHost = getHostFromScriptUrl();
  const host = metroHost && metroHost !== 'localhost' && metroHost !== '127.0.0.1'
    ? metroHost
    : FALLBACK_HOST;
  return `http://${host}:8000/api/v1`;
};

export const API_BASE_URL = getBaseUrl();

export const endpoints = {
  login: `${API_BASE_URL}/auth/login`,
  register: `${API_BASE_URL}/auth/register`,
  user: `${API_BASE_URL}/auth/user`,
  logout: `${API_BASE_URL}/auth/logout`,
  roles: `${API_BASE_URL}/roles`,
  // Three-part mock exam (same engine as the web app)
  examModes: `${API_BASE_URL}/exam/modes`,
  examStart: (mode) => `${API_BASE_URL}/exam/${mode}/start`,
  examAnswer: (sessionId) => `${API_BASE_URL}/exam/${sessionId}/answer`,
  examFinish: (sessionId) => `${API_BASE_URL}/exam/${sessionId}/finish`,
  practiceHistory: `${API_BASE_URL}/practice`,
};
