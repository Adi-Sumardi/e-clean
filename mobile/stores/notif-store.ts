import { create } from "zustand";
import { storage } from "@/lib/storage";

const KEY = "eclean.notif.seen";

interface NotifState {
  /** Map of notification id -> seen. */
  seen: Record<string, true>;
  hydrate: () => Promise<void>;
  /** Mark the given notification ids as seen (read). */
  markSeen: (ids: string[]) => void;
  /** Count unread among the given ids. */
  unreadCount: (ids: string[]) => number;
}

export const useNotifStore = create<NotifState>((set, get) => ({
  seen: {},
  hydrate: async () => {
    try {
      const raw = await storage.getItem(KEY);
      if (raw) set({ seen: JSON.parse(raw) });
    } catch {
      // ignore corrupt cache
    }
  },
  markSeen: (ids) => {
    const seen = { ...get().seen };
    let changed = false;
    for (const id of ids) {
      if (!seen[id]) {
        seen[id] = true;
        changed = true;
      }
    }
    if (changed) {
      set({ seen });
      storage.setItem(KEY, JSON.stringify(seen)).catch(() => {});
    }
  },
  unreadCount: (ids) => {
    const { seen } = get();
    return ids.reduce((n, id) => (seen[id] ? n : n + 1), 0);
  },
}));
