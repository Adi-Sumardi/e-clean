import * as SecureStore from "expo-secure-store";
import { Platform } from "react-native";
import AsyncStorage from "@react-native-async-storage/async-storage";

const isSecureAvailable = Platform.OS === "ios" || Platform.OS === "android";

export const storage = {
  async getItem(key: string): Promise<string | null> {
    if (isSecureAvailable) return SecureStore.getItemAsync(key);
    return AsyncStorage.getItem(key);
  },
  async setItem(key: string, value: string): Promise<void> {
    if (isSecureAvailable) return SecureStore.setItemAsync(key, value);
    return AsyncStorage.setItem(key, value);
  },
  async removeItem(key: string): Promise<void> {
    if (isSecureAvailable) return SecureStore.deleteItemAsync(key);
    return AsyncStorage.removeItem(key);
  },
};
