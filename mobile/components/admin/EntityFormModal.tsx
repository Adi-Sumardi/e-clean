import { useEffect, useState } from "react";
import {
  ActivityIndicator,
  Modal,
  Pressable,
  ScrollView,
  Switch,
  Text,
  TextInput,
  View,
} from "react-native";
import { Ionicons } from "@expo/vector-icons";
import { FormSelect, type SelectOption } from "@/components/FormSelect";

export interface FieldDef {
  key: string;
  label: string;
  type: "text" | "textarea" | "number" | "switch" | "select" | "password";
  required?: boolean;
  placeholder?: string;
  options?: SelectOption[];
  keyboardType?: "default" | "email-address" | "phone-pad" | "numeric";
}

export type FormValues = Record<string, string | number | boolean | null>;

interface Props {
  visible: boolean;
  title: string;
  fields: FieldDef[];
  initialValues?: FormValues;
  submitting?: boolean;
  submitLabel?: string;
  onCancel: () => void;
  onSubmit: (values: FormValues) => void;
}

/**
 * Reusable create/edit modal driven by a field definition list. Used by the
 * unit / lokasi / users management screens.
 */
export function EntityFormModal({
  visible,
  title,
  fields,
  initialValues,
  submitting,
  submitLabel = "Simpan",
  onCancel,
  onSubmit,
}: Props) {
  const [values, setValues] = useState<FormValues>({});
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    if (visible) {
      const seed: FormValues = {};
      fields.forEach((f) => {
        seed[f.key] =
          initialValues?.[f.key] ?? (f.type === "switch" ? true : "");
      });
      setValues(seed);
      setError(null);
    }
  }, [visible]);

  const set = (key: string, v: FormValues[string]) =>
    setValues((prev) => ({ ...prev, [key]: v }));

  const handleSubmit = () => {
    for (const f of fields) {
      if (f.required) {
        const v = values[f.key];
        if (v === "" || v === null || v === undefined) {
          setError(`${f.label} wajib diisi.`);
          return;
        }
      }
    }
    setError(null);
    onSubmit(values);
  };

  return (
    <Modal
      visible={visible}
      transparent
      animationType="slide"
      onRequestClose={onCancel}
    >
      <View className="flex-1 bg-black/40 justify-end">
        <View className="bg-background rounded-t-3xl max-h-[88%]">
          <View className="flex-row items-center justify-between px-5 pt-5 pb-3 border-b border-outline-variant">
            <Text className="text-lg font-bold text-on-surface">{title}</Text>
            <Pressable onPress={onCancel} className="p-1">
              <Ionicons name="close" size={24} color="#414754" />
            </Pressable>
          </View>

          <ScrollView
            contentContainerStyle={{ padding: 20, paddingBottom: 8 }}
            keyboardShouldPersistTaps="handled"
          >
            {fields.map((f) => {
              const v = values[f.key];
              if (f.type === "switch") {
                return (
                  <View
                    key={f.key}
                    className="flex-row items-center justify-between py-3"
                  >
                    <Text className="text-on-surface font-semibold">
                      {f.label}
                    </Text>
                    <Switch
                      value={Boolean(v)}
                      onValueChange={(val) => set(f.key, val)}
                    />
                  </View>
                );
              }
              if (f.type === "select") {
                return (
                  <FormSelect
                    key={f.key}
                    label={f.label}
                    required={f.required}
                    value={(v as string | number) ?? null}
                    options={f.options ?? []}
                    onChange={(val) => set(f.key, val)}
                    placeholder={f.placeholder ?? `Pilih ${f.label}...`}
                  />
                );
              }
              return (
                <View key={f.key} className="mb-4">
                  <Text className="text-on-surface font-semibold mb-2">
                    {f.label}
                    {f.required ? <Text className="text-error"> *</Text> : null}
                  </Text>
                  <TextInput
                    value={v == null ? "" : String(v)}
                    onChangeText={(t) =>
                      set(f.key, f.type === "number" ? t.replace(/[^0-9]/g, "") : t)
                    }
                    placeholder={f.placeholder}
                    placeholderTextColor="#c1c6d6"
                    secureTextEntry={f.type === "password"}
                    autoCapitalize={
                      f.keyboardType === "email-address" ? "none" : "sentences"
                    }
                    keyboardType={
                      f.type === "number" ? "numeric" : f.keyboardType ?? "default"
                    }
                    multiline={f.type === "textarea"}
                    className="bg-surface border border-outline rounded-xl px-4 text-on-surface"
                    style={
                      f.type === "textarea"
                        ? { minHeight: 90, paddingTop: 12, textAlignVertical: "top" }
                        : { height: 48 }
                    }
                  />
                </View>
              );
            })}

            {error ? (
              <Text className="text-error text-sm mb-2">{error}</Text>
            ) : null}
          </ScrollView>

          <View className="flex-row gap-3 px-5 py-4 border-t border-outline-variant">
            <Pressable
              onPress={onCancel}
              className="flex-1 h-12 rounded-xl border border-outline items-center justify-center"
            >
              <Text className="text-on-surface font-bold">Batal</Text>
            </Pressable>
            <Pressable
              onPress={handleSubmit}
              disabled={submitting}
              className="flex-[2] h-12 rounded-xl bg-primary items-center justify-center flex-row gap-2"
              style={{ opacity: submitting ? 0.7 : 1 }}
            >
              {submitting ? (
                <ActivityIndicator color="#fff" />
              ) : (
                <Text className="text-on-primary font-bold">{submitLabel}</Text>
              )}
            </Pressable>
          </View>
        </View>
      </View>
    </Modal>
  );
}
