"use client";

import { QueryClient } from "@tanstack/react-query";
import { PersistQueryClientProvider } from "@tanstack/react-query-persist-client";
import { createSyncStoragePersister } from "@tanstack/query-sync-storage-persister";
import { useState } from "react";

/**
 * Provider React Query + persistensi offline.
 *
 * - Cache di-persist ke localStorage → data (jadwal, laporan, /me, dst.)
 *   bertahan lintas refresh dan TETAP tampil saat offline.
 * - networkMode "offlineFirst" → query memakai data cache lebih dulu dan tidak
 *   "pause" saat offline; submit laporan tetap jalan (masuk outbox).
 * - staleTime tinggi → navigasi balik terasa instan tanpa fetch ulang.
 */
export default function Providers({ children }: { children: React.ReactNode }) {
  const [client] = useState(
    () =>
      new QueryClient({
        defaultOptions: {
          queries: {
            networkMode: "offlineFirst",
            staleTime: 5 * 60 * 1000,
            gcTime: 24 * 60 * 60 * 1000,
            retry: 1,
            refetchOnWindowFocus: false,
          },
          mutations: { networkMode: "offlineFirst" },
        },
      }),
  );

  const [persister] = useState(() =>
    createSyncStoragePersister({
      storage: typeof window !== "undefined" ? window.localStorage : undefined,
      key: "kopkaryapi.rq",
    }),
  );

  return (
    <PersistQueryClientProvider
      client={client}
      persistOptions={{ persister, maxAge: 24 * 60 * 60 * 1000 }}
    >
      {children}
    </PersistQueryClientProvider>
  );
}
