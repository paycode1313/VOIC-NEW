import React, { useEffect, useState } from 'react';
import { View, Text, StyleSheet, TouchableOpacity, ActivityIndicator } from 'react-native';
import { CameraView, useCameraPermissions } from 'expo-camera';
import { Ionicons, Feather } from '@expo/vector-icons';
import { colors } from '../constants/theme';

export default function CameraPip({
  eyeContactRatio = 0.92,
  isSmiling = true,
  faceDetected = true,
  onToggleState,
}) {
  const [permission, requestPermission] = useCameraPermissions();
  const [isExpanded, setIsExpanded] = useState(false);

  // Auto request camera permission on mount
  useEffect(() => {
    (async () => {
      if (!permission) {
        await requestPermission();
      } else if (!permission.granted && permission.canAskAgain) {
        await requestPermission();
      }
    })();
  }, [permission]);

  const toggleSize = () => {
    setIsExpanded((prev) => !prev);
    if (onToggleState) onToggleState();
  };

  return (
    <View style={[styles.container, isExpanded && styles.containerExpanded]}>
      {/* Front Camera Video Feed / Fallback */}
      {!permission ? (
        <View style={styles.centerBox}>
          <ActivityIndicator size="small" color={colors.accentCyan} />
          <Text style={styles.permissionText}>Menyiapkan kamera...</Text>
        </View>
      ) : !permission.granted ? (
        <View style={styles.centerBox}>
          <Ionicons name="camera-reverse-outline" size={28} color={colors.accentCyan} />
          <Text style={styles.permissionText}>Izinkan Kamera Selfie</Text>
          <TouchableOpacity
            style={styles.permissionButton}
            onPress={requestPermission}
            activeOpacity={0.8}
          >
            <Text style={styles.permissionButtonText}>Buka Kamera</Text>
          </TouchableOpacity>
        </View>
      ) : (
        <View style={styles.cameraWrapper}>
          <CameraView
            style={styles.cameraView}
            facing="front"
            mirror={true}
          />
        </View>
      )}

      {/* Header Badge */}
      <View style={styles.header}>
        <View style={styles.statusIndicator}>
          <View
            style={[
              styles.dot,
              { backgroundColor: faceDetected && permission?.granted ? colors.success : colors.danger },
            ]}
          />
          <Text style={styles.headerText}>Kamera Anda</Text>
        </View>
        <TouchableOpacity onPress={toggleSize} hitSlop={{ top: 12, bottom: 12, left: 12, right: 12 }}>
          <Feather
            name={isExpanded ? 'minimize-2' : 'maximize-2'}
            size={12}
            color="#FFFFFF"
          />
        </TouchableOpacity>
      </View>

      {/* Live Telemetry Overlay */}
      <View style={styles.telemetryOverlay}>
        <View style={styles.metricRow}>
          <Ionicons name="eye" size={10} color={colors.accentCyan} />
          <Text style={styles.metricText}>
            Mata: {Math.round(eyeContactRatio * 100)}%
          </Text>
        </View>
        <View style={styles.metricRow}>
          <Ionicons
            name={isSmiling ? 'happy' : 'remove-circle-outline'}
            size={10}
            color={isSmiling ? colors.success : colors.textMuted}
          />
          <Text style={[styles.metricText, { color: isSmiling ? colors.success : colors.textSecondary }]}>
            {isSmiling ? 'Senyum' : 'Rileks'}
          </Text>
        </View>
      </View>
    </View>
  );
}

const styles = StyleSheet.create({
  container: {
    width: 120,
    height: 160,
    borderRadius: 16,
    backgroundColor: '#080F1B',
    borderWidth: 1.5,
    borderColor: colors.cardBorder,
    overflow: 'hidden',
    shadowColor: colors.primary,
    shadowOffset: { width: 0, height: 6 },
    shadowOpacity: 0.5,
    shadowRadius: 14,
    elevation: 10,
  },
  containerExpanded: {
    width: 170,
    height: 226,
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
    padding: 8,
    backgroundColor: '#080F1B',
  },
  permissionText: {
    fontSize: 9,
    color: colors.textSecondary,
    textAlign: 'center',
    marginTop: 6,
    marginBottom: 8,
    fontWeight: '600',
  },
  permissionButton: {
    paddingHorizontal: 10,
    paddingVertical: 4,
    borderRadius: 8,
    backgroundColor: colors.primary,
  },
  permissionButtonText: {
    fontSize: 10,
    fontWeight: 'bold',
    color: '#FFFFFF',
  },
  header: {
    position: 'absolute',
    top: 6,
    left: 6,
    right: 6,
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    zIndex: 10,
  },
  statusIndicator: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: 'rgba(7, 11, 20, 0.7)',
    paddingHorizontal: 6,
    paddingVertical: 2,
    borderRadius: 10,
  },
  dot: {
    width: 6,
    height: 6,
    borderRadius: 3,
    marginRight: 4,
  },
  headerText: {
    fontSize: 8.5,
    fontWeight: 'bold',
    color: '#FFFFFF',
  },
  telemetryOverlay: {
    position: 'absolute',
    bottom: 0,
    left: 0,
    right: 0,
    paddingHorizontal: 6,
    paddingVertical: 4,
    backgroundColor: 'rgba(7, 11, 20, 0.85)',
    borderTopWidth: 1,
    borderTopColor: colors.cardBorderSubtle,
    zIndex: 10,
  },
  metricRow: {
    flexDirection: 'row',
    alignItems: 'center',
    marginVertical: 1,
  },
  metricText: {
    fontSize: 9.5,
    fontWeight: 'bold',
    color: '#FFFFFF',
    marginLeft: 4,
  },
});
