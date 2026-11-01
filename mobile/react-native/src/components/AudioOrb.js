import React, { useEffect, useRef } from 'react';
import { View, StyleSheet, Animated } from 'react-native';
import { LinearGradient } from 'expo-linear-gradient';
import { MaterialCommunityIcons } from '@expo/vector-icons';
import { colors } from '../constants/theme';

export default function AudioOrb({ isSpeaking = false, isListening = false, isThinking = false, size = 160 }) {
  const pulseAnim = useRef(new Animated.Value(1)).current;
  const rotateAnim = useRef(new Animated.Value(0)).current;

  useEffect(() => {
    let animation;
    if (isSpeaking || isListening || isThinking) {
      animation = Animated.loop(
        Animated.sequence([
          Animated.timing(pulseAnim, {
            toValue: 1.15,
            duration: isSpeaking ? 650 : 800,
            useNativeDriver: true,
          }),
          Animated.timing(pulseAnim, {
            toValue: 0.95,
            duration: isSpeaking ? 650 : 800,
            useNativeDriver: true,
          }),
        ])
      );
      animation.start();
    } else {
      Animated.timing(pulseAnim, {
        toValue: 1,
        duration: 400,
        useNativeDriver: true,
      }).start();
    }

    return () => {
      if (animation) animation.stop();
    };
  }, [isSpeaking, isListening, isThinking]);

  // Determine active visual theme matching Web 3D Audio Orb
  let orbGradient = colors.orbGradients;
  let activeGlowColor = 'rgba(37, 99, 235, 0.3)';
  let ringBorderColor = colors.primary;
  let iconName = 'waveform';

  if (isSpeaking) {
    orbGradient = colors.orbSpeaking;
    activeGlowColor = 'rgba(37, 99, 235, 0.6)';
    ringBorderColor = colors.accentCyan;
    iconName = 'volume-high';
  } else if (isListening) {
    orbGradient = colors.orbListening;
    activeGlowColor = 'rgba(16, 185, 129, 0.55)';
    ringBorderColor = colors.success;
    iconName = 'microphone';
  } else if (isThinking) {
    orbGradient = colors.orbThinking;
    activeGlowColor = 'rgba(245, 158, 11, 0.55)';
    ringBorderColor = colors.warning;
    iconName = 'brain';
  }

  return (
    <View style={[styles.container, { width: size * 1.4, height: size * 1.4 }]}>
      {/* Outer Atmospheric Glow */}
      <Animated.View
        style={[
          styles.glowCircle,
          {
            width: size * 1.35,
            height: size * 1.35,
            borderRadius: (size * 1.35) / 2,
            backgroundColor: activeGlowColor,
            transform: [{ scale: pulseAnim }],
          },
        ]}
      />

      {/* Futuristic Middle Ring */}
      <Animated.View
        style={[
          styles.ringCircle,
          {
            width: size * 1.16,
            height: size * 1.16,
            borderRadius: (size * 1.16) / 2,
            borderColor: ringBorderColor,
            opacity: 0.5,
            transform: [{ scale: pulseAnim }],
          },
        ]}
      />

      {/* Core 3D Audio Orb */}
      <Animated.View
        style={[
          styles.coreOrb,
          {
            width: size,
            height: size,
            borderRadius: size / 2,
            transform: [{ scale: pulseAnim }],
          },
        ]}
      >
        <LinearGradient
          colors={orbGradient}
          start={{ x: 0, y: 0 }}
          end={{ x: 1, y: 1 }}
          style={styles.gradientFill}
        >
          <MaterialCommunityIcons
            name={iconName}
            size={size * 0.36}
            color="#FFFFFF"
          />
        </LinearGradient>
      </Animated.View>
    </View>
  );
}

const styles = StyleSheet.create({
  container: {
    justifyContent: 'center',
    alignItems: 'center',
  },
  glowCircle: {
    position: 'absolute',
  },
  ringCircle: {
    position: 'absolute',
    borderWidth: 1.5,
  },
  coreOrb: {
    overflow: 'hidden',
    shadowColor: colors.primary,
    shadowOffset: { width: 0, height: 10 },
    shadowOpacity: 0.7,
    shadowRadius: 28,
    elevation: 16,
  },
  gradientFill: {
    flex: 1,
    justifyContent: 'center',
    alignItems: 'center',
  },
});
