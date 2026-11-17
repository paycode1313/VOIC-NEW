import React, { useEffect, useState } from 'react';
import { View, Text, StyleSheet, TouchableOpacity, ActivityIndicator } from 'react-native';
import { CameraView, useCameraPermissions } from 'expo-camera';
import { Ionicons, Feather } from '@expo/vector-icons';
import { colors } from '../constants/theme';

export default function CameraPip({ cameraEnabled = true }) {
  const [permission, requestPermission] = useCameraPermissions();
  const [isExpanded, setIsExpanded] = useState(false);

  useEffect(() => {
    if (!cameraEnabled) return;
    (async () => {
      if (!permission) {
        await requestPermission();
      } else if (!permission.granted && permission.canAskAgain) {
        await requestPermission();
      }
    })();
  }, [permission, cameraEnabled]);

  if (!cameraEnabled) {
    return (
      <View style={styles.offContainer}>
        <Feather name="video-off" size={14} color={colors.textMuted} />
        <Text style={styles.offText}>Kamera Off</Text>
      </View>
    );
  }

  return (
    <View style={[styles.container, isExpanded && styles.containerExpanded]}>
      {!permission ? (
        <View style={styles.centerBox}>
          <ActivityIndicator size="small" color={colors.accentCyan} />
        </View>
      ) : !permission.granted ? (
        <TouchableOpacity style={styles.centerBox} onPress={requestPermission} activeOpacity={0.8}>
          <Ionicons name="camera-outline" size={20} color={colors.accentCyan} />
          <Text style={styles.permissionText}>Izinkan Kamera</Text>
        </TouchableOpacity>
      ) : (
        <View style={styles.cameraWrapper}>
          <CameraView style={styles.cameraView} facing="front" mirror={true} />
        </View>
      )}

      {/* Top badge + expand toggle */}
      <View style={styles.header}>
        <View style={styles.liveDotBadge}>
          <View style={[styles.dot, { backgroundColor: permission?.granted ? colors.success : colors.warning }]} />
          <Text style={styles.headerText}>Anda</Text>
        </View>
        <TouchableOpacity
          onPress={() => setIsExpanded((prev) => !prev)}
          hitSlop={{ top: 10, bottom: 10, left: 10, right: 10 }}
          style={styles.expandBtn}
        >
          <Feather name={isExpanded ? 'minimize-2' : 'maximize-2'} size={10} color="#FFFFFF" />
        </TouchableOpacity>
      </View>

      <View style={styles.telemetryOverlay}>
        <Feather name="video" size={10} color={colors.accentCyan} />
        <Text style={styles.metricText}>
          {permission?.granted ? 'Preview Aktif' : 'Menunggu Izin'}
        </Text>
      </View>
    </View>
  );
}

const styles = StyleSheet.create({
  container: {
    width: 88,
    height: 118,
    borderRadius: 14,
    backgroundColor: '#080F1B',
    borderWidth: 1.5,
    borderColor: 'rgba(56, 189, 248, 0.45)',
    overflow: 'hidden',
  },
  containerExpanded: {
    width: 120,
    height: 160,
  },
  offContainer: {
    paddingHorizontal: 10,
    paddingVertical: 7,
    borderRadius: 12,
    backgroundColor: 'rgba(8, 15, 27, 0.8)',
    borderWidth: 1,
    borderColor: 'rgba(255, 255, 255, 0.14)',
    flexDirection: 'row',
    alignItems: 'center',
    gap: 5,
  },
  offText: {
    fontSize: 10,
    fontWeight: '700',
    color: colors.textMuted,
  },
  cameraWrapper: {
    ...StyleSheet.absoluteFillObject,
    backgroundColor: '#080F1B',
  },
  cameraView: {
    width: '100%',
    height: '100%',
  },
  centerBox: {
    flex: 1,
    justifyContent: 'center',
    alignItems: 'center',
    padding: 6,
    backgroundColor: '#080F1B',
  },
  permissionText: {
    fontSize: 9.5,
    color: colors.textSecondary,
    textAlign: 'center',
    marginTop: 5,
    fontWeight: '700',
  },
  header: {
    position: 'absolute',
    top: 5,
    left: 5,
    right: 5,
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    zIndex: 10,
  },
  liveDotBadge: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: 'rgba(7, 11, 20, 0.72)',
    paddingHorizontal: 5,
    paddingVertical: 2,
    borderRadius: 8,
  },
  dot: {
    width: 5,
    height: 5,
    borderRadius: 2.5,
    marginRight: 4,
  },
  headerText: {
    fontSize: 8.5,
    fontWeight: '700',
    color: '#FFFFFF',
  },
  expandBtn: {
    width: 18,
    height: 18,
    borderRadius: 9,
    backgroundColor: 'rgba(7, 11, 20, 0.7)',
    alignItems: 'center',
    justifyContent: 'center',
  },
  telemetryOverlay: {
    position: 'absolute',
    bottom: 0,
    left: 0,
    right: 0,
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    gap: 4,
    paddingVertical: 3.5,
    paddingHorizontal: 4,
    backgroundColor: 'rgba(7, 11, 20, 0.82)',
    zIndex: 10,
  },
  metricText: {
    fontSize: 8.5,
    fontWeight: '700',
    color: '#FFFFFF',
  },
});
