import { Alert, Image, Pressable, Text, View } from "react-native";
import * as ImagePicker from "expo-image-picker";
import { Ionicons } from "@expo/vector-icons";

export interface PhotoItem {
  uri: string;
}

interface Props {
  label: string;
  required?: boolean;
  photos: PhotoItem[];
  onChange: (photos: PhotoItem[]) => void;
  max?: number;
  thumbSize?: "sm" | "md" | "lg";
  hint?: string;
}

const SIZE_CLASS: Record<NonNullable<Props["thumbSize"]>, string> = {
  sm: "w-20 h-20",
  md: "w-24 h-24",
  lg: "w-32 h-32",
};

export function PhotoUpload({
  label,
  required,
  photos,
  onChange,
  max = 5,
  thumbSize = "md",
  hint,
}: Props) {
  const thumb = SIZE_CLASS[thumbSize];

  const pickFromCamera = async () => {
    try {
      const perm = await ImagePicker.requestCameraPermissionsAsync();
      if (!perm.granted) {
        Alert.alert(
          "Izin Kamera Diperlukan",
          "Aktifkan izin kamera di Pengaturan aplikasi untuk mengambil foto."
        );
        return;
      }
      const result = await ImagePicker.launchCameraAsync({
        quality: 0.6,
        allowsEditing: false,
      });
      if (!result.canceled && result.assets?.length) {
        onChange([...photos, ...result.assets.map((a) => ({ uri: a.uri }))]);
      }
    } catch (e) {
      Alert.alert(
        "Gagal Membuka Kamera",
        e instanceof Error ? e.message : "Terjadi kesalahan saat membuka kamera."
      );
    }
  };

  const pickFromGallery = async () => {
    try {
      const perm = await ImagePicker.requestMediaLibraryPermissionsAsync();
      if (!perm.granted) {
        Alert.alert(
          "Izin Galeri Diperlukan",
          "Aktifkan izin galeri/foto di Pengaturan aplikasi untuk memilih foto."
        );
        return;
      }
      const result = await ImagePicker.launchImageLibraryAsync({
        mediaTypes: ["images"],
        quality: 0.6,
        allowsMultipleSelection: max > 1,
        selectionLimit: Math.max(1, max - photos.length),
      });
      if (!result.canceled && result.assets?.length) {
        onChange([...photos, ...result.assets.map((a) => ({ uri: a.uri }))]);
      }
    } catch (e) {
      Alert.alert(
        "Gagal Membuka Galeri",
        e instanceof Error ? e.message : "Terjadi kesalahan saat membuka galeri."
      );
    }
  };

  const remove = (idx: number) => {
    onChange(photos.filter((_, i) => i !== idx));
  };

  return (
    <View className="mb-4">
      <View className="flex-row items-center gap-2 mb-2">
        <Ionicons name="image-outline" size={18} color="#414754" />
        <Text className="text-on-surface font-semibold">
          {label}
          {required ? <Text className="text-error"> *</Text> : null}
        </Text>
        <Text className="text-on-surface-variant text-xs">
          (maks {max})
        </Text>
      </View>

      <View className="flex-row flex-wrap gap-3">
        {photos.map((p, idx) => (
          <View
            key={idx}
            className={`${thumb} rounded-xl overflow-hidden border border-outline-variant relative`}
          >
            <Image source={{ uri: p.uri }} className="w-full h-full" />
            <Pressable
              onPress={() => remove(idx)}
              className="absolute top-1 right-1 w-7 h-7 rounded-full bg-black/70 items-center justify-center"
            >
              <Ionicons name="close" size={16} color="#ffffff" />
            </Pressable>
          </View>
        ))}
        {photos.length < max && (
          <Pressable
            onPress={pickFromCamera}
            className={`${thumb} rounded-xl border-2 border-dashed border-outline-variant bg-surface-container-lowest items-center justify-center`}
          >
            <Ionicons name="camera" size={28} color="#005bbf" />
            <Text className="text-xs text-on-surface-variant mt-1">Kamera</Text>
          </Pressable>
        )}
      </View>

      {photos.length < max && (
        <Pressable
          onPress={pickFromGallery}
          className="mt-3 self-start flex-row items-center gap-2 px-4 py-2 rounded-xl border border-outline-variant bg-surface-container-lowest"
        >
          <Ionicons name="images-outline" size={16} color="#005bbf" />
          <Text className="text-primary font-semibold text-sm">
            Pilih dari galeri
          </Text>
        </Pressable>
      )}

      {hint ? (
        <Text className="text-xs text-on-surface-variant mt-2">{hint}</Text>
      ) : null}
    </View>
  );
}
