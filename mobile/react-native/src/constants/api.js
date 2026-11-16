import { Platform } from 'react-native';

// Base URL API Laravel
// Gunakan hostname aktif saat di browser (bisa localhost di laptop atau 10.161.13.82 di HP),
// dan gunakan IP laptop saat di aplikasi native (Expo Go).
const getBaseUrl = () => {
  if (Platform.OS === 'web' && typeof window !== 'undefined' && window.location?.hostname) {
    return `http://${window.location.hostname}:8000/api/v1`;
  }
  return 'http://10.161.13.82:8000/api/v1';
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
