import React, { useState } from 'react';
import { StatusBar } from 'expo-status-bar';
import { SafeAreaProvider } from 'react-native-safe-area-context';
import { View, StyleSheet } from 'react-native';
import LoginScreen from './src/screens/LoginScreen';
import HomeScreen from './src/screens/HomeScreen';
import PracticeArenaScreen from './src/screens/PracticeArenaScreen';
import EvaluationReportScreen from './src/screens/EvaluationReportScreen';
import { colors } from './src/constants/theme';

export default function App() {
  const [currentScreen, setCurrentScreen] = useState('login'); // 'login' | 'home' | 'practice' | 'report'
  const [currentUser, setCurrentUser] = useState(null);
  const [activeSession, setActiveSession] = useState(null);
  const [finalReport, setFinalReport] = useState(null);

  const handleLoginSuccess = (user) => {
    setCurrentUser(user);
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

  const handleBackToHome = () => {
    setActiveSession(null);
    setFinalReport(null);
    setCurrentScreen('home');
  };

  const handleLogout = () => {
    setCurrentUser(null);
    setActiveSession(null);
    setFinalReport(null);
    setCurrentScreen('login');
  };

  return (
    <SafeAreaProvider>
      <View style={styles.container}>
        <StatusBar style="light" backgroundColor={colors.surface} />
        {currentScreen === 'login' && (
          <LoginScreen onLoginSuccess={handleLoginSuccess} />
        )}
        {currentScreen === 'home' && (
          <HomeScreen
            user={currentUser}
            onStartPractice={handleStartPractice}
            onLogout={handleLogout}
          />
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
