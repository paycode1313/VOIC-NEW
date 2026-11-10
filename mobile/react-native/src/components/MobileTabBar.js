import React from 'react';
import { Text, TouchableOpacity, View, StyleSheet } from 'react-native';
import { Feather } from '@expo/vector-icons';
import { useSafeAreaInsets } from 'react-native-safe-area-context';
import { colors } from '../constants/theme';

const tabs = [
  { key: 'practice', label: 'Latihan', icon: 'mic' },
  { key: 'history', label: 'Riwayat', icon: 'clock' },
  { key: 'profile', label: 'Profil', icon: 'user' },
];

export default function MobileTabBar({ activeTab, onSelectTab }) {
  const insets = useSafeAreaInsets();

  return (
    <View style={[styles.container, { paddingBottom: Math.max(insets.bottom, 8) }]}>
      {tabs.map((tab) => {
        const isActive = activeTab === tab.key;

        return (
          <TouchableOpacity
            key={tab.key}
            accessibilityRole="tab"
            accessibilityState={{ selected: isActive }}
            accessibilityLabel={tab.label}
            onPress={() => onSelectTab(tab.key)}
            style={styles.tab}
            activeOpacity={0.75}
          >
            <Feather name={tab.icon} size={19} color={isActive ? colors.primaryLight : colors.textMuted} />
            <Text style={[styles.label, isActive && styles.activeLabel]}>{tab.label}</Text>
          </TouchableOpacity>
        );
      })}
    </View>
  );
}

const styles = StyleSheet.create({
  container: {
    minHeight: 66,
    flexDirection: 'row',
    backgroundColor: colors.surface,
    borderTopWidth: 1,
    borderTopColor: colors.cardBorderSubtle,
    paddingTop: 9,
  },
  tab: {
    flex: 1,
    minHeight: 48,
    alignItems: 'center',
    justifyContent: 'center',
    gap: 4,
  },
  label: {
    color: colors.textMuted,
    fontSize: 11,
    fontWeight: '600',
  },
  activeLabel: {
    color: colors.primaryLight,
  },
});