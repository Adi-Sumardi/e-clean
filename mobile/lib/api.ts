import axios, { AxiosError, AxiosRequestConfig } from "axios";
import Constants from "expo-constants";
import { storage } from "./storage";

export const API_URL_KEY = "eclean.api.url";

export async function getApiUrl(): Promise<string> {
  const customUrl = await storage.getItem(API_URL_KEY);
  if (customUrl) return customUrl;
  return (
    process.env.EXPO_PUBLIC_API_URL ??
    (Constants.expoConfig?.extra as { apiUrl?: string } | undefined)?.apiUrl ??
    "https://css.kopkaryapi.id"
  );
}

export async function saveApiUrl(url: string): Promise<void> {
  if (url) {
    await storage.setItem(API_URL_KEY, url);
  } else {
    await storage.removeItem(API_URL_KEY);
  }
}

export const TOKEN_KEY = "eclean.auth.token";

/** Standard Laravel response envelope used by the E-Clean API. */
export interface ApiEnvelope<T> {
  success: boolean;
  message: string;
  data: T;
  errors?: Record<string, string[]>;
}

export const api = axios.create({
  // Initial fallback, request interceptor overrides this dynamically
  baseURL: "https://css.kopkaryapi.id/api/v1",
  timeout: 20000,
  headers: { Accept: "application/json" },
});

api.interceptors.request.use(async (config) => {
  const url = await getApiUrl();
  config.baseURL = `${url}/api/v1`;

  const token = await storage.getItem(TOKEN_KEY);
  if (token) config.headers.Authorization = `Bearer ${token}`;
  return config;
});

api.interceptors.response.use(
  (res) => res,
  async (err: AxiosError) => {
    if (err.response?.status === 401) {
      await storage.removeItem(TOKEN_KEY);
    }
    return Promise.reject(err);
  }
);

/** Error carrying a human-readable message + Laravel validation errors. */
export class ApiError extends Error {
  status?: number;
  errors?: Record<string, string[]>;
  constructor(
    message: string,
    status?: number,
    errors?: Record<string, string[]>
  ) {
    super(message);
    this.name = "ApiError";
    this.status = status;
    this.errors = errors;
  }
}

/** Normalize any thrown axios error into an ApiError with the API message. */
export function toApiError(err: unknown): ApiError {
  if (err instanceof ApiError) return err;
  const ax = err as AxiosError<ApiEnvelope<unknown>>;
  const data = ax.response?.data;
  const message =
    data?.message ??
    (ax.code === "ECONNABORTED"
      ? "Koneksi timeout. Periksa jaringan Anda."
      : ax.request
        ? "Tidak dapat terhubung ke server."
        : ax.message) ??
    "Terjadi kesalahan.";
  return new ApiError(message, ax.response?.status, data?.errors);
}

/** Perform a request and unwrap the `data` field of the envelope. */
export async function request<T>(config: AxiosRequestConfig): Promise<T> {
  try {
    const res = await api.request<ApiEnvelope<T>>(config);
    return res.data.data;
  } catch (err) {
    throw toApiError(err);
  }
}

/** Build multipart/form-data, expanding arrays into `field[]` entries. */
export function toFormData(
  fields: Record<string, unknown>
): FormData {
  const form = new FormData();
  for (const [key, value] of Object.entries(fields)) {
    if (value === undefined || value === null) continue;
    if (Array.isArray(value)) {
      value.forEach((item) => form.append(`${key}[]`, item as never));
    } else {
      form.append(key, value as never);
    }
  }
  return form;
}

/** Wrap a local image uri as a multipart file part for React Native. */
export function filePart(uri: string, name = "photo.jpg") {
  const ext = uri.split(".").pop()?.toLowerCase() ?? "jpg";
  const type = ext === "png" ? "image/png" : "image/jpeg";
  return { uri, name: `${name}.${ext}`, type } as unknown as Blob;
}
