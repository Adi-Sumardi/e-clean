import { useState } from "react";
import { Platform, Pressable, Text, View } from "react-native";
import { Ionicons } from "@expo/vector-icons";
import DateTimePicker from "@react-native-community/datetimepicker";

interface Props {
  label: string;
  required?: boolean;
  /** Value as "HH:MM" (24h). Empty string means unset. */
  value: string;
  onChange: (value: string) => void;
  icon?: React.ComponentProps<typeof Ionicons>["name"];
  placeholder?: string;
}

function parseTime(value: string): Date {
  const d = new Date();
  const m = /^(\d{1,2}):(\d{2})$/.exec(value);
  if (m) {
    d.setHours(Number(m[1]), Number(m[2]), 0, 0);
  }
  return d;
}

function fmt(d: Date): string {
  return `${String(d.getHours()).padStart(2, "0")}:${String(
    d.getMinutes()
  ).padStart(2, "0")}`;
}

export function FormTimeField({
  label,
  required,
  value,
  onChange,
  icon = "time-outline",
  placeholder = "Pilih jam",
}: Props) {
  const [show, setShow] = useState(false);

  const onPicked = (event: { type: string }, date?: Date) => {
    // Android fires once and closes; iOS stays open (spinner).
    if (Platform.OS === "android") setShow(false);
    if (event.type === "dismissed") return;
    if (date) onChange(fmt(date));
  };

  return (
    <View className="mb-4">
      <View className="flex-row items-center gap-2 mb-2">
        <Ionicons name={icon} size={18} color="#414754" />
        <Text className="text-on-surface font-semibold">
          {label}
          {required ? <Text className="text-error"> *</Text> : null}
        </Text>
      </View>

      <Pressable
        onPress={() => setShow(true)}
        className="flex-row items-center justify-between bg-surface-container-lowest border border-outline-variant rounded-xl px-3.5 h-12 active:opacity-80"
      >
        <Text
          className={value ? "text-on-surface font-medium text-base" : "text-on-surface-variant"}
        >
          {value || placeholder}
        </Text>
        <Ionicons name="chevron-down" size={18} color="#414754" />
      </Pressable>

      {show && (
        <DateTimePicker
          value={parseTime(value)}
          mode="time"
          is24Hour
          display={Platform.OS === "ios" ? "spinner" : "clock"}
          onChange={onPicked}
        />
      )}

      {Platform.OS === "ios" && show && (
        <Pressable
          onPress={() => setShow(false)}
          className="mt-2 self-end px-4 py-2 rounded-lg bg-primary"
        >
          <Text className="text-white font-semibold text-sm">Selesai</Text>
        </Pressable>
      )}
    </View>
  );
}
