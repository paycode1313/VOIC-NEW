import React, { useEffect, useState } from 'react';
import { StatusBar } from 'expo-status-bar';
import { SafeAreaProvider } from 'react-native-safe-area-context';
import { Alert, View, StyleSheet } from 'react-native';
import LoginScreen from './src/screens/LoginScreen';
import HomeScreen from './src/screens/HomeScreen';
import PracticeArenaScreen from './src/screens/PracticeArenaScreen';
import EvaluationReportScreen from './src/screens/EvaluationReportScreen';
import HistoryScreen from './src/screens/HistoryScreen';
import ProfileScreen from './src/screens/ProfileScreen';
import MobileTabBar from './src/components/MobileTabBar';
import { colors } from './src/constants/theme';
import { apiService, clearAuthToken, getAuthToken, setUnauthorizedHandler } from './src/services/apiService';

export default function App() {
  const [currentScreen, setCurrentScreen] = useState('login'); // 'login' | 'home' | 'practice' | 'report'
  const [activeTab, setActiveTab] = useState('practice');
  const [currentUser, setCurrentUser] = useState(null);
  const [activeSession, setActiveSession] = useState(null);
  const [finalReport, setFinalReport] = useState(null);

  const handleLoginSuccess = (user) => {
    setCurrentUser(user);
    setActiveTab('practice');
    setCurrentScreen('home');
  };

  const handleStartPractice = (sessionData) => {
    setActiveSession(sessionData);
    setCurrentScreen('practice');
  };

  const handleFinishSession = (reportData) => {
    setFinalReport(reportData);
    setCurrentScreen('report');
  };

  const handleOpenHistorySession = (session) => {
    const examReport = session.feedback_notes?.exam_report || session.report || null;
    const coachReport = session.feedback_notes?.coach_report || session.coach_report || null;
    setActiveSession({
      sessionId: session.id,
      role: session.ai_role || session.aiRole || null,
    });
    setFinalReport({
      ...session,
      report: examReport,
      coach_report: coachReport,
    });
    setCurrentScreen('report');
  };

  const handleBackToHome = () => {
    setActiveSession(null);
    setFinalReport(null);
    setCurrentScreen('home');
  };

  const handleLogout = () => {
    clearAuthToken();
    setCurrentUser(null);
    setActiveSession(null);
    setFinalReport(null);
    setActiveTab('practice');
    setCurrentScreen('login');
  };

  useEffect(() => {
    setUnauthorizedHandler(() => {
      setCurrentUser(null);
      setActiveSession(null);
      setFinalReport(null);
      setActiveTab('practice');
      setCurrentScreen('login');
      Alert.alert('Sesi Berakhir', 'Silakan masuk kembali ke akun VOIC Anda.');
    });

    if (getAuthToken() && !currentUser) {
      apiService.getCurrentUser()
        .then((res) => {
          if (res?.success && res?.user) {
            setCurrentUser(res.user);
            setCurrentScreen('home');
          }
        })
        .catch(() => {});
    }
  }, []);

  return (
    <SafeAreaProvider>
      <View style={styles.container}>
        <StatusBar style="light" backgroundColor={colors.surface} />
        {currentScreen === 'login' && (
          <LoginScreen onLoginSuccess={handleLoginSuccess} />
        )}
        {currentScreen === 'home' && activeTab === 'practice' && (
          <HomeScreen
            user={currentUser}
            onStartPractice={handleStartPractice}
          />
        )}
        {currentScreen === 'home' && activeTab === 'history' && (
          <HistoryScreen
            onSelectSession={handleOpenHistorySession}
            onStartPracticeTab={() => setActiveTab('practice')}
          />
        )}
        {currentScreen === 'home' && activeTab === 'profile' && (
          <ProfileScreen user={currentUser} onLogout={handleLogout} />
        )}
        {currentScreen === 'practice' && activeSession && (
          <PracticeArenaScreen
            sessionData={activeSession}
            onFinishSession={handleFinishSession}
            onBack={handleBackToHome}
          />
        )}
        {currentScreen === 'report' && (
          <EvaluationReportScreen
            report={finalReport}
            role={activeSession?.role}
            onBackToHome={handleBackToHome}
          />
        )}
        {currentScreen === 'home' && (
          <MobileTabBar activeTab={activeTab} onSelectTab={setActiveTab} />
        )}
      </View>
    </SafeAreaProvider>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: colors.background,
  },
});
