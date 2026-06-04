import { Platform } from "react-native";
import Constants, { ExecutionEnvironment } from "expo-constants";
import * as Device from "expo-device";
import { authService } from "./services";

/**
 * Lazy-load expo-notifications ONLY when a function is called,
 * not at module-init time. This prevents _toString crashes in Expo Go SDK 53+.
 */
type NotificationsModule = typeof import("expo-notifications");

let _notifications: NotificationsModule | null | undefined; // undefined = not yet loaded

function getNotifications(): NotificationsModule | null {
  if (_notifications !== undefined) return _notifications;

  try {
    // eslint-disable-next-line @typescript-eslint/no-require-imports
    _notifications = require("expo-notifications") as NotificationsModule;

    // Set the foreground handler. In Expo Go SDK 53+ this may log a warning
    // about remote notifications being removed — that's OK, local notifications
    // (scheduleNotificationAsync) still work.
    try {
      _notifications.setNotificationHandler({
        handleNotification: async () => ({
          shouldShowBanner: true,
          shouldShowList: true,
          shouldPlaySound: true,
          shouldSetBadge: false,
        }),
      });
    } catch {
      console.warn("[Push] setNotificationHandler failed (Expo Go?), local notifications may still work");
    }
  } catch {
    console.warn("[Push] expo-notifications not available");
    _notifications = null;
  }
  return _notifications;
}

async function ensureAndroidChannel() {
  const Notifications = getNotifications();
  if (Platform.OS === "android" && Notifications) {
    try {
      await Notifications.setNotificationChannelAsync("default", {
        name: "default",
        importance: Notifications.AndroidImportance.HIGH,
        vibrationPattern: [0, 250, 250, 250],
      });
    } catch {
      console.warn("[Push] Failed to create Android notification channel");
    }
  }
}

/**
 * Resolve the EAS / Expo project ID from config.
 */
function getProjectId(): string | undefined {
  const easProjectId =
    (Constants.expoConfig?.extra as { eas?: { projectId?: string } } | undefined)
      ?.eas?.projectId;
  if (easProjectId) return easProjectId;
  if (Constants.easConfig?.projectId) return Constants.easConfig.projectId;
  return undefined;
}

/**
 * Request permission and resolve the Expo push token for this device.
 * In __DEV__ mode, emulators are allowed so local notification testing works.
 */
export async function getExpoPushToken(): Promise<string | null> {
  const Notifications = getNotifications();
  if (!Notifications) return null;

  try {
    // Expo Go SDK 53+ removed remote push notification support, calling getExpoPushTokenAsync will crash.
    const isExpoGo = Constants.executionEnvironment === ExecutionEnvironment.StoreClient;
    if (isExpoGo) {
      console.warn("[Push] Remote push notifications are not supported in Expo Go. Skipping token registration.");
      return null;
    }

    if (!Device.isDevice && !__DEV__) return null;

    await ensureAndroidChannel();

    const existing = await Notifications.getPermissionsAsync();
    let status = existing.status;
    if (status !== "granted") {
      const req = await Notifications.requestPermissionsAsync();
      status = req.status;
    }
    if (status !== "granted") {
      console.warn("[Push] Notification permission not granted");
      return null;
    }

    const projectId = getProjectId();
    if (!projectId) {
      console.warn("[Push] No projectId found — push tokens require `eas build`.");
    }

    const token = await Notifications.getExpoPushTokenAsync(
      projectId ? { projectId } : undefined
    );
    return token.data;
  } catch (err) {
    console.warn("[Push] Failed to get push token:", err);
    return null;
  }
}

/**
 * Register this device's push token with the backend (best-effort).
 */
export async function registerPushToken(): Promise<void> {
  const token = await getExpoPushToken();
  if (!token) return;
  try {
    await authService.registerPushToken(token);
  } catch {
    // ignore — will retry on next login/hydrate
  }
}

/**
 * Clear the device token on the backend (best-effort, on logout).
 */
export async function unregisterPushToken(): Promise<void> {
  try {
    await authService.unregisterPushToken();
  } catch {
    // ignore
  }
}

/**
 * Schedule a local test notification (dev only).
 */
export async function sendTestNotification(): Promise<void> {
  const Notifications = getNotifications();
  if (!Notifications) {
    console.warn("[Push] Notifications not available for testing");
    return;
  }

  await ensureAndroidChannel();

  await Notifications.scheduleNotificationAsync({
    content: {
      title: "🔔 ServiceGO Test",
      body: "Push notification is working!",
      data: { type: "test" },
    },
    trigger: {
      type: Notifications.SchedulableTriggerInputTypes.TIME_INTERVAL,
      seconds: 2,
    },
  });
}
