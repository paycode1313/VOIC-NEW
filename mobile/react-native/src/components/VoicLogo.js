import React from 'react';
import Svg, { Circle, Defs, Line, LinearGradient, Path, Stop } from 'react-native-svg';

const emblemPath = 'M 86.0 46.5 A 4.5 4.5 0 0 0 81.5 42.0 L 81.5 33.0 A 5.0 5.0 0 0 0 71.5 33.0 L 71.5 42.0 A 4.5 4.5 0 0 0 67.0 46.5 L 59.75 46.5 A 4.5 4.5 0 0 0 55.25 42.0 L 55.25 25.75 A 5.25 5.25 0 0 0 44.75 25.75 L 44.75 42.0 A 4.5 4.5 0 0 0 40.25 46.5 L 33.0 46.5 A 4.5 4.5 0 0 0 28.5 42.0 L 28.5 37.5 A 5.0 5.0 0 0 0 18.5 37.5 L 18.5 62.5 A 5.0 5.0 0 0 0 28.5 62.5 L 28.5 58.0 A 4.5 4.5 0 0 0 33.0 53.5 L 40.25 53.5 A 4.5 4.5 0 0 0 44.75 58.0 L 44.75 74.25 A 5.25 5.25 0 0 0 55.25 74.25 L 55.25 58.0 A 4.5 4.5 0 0 0 59.75 53.5 L 67.0 53.5 A 4.5 4.5 0 0 0 71.5 58.0 L 71.5 67.0 A 5.0 5.0 0 0 0 81.5 67.0 L 81.5 58.0 A 4.5 4.5 0 0 0 86.0 53.5 Q 92.0 53.5 97.4 57.5 A 48.0 48.0 0 1 1 97.4 42.5 Q 92.0 46.5 86.0 46.5 Z';

export default function VoicLogo({ width = 112, height = 32, wordmarkColor = '#FFFFFF' }) {
  return (
    <Svg width={width} height={height} viewBox="0 0 355 100" fill="none" accessibilityLabel="VOIC AI">
      <Defs>
        <LinearGradient id="voicLogoGradient" x1="0" y1="0" x2="0" y2="100" gradientUnits="userSpaceOnUse">
          <Stop offset="0" stopColor="#256FE6" />
          <Stop offset="1" stopColor="#0E3260" />
        </LinearGradient>
      </Defs>
      <Path d={emblemPath} fill="url(#voicLogoGradient)" />
      <Path d="M 124,26.75 L 144,73.25 L 164,26.75" stroke={wordmarkColor} strokeWidth="11.5" strokeLinecap="round" strokeLinejoin="round" />
      <Circle cx="214" cy="50" r="23.25" stroke={wordmarkColor} strokeWidth="11.5" />
      <Line x1="268" y1="26.75" x2="268" y2="73.25" stroke={wordmarkColor} strokeWidth="11.5" strokeLinecap="round" />
      <Path d="M 341.8,35.05 A 23.25,23.25 0 1 0 341.8,64.95" stroke={wordmarkColor} strokeWidth="11.5" strokeLinecap="round" />
    </Svg>
  );
}