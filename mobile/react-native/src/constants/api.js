// Base URL API Laravel
// - Gunakan 'http://10.0.2.2:8000/api/v1' jika menggunakan Android Emulator
// - Gunakan 'http://localhost:8000/api/v1' jika preview di Web Browser laptop
// - Gunakan IP Wi-Fi laptop agar HP di jaringan yang sama bisa mengakses API
// Laragon Apache (port 80) sudah aktif:
export const API_BASE_URL = 'http://10.245.84.82/VOIC-NEW/public/api/v1';
// Alternatif jika menggunakan php artisan serve (dari root project):
// export const API_BASE_URL = 'http://10.245.84.82:8000/api/v1';

export const endpoints = {
  login: `${API_BASE_URL}/auth/login`,
  register: `${API_BASE_URL}/auth/register`,
  user: `${API_BASE_URL}/auth/user`,
  roles: `${API_BASE_URL}/roles`,
  // Three-part mock exam (same engine as the web app)
  examModes: `${API_BASE_URL}/exam/modes`,
  examStart: (mode) => `${API_BASE_URL}/exam/${mode}/start`,
  examAnswer: (sessionId) => `${API_BASE_URL}/exam/${sessionId}/answer`,
  examFinish: (sessionId) => `${API_BASE_URL}/exam/${sessionId}/finish`,
  practiceHistory: `${API_BASE_URL}/practice`,
};
