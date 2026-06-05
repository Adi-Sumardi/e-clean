import { useMemo, useState } from "react";
import {
  Modal,
  Pressable,
  ScrollView,
  Text,
  TextInput,
  View,
} from "react-native";
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
  disabled?: boolean;
  /** Show a search box when there are many options. */
  searchable?: boolean;
}

export function FormSelect({
  label,
  required,
  value,
  placeholder = "Pilih...",
  options,
  onChange,
  icon,
  disabled,
  searchable,
}: Props) {
  const [open, setOpen] = useState(false);
  const [query, setQuery] = useState("");

  const selected = options.find((o) => o.value === value);
  const showSearch = searchable ?? options.length > 8;

  const filtered = useMemo(() => {
    const q = query.trim().toLowerCase();
    if (!q) return options;
    return options.filter((o) => o.label.toLowerCase().includes(q));
  }, [query, options]);

  const close = () => {
    setOpen(false);
    setQuery("");
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
        onPress={() => !disabled && setOpen(true)}
        className={`flex-row items-center justify-between border rounded-xl px-3.5 h-12 ${
          disabled
            ? "bg-surface-variant/40 border-outline-variant"
            : "bg-surface-container-lowest border-outline-variant active:opacity-80"
        }`}
      >
        <Text
          className={`flex-1 ${selected ? "text-on-surface font-medium" : "text-on-surface-variant"}`}
          numberOfLines={1}
        >
          {selected ? selected.label : placeholder}
        </Text>
        <Ionicons
          name="chevron-down"
          size={18}
          color={disabled ? "#aeb3c2" : "#414754"}
        />
      </Pressable>

      <Modal
        visible={open}
        transparent
        animationType="slide"
        onRequestClose={close}
        statusBarTranslucent
      >
        <Pressable
          onPress={close}
          className="flex-1 bg-black/40 justify-end"
        >
          <Pressable
            onPress={(e) => e.stopPropagation()}
            className="bg-surface rounded-t-3xl max-h-[75%] pb-6"
            style={{ elevation: 24 }}
          >
            {/* grab handle */}
            <View className="items-center pt-3 pb-1">
              <View className="w-10 h-1.5 rounded-full bg-outline-variant" />
            </View>

            {/* header */}
            <View className="flex-row items-center justify-between px-5 pt-1 pb-3">
              <View className="flex-row items-center gap-2">
                {icon ? <Ionicons name={icon} size={18} color="#0a5fd6" /> : null}
                <Text className="text-on-surface font-bold text-base">
                  {label}
                </Text>
              </View>
              <Pressable
                onPress={close}
                hitSlop={8}
                className="w-8 h-8 rounded-full bg-surface-variant items-center justify-center"
              >
                <Ionicons name="close" size={18} color="#414754" />
              </Pressable>
            </View>

            {showSearch && (
              <View className="mx-5 mb-2 flex-row items-center gap-2 bg-surface-container-lowest border border-outline-variant rounded-xl px-3 h-11">
                <Ionicons name="search" size={16} color="#5a6072" />
                <TextInput
                  value={query}
                  onChangeText={setQuery}
                  placeholder="Cari..."
                  placeholderTextColor="#8a90a0"
                  className="flex-1 text-on-surface"
                  autoCorrect={false}
                />
              </View>
            )}

            <ScrollView
              keyboardShouldPersistTaps="handled"
              contentContainerStyle={{ paddingHorizontal: 12, paddingBottom: 8 }}
            >
              {filtered.length === 0 ? (
                <Text className="text-on-surface-variant text-center py-8">
                  Tidak ada pilihan.
                </Text>
              ) : (
                filtered.map((opt) => {
                  const active = opt.value === value;
                  return (
                    <Pressable
                      key={String(opt.value)}
                      onPress={() => {
                        onChange(opt.value);
                        close();
                      }}
                      className={`flex-row items-center justify-between px-4 py-3.5 rounded-xl mb-1 active:opacity-80 ${
                        active ? "bg-primary/10" : ""
                      }`}
                    >
                      <Text
                        className={`flex-1 ${active ? "text-primary font-bold" : "text-on-surface"}`}
                        numberOfLines={2}
                      >
                        {opt.label}
                      </Text>
                      {active && (
                        <Ionicons
                          name="checkmark-circle"
                          size={20}
                          color="#0a5fd6"
                        />
                      )}
                    </Pressable>
                  );
                })
              )}
            </ScrollView>
          </Pressable>
        </Pressable>
      </Modal>
    </View>
  );
}
