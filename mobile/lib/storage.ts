import * as SecureStore from "expo-secure-store";
import { Platform } from "react-native";
import AsyncStorage from "@react-native-async-storage/async-storage";

const isSecureAvailable = Platform.OS === "ios" || Platform.OS === "android";

// Simple in-memory cache dictionary to avoid slow SecureStore calls on repeated reads.
const cache: Record<string, string | null> = {};
const cacheLoaded: Record<string, boolean> = {};

export const storage = {
  async getItem(key: string): Promise<string | null> {
    if (cacheLoaded[key]) {
      return cache[key];
    }
    let value: string | null = null;
    if (isSecureAvailable) {
      value = await SecureStore.getItemAsync(key);
    } else {
      value = await AsyncStorage.getItem(key);
    }
    cache[key] = value;
    cacheLoaded[key] = true;
    return value;
  },

  async setItem(key: string, value: string): Promise<void> {
    cache[key] = value;
    cacheLoaded[key] = true;
    if (isSecureAvailable) {
      await SecureStore.setItemAsync(key, value);
    } else {
      await AsyncStorage.setItem(key, value);
    }
  },

  async removeItem(key: string): Promise<void> {
    cache[key] = null;
    cacheLoaded[key] = true;
    if (isSecureAvailable) {
      await SecureStore.deleteItemAsync(key);
    } else {
      await AsyncStorage.removeItem(key);
    }
  },
};

