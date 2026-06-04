import { Text, TextInput, View } from "react-native";
import { Ionicons } from "@expo/vector-icons";

interface Props {
  label: string;
  required?: boolean;
  value: string;
  onChangeText: (v: string) => void;
  placeholder?: string;
  multiline?: boolean;
  rows?: number;
  keyboardType?: "default" | "email-address" | "phone-pad" | "numeric";
  icon?: React.ComponentProps<typeof Ionicons>["name"];
  hint?: string;
}

export function FormField({
  label,
  required,
  value,
  onChangeText,
  placeholder,
  multiline,
  rows = 4,
  keyboardType = "default",
  icon,
  hint,
}: Props) {
  return (
    <View className="mb-4">
      <View className="flex-row items-center gap-2 mb-2">
        {icon ? <Ionicons name={icon} size={18} color="#414754" /> : null}
        <Text className="text-on-surface font-semibold">
          {label}
          {required ? <Text className="text-error"> *</Text> : null}
        </Text>
      </View>
      <TextInput
        value={value}
        onChangeText={onChangeText}
        placeholder={placeholder}
        placeholderTextColor="#c1c6d6"
        multiline={multiline}
        numberOfLines={multiline ? rows : undefined}
        keyboardType={keyboardType}
        className="bg-surface-container-lowest border border-outline-variant rounded-xl px-3 text-on-surface"
        style={
          multiline
            ? {
                textAlignVertical: "top",
                paddingTop: 12,
                paddingBottom: 12,
                minHeight: rows * 22,
              }
            : { height: 48 }
        }
      />
      {hint ? (
        <Text className="text-xs text-on-surface-variant mt-1">{hint}</Text>
      ) : null}
    </View>
  );
}
