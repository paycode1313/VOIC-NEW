import { Platform } from 'react-native';

// Base URL API Laravel
// Use Laragon on the laptop for web, and the laptop's Wi-Fi IP on physical devices.
const webApiBaseUrl = 'http://localhost/VOIC-NEW/public/api/v1';
const mobileApiBaseUrl = 'http://10.245.84.82/VOIC-NEW/public/api/v1';

export const API_BASE_URL = Platform.OS === 'web' ? webApiBaseUrl : mobileApiBaseUrl;

export const endpoints = {
  login: `${API_BASE_URL}/auth/login`,
  register: `${API_BASE_URL}/auth/register`,
  user: `${API_BASE_URL}/auth/user`,
  logout: `${API_BASE_URL}/auth/logout`,
  roles: `${API_BASE_URL}/roles`,
  practiceStart: `${API_BASE_URL}/practice/start`,
  practiceMessage: (sessionId) => `${API_BASE_URL}/practice/${sessionId}/message`,
  practiceFinish: (sessionId) => `${API_BASE_URL}/practice/${sessionId}/finish`,
  practiceHistory: `${API_BASE_URL}/practice`,
};
