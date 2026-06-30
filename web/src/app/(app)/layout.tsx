import AuthGate from "@/components/AuthGate";
import BottomNav from "@/components/BottomNav";
import SyncProvider from "@/components/SyncProvider";

/** Layout untuk area terproteksi (butuh login) + bottom nav. */
export default function AppLayout({
  children,
}: {
  children: React.ReactNode;
}) {
  return (
    <AuthGate>
      <div className="mx-auto flex min-h-dvh w-full max-w-md md:max-w-2xl flex-col">
        <main className="flex-1 px-4 pb-28 pt-6">{children}</main>
        <BottomNav />
        <SyncProvider />
      </div>
    </AuthGate>
  );
}
