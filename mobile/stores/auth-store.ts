import { create } from "zustand";
import { TOKEN_KEY } from "@/lib/api";
import { storage } from "@/lib/storage";
import { authService } from "@/lib/services";
import { registerPushToken, unregisterPushToken } from "@/lib/push";
import { primaryRole, type ApiUser, type UserRole } from "@/lib/types";

export type { UserRole };

/**
 * UI-facing user shape. The backend returns `roles[]`; we collapse it to a
 * single primary `role` for the existing screens. `unit` is optional because
 * the API's UserResource does not currently expose it.
 */
export interface User {
  id: number;
  name: string;
  email: string;
  phone?: string | null;
  role: UserRole;
  roles: string[];
  permissions: string[];
  unit?: { id: number; name: string } | null;
}

function mapUser(api: ApiUser): User {
  return {
    id: api.id,
    name: api.name,
    email: api.email,
    phone: api.phone ?? null,
    role: primaryRole(api.roles),
    roles: api.roles ?? [],
    permissions: api.permissions ?? [],
    unit: null,
  };
}

interface AuthState {
  user: User | null;
  token: string | null;
  status: "idle" | "loading" | "authenticated" | "unauthenticated";
  error: string | null;
  hydrate: () => Promise<void>;
  login: (email: string, password: string) => Promise<void>;
  logout: () => Promise<void>;
  refreshUser: () => Promise<void>;
  updateProfile: (data: {
    name?: string;
    phone?: string;
    current_password?: string;
    password?: string;
    password_confirmation?: string;
  }) => Promise<void>;
}

export const useAuthStore = create<AuthState>((set, get) => ({
  user: null,
  token: null,
  status: "idle",
  error: null,

  hydrate: async () => {
    set({ status: "loading" });
    const token = await storage.getItem(TOKEN_KEY);
    if (!token) {
      set({ status: "unauthenticated", token: null, user: null });
      return;
    }
    try {
      const apiUser = await authService.me();
      set({ token, user: mapUser(apiUser), status: "authenticated" });
      void registerPushToken();
    } catch {
      await storage.removeItem(TOKEN_KEY);
      set({ status: "unauthenticated", token: null, user: null });
    }
  },

  login: async (email, password) => {
    set({ status: "loading", error: null });
    try {
      const { user, token } = await authService.login(
        email.trim(),
        password
      );
      await storage.setItem(TOKEN_KEY, token);
      set({ token, user: mapUser(user), status: "authenticated" });
      void registerPushToken();
    } catch (err) {
      const message =
        err instanceof Error ? err.message : "Gagal masuk. Coba lagi.";
      set({ status: "unauthenticated", error: message });
      throw err;
    }
  },

  logout: async () => {
    await unregisterPushToken();
    try {
      await authService.logout();
    } catch {
      // ignore — clear local session regardless
    }
    await storage.removeItem(TOKEN_KEY);
    set({ token: null, user: null, status: "unauthenticated" });
  },

  refreshUser: async () => {
    try {
      const apiUser = await authService.me();
      set({ user: mapUser(apiUser) });
    } catch {
      // keep existing user on transient failure
    }
  },

  updateProfile: async (data) => {
    const updated = await authService.updateProfile(data);
    set({ user: { ...get().user, ...mapUser(updated) } });
  },
}));
