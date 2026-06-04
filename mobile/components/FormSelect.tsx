import { Alert, Pressable, Text, View } from "react-native";
import { Ionicons } from "@expo/vector-icons";

export interface SelectOption {
  value: string | number;
  label: string;
}

interface Props {
  label: string;
  required?: boolean;
  value?: string | number | null;
  placeholder?: string;
  options: SelectOption[];
  onChange: (value: string | number) => void;
  icon?: React.ComponentProps<typeof Ionicons>["name"];
}

export function FormSelect({
  label,
  required,
  value,
  placeholder = "Pilih...",
  options,
  onChange,
  icon,
}: Props) {
  const selected = options.find((o) => o.value === value);

  const openPicker = () => {
    Alert.alert(label, undefined, [
      ...options.map((opt) => ({
        text: opt.label,
        onPress: () => onChange(opt.value),
      })),
      { text: "Batal", style: "cancel" as const },
    ]);
  };

  return (
    <View className="mb-4">
      <View className="flex-row items-center gap-2 mb-2">
        {icon ? <Ionicons name={icon} size={18} color="#414754" /> : null}
        <Text className="text-on-surface font-semibold">
          {label}
          {required ? <Text className="text-error"> *</Text> : null}
        </Text>
      </View>
      <Pressable
        onPress={openPicker}
        className="flex-row items-center justify-between bg-surface-container-lowest border border-outline-variant rounded-xl px-3 h-12 active:opacity-80"
      >
        <Text
          className={selected ? "text-on-surface" : "text-on-surface-variant"}
        >
          {selected ? selected.label : placeholder}
        </Text>
        <Ionicons name="chevron-down" size={18} color="#414754" />
      </Pressable>
    </View>
  );
}
