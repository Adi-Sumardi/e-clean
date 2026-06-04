# E-Clean Mobile

Mobile app for E-Cleaning Manager built with **Expo (React Native) + Expo Router + NativeWind + EAS Build**.

## Setup

```bash
cd mobile
npm install
cp .env.example .env  # set EXPO_PUBLIC_API_URL ke backend Laravel
npx expo start
```

For Android emulator the Laravel dev server URL is typically `http://10.0.2.2:8000`.
For physical devices use your machine LAN IP, e.g. `http://192.168.1.10:8000`.

## EAS Build

```bash
npm install -g eas-cli
eas login
eas build:configure
eas build --platform android --profile preview
```

## API integration

Base URL is `EXPO_PUBLIC_API_URL` + `/api/v1` (see `lib/api.ts`). All requests
go through a shared axios client that injects the Sanctum Bearer token and
unwraps the Laravel `{ success, message, data }` envelope.

- `lib/api.ts` — axios client, token interceptor, `request()` envelope unwrap,
  `toFormData` / `filePart` multipart helpers, `ApiError` normalization.
- `lib/types.ts` — TS mirrors of the backend API Resources.
- `lib/services.ts` — typed functions per resource (auth, lokasi, jadwal,
  activity-reports, penilaian, dashboard).
- `lib/hooks.ts` — React Query hooks (`useJadwalToday`, `useLokasi`,
  `useCreateActivityReport`, `useDashboard`, …).

## Wired to the real API ✅

- **Auth** — real `POST /v1/auth/login`, `GET /v1/auth/me` (hydrate),
  `POST /v1/auth/logout`, `PUT /v1/auth/profile` (`stores/auth-store.ts`).
- **Tugas (petugas)** — today's schedule from `GET /v1/jadwal/today`, with
  pull-to-refresh + loading/error states (`app/(tabs)/tugas.tsx`).
- **Dashboard petugas** — today's tasks/stats from `/v1/jadwal/today`.
- **Laporan kegiatan** — lokasi & jadwal dropdowns from the API; submit does a
  real multipart `POST /v1/activity-reports` with before/after photos.

API docs: the backend exposes **Laravel Scramble** at `GET /docs/api`
(OpenAPI JSON at `/docs/api.json`).

## Supervisor approval (wired) ✅

The Supervisor dashboard's **approval queue** is live: it pulls submitted
reports across all field domains (kebersihan / satpam / OB / toko), can be
filtered **per unit** via the unit pill row, and Approve/Reject call the real
API (`POST .../{id}/approve|reject`). See `usePendingApprovals`,
`useApproveReport`, `useRejectReport` in `lib/hooks.ts`.

Backend domains now available under `/api/v1`:
`satpam/{jadwal,laporan}`, `office-boy/{jadwal,laporan}`,
`toko/{jadwal,laporan}` (each with approve/reject), plus `units`.

## Field-staff report forms (wired) ✅

The satpam / office-boy / store report forms now submit to the real API
(`POST /v1/{satpam|office-boy|toko}/laporan`, multipart with photos) and pull
their location dropdown from `/v1/lokasi`. See `fieldService` /
`useCreateFieldLaporan` (`lib/hooks.ts`). The store report folds its cashier
summary into `catatan_stok` since the backend table tracks checklist + stock
note rather than financial columns.

## Belum diimplementasi (next iterations)

- Wire the remaining **admin** screens that still use sample data: penilaian,
  leaderboard, laporan-bulanan, lokasi/unit/users management.
- **Guest complaint** screens (`lapor-insiden`, dashboard banner) — no API yet.
  (The web guest-complaint page now shows who last cleaned the room + time.)
- Offline-first (SQLite queue + auto sync).
- Push notifications.
