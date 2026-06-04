import { useEffect, useRef, useCallback } from "react";
import {
  View,
  Text,
  Image,
  StyleSheet,
  Animated,
  Dimensions,
  Easing,
} from "react-native";

const { width: SCREEN_WIDTH } = Dimensions.get("window");

// eslint-disable-next-line @typescript-eslint/no-require-imports
const APP_ICON = require("../assets/icons/app_icon.png");

interface AnimatedSplashProps {
  onFinish: () => void;
}

export default function AnimatedSplash({ onFinish }: AnimatedSplashProps) {
  const logoScale = useRef(new Animated.Value(0.3)).current;
  const logoOpacity = useRef(new Animated.Value(0)).current;

  const nameOpacity = useRef(new Animated.Value(0)).current;
  const nameTranslateY = useRef(new Animated.Value(24)).current;

  const taglineOpacity = useRef(new Animated.Value(0)).current;
  const taglineTranslateY = useRef(new Animated.Value(16)).current;

  const barWidth = useRef(new Animated.Value(0)).current;
  const shimmerX = useRef(new Animated.Value(-60)).current;

  const containerOpacity = useRef(new Animated.Value(1)).current;

  const finish = useCallback(() => onFinish(), [onFinish]);

  useEffect(() => {
    // 1. Logo entrance
    Animated.parallel([
      Animated.timing(logoOpacity, {
        toValue: 1,
        duration: 500,
        easing: Easing.out(Easing.cubic),
        useNativeDriver: true,
      }),
      Animated.spring(logoScale, {
        toValue: 1,
        damping: 12,
        stiffness: 100,
        mass: 0.8,
        useNativeDriver: true,
      }),
    ]).start();

    // 2. App name (delay 400ms)
    Animated.sequence([
      Animated.delay(400),
      Animated.parallel([
        Animated.timing(nameOpacity, {
          toValue: 1,
          duration: 500,
          easing: Easing.out(Easing.cubic),
          useNativeDriver: true,
        }),
        Animated.spring(nameTranslateY, {
          toValue: 0,
          damping: 14,
          stiffness: 90,
          useNativeDriver: true,
        }),
      ]),
    ]).start();

    // 3. Tagline (delay 700ms)
    Animated.sequence([
      Animated.delay(700),
      Animated.parallel([
        Animated.timing(taglineOpacity, {
          toValue: 1,
          duration: 500,
          easing: Easing.out(Easing.cubic),
          useNativeDriver: true,
        }),
        Animated.spring(taglineTranslateY, {
          toValue: 0,
          damping: 14,
          stiffness: 90,
          useNativeDriver: true,
        }),
      ]),
    ]).start();

    // 4. Progress bar (delay 900ms)
    Animated.sequence([
      Animated.delay(900),
      Animated.timing(barWidth, {
        toValue: 1,
        duration: 1600,
        easing: Easing.bezier(0.25, 0.1, 0.25, 1),
        useNativeDriver: false, // width animation can't use native driver
      }),
    ]).start();

    // 5. Shimmer loop
    const shimmerLoop = () => {
      shimmerX.setValue(-60);
      Animated.timing(shimmerX, {
        toValue: 180,
        duration: 1200,
        easing: Easing.linear,
        useNativeDriver: true,
      }).start(() => shimmerLoop());
    };
    const shimmerTimer = setTimeout(shimmerLoop, 900);

    // 6. Fade out & finish
    Animated.sequence([
      Animated.delay(2800),
      Animated.timing(containerOpacity, {
        toValue: 0,
        duration: 400,
        easing: Easing.in(Easing.cubic),
        useNativeDriver: true,
      }),
    ]).start(() => finish());

    return () => clearTimeout(shimmerTimer);
  }, []);

  const barWidthInterpolated = barWidth.interpolate({
    inputRange: [0, 1],
    outputRange: ["0%", "100%"],
  });

  return (
    <Animated.View style={[styles.container, { opacity: containerOpacity }]}>
      {/* Decoration circles */}
      <View style={styles.decorCircle1} />
      <View style={styles.decorCircle2} />
      <View style={styles.decorCircle3} />

      {/* Logo */}
      <Animated.View
        style={[
          styles.logoContainer,
          {
            opacity: logoOpacity,
            transform: [{ scale: logoScale }],
          },
        ]}
      >
        <View style={styles.logoShadow}>
          <Image source={APP_ICON} style={styles.logo} resizeMode="contain" />
        </View>
      </Animated.View>

      {/* App name */}
      <Animated.View
        style={{
          opacity: nameOpacity,
          transform: [{ translateY: nameTranslateY }],
        }}
      >
        <Text style={styles.appName}>ServiceGO</Text>
      </Animated.View>

      {/* Tagline */}
      <Animated.View
        style={{
          opacity: taglineOpacity,
          transform: [{ translateY: taglineTranslateY }],
        }}
      >
        <Text style={styles.tagline}>Smart Operational Management</Text>
      </Animated.View>

      {/* Progress bar */}
      <View style={styles.barTrack}>
        <Animated.View style={[styles.barFill, { width: barWidthInterpolated }]}>
          <Animated.View
            style={[
              styles.shimmer,
              { transform: [{ translateX: shimmerX }] },
            ]}
          />
        </Animated.View>
      </View>
    </Animated.View>
  );
}

const styles = StyleSheet.create({
  container: {
    position: "absolute",
    top: 0,
    left: 0,
    right: 0,
    bottom: 0,
    backgroundColor: "#f8f9fa",
    alignItems: "center",
    justifyContent: "center",
    zIndex: 999,
  },
  decorCircle1: {
    position: "absolute",
    width: 320,
    height: 320,
    borderRadius: 160,
    backgroundColor: "rgba(0, 91, 191, 0.06)",
    top: -60,
    right: -80,
  },
  decorCircle2: {
    position: "absolute",
    width: 240,
    height: 240,
    borderRadius: 120,
    backgroundColor: "rgba(0, 110, 44, 0.05)",
    bottom: 60,
    left: -60,
  },
  decorCircle3: {
    position: "absolute",
    width: 160,
    height: 160,
    borderRadius: 80,
    backgroundColor: "rgba(158, 67, 0, 0.04)",
    bottom: -40,
    right: 30,
  },
  logoContainer: {
    marginBottom: 24,
  },
  logoShadow: {
    shadowColor: "#005bbf",
    shadowOffset: { width: 0, height: 8 },
    shadowOpacity: 0.22,
    shadowRadius: 16,
    elevation: 12,
    borderRadius: 32,
  },
  logo: {
    width: 120,
    height: 120,
    borderRadius: 28,
  },
  appName: {
    fontSize: 32,
    fontWeight: "800",
    color: "#005bbf",
    letterSpacing: -0.5,
    textAlign: "center",
  },
  tagline: {
    fontSize: 15,
    fontWeight: "500",
    color: "#414754",
    marginTop: 6,
    letterSpacing: 0.2,
    textAlign: "center",
  },
  barTrack: {
    marginTop: 40,
    width: 180,
    height: 4,
    borderRadius: 2,
    backgroundColor: "rgba(0, 91, 191, 0.1)",
    overflow: "hidden",
  },
  barFill: {
    height: "100%",
    borderRadius: 2,
    backgroundColor: "#005bbf",
    overflow: "hidden",
  },
  shimmer: {
    position: "absolute",
    top: 0,
    bottom: 0,
    width: 60,
    backgroundColor: "rgba(255,255,255,0.35)",
  },
});
